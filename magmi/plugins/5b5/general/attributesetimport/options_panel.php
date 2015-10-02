<?php
    require_once('functions.php');
?>


<div class="plugin_description">
	This plugin imports (inserts, updates, deletes) a list of attribute sets before the products will be updated.
</div>
<?php
    options($this, 'Attribute import options', '5B5ATI', 'attribute', true, true, true, true, 'category_ids,country_of_manufacture,created_at,custom_design,custom_design_from,custom_design_to,custom_layout_update,description,gallery,gift_message_available,group_price,has_options,image,image_label,is_recurring,links_exist,links_purchased_separately,links_title,media_gallery,meta_description,meta_keyword,meta_title,minimal_price,msrp,msrp_display_actual_price_type,msrp_enabled,name,news_from_date,news_to_date,old_id,options_container,page_layout,price,price_type,price_view,recurring_profile,required_options,samples_title,shipment_type,short_description,sku,sku_type,small_image,small_image_label,special_from_date,special_price,special_to_date,status,tax_class_id,thumbnail,thumbnail_label,tier_price,updated_at,url_key,url_path,visibility,weight,weight_type', 'CSV', $this->_plugin);
    options($this, 'Attribute set import options', '5B5ASI', 'attribute set', true, true, true, true, 'Default', 'CSV', $this->_plugin);
    options($this, 'Attribute association import options', '5B5AAI', 'attribute association', true, true, true, true, 'Default', 'CSV', $this->_plugin);
?>
