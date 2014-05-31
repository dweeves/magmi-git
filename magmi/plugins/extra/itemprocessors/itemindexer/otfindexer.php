<?php

class ItemIndexer extends Magmi_ItemProcessor
{
    protected $_toindex;
    protected $tns;
    protected $visinf;
    protected $catpaths;

    public function getPluginInfo()
    {
        return array("name"=>"On the fly indexer","author"=>"Dweeves","version"=>"0.1.6",
            "url"=>$this->pluginDocUrl("On_the_fly_indexer"));
    }

    public function getPluginParamNames()
    {
        return array("OTFI:urlending","OTFI:usecatinurl");
    }

    public function initialize($params)
    {
        $this->_toindex = null;
        // initialize shortname array for tables
        $this->tns = array("cpe"=>$this->tablename("catalog_product_entity"),
            "cce"=>$this->tablename("catalog_category_entity"),"ccp"=>$this->tablename("catalog_category_product"),
            "cpw"=>$this->tablename("catalog_product_website"),"cs"=>$this->tablename("core_store"),
            "csg"=>$this->tablename("core_store_group"),"cpev"=>$this->tablename("catalog_product_entity_varchar"),
            "cpei"=>$this->tablename("catalog_product_entity_int"),
            "ccev"=>$this->tablename("catalog_category_entity_varchar"),"ea"=>$this->tablename("eav_attribute"),
            "ccpi"=>$this->tablename("catalog_category_product_index"),"curw"=>$this->tablename("core_url_rewrite"));
        $inf = $this->getAttrInfo("visibility");
        if ($inf == null)
        {
            $this->initAttrInfos(array("visibility"));
            $inf = $this->getAttrInfo("visibility");
        }
        $this->visinf = $inf;
    }

    /**
     * Return item category ids from PID (full tree)
     *
     * @param $baselevel :
     *            begin tree from specified category level (defaults 0)
     * @return array of category ids (all mixed branches) for the item
     *         *
     */
    public function getItemCategoryIds($pid)
    {
        $sql = "SELECT cce.path FROM {$this->tns["ccp"]} as ccp 
		JOIN {$this->tns["cce"]} as cce ON ccp.category_id=cce.entity_id
		WHERE ccp.product_id=?";
        $result = $this->selectAll($sql, $pid);
        $catidlist = array();
        foreach ($result as $row)
        {
            $catidlist = array_merge($catidlist, explode("/", $row["path"]));
        }
        $catidlist = array_unique($catidlist);
        sort($catidlist);
        return $catidlist;
    }

    /**
     * Return item category paths per store from PID
     * *
     */
    public function getItemCategoryPaths($pid)
    {
        $sql = "SELECT cce.path as cpath,SUBSTR(cce.path,LOCATE('/',cce.path,3)+1) as cshortpath,csg.default_store_id as store_id,cce.entity_id as catid
			  FROM {$this->tns["ccp"]} as ccp 
			  JOIN {$this->tns["cce"]} as cce ON cce.entity_id=ccp.category_id 
			  JOIN {$this->tns["csg"]} as csg ON csg.root_category_id=SUBSTR(SUBSTRING_INDEX(cce.path,'/',2),LOCATE('/',SUBSTRING_INDEX(cce.path,'/',2))+1)
			  WHERE ccp.product_id=?";
        $result = $this->selectAll($sql, $pid);
        $cpaths = array();
        foreach ($result as $row)
        {
            $sid = $row["store_id"];
            if (!isset($cpaths[$sid]))
            {
                $cpaths[$sid] = array();
            }
            $cpaths[$sid][] = $row;
        }
        unset($result);
        return $cpaths;
    }

