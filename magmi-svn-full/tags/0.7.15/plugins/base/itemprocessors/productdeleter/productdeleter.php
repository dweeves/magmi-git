<?php
class ProductDeleter extends Magmi_ItemProcessor
{
	public function getPluginInfo()
	{
		   return array(
            "name" => "Product Deleter",
            "author" => "Dweeves",
            "version" => "0.0.1",
        	"url" => "http://sourceforge.net/apps/mediawiki/magmi/index.php?title=Product_Deleter"
        );
	}
	
	public function getPluginParamNames()
	{
		return array("PDEL:delsimples");
	}
	
	public function processItemAfterId(&$item,$params=null)
	{
		
		//get item ids, since we are before id
		$pid=$params["product_id"];
		if(isset($item["magmi:delete"]) && $item["magmi:delete"]==1)
		{
			$this->log("DELETING SKU '".$item["sku"]."' =>".$pid,"info");
			//delete simple products if flag set
			if($this->getParam("PDEL:delsimples",false)==true)
			{
				$childrensel="SELECT entity_id FROM ".$this->tablename("catalog_product_entity")." as cpe
				JOIN ".$this->tablename("catalog_product_super_link")." as cpl ON cpl.parent_id=? AND cpe.entity_id=cpl.product_id";
				$sql="DELETE cpe.* FROM ".$this->tablename("catalog_product_entity")." cpe WHERE cpe.entity_id IN (SELECT s1.entity_id FROM ($childrensel) as s1)";
				
				$this->delete($sql,$pid);
			}
			//delete product (this cascades for all eav & relations)
			$sql="DELETE FROM ".$this->tablename("catalog_product_entity")." WHERE entity_id=?";
			$this->delete($sql,$pid);
			$this->log($sql,"info");
			$item=array();
		}
	}
}