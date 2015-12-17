
<div class="plugin_description">This utility enable remapping of select
	values</div>


<div>

	<ul class="formline">
		<li class="label">attribute code to remap (must be select):</li>
		<li class="value"><input type="text" name="SREMAP:attrcode"
			id="SREMAP:attrcode"
			value="<?php echo $this->getParam("SREMAP:attrcode")?>"></li>
	</ul>

	<ul class="formline">
		<li class="label">CSVs base directory</li>
		<li class="value"><input type="text" name="CSV:basedir"
			id="CSV:basedir"
			value="<?php echo $this->getParam("CSV:basedir", "var/import")?>">
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
		value="<?php echo $this->getParam("CSV:separator")?>"><span
		class="">CSV Enclosure:</span><input type="text" maxlength="3"
		size="3" name="CSV:enclosure"
		value='<?php echo $this->getParam("CSV:enclosure")?>'>
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
