<?php
require_once ("../inc/magmi_config.php");
$currentprofile = $_REQUEST["profile"];
if ($currentprofile == "default")
{
    $currentprofile = null;
}
$newprofile = $_REQUEST["newprofile"];
if ($newprofile != "")
{
    
    $bcfg = new EnabledPlugins_Config($currentprofile);
    $confdir = Magmi_Config::getInstance()->getConfDir();
    $npdir = $confdir . DIRSEP . $newprofile;
    mkdir($npdir, Magmi_Config::getInstance()->getDirMask());
    $cpdir = $bcfg->getProfileDir();
    $filelist = scandir($cpdir);
    foreach ($filelist as $fname)
    {
        if (substr($fname, -5) == ".conf")
        {
            copy($cpdir . DIRSEP . $fname, $npdir . DIRSEP . $fname);
        }
    }
}
else
{
    $newprofile = $currentprofile;
}
header("Location:magmi.php?configstep=2&profile=$newprofile");

