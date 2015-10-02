<div id="mysql_config">
	<ul class="formline">
		<li class="label">Input DB Host</li>
		<li class="value"><input type="text" name="SQL:dbhost"
			value="<?php echo $this->getParam("SQL:dbhost", "localhost")?>" /></li>
	</ul>
	<ul class="formline">
		<li class="label">Input DB Name</li>
		<li class="value"><input type="text" name="SQL:dbname"
			value="<?php echo $this->getParam("SQL:dbname", "")?>" /></li>
	</ul>
	<ul class="formline">
		<li class="label">Input DB User</li>
		<li class="value"><input type="text" name="SQL:dbuser"
			value="<?php echo $this->getParam("SQL:dbuser", "")?>" /></li>
	</ul>
	<ul class="formline">
		<li class="label">Input DB Password</li>
		<li class="value"><input type="password" name="SQL:dbpass"
			value="<?php echo $this->getParam("SQL:dbpass", "")?>" /></li>
	</ul>
	<ul class="formline">
		<li class="label">Input DB Initial Statements (optional)</li>
		<li class="value"><textarea name="SQL:dbextra" cols="80" rows="5">
<?php echo $this->getParam("SQL:dbextra", "")?>
</textarea>
			<div class="fieldinfo">Put DB requests like SET NAMES if necessary
				separated by ;</div></li>
	</ul>
</div>
