<?php
require_once("../inc/magmi_defs.php");
require_once("magmi_pluginhelper.php");
require_once("magmi_web_utils.php");
$pltype=getWebParam("plugintype");
$plclass=getWebParam("pluginclass");
$profile=getWebParam("profile","");
$file=getWebParam('file');

$ph=Magmi_PluginHelper::getInstance($profile);
$ph->setEngineClass(getWebParam("engineclass"));
$plinst=$ph->createInstance($pltype,$plclass,getWebParams(),true);
echo $plinst->getOptionsPanel($file)->getHtml();