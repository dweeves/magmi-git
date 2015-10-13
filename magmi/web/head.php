<?php


function authenticate($username="",$password=""){ 
    require "../inc/magmi_auth.php";
    $auth = new Magmi_Auth($username,$password);
    
    return $auth->authenticate();
} 

if (!isset($_SERVER['PHP_AUTH_USER'])) { 
    header('WWW-Authenticate:Basic realm="Magmi"'); 
    header('HTTP/1.0 401 Unauthorized'); 
    echo 'You must be logged in to use Magmi'; 
    die(); 
} else { 
    if (!authenticate($_SERVER['PHP_AUTH_USER'],$_SERVER['PHP_AUTH_PW'])){ 
        header('WWW-Authenticate: Basic realm="Magmi"'); 
        header('HTTP/1.0 401 Unauthorized'); 
        echo 'You must be logged in to use Magmi'; 
        die(); 
    } 

} 
/***************** *********************/

set_include_path(get_include_path() . PATH_SEPARATOR . "../inc");
ini_set("display_errors", 1);
ini_set("error_reporting", E_ALL);
ini_set("magic_quotes_gpc", 0);
require_once("magmi_version.php");
session_start();
if (!isset($_SESSION["token"])) {
    $token = uniqid(mt_rand(), true);
    $_SESSION['token'] = $token;
}
?>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title>MAGMI (MAGento Mass Importer) by Dweeves - version <?php echo Magmi_Version::$version ?></title>
<link rel="stylesheet" href="css/960.css"></link>
<link rel="stylesheet" href="css/reset.css"></link>
<link rel="stylesheet" href="css/magmi.css"></link>
<script type="text/javascript" src="js/prototype.js"></script>
<script type="text/javascript" src="js/ScrollBox.js"></script>
<script type="text/javascript" src="js/magmi_utils.js"></script>
<META HTTP-EQUIV="Pragma" CONTENT="no-cache">
<META HTTP-EQUIV="Cache-control" CONTENT="no-cache">
<META HTTP-EQUIV="Expires" CONTENT="-1">
</head>
<body>
