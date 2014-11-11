<?php

class Magmi_ConfigurableItemProcessor extends Magmi_ItemProcessor
{
    private $_configurable_attrs = array();
    private $_use_defaultopc = false;
    private $_optpriceinfo = array();
    private $_currentsimples = array();

    public function initialize($params)
    {}
    /* Plugin info declaration */
    public function getPluginInfo()
    {
        return array("name"=>"Configurable Item processor","author"=>"Dweeves","version"=>"1.3.7a",
            "url"=>$this->pluginDocUrl("Configurable_Item_processor"));
    }

    /**
     *
     * @param unknown $asid            
     * @return multitype:
     */
    public function getConfigurableOptsFromAsId($asid)
    {
        if (!isset($this->_configurable_attrs[$asid]))
        {
            $ea = $this->tablename("eav_attribute");
            $eea = $this->tablename("eav_entity_attribute");
            $eas = $this->tablename("eav_attribute_set");
            $eet = $this->tablename("eav_entity_type");
            
            $sql = "SELECT ea.attribute_code FROM `$ea` as ea
		JOIN $eet as eet ON eet.entity_type_id=ea.entity_type_id AND eet.entity_type_id=?
		JOIN $eas as eas ON eas.entity_type_id=eet.entity_type_id AND eas.attribute_set_id=?
		JOIN $eea as eea ON eea.attribute_id=ea.attribute_id";
            $cond = "ea.is_user_defined=1";
            if ($this->getMagentoVersion() != "1.3.x")
            {
                $cea = $this->tablename("catalog_eav_attribute");
                $sql .= " JOIN $cea as cea ON cea.attribute_id=ea.attribute_id AND cea.is_global=1 AND cea.is_configurable=1";
            }
            else
            {
                $cond .= " AND ea.is_global=1 AND ea.is_configurable=1";
            }
            $sql .= " WHERE $cond
			GROUP by ea.attribute_id";
            
            $result = $this->selectAll($sql, array($this->getProductEntityType(),$asid));
            foreach ($result as $r)
            {
                $this->_configurable_attrs[$asid][] = $r["attribute_code"];
            }
        }
        return $this->_configurable_attrs[$asid];
    }

    public function dolink($pid, $cond, $conddata = array())
    {
        $cpsl = $this->tablename("catalog_product_super_link");
        $cpr = $this->tablename("catalog_product_relation");
        $cpe = $this->tablename("catalog_product_entity");
        $sql = "DELETE cpsl.*,cpsr.* FROM $cpsl as cpsl
				JOIN $cpr as cpsr ON cpsr.parent_id=cpsl.parent_id
				WHERE cpsl.parent_id=?";
        $this->delete($sql, array($pid));
        // recreate associations
        $sql = "INSERT INTO $cpsl (`parent_id`,`product_id`) SELECT cpec.entity_id as parent_id,cpes.entity_id  as product_id  
				  FROM $cpe as cpec 
				  JOIN $cpe as cpes ON cpes.type_id IN ('simple','virtual') AND cpes.sku $cond
			  	  WHERE cpec.entity_id=?";
        $this->insert($sql, array_merge($conddata, array($pid)));
        $sql = "INSERT INTO $cpr (`parent_id`,`child_id`) SELECT cpec.entity_id as parent_id,cpes.entity_id  as child_id  
				  FROM $cpe as cpec 
				  JOIN $cpe as cpes ON cpes.type_id IN ('simple','virtual') AND cpes.sku $cond
			  	  WHERE cpec.entity_id=?";
        $this->insert($sql, array_merge($conddata, array($pid)));
        unset($conddata);
    }

    public function autoLink($pid)
    {
        $this->dolink($pid, "LIKE CONCAT(cpec.sku,'%')");
    }

    public function updSimpleVisibility($pid)
    {
        $vis = $this->getParam("CFGR:updsimplevis", 0);
        if ($vis != 0)
        {
            $attinfo = $this->getAttrInfo("visibility");
            $sql = "UPDATE " . $this->tablename("catalog_product_entity_int") . " as cpei
			JOIN " . $this->tablename("catalog_product_super_link") . " as cpsl ON cpsl.parent_id=?
			JOIN " . $this->tablename("catalog_product_entity") . " as cpe ON cpe.entity_id=cpsl.product_id 
			SET cpei.value=?
			WHERE cpei.entity_id=cpe.entity_id AND attribute_id=?";
            $this->update($sql, array($pid,$vis,$attinfo["attribute_id"]));
        }
    }

