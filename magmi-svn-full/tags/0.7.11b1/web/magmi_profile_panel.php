<?php 
if(isset($_REQUEST["profile"]))
{
	$profile=$_REQUEST["profile"];
}
else
{
	
	if(isset($_SESSION["last_runned_profile"]))
	{
		$profile=$_SESSION["last_runned_profile"];
	}
}
if($profile=="")
{
	$profile="default";
}
$profilename=($profile!="default"?$profile:"Default");
?>
<script type="text/javascript">
		var profile="<?php echo $profile?>";
	</script>
<script type="text/javascript">

</script>
<div class="container_12" id="profile_action">
<div class="grid_12 subtitle"><span>Configure Current Profile (<?php echo $profilename?>)</span>
<?php 
$eplconf=new EnabledPlugins_Config($profile);
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
	<h3>Profile to configure</h3>
	<ul class="formline">
		<li class="label">Current Magmi Profile:</li>
		<li class="value">	
			<select name="profile" onchange="$('chooseprofile').submit()">
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
	<input type="submit" value="Copy Profile &amp; switch"></input>
	<?php
	require_once("magmi_pluginhelper.php");
	$order=array("datasources","general","itemprocessors");
	$plugins=Magmi_PluginHelper::getInstance('main')->getPluginClasses($order);
	$pcats=array();
	foreach($plugins as $k=>$pclasslist)
	{
		foreach($pclasslist as $pclass)
		{
			$pcat=$pclass::getCategory();
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
<div class="container_12" id="profile_cfg">
<form action="" method="POST" id="saveprofile_form">
	<input type="hidden" name="profile" id="curprofile" value="<?php echo $profile?>">
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
<script type="text/javascript">
addclass=function(it,o)
{
	if(it.checked){
		this.arr.push(it.name);
	}
};

gatherclasses=function(tlist)
{
	tlist.each(function(t,o){
		var context={arr:[]};
		$$(".pl_"+t.toLowerCase()).each(addclass,context);
		var target=$("plc_"+t);
		target.value=context.arr.join(",");
	});
};

initConfigureLink=function(maincont)
{
 var cfgdiv=maincont.select('.pluginconf');
 if(cfgdiv.length>0)
 {
 	cfgdiv=cfgdiv[0];
 	var confpanel=maincont.select('.pluginconfpanel');
	 confpanel=confpanel[0]
	cfgdiv.stopObserving('click');
 	cfgdiv.observe('click',function(ev){
 	 	confpanel.toggleClassName('selected');
 		 confpanel.select('.ifield').each(function(it){
 			it.select('.fieldhelp').each(function(fh){
 				fh.observe('click',function(ev){
 					it.select('.fieldsyntax').each(function(el){el.toggle();})
 						});
 				});
 			});
 	 	});

 }
}
showConfLink=function(maincont)
{
	var cfgdiv=maincont.select('.pluginconf');
	if(cfgdiv.length>0)
	 {
	 
	cfgdiv=cfgdiv[0];
	cfgdiv.show();
	 }
	
}

loadConfigPanel=function(container,profile,plclass,pltype)
{
 new Ajax.Updater({success:container},'ajax_pluginconf.php',
	{parameters:{
		profile:profile,
		plugintype:pltype,
		pluginclass:plclass},
		evalScripts:true,
		onComplete:
	 	function(){
	 		showConfLink($(container.parentNode));
	 		initConfigureLink($(container.parentNode));
	 	}});
}
removeConfigPanel=function(container)
{
var cfgdiv=$(container.parentNode).select('.pluginconf');
cfgdiv=cfgdiv[0];
cfgdiv.stopObserving('click');
 cfgdiv.hide();
 container.removeClassName('selected');
 container.update('');
}


initAjaxConf=function(profile)
{
	//foreach plugin selection
	$$('.pluginselect').each(function(pls)
	{
		var del=pls.firstDescendant();
		var evname=(del.tagName=="SELECT"?'change':'click');
			
		//check the click
		del.observe(evname,function(ev)
		{
			var el=Event.element(ev);
			var plclass=(el.tagName=="SELECT")?el.value:el.name;
			var elclasses=el.classNames();
			var pltype="";
			elclasses.each(function(it){if(it.substr(0,3)=="pl_"){pltype=it.substr(3);}});
			var doload=(el.tagName=="SELECT")?true:el.checked;	
			var targets=$(pls.parentNode).select(".pluginconfpanel");
			var container=targets[0];
			if(doload)
			{
				loadConfigPanel(container,profile,plclass,pltype);
			}
			else
			{
				removeConfigPanel(container);
			}
		});
	});			
}
initDefaultPanels=function()
{
	$$('.pluginselect').each(function(it){initConfigureLink($(it.parentNode));});
}

initAjaxConf('<?php echo $profile?>');
initDefaultPanels();
$('saveprofile').observe('click',function()
		{
		gatherclasses(['GENERAL','ITEMPROCESSORS']);
	new Ajax.Updater('profileconf_msg',
			 "magmi_saveprofile.php",
			 {parameters:$('saveprofile_form').serialize('true'),
			  onSuccess:function(){
			  <?php if(!$conf_ok){?>
					$('chooseprofile').submit();					
			  <?php }else{?>
			  		$('profileconf_msg').show();
			  <?php }?>}
			 
 			});
		});							
	</script>
