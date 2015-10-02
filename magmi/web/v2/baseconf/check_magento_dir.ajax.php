<?php
/**
 * Created by PhpStorm.
 * User: seb
 * Date: 27/03/15
 * Time: 18:51
 */
session_start();
require_once("../utils.php");
require_once("../message.php");
$mdir=$_REQUEST["magentodir"];
if (file_exists($mdir) && file_exists("$mdir/app/Mage.php")) {
    $conf=getSessionConfig();
    $conf->set("MAGENTO", 'basedir', $mdir);
    $conf->save();
    setMessage("OK", "using magento directory $mdir", "magentodir");
} else {
    setMessage("ERROR", "directory $mdir is not a magento directory", "magentodir");
}