    /**
     * Build catalog_category_product_index entry for given pid
     *
     * @param int $pid
     *            , product id to create index entry for
     */
    public function buildCatalogCategoryProductIndex($pid)
    {
        $catidlist = $this->getItemCategoryIds($pid);
        if (count($catidlist) == 0)
        {
            return;
        }
        array_shift($catidlist);
        // get all category ids on which the product is affected
        
        // let's make a IN placeholder string with that
        $catidin = $this->arr2values($catidlist);
        // first delete lines where last inserted product was
        $sql = "DELETE FROM {$this->tns["ccpi"]} WHERE product_id=?";
        $this->delete($sql, $pid);
        // then add lines for index
        $sqlsel = "INSERT IGNORE INTO {$this->tns["ccpi"]} 
				 SELECT cce.entity_id as category_id,ccp.product_id,ccp.position,IF(cce.entity_id=ccp.category_id,1,0) as is_parent,csg.default_store_id,cpei.value as visibility 
				 FROM {$this->tns["ccp"]} as ccp
				 JOIN {$this->tns["cpe"]} as cpe ON ccp.product_id=cpe.entity_id
				 JOIN {$this->tns["cpei"]} as cpei ON cpei.attribute_id=? AND cpei.entity_id=cpe.entity_id
				 JOIN {$this->tns["cce"]} as cce ON cce.entity_id IN ($catidin)
				 JOIN {$this->tns["csg"]} as csg ON csg.root_category_id=SUBSTR(SUBSTRING_INDEX(cce.path,'/',2),LOCATE('/',SUBSTRING_INDEX(cce.path,'/',2))+1)
				 WHERE ccp.product_id=?
	    		 ORDER by is_parent DESC,csg.default_store_id,cce.entity_id";
        // build data array for request
        $data = array_merge(array($this->visinf["attribute_id"]), $catidlist, array($pid));
        // create index line(s)
        $this->insert($sqlsel, $data);
    }

    /**
     * Build core_url_rewrite index entry for given pid
     *
     * @param int $pid
     *            , product id to create index entry for
     */
    public function buildUrlCatProdRewrite($pid, $purlk)
    {
        $catpathlist = $this->getItemCategoryPaths($pid);
        $data = array();
        $vstr = array();
        // now we have catpaths per store
        $cnames = array();
        foreach ($catpathlist as $storeid => $paths)
        {
            $catids = array();
            
            foreach ($paths as $pathinfo)
            {
                $catids = array_unique(array_merge($catids, explode("/", $pathinfo["cshortpath"])));
            }
            
            $catin = $this->arr2values($catids);
            
            // use tricky double join on eav_attribute to find category related 'name' attribute using 'children' category only attr to distinguish on category entity_id
            $sql = "SELECT cce.entity_id as catid,COALESCE(ccev.value,ccevd.value) as value 
				FROM {$this->tns["cce"]} as cce 
			  	JOIN {$this->tns["ea"]} as ea1 ON ea1.attribute_code='children'
			 	JOIN {$this->tns["ea"]} as ea2 ON ea2.attribute_code ='name' AND ea2.entity_type_id=ea1.entity_type_id
			  	JOIN {$this->tns["ccev"]} as ccevd ON ccevd.attribute_id=ea2.attribute_id AND ccevd.entity_id=cce.entity_id AND ccevd.store_id=0
			  	LEFT JOIN {$this->tns["ccev"]} as ccev ON ccev.attribute_id=ea2.attribute_id AND ccev.entity_id=cce.entity_id AND ccev.store_id=?
			  	WHERE cce.entity_id IN ($catin)
			  	GROUP BY cce.entity_id";
            $result = $this->selectAll($sql, array_merge(array($storeid), $catids));
            
            // iterate on all names
            foreach ($result as $row)
            {
                $catid = $row["catid"];
                $cnames[$catid] = $row["value"];
            }
            
            foreach ($paths as $pinfo)
            {
                $sp = $pinfo["cshortpath"];
                $cpids = explode("/", $sp);
                $names = array();
                foreach ($cpids as $cpid)
                {
                    if (isset($cnames[$cpid]))
                    {
                        $names[] = $cnames[$cpid];
                    }
                }
                // make string with that
                $namestr = implode("/", $names);
                // build category url key (allow / in slugging)
                $curlk = Slugger::slug($namestr, true);
                
                // product + category url entries request
                $catid = $pinfo["catid"];
                $sdata = array($pid,$storeid,$catid,"product/$pid/$catid",
                    "catalog/product/view/id/$pid/category/$catid","$curlk/$purlk",1);
                $vstr[] = "(" . $this->arr2values($sdata) . ")";
                $data = array_merge($data, $sdata);
            }
        }
        if (count($vstr) > 0)
        {
            $sqlprodcat = "INSERT IGNORE INTO {$this->tns["curw"]} (product_id,store_id,category_id,id_path,target_path,request_path,is_system) VALUES " .
                 implode(",", $vstr);
            $this->insert($sqlprodcat, $data);
        }
        if (count($catpathlist) > 0)
        {
            // now insert category url rewrite
            $this->buildCatUrlRewrite($catpathlist, $cnames);
        }
        unset($data);
        unset($sdata);
        unset($vstr);
    }

