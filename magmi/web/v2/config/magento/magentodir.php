<?php
/**
 * Created by PhpStorm.
 * User: seb
 * Date: 30/03/15
 * Time: 18:05
 */
if(!isset($_SESSION)) {
    require_once("../../inc/basedefs.php");
}

$conf=getSessionConfig();
$errs=hasMessages("ERROR","magentodir");
?>

<h2>Magento Directory</h2>
    <div id="magdir_msg"><?php show_messages("magentodir")?></div>
    <div class="input-group">
    <span class="input-group-addon" id="magentodirlabel">Magento Directory</span>
    <input type="text" id="magentodir" name="magentodir" class="form-control" placeholder="enter where magento directory is located" aria-describedby="magentodirlabel" value="<?php echo $conf->getMagentoDir()?>">
</div>
<?php if(!$errs)
{?>
<div id="magentoinfo">
     <?php if($conf->get('MAGENTO','basedir')) {
            require_once('magentoinfo.php');
        }
        ?>
</div>
<?php }?>
<script type="text/javascript">
    $(document).ready(function() {
        $('#magentodir').focus(function(){
        $('#magentodir').blur(function () {
            $.post('magento/check_magento_dir.ajax.php', {'magentodir': $('#magentodir').val()}, function (data) {
                $('#magentodir_container').load('magento/magentodir.php');
                $('#magentodir').blur(function(){});
            });
        });});
    });
</script>
