<div>
	<ul class="formline">
		<li class="label">CSV import mode</li>
		<li class="value"><select name="<?php echo $prefix ?>:importmode" id="<?php echo $prefix ?>:importmode">
				<option value="local"
					<?php if ($self->getParam("$prefix:importmode", "local")=="local") {
    ?>
					selected="selected" <?php 
}?>>Local</option>
				<option value="remote"
					<?php if ($self->getParam("$prefix:importmode", "local")=="remote") {
    ?>
					selected="selected" <?php 
}?>>Remote</option>
		</select>

	</ul>

	<div id="<?php echo $prefix ?>:localcsv"
		<?php if ($self->getParam("$prefix:importmode", "local")=="remote") {
    ?>
		style="display: none" <?php 
}?>>
		<ul class="formline">
			<li class="label">CSVs base directory</li>
			<li class="value"><input type="text" name="<?php echo $prefix ?>:basedir"
				id="<?php echo $prefix ?>:basedir"
				value="<?php echo $self->getParam("$prefix:basedir", "var/import")?>"></input>
				<div class="fieldinfo">Relative paths are relative to magento base
					directory , absolute paths will be used as is</div></li>
		</ul>
		<ul class="formline">
			<li class="label">File to import:</li>
			<li class="value" id="<?php echo $prefix ?>:csvds_filelist">
 <?php require('csvds_filelist.php'); ?>
 </li>
		</ul>
	</div>

	<div id="<?php echo $prefix ?>:remotecsv"
		<?php if ($self->getParam("$prefix:importmode", "local")=="local") {
    ?>
		style="display: none" <?php 
}?>>
		<ul class="formline">
			<li class="label">Remote CSV url</li>
			<li class="value"><input type="text" name="<?php echo $prefix ?>:remoteurl"
				id="<?php echo $prefix ?>:remoteurl"
				value="<?php echo $self->getParam("$prefix:remoteurl", "")?>"
				style="width: 400px"></input> <input type="checkbox"
				id="<?php echo $prefix ?>:forcedl" name="<?php echo $prefix ?>:forcedl"
				<?php if ($self->getParam("$prefix:forcedl", false)==true) {
    ?>
				checked="checked" <?php 
}?>>Force Download</li>
		</ul>

		<div id="<?php echo $prefix ?>:remotecookie">
			<ul class="formline">
				<li class="label">HTTP Cookie</li>
				<li class="value"><input type="text" name="<?php echo $prefix ?>:remotecookie"
					id="<?php echo $prefix ?>:remotecookie"
					value="<?php echo $self->getParam("$prefix:remotecookie", "")?>"
					style="width: 400px"></li>
			</ul>
		</div>
		<input type="checkbox" id="<?php echo $prefix ?>:remoteauth" name="<?php echo $prefix ?>:remoteauth"
			<?php  if ($self->getParam("$prefix:remoteauth", false)==true) {
     ?>
			checked="checked" <?php 
 }?>>authentication needed
		<div id="<?php echo $prefix ?>:remoteauth"
			<?php  if ($self->getParam("$prefix:remoteauth", false)==false) {
     ?>
			style="display: none" <?php 
 }?>>
			<div class="remoteuserpass">
				<ul class="formline">
					<li class="label">User</li>
					<li class="value"><input type="text" name="<?php echo $prefix ?>:remoteuser"
						id="<?php echo $prefix ?>:remoteuser"
						value="<?php echo $self->getParam("$prefix:remoteuser", "")?>"></li>

				</ul>
				<ul class="formline">
					<li class="label">Password</li>
					<li class="value"><input type="text" name="<?php echo $prefix ?>:remotepass"
						id="<?php echo $prefix ?>:remotepass"
						value="<?php echo $self->getParam("$prefix:remotepass", "")?>"></li>
				</ul>
			</div>

		</div>

	</div>


</div>
<div>
	<h4>CSV options</h4>
	<span class="">CSV separator:</span><input type="text" maxlength="3"
		size="3" name="<?php echo $prefix ?>:separator"
		value="<?php echo $self->getParam("$prefix:separator")?>"></input> <span
		class="">CSV Enclosure:</span><input type="text" maxlength="3"
		size="3" name="<?php echo $prefix ?>:enclosure"
		value='<?php echo $self->getParam("$prefix:enclosure")?>'></input>
</div>

<div class="">
	<input type="checkbox" name="<?php echo $prefix ?>:allowtrunc"
		<?php if ($self->getParam("$prefix:allowtrunc", false)==true) {
    ?>
		checked="checked" <?php 
}?>> Allow truncated lines (bypasses data line
	structure correlation with headers)
</div>

<?php

$hdline = $self->getParam("<?php echo $prefix ?>:headerline", "");
$malformed = ($hdline != "" && $hdline != 1)?>
<input type="checkbox" id="<?php echo $prefix ?>:malformed_cb" <?php if ($malformed) {
    ?>
	checked="checked" <?php 
}?> />
Malformed CSV (column list line not at top of file)
<div id="<?php echo $prefix ?>:malformed" <?php if (!$malformed) {
    ?> style="display: none"
	<?php 
}?>>
	<span class="">CSV Header at line:</span><input type="text"
		id="<?php echo $prefix ?>:headerline" name="<?php echo $prefix ?>:headerline" maxlength="7" size="7"
		value="<?php echo $hdline?>"></input>
</div>
