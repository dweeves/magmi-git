<?php
$files = $self->getCSVList($prefix);
if ($files !== false && count($files) > 0) {
    ?>
<select name="<?php echo $prefix ?>:filename" id="<?php echo $prefix ?>:csvfile">
	<?php foreach ($files as $fname) {
    ?>
		<option <?php if ($fname==$self->getParam("$prefix:filename")) {
    ?>
		selected=selected <?php 
}
    ?> value="<?php echo $fname?>"><?php echo basename($fname)?></option>
	<?php 
}
    ?>
</select>
<a id='<?php echo $prefix ?>:csvdl'
	href="./download_file.php?file=<?php $self->getParam("$prefix:filename")?>">Download
	CSV</a>
<script type="text/javascript">
 $('<?php echo $prefix ?>:csvdl').observe('click',function(el){
	    var fval=$('<?php echo $prefix ?>:csvfile').value;
 		$('<?php echo $prefix ?>:csvdl').href="./download_file.php?file="+fval;}
	);
</script><?php 
} else {
    ?>
<span> No csv files found in <?php echo $self->getScanDir(false)?></span>
<?php 
}?>