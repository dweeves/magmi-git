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
require_once("../inc/magmi_engine.php");

/* Magmi ProductImporter is now a Magmi_Engine instance */
class Magmi_ProductImportEngine extends Magmi_Engine
{

	public $attrinfo=array();
	public $attrbytype=array();
	public $store_ids=array();
	public $status_id=array();
	public $attribute_sets=array();
	public $prod_etype;
	public $sidcache=array();
	public $mode="update";
	private $_attributehandlers;
	private $_current_row;
	private $_nsku;
	private $_optidcache=null;
	private $_curitemids=array("sku"=>null);
	private $_dstore=array();
	private $_same;
	private $_currentpid;
	private $_extra_attrs;
	private $_profile;
	private $_defaultwsid;
	private $_wsids=array();
	private $_sid_wsscope=array();
	private $_sid_sscope=array();
	
	
	

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
	}

	public function getEngineInfo()
	{
		return array("name"=>"Magmi Product Import Engine","version"=>"1.1.7","author"=>"dweeves");
	}
	
	/**
	 * load properties
	 * @param string $conf : configuration .ini filename
	 */

	
	

	public function initProdType()
	{
		$tname=$this->tablename("eav_entity_type");
		$this->prod_etype=$this->selectone("SELECT entity_type_id FROM $tname WHERE entity_type_code=?","catalog_product","entity_type_id");
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
	 * Initilialize webstore list
	 */
	public function initWebsites()
	{
		$cws=$this->tablename('core_website');
		$this->_defaultwsid=$this->selectone("SELECT website_id from $cws WHERE is_default=1",null,"website_id");
		
	}

	public function getStoreIdsForWebsiteScope($scodes)
	{
		if(!isset($this->_sid_wsscope[$scodes]))
		{
			$this->_sid_wsscope[$scodes]=array();
			$wscarr=csl2arr($scodes);
			$qcolstr=$this->arr2values($wscarr);
			$cs=$this->tablename("core_store");
			$sql="SELECT cs_sec.store_id from $cs as csmain
				 JOIN $cs as cs_sec ON cs_sec.website_id=csmain.website_id AND csmain.code IN ($qcolstr)";
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

	public function getStoreIds($storestr)
	{
		//if no cache hit for these store list
		if(!isset($this->sidcache[$storestr]))
		{
			//default store flag
			$bfound=false;
			$stores=csl2arr($storestr);
			$sids=array();
			//find store id for store list
			foreach($stores as $scode)
			{
				$scode=trim($scode);
				$sid=$this->store_ids[$scode];

				//add store id to id list
				$sids[]=$sid;
			}
			//fill id cache list for store list
			$this->sidcache[$storestr]=$sids;
		}
		//return id cache list for store list
		return $this->sidcache[$storestr];
	}

	/**
	 * returns mode
	 */
	public function getMode()
	{
		return $this->mode;
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
	
	public function initAttrInfos($cols)
	{
		//Find product entity type
		$tname=$this->tablename("eav_entity_type");
		$this->prod_etype=$this->selectone("SELECT entity_type_id FROM $tname WHERE entity_type_code=?","catalog_product","entity_type_id");
		//create statement parameter string ?,?,?.....
		$qcolstr=$this->arr2values($cols);
		
		$tname=$this->tablename("eav_attribute");
		if($this->magversion=="1.4.x")
		{
			$extra=$this->tablename("catalog_eav_attribute");
			//SQL for selecting attribute properties for all wanted attributes
			$sql="SELECT `$tname`.*,$extra.is_global FROM `$tname`
			LEFT JOIN $extra ON $tname.attribute_id=$extra.attribute_id
			WHERE  ($tname.attribute_code IN ($qcolstr)) AND (entity_type_id=$this->prod_etype)";		
		}
		else
		{
			$sql="SELECT `$tname`.* FROM `$tname` WHERE ($tname.attribute_code IN ($qcolstr)) AND (entity_type_id=$this->prod_etype)";
		}
		$result=$this->selectAll($sql,$cols);

		//create an attribute code based array for the wanted columns
		foreach($result as $r)
		{
			$this->attrinfo[$r["attribute_code"]]=$r;
		}
		unset($result);
		//create a backend_type based array for the wanted columns
		//this will greatly help for optimizing inserts when creating attributes
		//since eav_ model for attributes has one table per backend type
		foreach($this->attrinfo as $k=>$a)
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

	public function getAttrInfo($col)
	{
		return isset($this->attrinfo[$col])?$this->attrinfo[$col]:null;
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
		"SELECT sku,entity_id as pid,attribute_set_id as asid FROM $tname WHERE sku=?",
		$sku);
		if(count($result)>0)
		{
			return $result[0];
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
		$values=array($item['type'],$asid,$item['sku'],$this->prod_etype,null,strftime("%Y-%m-%d %H:%M:%S"));
		$sql="INSERT INTO `$tname`
				(`type_id`, 
				`attribute_set_id`,
	 			`sku`, 
	 			`entity_type_id`, 
	 			`entity_id`,
	 			`created_at`
	 			) 
	 			VALUES ( ?,?,?,?,?,?)";
		$lastid=$this->insert($sql,$values);
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
	function getOptionsFromValues($attid,$store_id,$optvals)
	{
		$ovstr=substr(str_repeat("?,",count($optvals)),0,-1);
		$t1=$this->tablename('eav_attribute_option');
		$t2=$this->tablename('eav_attribute_option_value');
		$sql="SELECT optvals.option_id as opvs,optvals.value FROM $t2 as optvals";
		$sql.=" JOIN $t1 as opt ON opt.option_id=optvals.option_id AND opt.attribute_id=?";
		$sql.=" WHERE optvals.store_id=? AND optvals.value IN ($ovstr)";
		return $this->selectAll($sql,array_merge(array($attid,$store_id),$optvals));
	}


	/* create a new option entry for an attribute */
	function createOption($attid)
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
	function  createOptionValue($optid,$store_id,$optval)
	{
		$t=$this->tablename('eav_attribute_option_value');
		$optval_id=$this->insert("INSERT INTO $t (option_id,store_id,value) VALUES (?,?,?)",array($optid,$store_id,$optval));
		return $optval_id;
	}


	function getOptionIds($attid,$storeid,$values)
	{
		$optids=array();
		$existing=$this->getOptionsFromValues($attid,$storeid,$values);
		$exvals=array();
		foreach($existing as $optdesc)
		{
			$exvals[]=$optdesc["value"];
		}
		$new=array_merge(array_diff($values,$exvals));
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
			foreach($existing as $ex)
			{
				array_shift($brows);
			}
			for($i=0;$i<count($new);$i++)
			{
				$row=$brows[$i];
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

	function cacheOptIds($attid,$row)
	{
		$this->_optidcache[$attid]=$row;
	}

	function getCachedOptIds($attid)
	{
		return $this->_optidcache[$attid];
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




	public function getItemStoreIds($item,$scope)
	{
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
	public function createAttributes($pid,&$item,$attmap)
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
				//get attribute id
				$attid=$attrdesc["attribute_id"];
				//get attribute value in the item to insert based on code
				$atthandler="handle".ucfirst($attrdesc["attribute_code"])."Attribute";
				$attrcode=$attrdesc["attribute_code"];
				if(!in_array($attrcode,array_keys($item)))
				{
					continue;
				}
				$ivalue=$item[$attrcode];
				
				$store_ids=$this->getItemStoreIds($item,$attrdesc["is_global"]);
				
				//do not handle empty generic int values in create mode
				if($ivalue=="" && $this->mode!="update" && $tp=="int")
				{
					continue;
				}
			
				foreach($store_ids as $store_id)
				{
					
					$ovalue=$ivalue;
					
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
							if(isset($hvalue) && $hvalue!="__MAGMI_UNHANDLED__")
							{
								$ovalue=$hvalue;
								break;
							}
						}
					}
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
						array_pop($sids);
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
			
			if(empty($deletes) && empty($inserts))
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
		if(!isset($this->stockcolumns))
		{
			$this->stockcolumns=array('qty','min_qty','use_config_min_qty','is_qty_decimal',
									  'backorders','use_config_backorders',
									  'min_sale_qty','use_config_min_sale_qty',
									  'max_sale_qty','use_config_max_sale_qty',
									  'is_in_stock',
									  'low_stock_date','notify_stock_qty','use_config_stock_qty',
									  'manage_stock','use_config_manage_stock',
									  'stock_status_changed_automatically',
									  'use_config_qty_increments','qty_increments',
									  'enable_qty_increments','use_config_enable_qty_increments'
									
							);
		}
		
		#take only stock columns that are in item
		$test=array_intersect(array_keys($item),$this->stockcolumns);
		#no stock columns set, item exists, no stock update needed.
		if(count($test)==0 && !$isnew)
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
			if(!$is_in_stock && $item["qty"]>$mqty)
			{
				$is_in_stock=1;
			}
			$item["is_in_stock"]=$is_in_stock;
		}
		#take only stock columns that are in  item after item update
		$common=array_intersect(array_keys($item),$this->stockcolumns);
		
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
			$sql="UPDATE `$csit` SET $svstr WHERE product_id=? AND stock_id=?";
			$this->update($sql,array_merge(array_values($stockvals),array($pid,$stock_id)));
		}
		$data=array();		
		$wsids=$this->getItemWebsites($item);
		//for each website code
		$csscols=array("website_id","product_id","stock_id","qty","stock_status");
		$cssvals=$this->filterkvarr($item,$csscols);
		$stock_id=(isset($cssvals["stock_id"])?$cssvals["stock_id"]:1);
		$stock_status=(isset($cssvals["stock_status"])?$cssvals["stock_status"]:1);
		#force unset/reinsert in $cssvals to ensure order even if value existed before
		$cssvals["stock_id"]=$stock_id;
		$cssvals["stock_status"]=$stock_status;
		$cssvals["qty"]=(isset($item["qty"])?$item["qty"]:0);
		//clear item stock status
		$this->delete("DELETE FROM `$css` where product_id=? AND stock_id=?",array($pid,$stock_id));
		//rebuild item stock status
		$data=array();
		$colstr=$this->arr2values($csscols);
		foreach($wsids as $wsid)
		{
			$cssvals["product_id"]=$pid;
			$cssvals["website_id"]=$wsid;
			$inserts[]="($colstr)";
			$data=array_merge($data,array_values($cssvals));
		}
		$sql="INSERT INTO `$css` (".$this->arr2columns($csscols).") VALUES ".implode(",",$inserts);
		$this->insert($sql,$data);	
		unset($inserts);
		unset($data);
		unset($cssvals);
		unset($csscols);
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
		$catids=csl2arr($item["category_ids"]);
		//build possible path list
		$sql="SELECT entity_id FROM $cce
			  WHERE entity_id IN (".$item['category_ids'].")";
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
		foreach($catids as $catid)
		{
			$inserts[]="(?,?)";
			$data[]=$catid;
			$data[]=$pid;
		}
		#create new category assignment for products, if multi store with repeated ids
		#ignore duplicates
		$sql="INSERT IGNORE INTO $ccpt (`category_id`,`product_id`)
			 VALUES ";
		$sql.=implode(",",$inserts);
		$this->insert($sql,$data);
		unset($data);
		unset($inserts);
	}


	public function getItemWebsites($item)
	{
		//use default website
		if(!isset($item["websites"]) || empty($item["websites"]))
		{
			return array($this->_defaultwsid);
		}
		else
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

	public function onNewSku($sku)
	{
		$this->clearOptCache();
		//only assign values to store 0 by default in create mode for new sku
		//for store related options
		if($this->mode!="update")
		{
			$this->_dstore=array(0);
		}
		else
		{
			$this->_dstore=array();
		}
		$this->_same=false;
		$this->_nsku++;
	}

	public function onSameSku($sku)
	{
		unset($this->_dstore);
		$this->_dstore=array();
		$this->_same=true;
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
				$this->_curitemids=array("pid"=>null,"sku"=>$sku,"asid"=>isset($item["attribute_set"])?$this->getAttributeSetId($item["attribute_set"]):null);
			}
			unset($cids);
			$this->onNewSku($sku);
		}
		else
		{
			$this->onSameSku($sku);
		}
		return $this->_curitemids;
	}
	/**
	 * full import workflow for item
	 * @param array $item : attribute values for product indexed by attribute_code
	 */
	public function importItem($item)
	{
		if(Magmi_StateManager::getState()=="canceled")
		{
			exit();
		}
		//first step

		if(!$this->callPlugins("itemprocessors","processItemBeforeId",$item))
		{
			return false;
		}
		
		$itemids=$this->getItemIds($item);
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
			
			if(!isset($asid))
			{
				$this->log("cannot create product sku:{$item["sku"]}, no attribute_set defined","error");
				return false;
			}
			if($this->mode!=='update')
			{
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
		try
		{
			if(!$this->callPlugins("itemprocessors","processItemAfterId",$item,array("product_id"=>$pid,"new"=>$isnew,"same"=>$this->_same,"asid"=>$asid)))
			{
				return false;
			}
				
			
				
			//create new ones
			$attrmap=$this->attrbytype;
			do
			{
				$attrmap=$this->createAttributes($pid,$item,$attrmap);	
			}
			while(count($attrmap)>0);
			
			if(!testempty($item,"category_ids"))
			{
				//assign categories
				$this->assignCategories($pid,$item);
			}
			
			//update websites
			if($this->mode!="update" || isset($item["websites"]))
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


	
	public function getBuiltinPluginClasses()
	{
		//force include for this "special" handler
		$plpath=dirname(dirname(__FILE__))."/plugins/inc/magmi_defaultattributehandler.php";
		require_once($plpath);
		return array("itemprocessors"=>"Magmi_DefaultAttributeItemProcessor");
	}
	

	
	public function getCurrentRow()
	{
		return $this->_current_row;
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
		$this->initPlugins($this->_profile);
		$this->mode=$this->getParam($params,"mode","update");
		$this->createPlugins($this->_profile,$params);
	}
	
	
	public function reportStats(&$tstart,&$tdiff,&$lastdbtime,&$lastrec)
	{
		$tend=microtime(true);
		$this->log($this->_current_row." - ".($tend-$tstart)." - ".($tend-$tdiff),"itime");
		$this->log($this->_nreq." - ".($this->_indbtime)." - ".($this->_indbtime-$lastdbtime)." - ".($this->_nreq-$lastrec),"dbtime");
		$lastrec=$this->_nreq;
		$lastdbtime=$this->_indbtime;
		$tdiff=microtime(true);
	}
	
	public function engineRun($params)
	{
		$this->log("Import Mode:$this->mode","startup");
		$this->log("MAGMI by dweeves - version:".Magmi_Version::$version,"title");
		$this->log("step:".$this->getProp("GLOBAL","step",0.5)."%","step");
		//initialize db connectivity
		$this->datasource=$this->getPluginInstance("datasources",0);
		$this->callPlugins("datasources,general","beforeImport");			
		$nitems=$this->lookup();
		Magmi_StateManager::setState("running");
		//if some rows found
		if($nitems>0)
		{
			//intialize store id cache
			$this->initWebsites();
			$this->callPlugins("datasources,itemprocessors","startImport");
			//initializing item processors
			$cols=$this->datasource->getColumnNames();
			$this->log(count($cols),"columns");
			$this->callPlugins("itemprocessors","processColumnList",$cols);
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
				//counter
				$this->_current_row++;	
				if($this->_current_row%$rstep==0)
				{
					$this->reportStats($tstart,$tdiff,$lastdbtime,$lastrec);
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
							$this->commitTransaction();
						}
						else
						{
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
					$this->logException($e,"ERROR ON RECORD #$this->_current_row");
				}
				if($this->isLastItem($item))
				{
					unset($item);
					break;
				}
				unset($item);
			}
			$this->callPlugins("datasource,general,itemprocessors","endImport");
			$this->reportStats($tstart,$tdiff,$lastdbtime,$lastrec);
		}
		else
		{
			$this->log("No Records returned by datasource","warning");
		}
		$this->callPlugins("datasource,general,itemprocessors","afterImport");
		$this->log("Import Ended","end");
		Magmi_StateManager::setState("idle");		
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