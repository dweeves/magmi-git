<select name="CSV:filename" id="csvfile">
	<?php 
	$files=$this->getCSVList();
	?>
	<?php foreach($files as $fname){ ?>	
		<option <?php if($fname==$this->getParam("CSV:filename")){?>selected=selected<?php }?>><?php echo $fname?></option>
	<?php }?>
</select>
<a id='csvdl' href="./download_file.php?file=<?php echo $this->getParam("CSV:filename")?>">Download CSV</a>
<script type="text/javascript">
 $('csvfile').observe('change',function(el){
 		$('csvdl').href="./download_file.php?file="+$('csvfile').value;}
	);
</script>