<?php

/**
 * MAGENTO MASS IMPORTER CLASS
 *
 * version : 1.7.8
 * author : S.BRACQUEMONT aka dweeves
 * updated : 2010-10-09
 *
 */
require_once(dirname(__DIR__)."/inc/magmi_defs.php");
/* use external file for db helper */
require_once ("magmi_engine.php");
require_once ("magmi_valueparser.php");

/**
 *
 *
 * Magmi Product Import engine class
 * This class handle product import
 *
 * @author dweeves
 *        
 */
class Magmi_ProductImportEngine extends Magmi_Engine
{
    //attribute info cache
    public $attrinfo = array();
    //attribute info by type
    public $attrbytype = array();
    //attribute set cache
    public $attribute_sets = array();
    //product entity type
    public $prod_etype;
    //default attribute set id
    public $default_asid;
    //store id cache
    public $sidcache = array();
    //default mode
    public $mode = "update";
    //cache for column names that are not attributes
    private $_notattribs = array();
    //list of attribute handlers
    private $_attributehandlers;
    //current import row
    private $_current_row;
    //option id cache for select/multiselect
    private $_optidcache = null;
    //current item ids 
    private $_curitemids = array("sku"=>null);
    //default store list to impact
    private $_dstore = array();
    //same flag if current import line is referencing same item than the previous one
    private $_same;
    //extra attributes to create
    private $_extra_attrs;
    //current import profile
    private $_profile;
    //Store ids cache for website scoped attributes
    private $_sid_wsscope = array();
    //Store ids cache for store scope attributes
    private $_sid_sscope = array();
    //magento product table columns list
    private $_prodcols = array();
    //magento stock related table columns list
    private $_stockcols = array();
    //stats 
    private $_skustats = array();
    //handlers cache
    private  $_handlercache=array();

    /**
     * Constructor
     * add default attribute processor
     */
    public function __construct()
    {
        parent::__construct();
        $this->setBuiltinPluginClasses("itemprocessors", 
            MAGMI_PLUGIN_DIR .
            '/inc/magmi_defaultattributehandler.php::Magmi_DefaultAttributeItemProcessor');
    }

    public function getSkuStats()
    {
        return $this->_skustats;
    }

    /**
     * (non-PHPdoc)
     *
     * @see Magmi_Engine::getEngineInfo()
     */
    public function getEngineInfo()
    {
        return array("name"=>"Magmi Product Import Engine","version"=>"1.9","author"=>"dweeves");
    }

    /**
     * load properties
     *
     * @param string $conf
     *            : configuration .ini filename
     */
    public function initProdType()
    {
        $tname = $this->tablename("eav_entity_type");
        $this->prod_etype = $this->selectone("SELECT entity_type_id FROM $tname WHERE entity_type_code=?", 
            "catalog_product", "entity_type_id");
        $this->default_asid = $this->getAttributeSetId('Default');
    }

    public function getPluginFamilies()
    {
        return array("datasources","general","itemprocessors");
    }

    public function registerAttributeHandler($ahinst, $attdeflist)
    {
        foreach ($attdeflist as $attdef)
        {
            $ad = explode(":", $attdef);
            if (count($ad) != 2)
            {
                $this->log("Invalid registration string ($attdef) :" . get_class($ahinst), "warning");
            }
            else
            {
                $this->_attributehandlers[$attdef] = $ahinst;
            }
        }
    }

    /**
     *
     * Return list of store codes that share the same website than the stores passed as parameter
     *
     * @param string $scodes
     *            comma separated list of store view codes
     */
    public function getStoreIdsForWebsiteScope($scodes)
    {
        if (!isset($this->_sid_wsscope[$scodes]))
        {
            $this->_sid_wsscope[$scodes] = array();
            $wscarr = csl2arr($scodes);
            $qcolstr = $this->arr2values($wscarr);
            $cs = $this->tablename("core_store");
            $sql = "SELECT csdep.store_id FROM $cs as csmain 
				 JOIN $cs as csdep ON csdep.website_id=csmain.website_id
				 WHERE csmain.code IN ($qcolstr) ";
            $sidrows = $this->selectAll($sql, $wscarr);
            foreach ($sidrows as $sidrow)
            {
                $this->_sid_wsscope[$scodes][] = $sidrow["store_id"];
            }
        }
        return $this->_sid_wsscope[$scodes];
    }

    /**
     * Returns the list of store ids corresponding to the store view codes 
     * @param unknown $scodes
     * @return multitype:
     */
    public function getStoreIdsForStoreScope($scodes)
    {
        if (!isset($this->_sid_sscope[$scodes]))
        {
            $this->_sid_sscope[$scodes] = array();
            $scarr = csl2arr($scodes);
            $qcolstr = $this->arr2values($scarr);
            $cs = $this->tablename("core_store");
            $sql = "SELECT csmain.store_id from $cs as csmain WHERE csmain.code IN ($qcolstr)";
            $sidrows = $this->selectAll($sql, $scarr);
            foreach ($sidrows as $sidrow)
            {
                $this->_sid_sscope[$scodes][] = $sidrow["store_id"];
            }
        }
        return $this->_sid_sscope[$scodes];
    }
    
    /**
     * Returns Magento current data for given item
     * @param unknown $item : item to get magento data from
     * @param unknown $params : item metadata
     * @param string $cols : columns list to return (if not set, all items column list)
     * @return Ambigous <multitype:, multitype:unknown >
     */
    public function getMagentoData($item, $params, $cols = null)
    {
        // out data
        $out = array();
        // if no specific columns set, use all item keys as base
        if ($cols == null)
        {
            $cols = array_keys($item);
        }
        $this->initAttrInfos($cols);
        // cross with defined attributes
        $attrkeys = array_intersect($cols, array_keys($this->attrinfo));
        // Create several maps:
        // 1 per backend type => 1 request per backend type
        // 1 to retrieve attribute code from attribute id (avoid a join since we already have the map)
        $bta = array();
        $idcodemap = array();
        
        // Handle atribute retrieval
        
        foreach ($attrkeys as $k)
        {
            $attrdata = $this->attrinfo[$k];
            $bt = "" . $attrdata["backend_type"];
            if ($bt != "static")
            {
                $attid = $attrdata["attribute_id"];
                if (!isset($bta[$bt]))
                {
                    $bta[$bt] = array();
                }
                $bta[$bt][] = $attid;
                $idcodemap[$attid] = $k;
            }
        }
        
        // Peform SQL "by type"
        foreach (array_keys($bta) as $bt)
        {
            $cpet = $this->tablename("catalog_product_entity_$bt");
            $storeids = $this->getItemStoreIds($item);
            $sid = $storeids[0];
            $sql = "SELECT attribute_id,value FROM $cpet WHERE entity_id=? AND store_id=? AND attribute_id IN (" .
                 $this->arr2values($bta[$bt]) . ")";
            $tdata = $this->selectAll($sql, array_merge(array($params["product_id"],$sid), $bta[$bt]));
            foreach ($tdata as $row)
            {
                $out[$idcodemap[$row["attribute_id"]]] = $row["value"];
            }
            unset($tdata);
        }
        
        // Check for qty attributes
        $scols = array_intersect($cols, $this->getStockCols());
        $sql = "SELECT " . implode(",", $scols) . " FROM " . $this->tablename("cataloginventory_stock_item") .
             " WHERE product_id=?";
        $tdata = $this->selectAll($sql, array($params["product_id"]));
        if (count($tdata) > 0)
        {
            $out = array_merge($out, $tdata[0]);
        }
        
        unset($idcodemap);
        unset($bta);
        return $out;
    }

    /**
     * returns execution mode
     */
    public function getMode()
    {
        return $this->mode;
    }
    
    /**
     * Adds an extra attribute to process
     * Useful for some plugins if generating attribute values that are not in initial scanned list
     * @param unknown $attr attribute code
     */
    public function addExtraAttribute($attr)
    {
        $attinfo = $this->attrinfo[$attr];
        $this->_extra_attrs[$attinfo["backend_type"]]["data"][] = $attinfo;
    }
    
