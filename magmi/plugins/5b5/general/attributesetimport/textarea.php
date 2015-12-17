<?php
    $fullName = "$prefix:$name";
?>
<div class="" id="<?php echo $fullName ?>">
	<span class=""><?php echo $description ?>:</span><textarea
		style="width: 900px;" rows=10 name="<?php echo $fullName ?>"><?php echo htmlentities($self->getParam($fullName, $default))?></textarea>
</div>
