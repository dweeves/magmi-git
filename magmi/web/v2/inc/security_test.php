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
    <?php }
    else {
        $dname=dirname($_SERVER['SCRIPT_FILENAME']);
       require_once($dname.'/content.php');
    }?>