    /**
     * Returns the list of magento base product table columns
     * @return multitype:
     */
    public function getProdCols()
    {
        if (count($this->_prodcols) == 0)
        {
            $sql = 'DESCRIBE ' . $this->tablename('catalog_product_entity');
            $rows = $this->selectAll($sql);
            foreach ($rows as $row)
            {
                $this->_prodcols[] = $row['Field'];
            }
        }
        return $this->_prodcols;
    }

    /**
     * Returns the list of magento product item stock info table columns
     * @return multitype:
     */
    public function getStockCols()
    {
        if (count($this->_stockcols) == 0)
        {
            $sql = 'DESCRIBE ' . $this->tablename('cataloginventory_stock_item');
            $rows = $this->selectAll($sql);
            foreach ($rows as $row)
            {
                $this->_stockcols[] = $row['Field'];
            }
        }
        return $this->_stockcols;
    }

    /**
     * Initialize attribute infos to be used during import
     *
     * @param array $cols
     *            : array of attribute names
     */
    public function checkRequired($cols)
    {
        $eav_attr = $this->tablename("eav_attribute");
        $sql = "SELECT attribute_code FROM $eav_attr WHERE  is_required=1
		AND frontend_input!='' AND frontend_label!='' AND entity_type_id=?";
        $required = $this->selectAll($sql, $this->prod_etype);
        $reqcols = array();
        foreach ($required as $line)
        {
            $reqcols[] = $line["attribute_code"];
        }
        $required = array_diff($reqcols, $cols);
        return $required;
    }

    public function checkAttributeInfo($attrinf)
    {
        $smodel=$attrinf['source_model'];
        $finp=$attrinf['frontend_input'];
        $bt=$attrinf['backend_type'];
        $user=$attrinf['is_user_defined'];
        //checking specific extension custom model for selects that might not respect magento default model
        if($user==1 && $bt=='int' && $finp=='select' && isset($smodel) && $smodel!="eav/entity_attribute_source_table")
        {
              $this->log("Potential assignment problem, specific model found for select attribute => ".$attrinf['attribute_code']."($smodel)","warning");
        }

    }
    /**
     *
     * gets attribute metadata from DB and put it in attribute metadata caches
     *
     * @param array $cols
     *            list of attribute codes to get metadata from
     *            if in this list, some values are not attribute code, no metadata will be cached.
     */
    public function initAttrInfos($cols)
    {
        if ($this->prod_etype == null)
        {
            // Find product entity type
            $tname = $this->tablename("eav_entity_type");
            $this->prod_etype = $this->selectone("SELECT entity_type_id FROM $tname WHERE entity_type_code=?", 
                "catalog_product", "entity_type_id");
        }
        
        // remove from candidates, those which we already know are not attributes
        $candidates = array_diff($cols, $this->_notattribs);
        // remove from candidates already known attributes
        $candidates = array_diff($candidates, array_keys($this->attrinfo));
        // now we have a count of "unknown columns" that are potential attributes
        $toscan = array_values($candidates);
        if (count($toscan) > 0)
        {
            // create statement parameter string ?,?,?.....
            $qcolstr = $this->arr2values($toscan);
            
            $tname = $this->tablename("eav_attribute");
            if ($this->getMagentoVersion() != "1.3.x")
            {
                $extra = $this->tablename("catalog_eav_attribute");
                // SQL for selecting attribute properties for all wanted attributes
                $sql = "SELECT `$tname`.*,$extra.* FROM `$tname`
				LEFT JOIN $extra ON $tname.attribute_id=$extra.attribute_id
				WHERE  ($tname.attribute_code IN ($qcolstr)) AND (entity_type_id=?)";
            }
            else
            {
                $sql = "SELECT `$tname`.* FROM `$tname` WHERE ($tname.attribute_code IN ($qcolstr)) AND (entity_type_id=?)";
            }
            $toscan[] = $this->prod_etype;
            $result = $this->selectAll($sql, $toscan);
            
            $attrinfs = array();
            // create an attribute code based array for the wanted columns
            foreach ($result as $r)
            {
                $attrinfs[$r["attribute_code"]] = $r;
            }
            unset($result);

            //check specific select info with custom model



            // create a backend_type based array for the wanted columns
            // this will greatly help for optimizing inserts when creating attributes
            // since eav_ model for attributes has one table per backend type
            // skip already in attrinfo
            foreach ($attrinfs as $k => $a)
            {
                if (!in_array($k, array_keys($this->attrinfo)))
                {
                    $bt = $a["backend_type"];
                    if (!isset($this->attrbytype[$bt]))
                    {
                        $this->attrbytype[$bt] = array("data"=>array());
                    }
                    $this->attrbytype[$bt]["data"][] = $a;
                    $this->attrinfo[$k] = $a;
                }
                $this->checkAttributeInfo($a);
            }
            // now add a fast index in the attrbytype array to store id list in a comma separated form
            foreach ($this->attrbytype as $bt => $test)
            {
                $idlist = array();
                foreach ($test["data"] as $it)
                {
                    $idlist[] = $it["attribute_id"];
                }
                $this->attrbytype[$bt]["ids"] = implode(",", $idlist);
            }
            // Important Bugfix, array_merge_recurvise to merge 2 dimenstional arrays.
            $this->_notattribs = array_diff($cols, array_keys($this->attrinfo));
        }
        /*
         * now we have 2 index arrays 1. $this->attrinfo which has the following structure: key : attribute_code value : attribute_properties 2. $this->attrbytype which has the following structure: key : attribute backend type value : array of : data => array of attribute_properties ,one for each attribute that match the backend type ids => list of attribute ids of the backend type
         */
    }

    /**
     * retrieves attribute metadata
     *
     * @param string $attcode
     *            attribute code
     * @param boolean $lookup
     *            if set, this will try to get info from DB otherwise will get from cache and may return null if not cached
     * @return array attribute metadata info
     */
    public function getAttrInfo($attcode, $lookup = true)
    {
        $attrinf = isset($this->attrinfo[$attcode]) ? $this->attrinfo[$attcode] : null;
        if ($attrinf == null && $lookup)
        {
            $this->initAttrInfos(array($attcode));
        }
        if (isset($this->attrinfo[$attcode]))
        {
            $attrinf = $this->attrinfo[$attcode];
        }
        return $attrinf;
    }

    /**
     * retrieves attribute set id for a given attribute set name
     *
     * @param string $asname
     *            : attribute set name
     */
    public function getAttributeSetId($asname)
    {
        if (!isset($this->attribute_sets[$asname]))
        {
            $tname = $this->tablename("eav_attribute_set");
            $asid = $this->selectone(
                "SELECT attribute_set_id FROM $tname WHERE attribute_set_name=? AND entity_type_id=?", 
                array($asname,$this->prod_etype), 'attribute_set_id');
            $this->attribute_sets[$asname] = $asid;
        }
        return $this->attribute_sets[$asname];
    }

    /**
     * Retrieves product id for a given sku
     *
     * @param string $sku
     *            : sku of product to get id for
     */
    public function getProductIds($sku)
    {
        $tname = $this->tablename("catalog_product_entity");
        $result = $this->selectAll(
            "SELECT sku,entity_id as pid,attribute_set_id  as asid,type_id as type FROM $tname WHERE sku=?", $sku);
        if (count($result) > 0)
        {
            $pids = $result[0];
            $pids["__new"] = false;
            return $pids;
        }
        else
        {
            return false;
        }
    }

    /**
     * creates a product in magento database
     *
     * @param array $item:
     *            product attributes as array with key:attribute name,value:attribute value
     * @param int $asid
     *            : attribute set id for values
     * @return : product id for newly created product
     */
    public function createProduct($item, $asid)
    {
        // force item type if not exists
        if (!isset($item["type"]))
        {
            $item["type"] = "simple";
        }
        $tname = $this->tablename('catalog_product_entity');
        $item['type_id'] = $item['type'];
        $item['attribute_set_id'] = $asid;
        $item['entity_type_id'] = $this->prod_etype;
        $item['created_at'] = strftime("%Y-%m-%d %H:%M:%S");
        $item['updated_at'] = strftime("%Y-%m-%d %H:%M:%S");
        $columns = array_intersect(array_keys($item), $this->getProdCols());
        $values = $this->filterkvarr($item, $columns);
        $sql = "INSERT INTO `$tname` (" . implode(",", $columns) . ") VALUES (" . $this->arr2values($columns) . ")";
        $lastid = $this->insert($sql, array_values($values));
        return $lastid;
    }

