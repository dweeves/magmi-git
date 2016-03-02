<?php
function csvOptions($self, $prefix)
{
    require('csv_options.php');
}

function checkbox($self, $prefix, $name, $default, $description)
{
    require('checkbox.php');
}

function text($self, $prefix, $name, $default, $description)
{
    require('text.php');
}

function textarea($self, $prefix, $name, $default, $description)
{
    require('textarea.php');
}

function javascript($self, $prefix, $withCsvOptions, $plugin)
{
    require('javascript.php');
}

function startDiv($self, $prefix, $name, $show=true)
{
    $fullName = "$prefix:$name";
    ?><div id="<?php echo $fullName ?>"<?php if (!$show) {
    ?>style="display:none;"<?php 
}
    ?>><?php

}
function endDiv($self)
{
    ?></div><?php

}

function options($self, $title, $prefix, $entityName, $withCsvOptions, $withMagmiDelete, $withEnable, $withDefaults, $pruneKeepDefaultValue, $sourceText, $plugin)
{
    $default_rows_for_sets = "attribute_set_name,attribute_code,attribute_group_name
*,name,General
*,description,General
*,short_description,General
*,sku,General
*,weight,General
*,news_from_date,General
*,news_to_date,General
*,status,General
*,url_key,General
*,visibility,General
*,country_of_manufacture,General
*,price,Prices
*,group_price,Prices
*,special_price,Prices
*,special_from_date,Prices
*,special_to_date,Prices
*,tier_price,Prices
*,msrp_enabled,Prices
*,msrp_display_actual_price_type,Prices
*,msrp,Prices
*,tax_class_id,Prices
*,price_view,Prices
*,meta_title,Meta Information
*,meta_keyword,Meta Information
*,meta_description,Meta Information
*,image,Images
*,small_image,Images
*,thumbnail,Images
*,media_gallery,Images
*,gallery,Images
*,is_recurring,Recurring Profile
*,recurring_profile,Recurring Profile
*,custom_design,Design
*,custom_design_from,Design
*,custom_design_to,Design
*,custom_layout_update,Design
*,page_layout,Design
*,options_container,Design
*,gift_message_available,Gift Options";


    if (isset($title)) {
        ?><h3><?php echo $title ?></h3><?php

    }
    if ($withEnable) {
        checkbox($self, $prefix, 'enable', true, "Enable ${entityName} import");
        startDiv($self, $prefix, 'enabled', $self->getParam($prefix.":enable", "off")=="on");
    }
    if ($withCsvOptions) {
        csvOptions($self, $prefix);
    }
    ?>
    <h4>Import behavior</h4>
    <?php
    if ($withDefaults) {
        text($self, $prefix, 'default_values', "", "Set default values for non-existing columns in $sourceText (JSON)");
    }
    if ($prefix == '5B5AAI') {
        textarea($self, $prefix, 'default_rows', $default_rows_for_sets, "Add these attribute associations to given CSV data, '*' for attribute set name  means 'for each attribute set from given CSV' (Format: CSV with titles, spearator ',', enclosure '\"').");
    }
    checkbox($self, $prefix, 'prune', true, "Prune ${entityName}s which are not in $sourceText from database");
    startDiv($self, $prefix, 'prune_opts');
    if ($prefix == '5B5ATI' || $prefix == '5B5AAI') {
        checkbox($self, $prefix, 'prune_keep_system_attributes', true, "Dont touch non-user attributes when pruning.");
    }
    text($self, $prefix, 'prune_keep', $pruneKeepDefaultValue, "additionally, keep following ${entityName}s when pruning, even if not given in $sourceText (comma-separated)");
    endDiv($self);
    if ($withMagmiDelete) {
        checkbox($self, $prefix, 'magmi_delete', true, "Delete ${entityName}s marked \"magmi:delete\" = 1");
    }
    checkbox($self, $prefix, 'create', true, "Create ${entityName}s from $sourceText which are not in database");
    checkbox($self, $prefix, 'update', true, "Update ${entityName}s from $sourceText which are already in database");
    if ($prefix == '5B5ASI') {
        startDiv($self, $prefix, 'attribute_groups');
        options($self, null, '5B5AGI', 'attribute group', false, false, false, false, "General,Prices,Meta Information,Images,Recurring Profile,Design,Gift Options", '"magmi:groups"', $plugin);
        endDiv($self);
    }
    if ($withEnable) {
        endDiv($self);
    }
    javascript($self, $prefix, $withCsvOptions, $plugin);
}

?>