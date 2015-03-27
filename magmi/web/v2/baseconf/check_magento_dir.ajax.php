<?php
/**
 * Created by PhpStorm.
 * User: seb
 * Date: 27/03/15
 * Time: 18:51
 */
session_start();
require_once("../utils.php");
$mdir=$_REQUEST["magentodir"];
if(file_exists($mdir) && file_exists("$mdir/Mage.php"))
{

    setMessage("OK","using magento directory $mdir");
}
else
{
    setMessage("ERROR","directory $mdir is not a magento directory")
}