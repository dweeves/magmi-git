<?php

class CategoryImporter extends Magmi_ItemProcessor
{
    // Store map code => id
    protected $stores      = array();
    // Category cache build cache
    protected $_idcache    = array();
    // not used in base implementation
    protected $_catattr    = array();
    // (varchar|int) => assoc array (populated by initialize->getCatAttributeInfos)
    protected $_cattrinfos = array();
    // store_id => category root details (populated by initialize->initCats)
    protected $_catroots   = array();
    // website_id => array of store_id (populated by initialize->initCats)
    protected $_catrootw   = array();
    // entity type of category (set in initialize->initCats)
    protected $_cat_eid    = null;
    // category separator (default: '/')
    protected $_tsep;
    // tricky escaped separator that matches slugging separator
    protected $_escapedtsep = "---";

    /**
     * Initializes root categories and category eav attributes.
     */
    public function initialize($params)
    {
        $this->initStores();
        $this->initCats();
        $this->_cattrinfos = array(
            'varchar' => array('name'      => array(), 'url_key'   => array(), 'url_path'        => array()),
            'int'     => array('is_active' => array(), 'is_anchor' => array(), 'include_in_menu' => array())
        );
        foreach ($this->_cattrinfos as $catype => $attrlist) {
            foreach (array_keys($attrlist) as $catatt) {
                $this->_cattrinfos[$catype][$catatt] = $this->getCatAttributeInfos($catatt);
            }
        }
        $this->_tsep = $this->getParam("CAT:treesep", "/");
    }

    public function initStores()
    {
        $sql = 'SELECT store_id AS id, code FROM ' . $this->tablename('store');
        $stores = $this->selectAll($sql, null, 'code');
        $this->stores = array_reduce($stores, function ($result, $store) {
            $result[$store['code']] = $store['id'];
            return $result;
        }, array());
    }

