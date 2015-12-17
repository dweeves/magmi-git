<?php
/*
 * Prints HTML for a checkbox option to the output.
 * expects:
 *  $prefix (string): the prefix for options
 *  $name (string): the option name
 */
    $fullName = "$prefix:$name";
    $currentValue = $self->getParam($fullName, $default)=="on"?"on":"off";
?>
<div class="" id="<?php echo $fullName?>">
	<input type="checkbox" name="<?php echo $fullName?>_cb" id="<?php echo $fullName?>_cb"
		<?php if ($self->getParam($fullName, $default)=="on") {
    ?>
		checked="checked" <?php 
}?>> <?php echo $description?>
	<input type="hidden" id="<?php echo $fullName ?>_hf" name="<?php echo $fullName?>" value="<?php echo $currentValue ?>"/>
	<script type="text/javascript">
		$('<?php echo $fullName?>_cb').observe('click',function(){
			if($('<?php echo $fullName ?>_cb').checked) {
				$('<?php echo $fullName ?>_hf').value = 'on';
			} else {
				$('<?php echo $fullName ?>_hf').value = 'off';
			}
		});
	</script>
</div>