    /**
     * Updateds product update time
     *
     * @param unknown_type $pid
     *            : entity_id of product
     */
    public function touchProduct($pid)
    {
        $tname = $this->tablename('catalog_product_entity');
        $this->update("UPDATE $tname SET updated_at=? WHERE entity_id=?", array(strftime("%Y-%m-%d %H:%M:%S"),$pid));
    }

    /**
     * Get Option id for select attributes based on value
     *
     * @param int $attid
     *            : attribute id to find option id from value
     * @param mixed $optval
     *            : value to get option id for
     * @return : array of lines (should be as much as values found),"opvd"=>option_id for value on store 0,"opvs" option id for value on current store
     */
    public function getOptionsFromValues($attid, $store_id, $optvals=null)
    {
        //add support for passing no values,returning all optionid/value tuples
        if($optvals!=null) {
            $ovstr = substr(str_repeat("?,", count($optvals)), 0, -1);
            $extra ="AND BINARY optvals.value IN($ovstr)";
        }
        else
        {
            $optvals=array();
            $extra='';
        }
        $t1 = $this->tablename('eav_attribute_option');
        $t2 = $this->tablename('eav_attribute_option_value');
        $sql = "SELECT optvals.option_id as opvs,optvals.value,opt.sort_order FROM $t2 as optvals";
        $sql .= " JOIN $t1 as opt ON opt.option_id=optvals.option_id AND opt.attribute_id=?";
        $sql .= " WHERE optvals.store_id=? $extra";
        return $this->selectAll($sql, array_merge(array($attid,$store_id), $optvals));
    }
    
    /* create a new option entry for an attribute */
    public function createOption($attid,$pos=0)
    {
        $t = $this->tablename('eav_attribute_option');
        $optid = $this->insert("INSERT INTO $t (attribute_id,sort_order) VALUES (?,?)", array($attid,$pos));
        return $optid;
    }

    /**
     * Creates a new option value for an option entry for a store
     *
     * @param int $optid
     *            : option entry id
     * @param int $store_id
     *            : store id to add value for
     * @param mixed $optval
     *            : new option value to add
     * @return : option id for new created value
     */
    public function createOptionValue($optid, $store_id, $optval)
    {
        $t = $this->tablename('eav_attribute_option_value');
        $optval_id = $this->insert("INSERT INTO $t (option_id,store_id,value) VALUES (?,?,?)", 
            array($optid,$store_id,$optval));
        return $optval_id;
    }

    /**
     * updates option positioning
     * @param $optid option id
     * @param $newpos new position
     */
    public function updateOptPos($optid,$newpos)
    {
        $t = $this->tablename('eav_attribute_option');
        $this->update("UPDATE $t SET sort_order=? WHERE option_id=?",array($newpos,$optid));
    }
    /**
     * Returns option ids for a given store for a set of values (for select/multiselect attributes)
     * - Create new entries if values do not exist
     * 
     * @param unknown $attid
     *            attribute id
     * @param unknown $storeid
     *            store id
     * @param unknown $values
     *            value to create options for
     * @return Ambigous <multitype:, unknown>
     */
    public function getOptionIds($attid, $storeid, $values)
    {
        $svalues = array(); // store specific values
        $avalues = array(); // default (admin) values
        $pvalues=array();

        for($i=0;$i<count($values);$i++)
        {
            //if a position is defined for the option, memorize it
            $pvals=explode("||",$values[$i]);
            if(count($pvals)>1)
            {
                $pval=trim($pvals[1]);
                if($pval!="") {
                    $pvalues[] = intval($pval);
                }
            }
            else
            {
                $pvalues[]=-1;
            }
            $val=$pvals[0];

            // if we have a reference value in admin
            if (preg_match("|^(.*)::\[(.*)\]$|", $val, $matches))
            {
                // add translated value in store value array
                $svalues[] = $matches[1];
                // add admin value in admin value array
                $avalues[] = $matches[2];
            }
            else
            {
                // if no translation, add values in both admin & current store array
                $svalues[] = $val;
                $avalues[] = $val;
            }
        }
        $cval=count($values);
        // get Existing options for admin values & current attribute (store = 0)
        // this array contains two items:
        //    'opvs' => the option id;
        //    'value' => the corresponding admin value
        $optAdmin = $this->getCachedOpts($attid,0);
        //for all defined values
        for($i=0;$i<$cval;$i++)
        {
            $pos=$pvalues[$i];
            //if not existing in cache,create it
            if(!isset($optAdmin[$avalues[$i]]))
            {
                //create new option entry
                $newoptid = $this->createOption($attid,$pos);
                $this->createOptionValue($newoptid, 0,$avalues[$i]);
                //cache new created one
                $this->cacheOpt($attid, 0, $newoptid, $avalues[$i],$pos==-1?0:$pos);
            }
            //else check for position change
            else{
                $curopt=$optAdmin[$avalues[$i]];
                if($pos!=-1 && $pos!=$curopt[1])
                {
                    $this->updateOptPos($curopt[0],$pvalues[$i]);
                    $this->cacheOpt($attid, 0, $curopt[0], $avalues[$i],$pos);
                }
            }
        }

        //operating on store values
        if($storeid!=0)
        {
            $optExisting=$this->getCachedOpts($attid,$storeid);
            //iterating on store values
            for($i=0;$i<$cval;$i++)
            {
                //if missing
                if(!isset($optExisting[$svalues[$i]]))
                {
                    //get option id from admin
                  $opt=$this->getCachedOpt($attid,0,$avalues[$i]);
                  $this->createOptionValue($opt[0],$storeid,$svalues[$i]);
                  $this->cacheOpt($attid,$storeid,$opt[0],$svalues[$i],$opt[1]);

                }
            }
        }

        //now we have the full cache in optExisting, just take wanted values from it
        for($i=0;$i<$cval;$i++)
        {
            $av=$avalues[$i];
            $opt=$this->getCachedOpt($attid,0,$av);
            $optids[$av]=$opt[0];
        }


        // remove existing store values
        unset($optExisting);
        // remove existing values
        unset($optAdmin);
        // return option ids
        return $optids;
    }


    /**
     * Cache an option definition
     * @param $attid attribute id
     * @param $storeid store id
     * @param $optid option id
     * @param $val value for option
     * @param int $pos position for option
     */
    public function cacheOpt($attid,$storeid,$optid,$val,$pos=0)
    {
        $akey="a$attid";
        $skey="s$storeid";
        $this->_optidcache[$akey][$skey][$val]=array($optid,$pos);
    }

    /**
     * Retrieve a cache entry for option
     * @param $attid attribute id
     * @param $storeid store id
     * @param $val value to get option id
     * @return mixed cache entry for option (array with value=>array(option_id,position)
     */
    public function getCachedOpt($attid,$storeid,$val)
    {
        $akey="a$attid";
        $skey="s$storeid";
        return $this->_optidcache[$akey][$skey][$val];
    }

    /**
     * return cached option definition rows for a given attribute id
     * 
     * @param unknown $attid
     *            attribute id
     * @return NULL or option definition rows found
     */
    public function getCachedOpts($attid,$storeid=0)
    {
        $akey="a$attid";
        $skey="s$storeid";
        if (!isset($this->_optidcache[$akey])) {
            $this->_optidcache[$akey] = array();
        }
        if(!isset($this->_optidcache[$akey][$skey]))
        {
           $this->_optidcache[$akey][$skey] = array();
           //initialize cache with all existing values
           $exvals = $this->getOptionsFromValues($attid, $storeid);
           foreach ($exvals as $optdesc)
           {
                $this->cacheOpt($attid,$storeid,$optdesc['opvs'],$optdesc['value'],$optdesc['sort_order']);
           }
        }

        return $this->_optidcache[$akey][$skey];
    }

