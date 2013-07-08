<select name="CSV:filename">
	<?php 
	$files=$this->getCSVList();
	?>
	<?php foreach($files as $fname){ ?>	
		<option <?php if($fname==$this->getParam("CSV:filename")){?>selected=selected<?php }?>><?php echo $fname?></option>
	<?php }?>
</select>
<a href="<?php echo $this->getParam("CSV:filename")?>">Download CSV</a>