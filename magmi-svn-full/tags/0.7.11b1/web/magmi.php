<?php require_once("header.php");
	require_once("magmi_config.php");
	require_once("magmi_statemanager.php");
	
	require_once("fshelper.php");
	require_once("magmi_web_utils.php");
	$badrights=array();
	foreach(array("../state","../conf","../plugins") as $dirname)
	{
		if(!FSHelper::isDirWritable($dirname))
		{
			$badrights[]=$dirname;
		}
	}
	if(count($badrights)==0)
	{
		$state=Magmi_StateManager::getState();
		
		if($state=="running" || (isset($_REQUEST["run"]) && $_REQUEST["run"]=="import"))
		{
			require_once("magmi_import_run.php");		
		}
		else
		{
			Magmi_StateManager::setState("idle",true);
			require_once("magmi_config_setup.php");		
			require_once("magmi_profile_config.php");		
		}		
		
	}
	else
	{
		?>
	
	<div class="container_12" >
	<div class="grid_12">
		<div class="magmi_error" style="margin-top:5px">
		Directory permissions not compatible with Mass Importer operations
		<ul>
		<?php foreach($badrights as $dirname){
			$trname=str_replace("..","magmi",$dirname);
			?>
			<li><?php echo $trname?> not writable!</li>
		<?php }?>
		</ul>
		</div>
	</div>
	</div>
		<?php 
	}
?>
<?php require_once("footer.php");?>
