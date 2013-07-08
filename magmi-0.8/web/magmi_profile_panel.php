<?php 
$engclass=getWebParam("engineclass");
$profilename=($profile!="default"?$profile:"Default");
require_once("magmi_pluginhelper.php");
$ph=Magmi_PluginHelper::getInstance($profile);
$ph->setEngineClass($engclass);
?>
<script type="text/javascript" src="js/magmi_panelutils.js"></script>
<script type="text/javascript">
		var profile="<?php echo $profile?>";
	</script>
<script type="text/javascript">

</script>
<div class="container_12" id="profile_action">
<div class="grid_12 subtitle"><span>Configure Current Profile (<?php echo $profilename?>)</span>
<?php 
$eplconf=new EnabledPlugins_Config($ph->getEngine()->getProfilesDir(),$profile);
$eplconf->load();
$conf_ok=$eplconf->hasSection("PLUGINS_DATASOURCES");
?>
<span class="saveinfo<?php if(!$conf_ok){?> log_warning<?php }?>" id="profileconf_msg">
<?php if($conf_ok){?>
Saved:<?php echo $eplconf->getLastSaved("%c")?>
<?php }
else{?>
<?php echo $profilename?> Profile Config not saved yet
<?php 
}?>
</span>
</div>
<div class="grid_12 col">
	<form action="magmi_chooseprofile.php" method="POST" id="chooseprofile" >
	<input type="hidden" name="engineclass" value="<?php echo $engclass?>"/>
	<input type="hidden" name="PHPSESSID" value="<?php echo session_id()?>"/>
	<h3>Profile to configure</h3>
	<ul class="formline">
		<li class="label">Current Magmi Profile:</li>
		<li class="value">	
			
			<select name="profile" id="cp_profile">
			<option <?php if(null==$profile){?>selected="selected"<?php }?> value="default">Default</option>
			<?php foreach($profilelist as $profname){?>
			<option <?php if($profname==$profile){?>selected="selected"<?php }?> value="<?php echo $profname?>"><?php echo $profname?></option>
			<?php }?>
			</select>
		</li>
	</ul>
	<ul class="formline">
		<li class="label">Copy Selected Profile to:</li>
		<li class="value"><input type="text" name="newprofile"></input></li>
	
	</ul>
	<input id="cp_copyswitch" type="submit" value="Copy Profile &amp; switch"></input>
	<?php
	require_once("magmi_pluginhelper.php");
	$order=array("datasources","general","itemprocessors");
	
	$plugins=$ph->getEnginePluginClasses();
	$pcats=array();
	foreach($plugins as $k=>$pclasslist)
	{
		foreach($pclasslist as $pclass)
		{
			//invoke static method, using call_user_func (5.2 compat mode)
			$pcat=call_user_func(array($pclass,"getCategory"));
			if(!isset($pcats[$pcat]))
			{
				$pcats[$pcat]=array();
			}
			$pcats[$pcat][]=$pclass;
		}
	}
?>
</form>
</div>
</div>

<script type="text/javascript">
 $('#cp_profile').change(function(){
	 $('#cp_copyswitch').trigger('click');
	 });
