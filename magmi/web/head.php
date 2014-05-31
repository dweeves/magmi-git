<?php
set_include_path(get_include_path() . PATH_SEPARATOR . "../inc");
ini_set("display_errors", 1);
ini_set("error_reporting", E_ALL);
ini_set("magic_quotes_gpc", 0);
require_once ("magmi_version.php");
session_start();
?>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title>MAGMI (MAGento Mass Importer) by Dweeves - version <?php echo Magmi_Version::$version ?></title>
<link rel="stylesheet" href="css/960.css"></link>
<link rel="stylesheet" href="css/reset.css"></link>
<link rel="stylesheet" href="css/magmi.css"></link>
<script
	src="//ajax.googleapis.com/ajax/libs/prototype/1.7.1.0/prototype.js"></script>
<script type="text/javascript" src="js/ScrollBox.js"></script>
<script type="text/javascript" src="js/magmi_utils.js"></script>
<META HTTP-EQUIV="Pragma" CONTENT="no-cache">
<META HTTP-EQUIV="Cache-control" CONTENT="no-cache">
<META HTTP-EQUIV="Expires" CONTENT="-1">
</head>
<body>