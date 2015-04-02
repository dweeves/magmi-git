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

function getDBCredentialsFromLocalXML($localxml)
{
    $entries=array("host"=>null,"username"=>null,"password"=>null,"dbname"=>null);
    if(file_exists($localxml)) {
        $doc = simplexml_load_file($localxml);
        $cnxp = $doc->xpath("//default_setup/connection");
        if (count($cnxp) > 0) {
            $cnx = $cnxp[0];
            foreach ($cnx->children() as $entry) {
                $en = $entry->getName();
                if (in_array($en, array_keys($entries))) {

                    $entries[$en] = "".$entry;
                }
            }
        }
    }
    return $entries;
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