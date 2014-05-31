<div class="plugin_description">
	<p>This plugin can be used to send a email report for the import</p>
	<p>
		it uses PHP <b>mail</b> feature, <b>please ensure your setup is
			compatible with this</b>
	</p>
</div>
<ul class="formline">
	<li class="label">Email report to:</li>
	<li class="value"><input type="text" style="width: 400px"
		name="EMAILREP:to"
		value="<?php echo $this->getParam("EMAILREP:to","")?>">
		<div class="fieldinfo">You can set several receiver emails separated
			by a comma (,)</div></li>
</ul>
<ul class="formline">
	<li class="label">Report sender:</li>
	<li class="value"><input type="text" name="EMAILREP:from"
		value="<?php echo $this->getParam("EMAILREP:from","magmi@sourceforge.net")?>"></li>
</ul>
<ul class="formline">
	<li class="label">Report sender alias:</li>
	<li class="value"><input type="text" name="EMAILREP:from_alias"
		value="<?php echo $this->getParam("EMAILREP:from_alias","Magmi Importer")?>"></li>
</ul>
<ul class="formline">
	<li class="label">Subject:</li>
	<li class="value"><input type="text" name="EMAILREP:subject"
		value="<?php echo $this->getParam("EMAILREP:subject","Magmi import report")?>"></li>
</ul>
<ul class="formline">
	<li class="label">Body:</li>
	<li class="value"><textarea name="EMAILREP:body"><?php echo $this->getParam("EMAILREP:body","report attached");?></textarea></li>
</ul>
<ul class="formline">
	<li class="label">Attachments</li>
	<li class="value"><input type="checkbox" name="EMAILREP:attachlog"
		<?php if($this->getParam("EMAILREP:attachlog")==true){?>
		checked="checked" <?php }?>>Attach import log</li>
	<li class="value"><input type="checkbox" name="EMAILREP:attachcsv"
		<?php if($this->getParam("EMAILREP:attachcsv")==true){?>
		checked="checked" <?php }?>>Attach source CSV</li>
</ul>