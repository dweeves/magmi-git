<?php

/**
 * MAGENTO MASS IMPORTER CLASS
 *
 * version : 0.6
 * author : S.BRACQUEMONT aka dweeves
 * updated : 2010-10-09
 *
 */

/* use external file for db helper */
require_once("magmi_engine.php");

/**
 *
 * Magmi Product Import engine class
 * This class handle product import
 * @author dweeves
 *
 */
class Magmi_ProductImportEngine extends Magmi_Engine
{

	public $attrinfo=array();
	public $attrbytype=array();
	public $store_ids=array();
	public $status_id=array();
	public $attribute_sets=array();
	public $prod_etype;
	public $default_asid;
	public $sidcache=array();
	public $mode="update";
	private $_attributehandlers;
	private $_current_row;
	private $_optidcache=null;
	private $_curitemids=array("sku"=>null);
	private $_dstore=array();
	private $_same;
	private $_currentpid;
	private $_extra_attrs;
	private $_profile;
	private $_sid_wsscope=array();
	private $_sid_sscope=array();
	private $_prodcols=array();
	private $_stockcols=array();
	private $_skustats=array();
	private $_item_meta;
	

	public function addExtraAttribute($attr)
	{
		$attinfo=$this->attrinfo[$attr];
		$this->_extra_attrs[$attinfo["backend_type"]]["data"][]=$attinfo;

	}
	/**
	 * constructor
	 * @param string $conffile : configuration .ini filename
	 */
	public function __construct()
	{
	  parent::__construct();
		$this->setBuiltinPluginClasses("itemprocessors",dirname(dirname(__FILE__))."/plugins/inc/magmi_defaultattributehandler.php::Magmi_DefaultAttributeItemProcessor");
	}


	public function getSkuStats()
	{
		return $this->_skustats;
	}

	/**
	 * (non-PHPdoc)
	 * @see Magmi_Engine::getEngineInfo()
	 */
	public function getEngineInfo()
	{
		return array("name"=>"Magmi Product Import Engine","version"=>"1.7.4","author"=>"dweeves");
	}

	/**
	 * load properties
	 * @param string $conf : configuration .ini filename
	 */




	public function initProdType()
	{
		$tname=$this->tablename("eav_entity_type");
		$this->prod_etype=$this->selectone("SELECT entity_type_id FROM $tname WHERE entity_type_code=?","catalog_product","entity_type_id");
		$this->default_asid=$this->getAttributeSetId('Default');
	}



	public function getPluginFamilies()
	{
		return array("datasources","general","itemprocessors");
	}

	public function registerAttributeHandler($ahinst,$attdeflist)
	{
		foreach($attdeflist as $attdef)
		{
			$ad=explode(":",$attdef);
			if(count($ad)!=2)
			{
				$this->log("Invalid registration string ($attdef) :".get_class($ahinst),"warning");
			}
			else
			{
				$this->_attributehandlers[$attdef]=$ahinst;
			}
		}
	}




	/**
	 *
	 * Return list of store codes that share the same website than the stores passed as parameter
	 * @param string $scodes comma separated list of store view codes
	 */
	public function getStoreIdsForWebsiteScope($scodes)
	{
		if(!isset($this->_sid_wsscope[$scodes]))
		{
			$this->_sid_wsscope[$scodes]=array();
			$wscarr=csl2arr($scodes);
			$qcolstr=$this->arr2values($wscarr);
			$cs=$this->tablename("core_store");
			$sql="SELECT csdep.store_id FROM $cs as csmain 
				 JOIN $cs as csdep ON csdep.website_id=csmain.website_id
				 WHERE csmain.code IN ($qcolstr) ";
			$sidrows=$this->selectAll($sql,$wscarr);
			foreach($sidrows as $sidrow)
			{
				$this->_sid_wsscope[$scodes][]=$sidrow["store_id"];
			}
		}
		return $this->_sid_wsscope[$scodes];
	}

	public function getStoreIdsForStoreScope($scodes)
	{
		if(!isset($this->_sid_sscope[$scodes]))
		{
			$this->_sid_sscope[$scodes]=array();
			$scarr=csl2arr($scodes);
			$qcolstr=$this->arr2values($scarr);
			$cs=$this->tablename("core_store");
			$sql="SELECT csmain.store_id from $cs as csmain WHERE csmain.code IN ($qcolstr)";
			$sidrows=$this->selectAll($sql,$scarr);
			foreach($sidrows as $sidrow)
			{
				$this->_sid_sscope[$scodes][]=$sidrow["store_id"];
			}

		}
		return $this->_sid_sscope[$scodes];
	}


	/**
	 * returns mode
	 */
	public function getMode()
	{
		return $this->mode;
	}

	public function getProdCols()
	{
		if(count($this->_prodcols)==0)
		{
			$sql='DESCRIBE '.$this->tablename('catalog_product_entity');
			$rows=$this->selectAll($sql);
			foreach($rows as $row)
			{
				$this->_prodcols[]=$row['Field'];
			}
		}
		return $this->_prodcols;
	}

	public function getStockCols()
	{
		if(count($this->_stockcols)==0)
		{
			$sql='DESCRIBE '.$this->tablename('cataloginventory_stock_item');
			$rows=$this->selectAll($sql);
			foreach($rows as $row)
			{
				$this->_stockcols[]=$row['Field'];
			}
		}
		return $this->_stockcols;
	}
	/**
	 * Initialize attribute infos to be used during import
	 * @param array $cols : array of attribute names
	 */
	public function checkRequired($cols)
	{
		$eav_attr=$this->tablename("eav_attribute");
		$sql="SELECT attribute_code FROM $eav_attr WHERE  is_required=1
		AND frontend_input!='' AND frontend_label!='' AND entity_type_id=?";
		$required=$this->selectAll($sql,$this->prod_etype);
		$reqcols=array();
		foreach($required as $line)
		{
			$reqcols[]=$line["attribute_code"];
		}
		$required=array_diff($reqcols,$cols);
		return $required;
	}

