<?php
require_once("../inc/magmi_config.php");
$currentprofile=$_REQUEST["profile"];
if($currentprofile=="default")
{
	$currentprofile=null;
}
$newprofile=$_REQUEST["newprofile"];
if($newprofile!="")
{

	$bcfg=new EnabledPlugins_Config($currentprofile);
	$confdir=Magmi_Config::getInstance()->getConfDir();
	$npdir=$confdir.DS.$newprofile;
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
header("Location:magmi.php?configstep=2&profile=$newprofile");

