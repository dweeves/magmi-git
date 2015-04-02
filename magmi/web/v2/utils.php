<?php
/**
 * Created by PhpStorm.
 * User: seb
 * Date: 26/03/15
 * Time: 18:17
 */

require_once(dirname(dirname(__DIR__)).'/inc/magmi_config.php');

function getSessionConfig()
{

    $conf=Magmi_Config::getInstance();
    if(isset($_SESSION['MAGMI_CONFIG_FILE']))
    {
        $conf->load($_SESSION['MAGMI_CONFIG_FILE']);
    }
    else
    {
        $conf->load();
    }
    return $conf;
}

function getWebServerType()
{
    $wst=$_SERVER["SERVER_SOFTWARE"];
    $wsdata=explode('/',$wst);
      if(count($wsdata)==2) {
           $sname = strtolower($wsdata[0]);
           $sver = strtok($wsdata[1], ' ');
           $classname=ucfirst($sname)."ServerHelper";
           $inst=new $classname($sver);
       }
       else
       {
           $sname=$wst;
           $sver=null;
       }
    return array('Server'=>$sname,'Version'=>$sver);
}



function checkMagentoDir($mdir)
{
    $msg=array("OK"=>null,"ERROR"=>null);

    if(file_exists($mdir) && file_exists("$mdir/app/Mage.php")) {
        $msg["OK"]="using magento directory $mdir";
    }
    else
    {
        $msg["ERROR"]="invalid magento directory $mdir";
    }
    return $msg;
}