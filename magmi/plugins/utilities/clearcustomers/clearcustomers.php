<?php

class ClearCustomerUtility extends Magmi_UtilityPlugin
{

    public function getPluginInfo()
    {
        return array("name"=>"Clear Customers, Tags and Wishlists","author"=>"www.blinkdata.com","version"=>"1.0.1");
    }

    public function runUtility()
    {
        $sql = "SET FOREIGN_KEY_CHECKS = 0";
        $this->exec_stmt($sql);
        $tables = array("customer_address_entity","customer_address_entity_datetime","customer_address_entity_decimal",
            "customer_address_entity_int","customer_address_entity_text","customer_address_entity_varchar",
            "customer_entity","customer_entity_datetime","customer_entity_decimal","customer_entity_int",
            "customer_entity_text","customer_entity_varchar","tag","tag_properties","tag_relation","wishlist",
            "wishlist_item","wishlist_item_option","log_customer");
        
        foreach ($tables as $table)
        {
            $this->exec_stmt("TRUNCATE TABLE `" . $this->tablename($table) . "`");
        }
        
        $sql = "SET FOREIGN_KEY_CHECKS = 1";
        $this->exec_stmt($sql);
        
        echo "Customers cleared";
    }

    public function getWarning()
    {
        return "Are you sure?, it will destroy all customers, tags and wishlists!!!";
    }

    public function getShortDescription()
    {
        return "This Utility clears all of your customers, tags and wishlists";
    }
}