    public function buildCatUrlRewrite($catpathlist, $cnames)
    {
        $vstr = array();
        $data = array();
        foreach ($catpathlist as $storeid => $paths)
        {
            foreach ($paths as $pinfo)
            {
                $sp = $pinfo["cshortpath"];
                $cpids = explode("/", $sp);
                $names = array();
                foreach ($cpids as $cpid)
                {
                    if (isset($cnames[$cpid]))
                    {
                        $names[] = $cnames[$cpid];
                        // make string with that
                        $namestr = implode("/", $names);
                        $urlend = $this->getParam("OTFI:urlending", ".html");
                        // build category url key (allow / in slugging)
                        $curlk = Slugger::slug($namestr, true) . $urlend;
                        $cdata = array($storeid,$cpid,"category/$cpid","catalog/category/view/id/$cpid","$curlk",1);
                        $vstr[] = "(" . $this->arr2values($cdata) . ")";
                        $data = array_merge($data, $cdata);
                    }
                }
            }
        }
        if (count($vstr) > 0)
        {
            $sqlcat = "INSERT INTO {$this->tns["curw"]} (store_id,category_id,id_path,target_path,request_path,is_system) VALUES " .
                 implode(",", $vstr) . " ON DUPLICATE KEY UPDATE request_path=VALUES(`request_path`)";
            $this->insert($sqlcat, $data);
        }
    }

    public function builProductUrlRewrite($pid)
    {
        $sql = "SELECT ea.attribute_code,cpei.value,cpev.attribute_id,cpev.value 
			  FROM {$this->tns["cpe"]} AS cpe
			  JOIN {$this->tns["ea"]} as ea ON ea.attribute_code IN ('url_key','name')
			  JOIN {$this->tns["cpev"]} as cpev ON cpev.entity_id=cpe.entity_id AND cpev.attribute_id=ea.attribute_id
			  JOIN {$this->tns["cpei"]} as cpei ON cpei.entity_id=cpe.entity_id AND cpei.attribute_id=? AND cpei.value>1
			  WHERE cpe.entity_id=?";
        $result = $this->selectAll($sql, array($this->visinf["attribute_id"],$pid));
        // nothing to build, product is not visible,return
        if (count($result) == 0)
        {
            return;
        }
        // see what we get as available product attributes
        foreach ($result as $row)
        {
            if ($row["attribute_code"] == "url_key")
            {
                $pburlk = nullifempty($row["value"]);
            }
            if ($row["attribute_code"] == "name")
            {
                $pname = $row["value"];
            }
        }
        // if we've got an url key use it, otherwise , make a slug from the product name as url key
        $urlend = $this->getParam("OTFI:urlending", ".html");
        $purlk = (isset($pburlk) ? $pburlk : Slugger::slug($pname)) . $urlend;
        
        // delete old "system" url rewrite entries for product
        $sql = "DELETE FROM {$this->tns["curw"]} WHERE product_id=? AND is_system=1";
        $this->delete($sql, $pid);
        
        // product url index info
        $produrlsql = "SELECT cpe.entity_id,cs.store_id,
				 CONCAT('product/',cpe.entity_id) as id_path,
				 CONCAT('catalog/product/view/id/',cpe.entity_id) as target_path,
				 ? AS request_path,
				 1 as is_system
				 FROM {$this->tns["cpe"]} as cpe
				 JOIN {$this->tns["cpw"]} as cpw ON cpw.product_id=cpe.entity_id
				 JOIN {$this->tns["cs"]} as cs ON cs.website_id=cpw.website_id
				 JOIN {$this->tns["ccp"]} as ccp ON ccp.product_id=cpe.entity_id
				 JOIN {$this->tns["cce"]} as cce ON ccp.category_id=cce.entity_id
				 WHERE cpe.entity_id=?";
        
        // insert lines
        $sqlprod = "INSERT INTO {$this->tns["curw"]} (product_id,store_id,id_path,target_path,request_path,is_system) $produrlsql ON DUPLICATE KEY UPDATE request_path=VALUES(`request_path`)";
        $this->insert($sqlprod, array($purlk,$pid));
        return $purlk;
    }

