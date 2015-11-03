<?php
require_once("security.php");
require_once("../inc/magmi_config.php");
$conf = Magmi_Config::getInstance();
$conf->load();

$selected = $conf->get('DATABASE', 'resource');
$magentoConfig = new SimpleXMLElement(file_get_contents($conf->get('MAGENTO', 'basedir').'/app/etc/local.xml'));
foreach ($magentoConfig->global->resources->children() as $resource) {
    $name = $resource->getName();
    if ($name != 'db') {
        echo '<option value="'.$name.'" '.($selected == $name ? 'selected' : '').'>'.$name.'</option>';
    }
}