    /**
     * returns tax class id for a given tax class value
     *
     * @param $tcvalue :
     *            tax class value
     */
    public function getTaxClassId($tcvalue)
    {
        // allow for ids in tax_class_id column , extending support
        if (is_numeric($tcvalue))
        {
            $txid = $tcvalue;
        }
        else
        {
            $t = $this->tablename('tax_class');
            $txid = $this->selectone("SELECT class_id FROM $t WHERE class_name=?", array($tcvalue), "class_id");
        }
        // bugfix for tax class id, if not found set it to none
        if (!isset($txid))
        {
            $txid = 0;
        }
        return $txid;
    }

    /**
     * parses a calculated value with tokens like {{ }} or {}
     * 
     * @param unknown $pvalue
     *            parsing value
     * @param unknown $item
     *            item for resolving {item.xxx} tokens
     * @param unknown $params
     *            params for resolving {meta.xxx} tokens
     * @return string resolved value
     */
    public function parseCalculatedValue($pvalue, $item, $params)
    {
        $pvalue = Magmi_ValueParser::parseValue($pvalue, array("item"=>$item,"meta"=>$params));
        return $pvalue;
    }

    /**
     * Return affected store ids for a given item given an attribute scope
     *
     * @param array $item
     *            : item to get store for scope
     * @param string $scope
     *            : scope to get stores from.
     */
    public function getItemStoreIds($item, $scope = 0)
    {
        if (!isset($item['store']))
        {
            $item['store'] = "admin";
        }
        switch ($scope)
        {
            // global scope
            case 1:
                $bstore_ids = $this->getStoreIdsForStoreScope("admin");
                break;
            // store scope
            case 0:
                $bstore_ids = $this->getStoreIdsForStoreScope($item["store"]);
                break;
            // website scope
            case 2:
                $bstore_ids = $this->getStoreIdsForWebsiteScope($item["store"]);
                break;
        }
        
        $itemstores = array_unique(array_merge($this->_dstore, $bstore_ids));
        sort($itemstores);
        return $itemstores;
    }

    /**
     * Optimized attribute handler resolution
     * @param $item
     * @param $attinfo
     */
    public function getHandlers($attrdesc)
    {
        $attrcode=$attrdesc['attribute_code'];
        $atype=$attrdesc['backend_type'];
        // use reflection to find special handlers
        $typehandler = "handle" . ucfirst($atype) . "Attribute";
        $atthandler = "handle" . ucfirst($attrcode) . "Attribute";
        $handlers=array();
        if(!in_array($attrcode,$this->_handlercache))
        {
            foreach ($this->_attributehandlers as $match => $ah)
            {
                $matchinfo = explode(":", $match);
                $mtype = $matchinfo[0];
                $mtest = $matchinfo[1];
                unset($matchinfo);
                if (preg_match("/$mtest/", $attrdesc[$mtype])) {
                    // if there is a specific handler for attribute, use it
                    if (method_exists($ah, $atthandler)) {
                        $handlers[] = array($ah,$atthandler);
                    }
                    else
                        // use generic type attribute
                        if (method_exists($ah, $typehandler)) {
                            $handlers[] = array($ah,$typehandler);
                        }
                }
            }
            $this->_handlercache[$attrcode]=$handlers;
            unset($handlers);
        }
        return $this->_handlercache[$attrcode];


    }

    /**
     * Filter attribute map with item data
     * @param $attmap attribute map to filter
     * @param $item item to match
     * @param $itemids item identifiers
     * @return array filtered attribute map that matches item data
     */
    public function filterAttributeMap($attmap,$item,$itemids)
    {
        $fmap=array();
        //code not optimized to keep php 5.2.x compat, to review with maybe dynamic inclusion
        foreach ($attmap as $tp => $a) {
            if($tp=="static")
            {
                continue;
            }
            foreach($a["data"] as $attrdesc)
            {
                if ($attrdesc["apply_to"] != null &&
                                    strpos($attrdesc["apply_to"], strtolower($itemids["type"])) === false)
                {
                      // do not handle attribute if it does not apply to the product type
                       continue;
                }
                $attrcode = $attrdesc["attribute_code"];
                // if the attribute code is no more in item (plugins may have come into the way), continue
                if (!in_array($attrcode, array_keys($item)))
                {
                                  continue;
                }
                if(!isset($fmap[$tp]))
                {
                    $fmap[$tp]=array("data"=>array());
                }
                $fmap[$tp]["data"][]=$attrdesc;
            }
        }
        return $fmap;
    }

    /**
     * Create product attribute from values for a given product id
     *
     * @param $pid :
     *            product id to create attribute values for
     * @param $item :
     *            attribute values in an array indexed by attribute_code
     */
    public function createAttributes($pid, &$item, $attmap, $isnew, $itemids)
    {
        /**
         * get all store ids
         */
        $this->_extra_attrs = array();
        /* now is the interesring part */
		/* iterate on attribute backend type index */
        $fmap=$this->filterAttributeMap($attmap,$item,$itemids);
		foreach ($fmap as $tp => $a)
        {
            /* for static types, do not insert into attribute tables */
           /* if ($tp == "static")
            {
                continue;
            }*/
            
            // table name for backend type data
            $cpet = $this->tablename("catalog_product_entity_$tp");
            // data table for inserts
            $data = array();
            // inserts to perform on backend type eav
            $inserts = array();
            // deletes to perform on backend type eav
            $deletes = array();
            

            // iterate on all attribute descriptions for the given backend type
            foreach ($a["data"] as $attrdesc)
            {
                // check item type is compatible with attribute apply_to
                /*if ($attrdesc["apply_to"] != null &&
                     strpos($attrdesc["apply_to"], strtolower($itemids["type"])) === false)
                {
                    // do not handle attribute if it does not apply to the product type
                    continue;
                }*/
                // get attribute id
                $attid = $attrdesc["attribute_id"];
                // get attribute value in the item to insert based on code

                $attrcode = $attrdesc["attribute_code"];

                // if the attribute code is no more in item (plugins may have come into the way), continue
                /*if (!in_array($attrcode, array_keys($item)))
                {
                    continue;
                }*/
                // get the item value
                $ivalue = $item[$attrcode];
                // get item store id for the current attribute
                //if is global then , global scope applies but if configurable, back to store view scope since
                //it's a select
                $scope=$attrdesc["is_global"];
                $scope=$scope>0?$scope-$attrdesc["is_configurable"]:0;
                $store_ids = $this->getItemStoreIds($item, $scope);
                
                // do not handle empty generic int values in create mode
                if ($ivalue == "" && $this->mode != "update" && $tp == "int")
                {
                    continue;
                }
                //attribute handler for attribute
                $handlers=$this->getHandlers($attrdesc);

                // for all store ids
                foreach ($store_ids as $store_id)
                {
                    
                    // base output value to be inserted = base source value
                    $ovalue = $ivalue;
                    //iterate on available handlers until one gives a proper value
                    for($i=0;$i<count($handlers);$i++)
                    {
                        //get handler info array for current handler (handler instance & callback name)
                        list($hdl,$cb)=$handlers[$i];
                        //call appropriate callback on current handler to get return value to insert in DB
                        $hvalue=$hdl->$cb($pid, $item, $store_id, $attrcode, $attrdesc, $ivalue);
                        //if valid value returned, take it as output value & break
                        if (isset($hvalue) && $hvalue != '__MAGMI_UNHANDLED__')
                        {
                            $ovalue = $hvalue;
                            break;
                        }
                    }

                    // if __MAGMI_UNHANDLED__ ,don't insert anything, __MAGMI_IGNORE__ has also to do nothing
                    if ($ovalue == '__MAGMI_UNHANDLED__' || $ovalue=='__MAGMI_IGNORE__')
                    {
                        $ovalue = false;
                    }
                    
                    else
                    // if handled value is a "DELETE" or a NULL , which will also be removed
                    if ($ovalue == '__MAGMI_DELETE__' || $ovalue=='__NULL__')
                    {
                        $deletes[] = $attid;
                        // do not handle value in insert
                        $ovalue = null;
                    }

                    // if we have something to do with this value
                    if ($ovalue !== false && $ovalue != null)
                    {
                        
                        $data[] = $this->prod_etype;
                        $data[] = $attid;
                        $data[] = $store_id;
                        $data[] = $pid;
                        $data[] = $ovalue;
                        $insstr = "(?,?,?,?,?)";
                        $inserts[] = $insstr;
                    }
                    
                    // if one of the store in the list is admin
                    if ($store_id == 0)
                    {
                        $sids = $store_ids;
                        // remove all values bound to the other stores for this attribute,so that they default to "use admin value"
                        array_shift($sids);
                        if (count($sids) > 0)
                        {
                            $sidlist = implode(",", $sids);
                            $ddata = array($this->prod_etype,$attid,$pid);
                            $sql = "DELETE FROM $cpet WHERE entity_type_id=? AND attribute_id=? AND store_id IN ($sidlist) AND entity_id=?";
                            $this->delete($sql, $ddata);
                            unset($ddata);
                        }
                        unset($sids);
                        break;
                    }
                }
            }
            //if we have values to insert or update
            if (!empty($inserts))
            {
                // now perform insert for all values of the the current backend type in one
                // single insert
                $sql = "INSERT INTO $cpet
			(`entity_type_id`, `attribute_id`, `store_id`, `entity_id`, `value`)
			VALUES ";
                $sql .= implode(",", $inserts);
                // this one taken from mysql log analysis of magento import
                // smart one :)
                $sql .= " ON DUPLICATE KEY UPDATE `value`=VALUES(`value`)";
                $this->insert($sql, $data);
            }
            
            //if we have values to delete
            if (!empty($deletes))
            {
                $sidlist = implode(",", $store_ids);
                $attidlist = implode(",", $deletes);
                $sql = "DELETE FROM $cpet WHERE entity_type_id=? AND attribute_id IN ($attidlist) AND store_id IN ($sidlist) AND entity_id=?";
                $this->delete($sql, array($this->prod_etype,$pid));
            }
            //if no values inserted or deleted on a new item, we have a problem
            if (empty($deletes) && empty($inserts) && $isnew)
            {
                //in fact we have a problem if we have a really new item
                if (!$this->_same)
                {
                    $this->log("No $tp Attributes created for sku " . $item["sku"], "warning");
                }
            }
            //memory release
            unset($store_ids);
            unset($data);
            unset($inserts);
            unset($deletes);
        }
        unset($fmap);
        //if new attributes are to be processed, return them
        return $this->_extra_attrs;
    }

