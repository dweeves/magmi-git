<?php 
require_once("magmi_config.php");
require_once("magmi_pluginhelper.php");
$engclass=getWebParam("engineclass");
$ph=Magmi_PluginHelper::getInstance();
 setEngineAndProfile($ph, $engclass, getWebParam("profile"));
$conf_ok=1;
?>
<?php include_once("./magmi_profile_panel.php")?>