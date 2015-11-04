<?php
require_once("security.php");
require_once("../inc/magmi_config.php");
$conf = Magmi_Config::getInstance();
if ($conf->save($_POST)) {
    $date = filemtime($conf->getConfFile());
    echo "Common Configuration saved (" . strftime("%c", $date) . ")";
} else {
    $lasterr = error_get_last();
    echo "<div class='error'>" . $lasterr['message'] . "</div>";
}