</script>
<div class="container_12" id="profile_cfg">
<form action="" method="POST" id="saveprofile_form">
	<input type="hidden" name="engine" id="engine" value="<?php echo $engclass?>">
	<input type="hidden" name="profile" id="curprofile" value="<?php echo $profile?>">
	<input type="hidden" name="PHPSESSID" value="<?php echo session_id()?>">
	<?php foreach($order as $k)
	{?>
	<input type="hidden" id="plc_<?php echo strtoupper($k)?>" value="<?php echo implode(",",$eplconf->getEnabledPluginClasses($k))?>" name="PLUGINS_<?php echo strtoupper($k)?>:classes"></input>
	<div class="grid_12 col <?php if($k==$order[count($order)-1]){?>omega<?php }?>">
		<h3><?php echo ucfirst($k)?></h3>
		<?php if($k=="datasources")
		{?>
			<?php $pinf=$plugins[$k];?>
			<?php if(count($pinf)>0){?>
			<div class="pluginselect" style="float:left">
			
			<select name="PLUGINS_DATASOURCES:class" class="pl_<?php echo $k?>">
			
			
			<?php 
			$sinst=null;
			Magmi_PluginHelper::getInstance($profile)->setEngineClass($engclass);
			foreach($pinf as $pclass)
			{
				$pinst=Magmi_PluginHelper::getInstance($profile)->createInstance($k,$pclass);
				if($sinst==null)
				{
					
					$sinst=$pinst;
				}
				$pinfo=$pinst->getPluginInfo();
				if($eplconf->isPluginEnabled($k,$pclass))
				{
					$sinst=$pinst;
				}
			?>
				<option value="<?php echo $pclass?>"<?php  if($sinst==$pinst){?>selected="selected"<?php }?>><?php echo $pinfo["name"]." v".$pinfo["version"]?></option>
			<?php }
			?>
			
			</select>
			</div>
			<?php if(isset($pinfo["url"])){?>
			<div class="plugindoc" >
			<a href="<?php echo $pinfo["url"]?>" target="magmi_doc">documentation</a>
			</div>
			<?php }?>
			<div class="pluginconfpanel selected">
			<?php echo $sinst->getOptionsPanel()->getHtml();?>
			</div>
			<?php }else{
						$conf_ok=0;
				
				?>
			Magmi needs a datasource plugin, please install one
			<?php }?>
			<?php 
		}
		else
		{
			foreach($pcats as $pcat=>$pclasslist) {?>
								
				<?php 
				$catopen=false;
				$pinf=$plugins[$k];?>
		
				<?php foreach($pinf as $pclass)	{
					if(!in_array($pclass,$pclasslist))
					{
						continue;
					}
					else
					{?>
						<?php if(!$catopen){$catopen=true?>
						<div class="grid_12 omega"><h1><?php echo $pcat?></h1>
						<?php }?>
						<ul>
						<?php
								$pinst=Magmi_PluginHelper::getInstance($profile)->createInstance($k,$pclass);
							$pinfo=$pinst->getPluginInfo();
			  				$info=$pinst->getShortDescription();
			  				$plrunnable=$pinst->isRunnable();
							$enabled=$eplconf->isPluginEnabled($k,$pclass)
			  				?>
						<li>
							<div class="pluginselect">
							<?php if($plrunnable[0]){?>
								<input type="checkbox" class="pl_<?php echo $k?>" name="<?php echo $pclass?>" <?php if($eplconf->isPluginEnabled($k,$pclass)){?>checked="checked"<?php }?>>
							<?php } else {?>
								<input type="checkbox" class="pl_<?php echo $k?>" name="<?php echo $pclass?>" disabled="disabled">
							<?php }?>	
							<span class="pluginname"><?php echo $pinfo["name"]." v".$pinfo["version"];?></span>
							</div>	
							<div class="plugininfo">
							<?php if($info!==null){?>
								<span>info</span>
								<div class="plugininfohover">
								<?php echo $info?>
								<?php if(!$plrunnable[0]){?>
									<div class="error">
										<pre><?php echo $plrunnable[1]?></pre>
									</div>
								<?php }?>
								</div>
							<?php }?>
							</div>
							<div class="pluginconf"  <?php if(!$enabled){?>style="display:none"<?php }?>>
							<span><a href="javascript:void(0)">configure</a></span>
							</div>
							<?php if(isset($pinfo["url"])){?>
							<div class="plugindoc">
							<a href="<?php echo $pinfo["url"]?>" target="magmi_doc">documentation</a>
							</div>
							<?php }?>
	
							<div class="pluginconfpanel">
							<?php if($enabled){echo $pinst->getOptionsPanel()->getHtml();}?>
							</div>
					</li>
				</ul>
			<?php }?>
		<?php }?>	
		<?php if($catopen){?></div><?php }?>
		<?php }}?>
	</div>
	<?php }?>
</form>
<div class="grid_12">
<div style="float:right">
	<a id="saveprofile" class="actionbutton" href="javascript:void(0)" <?php if(!$conf_ok){?>disabled="disabled"<?php }?>>Save Profile (<?php echo $profilename?>)</a>
</div>
</div>
</div>

<div id="paramchanged" style="display:none">
		<div class="subtitle"><h3>Parameters changed</h3></div>

	<div class="changedesc"><b>You changed parameters without saving profile , would you like to:</b></div>
	
	<ul>
	<li>
		<input type="radio" name="paramcr" value="saveprof">Save chosen Profile (<?php echo $profilename ?>) with current parameters
	</li>
	<li>
		<input type="radio" name="paramcr" value="applyp" checked="checked">Apply current parameters as profile override without saving
	</li>
	<li>
		<input type="radio" name="paramcr" value="useold">Discard changes &amp; apply last saved <?php echo $profilename ?> profile values
	</li>
	</ul>
	<div class="actionbuttons">
	<a class="actionbutton" href="javascript:handleRunChoice('paramcr',comparelastsaved());" id="paramchangeok">Run with selected option</a>
	<a class="actionbutton" href="javascript:cancelimport();" id="paramchangecancel">Cancel Run</a>
	</div>
</div>

<div id="pluginschanged" style="display:none">
	<div class="subtitle"><h3>Plugin selection changed</h3></div>
	<div class="changedesc"><b>You changed selected plugins without saving profile , would you like to:</b></div>
	
	<ul>
	<li>
		<input type="radio" name="plugselcr" value="saveprof" checked="checked">Save chosen Profile (<?php echo $profilename ?>) with current parameters
	</li>
	<li>
		<input type="radio" name="plugselcr" value="useold">Discard changes &amp; apply  last saved <?php echo $profilename ?> profile values
	</li>
	</ul>
	<div class="actionbuttons">
	<a class="actionbutton" href="javascript:handleRunChoice('plugselcr',comparelastsaved());" id="plchangeok">Run with selected option</a>
	<a class="actionbutton" href="javascript:cancelimport();" id="plchangecancel">Cancel Run</a>
	</div>
</div>

<script type="text/javascript">

window.lastsaved={};


initAjaxConf('<?php echo $profile?>','<?php echo $engclass?>');
initDefaultPanels();


$('#saveprofile').click(function()
								{
									saveProfile(<?php echo $conf_ok?1:0 ?>,function(){$('#chooseprofile').submit();});
									});	

$('#runmagmi').submit(function(ev){

	var ls=comparelastsaved();
	if(ls.changed!==false)
	{
		 $('overlaycontent').update($(ls.target));
		 $$('#overlaycontent > div').each(function(el){el.show()});
		 $('overlay').show();			
		 ev.stop();
	}
	});
	</script>
