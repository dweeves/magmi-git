	<?php require_once("../inc/magmi_pluginhelper.php");?>
	<?php require_once("../inc/magmi_config.php");?>
	
	<script type="text/javascript">
		var MagmiImporter=Class.create({
			bsfcallbacks:[],
			registerBeforeSubmit:function(cb){this.bsfcallbacks.push(cb)},
			submit:function(){
				var context={results:[]};
				this.bsfcallbacks.each(function(bsc,o){this.results.push(bsc())},context);
				for(i=0;i<context.results.length;i++)
				{
					if(context.results[i]!="" && context.results[i]==false)
					{
						return false;
					}
				}
				$('import_form').submit();}
		});
		var magmi_import=new MagmiImporter();
	</script>
	<div class="container_12">
	<div class="import_params">
	<form id="import_form" method="post" action="magmi.php?run=2">
	<h2>import parameters</h2>
	Mode:<select name="mode" id="mode">
		<option value="update">Update existing items,skip new ones</option>
		<option value="create">create new items &amp; update existing ones</option>
	</select>
	<span id="rstspan" style="display:none">
	<input type="checkbox" id="reset" name="reset" onclick="">Clear all products</span>
	<?php 
		$conf=Magmi_Config::getInstance();
		$conf->load();
		$dst=$conf->getEnabledPluginClasses("datasources");
		$ds=$dst[0];
		$dsinst=Magmi_PluginHelper::getInstance()->createInstance($ds);
		$dsinfo=$dsinst->getPluginInfo();
	?>
	<div class="datasource_plugin_config">
	<h2>Data Source - <?php echo $dsinfo["name"] . " -v".$dsinfo["version"]?></h2>
	<div id="dsp_option_panel">
		<?php 
		echo $dsinst->getOptionsPanel()->getHtml();?>
	</div>
	</div>
	<?php 
		if($conf->getEnabledPluginClasses("GENERAL"))
		{
		foreach($conf->getEnabledPluginClasses("GENERAL") as $plc){?>
		<div class="general_plugin_config">
			<?php $plinst=Magmi_PluginHelper::getInstance()->createInstance($plc); 
				  $plinfo=$plinst->getPluginInfo();
				  $panel=$plinst->getOptionsPanel();
				  ?>
				  <h2><?php echo "{$plinfo["name"]} - v{$plinfo["version"]}"?></h2>
				  <div class="gp_configpanel">
				  	<?php if($panel){
				  		echo $panel->getHtml();
				  	} ?>
				  </div>
		</div>
	<?php }}?>
	<?php 
		if($conf->getEnabledPluginClasses("ITEMPROCESSORS"))
		{
			foreach($conf->getEnabledPluginClasses("ITEMPROCESSORS") as $plc){?>
			<div class="itemprocessor_plugin_config">
			<?php $plinst=Magmi_PluginHelper::getInstance()->createInstance($plc); 
				  $plinfo=$plinst->getPluginInfo();
				  $panel=$plinst->getOptionsPanel();
				  ?>
				  
				  <h2><?php echo "{$plinfo["name"]} - v{$plinfo["version"]}"?></h2>
				  <div class="ipp_configpanel">
				  		<?php echo $panel->getHtml();?>
				 </div>
			</div>
	<?php }}?>
	<div class="grid_12">
	<div style="float:right">
	<a href='magmi.php'>Back to configuration</a>
	<a href="javascript:magmi_import.submit()">Launch Import</a>
	</div>
	</div>
	</form>
	</div>
	</div>
	<script type="text/javascript">
	checkmode=function()
	{
		if($F('mode')=='create')
		{
			$('rstspan').setStyle({display:'inline'});
		}
		else
		{
			$('rstspan').setStyle({display:'none'});
			$('reset').checked=false;
		}
	}
	$('mode').observe('change',checkmode);
</script>
	<script type="text/javascript">
	/**  
	 * having fun with prototype & javascript closures , automatically add an evenhandler on all "fieldhelp" containers in ".ifield" class range
	 * that toggles automatically all "fieldsyntax" containers bound to the same ifield range !!!
	 */
		$$('.ifield').each(function(it){
			it.select('.fieldhelp').each(function(fh){
				fh.observe('click',function(ev){
					it.select('.fieldsyntax').each(function(el){el.toggle();})
						})
				})
			});
		$('reset').observe('click',function(ev){
			var res=confirm('Are you sure ?, it will destroy all existing items in catalog!!!')
			if(res==false)
			{
				Event.stop(ev);
			}
			});
	</script>
	