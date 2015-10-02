<?php
/**
 * Created by PhpStorm.
 * User: seb
 * Date: 26/03/15
 * Time: 18:58
 */
require_once("../../../inc/magmi_defs.php");
require_once("magmi_utils.php");
require_once("../utils.php");
session_start();
$conf= $_REQUEST['magmiconf'];
if ($conf!=='') {
    if (!isabspath($conf)) {
        $conf = MAGMI_BASEDIR . DIRECTORY_SEPARATOR . $conf;
    }
    if (!file_exists($conf)) {
        setMessage("ERROR", "invalid file : $conf not found", "magmiconf");
    } else {
        $_SESSION['MAGMI_CONFIG_FILE'] = $conf;
        setMessage("OK", "using magmi configuration file : $conf ", "magmiconf");
    }
} else {
    unset($_SESSION['MAGMI_CONFIG_FILE']);
}
