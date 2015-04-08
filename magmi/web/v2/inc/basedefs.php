<?php
 $incdir=__DIR__;
 define('MAGMI_DIR',dirname(dirname(dirname(__DIR__))));
 define('UI_INCDIR',$incdir);
define('UI_BASEDIR',dirname($incdir));
 require_once(MAGMI_DIR."/inc/magmi_defs.php");
 require_once(UI_INCDIR.'/utils.php');
?>