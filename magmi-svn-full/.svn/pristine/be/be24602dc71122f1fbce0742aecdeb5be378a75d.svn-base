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
require_once("dbhelper.class.php");

require_once("magmi_statemanager.php");
require_once("magmi_pluginhelper.php");
require_once("magmi_config.php");
require_once("magmi_version.php");

ini_set("allow_url_fopen",true);
//utilities method
// return null for empty string
function nullifempty($val)
{
	return (isset($val)?(strlen($val)==0?null:$val):null);
}
// return false for empty string
function falseifempty($val)
{
	return (isset($val)?(strlen($val)==0?false:$val):false);
}
//test for empty string
function testempty($arr,$val)
{
	
	return !isset($arr[$val]) || strlen(trim($arr[$val]))==0;
}

/* here inheritance from DBHelper is used for syntactic convenience */
class MagentoMassImporter extends DBHelper
{

	public $attrinfo=array();
	public $attrbytype=array();
	public $store_ids=array();
	public $status_id=array();
	public $attribute_sets=array();
	public $ws_store_map=array();
	public $reset=false;
	public $tprefix;
	public $magdir;
	public $logger=null;
	public $enabled_label;
	public $prod_etype;
	public $sidcache=array();
	public $mode="update";
	public static $state=null;
	protected static $_statefile=null;
	public $customip=null;
	public  static $_script=__FILE__;
	private $_pluginclasses=array();
	private $_activeplugins;
	private $_conf;
	private $_initialized=false;
	private $_attributehandlers;
	private $_current_row;
	private $_nsku;
	private $_optidcache=null;
	private $_curitemids=array("sku"=>null);
	private $_dstore;
	private $_same;
	private $_currentpid;
	public $magversion;
	private $_extra_attrs;
	private $_profile;
	private $_excid=0;
	public function setLogger($logger)
	{
		$this->logger=$logger;
	}

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

	/**
	 * load properties
	 * @param string $conf : configuration .ini filename
	 */
	public function init($profile)
	{
		if($this->_initialized)
		{
			return;
		}
		try
		{
			$pluginclasses=Magmi_PluginHelper::getInstance($profile)->getPluginClasses();
			$this->_activeplugins=array("general"=>array(),"processors"=>array());
			//Common configuration
			$this->_conf=Magmi_Config::getInstance();
			$this->_conf->load();
			$this->magversion=$this->_conf->get("MAGENTO","version");
			$this->magdir=$this->_conf->get("MAGENTO","basedir");
			$this->tprefix=$this->_conf->get("DATABASE","table_prefix");
			//Profile configuration
			$enabledplugins=new EnabledPlugins_Config($profile);
			$enabledplugins->load();
			$this->_pluginclasses["processors"]=$enabledplugins->getEnabledPluginClasses("ITEMPROCESSORS");	
			$ds=$enabledplugins->getEnabledPluginClasses("DATASOURCES");
			$this->datasource_class=$ds[0];
			$engen=explode(",",$this->_conf->get("PLUGINS_GENERAL","classes",implode(",",$pluginclasses["general"])));
			$this->_pluginclasses["general"]=$enabledplugins->getEnabledPluginClasses("GENERAL");
			$this->_initialized=true;
			$this->_profile=$profile;
			
		}
		catch(Exception $e)
		{
			die("Error parsing ini file:{$this->_conf->getConfigFilename()} \n".$e->getMessage());
		}
	}

