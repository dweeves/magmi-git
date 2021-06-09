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
				pluginclass:pclass
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
		$("plugin_run:"+pclass).hide();
	};
</script>
<div class="container">
<div class="row">
<div class="col-12">
	<h3 class="subtitle omega">Magmi Utilities</h3>
    <div class="list-group">
	<?php
$mmi = new Magmi_UtilityEngine();
$mmi->initialize();
$mmi->initPlugins();
$mmi->createPlugins("__utilities__", null);
$plist = $mmi->getPluginInstances("utilities");
?>
	<?php

foreach ($plist as $pinst) {
    $pclass = $pinst->getPluginClass();
    $pinfo = $pinst->getPluginInfo();
    $info = $pinst->getShortDescription(); ?>
	<div class="list-group-item bg-light utility">
		<h4 class="pluginname"><?php echo $pinfo["name"]." v".$pinfo["version"]; ?></h4>
		<?php ?>
			<p class="plugindescription">
			<?php if ($info !== null) { ?>
				<?php echo $info?>
			<?php } ?>
			</p>
			<div class="plugininfo mb-2 clearfix">
				<a href="javascript:togglePanel('<?php echo $pclass?>')" class="btn btn-primary btn-sm float-right">Options</a>
			</div>

		<div class="pluginoptionpanel" id="pluginoptions:<?php echo $pclass?>" style="display: none; clear: both;">
			<form id="<?php echo $pclass?>_params">
				<?php echo $pinst->getOptionsPanel()->getHtml()?>
			</form>
		</div>

		<div id="plugin_run:<?php echo $pclass?>" class="pluginrun_results"
			style="display: none">
			<h3><?php echo $pinfo["name"]." v".$pinfo["version"]; ?> Results</h3>
			<div id="plugin_run:<?php echo $pclass?>_res"></div>
		</div>

		<div class="separator"></div>
		<div class="utility_run actionbutton">
			<a id="plrun_<?php echo $pclass?>" class="btn btn-secondary btn-sm float-right" href="javascript:runUtility('<?php echo $pclass?>')">Runutility</a>
		</div>
	</div>
	<?php
}?>
</div>
</div>
</div>
</div>

<div class="container mt-2 mb-2">
	<div class="row">
		<div class="col-12">
			<a href="magmi.php" class="btn btn-secondary">Back to Magmi Config Interface</a>
		</div>
	</div>
</div>
<script type="text/javascript">
	var warntargets=[];
	<?php

$warn = $pinst->getWarning();
if ($warn != null) {
    $pclass = $pinst->getPluginClass(); ?>
		warntargets.push({target:'plrun_<?php echo $pclass?>',msg:'<?php echo $warn?>'});
	<?php
}
?>
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
