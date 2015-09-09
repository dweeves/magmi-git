<?php
require_once ("magmi_config.php");
$conf = Magmi_Config::getInstance();
$conf->load();
$profilelist = $conf->getProfileList();
$conf_ok = 1;
?>
<?php include_once("./magmi_profile_panel.php")?>