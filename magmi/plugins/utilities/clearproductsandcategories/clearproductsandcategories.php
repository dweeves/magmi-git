<?php

class ClearProductandcategoryUtility extends Magmi_UtilityPlugin
{

    public function getPluginInfo()
    {
        return array("name"=>"Clear Catalog, Categories and Reviews","author"=>"www.blinkdata.com","version"=>"1.0.1");
    }

    public function runUtility()
    {
        $sql = "SET FOREIGN_KEY_CHECKS = 0";
        $this->exec_stmt($sql);
        $tables = array("catalog_product_bundle_option","catalog_product_bundle_option_value",
            "catalog_product_bundle_selection","catalog_product_entity_datetime","catalog_product_entity_decimal",
            "catalog_product_entity_gallery","catalog_product_entity_int","catalog_product_entity_media_gallery",
            "catalog_product_entity_media_gallery_value","catalog_product_entity_text",
            "catalog_product_entity_tier_price","catalog_product_entity_varchar","catalog_product_link",
            "catalog_product_link_attribute_decimal","catalog_product_link_attribute_int",
            "catalog_product_link_attribute_varchar",
            
            // "catalog_product_link_attribute",
            // "catalog_product_link_type",
            "catalog_product_option","catalog_product_option_price","catalog_product_option_title",
            "catalog_product_option_type_price","catalog_product_option_type_title","catalog_product_option_type_value",
            "catalog_product_super_attribute","catalog_product_super_attribute_label",
            "catalog_product_super_attribute_pricing","catalog_product_super_link","catalog_product_enabled_index",
            "catalog_product_website","catalog_product_entity","catalog_product_relation","cataloginventory_stock",
            "cataloginventory_stock_item","cataloginventory_stock_status",

            "catalog_category_product_index","catalog_category_product","catalog_category_entity",
            "catalog_category_entity_datetime","catalog_category_entity_decimal","catalog_category_entity_int",
            "catalog_category_entity_text","catalog_category_entity_varchar",

            "catalogrule_affected_product","catalogrule_product","catalogrule_product_price","catalogsearch_fulltext",
            "catalogsearch_query","catalogsearch_result","product_alert_price","product_alert_stock","tag",
            "tag_properties","tag_relation","tag_summary",

            "sales_bestsellers_aggregated_daily","sales_bestsellers_aggregated_monthly",
            "sales_bestsellers_aggregated_yearly","report_viewed_product_index",

            "review","review_detail","review_entity_summary","review_store");
        
        if ($this->getMagentoVersion() >= "1.7.")
        {
            $tables[] = "report_viewed_product_aggregated_daily";
            $tables[] = "report_viewed_product_aggregated_monthly";
            $tables[] = "report_viewed_product_aggregated_yearly";
        }
        
        // clear flat catalogs index
        $stmt = $this->exec_stmt("SHOW TABLES LIKE '" . $this->tablename('catalog_product_flat') . "%'", NULL, false);
        while ($row = $stmt->fetch(PDO::FETCH_NUM))
        {
            $this->exec_stmt("TRUNCATE TABLE " . $row[0]);
        }
        
        // clear flat category index
        $stmt = $this->exec_stmt("SHOW TABLES LIKE '" . $this->tablename('catalog_category_flat') . "%'", NULL, false);
        while ($row = $stmt->fetch(PDO::FETCH_NUM))
        {
            $this->exec_stmt("TRUNCATE TABLE " . $row[0]);
        }
        
        foreach ($tables as $table)
        {
            $this->exec_stmt("TRUNCATE TABLE `" . $this->tablename($table) . "`");
        }
        
        $sql = "SET FOREIGN_KEY_CHECKS = 1";
        $this->exec_stmt($sql);
        
        $tname = $this->tablename("eav_entity_type");
        $sql = "select entity_type_id from $tname where entity_model='catalog/category'";
        $entity_type_id = $this->selectone($sql, null, "entity_type_id");
        
        $tname = $this->tablename("eav_attribute_set");
        $sql = "select attribute_set_id from $tname where entity_type_id=$entity_type_id";
        $attributeSetId = $this->selectone($sql, null, "attribute_set_id");
        
        $tname = $this->tablename("eav_attribute");
        
        $sql = "select attribute_id from $tname where entity_type_id={$entity_type_id} and attribute_code='is_active'";
        $isactive_id = $this->selectone($sql, null, "attribute_id");
        
        $sql = "select attribute_id from $tname where entity_type_id={$entity_type_id} and attribute_code='include_in_menu'";
        $includeinmenu_id = $this->selectone($sql, null, "attribute_id");
        
        $sql = "select attribute_id from $tname where entity_type_id={$entity_type_id} and attribute_code='url_key'";
        $urlkey_id = $this->selectone($sql, null, "attribute_id");
        
        $sql = "select attribute_id from $tname where entity_type_id={$entity_type_id} and attribute_code='name'";
        $name_id = $this->selectone($sql, null, "attribute_id");
        
        $sql = "select attribute_id from $tname where entity_type_id={$entity_type_id} and attribute_code='display_mode'";
        $displaymode_id = $this->selectone($sql, null, "attribute_id");
        
        $sql = "insert  into " . $this->tablename("catalog_category_entity") . " ";
        $sql .= "(entity_id,entity_type_id,attribute_set_id,parent_id,created_at,updated_at,path,position,level,children_count) values ";
        $sql .= "(1,{$entity_type_id},{$attributeSetId},0,'0000-00-00 00:00:00','2009-02-20 00:25:34','1'  ,1,0,1),";
        $sql .= "(3,{$entity_type_id},{$attributeSetId},1,'0000-00-00 00:00:00','2009-02-20 00:25:34','1/3'  ,3,1,1),";
        $sql .= "(4,{$entity_type_id},{$attributeSetId},3,'0000-00-00 00:00:00','2009-02-20 00:25:34','1/3/4',4,2,0)";
        $this->exec_stmt($sql);
        
        $sql = "insert  into " . $this->tablename("catalog_category_entity_int") . " ";
        $sql .= "(value_id,entity_type_id,attribute_id,store_id,entity_id,value) values ";
        $sql .= "(1,{$entity_type_id},{$isactive_id},0,1,1),";
        $sql .= "(2,{$entity_type_id},{$includeinmenu_id},0,1,1),";
        $sql .= "(3,{$entity_type_id},{$isactive_id},0,3,1),";
        $sql .= "(4,{$entity_type_id},{$includeinmenu_id},0,3,1),";
        $sql .= "(5,{$entity_type_id},{$isactive_id},0,4,1),";
        $sql .= "(6,{$entity_type_id},{$includeinmenu_id},1,4,1)";
        $this->exec_stmt($sql);
        
        $sql = "insert  into " . $this->tablename("catalog_category_entity_varchar") . " ";
        $sql .= "(value_id,entity_type_id,attribute_id,store_id,entity_id,value) values ";
        $sql .= "(1,{$entity_type_id},{$name_id},0,3,'Root Catalog'),";
        $sql .= "(2,{$entity_type_id},{$displaymode_id},0,3,'PRODUCTS'),";
        $sql .= "(3,{$entity_type_id},{$urlkey_id},0,3,'root-catalog'),";
        $sql .= "(4,{$entity_type_id},{$name_id},0,4,'Default Category'),";
        $sql .= "(5,{$entity_type_id},{$displaymode_id},0,4,'PRODUCTS'),";
        $sql .= "(6,{$entity_type_id},{$urlkey_id},0,4,'default-category')";
        $this->exec_stmt($sql);
        
        $sql = "insert  into " . $this->tablename("cataloginventory_stock") . " ";
        $sql .= "(stock_id,stock_name) values ";
        $sql .= "(1,'Default')";
        $this->exec_stmt($sql);
        
        echo "Catalog and categories cleared";
    }

    public function getWarning()
    {
        return "Are you sure?, it will destroy all existing items in catalog and the category tree!!!";
    }

    public function getShortDescription()
    {
        return "This Utility clears the catalog, categories and reviews";
    }
}