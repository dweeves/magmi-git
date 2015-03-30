<!DOCTYPE html>
<html lang="en">
<head>
<?php
/**
 * Created by PhpStorm.
 * User: seb
 * Date: 26/03/15
 * Time: 17:16
 */
require_once("head.php");
require_once("message.php");?>
</head>
<body>
<div class="header">
<?php require_once("header.php");?>
</div>
<div id="messages">
<?php show_messages("_global");?>
</div>
<div class="container" id="main_content">
</div>
</body>
