
<div class="plugin_description">
	This plugin enables magmi import from csv files (using Dataflow format
	+ magmi extended columns)<br /> <b>NOT Magento 1.5 new importexport
		format!!</b>
</div>
<div>

	<div class="csvmode"></div>

		<label for="CSV:importmode">CSV import mode</label>
		<select name="CSV:importmode" id="CSV:importmode" class="form-control">
				<option value="local"
					<?php if ($this->getParam("CSV:importmode", "local") == "local") {
    ?>
					selected="selected" <?php
}?>>Local</option>
				<option value="remote"
					<?php if ($this->getParam("CSV:importmode", "local") == "remote") {
        ?>
					selected="selected" <?php
    }?>>Remote</option>
		</select>

	<div id="localcsv"
		<?php if ($this->getParam("CSV:importmode", "local") == "remote") {
        ?>
		style="display: none" <?php
    }?>>

		<label for="CSV:basedir">CSVs base directory</label>
		<input type="text" name="CSV:basedir"
			id="CSV:basedir" class="form-control"
			value="<?php echo $this->getParam("CSV:basedir", "var/import")?>">
			<p class="fieldinfo">Relative paths are relative to magento base directory , absolute paths will be used as is.</p>

		<div class="value" id="csvds_filelist">
			<span for="CSV:basedir">File to import:</span>
		 	<?php echo $this->getOptionsPanel("csvds_filelist.php")->getHtml(); ?>
		 </div>
	</div>

	<div id="remotecsv"
		<?php if ($this->getParam("CSV:importmode", "local") == "local") {
        ?>
		style="display: none" <?php
    }?>>

			<label for="CSV:remoteurl">Remote CSV url</label>
			<input type="text" name="CSV:remoteurl" id="CSV:remoteurl" class="form-control" value="<?php echo $this->getParam("CSV:remoteurl", "")?>">

			<label class="form-check-label">
				<input type="checkbox" id="CSV:forcedl" class="form-check-input" name="CSV:forcedl" <?php if ($this->getParam("CSV:forcedl", false) == true) {?> checked="checked" <?php }?>>Force Download
			</label>

		<div id="remotecookie">
			<label for="CSV:remotecookie">HTTP Cookie</label>
			<input type="text" name="CSV:remotecookie" id="CSV:remotecookie" class="form-control" value="<?php echo $this->getParam("CSV:remotecookie", "")?>">
		</div>

		<label class="form-check-label">
			<input type="checkbox" id="CSV:remoteauth" class="form-check-input" name="CSV:remoteauth"
			<?php if ($this->getParam("CSV:remoteauth", false) == true) { ?> checked="checked" <?php }?>>Authentication needed
 		</label>
		<div id="remoteauth"
			<?php  if ($this->getParam("CSV:remoteauth", false) == false) {
        ?>
			style="display: none" <?php
    }?>>
			<div class="remoteuserpass">
				<ul class="formline">
					<li class="label">User</li>
					<li class="value"><input type="text" name="CSV:remoteuser"
						id="CSV:remoteuser"
						value="<?php echo $this->getParam("CSV:remoteuser", "")?>"></li>

				</ul>
				<ul class="formline">
					<li class="label">Password</li>
					<li class="value"><input type="text" name="CSV:remotepass"
						id="CSV:remotepass"
						value="<?php echo $this->getParam("CSV:remotepass", "")?>"></li>
				</ul>
			</div>

		</div>

	</div>


</div>
<div>
	<h3>CSV options</h3>
	<span class="">CSV separator:</span><input type="text" maxlength="3"
		size="3" name="CSV:separator"
		value="<?php echo $this->getParam("CSV:separator")?>"><span
		class="">CSV Enclosure:</span><input type="text" maxlength="3"
		size="3" name="CSV:enclosure"
		value='<?php echo $this->getParam("CSV:enclosure")?>'>
</div>

<div class="">
	<input type="checkbox" name="CSV:noheader"
		<?php if ($this->getParam("CSV:noheader", false) == true) {
        ?>
		checked="checked" <?php
    }?>> Headerless CSV (Use Column Mapper Plugin
	to set processable column names)
</div>
<div class="">
	<input type="checkbox" name="CSV:allowtrunc"
		<?php if ($this->getParam("CSV:allowtrunc", false) == true) {
        ?>
		checked="checked" <?php
    }?>> Allow truncated lines (bypasses data line
	structure correlation with headers)
</div>

<?php

$hdline = $this->getParam("CSV:headerline", "");
$malformed = ($hdline != "" && $hdline != 1)?>
<input type="checkbox" id="malformedcb" <?php if ($malformed) {
    ?>
	checked="checked" <?php
}?> />
Malformed CSV (column list line not at top of file)
<div id="malformed" <?php if (!$malformed) {
        ?> style="display: none"
	<?php
    }?>>
	<span class="">CSV Header at line:</span><input type="text"
		id="CSV:headerline" name="CSV:headerline" maxlength="7" size="7"
		value="<?php echo $hdline?>">
</div>
<script type="text/javascript">
	handle_auth=function()
	{
		if($('CSV:remoteauth').checked)
		{
			$('remoteauth').show();
		}
		else
		{
			$('remoteauth').hide();
		}
	}

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
	$('CSV:importmode').observe('change',function()
			{
				if($F('CSV:importmode')=='local')
				{
					$('localcsv').show();
					$('remotecsv').hide();
				}
				else
				{
					$('localcsv').hide();
					$('remotecsv').show();
				}
			});
	$('CSV:remoteauth').observe('click',handle_auth);
	$('CSV:remoteurl').observe('blur',handle_auth);
</script>
