<?php

class Magmi_ConfigurableItemProcessor extends Magmi_ItemProcessor
{
    private $_configurable_attrs = array();
    private $_use_defaultopc = false;
    private $_optpriceinfo = array();
    private $_currentsimples = array();
    private $baseImageCache = array();
	private $addsimpleimages;
    private $backImageSupport;

    public function initialize($params)
    {
		$this->addsimpleimages = $this->getParam("CFGR:addsimpleimages", 0);
		$this->backImageSupport = $this->getParam("CFGR:backimage", 0);
	
	}
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
		
		//cache select results
		$sql = "SELECT cpec.entity_id as parent_id,cpes.entity_id  as product_id  
				  FROM $cpe as cpec 
				  JOIN $cpe as cpes ON cpes.type_id IN ('simple','virtual') AND cpes.sku $cond
			  	  WHERE cpec.entity_id=?";
		$rows = $this->selectAll($sql, array_merge($conddata, array($pid)));
		
		$ids = array();
		//convert result array into a string of values
		foreach($rows as $row){
			$values .= "(".$row['parent_id'].",".$row['product_id']."),";
			$ids[] = $row['product_id'];
		}
		$values = rtrim($values, ',');
		
		if( ! empty($values) ){
			// recreate associations
			$sql = "INSERT INTO $cpsl (`parent_id`,`product_id`) VALUES $values";
			$this->insert($sql);
			$sql = "INSERT INTO $cpr (`parent_id`,`child_id`) VALUES $values";
			$this->insert($sql);
		}
        unset($conddata);
		return $ids;
    }

    public function autoLink($pid)
    {
        return $this->dolink($pid, "LIKE CONCAT(cpec.sku,'%')");
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
        return $this->dolink($pid, "IN (" . $this->arr2values($skulist) . ")", $skulist);
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
                
                foreach ($this->_optpriceinfo[$confopt] as $opdef)
                {
                    // if optpriceinfo has no is_percent, force to 0
                    $opinf = explode(":", $opdef);
                    $optids = $this->getOptionIds($attrid, 0, explode("//", $opinf[0]));
                    foreach ($optids as $optid)
                    {
                        // generate price info for each given website
                        foreach ($wsids as $wsid)
                        {
                            if (count($opinf) < 3)
                            {
                                $opinf[] = 0;
                            }
                            
                            $data[] = $psaid;
                            $data[] = $optid;
                            $data[] = $opinf[1];
                            $data[] = $opinf[2];
                            $data[] = $wsid;
                            $ins[] = "(?,?,?,?,?)";
                        }
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
		$ids = array();
        switch ($matchmode)
        {
            case "none":
                break;
            case "auto":
                // destroy old associations
                $ids = $this->autoLink($pid);
                $this->updSimpleVisibility($pid);
                break;
            case "cursimples":
                $ids = $this->fixedLink($pid, $this->_currentsimples);
                $this->updSimpleVisibility($pid);
                
                break;
            case "fixed":
                $sskus = explode(",", $item["simples_skus"]);
                trimarray($sskus);
                $ids = $this->fixedLink($pid, $sskus);
                $this->updSimpleVisibility($pid);
                unset($item["simples_skus"]);
                unset($sskus);
                break;
            default:
                break;
        }
		
		if($this->addsimpleimages >= 1){
			// Calling rewriteImageAttributes() here ensures that it runs before handleVarcharAttribute().
			$this->rewriteImageAttributes($item, $ids);
		}
		
        // always clear current simples
        if (count($this->_currentsimples) > 0)
        {
            unset($this->_currentsimples);
            $this->_currentsimples = array();
        }
        return true;
    }
	
	/**
		Cang Luo 31/10/2014 
		Overwrite the image attributes with image paths from associated products only if it has associated products
		This function must run before the handleVarcharAttribute() function of Image Attribute Processor
		@param Array  $item
				: The item array.
		@param Array $ids
				: an array of IDs
	*/
	private function rewriteImageAttributes(&$item, $ids){
		$state=0;
		if(count($ids) > 0){
			if($this->addsimpleimages>=2){
				$firstBaseImage = $this->fetchBaseImage($ids[0]);
				if( !empty($firstBaseImage) ){
					$state += 1;
					$item['image'] = $firstBaseImage;
					$item['small_image'] = $item['image'];
					$item['thumbnail'] = $item['image'];
				}
			}
			$gallery = $this->fetchGalleryImages($item, $ids);
			if( !empty($gallery) ){
				$state += 2;
				$item['media_gallery'] = $gallery;
			}
		}else{
			$this->log("No associated products found for item ".$item['sku'].", fall back to original image values.", 'warning');
		}
		$item['IMAGES_OVERWRITTEN']=$state;
	}

	/**
		Cang Luo 03/11/2014 
		select the base image path of given product ID from database
		@param string $id
				: an integer
		@return string/false	
				: returns the path of base image of given product,
				: returns false if $id is not in accepted format or no result found.
	*/
	public function fetchBaseImage($id){
		$id = trim($id);
		if(!preg_match('/^\d+$/', $id)){
			$this->log("Invalid ID for base image: $id", 'warning');
			return false;
		}
		if(!isset($this->baseImageCache[$id])){
			$image_attinfo = $this->getAttrInfo('image');
			$attribute_id = $image_attinfo['attribute_id'];
			$t = $this->tablename('catalog_product_entity_varchar');
			$sql = "SELECT value FROM $t WHERE attribute_id = $attribute_id AND entity_id = ?";
			$path = $this->selectone($sql, $id, 'value');
			if(is_null($path)){
				return false;
			}
			//caching image path
			$this->baseImageCache[$id] = $path;
		}
		return $this->baseImageCache[$id];
	}
	
	/**
		Cang Luo 03/11/2014 
		Select the gallery image paths and labels of given product IDs from database
		@param array $ids
				: an array of integers
		@return string/false	
				: returns a string of paths and labels, in the format of: path1[::label1]; path2[::label2];...
				: returns false if $ids is not in accepted format.
	*/
	public function fetchGalleryImages($item, $ids){
		$idString = $ids;
		if(is_array($ids)){
			$idString = implode(',' , $ids);
		}
		if(!preg_match('/^\d+(,\d+)*$/', $idString)){
			$this->log("Invalid IDs for gallery images: $idString", 'warning');
			return false;
		}
		$sids = $this->getItemStoreIds($item, 0);
		$sids = '('.implode(",", $sids).')';
        $tg = $this->tablename('catalog_product_entity_media_gallery');
        $tgv = $this->tablename('catalog_product_entity_media_gallery_value');
		$sql = "SELECT value, label
				 FROM $tgv AS emgv
				 JOIN $tg AS emg ON emg.value_id = emgv.value_id 
				 WHERE emg.entity_id in ($idString) AND emgv.store_id IN $sids AND value IS NOT NULL";
		$rows = $this->selectAll($sql);
		
		//back image support
		if($this->backImageSupport == 1){
			$backImage = false;
			if(count($ids) > 1){
				//get the base image of the second linked simple product
				$backImage = $this->fetchBaseImage($ids[1]);
			}
		}
		$ovalue = '';
		foreach($rows as $row){
			//back image support
			if($this->backImageSupport == 1){
				if($row['label'] === 'back'){
					$row['label'] = ''; //reset existing 'back' label
				}
				if($row['value'] === $backImage){
					//set the label of base image of the second simple product to 'back'
					$row['label'] = 'back'; 
				}
			}
			$ovalue .= $row['value']. (empty($row['label']) ? '' : '::'.$row['label']) .';';
		}
		unset($rows);
		return rtrim($ovalue, ';');
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
        return array("CFGR:simplesbeforeconf","CFGR:updsimplevis","CFGR:nolink","CFGR:addsimpleimages","CFGR:backimage");
    }

    static public function getCategory()
    {
        return "Product Type Import";
    }
}