	/**
	 *
	 * gets attribute metadata from DB and put it in attribute metadata caches
	 * @param array $cols list of attribute codes to get metadata from
	 *                    if in this list, some values are not attribute code, no metadata will be cached.
	 */
	public function initAttrInfos($cols)
	{
		if($this->prod_etype==null)
		{
			//Find product entity type
			$tname=$this->tablename("eav_entity_type");
			$this->prod_etype=$this->selectone("SELECT entity_type_id FROM $tname WHERE entity_type_code=?","catalog_product","entity_type_id");
		}

		$toscan=array_values(array_diff($cols,array_keys($this->attrinfo)));
		if(count($toscan)>0)
		{
			//create statement parameter string ?,?,?.....
			$qcolstr=$this->arr2values($toscan);

			$tname=$this->tablename("eav_attribute");
			if($this->getMagentoVersion()!="1.3.x")
			{
				$extra=$this->tablename("catalog_eav_attribute");
				//SQL for selecting attribute properties for all wanted attributes
				$sql="SELECT `$tname`.*,$extra.is_global,$extra.apply_to FROM `$tname`
				LEFT JOIN $extra ON $tname.attribute_id=$extra.attribute_id
				WHERE  ($tname.attribute_code IN ($qcolstr)) AND (entity_type_id=?)";		
			}
			else
			{
				$sql="SELECT `$tname`.* FROM `$tname` WHERE ($tname.attribute_code IN ($qcolstr)) AND (entity_type_id=?)";
			}
			$toscan[]=$this->prod_etype;
			$result=$this->selectAll($sql,$toscan);

			$attrinfs=array();
			//create an attribute code based array for the wanted columns
			foreach($result as $r)
			{
				$attrinfs[$r["attribute_code"]]=$r;
			}
			unset($result);

			//create a backend_type based array for the wanted columns
			//this will greatly help for optimizing inserts when creating attributes
			//since eav_ model for attributes has one table per backend type
			foreach($attrinfs as $k=>$a)
			{
				//do not index attributes that are not in header (media_gallery may have been inserted for other purposes)
				if(!in_array($k,$cols))
				{
					continue;
				}
				$bt=$a["backend_type"];
				if(!isset($this->attrbytype[$bt]))
				{
					$this->attrbytype[$bt]=array("data"=>array());
				}
				$this->attrbytype[$bt]["data"][]=$a;
			}
			//now add a fast index in the attrbytype array to store id list in a comma separated form
			foreach($this->attrbytype as $bt=>$test)
			{
				$idlist;
				foreach($test["data"] as $it)
				{
					$idlist[]=$it["attribute_id"];
				}
				$this->attrbytype[$bt]["ids"]=implode(",",$idlist);
			}
			$this->attrinfo=array_merge($this->attrinfo,$attrinfs);
		}
		$notattribs=array_diff($cols,array_keys($this->attrinfo));
		foreach($notattribs as $k)
		{
			$this->attrinfo[$k]=null;
		}
		/*now we have 2 index arrays
		 1. $this->attrinfo  which has the following structure:
		 key : attribute_code
		 value : attribute_properties
		 2. $this->attrbytype which has the following structure:
		 key : attribute backend type
		 value : array of :
		 data => array of attribute_properties ,one for each attribute that match
		 the backend type
		 ids => list of attribute ids of the backend type */
	}

	/**
	 *
	 * retrieves attribute metadata
	 * @param string $attcode attribute code
	 * @param boolean $lookup if set, this will try to get info from DB otherwise will get from cache and may return null if not cached
	 * @return array attribute metadata info
	 */
	public function getAttrInfo($attcode,$lookup=true)
	{
		$attrinf=isset($this->attrinfo[$attcode])?$this->attrinfo[$attcode]:null;
		if($attrinf==null && $lookup)
		{
			$this->initAttrInfos(array($attcode));

		}
		if(count($this->attrinfo[$attcode])==0)
		{

			$attrinf=null;
			unset($this->attrinfo[$attcode]);
		}
		else
		{
			$attrinf=$this->attrinfo[$attcode];
		}
		return $attrinf;
	}

	/**
	 * retrieves attribute set id for a given attribute set name
	 * @param string $asname : attribute set name
	 */
	public function getAttributeSetId($asname)
	{

		if(!isset($this->attribute_sets[$asname]))
		{
			$tname=$this->tablename("eav_attribute_set");
			$asid=$this->selectone(
				"SELECT attribute_set_id FROM $tname WHERE attribute_set_name=? AND entity_type_id=?",
			array($asname,$this->prod_etype),
				'attribute_set_id');
			$this->attribute_sets[$asname]=$asid;
		}
		return $this->attribute_sets[$asname];
	}

	/**
	 * Retrieves product id for a given sku
	 * @param string $sku : sku of product to get id for
	 */
	public function getProductIds($sku)
	{
		$tname=$this->tablename("catalog_product_entity");
		$result=$this->selectAll(
		"SELECT sku,entity_id as pid,attribute_set_id  as asid,type_id as type FROM $tname WHERE sku=?",
		$sku);
		if(count($result)>0)
		{
			$pids= $result[0];
			$pids["__new"]=false;
			return $pids;
		}
		else
		{
			return false;
		}
	}
	
	

	/**
	 * creates a product in magento database
	 * @param array $item: product attributes as array with key:attribute name,value:attribute value
	 * @param int $asid : attribute set id for values
	 * @return : product id for newly created product
	 */
	public function createProduct($item,$asid)
	{
		//force item type if not exists
		if(!isset($item["type"]))
		{
			$item["type"]="simple";
		}
		$tname=$this->tablename('catalog_product_entity');
		$item['type_id']=$item['type'];
		$item['attribute_set_id']=$asid;
		$item['entity_type_id']=$this->prod_etype;
		$item['created_at']=strftime("%Y-%m-%d %H:%M:%S");
		$item['updated_at']=strftime("%Y-%m-%d %H:%M:%S");
		$columns=array_intersect(array_keys($item), $this->getProdCols());
		$values=$this->filterkvarr($item, $columns);
		$sql="INSERT INTO `$tname` (".implode(",",$columns).") VALUES (".$this->arr2values($columns).")";
		$lastid=$this->insert($sql,array_values($values));
		return $lastid;
	}

	/**
	 * Updateds product update time
	 * @param unknown_type $pid : entity_id of product
	 */
	public function touchProduct($pid)
	{
		$tname=$this->tablename('catalog_product_entity');
		$this->update("UPDATE $tname SET updated_at=? WHERE entity_id=?",array(strftime("%Y-%m-%d %H:%M:%S"),$pid));
	}

