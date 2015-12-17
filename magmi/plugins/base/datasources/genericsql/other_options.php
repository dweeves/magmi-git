<div id="other_config">
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
		<li class="label">PDO Connection String</li>
		<li class="value"><input type="text" size="80" name="SQL:pdostr"
			value="<?php echo $this->getParam("SQL:pdostr", "")?>" />
			<div class="fieldinfo">you must have correct PDO driver installed
				&amp; also know PDO connection string syntax</div></li>
	</ul>
</div>
