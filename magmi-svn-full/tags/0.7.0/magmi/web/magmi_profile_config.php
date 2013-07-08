<?php 
require_once("magmi_config.php");
$conf=Magmi_Config::getInstance();
$conf->load();
global $profile;
$profile=null;
if(isset($_REQUEST["profile"]))
{
	$profile=$_REQUEST["profile"];
	if($profile=="default")
	{
		$profile=null;
	}
}
$plconf=new EnabledPlugins_Config($profile);
$plconf->load();
$profilelist=$conf->getProfileList();
$conf_ok=1;
?>
<?php include_once("./magmi_profile_panel.php")?>