
<div class="plugin_description">This utility enable mass
	creation/translation of select/multiselect attribute values</div>
<div>
	<ul class="formline">
		<li class="label">CSVs base directory</li>
		<li class="value"><input type="text" name="CSV:basedir"
			id="CSV:basedir"
			value="<?php echo $this->getParam("CSV:basedir","var/import")?>"></input>
			<div class="fieldinfo">Relative paths are relative to magento base
				directory , absolute paths will be used as is</div></li>
	</ul>
	<ul class="formline">
		<li class="label">File to import:</li>
		<li class="value" id="csvds_filelist">
<?php echo $this->getOptionsPanel("csvds_filelist.php")->getHtml(); ?>
</li>
	</ul>
</div>
<div>
	<span class="">CSV separator:</span><input type="text" maxlength="3"
		size="3" name="CSV:separator"
		value="<?php echo $this->getParam("CSV:separator")?>"></input> <span
		class="">CSV Enclosure:</span><input type="text" maxlength="3"
		size="3" name="CSV:enclosure"
		value='<?php echo $this->getParam("CSV:enclosure")?>'></input>
</div>

<script type="text/javascript">
	$('CSV:basedir').observe('blur',function()
			{
			new Ajax.Updater('csvds_filelist','ajax_pluginconf.php',{
			parameters:{file:'csvds_filelist.php',
						plugintype:'utilities',
					    pluginclass:'<?php echo get_class($this->_plugin)?>',
					    profile:'<?php echo $this->getConfig()->getProfile()?>',
					    'CSV:basedir':$F('CSV:basedir')}});
			});
</script>
