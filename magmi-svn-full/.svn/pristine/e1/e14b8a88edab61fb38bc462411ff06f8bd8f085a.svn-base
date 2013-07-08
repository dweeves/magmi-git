<?php
session_start();
unset($_SESSION["plugin_install_error"]);
require_once("../inc/magmi_pluginhelper.php");
$ph=Magmi_PluginHelper::getInstance();
$result=$ph->installPluginPackage($_FILES["plugin_package"]["tmp_name"]);
if($result["plugin_install"]=="ERROR")
{
	$_SESSION["plugin_install_error"]=$result["ERROR"];
}
session_write_close();
header("Location: ./magmi.php");