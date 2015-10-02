<?php
/**
 * Created by PhpStorm.
 * User: seb
 * Date: 02/04/15
 * Time: 16:28
 */
$mdir="";
if (isset($_REQUEST["magentodir"])) {
    $mdir=$_REQUEST["magentodir"];
    $this->setMagentoDir($mdir);
    $secure_result=$this->secureServer();
    foreach ($secure_result as $type=>$data) {
        for ($i=0;$i<count($data);$i++) {
            setMessage($type, $data[$i], "secureserver");
        }
    }
    show_messages("secureserver");
    if (hasMessages("ERROR", "secureserver")) {
        ?>

   <?php 
    } else {
        ?>

        <div class="bs-callout bs-callout-success">
            <h4>Security Setup complete</h4>
            <p>Magmi will now redirect to its main interface</p>
        </div>
        <script type="text/javascript">
            setTimeout(function(){
                window.location="<?php echo BASE_URL?>/index.php";
            },5000);
        </script>
        <?php 
    }
    ?>
<?php

} else {
    if (isset($result) && $result["ERROR"]) {
        setMessage("ERROR", $result["ERROR"], "magentodir");
    }
    ?>

<div class="bs-callout bs-callout-info">
           <h4>Requiring Magento directory</h4>
           <p>Magmi will secure magmi web interface with Magento Database Credentials stored in the local.xml file of Magento</p>
</div>
<?php    show_messages("magentodir");
    ?>


    <form name="magentodir_security" role="form" method="POST" class="form-inline">
<div class="container" id="magentodir_container">
<h2>Magento Directory</h2>
    <div id="magdir_msg"><?php show_messages("magentodir")?></div>
    <div class="input-group">
    <span class="input-group-addon" id="magentodirlabel">Magento Directory</span>
    <input type="text" id="magentodir" name="magentodir" class="form-control input-xlarge" placeholder="enter where magento directory is located" aria-describedby="magentodirlabel" value="<?php echo $mdir?>">
    </div>
    <input type="submit" value="Secure Interface" class="btn btn-outline">

</form>
<?php 
}?>
