<?php
$files = $this->getCSVList();
if ($files !== false && count($files) > 0)
{
    ?>
<select name="CSV:filename" id="csvfile">
	<?php foreach($files as $fname){ ?>	
		<option
		<?php if($fname==$this->getAbsPath($this->getParam("CSV:filename"))){?>
		selected=selected <?php }?> value="<?php echo $fname?>"><?php echo basename($fname)?></option>
	<?php }?>
</select>
<a id='csvdl'
	href="./download_file.php?file=<?php echo $this->getAbsPath($this->getParam("CSV:filename"))?>">Download
	CSV</a>
<script type="text/javascript">
 $('csvdl').observe('click',function(el){
	    var fval=$('csvfile').value;
 		$('csvdl').href="./download_file.php?file="+fval;}
	);
</script><?php } else {?>
<span> No csv files found in <?php echo $this->getScanDir(false)?></span>
<?php }?>