    public function fixedLink($pid, $skulist)
    {
        $this->dolink($pid, "IN (" . $this->arr2values($skulist) . ")", $skulist);
    }

    public function buildSAPTable($sapdesc)
    {
        $saptable = array();
        $sapentries = explode(",", $sapdesc);
        foreach ($sapentries as $sapentry)
        {
            $sapinf = explode("::", $sapentry);
            $sapname = $sapinf[0];
            $sapdata = $sapinf[1];
            $sapdarr = explode(";", $sapdata);
            $saptable[$sapname] = $sapdarr;
            unset($sapdarr);
        }
        unset($sapentries);
        return $saptable;
    }

    public function processItemBeforeId(&$item, $params = null)
    {
        // if item is not configurable, nothing to do
        if ($item["type"] !== "configurable")
        {
            return true;
        }
        if ($this->_use_defaultopc ||
             ($item["options_container"] != "container1" && $item["options_container"] != "container2"))
        {
            $item["options_container"] = "container2";
        }
        // reset option price info
        $this->_optpriceinfo = array();
        if (isset($item["super_attribute_pricing"]) && !empty($item["super_attribute_pricing"]))
        {
            $this->_optpriceinfo = $this->buildSAPTable($item["super_attribute_pricing"]);
            unset($item["super_attribute_pricing"]);
        }
        return true;
    }

    public function getMatchMode($item)
    {
        $matchmode = "auto";
        if ($this->getParam('CFGR:nolink', 0))
        {
            $matchmode = "none";
        }
        else
        {
            if ($this->getParam("CFGR:simplesbeforeconf") == 1)
            {
                $matchmode = "cursimples";
            }
            if (isset($item["simples_skus"]) && trim($item["simples_skus"]) != "")
            {
                $matchmode = "fixed";
            }
        }
        return $matchmode;
    }

