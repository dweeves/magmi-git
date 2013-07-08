<?php

class ItemIndexer extends Magmi_ItemProcessor
{
	

	
	public function getPluginInfo()
	{
		return array(
            "name" => "On the fly indexer",
            "author" => "Dweeves",
            "version" => "0.0.1"
            );
	}
	
	public function initialize($params)
	{
		
	}
	
	
	public function buildPriceIndex($item)
	{
		$priceidx=$this->tablename("catalog_product_index_price");
		$sql="DELETE FROM $priceidx WHERE entity_id=?";
		$this->delete($sql,$pid);
		$cpe=$this->tablename("catalog_product_entity");
		$cs=$this->tablename("core_store");
		$cg=$this->tablename("customer_group");
		$cped=$this->tablename("catalog_product_entity_decimal");
		$ea=$this->tablename("eav_attribute");
		$cpetp=$this->tablename("catalog_product_entity_tier_price");
		$cpei=$this->tablename("catalog_product_entity_int");
		$sql="INSERT INTO $priceidx SELECT cped.entity_id,
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
				JOIN $ea as ead ON ead.entity_type_id=4  AND ead.attribute_code IN('price','special_price','minimal_price') AND cped.attribute_id=ead.attribute_id 
				JOIN $ea as eai ON eai.entity_type_id=4 AND eai.attribute_code='tax_class_id' 
				LEFT JOIN $cpetp as cpetp ON cpetp.entity_id=cped.entity_id 
				LEFT JOIN $cpetp as cpetp2 ON cpetp2.entity_id=cped.entity_id AND cpetp2.customer_group_id=cg.customer_group_id
				LEFT JOIN $cpei as cpei ON cpei.entity_id=cpe.entity_id AND cpei.attribute_id=eai.attribute_id 
				WHERE cpe.entity_id=?
				GROUP by cs.website_id,cg.customer_group_id
				ORDER by cg.customer_group_id,cs.website_id
		";
		$this->insert($sql,$pid);
		
	}
	
	public function processItemAfterImport(&$item,$params=null)
	{
		$pid=$params["product_id"];
		$this->buildPrinceIndex($item);
		return true;
	}
}


