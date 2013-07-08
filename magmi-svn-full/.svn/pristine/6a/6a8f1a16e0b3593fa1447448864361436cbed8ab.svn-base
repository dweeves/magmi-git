	<?php 
	ini_set('magic_gpc_quotes',0);
	$logfile=isset($_REQUEST['logfile'])?$_REQUEST['logfile']:Magmi_StateManager::getProgressFile();
	$profile=isset($_REQUEST['profile'])?$_REQUEST['profile']:'default';
	$_SESSION["last_runned_profile"]=$profile;
	
	$mode=isset($_REQUEST['mode'])?$_REQUEST['mode']:null;
	session_write_close();
	
	?>
	<div class="clear"></div>
	<div id="import_log" class="container_12">
		<div class="section_title grid_12">
			<span>Importing using profile (<?php echo $profile?>)...</span>
			<span><input id="cancel_button" type="button" value="cancel" onclick="cancelImport()"></input></span>
			<div id="progress_container">
				&nbsp;
				<div id="import_progress"></div>
				<div id="import_current">&nbsp;</div>
			</div>
		</div>
		<div class='grid_12 log_info' style="display:none" id='startimport_div'></div>
		<div id="runlog" class="grid_12">
		</div>
		<div class='grid_12 log_info' style="display:none" id='endimport_div'></div>
	</div>
<script type="text/javascript">
	var pcall=0;

	updateTime=function(tdiv,xprefix)
	{
		new Ajax.Updater(tdiv,'ajax_gettime.php',{parameters:{prefix:xprefix},
			onComplete:function(){$(tdiv).show();}});
	}
	
	endImport=function(t)
	{
		if(window.upd!=null)
		{
			$('cancel_button').hide();
			window.upd.stop();
			window.upd=null;
			updateTime('endimport_div','Import Ended');
			if(window._sr!=null)
			{		
				window._sr.transport.abort();
				window._sr=null;
			}
		}
	}

	startProgress=function()
	{
		window.upd=new Ajax.PeriodicalUpdater("runlog","magmi_progress.php",{frequency:1,evalScripts:true,parameters:{
		logfile:'<?php echo addslashes($logfile) ?>'}});
	}
	
	startImport=function(filename)
	{
		if(window._sr==null)
		{
			updateTime('startimport_div','Import Started');
			var rq=new Ajax.Request('magmi_run.php',{method:'post',
								 parameters:{profile:'<?php echo $profile?>',
						 		 mode:'<?php echo $mode?>',
						 		 logfile:'<?php echo addslashes($logfile)?>',
							 	 engine:'magmi_productimportengine:Magmi_ProductImportEngine'},
								onCreate:function(r){window._sr=r}});
			startProgress.delay(0.5);
		}
	}
	
	setProgress=function(pc)
	{
		$('import_current').setStyle({width:''+pc+'%'});
		$('import_progress').update(''+pc+'%');
	}

	cancelImport=function()
	{
		var rq=new Ajax.Request("magmi_cancel.php",{method:'get'});
		if(window._sr!=null)
		{
			window._sr.transport.abort();
			window._sr=null;
		}
	}

	<?php if($mode!==null){?>
	startImport();
	<?php }else{
	?>
	startProgress();
	<?php }?>
</script>
