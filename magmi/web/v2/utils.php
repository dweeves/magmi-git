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
    if (isset($_SESSION['MAGMI_CONFIG_FILE'])) {
        $conf->load($_SESSION['MAGMI_CONFIG_FILE']);
    } else {
        $conf->load();
    }
    return $conf;
}