    /**
     * update product stock
     *
     * @param int $pid
     *            : product id
     * @param array $item
     *            : attribute values for product indexed by attribute_code
     */
    public function updateStock($pid, $item, $isnew)
    {
        $scols = $this->getStockCols();
        // ake only stock columns that are in item
        $itstockcols = array_intersect(array_keys($item), $scols);
        // o stock columns set, item exists, no stock update needed.
        if (count($itstockcols) == 0 && !$isnew)
        {
            return;
        }
        $csit = $this->tablename("cataloginventory_stock_item");
        $css = $this->tablename("cataloginventory_stock_status");
        // alculate is_in_stock flag
        if (isset($item["qty"]))
        {
            if (!isset($item["manage_stock"]))
            {
                $item["manage_stock"] = 1;
                $item["use_config_manage_stock"] = 0;
            }
            
            $mqty = (isset($item["min_qty"]) ? $item["min_qty"] : 0);
            $is_in_stock = isset($item["is_in_stock"]) ? $item["is_in_stock"] : ($item["qty"] > $mqty ? 1 : 0);
            $item["is_in_stock"] = $is_in_stock;
        }
        // ake only stock columns that are in item after item update
        $common = array_intersect(array_keys($item), $scols);
        
        // reate stock item line if needed
        $stock_id = (isset($item["stock_id"]) ? $item["stock_id"] : 1);
        $sql = "INSERT IGNORE INTO `$csit` (product_id,stock_id) VALUES (?,?)";
        $this->insert($sql, array($pid,$stock_id));
        
        if (count($common) > 0)
        {
            $cols = $this->arr2columns($common);
            $stockvals = $this->filterkvarr($item, $common);
            
            // ill with values
            $svstr = $this->arr2update($stockvals);
            if (isset($item["qty"]) && $item["qty"] != "")
            {
                $relqty = NULL;
                
                // if magmi_qty_absolute flag is not set, then use standard "relative" qty parsing.
                if (!isset($item["magmi_qty_absolute"]) || $item["magmi_qty_absolute"] == 0)
                {
                    // test for relative qty
                    if ($item["qty"][0] == "+" || $item["qty"][0] == "-")
                    {
                        $relqty = getRelative($item["qty"]);
                    }
                }
                // if relative qty
                if ($relqty != NULL)
                {
                    // update UPDATE statement value affectation
                    $svstr = preg_replace("/(^|,)qty=\?/", "$1qty=qty$relqty?", $svstr);
                    $stockvals["qty"] = $item["qty"];
                    $svstr = str_replace("is_in_stock=?", "is_in_stock=(qty>min_qty)", $svstr);
                    unset($stockvals["is_in_stock"]);
                }
            }
            $sql = "UPDATE `$csit` SET $svstr WHERE product_id=? AND stock_id=?";
            $this->update($sql, array_merge(array_values($stockvals), array($pid,$stock_id)));
        }
        
        $data = array();
        $wsids = $this->getItemWebsites($item);
        $csscols = array("website_id","product_id","stock_id","qty","stock_status");
        $cssvals = $this->filterkvarr($item, $csscols);
        $stock_id = (isset($cssvals["stock_id"]) ? $cssvals["stock_id"] : 1);
        $stock_status = (isset($cssvals["stock_status"]) ? $cssvals["stock_status"] : 1);
        // new auto synchro on lat inserted stock item values for stock status.
        // also works for multiple stock ids.
        
        // [start] exanto.de - this does not work inside a DB transaction bc cataloginventory_stock_item is not written yet on fresh imports
        /*
         * :ORG: $sql="INSERT INTO `$css` SELECT csit.product_id,ws.website_id,cis.stock_id,csit.qty,? as stock_status FROM `$csit` as csit JOIN ".$this->tablename("core_website")." as ws ON ws.website_id IN (".$this->arr2values($wsids).") JOIN ".$this->tablename("cataloginventory_stock")." as cis ON cis.stock_id=? WHERE product_id=? ON DUPLICATE KEY UPDATE stock_status=VALUES(`stock_status`),qty=VALUES(`qty`)";
         */
        // Fixed version
        $cpe = $this->tablename("catalog_product_entity");
        // Fix , $stockvals is already a mix between item keys & stock table keys.
        $qty = isset($stockvals['qty']) ? $stockvals['qty'] : 0;
        if (!$qty)
        {
            $qty = 0;
        }
        $sql = "INSERT INTO `$css` SELECT '$pid' as product_id,ws.website_id,cis.stock_id,'$qty' as qty,? as stock_status
				FROM `$cpe` as cpe
				JOIN " .
             $this->tablename("core_website") . " as ws ON ws.website_id IN (" . $this->arr2values($wsids) . ")
				JOIN " .
             $this->tablename("cataloginventory_stock") . " as cis ON cis.stock_id=?
				WHERE cpe.entity_id=?
				ON DUPLICATE KEY UPDATE stock_status=VALUES(`stock_status`),qty=VALUES(`qty`)";
        // [ end ] exanto.de - this does not work inside a DB transaction bc cataloginventory_stock_item is not written yet on fresh imports
        
        $data[] = $stock_status;
        $data = array_merge($data, $wsids);
        $data[] = $stock_id;
        $data[] = $pid;
        $this->insert($sql, $data);
        unset($data);
    }

