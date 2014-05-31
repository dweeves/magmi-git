<div class="plugin_description">
	This plugin imports data from Generic SQL Backend<br /> You should put
	sql files in the <b><?php echo $this->getPluginDir()."/requests"?></b>
	directory
</div>
<ul class="formline">
<?php $dbtype=$this->getParam("SQL:dbtype");?>
<li class="label">Input DB Type</li>
	<li><select name="SQL:dbtype" id="SQL:dbtype">
			<option value="mysql" <?php if ($dbtype=="mysql"){?>
				selected="selected" <?php }?>>MySQL</option>
			<option value="other" <?php if ($dbtype=="other"){?>
				selected="selected" <?php }?>>Other</option>
	</select></li>
</ul>
<div id="options_container">
<?php echo $this->getOptionsPanel("$dbtype"."_options.php")->getHtml();?>
</div>
<ul class="formline">
	<li class="label">SQL file</li>
	<li class="value">
<?php $dr=$this->getParam("SQL:queryfile");?>
<?php $sqlfiles=$this->getSQLFileList();?>
<?php if(count($sqlfiles)>0){?>

<select name="SQL:queryfile">
	<?php foreach($sqlfiles as $curfile):?>
	<option <?php if($curfile==$dr){?> selected=selected <?php }?>
				value="<?php echo $curfile?>"><?php echo basename($curfile)?></option>
	<?php endforeach?>
</select>
<?php }else{?>
	<span class="error">No SQL files detected in <?php echo $this->getPluginDir()."/requests"?></span>
<?php }?>
</li>
</ul>
<script type="text/javascript">
var dbt=$('SQL:dbtype');
dbt.observe('change',function(ev)
		{
			new Ajax.Updater('options_container','ajax_pluginconf.php',{
				parameters:{file:$('SQL:dbtype').value+'_options.php',
							plugintype:'datasources',
						    pluginclass:'<?php echo get_class($this->_plugin)?>',
						    profile:'<?php echo $this->getConfig()->getProfile()?>',
						    }});
		}
);
</script>