	public function getProp($sec,$val,$default=null)
	{
		return $this->_conf->get($sec,$val,$default);
	}
	/**
	 * Initialize Connection with Magento Database
	 */
	public function connectToMagento()
	{
		#get database infos from properties
		$host=$this->getProp("DATABASE","host","localhost");
		$dbname=$this->getProp("DATABASE","dbname","magento");
		$user=$this->getProp("DATABASE","user");
		$pass=$this->getProp("DATABASE","password");
		$debug=$this->getProp("DATABASE","debug");
		$this->initDb($host,$dbname,$user,$pass,$debug);
		//suggested by pastanislas
		$this->_db->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY,true);
	}

	/*
	 * Disconnect Magento db
	 */
	public function disconnectFromMagento()
	{
		$this->exitDb();
	}

	public function registerAttributeHandler($ahinst,$attdeflist)
	{
		foreach($attdeflist as $attdef)
		{
			$this->_attributehandlers[$attdef]=$ahinst;
		}
	}
	
	

	public function getStoresWebSiteIds($storestr)
	{
		$sarr=explode(",",$storestr);
		$wsids=array();
		foreach($sarr as $scode)
		{
			if(isset($this->ws_store_map["s"][$scode]))
			{
				$wsids=array_merge($wsids,$this->ws_store_map["s"][$scode]);
			}
		}
		return array_unique($wsids);
			
	}
	public function getWebsitesStoreIds($wsstr)
	{
		$sids=array();
		$wsarr=explode(",",$wsstr);
		foreach($wsarr as $ws)
		{
			if(isset($this->ws_store_map["w"][$ws]))
			{
				$sids=array_merge($sids,$this->ws_store_map["w"][$ws]);
			}
		}
		return array_unique($sids);
	}

	/**
	 * logging function
	 * @param string $data : string to log
	 * @param string $type : log type
	 */
	public function log($data,$type="default")
	{
		if(isset($this->logger))
		{
			$this->logger->log($data,$type);
		}
		else
		{
			print "$type:($data)\n";
			flush();
		}
	}
	
	public function trace($e)
	{
		$this->_excid++;
		$traces=$e->getTrace();
		$f=fopen(Magmi_StateManager::getTraceFile(),"a");
		fwrite($f,"---- TRACE : $this->_excid -----\n");
		$trstr="";
		foreach($traces as $trace)
		{
			$fname=str_replace(dirname(dirname(__FILE__)),"",$trace["file"]);
			$trstr.= $fname.":".$trace["line"]." - ".$trace["function"]."(".implode(",",$trace["args"]).")\n";	
		}
		fwrite($f,$trstr);
		fwrite($f,"---- ENDTRACE : $this->_excid -----\n");
		fclose($f);
		
	}
	/**
	 * Initilialize webstore list
	 */
	public function initStores()
	{
		$csname=$this->tablename("core_store");
		$wsname=$this->tablename("core_website");
		$sql="SELECT cs.code as stcode,store_id,cs.website_id,ws.code as wscode FROM $csname as cs
				JOIN $wsname as ws ON cs.website_id=ws.website_id";
		$result=$this->selectAll($sql);
		$wsm=array();
		foreach($result as $r)
		{
			$scode=$r["stcode"];
			$wscode=$r["wscode"];
			$this->store_ids[$scode]=$r["store_id"];
			$wsid=$r["website_id"];
			if(!isset($wsm["w"][$wscode]))
			{
				$wsm["w"][$wscode]=array();
			}
			if(!isset($wsm["s"][$scode]))
			{
				$wsm["s"][$scode]=array();
			}
			$wsm["w"][$wscode][]=$r["store_id"];
			$wsm["s"][$scode][]=$wsid;
			$wsm["s"][$scode]=array_unique($wsm["s"][$scode]);
		}
		unset($result);
		$this->ws_store_map=$wsm;
	}


	public function getStoreIds($storestr)
	{
		//if no cache hit for these store list
		if(!isset($this->sidcache[$storestr]))
		{
			//default store flag
			$bfound=false;
			$stores=explode(",",$storestr);
			$sids=array();
			//find store id for store list
			foreach($stores as $scode)
			{

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
	 * returns prefixed table name
	 * @param string $magname : magento base table name
	 */
	public function tablename($magname)
	{
		return $this->tprefix!=""?$this->tprefix."_$magname":$magname;
	}

	/**
	 * returns mode
	 */
	public function getMode()
	{
		return $this->mode;
	}
	
	public function getMagentoDir()
	{
		return $this->magdir;
	}
	/**
	 * Initialize attribute infos to be used during import
	 * @param array $cols : array of attribute names
	 */
	
	public function initAttrInfos($cols)
	{
		//Find product entity type
		$tname=$this->tablename("eav_entity_type");
		$this->prod_etype=$this->selectone("SELECT entity_type_id FROM $tname WHERE entity_type_code=?","catalog_product","entity_type_id");
		$gcols=array_unique(array_merge($cols,array("media_gallery")));
		//create statement parameter string ?,?,?.....
		$qcolstr=substr(str_repeat("?,",count($gcols)),0,-1);
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
		$result=$this->selectAll($sql,$gcols);

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
	public function getProductId($sku)
	{
		$tname=$this->tablename("catalog_product_entity");
		return $this->selectone(
		"SELECT entity_id FROM $tname WHERE sku=?",
		$sku,
		'entity_id');
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
				$bstore_ids=array(0);
				break;
			//store scope
			case 0:
				$bstore_ids=$this->getStoreIds($item["store"]);
				break;
			//website scope
			case 2:	
				$bstore_ids=$this->getStoreIds($item["store"]);
				if(count($bstore_ids)==1 && $bstore_ids[0]==0)
				{
					$bstore_ids=$this->getWebsitesStoreIds($item["websites"]);
				}
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
			//use reflection to find special handlers
			$typehandler="handle".ucfirst($tp)."Attribute";
			//iterate on all attribute descriptions for the given backend type
			foreach($a["data"] as $attrdesc)
			{
				//by default, we will perform an insetion
				$insert=true;
				//get attribute id
				$attid=$attrdesc["attribute_id"];
				//get attribute value in the item to insert based on code
				$atthandler="handle".ucfirst($attrdesc["attribute_code"])."Attribute";
				$attrcode=$attrdesc["attribute_code"];
				$ivalue=$item[$attrcode];
				
				$store_ids=$this->getItemStoreIds($item,$attrdesc["is_global"]);
				
				$deletes=array();
				//do not handle empty generic int values in create mode
				if($ivalue=="" && $this->mode=="create" && $tp=="int")
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
						$hvalue=null;
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
						$deletes[]=array($attid,$store_id,$pid);
						$ddata=array($this->prod_etype,$attid,$store_id,$pid);
						$sql="DELETE FROM $cpet WHERE entity_type_id=? AND attribute_id=? AND store_id=? AND entity_id=?";
						$this->delete($sql,$ddata);
						unset($ddata);
					}
					else
					if($ovalue!==false)
					{
						$inserts[]="(?,?,?,?,?)";
						$data[]=$this->prod_etype;
						$data[]=$attid;
						$data[]=$store_id;
						$data[]=$pid;
						$data[]=$ovalue;
					}
		
					//if one of the store in the list is admin
					if($store_id==0)
					{
						//remove all values bound to the other stores for this attribute,so that they default to "use admin value"
						array_pop($store_ids);
						if(count($store_ids)>0)
						{
							$sidlist=implode(",",$store_ids);
							$ddata=array($this->prod_etype,$attid,$pid);
							$sql="DELETE FROM $cpet WHERE entity_type_id=? AND attribute_id=? AND store_id IN ($sidlist) AND entity_id=?";
							$this->delete($sql,$ddata);
						}
						break;
					}
				}
			}
			
			unset($store_ids);
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
			else
			if(!empty($deletes))
			{
				unset($deletes);
			}
			else
			{
				if(!$this->_same)
				{
					$this->log("No $tp Attributes created for sku ".$item["sku"],"warning");
				}
			}
			unset($data);
			unset($inserts);
			
		}
		return $this->_extra_attrs;
	}

	

	/**
	 * update product stock
	 * @param int $pid : product id
	 * @param array $item : attribute values for product indexed by attribute_code
	 */
	public function updateStock($pid,$item)
	{
		if(!$this->reset && $this->mode!=="update")
		{
			$tcsi=$this->tablename('cataloginventory_stock_item');
			$tcss=$this->tablename('cataloginventory_stock_status');
			$sqls=array("DELETE FROM `$tcsi` WHERE product_id=?",
				 "DELETE FROM `$tcss` WHERE product_id=?");

			foreach($sqls as $sql)
			{
				$this->delete($sql,$pid);
			}
		}
		$csit=$this->tablename("cataloginventory_stock_item");
		$css=$this->tablename("cataloginventory_stock_status");
		$stockid=isset($item["stock_id"])?$item["stock_id"]:1; //use stock_id if set in exotic multi repo cases
		$is_in_stock=isset($item["is_in_stock"])?$item["is_in_stock"]:($item["qty"]>0?1:0);
		if(!$is_in_stock && $item["qty"]>0)
		{
			$is_in_stock=1;
		}
		if($this->mode!=="update")
		{
				
			$lsdate=nullifempty(isset($item["low_stock_date"])?$item["low_stock_date"]:"");
			$sql="INSERT INTO `$csit`
			(`product_id`, 
 			 `stock_id`,
  			  `qty`, 
  			  `is_in_stock`, 
  			  `low_stock_date`,
   			 `stock_status_changed_automatically`) 
			VALUES (?,?,?,?,?,?) 
			ON DUPLICATE KEY UPDATE 
			`qty`=VALUES(`qty`),
			`is_in_stock`=VALUES(`is_in_stock`),
			`low_stock_date`=VALUES(`low_stock_date`),
			`stock_status_changed_automatically`=VALUES(`stock_status_changed_automatically`)";
			$data=array($pid,$stockid,$item["qty"],$is_in_stock,$lsdate,1);
			$this->insert($sql,$data);
			unset($data);
			$sql="INSERT INTO `$css` (`website_id`,`product_id`,`stock_id`,`qty`,`stock_status`)";
			$data=array();
			$inserts=array();
			
			$wsids=$this->getStoresWebsiteIds($item["store"]);	
			//for each website code
			foreach($wsids as $wsid)
			{
				$inserts[]="(?,?,?,?,?)";
				$data[]=$wsid;
				$data[]=$pid;
				$data[]=$stockid;
				$data[]=$item["qty"];
				$data[]=1;
			}
			$sql.=" VALUES ".implode(",",$inserts);
			$this->insert($sql,$data);
			unset($data);
			unset($inserts);
		}
		else
		{
			$data=array();
			//Fast stock update
			$data[]=$item["qty"];
			$data[]=$is_in_stock;
			$data[]=$pid;
			$sql="UPDATE `$csit` SET qty=?,is_in_stock=? WHERE product_id=?";
			$this->update($sql,$data);
			$sql="UPDATE `$css` SET qty=? WHERE product_id=?";
			unset($data);
			$data=array($item["qty"],$pid);
			$this->update($sql,$data);
			unset($data);
		}
		unset($data);
		unset($inserts);
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
		$catids=explode(",",$item["category_ids"]);
		//build possible path list
		$sql="SELECT entity_id FROM $cce
			  WHERE entity_id IN (".$item['category_ids'].")";
		$ccpt=$this->tablename("catalog_category_product");
		#if we did not reset products
		if(!$this->reset)
		{
			#remove category assignment of current product
			#only for current store
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



	/**
	 * set website of product if not exists
	 * @param int $pid : product id
	 * @param array $item : attribute values for product indexed by attribute_code
	 */
	public function updateWebSites($pid,$item)
	{
		if(isset($item["websites"]))
		{
		$cpst=$this->tablename("catalog_product_website");
		$cws=$this->tablename("core_website");
		//exploding websites if several values set at once
		$wsarr=explode(",",$item["websites"]);
		$qcolstr=substr(str_repeat("?,",count($wsarr)),0,-1);
		//get all website codes for item from store values
		//associate product with all websites in a single multi insert (use ignore to avoid duplicates)
		$sql="INSERT IGNORE INTO `$cpst` (`product_id`, `website_id`) SELECT $pid,website_id FROM $cws WHERE code IN ($qcolstr)";
		$this->insert($sql,$wsarr);
		unset($data);
		unset($inserts);
		}
		else
		{
			$this->log("no websites set, item not associated to any website","warning");
		}
	}



	public function callPlugins($types,$step,&$item,$params)
	{
		foreach($types as $type)
		{
			$meth="call".ucfirst($type);
			if(!$this->$meth($step,$item,$params))
			{
				return false;
			}
		}
		return true;
	}

	public function callGeneral($step)
	{
		$methname=$step;
		foreach($this->_activeplugins["general"] as $gp)
		{
			if(method_exists($gp,$methname))
			{
				if(!$gp->$methname())
				{
					return false;
				}
			}
		}
		return true;
			
	}

	public function callProcessors($step,&$data=null,$params=null,$prefix="")
	{
		$methname=$prefix.ucfirst($step);
		foreach($this->_activeplugins["processors"] as $ip)
		{
			if(method_exists($ip,$methname))
			{
				if($prefix=="processItem" || $prefix=="process")
				{				
					if(!$ip->$methname($data,$params))
					{
						return false;
					}
				}
				else
				{
					$ip->$methname($params);
				}
			}
		}
		return true;

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
		if($this->mode=="create")
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
			$this->_curitemids["sku"]=$sku;
			//first get product id
			$this->_curitemids["pid"]=$this->getProductId($sku);
			if($this->mode!=="update")
			{
				$this->_curitemids["asid"]=$this->getAttributeSetId($item["attribute_set"]);
			}
			$this->onNewSku($sku);
			//retrieve attribute set from given name
			//if not in cache, add to cache
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

		if(!$this->callProcessors("beforeId",$item,null,"processItem"))
		{
			return;
		}
		$itemids=$this->getItemIds($item);
		$pid=$itemids["pid"];
		$isnew=false;
		
		if(!isset($pid))
		{
			//if not found & mode !=update
			if($this->mode!=='update')
			{
				$asid=$itemids["asid"];
				$pid=$this->createProduct($item,$asid);
				$this->_curitemids["pid"]=$pid;
				$isnew=true;
			}
			else
			{
				//mode is update, do nothing
				$this->log("skipping unknown sku:{$item["sku"]} - update mode set","skip");
				return;
			}
		}
		try
		{
			if(!$this->callProcessors("afterId",$item,array("product_id"=>$pid,"new"=>$isnew,"same"=>$this->_same),"processItem"))
			{
				return;
			}
				
			//begin transaction
			$this->beginTransaction();
				
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
			if($this->mode=="create" || isset($item["websites"]))
			{
				$this->updateWebSites($pid,$item);
			}
			
			if(!testempty($item,"qty") && !$this->_same)
			{
				//update stock
				$this->updateStock($pid,$item);
			}

			$this->touchProduct($pid);
			//ok,we're done
			if(!$this->callProcessors("afterImport",$item,array("product_id"=>$pid,"new"=>$isnew,"same"=>$this->_same),"processItem"))
			{
				return;
			}
			$this->commitTransaction();
		}
		catch(Exception $e)
		{

			$this->callProcessors("exception",$item,array("exception"=>$e),"processItem");
			$this->trace($e);
			$this->log($this->_excid.":".$e->getMessage()." - {$this->_laststmt->queryString}","error");
			
			//if anything got wrong, rollback
			$this->rollbackTransaction();
		}
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

		$count=$this->datasource->getRecordsCount();
		$t1=microtime(true);
		$time=$t1-$t0;
		$this->log("$count:$time","lookup");
		return $count;
	}

	/**
	 * main import function
	 * @param string $csvfile : csv file name to import
	 * @param bool $reset : destroy all products before import
	 */

	public function getParam($params,$pname,$default=null)
	{
		return isset($params[$pname])?$params[$pname]:$default;
	}

	public function createGeneralPlugins($params)
	{
		foreach($this->_pluginclasses["general"] as $giclass)
		{
			$gi=Magmi_PluginHelper::getInstance($this->_profile)->createInstance($giclass,$params,$this);
			$this->_activeplugins["general"][]=$gi;
		}

	}
	public function createItemProcessors($params)
	{
		//insert default attribute processor plugin
		require_once("magmi_defaultattributehandler.php");
		$this->_pluginclasses["processors"][]="Magmi_DefaultAttributeItemProcessor";
		foreach($this->_pluginclasses["processors"] as $ipclass)
		{
			$ip=Magmi_PluginHelper::getInstance($this->_profile)->createInstance($ipclass,$params,$this);
			$this->_activeplugins["processors"][]=$ip;
		}
	}
	
	public function getCurrentRow()
	{
		return $this->_current_row;
	}

	public function createDatasource($params)
	{
		$dsclass=$this->datasource_class;
		$this->datasource=Magmi_PluginHelper::getInstance($this->_profile)->createInstance($dsclass,$params,$this);
	}

	public function isLastItem($item)
	{
		return isset($item["__MAGMI_LAST__"]);
	}
	
	public function setLastItem(&$item)
	{
		$item["__MAGMI_LAST__"]=1;
	}
	public function import($params=array())
	{
		$profile=$this->getParam($params,"profile",null);
		if($profile=="default")
		{
			$profile=null;
		}
		$this->init($profile);
		$mode=$this->getParam($params,"mode","update");
		$this->_excid=0;
		//initializing datasource
		try
		{
			
			$this->log("Magento Mass Importer by dweeves - version:".Magmi_Version::$version,"title");
			$this->log("step:".$this->getProp("GLOBAL","step",0.5)."%","step");
			//initialize db connectivity
			$this->connectToMagento();
			$this->createDatasource($params);
			$this->createGeneralPlugins($params);
			$this->datasource->beforeImport();
			$this->callGeneral("beforeImport");
				
			$nitems=$this->lookup();
			Magmi_StateManager::setState("running");
			//store reset flag
			$this->mode=$mode;
			//if reset
			if($nitems>0)
			{
				//intialize store id cache
				$this->initStores();
				setLocale(LC_COLLATE,"fr_FR.UTF-8");
				$this->datasource->startImport();
					
				//initializing item processors
				$this->createItemProcessors($params);
				$this->callProcessors("importStart",$nodata,null,"on");
			
				$cols=$this->datasource->getColumnNames();
				$this->log(count($cols),"columns");
				$this->callProcessors("columnList",$cols,null,"process");
				//initialize attribute infos & indexes from column names
					
				$this->initAttrInfos($cols);
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
					
					try
					{
						if(is_array($item) && count($item)>0)
						{
							//import item
							$this->importItem($item);
						}
						else
						{
							$this->log("ERROR - RECORD #$this->__current_row - INVALID RECORD","error");
						}
						//intermediary measurement
						if($this->_current_row%$rstep==0)
						{
							$tend=microtime(true);
							$this->log($this->_current_row." - ".($tend-$tstart)." - ".($tend-$tdiff),"itime");
							$this->log($this->_nreq." - ".($this->_indbtime)." - ".($this->_indbtime-$lastdbtime)." - ".($this->_nreq-$lastrec),"dbtime");
							$lastrec=$this->_nreq;
							$lastdbtime=$this->_indbtime;
							$tdiff=microtime(true);
						}
					}
					catch(Exception $e)
					{
						$this->trace($e);
						$this->log($this->_excid.":ERROR - RECORD #$this->_current_row - ".$e->getMessage(),"error");
					}
					if($this->isLastItem($item))
					{
						break;
					}
					unset($item);
				}

				$this->datasource->endImport();
				$tend=microtime(true);
				$this->log($this->_current_row." - ".($tend-$tstart)." - ".($tend-$tdiff),"itime");
				$this->log($this->_nreq." - ".($this->_indbtime)." - ".($this->_indbtime-$lastdbtime)." - ".($this->_nreq-$lastrec),"dbtime");
			}
			$this->datasource->afterImport();
			$this->callGeneral("afterImport");
			$this->callProcessors("importEnd",$nodata,null,"on");
			$this->disconnectFromMagento();
			
			$this->log("Import Ended","end");
			Magmi_StateManager::setState("idle");
				
		}
		catch(Exception $e)
		{
			$this->log($e->getMessage(),"error");
			$this->log("Import Ended","end");
			Magmi_StateManager::setState("idle");
		}
	}


}