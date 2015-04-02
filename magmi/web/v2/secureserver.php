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

    require_once("security_setup.php");
    $inf=getWebServerType();
    $helper=getWebServerHelper();

?>
    <h2>Securing Magmi Access for <?php echo ucfirst($inf['Server']).' '.$inf['Version']?> server</h2>
   <?php if($helper!=null){?>
    <p> Magmi will now proceed to magmi interface security setup.</p>
        <?php echo $helper->getWebUI()?>
  <?php }
    else {?>
        <div class="bs-callout bs-callout-danger">
            <p>Magmi has no automatic procedure to secure its access with this Web Server</p>
            <p>Please contact your administrator or proceed manually </p>
                </div>
  <?php  }
}else
{?>
    <div class="bs-callout bs-callout-success">
            <h4>Magmi Access is now secured</h4>
            <p>You may now use <a href="index.php">new magmi web interface</a></p>

        </div>
<?php }?>
</div>
</body>