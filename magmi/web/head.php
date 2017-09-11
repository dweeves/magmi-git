<?php
	set_include_path(get_include_path() . PATH_SEPARATOR . "../inc");
	require_once('security.php');
	ini_set("display_errors", 1);
	ini_set("error_reporting", E_ALL);
	ini_set("magic_quotes_gpc", 0);
	require_once("magmi_version.php");
	session_start();
?>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<meta name="viewport" content="initial-scale=1,user-scalable=no,maximum-scale=1,width=device-width">
	<title>MAGMI (MAGento Mass Importer) by Dweeves - version <?php echo Magmi_Version::$version ?></title>
	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-beta/css/bootstrap.min.css" integrity="sha384-/Y6pD6FV/Vv2HJnA6t+vslU6fwYXjCFtcEpHbNJ0lyAFsXTsjBbfaDjzALeQsN6M" crossorigin="anonymous">
	<link rel="stylesheet" href="css/magmi.css"></link>
	<script src="https://code.jquery.com/jquery-3.2.1.slim.min.js" integrity="sha384-KJ3o2DKtIkvYIK3UENzmM7KCkRr/rE9/Qpg6aAZGJwFDMVNA/GpGFF93hXpG5KkN" crossorigin="anonymous"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.11.0/umd/popper.min.js" integrity="sha384-b/U6ypiBEHpOf/4+1nzFpr53nxSS+GLCkfwBdFNTxtclqqenISfwAzpKaMNFNmj4" crossorigin="anonymous"></script>
	<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-beta/js/bootstrap.min.js" integrity="sha384-h0AbiXch4ZDo7tp9hKZ4TsHbi047NrKGLO3SEJAg45jXxnGIfYzk4Si90RDIqNm1" crossorigin="anonymous"></script>

	<script type="text/javascript" src="js/init.js"></script>
	<script type="text/javascript" src="js/prototype.js"></script>
	<script type="text/javascript" src="js/ScrollBox.js"></script>
	<script type="text/javascript" src="js/magmi_utils.js"></script>
	<META HTTP-EQUIV="Pragma" CONTENT="no-cache">
	<META HTTP-EQUIV="Cache-control" CONTENT="no-cache">
	<META HTTP-EQUIV="Expires" CONTENT="-1">
</head>
<body class="container-fluid">