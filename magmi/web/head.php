<?php
set_include_path(get_include_path() . PATH_SEPARATOR . '../inc');
require_once('security.php');
require_once('magmi_version.php');
ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL);
ini_set('magic_quotes_gpc', 0);
session_start();
?>
<html>
<head>
	<title>MAGMI (MAGento Mass Importer) by Dweeves - version <?php echo Magmi_Version::$version ?></title>

	<meta name="viewport" content="initial-scale=1,user-scalable=no,maximum-scale=1,width=device-width">
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<meta http-equiv="Pragma" content="no-cache">
	<meta http-equiv="Cache-control" content="no-cache">
	<meta http-equiv="Expires" content="-1">
	<meta http-equiv="Content-Security-Policy" content="default-src 'self' 'unsafe-inline';">

	<link rel="author" href="humans.txt" />
	<link rel="stylesheet" href="css/magmi.css"></link>
	<link rel="stylesheet" href="../../node_modules/font-awesome/css/font-awesome.min.css">

	<script type="text/javascript" src="js/prototype.js"></script>
	<script type="text/javascript" src="../../node_modules/jquery/dist/jquery.min.js"></script>
	<script type="text/javascript" src="../../node_modules/popper.js/dist/umd/popper.min.js"></script>
	<script type="text/javascript" src="../../node_modules/bootstrap/dist/js/bootstrap.min.js"></script>
	<script type="text/javascript">var $j = jQuery.noConflict();</script>
	<script type="text/javascript" src="js/ScrollBox.js"></script>
	<script type="text/javascript" src="js/magmi_utils.js"></script>
	<script type="text/javascript" src="js/init.js"></script>
</head>
<body class="container-fluid">