    public function processItemAfterId(&$item, $params = null)
    {
        // if item is not configurable, nothing to do
        if ($item["type"] !== "configurable")
        {
            if ($this->getParam("CFGR:simplesbeforeconf") == 1)
            {
                $this->_currentsimples[] = $item["sku"];
            }
            return true;
        }
        
        // check for explicit configurable attributes
        if (isset($item["configurable_attributes"]))
        {
            $confopts = explode(",", $item["configurable_attributes"]);
            $copts = count($confopts);
            for ($i = 0; $i < $copts; $i++)
            {
                $confopts[$i] = trim($confopts[$i]);
            }
        } // if not found, try to deduce them
        else
        {
            $asconfopts = $this->getConfigurableOptsFromAsId($params["asid"]);
            // limit configurable options to ones presents & defined in item
            $confopts = array();
            foreach ($asconfopts as $confopt)
            {
                if (in_array($confopt, array_keys($item)) && trim($item[$confopt]) != "")
                {
                    $confopts[] = $confopt;
                }
            }
            unset($asconfotps);
        }
        // if no configurable attributes, nothing to do
        if (count($confopts) == 0)
        {
            $this->log(
                "No configurable attributes found for configurable sku: " . $item["sku"] . " cannot link simples.", 
                "warning");
            return true;
        }
        // set product to have options & required
        $tname = $this->tablename('catalog_product_entity');
        $sql = "UPDATE $tname SET has_options=1,required_options=1 WHERE entity_id=?";
        $this->update($sql, $params["product_id"]);
        // matching mode
        // if associated skus
        
        $matchmode = $this->getMatchMode($item);
        
        // check if item has exising options
        $pid = $params["product_id"];
        $cpsa = $this->tablename("catalog_product_super_attribute");
        $cpsal = $this->tablename("catalog_product_super_attribute_label");
        
        // process configurable options
        $ins_sa = array();
        $data_sa = array();
        $ins_sal = array();
        $data_sal = array();
        $idx = 0;
        foreach ($confopts as $confopt)
        {
            
            $attrinfo = $this->getAttrInfo($confopt);
            $attrid = $attrinfo["attribute_id"];
            $psaid = NULL;
            
            // try to get psaid for attribute
            $sql = "SELECT product_super_attribute_id as psaid FROM `$cpsa` WHERE product_id=? AND attribute_id=?";
            $psaid = $this->selectOne($sql, array($pid,$attrid), "psaid");
            // if no entry found, create one
            if ($psaid == NULL)
            {
                $sql = "INSERT INTO `$cpsa` (`product_id`,`attribute_id`,`position`) VALUES (?,?,?)";
                // inserting new options
                $psaid = $this->insert($sql, array($pid,$attrid,$idx));
            }
            
            // for all stores defined for the item
            $sids = $this->getItemStoreIds($item, 0);
            $data = array();
            $ins = array();
            foreach ($sids as $sid)
            {
                $data[] = $psaid;
                $data[] = $sid;
                $data[] = $attrinfo['frontend_label'];
                $ins[] = "(?,?,1,?)";
            }
            if (count($ins) > 0)
            {
                // insert/update attribute value for association
                $sql = "INSERT INTO `$cpsal` (`product_super_attribute_id`,`store_id`,`use_default`,`value`) VALUES " .
                     implode(",", $ins) . "ON DUPLICATE KEY UPDATE value=VALUES(`value`)";
                $this->insert($sql, $data);
            }
            // if we have price info for this attribute
            if (isset($this->_optpriceinfo[$confopt]))
            {
                $cpsap = $this->tablename("catalog_product_super_attribute_pricing");
                $wsids = $this->getItemWebsites($item);
                // if admin set as store, website force to 0
                if (in_array(0, $sids))
                {
                    $wsids = array(0);
                }
                $data = array();
                $ins = array();
                //option value list
                $optvlist=array();
                //option prices list
                $optplist=array();
                //retrieve all priced options at once to avoid duplication of existing
                //due to cache miss
                foreach ($this->_optpriceinfo[$confopt] as $opdef)
                {
                   $opinf = explode(":", $opdef);
                    $vlist=explode('//',$opinf[0]);
                    $optvlist=array_merge($optvlist,$vlist);
                    if (count($opinf) < 3)
                    {
                       // if optpriceinfo has no is_percent, force to 0
                        $opinf[] = 0;
                    }
                    foreach($vlist as $v) {
                        $optplist[$v] = array($opinf[1],$opinf[2]);
                    }
                }
                $optvlist=array_unique($optvlist);
                $optids = $this->getOptionIds($attrid, 0,$optvlist);
                unset($optvlist);

                foreach ($optids as $val=>$optid)
                {
                       // generate price info for each given website
                   foreach ($wsids as $wsid)
                   {


                        $data[] = $psaid;
                        $data[] = $optid;
                        $data[] = $optplist[$val][0];
                        $data[] = $optplist[$val][1];
                        $data[] = $wsid;
                        $ins[] = "(?,?,?,?,?)";
                   }
                }

                $sql = "INSERT INTO $cpsap (`product_super_attribute_id`,`value_index`,`pricing_value`,`is_percent`,`website_id`) VALUES " .
                     implode(",", $ins) .
                     " ON DUPLICATE KEY UPDATE pricing_value=VALUES(pricing_value),is_percent=VALUES(is_percent)";
                $this->insert($sql, $data);
                unset($data);
            }
            $idx++;
        }
        unset($confopts);
        switch ($matchmode)
        {
            case "none":
                break;
            case "auto":
                // destroy old associations
                $this->autoLink($pid);
                $this->updSimpleVisibility($pid);
                break;
            case "cursimples":
                $this->fixedLink($pid, $this->_currentsimples);
                $this->updSimpleVisibility($pid);
                
                break;
            case "fixed":
                $sskus = explode(",", $item["simples_skus"]);
                trimarray($sskus);
                $this->fixedLink($pid, $sskus);
                $this->updSimpleVisibility($pid);
                unset($item["simples_skus"]);
                unset($sskus);
                break;
            default:
                break;
        }
        // always clear current simples
        if (count($this->_currentsimples) > 0)
        {
            unset($this->_currentsimples);
            $this->_currentsimples = array();
        }
        return true;
    }

    public function processColumnList(&$cols, $params = null)
    {
        if (!in_array("options_container", $cols))
        {
            $cols = array_unique(array_merge($cols, array("options_container")));
            $this->_use_defaultopc = true;
            $this->log("no options_container set, defaulting to :Block after product info", "startup");
        }
    }

    public function getPluginParamNames()
    {
        return array("CFGR:simplesbeforeconf","CFGR:updsimplevis","CFGR:nolink");
    }

    static public function getCategory()
    {
        return "Product Type Import";
    }
}