	/**
	 * Get Option id for select attributes based on value
	 * @param int $attid : attribute id to find option id from value
	 * @param mixed $optval : value to get option id for
	 * @return : array of lines (should be as much as values found),"opvd"=>option_id for value on store 0,"opvs" option id for value on current store
	 */
	public function getOptionsFromValues($attid,$store_id,$optvals)
	{
		$ovstr=substr(str_repeat("?,",count($optvals)),0,-1);
		$t1=$this->tablename('eav_attribute_option');
		$t2=$this->tablename('eav_attribute_option_value');
		$sql="SELECT optvals.option_id as opvs,optvals.value FROM $t2 as optvals";
		$sql.=" JOIN $t1 as opt ON opt.option_id=optvals.option_id AND opt.attribute_id=?";
		$sql.=" WHERE optvals.store_id=? AND BINARY optvals.value IN  ($ovstr)";
		return $this->selectAll($sql,array_merge(array($attid,$store_id),$optvals));
	}


	/* create a new option entry for an attribute */
	public function createOption($attid)
	{
		$t=$this->tablename('eav_attribute_option');
		$optid=$this->insert("INSERT INTO $t (attribute_id) VALUES (?)",$attid);
		return $optid;
	}
	/**
	 * Creates a new option value for an option entry for a store
	 * @param int $optid : option entry id
	 * @param int $store_id : store id to add value for
	 * @param mixed $optval : new option value to add
	 * @return : option id for new created value
	 */
	public function  createOptionValue($optid,$store_id,$optval)
	{
		$t=$this->tablename('eav_attribute_option_value');
		$optval_id=$this->insert("INSERT INTO $t (option_id,store_id,value) VALUES (?,?,?)",array($optid,$store_id,$optval));
		return $optval_id;
	}


	public function getOptionIds($attid,$storeid,$values)
	{
		$optids=array();
		$svalues=array();
		$avalues=array();
		//Matching refstore value
		foreach($values as $val)
		{
			if(preg_match("|^(.*)::\[(.*)\]$|",$val,$matches))
			{
				$svalues[]=$matches[2];
				$avalues[]=$matches[1];
			}
			else
			{
				$svalues[]=$val;
				$avalues[]=$val;
			}
		}
		$existing=$this->getOptionsFromValues($attid,0,$avalues);
		$exvals=array();
		foreach($existing as $optdesc)
		{
			$exvals[]=$optdesc["value"];
		}
		$new=array_merge(array_diff($avalues,$exvals));
		if($storeid==0)
		{
			foreach($new as $nval)
			{
				$row=array("opvs"=>$this->createOption($attid),"value"=>$nval);
				$this->createOptionValue($row["opvs"],$storeid,$nval);
				$existing[]=$row;
			}
			$this->cacheOptIds($attid,$existing);

		}
		else
		{

			$brows=$this->getCachedOptIds($attid);
			if(count($brows)==0)
			{
				$existing=$this->getOptionsFromValues($attid,0,$avalues);
				$new=array_merge(array_diff($avalues,$exvals));
				foreach($new as $nval)
				{
					$row=array("opvs"=>$this->createOption($attid),"value"=>$nval);
					$this->createOptionValue($row["opvs"],$storeid,$nval);
					$existing[]=$row;
				}
				$this->cacheOptIds($attid,$existing);
				$brows=$this->getCachedOptIds($attid);
			}
			foreach($existing as $ex)
			{
				array_shift($brows);
			}
			$cnew=count($new);
			for($i=0;$i<$cnew;$i++)
			{
				$row=$brows[$i];
				if(!isset($row["opvs"]))
				{
					$row["opvs"]=$this->createOption($attid);
					$this->createOptionValue($row["opvs"],0,$new[$i]);
				}
				$this->createOptionValue($row["opvs"],$storeid,$new[$i]);
				$existing[]=$row;
			}
		}
		$optids=array();
		foreach($existing as $row)
		{
			$optids[]=$row["opvs"];
		}
		unset($existing);
		unset($exvals);
		return $optids;

	}

	public function cacheOptIds($attid,$row)
	{
		$this->_optidcache[$attid]=$row;
	}

	public function getCachedOptIds($attid)
	{
		if(isset($this->_optidcache[$attid]))
		{
			return $this->_optidcache[$attid];
		}
		else
		{
			return null;
		}
	}


	/**
	 * returns tax class id for a given tax class value
	 * @param $tcvalue : tax class value
	 */
	public function getTaxClassId($tcvalue)
	{
		$t=$this->tablename('tax_class');
		$txid=$this->selectone("SELECT class_id FROM $t WHERE class_name=?",array($tcvalue),"class_id");
		//bugfix for tax class id, if not found set it to none
		if(!isset($txid))
		{
			$txid=0;
		}
		return $txid;
	}

	public function parseCalculatedValue($pvalue,$item,$params)
	{
		$matches=array();
		$ik=array_keys($item);
		$rep="";
		
		//replace base item values
		while(preg_match("|\{item\.(.*?)\}|",$pvalue,$matches))
		{
			foreach($matches as $match)
			{
				if($match!=$matches[0])
				{
					if(in_array($match,$ik))
					{
						$rep='$item["'.$match.'"]';
					}
					else
					{
						$rep="";
					}
					$pvalue=str_replace($matches[0],$rep,$pvalue);
				}
			}
		}
		unset($matches);
		//replac meta
		$meta=$params;
		
		
		while(preg_match("|\{meta\.(.*?)\}|",$pvalue,$matches))
		{
			foreach($matches as $match)
			{
				if($match!=$matches[0])
				{
					if(in_array($match,$ik))
					{
						$rep='$meta["'.$match.'"]';
					}
					else
					{
						$rep="";
					}
					$pvalue=str_replace($matches[0],$rep,$pvalue);
				}
			}
		}
		unset($matches);
	
	
		//replacing expr values
		while(preg_match("|\{\{\s*(.*?)\s*\}\}|",$pvalue,$matches))
		{
			foreach($matches as $match)
			{
				if($match!=$matches[0])
				{
					$code=trim($match);
					//settiing meta values
					$meta=$params;
					$rep=eval("return ($code);");
					//escape potential "{{xxx}}" values in interpreted target
					//so that they won't be reparsed in next round
					$rep=preg_replace("|\{\{\s*(.*?)\s*\}\}|", "____$1____", $rep);
					$pvalue=str_replace($matches[0],$rep,$pvalue);							
				}				
			}
		}
		
		//unescape matches
		$pvalue=preg_replace("|____(.*?)____|",'{{$1}}',$pvalue);
		//replacing single values not in complex values
		while(preg_match('|\$item\["(.*?)"\]|',$pvalue,$matches))
		{
			foreach($matches as $match)
			{
				if($match!=$matches[0])
				{
					if(in_array($match,$ik))
					{
						$rep=$item[$match];
					}
					else
					{
						$rep="";
					}
					$pvalue=str_replace($matches[0],$rep,$pvalue);
				}
			}
		}
		
		unset($matches);
		return $pvalue;
	}

