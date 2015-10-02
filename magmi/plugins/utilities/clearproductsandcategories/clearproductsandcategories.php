<?php

class ClearProductandcategoryUtility extends Magmi_UtilityPlugin
{
    public function getPluginInfo()
    {
        return array("name"=>"Clear Catalog, Categories and Reviews","author"=>"www.blinkdata.com,dweeves","version"=>"1.0.2");
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

            "catalog_category_product_index","catalog_category_product",
            "catalogrule_affected_product","catalogrule_product","catalogrule_product_price","catalogsearch_fulltext",
            "catalogsearch_query","catalogsearch_result","product_alert_price","product_alert_stock","tag",
            "tag_properties","tag_relation","tag_summary",

            "sales_bestsellers_aggregated_daily","sales_bestsellers_aggregated_monthly",
            "sales_bestsellers_aggregated_yearly","report_viewed_product_index",

            "review","review_detail","review_entity_summary","review_store");

        if ($this->checkMagentoVersion("1.7.x", ">=")) {
            $tables[] = "report_viewed_product_aggregated_daily";
            $tables[] = "report_viewed_product_aggregated_monthly";
            $tables[] = "report_viewed_product_aggregated_yearly";
        }

        // clear flat catalogs index
        $stmt = $this->exec_stmt("SHOW TABLES LIKE '" . $this->tablename('catalog_product_flat') . "%'", null, false);
        while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
            $this->exec_stmt("TRUNCATE TABLE " . $row[0]);
        }

        // clear flat category index
        $stmt = $this->exec_stmt("SHOW TABLES LIKE '" . $this->tablename('catalog_category_flat') . "%'", null, false);
        while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
            $this->exec_stmt("TRUNCATE TABLE " . $row[0]);
        }

        foreach ($tables as $table) {
            $this->exec_stmt("TRUNCATE TABLE `" . $this->tablename($table) . "`");
        }

        $sql = "SET FOREIGN_KEY_CHECKS = 1";
        $this->exec_stmt($sql);

        //safely remove all non root categories (not destroying structural categories)
        //all sub values would be removed by cascading triggers.
        $sql="DELETE FROM ".$this->tablename('catalog_category_entity')." WHERE level>1";
        $this->exec_stmt($sql);

        //default cat stock
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
