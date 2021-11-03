<?php

class Magmi_SimplesRelaterProcessor extends Magmi_ItemProcessor
{
 
    public function initialize($params)
    {
    }
    /* Plugin info declaration */
    public function getPluginInfo()
    {
        return array("name"=>"Simples Relater processor","author"=>"Liam Wiltshire","version"=>"0.0.1",
            "url"=>$this->pluginDocUrl("Simples_Relater_processor"));
    }

    public function getProductIdBySku($sku){
        $sql = "SELECT entity_id FROM catalog_product_entity WHERE sku = ?";
        return $this->selectone($sql,array($sku),'entity_id');
    }

    public function relateSimpleToConfigurable($simpleId,$configurableSku){
        
        $cpsl = $this->tablename("catalog_product_super_link");
        $cpr = $this->tablename("catalog_product_relation");
        
        
        $parentId = $this->getProductIdBySku($configurableSku);

        //Check if the relation already exists;
        
        $sql = "SELECT COUNT(*) AS rows FROM $cpsl WHERE parent_id = ? AND product_id = ?";
        if ($this->selectone($sql,array($parentId,$simpleId)) > 0) return true; //Relation already exists
        
        $sql = "INSERT INTO $cpsl (`parent_id`,`product_id`) values (?,?)";
        $this->insert($sql,array($parentId,$simpleId));
        
        $sql = "INSERT INTO $cpr (`parent_id`,`child_id`) values (?,?)";
        $this->insert($sql,array($parentId,$simpleId));
        
    }
    
    public function processItemAfterId(&$item, $params = null)
    {

        if ($item["type"] === "simple" && isset($item["parent_product"]) && trim($item["parent_product"]) != ""){
            $relations = explode(",",$item["parent_product"]);
            
            foreach ($relations as $relation){
                $relate = explode(":",$relation);
                switch ($relate[0]){
                    case 'configurable':
                        $this->relateSimpleToConfigurable($params['product_id'],$relate[1]);
                        break;
                    case 'grouped':
                        break;
                    case 'bundled':
                        break;
                }
            }
            
        }
        
        return true;
    }

    public function getPluginParamNames()
    {
        return array();
        //return array("CFGR:simplesbeforeconf","CFGR:updsimplevis","CFGR:nolink");
    }

    public static function getCategory()
    {
        return "Product Type Import";
    }
}
