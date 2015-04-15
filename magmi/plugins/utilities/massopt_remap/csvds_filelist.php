<?php
if(!session_id()) {
    session_start();
}
$files = $this->getCSVList();
$token=$_SESSION["token"];
$cfname=$this->getParam("CSV:filename");
if($cfname==null)
{
    $cfname=$files[0];
}
if ($files !== false && count($files) > 0)
{
    ?>
<select name="CSV:filename" id="csvfile">
	<?php foreach($files as $fname){ ?>	
		<option <?php if($fname==$cfname){?>
		selected=selected <?php }?> value="<?php echo $cfname?>"><?php echo basename($fname)?></option>
	<?php }?>
</select>
<a id='csvdl'
	href="./download_file.php?file=<?php echo $cfname?>&token=<?php echo $token?>">Download
	CSV</a>
<script type="text/javascript">
 $('csvdl').observe('click',function(el){
	    var fval=$('csvfile').value;
 		$('csvdl').href="./download_file.php?file="+fval+"&token=<?php echo $token?>";}
	);
</script><?php } else {?>
<span> No csv files found in <?php echo $this->getScanDir(false)?></span>
<?php }?>