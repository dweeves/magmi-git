<?php
require_once(dirname(dirname(__FILE__))."/dbhelper.class.php");
require_once(dirname(dirname(__FILE__))."/magmi_config.php");
class CatalogClearUtility extends DbHelper
{
	private $tprefix;
	
	public function __construct()
	{
	}
	
	public function connect()
	{
		$mconf=new Magmi_Config();
		$mconf->load();
		$host=$mconf->get("DATABASE","host","localhost");
		$dbname=$mconf->get("DATABASE","dbname","magento");
		$user=$mconf->get("DATABASE","user");
		$pass=$mconf->get("DATABASE","password");
		$debug=$mconf->get("DATABASE","debug");
		$this->tprefix=$mconf->get("DATABASE","table_prefix");
		
		$this->initDb($host,$dbname,$user,$pass,$debug);
		//suggested by pastanislas
		$this->_db->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY,true);
	}
	
	public function disconnect()
	{
		$this->exitDb();
	}
	
	public function tablename($magname)
	{
		return $this->tprefix!=""?$this->tprefix."_$magname":$magname;
	}	/**
	 * Clear all products from catalog
	 */
	public function clearProducts()
	{
		$sql="SET FOREIGN_KEY_CHECKS = 0";
		$this->exec_stmt($sql);
		$tables=array("catalog_product_bundle_option",
					  "catalog_product_bundle_option_value",
					  "catalog_product_bundle_selection",
					  "catalog_product_entity_datetime",
					  "catalog_product_entity_decimal",
					  "catalog_product_entity_gallery",
					  "catalog_product_entity_int",
					  "catalog_product_entity_media_gallery",
					  "catalog_product_entity_media_gallery_value",
					  "catalog_product_entity_text",
					  "catalog_product_entity_tier_price",
					  "catalog_product_entity_varchar",
					  "catalog_product_entity",
					  "catalog_product_option",
					  "catalog_product_option_price",
					  "catalog_product_option_title",
					  "catalog_product_option_type_price",
					  "catalog_product_option_type_title",
					  "catalog_product_option_type_value",		
					  "catalog_product_super_attribute_label",
					  "catalog_product_super_attribute_pricing",
					  "catalog_product_super_attribute",
					  "catalog_product_super_link",
					  "catalog_product_relation",
					  "catalog_product_enabled_index",
					  "catalog_product_website",
					  "catalog_category_product_index",
					  "catalog_category_product",
					  "cataloginventory_stock_item",
					  "cataloginventory_stock_status");


		foreach($tables as $table)
		{
			$this->exec_stmt("TRUNCATE TABLE `".$this->tablename($table)."`");
		}

		$sql="SET FOREIGN_KEY_CHECKS = 1";

		$this->exec_stmt($sql);
	}
}

