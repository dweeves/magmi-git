<?php
require_once("security.php");
header('Pragma: public'); // required
header('Expires: -1'); // no cache
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
header('Cache-Control: private', false);
require_once("../inc/magmi_statemanager.php");
Magmi_StateManager::setState("canceled", true);
