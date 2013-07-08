
<div class="plugin_description">
This plugin enables magmi import from csv files 
</div>
<div>
<ul class="formline">
<li class="label">CSVs base directory</li>
<li class="value">
<input type="text" name="CSV:basedir" id="CSV:basedir" value="<?php echo $this->getParam("CSV:basedir","var/import")?>"></input>
<div class="fieldinfo">Relative paths are relative to magento base directory , absolute paths will be used as is</div></li>
</ul>
<ul class="formline">
<li class="label" >File to import:</li>
<li class="value" id="csvds_filelist">
<?php echo $this->getOptionsPanel("csvds_filelist.php")->getHtml(); ?>
</li>
</ul>
</div>
<div>
<span class="">CSV separator:</span><input type="text" maxlength="3" size="3" name="CSV:separator" value="<?php echo $this->getParam("CSV:separator")?>"></input>
<span class="">CSV Enclosure:</span><input type="text" maxlength="3" size="3" name="CSV:enclosure" value='<?php echo $this->getParam("CSV:enclosure")?>'></input>
</div>
<?php $hdline=$this->getParam("CSV:headerline","");
$malformed=($hdline!="" && $hdline!=1)?>
<input type="checkbox" id="malformedcb" <?php if($malformed){?>checked="checked"<?php }?>>Malformed CSV (column list line not at top of file)</input>
<div id="malformed" <?php if(!$malformed){?>style="display:none"<?php }?>>
<span class="">CSV Header at line:</span><input type="text" id="CSV:headerline" name="CSV:headerline"  maxlength="7" size="7" value="<?php echo $hdline?>"></input>
</div>
<script type="text/javascript">
	$('CSV:basedir').observe('blur',function()
			{
			new Ajax.Updater('csvds_filelist','ajax_pluginconf.php',{
			parameters:{file:'csvds_filelist.php',
						plugintype:'datasources',
					    pluginclass:'<?php echo get_class($this->_plugin)?>',
					    profile:'<?php echo $this->getConfig()->getProfile()?>',
					    'CSV:basedir':$F('CSV:basedir')}});
			});
	$('malformedcb').observe('click',function(ev){
		if($('malformedcb').checked)
		{
			$('malformed').show();	
		}
		else
		{
			$('malformed').hide();
		}
	});
	$('CSV:headerline').observe('blur',function()
	{
		var wellformed=($F('CSV:headerline')=="1" || $F('CSV:headerline')=="");
		if(wellformed)
		{
			$('malformedcb').checked=false;
			$('malformed').hide();
			$('CSV:headerline').value="";
		}
	});
</script>
