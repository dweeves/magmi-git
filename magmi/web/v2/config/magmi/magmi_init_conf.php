<?php
/**
 * Created by PhpStorm.
 * User: seb
 * Date: 30/03/15
 * Time: 19:20
 */
require_once("../../../inc/magmi_defs.php");
$conf=getSessionConfig();

if($conf->isDefault())
{
    $conf->saveToFile(MAGMI_BASEDIR."/conf/magmi.ini");
}
$conf->load();