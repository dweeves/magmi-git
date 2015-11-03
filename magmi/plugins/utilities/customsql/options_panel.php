
<ul class="formline">
	<li class="label">SQL file</li>
	<li class="value">
<?php
$dr = $this->getParam("UTCSQL:queryfile");
?>
<?php $sqlfiles=$this->getRequestFileList();?>

<?php

if (count($sqlfiles) > 0) {
    if (!isset($dr)) {
        $dr = $sqlfiles[0];
    }
    ?>
<select name="UTCSQL:queryfile" id="UTCSQL:queryfile">
	<?php foreach ($sqlfiles as $curfile):?>
	<option <?php if ($curfile==$dr) {
    ?> selected=selected <?php 
}
    ?>
				value="<?php echo $curfile?>"><?php echo $this->getRequestInfo($curfile)?></option>
	<?php endforeach?>
</select>
<?php 
} else {
    ?>
	<span class="error">No SQL files detected in <?php echo $this->getPluginDir()."/prequests"?></span>
<?php 
}?>
</li>
</ul>
<div id="fileoptions">
<?php

if (isset($dr)) {
    include("filevalues.php");
}
?>
</div>
<script type="text/javascript">
var ft=$('UTCSQL:queryfile');
ft.observe('change',function(ev)
		{
			new Ajax.Updater('fileoptions','ajax_pluginconf.php',{
				parameters:{file:'filevalues.php',
							plugintype:'utilities',
                            pluginclass:'<?php echo get_class($this->_plugin)?>',
						    profile:'<?php echo $this->getConfig()->getProfile()?>',
						    'UTCSQL:queryfile':$F('UTCSQL:queryfile')
						    }});
		}
);
</script>