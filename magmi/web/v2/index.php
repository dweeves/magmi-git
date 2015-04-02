<?php require_once('session.php')?>
<?php require_once('security.php')?>
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
require_once("message.php");
require_once("utils.php");
?>
</head>
<body>
<div class="header">
<?php require_once("header.php");?>
</div>
<div id="messages">
<?php show_messages("_global");?>
</div>
<div class="container" id="main_content">
<?php if($_SESSION['IS_SECURE']==0){
    require_once('security_setup.php');
    $info=getWebServerType();
    ?>

    <div class="bs-callout bs-callout-danger">
        <h4>Magmi Access is unsecured</h4>

        <p>Magmi is a powerful tool, directly accessing database of your Magento Installation</p>
        <p>It seems your <b><?php echo ucfirst($info['Server'])?></b> Web Server security is not properly configured
        and let anybody acces magmi web interface</p>
        <p>Please <a href="secureserver.php">secure your access</a> and you'll be able to use the new magmi interface.</p>

    </div>
    <?php }?>
</div>
</body>
