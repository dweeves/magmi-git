<?php

require_once("security.php");
$profile = $_REQUEST["profile"];
$dataSourceClasses = $_REQUEST["PLUGINS_DATASOURCES:class"];
$generalClasses = $_REQUEST["PLUGINS_GENERAL:classes"];
$itemProcessorClasses = $_REQUEST["PLUGINS_ITEMPROCESSORS:classes"];
if (!isset($itemProcessorClasses)) {
    $itemProcessorClasses = "";
}
if (!isset($generalClasses)) {
    $generalClasses = "";
}
$plugins = array();

foreach (explode(",", $dataSourceClasses) as $className) {
    $plugins[$className] = "datasources";
}

foreach (explode(",", $generalClasses) as $className) {
    $plugins[$className] = "general";
}

foreach (explode(",", $itemProcessorClasses) as $className) {
    $plugins[$className] = "itemprocessors";
}

require_once("../inc/magmi_pluginhelper.php");
require_once("../inc/magmi_config.php");
// saving plugin selection
$epc = new EnabledPlugins_Config($profile);
$epc->setPropsFromFlatArray(
    array("PLUGINS_DATASOURCES:class" => $dataSourceClasses, "PLUGINS_GENERAL:classes" => $generalClasses,
          "PLUGINS_ITEMPROCESSORS:classes" => $itemProcessorClasses)
);
if ($epc->save()) {

    // saving plugins params
    foreach ($plugins as $className => $pfamily) {
        if ($className != "") {
            $pluginInstance = Magmi_PluginHelper::getInstance($profile)->createInstance($pfamily, $className, $_REQUEST);
            $paramlist = $pluginInstance->getPluginParamNames();
            $sarr = $pluginInstance->getPluginParams($_REQUEST);
            $parr = $pluginInstance->getPluginParamsNoCurrent($_REQUEST);

            foreach ($paramlist as $pname) {
                if (!isset($parr[$pname])) {
                    $parr[$pname] = 0;
                }
            }
            $farr = array_merge($sarr, $parr);
            if (!$pluginInstance->persistParams($farr)) {
                $lasterr = error_get_last();
                echo "<div class='error'>" . $lasterr['message'] . "</div>";
            }
        }
    }
    $date = filemtime($epc->getConfFile());
    echo "Profile $profile saved (" . strftime("%c", $date) . ")";
} else {
    $lasterr = error_get_last();
    echo "<div class='error'>" . $lasterr['message'] . "</div>";
}
