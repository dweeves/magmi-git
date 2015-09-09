<?php

class Magmi_OptimizerPlugin extends Magmi_GeneralImportPlugin
{

    public function getPluginInfo()
    {
        return array("name"=>"Magmi Optimizer","author"=>"Dweeves","version"=>"1.0.5",
            "url"=>$this->pluginDocUrl("Magmi_Optimizer"));
    }

    public function beforeImport()
    {
        $tbls = array("eav_attribute_option_value"=>array("value","MAGMI_EAOV_OPTIMIZATION_IDX"),
            "catalog_product_entity_media_gallery"=>array("value","MAGMI_CPEM_OPTIMIZATION_IDX"),
            "catalog_category_entity_varchar"=>array("value","MAGMI_CCEV_OPTIMIZATION_IDX"),
            "eav_attribute"=>array("attribute_code","MAGMI_EA_CODE_OPTIMIZATION_IDX"));
        $this->log("Optimizing magmi", "info");
        foreach ($tbls as $tblname => $idxinfo)
        {
            try
            {
                $t = $this->tablename($tblname);
                $this->log("Adding index {$idxinfo[1]} on $t", "info");
                $sql = "ALTER  TABLE $t ADD INDEX {$idxinfo[1]} (`{$idxinfo[0]}`)";
                $this->exec_stmt($sql);
            }
            catch (Exception $e)
            {
                // ignore exception
                $this->log("Already optmized!", "info");
            }
        }
        return true;
    }

    public function initialize($params)
    {}
}