	/**
	 *
	 * Return affected store ids for a given item given an attribute scope
	 * @param array $item : item to get store for scope
	 * @param string $scope : scope to get stores from.
	 */
	public function getItemStoreIds($item,$scope=0)
	{
		if(!isset($item['store']))
		{
			$item['store']="admin";
		}
		switch($scope){
			//global scope
			case 1:
				$bstore_ids=$this->getStoreIdsForStoreScope("admin");
				break;
				//store scope
			case 0:
				$bstore_ids=$this->getStoreIdsForStoreScope($item["store"]);
				break;
				//website scope
			case 2:
				$bstore_ids=$this->getStoreIdsForWebsiteScope($item["store"]);
				break;
		}

		$itemstores=array_unique(array_merge($this->_dstore,$bstore_ids));
		sort($itemstores);
		return $itemstores;
	}

	/**
	 * Create product attribute from values for a given product id
	 * @param $pid : product id to create attribute values for
	 * @param $item : attribute values in an array indexed by attribute_code
	 */
	public function createAttributes($pid,&$item,$attmap,$isnew,$itemids)
	{
		/**
		 * get all store ids
		 */
		$this->_extra_attrs=array();
		/* now is the interesring part */
		/* iterate on attribute backend type index */
		foreach($attmap as $tp=>$a)
		{
			/* for static types, do not insert into attribute tables */
			if($tp=="static")
			{
				continue;
			}

			//table name for backend type data
			$cpet=$this->tablename("catalog_product_entity_$tp");
			//data table for inserts
			$data=array();
			//inserts to perform on backend type eav
			$inserts=array();
			//deletes to perform on backend type eav
			$deletes=array();

			//use reflection to find special handlers
			$typehandler="handle".ucfirst($tp)."Attribute";
			//iterate on all attribute descriptions for the given backend type
			foreach($a["data"] as $attrdesc)
			{
				//check item type is compatible with attribute apply_to
				if($attrdesc["apply_to"]!=null && strpos($attrdesc["apply_to"],$itemids["type"])===false)
				{
					//do not handle attribute if it does not apply to the product type
					continue;
				}			
				//get attribute id
				$attid=$attrdesc["attribute_id"];
				//get attribute value in the item to insert based on code
				$atthandler="handle".ucfirst($attrdesc["attribute_code"])."Attribute";
				$attrcode=$attrdesc["attribute_code"];
				//if the attribute code is no more in item (plugins may have come into the way), continue
				if(!in_array($attrcode,array_keys($item)))
				{
					continue;
				}
				//get the item value
				$ivalue=$item[$attrcode];
				//get item store id for the current attribute
				$store_ids=$this->getItemStoreIds($item,$attrdesc["is_global"]);


				//do not handle empty generic int values in create mode
				if($ivalue=="" && $this->mode!="update" && $tp=="int")
				{
					continue;
				}
				//for all store ids
				foreach($store_ids as $store_id)
				{

					//base output value to be inserted = base source value
					$ovalue=$ivalue;
					//check for attribute handlers for current attribute
					foreach($this->_attributehandlers as $match=>$ah)
					{
						$matchinfo=explode(":",$match);
						$mtype=$matchinfo[0];
						$mtest=$matchinfo[1];
						unset($matchinfo);
						unset($hvalue);
						if(preg_match("/$mtest/",$attrdesc[$mtype]))
						{
							//if there is a specific handler for attribute, use it
							if(method_exists($ah,$atthandler))
							{
								$hvalue=$ah->$atthandler($pid,$item,$store_id,$attrcode,$attrdesc,$ivalue);
							}
							else
							//use generic type attribute
							if(method_exists($ah,$typehandler))
							{
								$hvalue=$ah->$typehandler($pid,$item,$store_id,$attrcode,$attrdesc,$ivalue);
							}
							//if handlers returned a value that is not "__MAGMI_UNHANDLED__" , we have our output value
							if(isset($hvalue) && $hvalue!="__MAGMI_UNHANDLED__")
							{
								$ovalue=$hvalue;
								break;
							}
						}
					}
					//if __MAGMI_UNHANDLED__ ,don't insert anything
					if($ovalue=="__MAGMI_UNHANDLED__")
					{
						$ovalue=false;
					}
					//if handled value is a "DELETE"
					if($ovalue=="__MAGMI_DELETE__")
					{
						$deletes[]=$attid;
					}
					else
					//if we have something to do with this value
					if($ovalue!==false)
					{

						$data[]=$this->prod_etype;
						$data[]=$attid;
						$data[]=$store_id;
						$data[]=$pid;
						$data[]=$ovalue;
						$insstr="(?,?,?,?,?)";
						$inserts[]=$insstr;
					}

					//if one of the store in the list is admin
					if($store_id==0)
					{
						$sids=$store_ids;
						//remove all values bound to the other stores for this attribute,so that they default to "use admin value"
						array_shift($sids);
						if(count($sids)>0)
						{
							$sidlist=implode(",",$sids);
							$ddata=array($this->prod_etype,$attid,$pid);
							$sql="DELETE FROM $cpet WHERE entity_type_id=? AND attribute_id=? AND store_id IN ($sidlist) AND entity_id=?";
							$this->delete($sql,$ddata);
							unset($ddata);
						}
						unset($sids);
						break;
					}
				}
			}



			if(!empty($inserts))
			{
				//now perform insert for all values of the the current backend type in one
				//single insert
				$sql="INSERT INTO $cpet
			(`entity_type_id`, `attribute_id`, `store_id`, `entity_id`, `value`)
			VALUES ";
				$sql.=implode(",",$inserts);
				//this one taken from mysql log analysis of magento import
				//smart one :)
				$sql.=" ON DUPLICATE KEY UPDATE `value`=VALUES(`value`)";
				$this->insert($sql,$data);
			}

			if(!empty($deletes))
			{
				$sidlist=implode(",",$store_ids);
				$attidlist=implode(",",$deletes);
				$sql="DELETE FROM $cpet WHERE entity_type_id=? AND attribute_id IN ($attidlist) AND store_id IN ($sidlist) AND entity_id=?";
				$this->delete($sql,array($this->prod_etype,$pid));
			}

			if(empty($deletes) && empty($inserts) && $isnew)
			{
				if(!$this->_same)
				{
					$this->log("No $tp Attributes created for sku ".$item["sku"],"warning");
				}
			}
			unset($store_ids);
			unset($data);
			unset($inserts);
			unset($deletes);
		}
		return $this->_extra_attrs;
	}



