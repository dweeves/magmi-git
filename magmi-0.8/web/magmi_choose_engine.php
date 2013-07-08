<?php require_once("magmi_pluginhelper.php");
  $ph=Magmi_PluginHelper::getInstance();
  $engclass=getWebParam("engineclass","magmi_productimportengine::Magmi_ProductImportEngine");
  setEngineAndProfile($ph, $engclass, getWebParam("profile"));
  ?>
<div class="container_12" >
<div class="grid_12 subtitle"><span>Select Magmi Engine</span></div>
</div>
<?php $elist=$ph->getEngineList();
	  unset($elist['magmi_utilityengine::Magmi_UtilityEngine']);
?>
<div class="container_12">
		<div class="grid_12 col" id="chooseengine">
			<form id='magmi_ce_form' method='post' action='magmi.php'>
				<!--  <input type="hidden" name="PHPSESSID" value="<?php echo session_id()?>"> -->
				<select id="magmi_choose_engine" name="engineclass"">
				<?php foreach($elist as $k=>$inf){?>
					<option value="<?php echo $k?>" <?php if($k==$engclass){?>selected="selected"<?php }	?>><?php echo $inf["name"]."-".$inf["version"]?></option>
				<?php  }?>
				</select>
			</form>
		</div>
</div>
<script type="text/javascript">
	$('#magmi_choose_engine').change(function(){$('#magmi_ce_form').submit()});
</script>
