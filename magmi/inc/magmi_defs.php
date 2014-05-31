<?php
define("MAGMI_BASEDIR", dirname(dirname(__FILE__)));
define("MAGMI_INCDIR", MAGMI_BASEDIR . "/inc");
define("MAGMI_INTEGRATION_INCDIR", MAGMI_BASEDIR . "/integration/inc");
define("MAGMI_ENGINE_DIR", MAGMI_BASEDIR . "/engines");
set_include_path(
    ini_get("include_path") . PATH_SEPARATOR . MAGMI_INCDIR . PATH_SEPARATOR . MAGMI_INTEGRATION_INCDIR . PATH_SEPARATOR .
         MAGMI_ENGINE_DIR);
$dtz = date_default_timezone_get();
if ($dtz == "")
{
    date_default_timezone_set("UTC");
}
require_once ('magmi_loggers.php');