	/**
	 * update product stock
	 * @param int $pid : product id
	 * @param array $item : attribute values for product indexed by attribute_code
	 */
	public function updateStock($pid,$item,$isnew)
	{

		$scols=$this->getStockCols();
		#take only stock columns that are in item
		$itstockcols=array_intersect(array_keys($item),$scols);
		#no stock columns set, item exists, no stock update needed.
		if(count($itstockcols)==0 && !$isnew)
		{
			return;
		}
		$csit=$this->tablename("cataloginventory_stock_item");
		$css=$this->tablename("cataloginventory_stock_status");
		#calculate is_in_stock flag
		if(isset($item["qty"]))
		{
			if(!isset($item["manage_stock"]))
			{
				$item["manage_stock"]=1;
				$item["use_config_manage_stock"]=0;
			}

			$mqty=(isset($item["min_qty"])?$item["min_qty"]:0);
			$is_in_stock=isset($item["is_in_stock"])?$item["is_in_stock"]:($item["qty"]>$mqty?1:0);
			$item["is_in_stock"]=$is_in_stock;
		}
		#take only stock columns that are in  item after item update
		$common=array_intersect(array_keys($item),$scols);

		#create stock item line if needed
		$stock_id=(isset($item["stock_id"])?$item["stock_id"]:1);
		$sql="INSERT IGNORE INTO `$csit` (product_id,stock_id) VALUES (?,?)";
		$this->insert($sql,array($pid,$stock_id));

		if(count($common)>0)
		{
			$cols=$this->arr2columns($common);
			$stockvals=$this->filterkvarr($item,$common);

			#fill with values
			$svstr=$this->arr2update($stockvals);
			if(isset($item["qty"]) && $item["qty"]!="")
			{
				$relqty=NULL;
				
				//if magmi_qty_absolute flag is not set, then use standard "relative" qty parsing.
				if(!isset($item["magmi_qty_absolute"]) || $item["magmi_qty_absolute"]==0)
				{
					//test for relative qty
					if($item["qty"][0]=="+" || $item["qty"][0]=="-")
					{
						$relqty=getRelative($item["qty"]);
					}
				}
				//if relative qty
				if($relqty!=NULL)
				{
					//update UPDATE statement value affectation
					$svstr=preg_replace("/(^|,)qty=\?/","$1qty=qty$relqty?",$svstr);
					$stockvals["qty"]=$item["qty"];
					$svstr=str_replace("is_in_stock=?","is_in_stock=(qty>min_qty)",$svstr);
					unset($stockvals["is_in_stock"]);
				}
			}
			$sql="UPDATE `$csit` SET $svstr WHERE product_id=? AND stock_id=?";
			$this->update($sql,array_merge(array_values($stockvals),array($pid,$stock_id)));
		}

		$data=array();
		$wsids=$this->getItemWebsites($item);
		$csscols=array("website_id","product_id","stock_id","qty","stock_status");
		$cssvals=$this->filterkvarr($item,$csscols);
		$stock_id=(isset($cssvals["stock_id"])?$cssvals["stock_id"]:1);
		$stock_status=(isset($cssvals["stock_status"])?$cssvals["stock_status"]:1);
		//new auto synchro on lat inserted stock item values for stock status.
		//also works for multiple stock ids.
		$sql="INSERT INTO `$css` SELECT csit.product_id,ws.website_id,cis.stock_id,csit.qty,? as stock_status
				FROM `$csit` as csit 
				JOIN ".$this->tablename("core_website")." as ws ON ws.website_id IN (".$this->arr2values($wsids).") 
				JOIN ".$this->tablename("cataloginventory_stock")." as cis ON cis.stock_id=?
				WHERE product_id=?
				ON DUPLICATE KEY UPDATE stock_status=VALUES(`stock_status`),qty=VALUES(`qty`)";
		$data[]=$stock_status;
		$data=array_merge($data,$wsids);
		$data[]=$stock_id;
		$data[]=$pid;
		$this->insert($sql,$data);
		unset($data);
	}
	/**
	 * assign categories for a given product id from values
	 * categories should already be created & csv values should be as the ones
	 * given in the magento export (ie:  comma separated ids, minus 1,2)
	 * @param int $pid : product id
	 * @param array $item : attribute values for product indexed by attribute_code
	 */
	public function assignCategories($pid,$item)
	{
		$cce=$this->tablename("catalog_category_entity");
		$ccpt=$this->tablename("catalog_category_product");
		#handle assignment reset
		if(!isset($item["category_reset"]) || $item["category_reset"]==1)
		{
			$sql="DELETE $ccpt.*
			FROM $ccpt
			JOIN $cce ON $cce.entity_id=$ccpt.category_id
			WHERE product_id=?";
			$this->delete($sql,$pid);
		}


		$inserts=array();
		$data=array();
		$cdata=array();
		$ddata=array();
		$cpos=array();
		$catids=csl2arr($item["category_ids"]);
		
		//find positive category assignments
		
		foreach($catids as $catdef)
		{
		    $a=explode("::",$catdef);
			$catid=$a[0];
			$catpos=(count($a)>1?$a[1]:"0");
			$rel=getRelative($catid);
			if($rel=="-")
			{
				$ddata[]=$catid;
			}
			else
			{
				$cdata[$catid]=$catpos;
			}
		}
		
		//get all "real ids"
		$scatids=array_keys($cdata);
		$rcatids=$this->selectAll("SELECT cce.entity_id as id FROM $cce as cce WHERE cce.entity_id IN (".$this->arr2values($scatids).")",$scatids);
		$vcatids=array();
		foreach($rcatids as $rcatrow)
		{
			$vcatids[]=$rcatrow['id'];
		}
		//now get the diff
		$diff=array_diff(array_keys($cdata),$vcatids);
		$cdiff=count($diff);
		//if there are some, warning
		if($cdiff>0)
		{
			$this->log('Invalid category ids found for sku '.$item['sku'].":".implode(",",$diff),"warning");
			//remove invalid category entries
			for($i=0;$i<$cdiff;$i++)
			{
				unset($cdata[$diff[$i]]);
			}
		}
		
		if(count($cdata)==0)
		{
			$this->log('No valid categories found, skip category assingment for sku '.$item['sku'],"warning");
		}
		
		#now we have verified ids
		foreach($cdata as $catid=>$catpos)
		{
				$inserts[]="(?,?,?)";
				$data[]=$catid;
				$data[]=$pid;
				$data[]=$catpos;
		}
			
		#peform deletion of removed category affectation
		if(count($ddata)>0)
		{
			$sql="DELETE FROM $ccpt WHERE category_id IN (".$this->arr2values($ddata).") AND product_id=?";
			$ddata[]=$pid;
			$this->delete($sql,$ddata);
			unset($ddata);
		}
		
		
		
		#create new category assignment for products, if multi store with repeated ids
		#ignore duplicates
		if(count($inserts)>0)
		{
			$sql="INSERT INTO $ccpt (`category_id`,`product_id`,`position`)
				 VALUES	 ";
			$sql.=implode(",",$inserts);
			$sql.="ON DUPLICATE KEY UPDATE position=VALUES(`position`)";
			$this->insert($sql,$data);
			unset($data);
		}
		unset($deletes);
		unset($inserts);
	}


