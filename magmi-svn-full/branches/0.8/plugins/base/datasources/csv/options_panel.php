
<div class="plugin_description">
This plugin enables magmi import from csv files (using Dataflow format + magmi extended columns)<br/> <b>NOT Magento 1.5 new importexport format!!</b>
</div>
<div>

<div class="csvmode">
</div>

 <ul class="formline">
	 <li class="label">CSV import mode</li>
 	<li class="value">
 	<select name="CSV:importmode" id="CSV_importmode">
	 	<option value="local" <?php if($this->getParam("CSV:importmode","local")=="local"){?>selected="selected"<?php }?>>Local</option>
 		<option value="remote" <?php if($this->getParam("CSV:importmode","local")=="remote"){?>selected="selected"<?php }?>>Remote</option>
 	</select>
 </ul>

<div id="localcsv" <?php if($this->getParam("CSV:importmode","local")=="remote"){?> style="display:none"<?php }?>>
 <ul class="formline">
 <li class="label">CSVs base directory</li>
 <li class="value">
 <input type="text" name="CSV:basedir" id="CSV_basedir" value="<?php echo $this->getParam("CSV:basedir","var/import")?>"></input>
 <div class="fieldinfo">Relative paths are relative to magento base directory , absolute paths will be used as is</div></li>
 </ul>
 <ul class="formline">
 <li class="label" >File to import:</li>
 <li class="value" id="csvds_filelist">
 <?php echo $this->getOptionsPanel("csvds_filelist.php")->getHtml(); ?>
 </li>
 </ul>
</div>

<div id="remotecsv" <?php if($this->getParam("CSV:importmode","local")=="local"){?> style="display:none"<?php }?>>
 <ul class="formline">
 <li class="label">Remote CSV url</li>
 <li class="value">
 <input type="text" name="CSV:remoteurl" id="CSV_remoteurl" value="<?php echo $this->getParam("CSV:remoteurl","")?>" style="width:400px"></input>
 </li>
 </ul>
 <input type="checkbox" id="CSV_remoteauth" name="CSV:remoteauth" <?php  if($this->getParam("CSV:remoteauth",false)==true){?>checked="checked"<?php }?>>authentication needed
 <div id="remoteauth" <?php  if($this->getParam("CSV:remoteauth",false)==false){?>style="display:none"<?php }?>>
 
 <div class="remoteuserpass">
 	<ul class="formline">
 		<li class="label">User</li>
 		<li class="value"><input type="text" name="CSV:remoteuser" id="CSV_remoteuser" value="<?php echo $this->getParam("CSV:remoteuser","")?>"></li>
 		
 	</ul> 
 	<ul class="formline">
 		<li class="label">Password</li>
 		<li class="value"><input type="text" name="CSV:remotepass" id="CSV_remotepass" value="<?php echo $this->getParam("CSV:remotepass","")?>"></li>
 	</ul> 
 	</div>


</div>
</div>


</div>
<div>
<h3>CSV options</h3>
<span class="">CSV separator:</span><input type="text" maxlength="3" size="3" name="CSV:separator" value="<?php echo $this->getParam("CSV:separator")?>"></input>
<span class="">CSV Enclosure:</span><input type="text" maxlength="3" size="3" name="CSV:enclosure" value='<?php echo $this->getParam("CSV:enclosure")?>'></input>
</div>

<div class=""><input type="checkbox" name="CSV:noheader" <?php if($this->getParam("CSV:noheader",false)==true){?>checked="checked"<?php }?>>
Headerless CSV (Use Column Mapper Plugin to set processable column names)</div>
<div class=""><input type="checkbox" name="CSV:allowtrunc" <?php if($this->getParam("CSV:allowtrunc",false)==true){?>checked="checked"<?php }?>>
Allow truncated lines (bypasses data line structure correlation with headers)</div>

<?php $hdline=$this->getParam("CSV:headerline","");
$malformed=($hdline!="" && $hdline!=1)?>
<input type="checkbox" id="malformedcb" <?php if($malformed){?>checked="checked"<?php }?>/>Malformed CSV (column list line not at top of file)
<div id="malformed" <?php if(!$malformed){?>style="display:none"<?php }?>>
<span class="">CSV Header at line:</span><input type="text" id="CSV_headerline" name="CSV:headerline"  maxlength="7" size="7" value="<?php echo $hdline?>"></input>
</div>
<script type="text/javascript">
	handle_auth=function()
	{
		if($('#CSV_remoteauth').attr('checked'))
		{
			$('#remoteauth').show();	
		}
		else
		{
			$('#remoteauth').hide();
		}
	};

	$('#CSV_basedir').focusout(function()
	{
		loaddiv($('#csvds_filelist'),'ajax_pluginconf.php',
												decodeURIComponent($.param({file:'csvds_filelist.php',
																plugintype:'datasources',
				    pluginclass:'<?php echo get_class($this->_plugin)?>',
				    profile:'<?php echo $this->getConfig()->getProfile()?>',
				    engineclass:'<?php echo Magmi_PluginHelper::getInstance($this->getConfig()->getProfile())->getEngineClass()?>',
				    'CSV:basedir':$('#CSV_basedir').val()})));
	});
				
	$('#malformedcb').click(function(){
		if($('#malformedcb').attr('checked'))
		{
			$('#malformed').show();	
		}
		else
		{
			$('#malformed').hide();
		}
	});
	$('#CSV_headerline').blur(function()
	{
		var wellformed=($('#CSV:headerline').val()=="1" || $('#CSV:headerline').val=="");
		if(wellformed)
		{
			$('#malformedcb').attr('checked','false');
			$('#malformed').hide();
			$('#CSV:headerline').val("");
		}
	});
	$('#CSV_importmode').change(function()
			{
				if($('#CSV_importmode').val()=='local')
				{
					$('#localcsv').show();
					$('#remotecsv').hide();
				}
				else
				{
					$('#localcsv').hide();
					$('#remotecsv').show();
				}
			});
	$('#CSV_remoteauth').click(handle_auth);
	$('#CSV_remoteurl').blur(handle_auth);
	
</script>