    /**
     * Fetches category roots for store and website,
     * plus builds a website to stores map.
     */
    public function initCats()
    {

        $t = $this->tablename("catalog_category_entity");
        $csg = $this->tablename("store_group");
        $cs = $this->tablename("store");
        $ccev = $t . "_varchar";
        $ea = $this->tablename("eav_attribute");
        $result = $this->selectAll(
            "SELECT cs.store_id,csg.website_id,cce.path,ccev.value as name
								FROM $cs as cs
								JOIN $csg as csg on csg.group_id=cs.group_id
								JOIN $t as cce ON cce.entity_id=csg.root_category_id
								JOIN $ea as ea ON ea.attribute_code='name'
								JOIN $ccev as ccev ON ccev.attribute_id=ea.attribute_id AND ccev.entity_id=cce.entity_id
		 ");
        foreach ($result as $row) {
            $rootinfo = array("path"=>$row["path"],"name"=>$row["name"],
                "rootarr"=>explode("/", $row["path"]));
            $this->_catroots[$row["store_id"]] = $rootinfo;
            $this->_catrootw[$row["website_id"]][] = $row["store_id"];
            if ($this->_cat_eid == null) {
//                $this->_cat_eid = $row["entity_type_id"];
            }
        }
    }

    /**
     * Get attribute details for category entity
     */
    public function getCatAttributeInfos($attcode)
    {
        $t = $this->tablename("eav_attribute");
        $sql = "SELECT * FROM $t WHERE  attribute_code=?";

        $info = $this->selectAll($sql, array($attcode));
        return $info[0];
    }

    public function getCache($cdef, $bp)
    {
        $ck = "$bp::$cdef";
        return $this->_idcache[$ck];
    }

    public function isInCache($cdef, $bp)
    {
        $ck = "$bp::$cdef";
        return isset($this->_idcache[$ck]);
    }

    public function putInCache($cdef, $bp, $idarr)
    {
        $ck = "$bp::$cdef";
        $this->_idcache[$ck] = $idarr;
    }

    /* Interface method */
    public function getPluginInfo()
    {
        return array("name"=>"On the fly category creator/importer","author"=>"Dweeves","version"=>"0.2.5",
            "url"=>$this->pluginDocUrl("On_the_fly_category_creator/importer"));
    }

    /**
     * Gets existing category based on:
     * - name's attribute_id
     * - name value
     * - parent id
     *
     * @param Array  $parentpath  A list of parent categories from root to immediate parent
     * @param Array  $cattr       Category attributes from getCatAttributeInfos()
     *
     * @return int         ID of existing category
     */
    public function getExistingCategory($parentpath, $cattr)
    {
        $cet = $this->tablename("catalog_category_entity");
        $cetv = $this->tablename("catalog_category_entity_varchar");
        $parentid = array_pop($parentpath);
        $sql = "SELECT cet.entity_id FROM $cet as cet
              JOIN $cetv as cetv ON cetv.entity_id=cet.entity_id AND cetv.attribute_id=? AND cetv.value=?
              WHERE cet.parent_id=? ";
        $catid = $this->selectone($sql, array($this->_cattrinfos["varchar"]["name"]["attribute_id"], $cattr["name"], $parentid), "entity_id");
        return $catid;
    }

    /**
     * Gets category id by first checking existing otherwise
     * find it based on parents and siblings
     *
     * @param Array  $parentpath  A list of parent categories from root to immediate parent
     * @param Array  $cattr       Category attributes from getCatAttributeInfos()
     *
     * @return int         ID of existing category
     */
    public function getCategoryId($parentpath, $cattrs)
    {
        // Search for existing category and returns its ID of it exists
        $cattrs["name"] = str_replace($this->_escapedtsep, $this->_tsep, $cattrs["name"]);
        $catid = $this->getExistingCategory($parentpath, $cattrs);
        if ($catid != null) { return $catid; }

        // otherwise, get new category values from parent & siblings
        $cet = $this->tablename("catalog_category_entity");
        $path = implode("/", $parentpath);
        $parentid = array_pop($parentpath);
        // get child info using parent data
        $sql = "SELECT cce.attribute_set_id,cce.level+1 as level,COALESCE(MAX(eac.position),0)+1 as position
		FROM $cet as cce
		LEFT JOIN  $cet as eac ON eac.parent_id=cce.entity_id
		WHERE cce.entity_id=?
		GROUP BY eac.parent_id";
        $info = $this->selectAll($sql, array($parentid));
        $info = $info[0];
        // insert new category
        $sql = "INSERT INTO $cet 	(attribute_set_id,parent_id,position,level,path,children_count) VALUES (?,?,?,?,?,?)";
        // insert empty path until we get category id
        $data = array($info["attribute_set_id"],$parentid,$info["position"],$info["level"],"",0);
        // insert in db,get cat id
        $catid = $this->insert($sql, $data);

        unset($data);
        // set category path with inserted category id
        $sql = "UPDATE $cet SET path=?,created_at=NOW(),updated_at=NOW() WHERE entity_id=?";
        $data = array("$path/$catid",$catid);
        $this->update($sql, $data);
        unset($data);
        // set category attributes
        foreach ($this->_cattrinfos as $tp => $attinfo) {
            $inserts = array();
            $data = array();
            $tb = $this->tablename("catalog_category_entity_$tp");

            foreach ($attinfo as $attrcode => $attdata) {
                if (isset($attdata["attribute_id"])) {
                    $inserts[] = "(?,?,?,?)";
                    $data[] = $attdata["attribute_id"];
                    $data[] = 0; // store id 0 for categories
                    $data[] = $catid;
                    $data[] = $cattrs[$attrcode];
                }
            }

            $sql = "INSERT INTO $tb (attribute_id,store_id,entity_id,value) VALUES " .
                 implode(",", $inserts) . " ON DUPLICATE KEY UPDATE value=VALUES(`value`)";
            $this->insert($sql, $data);
            unset($data);
            unset($inserts);
        }
        return $catid;
    }

    /**
     * Extract attributes like is_anchor and include_in_menu
     *
     * @param String   &$catdef  A category string. Will be cleaned from options.
     *
     * @return Array             A list of category info
     */
    public function extractCatAttrs(&$catdef)
    {
        $cdefs   = explode($this->_tsep, $catdef);
        $odefs   = array();
        $s_odefs = array();
        $clist   = array();

        foreach ($cdefs as $cdef) {
            $parts   = explode("::", $cdef);
            $cname   = trim($parts[0]);
            $s_cname = $cname;
            $last    = array_pop($parts);
            // Check for storename::[defaultname] syntax
            if ($cname !== $last && stripos($last, '[') === 0) {
                $cname = trim($last, '[]');
            } else {
                // If not translation add $last back to array
                $parts[] = $last;
            }

            $cp = count($parts);

            $odefs[] = $cname;
            $s_odefs[] = $s_cname;

            $attrs = array(
                "name"            => $cname,
                "is_active"       => $cp > 1 ? $parts[1] : 1,
                "is_anchor"       => $cp > 2 ? $parts[2] : 1,
                "include_in_menu" => $cp > 3 ? $parts[3] : 1,
                "url_key"         => Slugger::slug($cname),
                "url_path"        => Slugger::slug(implode("/", $odefs), true) . $this->getParam("CAT:urlending", ".html"),
            );
            if ($cname !== $s_cname) {
                $attrs['translated_name']     = $s_cname;
                $attrs['translated_url_key']  = Slugger::slug($s_cname);
                $attrs['translated_url_path'] = Slugger::slug(implode('/', $s_odefs), true) . $this->getParam('CAT:urlending', '.html');
            }
            $clist[] = $attrs;
        }
        $catdef = implode($this->_tsep, $odefs);
        return $clist;
    }

    public function getCategoryIdsFromDef($pcatdef, $srdefs, $item)
    {
        $srp = "%RP:base%";
        foreach (array_keys($srdefs) as $tsrp) {
            // check which root we have
            if (substr($pcatdef, 0, strlen($tsrp)) == $tsrp) {
                $srp = $tsrp;
                break;
            }
        }
        // remove explicit root
        $pcatdef = str_replace($srp . $this->_tsep, "", $pcatdef);
        $zcatparts = explode($this->_tsep, $pcatdef);
        // cleaning parts (trimming, removing empty)
        $pcatparts = array();
        $czcatparts = count($zcatparts);
        for ($i = 0; $i < $czcatparts; $i++) {
            $cp = trim($zcatparts[$i]);
            if ($cp != "") {
                $pcatparts[] = $cp;
            }
        }

        $catparts = array();
        $catpos = array();

        // Build a position table to restore after cat ids will be created
        // basically removing position from category string, but keeping other options
        foreach ($pcatparts as $cp) {
            $a = explode("::", $cp);
            // Separte numeric options into array (is_active, is_anchored, include_in_menu, position)
            $options = array();
            if (count($a) > 1) {
                $options = array_filter($a, 'is_numeric');
                // The three first options are always used togehter
                // therefor position must be at the end as number 4
                // or the first and only one
                if (count($options) === 4 || count($options) === 1) {
                    $catpos[] = array_pop($options);
                } else {
                    $catpos[] = "0";
                }
            } else {
                $catpos[] = "0";
            }
            $translation_option = array_values(array_filter($a, function ($option) { return stripos($option, '[') === 0; }));
            $translation_option_part = count($translation_option) ? '::' . $translation_option[0] : '';
            $options_part = count($options) ? '::' . join('::', $options) : '';
            $catparts[] = $a[0] . $options_part . $translation_option_part;
        }
        // build a position free category def
        $catdef = implode($this->_tsep, $catparts);
        // if full def is in cache, use it
        if ($this->isInCache($catdef, $srp)) {
            $catids = $this->getCache($catdef, $srp);
        } else {
            // category ids
            $catids = array();
            $lastcached = array();

            // for each cat tree branch
            $pdef = array();
            foreach ($catparts as $catpart) {
                // ignore empty
                if ($catpart == "") {
                    continue;
                }
                // add it to the current tree level
                $pdef[] = $catpart;
                $ptest = implode($this->_tsep, $pdef);
                // test for tree level in cache
                if ($this->isInCache($ptest, $srp)) {
                    // if yes , set current known cat ids to corresponding cached branch
                    $catids = $this->getCache($ptest, $srp);
                    // store last cached branch
                    $lastcached = $pdef;
                } else {
                    // no more tree info in cache, stop further retrieval, we need to create missing levels
                    break;
                }
            }

            // path as array , basepath is always "/" separated
            $basearr = explode("/", $srdefs[$srp]["path"]);
            // add store tree root to category path
            $curpath = array_merge($basearr, $catids);
            // get categories attributes
            $catattributes = $this->extractCatAttrs(/*ref*/$catdef);
            $ccatids = count($catids);
            $ccatparts = count($catparts);
            // iterate on missing levels.
            for ($i = $ccatids; $i < $ccatparts; $i++) {
                if ($catparts[$i] == "") {
                    continue;
                }
                // retrieve category id (by creating it if needed from categories attributes)
                $catid = $this->getCategoryId($curpath, $catattributes[$i]);
                // add translation
                $this->addTranslationForCategory($catid, $catattributes[$i], $item);
                // add newly created level to item category ids
                $catids[] = $catid;
                // add newly created level to current paths
                $curpath[] = $catid;
                // cache newly created levels
                $lastcached[] = $catparts[$i];
                $this->putInCache(implode($this->_tsep, $lastcached), $srp, $catids);
            }
        }
        $ccatparts = count($catparts);
        // added position handling
        for ($i = 0; $i < $ccatparts; $i++) {
            $catids[$i] .= "::" . $catpos[$i];
        }

        return $catids;
    }

    public function addTranslationForCategory($id, $attributes, $item)
    {
        if (!isset($attributes['translated_name'])) return ;
        // Convert store codes to ids excluding admin
        $store_ids = array_map(
            function ($store) { return $this->stores[$store]; },
            array_filter(
                explode(',', $item['store']),
                function ($store) { return $store !== 'admin'; }
            )
        );
        if (empty($store_ids)) return ;

        $attributes['name']     =  $attributes['translated_name'];
        $attributes['url_key']  =  $attributes['translated_url_key'];
        $attributes['url_path'] =  $attributes['translated_url_path'];
        foreach ($store_ids as $store_id) {
            $this->updateOrCreateAttributes($id, $attributes, $store_id);
        }
    }

    public function updateOrCreateAttributes($category_id, $attributes, $store_id = 0)
    {
        foreach ($this->_cattrinfos as $tp => $attinfo) {
            $placeholders = array();
            $values       = array();
            $table        = $this->tablename("catalog_category_entity_$tp");

            foreach ($attinfo as $attrcode => $attdata) {
                if (isset($attdata['attribute_id'])) {
                    $placeholders[] = '(?,?,?,?)';
                    $values[] = $attdata['attribute_id'];
                    $values[] = $store_id;
                    $values[] = $category_id;
                    $values[] = $attributes[$attrcode];
                }
            }
            $sql = "INSERT INTO $table (attribute_id,store_id,entity_id,value) VALUES " .
                 implode(",", $placeholders) . " ON DUPLICATE KEY UPDATE value=VALUES(`value`)";
            $this->insert($sql, $values);


        }
    }

    /**
     * Interface method: Ensures that the column "category_ids" is a column.
     */
    public function processColumnList(&$cols, $params)
    {
        $cols[] = "category_ids";
        $cols = array_unique($cols);
        return true;
    }

    /**
     * Returns an array of root paths with keys like:
     *
     *   %RP:<id>%
     *   or %RP:base% if no explicit root is specified
     *
     * The values is what the content of $this->_catroots based on store
     *
     * @param Array  &$item  A row. The categories value is updated with key when using explicit roots
     *
     * @return Array         An array of roots and/or errors
     */
    public function getStoreRootPaths(&$item)
    {
        $rootpaths = array();
        $sids = $this->getItemStoreIds($item, 2);
        //$trimroot = "";
        // remove admin from store ids (no category root on it)
        if ($sids[0] == 0) {
            array_shift($sids);
        }
        // only admin store set,use websites store roots
        if (count($sids) == 0) {
            $wsids = $this->getItemWebsites($item);
            foreach ($wsids as $wsid) {
                $sids = array_merge($sids, $this->_catrootw[$wsid]);
            }
        }
        $rootpaths["__error__"] = array();
        // If using explicit root assignment , identify which root it is
        if (preg_match_all("|(?<!::)\[(.*?)\]|", $item["categories"], $matches)) {
            $cm1 = count($matches[1]);
            // for each found explicit root
            for ($i = 0; $i < $cm1; $i++) {
                // test store matching
                foreach ($sids as $sid) {
                    $srp = $this->_catroots[$sid];
                    $rname = $matches[1][$i];
                    $cmatch = (trim($rname) == $srp["name"]);
                    // found a store match
                    if ($cmatch) {
                        // set a specific store key
                        $k = "%RP:$sid%";
                        // store root path definitions
                        $rootpaths[$k] = array("path"=>$srp["path"],"rootarr"=>$srp["rootarr"]);
                        //$trimroot = trim($rname);
                        // replace root name with store root key
                        $item["categories"] = str_replace($matches[0][$i], $k, $item["categories"]);
                        break;
                    }
                }
            }
            // now finding unmatched replaces
        }
        if (preg_match_all("|(?<!::)\[(.*?)\]|", $item["categories"], $matches)) {
            $cm1 = count($matches[1]);

            for ($i = 0; $i < $cm1; $i++) {
                $rootpaths['__error__'] = $matches[1];
            }
        }
        $sids = array_keys($this->_catroots);
        $srp = $this->_catroots[$sids[0]];
        $rootpaths["%RP:base%"] = array("path"=>$srp["path"],"rootarr"=>$srp["rootarr"]);

        return $rootpaths;
    }

    public function processEscaping($icats)
    {
        return str_replace("\\" . $this->_tsep, $this->_escapedtsep, $icats);
    }

    public function processItemAfterId(&$item, $params = null)
    {
        if (isset($item["categories"])) {
            // handle escaping
            $icats = $this->processEscaping($item["categories"]);
            // first apply category root on each category

            $root = $this->getParam("CAT:baseroot", "");
            if ($root != "") {
                $catlist = explode(";;", $icats);
                $ccatlist = count($catlist);
                for ($i = 0; $i < $ccatlist; $i++) {
                    if (trim($catlist[$i]) != "") {
                        $catlist[$i] = $root . $this->_tsep . $catlist[$i];
                    }
                }
                // recompose rooted categories
                $item["categories"] = implode(";;", $catlist);
            }
            // get store root category paths, this may modify categories !!!!!
            $rootpaths = $this->getStoreRootPaths($item);

            // process escaping at the end
            $icats = $this->processEscaping($item["categories"]);

            // Return early if there are errors
            if (count($rootpaths["__error__"]) > 0) {
                $this->log("Cannot find site root with names : " . implode(",", $rootpaths["__error__"]), "error");
                return false;
            }
            // unset error if empty
            unset($rootpaths["__error__"]);

            // categories may have been changed, use escaping
            $catlist = explode(";;", $icats);
            $catids = array();
            foreach ($catlist as $catdef) {
                $cdef = $this->getCategoryIdsFromDef($catdef, $rootpaths, $item);
                if ($this->getParam("CAT:lastonly", 0) == 1) {
                    $cdef = array($cdef[count($cdef) - 1]);
                }
                $catids = array_unique(array_merge($catids, $cdef));
            }

            // assign to category roots
            if ($this->getParam("CAT:lastonly", 0) == 0) {
                foreach ($rootpaths as $base=>$ra) {
                    //find root lenght
                    $bl=strlen($base);
                    //for each part of category list to include upwards , match up to local root
                    foreach ($catlist as $catdef) {
                        if (substr($catdef, 0, $bl)==$base) {
                            $rootpath=$ra['rootarr'];
                            array_shift($rootpath);
                            $catids= array_merge($catids, $rootpath);
                        }
                    }
                }
            }
            $catids = array_unique($catids);
            $item["category_ids"] = implode(",", $catids);
        }
        return true;
    }

    public function getPluginParamNames()
    {
        return array('CAT:baseroot','CAT:lastonly','CAT:urlending','CAT:treesep');
    }

    public function afterImport()
    {
        $this->log("Updating Category children count....", "info");
        // automatically update all children_count for catalog categories
        $cce = $this->tablename("catalog_category_entity");
        $sql = "UPDATE  $cce as cce
        LEFT JOIN
            (SELECT s1.entity_id as cid, COALESCE( COUNT( s2.entity_id ) , 0 ) AS cnt
                FROM $cce AS s1
                LEFT JOIN $cce AS s2 ON s2.parent_id = s1.entity_id
            GROUP BY s1.entity_id) as sq ON sq.cid=cce.entity_id
            SET cce.children_count=sq.cnt";
        $this->update($sql);
        return true;
    }
}
