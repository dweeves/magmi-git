<?php
/**
 * Created by PhpStorm.
 * User: seb
 * Date: 30/03/15
 * Time: 18:05
 */
require_once(dirname(dirname(__DIR__))."/utils.php");
require_once(dirname(dirname(__DIR__))."/message.php");
$conf=getSessionConfig();
require_once("check_magento_dir.ajax.php");
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
        $('#magentodir').blur(function () {
            $.post('config/magento/check_magento_dir.ajax.php', {'magentodir': $('#magentodir').val()}, function (data) {
                $('#magentodir_container').load('config/magento/magentodir.php');
            });
        });
    });
</script>
