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

function setMessage($type,$data)
{
    $_SESSION["message"][$type][]=$data;
}

function clearMessages()
{
    unset($_SESSION["message"]);
    $_SESSION["message"]=array("OK"=>array(),
        "WARN"=>array(),
        "ERROR"=>array());
}

function hasMessages($type)
{
    return isset($_SESSION["message"]) && count($_SESSION["message"][$type])>0;
}

function getMessages($type)
{
    return $_SESSION["message"][$type];
}