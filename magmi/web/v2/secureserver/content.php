
<?php if ($_SESSION['IS_SECURE']==0) {
    require_once("security_setup.php");
    $inf=getWebServerType();
    $helper=getWebServerHelper();

    ?>
    <h2>Securing Magmi Access for <?php echo ucfirst($inf['Server']).' '.$inf['Version']?> server</h2>
   <?php if ($helper!=null) {
    ?>
    <p> Magmi will now proceed to magmi interface security setup.</p>
        <?php echo $helper->getWebUI()?>
  <?php 
} else {
    ?>
        <div class="bs-callout bs-callout-danger">
            <p>Magmi has no automatic procedure to secure its access with this Web Server</p>
            <p>Please contact your administrator or proceed manually </p>
                </div>
  <?php 
}
} else {
    ?>
    <div class="bs-callout bs-callout-success">
            <h4>Magmi Access is now secured</h4>
            <p>You may now use <a href="<?php echo BASE_URL?>/index.php">new magmi web interface</a></p>

        </div>
<?php 
}?>