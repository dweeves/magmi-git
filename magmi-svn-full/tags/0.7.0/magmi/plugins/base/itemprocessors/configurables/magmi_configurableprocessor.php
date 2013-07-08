<?php
class Magmi_ConfigurableItemProcessor extends Magmi_ItemProcessor
{

	
	private $_configurable_attrs=array();
	
	public function initialize($params)
	{
			
	}
	
	public function getPluginInfo()
	{
		return array(
            "name" => "Configurable Item processor",
            "author" => "Dweeves",
            "version" => "1.0.3"
            );
	}
	
public function initConfigurableOpts($cols)
	{
		$ea=$this->tablename("eav_attribute");
		$qcolstr=substr(str_repeat("?,",count($cols)),0,-1);
		if($this->_mmi->magversion=="1.4.x")
		{
			$cea=$this->tablename("catalog_eav_attribute");
			$sql="SELECT ea.attribute_code  FROM `$cea` as cea
				JOIN $ea as ea ON ea.attribute_id=cea.attribute_id AND ea.is_user_defined=1 AND ea.attribute_code IN ($qcolstr)
 				WHERE cea.is_global= 1 AND cea.is_configurable=1 ";
		}
		else
		{
			$sql="SELECT ea.attribute_code FROM $ea as ea WHERE ea.is_user_defined=1 AND ea.is_global=1 and ea.is_configurable=1 AND ea.attribute_code IN ($qcolstr) ";
		}
		$result=$this->selectAll($sql,$cols);
		foreach($result as $r)
		{
			$this->_configurable_attrs[]=$r["attribute_code"];
		}
	}
	
	public function processColumnList($cols)
	{
		//gather configurable options attribute code
		$this->initConfigurableOpts($cols);	
		return true;
	}
	
	public function doLink($pid,$cond)
	{
			$cpsl=$this->tablename("catalog_product_super_link");
			$cpr=$this->tablename("catalog_product_relation");
			$sql="DELETE cpsl.*,cpsr.* FROM $cpsl as cpsl
				JOIN $cpr as cpsr ON cpsr.parent_id=cpsl.parent_id
				WHERE cpsl.parent_id=?";
			$this->delete($sql,array($pid));
			//recreate associations
			$sql="INSERT INTO $cpsl (`parent_id`,`product_id`) SELECT cpec.entity_id as parent_id,cpes.entity_id  as product_id  
				  FROM catalog_product_entity as cpec 
				  JOIN catalog_product_entity as cpes ON cpes.type_id='simple' AND cpes.sku $cond
			  	  WHERE cpec.entity_id=?";
			$this->insert($sql,array($pid));
			$sql="INSERT INTO $cpr (`parent_id`,`child_id`) SELECT cpec.entity_id as parent_id,cpes.entity_id  as child_id  
				  FROM catalog_product_entity as cpec 
				  JOIN catalog_product_entity as cpes ON cpes.type_id='simple' AND cpes.sku $cond
			  	  WHERE cpec.entity_id=?";
			$this->insert($sql,array($pid));
		
	}
	
	public function quote(&$it)
	{
		$val="'".addslashes($it)."'";
		return $val;
	}
	
	public function autoLink($pid)
	{
		$this->dolink($pid,"LIKE CONCAT(cpec.sku,'%')");
	}
	
	public function fixedLink($pid,$skulist)
	{
		$arrin=explode(",",$skulist);
		$arrout=array();
		foreach($arrin as $v)
		{
			$arrout[]=$this->quote($v);
		}
		$skulist=implode(",",$arrout);
		unset($arrin);
		unset($arrout);
		$this->dolink($pid,"IN ($skulist)");		
	}
	
	public function processItemAfterId(&$item,$params)
	{
		//if item is not configurable, nothing to do
		if($item["type"]!=="configurable")
		{
			return true;
		}
		//if no configurable attributes, nothing to do
		if(count($this->_configurable_attrs)==0)
		{
			return true;
		}
		//matching mode
		//if associated skus 
		$matchmode=(isset($item["simples_skus"])?(trim($item["simples_skus"])!=""?"fixed":"none"):"auto");
		
		
		//check if item has exising options
		
		$pid=$params["product_id"];
		$psa=$this->tablename("catalog_product_super_attribute");
		$sql="DELETE FROM `$psa` WHERE `product_id`=?";
		$this->delete($sql,array($pid));
	
			
		//process configurable options
		$ins_sa=array();
		$data_sa=array();
		$ins_sal=array();
		$data_sal=array();
		$confopts=$this->_configurable_attrs;
		foreach($confopts as $confopt)
		{
			$attrinfo=$this->getAttrInfo($confopt);
			$cpsa=$this->tablename("catalog_product_super_attribute");
			$cpsal=$this->tablename("catalog_product_super_attribute_label");
			$sql="INSERT INTO `$cpsa` (`product_id`,`attribute_id`,`position`) VALUES (?,?,?)";
			//inserting new options
			$psaid=$this->insert($sql,array($pid,$attrinfo["attribute_id"],0));		
			//for all stores defined for the item
			$sids=$this->getItemStoreIds($item,0);
			$data=array();
			$ins=array();
			foreach($sids as $sid)
			{
				$data[]=$psaid;
				$data[]=$sid;
				$ins[]="(?,?,1,'')";
			}
			$sql="INSERT INTO `$cpsal` (`product_super_attribute_id`,`store_id`,`use_default`,`value`) VALUES ".implode(",",$ins);
			$this->insert($sql,$data);
		}
		
		switch($matchmode)
		{
			case "none":
				break;
			case "auto":
				//destroy old associations
				$this->autoLink($pid);
				break;
			case "fixed":
				$this->fixedLink($pid,$item["simples_skus"]);
				unset($item["simples_skus"]);
				break;
			default:
				break;
		}
		
		return true;
	}
}