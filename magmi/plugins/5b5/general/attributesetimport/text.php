<?php
    $fullName = "$prefix:$name";
?>
<div class="" id="<?php echo $fullName ?>">
	<span class=""><?php echo $description ?>:</span><input type="text"
		style="width: 900px;" name="<?php echo $fullName ?>"
		value="<?php echo htmlentities($self->getParam($fullName, $default))?>"></input>
</div>