	public function getItemWebsites($item,$default=false)
	{
		//support for websites column if set
		if(!empty($item["websites"])) 
		{
			if(!isset($this->_wsids[$item["websites"]]))
			{
				$this->_wsids[$item["websites"]]=array();
		
				$cws=$this->tablename("core_website");
				$wscodes=csl2arr($item["websites"]);
				$qcolstr=$this->arr2values($wscodes);
				$rows=$this->selectAll("SELECT website_id FROM $cws WHERE code IN ($qcolstr)",$wscodes);
				foreach($rows as $row)
				{
					$this->_wsids[$item["websites"]][]=$row['website_id'];
				}
			}
			return $this->_wsids[$item["websites"]];
		}
		
	  if(!isset($item['store']))
		{
			$item['store']="admin";
		}
		$k=$item["store"];
		
		if(!isset($this->_wsids[$k]))
		{
				$this->_wsids[$k]=array();
				$cs=$this->tablename("core_store");
				if(trim($k)!="admin")
				{
					$scodes=csl2arr($k);
					$qcolstr=$this->arr2values($scodes);
					$rows=$this->selectAll("SELECT website_id FROM $cs WHERE code IN ($qcolstr) AND store_id!=0 GROUP BY website_id",$scodes);
				}
				else
				{
					$rows=$this->selectAll("SELECT website_id FROM $cs WHERE store_id!=0 GROUP BY website_id ");
				}
				foreach($rows as $row)
				{
					$this->_wsids[$k][]=$row['website_id'];
				}
			}
			return $this->_wsids[$k];
	
	}

	/**
	 * set website of product if not exists
	 * @param int $pid : product id
	 * @param array $item : attribute values for product indexed by attribute_code
	 */
	public function updateWebSites($pid,$item)
	{
		$wsids=$this->getItemWebsites($item);
		$qcolstr=$this->arr2values($wsids);
		$cpst=$this->tablename("catalog_product_website");
		$cws=$this->tablename("core_website");
		//associate product with all websites in a single multi insert (use ignore to avoid duplicates)
		$sql="INSERT IGNORE INTO `$cpst` (`product_id`, `website_id`) SELECT ?,website_id FROM $cws WHERE website_id IN ($qcolstr)";
		$this->insert($sql,array_merge(array($pid),$wsids));
	}



	public function clearOptCache()
	{
		unset($this->_optidcache);
		$this->_optidcache=array();
	}

	public function onNewSku($sku,$existing)
	{
		$this->clearOptCache();
		//only assign values to store 0 by default in create mode for new sku
		//for store related options
		if(!$existing)
		{
			$this->_dstore=array(0);
		}
		else
		{
			$this->_dstore=array();
		}
		$this->_same=false;
	}

	public function onSameSku($sku)
	{
		unset($this->_dstore);
		$this->_dstore=array();
		$this->_same=true;
	}

	
	public function currentItemExists()
	{
		return $this->_curitemids["__new"]==false;
	}
	
	
	public function getItemIds($item)
	{
		$sku=$item["sku"];
		if($sku!=$this->_curitemids["sku"])
		{
			//try to find item ids in db
			$cids=$this->getProductIds($sku);
			if($cids!==false)
			{
				//if found use it
				$this->_curitemids=$cids;
			}
			else
			{
				//only sku & attribute set id from datasource otherwise.
				$this->_curitemids=array("pid"=>null,"sku"=>$sku,"asid"=>isset($item["attribute_set"])?$this->getAttributeSetId($item["attribute_set"]):$this->default_asid,"type"=>isset($item["type"])?$item["type"]:"Simple","__new"=>true);
			}
			//do not reset values for existing if non admin	
			$this->onNewSku($sku,($cids!==false));
			unset($cids);
		}
		else
		{
			$this->onSameSku($sku);
		}
		return $this->_curitemids;
	}

	public function handleIgnore(&$item)
	{
		//filter __MAGMI_IGNORE__ COLUMNS
		foreach($item as $k=>$v)
		{
			if($v=="__MAGMI_IGNORE__")
			{
				unset($item[$k]);
			}
		}
	}
	public function findItemStores($pid)
	{
		$sql="SELECT cs.code FROM ".$this->tablename("catalog_product_website")." AS cpw".
		" JOIN ".$this->tablename("core_store")." as cs ON cs.website_id=cpw.website_id".
		" WHERE cpw.product_id=?";
		$result=$this->selectAll($sql,array($pid));
		$scodes=array();
		foreach($result as $row)
		{
			$scodes[]=$row["code"];
		}
		return implode(",",$scodes);
	}

	public function checkItemStores($scodes)
	{
		if($scodes=="admin")
		{
			return $scodes;
		}

		$scarr=explode(",",$scodes);
		trimarray($scarr);
		$rscode=array();
		$sql="SELECT code FROM ".$this->tablename("core_store")." WHERE code IN (".$this->arr2values($scarr).")";
		$result=$this->selectAll($sql,$scarr);
		$rscodes=array();
		foreach($result as $row)
		{
			$rscodes[]=$row["code"];
		}
		$diff=array_diff($scarr, $rscodes);
		$out="";
		if(count($diff)>0)
		{
			$out="Invalid store code(s) found:".implode(",",$diff);
		}
		if($out!="")
		{
			if(count($rscodes)==0)
			{
				$out.=", NO VALID STORE FOUND";
			}
			$this->log($out,"warning");
		}

		return implode(",",$rscodes);
	}

