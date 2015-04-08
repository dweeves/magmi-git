<?php
/**
 * Created by PhpStorm.
 * User: seb
 * Date: 08/04/15
 * Time: 17:18
 */
require_once("basedefs.php");
require_once(UI_INCDIR.'/session.php');
require_once(UI_INCDIR.'/security.php');
require_once(UI_INCDIR.'/message.php');
$base_url=dirname(str_replace($_SERVER['DOCUMENT_ROOT'],'',$incdir));
define("BASE_URL",$base_url);
?>