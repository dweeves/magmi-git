<?php 
require_once("magmi_pluginhelper.php");
if(!isset($profile))
{
	$profile=getWebParam("profile");
}
$engclass=getWebParam("engineclass");
$ph=Magmi_PluginHelper::getInstance($profile);
$ph->setEngineClass($engclass);

$eplconf=new EnabledPlugins_Config($ph->getEngine()->getProfilesDir(),$profile);
$eplconf->load();
if(!$eplconf->hasSection("PLUGINS_DATASOURCES"))
{
	$conf_ok=0;
}
?>

<div class="container_12" >
<div class="grid_12 subtitle"><span>Run Magmi</span>
<?php if(!$conf_ok){?>
<span class="saveinfo log_warning"><b>No Profile saved yet, Run disabled!!</b></span>
<?php }?>
</div>
</div>
<form method="POST" id="runmagmi" action="magmi.php" <?php if(!$conf_ok){?>style="display:none"<?php }?>>
	<input type="hidden" name="run" value="import"></input>
	<input type="hidden" name="logfile" value="<?php echo Magmi_StateManager::getProgressFile()?>"></input>
	<input type="hidden" name="engineclass" value="<?php echo $engclass?>"></input>
	<div class="container_12">
		<div class="grid_12 col" id="directrun">	
			<h3>Directly run magmi with existing profile</h3>
			<div class="formline">
				<span class="label">Run Magmi With Profile:</span>
				<?php $profilelist=$ph->getEngine()->getProfileList(); ?>
				<select name="profile" id="runprofile">
					<option <?php if(null==$profile){?>selected="selected"<?php }?> value="default">Default</option>
					<?php foreach($profilelist as $profilename){?>
					<option <?php if($profilename==$profile){?>selected="selected"<?php }?> value="<?php echo $profilename?>"><?php echo $profilename?></option>
					<?php }?>
				</select>
			<span>using mode:</span>
				<select name="mode" id="mode">
					<option value="update">Update existing items only,skip new ones</option>
					<option value="create">create new items &amp; update existing ones</option>
					<option value="xcreate">create new items only, skip existing ones</option>

				</select>
			<input type="submit" value="Run Import" <?php if(!$conf_ok){?>disabled="disabled"<?php }?>></input>
			</div>
		</div>
		</div>
</form>
<div class="container_12">
<div class="grid_12">
<a href="magmi_utilities.php">Advanced Utilities</a>
</div>
</div>