    public function buildUrlRewrite($pid)
    {
        $purlk = $this->builProductUrlRewrite($pid);
        if ($this->getParam("OTFI:usecatinurl"))
        {
            $this->buildUrlCatProdRewrite($pid, $purlk);
        }
    }

    /**
     * OBSOLETED , TO BE REWORKED LATER
     */
    public function buildPriceIndex($pid)
    {
        $priceidx = $this->tablename("catalog_product_index_price");
        $pet = $this->getProductEntityType();
        $sql = "DELETE FROM $priceidx WHERE entity_id=?";
        $this->delete($sql, $pid);
        $cpe = $this->tablename("catalog_product_entity");
        $cs = $this->tablename("core_store");
        $cg = $this->tablename("customer_group");
        $cped = $this->tablename("catalog_product_entity_decimal");
        $ea = $this->tablename("eav_attribute");
        $cpetp = $this->tablename("catalog_product_entity_tier_price");
        $cpei = $this->tablename("catalog_product_entity_int");
        $sql = "INSERT INTO $priceidx SELECT cped.entity_id,
											cg.customer_group_id,
											cs.website_id,
											cpei.value as tax_class_id,
											cped.value as price,
											MIN(cped.value) as final_price,
											MIN(cped.value) as min_price,
											MIN(cped.value) as max_price,
											cpetp2.value as tier_price
				FROM $cpe as cpe 
				JOIN $cs as cs ON cs.store_id!=0
				JOIN $cped as cped ON cped.store_id=cs.store_id AND cped.entity_id=cpe.entity_id
				JOIN $cg as cg
				JOIN $ea as ead ON ead.entity_type_id=?  AND ead.attribute_code IN('price','special_price','minimal_price') AND cped.attribute_id=ead.attribute_id 
				JOIN $ea as eai ON eai.entity_type_id=ead.entity_type_id AND eai.attribute_code='tax_class_id' 
				LEFT JOIN $cpetp as cpetp ON cpetp.entity_id=cped.entity_id 
				LEFT JOIN $cpetp as cpetp2 ON cpetp2.entity_id=cped.entity_id AND cpetp2.customer_group_id=cg.customer_group_id
				LEFT JOIN $cpei as cpei ON cpei.entity_id=cpe.entity_id AND cpei.attribute_id=eai.attribute_id 
				WHERE cpe.entity_id=?
				GROUP by cs.website_id,cg.customer_group_id
				ORDER by cg.customer_group_id,cs.website_id
		";
        $this->insert($sql, array($pet,$pid));
    }
    
    // To be done, find a way to avoid reindexing if not necessary
    public function shouldReindex($item)
    {
        return count($item) > 0;
    }

    public function processItemAfterImport(&$item, $params = null)
    {
        if (count($item) > 0)
        {
            $this->reindexLastImported();
            // if current item is not the same than previous one
            if ($params["same"] == false)
            {
                if ($this->shouldReindex($item))
                {
                    $this->_toindex = array("sku"=>$item["sku"],"pid"=>$params["product_id"]);
                }
                else
                {
                    $this->log("Do not reindex, no indexed column changed");
                }
            }
        }
        return true;
    }
    
    // index last imported item
    public function reindexLastImported()
    {
        if ($this->_toindex != null)
        {
            $pid = $this->_toindex["pid"];
            $this->buildCatalogCategoryProductIndex($pid);
            $this->buildUrlRewrite($pid);
            $this->_toindex = null;
        }
    }

    public function afterImport()
    {
        // reindex last item since we index one row later than the current
        $this->reindexLastImported();
    }
}


