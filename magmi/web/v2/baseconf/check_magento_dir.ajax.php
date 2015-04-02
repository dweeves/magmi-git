<?php
/**
 * Created by PhpStorm.
 * User: seb
 * Date: 27/03/15
 * Time: 18:51
 */
require_once('../session.php');
require_once("../utils.php");
require_once("../message.php");
$conf=getSessionConfig();

if(isset($_REQUEST["magentodir"])) {
    $mdir = $_REQUEST["magentodir"];
}
else
{
    $mdir=$conf->getMagentoDir();
}
if(file_exists($mdir) && file_exists("$mdir/app/Mage.php"))
{
    $conf->set("MAGENTO",'basedir',$mdir);
    $conf->set("DATABASE",'connectivity','localxml');
    $conf->save();

    setMessage("OK","using magento directory $mdir","magentodir");
}
else
{
    setMessage("ERROR","directory $mdir is not a magento directory","magentodir");
}