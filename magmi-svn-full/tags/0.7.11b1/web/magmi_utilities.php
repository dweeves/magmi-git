<?php
	require_once("header.php");
	require_once("../engines/magmi_utilityengine.php");

?>
<script type="text/javascript">

	updatePanel=function(pclass,pparams)
	{
		params={
			engine:'magmi_utilityengine:Magmi_UtilityEngine',
			pluginclass:pclass,
			plugintype:'utilities',
			profile:'__utilities__'};
		getPluginParams(params,pparams);
		
		new Ajax.Updater("pluginoptions:"+pclass,"ajax_pluginconf.php",{parameters:params});
	};

	getPluginParams=function(pclass,pcontainer)
	{
		Object.extend(pcontainer,$(pclass+"_params").serialize(true));
	}
	
	runUtility=function(pclass)
	{
		var pparams={
				engine:'magmi_utilityengine:Magmi_UtilityEngine',
				pluginclass:pclass,	
				};
		getPluginParams(pclass,pparams);
		
		new Ajax.Updater("plugin_run:"+pclass+"_res",
						 "magmi_run.php",
						 {parameters:pparams,
						  onComplete:function(){
			  				$$(".pluginrun_results").each(function(el){el.hide();});
							$("plugin_run:"+pclass).show();
			  				updatePanel(pclass);}
						});
	};
	
	togglePanel=function(pclass)
	{
		var target="pluginoptions:"+pclass;
		$(target).toggle();
	};
</script>
<div class="container_12">
<div class="grid_12 col omega">
<h3>Magmi Utilities</h3>
<ul>
<?php 
 $mmi=new Magmi_UtilityEngine();
 $mmi->initialize();
 $mmi->initPlugins();
 
 $mmi->createPlugins("__utilities__",null);
 $plist=$mmi->getPluginInstances("utilities");
 ?>
<?php foreach($plist as $pinst)
{
	$pclass=$pinst->getPluginClass();
	$pinfo=$pinst->getPluginInfo();
	?>
	<li class="utility" >
	<div class="pluginselect">
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
	<div class="plugininfo">
		<a href="javascript:togglePanel('<?php echo $pclass?>')">Options</a>
	</div>
	
	<div class="utility_run">
		<span><a id="plrun_<?php echo $pclass?>" href="javascript:runUtility('<?php echo $pclass?>')" class="actionbutton " >Run Utility</a></span>
	</div>
		<form id="<?php echo $pclass?>_params">

	<div class="pluginoptionpanel" id="pluginoptions:<?php echo $pclass?>" style="display:none">
		<?php echo $pinst->getOptionsPanel()->getHtml()?>
	</div>
	</form>

	</li>
<?php }?>	
</ul>
</div>
<div class="grid_12">
<?php foreach($plist as $pinst)
{
	$pclass=$pinst->getPluginClass();
	$pinfo=$pinst->getPluginInfo();?>
	
		<div id="plugin_run:<?php echo $pclass?>" class="pluginrun_results" style="display:none">
	<h3><?php echo $pinfo["name"]." v".$pinfo["version"];?> Results</h3>
	<div id="plugin_run:<?php echo $pclass?>_res">
	</div>
	</div>
	
<?php }?>
</div>
</div>
<div class="container_12">
<div class="grid_12">
<a href="magmi.php">
Back to Magmi Config Interface
</a>
</div>
</div>
<script type="text/javascript">
	var warntargets=[];
	<?php $warn=$pinst->getWarning();
	if($warn!=null)
	{
		$pclass=$pinst->getPluginClass();?>
		warntargets.push({target:'plrun_<?php echo $pclass?>',msg:'<?php echo $warn?>'});
	<?php 	
	}?>
	warntargets.each(function(it){
		$(it.target).observe('click',function(ev){
			var res=confirm(it.msg);
			if(res==false)
			{
				Event.stop(ev);
				return;
			}
		})});

</script>
<?php require_once("footer.php")?>