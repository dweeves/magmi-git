
<div class="container">
<?php
session_start();
require_once("../utils.php");
require_once("../message.php");
   $conf=getSessionConfig();
    $cf=$conf->getConfFile();
$cf=isset($_SESSION['MAGMI_CONFIG_FILE'])?$_SESSION['MAGMI_CONFIG_FILE']:'';
    require_once("magmi_choose_conf.php");
?>
<?php
   if ($conf->isDefault()) {
       ?>
       <div class="alert alert-danger">
           You have not configured any magmi base configuration yet, please fill the following fields & click the "save" button.
       </div>
    <?php

   }?>
<h2>Magento Directory</h2>
    <div id="magdir_msg"><?php show_messages("magentodir")?></div>
    <div class="input-group">
    <span class="input-group-addon" id="magentodirlabel">Magento Directory</span>
    <input type="text" id="magentodir" name="magentodir" class="form-control" placeholder="enter where magento directory is located" aria-describedby="magentodirlabel" value="<?php echo $conf->getMagentoDir()?>">
</div>
    <div id="magentoinfo">
        <?php if ($conf->get('MAGENTO', 'basedir')) {
    require_once('magentoinfo.php');
}
        ?>
    </div>
        <h2>Database Connectivity Configuration</h2>
<div class="btn-group" role="group" aria-label="...">
  <button type="button" class="btn btn-default">Automatic</button>
  <button type="button" class="btn btn-default">Host/Port</button>
  <button type="button" class="btn btn-default">Socket</button>
</div>
</div>
<script type="text/javascript">
    $(document).ready(function(){

        $('#magconf').blur(function() {
            $.post('baseconf/magmi_changeconf.ajax.php', {'magmiconf': $('#magconf').val()}, function (data) {
                $('#main_content').load('baseconf/content.php',function(){$('#chooseconf_msg').load("message.php",{'msgtarget':'magmiconf'})});
            });});

        $('#magentodir').blur(function(){
            $.post('baseconf/check_magento_dir.ajax.php',{'magentodir':$('#magentodir').val()},function(data){
                $('#magdir_msg').load('messageupdate.php',{'msgtarget':'magentodir'});
                $('#magentoinfo').load('baseconf/magentoinfo.php');
            });});

    });
</script>


