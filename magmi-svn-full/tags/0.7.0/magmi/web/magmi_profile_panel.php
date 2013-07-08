<?php 
if(isset($_REQUEST["profile"]))
{
	$profile=$_REQUEST["profile"];
}
if($profile=="")
{
	$profile=null;
}
$profilename=isset($profile)?$profile:"Default";
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
			<?php foreach($profilelist as $profilename){?>
			<option <?php if($profilename==$profile){?>selected="selected"<?php }?> value="<?php echo $profilename?>"><?php echo $profilename?></option>
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
	$plugins=Magmi_PluginHelper::getInstance('main')->getPluginClasses();
	$order=array("datasources","general","itemprocessors");
?>
</form>
</div>
</div>
<div class="container_12" id="profile_cfg">
<form action="" method="POST" id="saveprofile_form">
	<input type="hidden" name="profile" id="curprofile" value="<?php echo $profile?>">
	<?php foreach($order as $k)
	{?>
	<div class="grid_12 col <?php if($k==$order[count($order)-1]){?>omega<?php }?>">
		<h3><?php echo ucfirst($k)?></h3>
		<?php if($k=="datasources")
		{?>
			<?php $pinf=$plugins[$k];?>
			<?php if(count($pinf)>0){?>
			<div class="pluginselect" style="float:left">
			<select name="PLUGINS_DATASOURCES:class">
			
			
			<?php 
			$sinst=null;
			foreach($pinf as $pclass)
			{
				$pinst=Magmi_PluginHelper::getInstance($profile)->createInstance($pclass);
				if($sinst==null)
				{
					
					$sinst=$pinst;
				}
				$pinfo=$pinst->getPluginInfo();
					
				if($plconf->isPluginEnabled($k,$pclass))
				{
					$sinst=$pinst;
				}
			?>
				<option value="<?php echo $pclass?>"<?php  if($sinst==$pinst){?>selected="selected"<?php }?>><?php echo $pinfo["name"]." v".$pinfo["version"]?></option>
			<?php }
			?>
			
			</select>
			</div>
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
		{?>
		<ul >
		<?php $pinf=$plugins[$k];?>
		<?php foreach($pinf as $pclass)	{
			$pinst=Magmi_PluginHelper::getInstance($profile)->createInstance($pclass);
			$pinfo=$pinst->getPluginInfo();
		?>
		<li>
		<div class="pluginselect">
		<input type="checkbox" class="pl_<?php echo strtoupper($k)?>" name="<?php echo $pclass?>" <?php if($plconf->isPluginEnabled($k,$pclass)){?>checked="checked"<?php }?>>
		<span class="pluginname"><?php echo $pinfo["name"]." v".$pinfo["version"];?></span>
		</div>

		<?php 
			  $info=$pinst->getShortDescription();
		?>
		<div class="plugininfo">
		<?php if($info!==null){?>
			<span>info</span>
			<div class="plugininfohover">
				<?php echo $info?>
			</div>
			
		<?php }?>
		</div>
		<?php $enabled=$plconf->isPluginEnabled($k,$pclass)?>
		<div class="pluginconf"  <?php if(!$enabled){?>style="display:none"<?php }?>>
			<span><a href="javascript:void(0)">configure</a></span>
		</div>
		<div class="pluginconfpanel">
			<?php if($enabled){echo $pinst->getOptionsPanel()->getHtml();}?>
		</div>

		</li>
		<?php }?>	
		<input type="hidden" id="plc_<?php echo strtoupper($k)?>" value="<?php echo implode(",",$plconf->getEnabledPluginClasses($k))?>" name="PLUGINS_<?php echo strtoupper($k)?>:classes"></input>
					
		<?php 
		}?>
				</ul>

	</div>
	<?php }?>
</form>
<div class="grid_12">
<div style="float:right">
	<a id="saveprofile" class="actionbutton" href="javascript:void(0)" <?php if(!$conf_ok){?>disabled="disabled"<?php }?>>Save Profile (<?php echo ($profile==null?"Default":$profile)?>)</a>
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
		$$(".pl_"+t).each(addclass,context);
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

loadConfigPanel=function(container,profile,plclass)
{
 new Ajax.Updater({success:container},'ajax_pluginconf.php',
	{parameters:{
		profile:profile,
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
			var doload=(el.tagName=="SELECT")?true:el.checked;	
			var targets=$(pls.parentNode).select(".pluginconfpanel");
			var container=targets[0];
			if(doload)
			{
				loadConfigPanel(container,profile,plclass);
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

initAjaxConf();
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
