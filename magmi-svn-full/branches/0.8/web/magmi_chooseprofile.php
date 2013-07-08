<?php
require_once("../inc/magmi_config.php");
require_once("../inc/magmi_pluginhelper.php");
require_once("magmi_web_utils.php");
$currentprofile=getWebParam("profile","default");
$eng=getWebParam("engineclass");
$newprofile=getWebParam("newprofile","");

if($newprofile!="")
{
	$ph=Magmi_PluginHelper::getInstance($currentprofile);
	$ph->setEngineClass($eng);

	$bcfg=new EnabledPlugins_Config($ph->getEngine()->getProfilesDir(),$currentprofile);
	$confdir=Magmi_Config::getInstance()->getConfDir();
	$npdir=$confdir.DS.$ph->getEngine()->getProfilesDir().DS.$newprofile;
	mkdir($npdir,Magmi_Config::getInstance()->getDirMask());
	$cpdir=$bcfg->getProfileDir();
	$filelist=scandir($cpdir);
	foreach($filelist as $fname)
	{
		if(substr($fname,-5)==".conf")
		{
			copy($cpdir.DS.$fname,$npdir.DS.$fname);
		}
	}
}
else
{
	$newprofile=$currentprofile;
}
$_SESSION["engineclass"]=$eng;
$_SESSION["profile"]=$newprofile;
//session_write_close();
header("Location:magmi.php");

