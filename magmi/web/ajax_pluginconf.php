<?php
require_once("security.php");
require_once("../inc/magmi_defs.php");
require_once("magmi_pluginhelper.php");
require_once("magmi_web_utils.php");
$pltype = $_REQUEST["plugintype"];
$plclass = $_REQUEST["pluginclass"];
$profile = $_REQUEST["profile"];
$file = null;
if (isset($_REQUEST['file'])) {
    $file = $_REQUEST['file'];
}
if ($profile == "") {
    $profile = null;
}

if (isset($_REQUEST["engine"])) {
    $engdef = explode(":", $_REQUEST["engine"]);
    $engine_name = basename($engdef[0]);
    $engine_class = $engdef[1];
    require_once("../engines/$engine_name.php");
    $enginst = new $engine_class();
    $enginst->initialize();
} else {
    $enginst = null;
}
$plinst = Magmi_PluginHelper::getInstance($profile)->createInstance($pltype, $plclass, $_REQUEST, $enginst);
echo $plinst->getOptionsPanel($file)->getHtml();
