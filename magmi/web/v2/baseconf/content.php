<div class="container">
<?php
require_once('../session.php');
require_once("../utils.php");
require_once("../message.php");
   $conf=getSessionConfig();
    $cf=$conf->getConfFile();
$cf=isset($_SESSION['MAGMI_CONFIG_FILE'])?$_SESSION['MAGMI_CONFIG_FILE']:'';
    require_once("magmi_choose_conf.php");
?>
<div id="magentodir_container">
    <?php require_once("magentodir.php");?>
</div>

</div>

