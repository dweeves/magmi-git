<div class="container">
<?php
   $conf=getSessionConfig();
    $cf=$conf->getConfFile();
$cf=isset($_SESSION['MAGMI_CONFIG_FILE'])?$_SESSION['MAGMI_CONFIG_FILE']:'';
    require_once("magmi/magmi_choose_conf.php");
?>
<div id="magentodir_container">
    <?php require_once("magento/magentodir.php");?>
</div>
<div id="dbconf_container">
    <?php require_once("db/db_config.php");?>
</div>
</div>