    /**
     * assign categories for a given product id from values
     * categories should already be created & csv values should be as the ones
     * given in the magento export (ie: comma separated ids, minus 1,2)
     *
     * @param int $pid
     *            : product id
     * @param array $item
     *            : attribute values for product indexed by attribute_code
     */
    public function assignCategories($pid, $item)
    {
        $cce = $this->tablename("catalog_category_entity");
        $ccpt = $this->tablename("catalog_category_product");
        // andle assignment reset
        if (!isset($item["category_reset"]) || $item["category_reset"] == 1)
        {
            $sql = "DELETE $ccpt.*
			FROM $ccpt
			JOIN $cce ON $cce.entity_id=$ccpt.category_id
			WHERE product_id=?";
            $this->delete($sql, $pid);
        }
        
        $inserts = array();
        $data = array();
        $cdata = array();
        $ddata = array();
        $cpos = array();
        $catids = csl2arr($item["category_ids"]);
        
        // find positive category assignments
        
        foreach ($catids as $catdef)
        {
            $a = explode("::", $catdef);
            $catid = $a[0];
            $catpos = (count($a) > 1 ? $a[1] : "0");
            $rel = getRelative($catid);
            if ($rel == "-")
            {
                $ddata[] = $catid;
            }
            else
            {
                $cdata[$catid] = $catpos;
            }
        }
        
        // get all "real ids"
        if (count($cdata) > 0)
        {
            $scatids = array_keys($cdata);
            $rcatids = $this->selectAll(
                "SELECT cce.entity_id as id FROM $cce as cce WHERE cce.entity_id IN (" . $this->arr2values($scatids) .
                     ")", $scatids);
            $vcatids = array();
            foreach ($rcatids as $rcatrow)
            {
                $vcatids[] = $rcatrow['id'];
            }
            // now get the diff
            $diff = array_diff(array_keys($cdata), $vcatids);
            $cdiff = count($diff);
            // if there are some, warning
            if ($cdiff > 0)
            {
                $this->log('Invalid category ids found for sku ' . $item['sku'] . ":" . implode(",", $diff), "warning");
                // remove invalid category entries
                for ($i = 0; $i < $cdiff; $i++)
                {
                    unset($cdata[$diff[$i]]);
                }
            }
            
            // ow we have verified ids
            foreach ($cdata as $catid => $catpos)
            {
                $inserts[] = "(?,?,?)";
                $data[] = $catid;
                $data[] = $pid;
                $data[] = $catpos;
            }
        }
        
        // eform deletion of removed category affectation
        if (count($ddata) > 0)
        {
            $sql = "DELETE FROM $ccpt WHERE category_id IN (" . $this->arr2values($ddata) . ") AND product_id=?";
            $ddata[] = $pid;
            $this->delete($sql, $ddata);
            unset($ddata);
        }
        
        // reate new category assignment for products, if multi store with repeated ids
        // gnore duplicates
        if (count($inserts) > 0)
        {
            $sql = "INSERT INTO $ccpt (`category_id`,`product_id`,`position`)
				 VALUES	 ";
            $sql .= implode(",", $inserts);
            $sql .= " ON DUPLICATE KEY UPDATE position=VALUES(`position`)";
            $this->insert($sql, $data);
            unset($data);
        }
        unset($deletes);
        unset($inserts);
    }

    /**
     * Return websites for an item line, based either on websites or store column
     * @param $item item to check
     * @param bool $default
     * @return mixed list of website ids for item
     */
    public function getItemWebsites($item, $default = false)
    {
        // support for websites column if set
        if (!empty($item["websites"]))
        {
            if (!isset($this->_wsids[$item["websites"]]))
            {
                $this->_wsids[$item["websites"]] = array();
                
                $cws = $this->tablename("core_website");
                $wscodes = csl2arr($item["websites"]);
                $qcolstr = $this->arr2values($wscodes);
                $rows = $this->selectAll("SELECT website_id FROM $cws WHERE code IN ($qcolstr)", $wscodes);
                foreach ($rows as $row)
                {
                    $this->_wsids[$item["websites"]][] = $row['website_id'];
                }
            }
            return $this->_wsids[$item["websites"]];
        }
        
        if (!isset($item['store']))
        {
            $item['store'] = "admin";
        }
        $k = $item["store"];
        
        if (!isset($this->_wsids[$k]))
        {
            $this->_wsids[$k] = array();
            $cs = $this->tablename("core_store");
            if (trim($k) != "admin")
            {
                $scodes = csl2arr($k);
                $qcolstr = $this->arr2values($scodes);
                $rows = $this->selectAll(
                    "SELECT website_id FROM $cs WHERE code IN ($qcolstr) AND store_id!=0 GROUP BY website_id", $scodes);
            }
            else
            {
                $rows = $this->selectAll("SELECT website_id FROM $cs WHERE store_id!=0 GROUP BY website_id ");
            }
            foreach ($rows as $row)
            {
                $this->_wsids[$k][] = $row['website_id'];
            }
        }
        return $this->_wsids[$k];
    }

    /**
     * set website of product if not exists
     *
     * @param int $pid
     *            : product id
     * @param array $item
     *            : attribute values for product indexed by attribute_code
     */
    public function updateWebSites($pid, $item)
    {
        $wsids = $this->getItemWebsites($item);
        $qcolstr = $this->arr2values($wsids);
        $cpst = $this->tablename("catalog_product_website");
        $cws = $this->tablename("core_website");
        // associate product with all websites in a single multi insert (use ignore to avoid duplicates)
        $ddata = array($pid);
        $sql = "DELETE FROM `$cpst` WHERE product_id=?";
        $this->delete($sql, $ddata);
        $sql = "INSERT INTO `$cpst` (`product_id`, `website_id`) SELECT ?,website_id FROM $cws WHERE website_id IN ($qcolstr)";
        $this->insert($sql, array_merge(array($pid), $wsids));
     }

    /**
     * Clears option id cache
     */
    public function clearOptCache()
    {
       /* unset($this->_optidcache);
        $this->_optidcache = array();*/
    }

    /**
     * Specific processing to set internal state for new sku to import
     * @param $sku sku to import
     * @param $existing boolean, item existing or not
     */
    public function onNewSku($sku, $existing)
    {
        //$this->clearOptCache();
        // only assign values to store 0 by default in create mode for new sku
        // for store related options
        if (!$existing)
        {
            $this->_dstore = array(0);
        }
        else
        {
            $this->_dstore = array();
        }
        $this->_same = false;
    }

    /**
     * Specific processing to set internal state on repeating sku on import
     * @param $sku repeated sku
     */
    public function onSameSku($sku)
    {
        unset($this->_dstore);
        $this->_dstore = array();
        $this->_same = true;
    }

    /**
     * returns if item to import already exists (need item to have been identified)
     * @return bool exists or not
     */
    public function currentItemExists()
    {
        return $this->_curitemids["__new"] == false;
    }

    /**
     * Override of log to add sku reference
     * @param $data raw log data
     * @param string $type log type (may be modified by plugin)
     * @param null $logger logger to use (null = defaul logger)
     */
    public function log($data,$type="info",$logger=null)
    {
        $prefix=((strpos($type,'warning')!==FALSE || strpos($type,'error')!==FALSE ) && isset($this->_curitemids['sku']))?"SKU ".$this->_curitemids['sku']." - " :'';
        parent::log($prefix.$data,$type,$logger);
    }

    /**
     * Fetches item identifiers (attribute_set, type, product id if existing)
     * if the item does not exist, uses the data source , otherwise fetch it from magento db
     * @param $item item to retreive ids for
     * @return array associative array for identifiers
     */
    public function getItemIds($item)
    {
        $sku = $item["sku"];
        if (strcmp($sku, $this->_curitemids["sku"]) != 0)
        {
            // try to find item ids in db
            $cids = $this->getProductIds($sku);
            if ($cids !== false)
            {
                // if found use it
                $this->_curitemids = $cids;
            }
            else
            {
                // only sku & attribute set id from datasource otherwise.
                $this->_curitemids = array("pid"=>null,"sku"=>$sku,
                    "asid"=>isset($item["attribute_set"]) ? $this->getAttributeSetId($item["attribute_set"]) : $this->default_asid,
                    "type"=>isset($item["type"]) ? $item["type"] : "simple","__new"=>true);
            }
            // do not reset values for existing if non admin
            $this->onNewSku($sku, ($cids !== false));
            unset($cids);
        }
        else
        {
            $this->onSameSku($sku);
        }
        return $this->_curitemids;
    }