	public function checkstore(&$item,$pid,$isnew)
	{
		//we have store column set , just check
		if(isset($item["store"]) && trim($item["store"])!="")
		{
			$scodes=$this->checkItemStores($item["store"]);
		}
		else
		{
			$scodes="admin";
		}
		if($scodes=="")
		{
			return false;
		}
		$item["store"]=$scodes;
		return true;
	}
	/**
	 * full import workflow for item
	 * @param array $item : attribute values for product indexed by attribute_code
	 */
	public function importItem($item)
	{

		$this->handleIgnore($item);
		if(Magmi_StateManager::getState()=="canceled")
		{
			throw new Exception("MAGMI_RUN_CANCELED");
		}
		//first step

		if(!$this->callPlugins("itemprocessors","processItemBeforeId",$item))
		{
			return false;
		}
		
		//check if sku has been reset
		if(!isset($item["sku"]) || trim($item["sku"])=='')
		{
			$this->log('No sku info found for record #'.$this->_current_row,"error");
			return false;	
		}
		//handle "computed" ignored columns
		$this->handleIgnore($item);
		//get Item identifiers in magento
		$itemids=$this->getItemIds($item);
		
		//extract product id & attribute set id
		$pid=$itemids["pid"];
		$asid=$itemids["asid"];
		
		$isnew=false;
		if(isset($pid) && $this->mode=="xcreate")
		{
			$this->log("skipping existing sku:{$item["sku"]} - xcreate mode set","skip");
			return false;
		}
		
		if(!isset($pid))
		{

			if($this->mode!=='update')
			{
				if(!isset($asid))
				{
					$this->log("cannot create product sku:{$item["sku"]}, no attribute_set defined","error");
					return false;
				}
				$pid=$this->createProduct($item,$asid);
				$this->_curitemids["pid"]=$pid;
				$isnew=true;
			}
			else
			{
				//mode is update, do nothing
				$this->log("skipping unknown sku:{$item["sku"]} - update mode set","skip");
				return false;
			}
		}
		else
		{
			$this->updateProduct($item,$pid);
		}
		
		try
		{
			if(!$this->callPlugins("itemprocessors","processItemAfterId",$item,array("product_id"=>$pid,"new"=>$isnew,"same"=>$this->_same,"asid"=>$asid)))
			{
				return false;
			}



			if(count($item)==0)
			{
				return true;
			}
			//handle "computed" ignored columns from afterImport
			$this->handleIgnore($item);

			if(!$this->checkstore($item,$pid,$isnew))
			{
				$this->log("invalid store value, skipping item sku:".$item["sku"]);
				return false;
			}
			//create new ones
			$attrmap=$this->attrbytype;
			do
			{
				$attrmap=$this->createAttributes($pid,$item,$attrmap,$isnew,$itemids);
			}
			while(count($attrmap)>0);

			if(!testempty($item,"category_ids"))
			{
				//assign categories
				$this->assignCategories($pid,$item);
			}

			//update websites
			if($this->mode!="update")
			{
				$this->updateWebSites($pid,$item);
			}

			if(!$this->_same)
			{
				//update stock
				$this->updateStock($pid,$item,$isnew);
			}

			$this->touchProduct($pid);
			//ok,we're done
			if(!$this->callPlugins("itemprocessors","processItemAfterImport",$item,array("product_id"=>$pid,"new"=>$isnew,"same"=>$this->_same)))
			{
				return false;
			}
		}
		catch(Exception $e)
		{
			$this->callPlugins(array("itemprocessors"),"processItemException",$item,array("exception"=>$e));
			$this->logException($e,$this->_laststmt->queryString);
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
	 * @param string $csvfile filename
	 */
	public function lookup()
	{
		$t0=microtime(true);
		$this->log("Performing Datasouce Lookup...","startup");

		$count=$this->datasource->getRecordsCount();
		$t1=microtime(true);
		$time=$t1-$t0;
		$this->log("$count:$time","lookup");
		$this->log("Found $count records, took $time sec","startup");

		return $count;
	}



	public function updateProduct($item,$pid)
	{
		$tname=$this->tablename('catalog_product_entity');
		if(isset($item['type']))
		{
			$item['type_id']=$item['type'];
		}
		$item['entity_type_id']=$this->prod_etype;
		$item['updated_at']=strftime("%Y-%m-%d %H:%M:%S");
		$columns=array_intersect(array_keys($item), $this->getProdCols());
		$values=$this->filterkvarr($item, $columns);

		$sql="UPDATE  `$tname` SET ".$this->arr2update($values). " WHERE entity_id=?";

		$this->update($sql,array_merge(array_values($values),array($pid)));

	}

	public function getProductEntityType()
	{
		return $this->prod_etype;
	}
	
	public function getCurrentRow()
	{
		return $this->_current_row;
	}

	public function setCurrentRow($cnum)
	{
		$this->_current_row=$cnum;
	}

	public function isLastItem($item)
	{
		return isset($item["__MAGMI_LAST__"]);
	}

	public function setLastItem(&$item)
	{
		$item["__MAGMI_LAST__"]=1;
	}

	public function engineInit($params)
	{
		$this->_profile=$this->getParam($params,"profile","default");
		//create an instance of local magento directory handler
		//this instance will autoregister in factory
		$mdh=new LocalMagentoDirHandler(Magmi_Config::getInstance()->getMagentoDir());
		$this->_timecounter->initTimingCats(array("global","line"));
		$this->initPlugins($this->_profile);
		$this->mode=$this->getParam($params,"mode","update");
	
	}


	public function reportStats($nrow,&$tstart,&$tdiff,&$lastdbtime,&$lastrec)
	{
		$tend=microtime(true);
		$this->log($nrow." - ".($tend-$tstart)." - ".($tend-$tdiff),"itime");
		$this->log($this->_nreq." - ".($this->_indbtime)." - ".($this->_indbtime-$lastdbtime)." - ".($this->_nreq-$lastrec),"dbtime");
		$lastrec=$this->_nreq;
		$lastdbtime=$this->_indbtime;
		$tdiff=microtime(true);
	}



	public function initImport($params)
	{
		$this->log("MAGMI by dweeves - version:".Magmi_Version::$version,"title");
		$this->log("Import Profile:$this->_profile","startup");
		$this->log("Import Mode:$this->mode","startup");
		$this->log("step:".$this->getProp("GLOBAL","step",0.5)."%","step");
		//intialize store id cache
		$this->connectToMagento();
		try
		{
			$this->initProdType();
			$this->createPlugins($this->_profile,$params);
			$this->_registerPluginLoopCallback("processItemAfterId", "onPluginProcessedItemAfterId");
			$this->callPlugins("datasources,itemprocessors","startImport");
			$this->resetSkuStats();
		}
		catch(Exception $e)
		{
		 $this->disconnectFromMagento();
		}
	}

	public function onPluginProcessedItemAfterId($plinst,&$item,$plresult)
	{
		$this->handleIgnore($item);
	}
	
	public function exitImport()
	{
		$this->callPlugins("datasources,general,itemprocessors","endImport");
		$this->callPlugins("datasources,general,itemprocessors","afterImport");
		$this->disconnectFromMagento();
	}

	public function updateSkuStats($res)
	{
		if(!$this->_same)
		{
			$this->_skustats["nsku"]++;
			if($res["ok"])
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

	public function processDataSourceLine($item,$rstep,&$tstart,&$tdiff,&$lastdbtime,&$lastrec)
	{
		//counter
		$res=array("ok"=>0,"last"=>0);
		$canceled=false;
		$this->_current_row++;
		if($this->_current_row%$rstep==0)
		{
			$this->reportStats($this->_current_row,$tstart,$tdiff,$lastdbtime,$lastrec);
		}
		try
		{
			if(is_array($item) && count($item)>0)
			{
				//import item
				$this->beginTransaction();
				$importedok=$this->importItem($item);
				if($importedok)
				{
					$res["ok"]=true;
					$this->commitTransaction();
				}
				else
				{
					$res["ok"]=false;
					$this->rollbackTransaction();

				}
			}
			else
			{
				$this->log("ERROR - RECORD #$this->_current_row - INVALID RECORD","error");
			}
			//intermediary measurement

		}
		catch(Exception $e)
		{
			$this->rollbackTransaction();
			$res["ok"]=false;
			$this->logException($e,"ERROR ON RECORD #$this->_current_row");
			if($e->getMessage()=="MAGMI_RUN_CANCELED")
			{
				$canceled=true;
			}
		}
		if($this->isLastItem($item) || $canceled)
		{
			unset($item);
			$res["last"]=1;
		}
		
		unset($item);
		$this->updateSkuStats($res);
		
		return $res;

	}

	public function resetSkuStats()
	{
		 $this->_skustats=array("nsku"=>0,"ok"=>0,"ko"=>0);
	}
	
	
	public function engineRun($params,$forcebuiltin=array())
	{
		
		$this->log("Import Profile:$this->_profile","startup");
		$this->log("Import Mode:$this->mode","startup");
		$this->log("step:".$this->getProp("GLOBAL","step",0.5)."%","step");
		$this->createPlugins($this->_profile,$params);
		$this->datasource=$this->getDataSource();
		$nitems=$this->lookup();
		Magmi_StateManager::setState("running");
		//if some rows found
		if($nitems>0)
		{
			$this->resetSkuStats();
			//intialize store id cache
			$this->callPlugins("datasources,itemprocessors","startImport");
			//initializing item processors
			$cols=$this->datasource->getColumnNames();
			$this->log(count($cols),"columns");
			$this->callPlugins("itemprocessors","processColumnList",$cols);
			if(count($cols)<2)
			{
				$this->log("Invalid input data , not enough columns found,check datasource parameters","error");
				$this->log("Import Ended","end");
				Magmi_StateManager::setState("idle");
				return;
			}
			$this->log("Ajusted processed columns:".count($cols),"startup");
			$this->initProdType();
			//initialize attribute infos & indexes from column names
			if($this->mode!="update")
			{
				$this->checkRequired($cols);
			}
			$this->initAttrInfos(array_values($cols));
			//counter
			$this->_current_row=0;
			//start time
			$tstart=microtime(true);
			//differential
			$tdiff=$tstart;
			//intermediary report step
			$this->initDbqStats();
			$pstep=$this->getProp("GLOBAL","step",0.5);
			$rstep=ceil(($nitems*$pstep)/100);
			//read each line
			$lastrec=0;
			$lastdbtime=0;
			while(($item=$this->datasource->getNextRecord())!==false)
			{
				 $this->_timecounter->initTimingCats(array("line"));
				 $res=$this->processDataSourceLine($item, $rstep,$tstart,$tdiff,$lastdbtime,$lastrec);
				 //break on "forced" last
				 if($res["last"]==1)
				 {
				 	$this->log("last item encountered","info");
				 	break;
				 }
			}
			$this->callPlugins("datasources,general,itemprocessors","endImport");
			$this->reportStats($this->_current_row,$tstart,$tdiff,$lastdbtime,$lastrec);
			$this->log("Skus imported OK:".$this->_skustats["ok"]."/".$this->_skustats["nsku"],"info");
			if($this->_skustats["ko"]>0)
			{
				$this->log("Skus imported KO:".$this->_skustats["ko"]."/".$this->_skustats["nsku"],"warning");
			}
		}
		else
		{
			$this->log("No Records returned by datasource","warning");
		}
		$this->callPlugins("datasources,general,itemprocessors","afterImport");
		$this->log("Import Ended","end");
		Magmi_StateManager::setState("idle");
		
		$timers=$this->_timecounter->getTimers();
	   $f=fopen(Magmi_StateManager::getStateDir()."/timings.txt","w");
		foreach($timers as $cat=>$info)
		{
			$rep="\nTIMING CATEGORY:$cat\n--------------------------------";
			foreach($info as $phase=>$pinfo)
			{
				$rep.="\nPhase:$phase\n";
				foreach($pinfo as $plugin=>$data)
				{
					$rdur=round($data["dur"],4);
					if($rdur>0)
					{
						$rep.="- Class:$plugin :$rdur ";
					}
				}
			}
			fwrite($f,$rep);
		}
		fclose($f);
	}

	public function onEngineException($e)
	{
		if(isset($this->datasource))
		{
			$this->datasource->onException($e);
		}
		$this->log("Import Ended","end");

		Magmi_StateManager::setState("idle");
	}

}