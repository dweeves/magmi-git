<?php

class ClearOrdersUtility extends Magmi_UtilityPlugin
{

    public function getPluginInfo()
    {
        return array("name"=>"Clear Orders","author"=>"www.blinkdata.com","version"=>"1.0.1");
    }

    public function runUtility()
    {
        $sql = "SET FOREIGN_KEY_CHECKS = 0";
        $this->exec_stmt($sql);
        $tables = array("sales_flat_order","sales_flat_order_address","sales_flat_order_grid","sales_flat_order_item",
            "sales_flat_order_payment","sales_flat_order_status_history","sales_flat_quote","sales_flat_quote_address",
            "sales_flat_quote_address_item","sales_flat_quote_item","sales_flat_quote_item_option",
            "sales_flat_quote_payment","sales_flat_quote_shipping_rate","sales_flat_shipment",
            "sales_flat_shipment_comment","sales_flat_shipment_grid","sales_flat_shipment_item",
            "sales_flat_shipment_track","log_quote","sales_invoiced_aggregated","sales_invoiced_aggregated_order",
            "sales_flat_invoice","sales_flat_invoice_comment","sales_flat_invoice_grid","sales_flat_invoice_item",
            "sales_flat_creditmemo","sales_flat_creditmemo_comment","sales_flat_creditmemo_grid",
            "sales_flat_creditmemo_item","downloadable_link_purchased","downloadable_link_purchased_item");
        
        foreach ($tables as $table)
        {
            $this->exec_stmt("TRUNCATE TABLE `" . $this->tablename($table) . "`");
        }
        
        $sql = "SET FOREIGN_KEY_CHECKS = 1";
        $this->exec_stmt($sql);
        
        echo "Orders cleared";
    }

    public function getWarning()
    {
        return "Are you sure?, it will destroy all orders, quotes, shipments and credits!!!";
    }

    public function getShortDescription()
    {
        return "This Utility clears all of your orders, quotes, shipments and credits";
    }
}