    //more efficient filtering for handleIgnore
    public function keepValue($v)
    {
        return $v!=='__MAGMI_IGNORE__';
    }
    //more efficient handleIgnore
    public function handleIgnore(&$item)
    {
        $item=array_filter($item,array($this,'keepValue'));
    }

    /**
     * @param $pid product id
     * @return string comma separated list of stores for item id
     */
    public function findItemStores($pid)
    {
        $sql = "SELECT cs.code FROM " . $this->tablename("catalog_product_website") . " AS cpw" . " JOIN " .
             $this->tablename("core_store") . " as cs ON cs.website_id=cpw.website_id" . " WHERE cpw.product_id=?";
        $result = $this->selectAll($sql, array($pid));
        $scodes = array();
        foreach ($result as $row)
        {
            $scodes[] = $row["code"];
        }
        return implode(",", $scodes);
    }

    public function checkItemStores($scodes)
    {
        if ($scodes == "admin")
        {
            return $scodes;
        }
        
        $scarr = explode(",", $scodes);
        trimarray($scarr);
        $rscode = array();
        $sql = "SELECT code FROM " . $this->tablename("core_store") . " WHERE code IN (" . $this->arr2values($scarr) .
             ")";
        $result = $this->selectAll($sql, $scarr);
        $rscodes = array();
        foreach ($result as $row)
        {
            $rscodes[] = $row["code"];
        }
        $diff = array_diff($scarr, $rscodes);
        $out = "";
        if (count($diff) > 0)
        {
            $out = "Invalid store code(s) found:" . implode(",", $diff);
        }
        if ($out != "")
        {
            if (count($rscodes) == 0)
            {
                $out .= ", NO VALID STORE FOUND";
            }
            $this->log($out, "warning");
        }
        
        return implode(",", $rscodes);
    }

    public function checkstore(&$item, $pid, $isnew)
    {
        // we have store column set , just check
        if (isset($item["store"]) && trim($item["store"]) != "")
        {
            $scodes = $this->checkItemStores($item["store"]);
        }
        else
        {
            $scodes = "admin";
        }
        if ($scodes == "")
        {
            return false;
        }
        $item["store"] = $scodes;
        return true;
    }

    /**
     * full import workflow for item
     *
     * @param array $item
     *            : attribute values for product indexed by attribute_code
     */
    public function importItem($item)
    {
        $this->handleIgnore($item);
        if (Magmi_StateManager::getState() == "canceled")
        {
            throw new Exception("MAGMI_RUN_CANCELED");
        }
        // first step
        
        if (!$this->callPlugins("itemprocessors", "processItemBeforeId", $item))
        {
            return false;
        }
        
        // check if sku has been reset
        if (!isset($item["sku"]) || trim($item["sku"]) == '')
        {
            $this->log('No sku info found for record #' . $this->_current_row, "error");
            return false;
        }
        // handle "computed" ignored columns
        $this->handleIgnore($item);
        // get Item identifiers in magento
        $itemids = $this->getItemIds($item);
        
        // extract product id & attribute set id
        $pid = $itemids["pid"];
        $asid = $itemids["asid"];
        
        $isnew = false;
        if (isset($pid) && $this->mode == "xcreate")
        {
            $this->log("skipping existing sku:{$item["sku"]} - xcreate mode set", "skip");
            return false;
        }
        
        if (!isset($pid))
        {
            
            if ($this->mode !== 'update')
            {
                if (!isset($asid))
                {
                    $this->log("cannot create product sku:{$item["sku"]}, no attribute_set defined", "error");
                    return false;
                }
                $pid = $this->createProduct($item, $asid);
                $this->_curitemids["pid"] = $pid;
                $isnew = true;
            }
            else
            {
                // mode is update, do nothing
                $this->log("skipping unknown sku:{$item["sku"]} - update mode set", "skip");
                return false;
            }
        }
        else
        {
            $this->updateProduct($item, $pid);
        }
        
        try
        {
            $basemeta = array("product_id"=>$pid,"new"=>$isnew,"same"=>$this->_same,"asid"=>$asid);
            $fullmeta = array_merge($basemeta, $itemids);
            if (!$this->callPlugins("itemprocessors", "processItemAfterId", $item, $fullmeta))
            {
                return false;
            }
            
            if (count($item) == 0)
            {
                return true;
            }
            // handle "computed" ignored columns from afterImport
            $this->handleIgnore($item);
            
            if (!$this->checkstore($item, $pid, $isnew))
            {
                $this->log("invalid store value, skipping item");
                return false;
            }
            // if column list has been modified by callback, update attribute info cache.
            $this->initAttrInfos(array_keys($item));
            // create new ones
            $attrmap = $this->attrbytype;
            do
            {
                $attrmap = $this->createAttributes($pid, $item, $attrmap, $isnew, $itemids);
            }
            while (count($attrmap) > 0);
            
            if (!testempty($item, "category_ids") || (isset($item["category_reset"]) && $item["category_reset"] == 1))
            {
                // assign categories
                $this->assignCategories($pid, $item);
            }
            
            // update websites if column is set
            if (isset($item["websites"]) || $isnew)
            {
                $this->updateWebSites($pid, $item);
            }

            //fix for multiple stock update
            //always update stock
            $this->updateStock($pid, $item, $isnew);

            $this->touchProduct($pid);
            // ok,we're done
            if (!$this->callPlugins("itemprocessors", "processItemAfterImport", $item, $fullmeta))
            {
                return false;
            }
        }
        catch (Exception $e)
        {
            $this->callPlugins(array("itemprocessors"), "processItemException", $item, array("exception"=>$e));
            $this->logException($e);
            throw $e;
        }
        return true;
    }

    public function getProperties()
    {
        return $this->_props;
    }

    /**
     * count lines of csv file
     *
     * @param string $csvfile
     *            filename
     */
    public function lookup()
    {
        $t0 = microtime(true);
        $this->log("Performing Datasouce Lookup...", "startup");
        
        $count = $this->datasource->getRecordsCount();
        $t1 = microtime(true);
        $time = $t1 - $t0;
        $this->log("$count:$time", "lookup");
        $this->log("Found $count records, took $time sec", "startup");
        
        return $count;
    }

    /**
     * Update raw product data in magento (catalog_product_entity fields)
     * @param $item data to update
     * @param $pid product id
     */
    public function updateProduct($item, $pid)
    {
        $tname = $this->tablename('catalog_product_entity');
        if (isset($item['type']))
        {
            $item['type_id'] = $item['type'];
        }
        $item['entity_type_id'] = $this->prod_etype;
        $item['updated_at'] = strftime("%Y-%m-%d %H:%M:%S");
        $columns = array_intersect(array_keys($item), $this->getProdCols());
        $values = $this->filterkvarr($item, $columns);
        
        $sql = "UPDATE  `$tname` SET " . $this->arr2update($values) . " WHERE entity_id=?";
        
        $this->update($sql, array_merge(array_values($values), array($pid)));
    }

    /**
     * @return mixed product entity type
     */
    public function getProductEntityType()
    {
        return $this->prod_etype;
    }

    /**
     * @return mixed current importing row
     */
    public function getCurrentRow()
    {
        return $this->_current_row;
    }

    public function setCurrentRow($cnum)
    {
        $this->_current_row = $cnum;
    }

    public function isLastItem($item)
    {
        return isset($item["__MAGMI_LAST__"]);
    }

    public function setLastItem(&$item)
    {
        $item["__MAGMI_LAST__"] = 1;
    }

    public function engineInit($params)
    {
        $this->_profile = $this->getParam($params, "profile", "default");
        // create an instance of local magento directory handler
        // this instance will autoregister in factory
        $mdh = new LocalMagentoDirHandler(Magmi_Config::getInstance()->getMagentoDir());
        $this->_timecounter->initTimingCats(array("global","line"));
        $this->initPlugins($this->_profile);
        $this->mode = $this->getParam($params, "mode", "update");
    }

    public function reportStats($nrow, &$tstart, &$tdiff, &$lastdbtime, &$lastrec)
    {
        $tend = microtime(true);
        $this->log($nrow . " - " . ($tend - $tstart) . " - " . ($tend - $tdiff), "itime");
        $this->log(
            $this->_nreq . " - " . ($this->_indbtime) . " - " . ($this->_indbtime - $lastdbtime) . " - " .
                 ($this->_nreq - $lastrec), "dbtime");
        $lastrec = $this->_nreq;
        $lastdbtime = $this->_indbtime;
        $tdiff = microtime(true);
    }

    public function initImport($params)
    {
        $this->log("MAGMI by dweeves - version:" . Magmi_Version::$version, "title");
        $this->log("Import Profile:$this->_profile", "startup");
        $this->log("Import Mode:$this->mode", "startup");
        $this->log("step:" . $this->getProp("GLOBAL", "step", 0.5) . "%", "step");
        // intialize store id cache
        $this->connectToMagento();
        try
        {
            $this->initProdType();
            $this->createPlugins($this->_profile, $params);
            $this->_registerPluginLoopCallback("processItemAfterId", "onPluginProcessedItemAfterId");
            $this->callPlugins("datasources,itemprocessors", "startImport");
            $this->resetSkuStats();
        }
        catch (Exception $e)
        {
            $this->disconnectFromMagento();
        }
    }

    public function onPluginProcessedItemAfterId($plinst, &$item, $plresult)
    {
        $this->handleIgnore($item);
    }

    /**
     * Breaks item processing , but validates partial import
     * This is useful for complex plugins that would assur
     *
     * @param array $item
     *            , item to break process on
     * @param array $params
     *            , processing parameters (item metadata)
     * @param
     *            bool touch , sets product update time if true (default)
     */
    public function breakItemProcessing(&$item, $params, $touch = true)
    {
        // setting empty item to break standard processing
        $item = array();
        if ($touch && isset($params["product_id"]))
        {
            $this->touchProduct($params["product_id"]);
        }
    }

    public function exitImport()
    {
        $this->callPlugins("datasources,general,itemprocessors", "endImport");
        $this->callPlugins("datasources,general,itemprocessors", "afterImport");
        $this->disconnectFromMagento();
    }

    public function updateSkuStats($res)
    {
        if (!$this->_same)
        {
            $this->_skustats["nsku"]++;
            if ($res["ok"])
            {
                $this->_skustats["ok"]++;
            }
            else
            {
                $this->_skustats["ko"]++;
            }
        }
    }

    public function getDataSource()
    {
        return $this->getPluginInstance("datasources");
    }

    public function processDataSourceLine($item, $rstep, &$tstart, &$tdiff, &$lastdbtime, &$lastrec)
    {
        // counter
        $res = array("ok"=>0,"last"=>0);
        $canceled = false;
        $this->_current_row++;
        if ($this->_current_row % $rstep == 0)
        {
            $this->reportStats($this->_current_row, $tstart, $tdiff, $lastdbtime, $lastrec);
        }
        try
        {
            if (is_array($item) && count($item) > 0)
            {
                // import item
                $this->beginTransaction();
                $importedok = $this->importItem($item);
                if ($importedok)
                {
                    $res["ok"] = true;
                    $this->commitTransaction();
                }
                else
                {
                    $res["ok"] = false;
                    $this->rollbackTransaction();
                }
            }
            else
            {
                $this->log("ERROR - RECORD #$this->_current_row - INVALID RECORD", "error");
            }
            // intermediary measurement
        }
        catch (Exception $e)
        {
            $this->rollbackTransaction();
            $res["ok"] = false;
            $this->logException($e, "ERROR ON RECORD #$this->_current_row");
            if ($e->getMessage() == "MAGMI_RUN_CANCELED")
            {
                $canceled = true;
            }
        }
        if ($this->isLastItem($item) || $canceled)
        {
            unset($item);
            $res["last"] = 1;
        }
        
        unset($item);
        $this->updateSkuStats($res);
        
        return $res;
    }

    public function resetSkuStats()
    {
        $this->_skustats = array("nsku"=>0,"ok"=>0,"ko"=>0);
    }

    public function engineRun($params, $forcebuiltin = array())
    {
        $this->log("Import Profile:$this->_profile", "startup");
        $this->log("Import Mode:$this->mode", "startup");
        $this->log("step:" . $this->getProp("GLOBAL", "step", 0.5) . "%", "step");
        $this->createPlugins($this->_profile, $params);
        $this->datasource = $this->getDataSource();
        $this->callPlugins("datasources,general", "beforeImport");
        $nitems = $this->lookup();
        Magmi_StateManager::setState("running");
        // if some rows found
        if ($nitems > 0)
        {
            // initializing product type early (in case of db update on startImport)
            $this->initProdType();
            $this->resetSkuStats();
            // intialize store id cache
            $this->callPlugins("datasources,itemprocessors", "startImport");
            // initializing item processors
            $cols = $this->datasource->getColumnNames();
            $this->log(count($cols), "columns");
            $this->callPlugins("itemprocessors", "processColumnList", $cols);
            if (count($cols) < 2)
            {
                $this->log("Invalid input data , not enough columns found,check datasource parameters", "error");
                $this->log("Import Ended", "end");
                Magmi_StateManager::setState("idle");
                return;
            }
            $this->log("Ajusted processed columns:" . count($cols), "startup");
            // initialize attribute infos & indexes from column names
            if ($this->mode != "update")
            {
                $this->checkRequired($cols);
            }
            $this->initAttrInfos(array_values($cols));
            // counter
            $this->_current_row = 0;
            // start time
            $tstart = microtime(true);
            // differential
            $tdiff = $tstart;
            // intermediary report step
            $this->initDbqStats();
            $pstep = $this->getProp("GLOBAL", "step", 0.5);
            $rstep = ceil(($nitems * $pstep) / 100);
            // read each line
            $lastrec = 0;
            $lastdbtime = 0;
            while (($item = $this->datasource->getNextRecord()) !== false)
            {
                $this->_timecounter->initTimingCats(array("line"));
                $res = $this->processDataSourceLine($item, $rstep, $tstart, $tdiff, $lastdbtime, $lastrec);
                // break on "forced" last
                if ($res["last"] == 1)
                {
                    $this->log("last item encountered", "info");
                    break;
                }
            }
            $this->callPlugins("datasources,general,itemprocessors", "endImport");
            $this->reportStats($this->_current_row, $tstart, $tdiff, $lastdbtime, $lastrec);
            $this->log("Skus imported OK:" . $this->_skustats["ok"] . "/" . $this->_skustats["nsku"], "info");
            if ($this->_skustats["ko"] > 0)
            {
                $this->log("Skus imported KO:" . $this->_skustats["ko"] . "/" . $this->_skustats["nsku"], "warning");
            }
        }
        else
        {
            $this->log("No Records returned by datasource", "warning");
        }
        $this->callPlugins("datasources,general,itemprocessors", "afterImport");
        $this->log("Import Ended", "end");
        Magmi_StateManager::setState("idle");
        
        $timers = $this->_timecounter->getTimers();
        $f = fopen(Magmi_StateManager::getStateDir() . "/timings.txt", "w");
        foreach ($timers as $cat => $info)
        {
            $rep = "\nTIMING CATEGORY:$cat\n--------------------------------";
            foreach ($info as $phase => $pinfo)
            {
                $rep .= "\nPhase:$phase\n";
                foreach ($pinfo as $plugin => $data)
                {
                    $rdur = round($data["dur"], 4);
                    if ($rdur > 0)
                    {
                        $rep .= "- Class:$plugin :$rdur ";
                    }
                }
            }
            fwrite($f, $rep);
        }
        fclose($f);
    }

    public function onEngineException($e)
    {
        if (isset($this->datasource))
        {
            $this->datasource->onException($e);
        }
        $this->log("Import Ended", "end");
        
        Magmi_StateManager::setState("idle");
    }
}
