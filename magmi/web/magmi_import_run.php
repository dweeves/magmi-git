	<?php
    require_once("security.php");
ini_set('gpc_magic_quotes', 0);
    require_once("security.php");
$profile = isset($_REQUEST["profile"]) ? strip_tags($_REQUEST["profile"]) : 'default';
$_SESSION["last_runned_profile"] = $profile;
session_write_close();
?>
<script type="text/javascript">
	var imp_params={engine:'magmi_productimportengine:Magmi_ProductImportEngine'};
	<?php
foreach ($_REQUEST as $k => $v) {
    echo "imp_params['$k']='$v';\n";
}
?>
	</script>
<div id="import_log" class="container mb-4">
	<div class="row">
		<div class="col-12">
			<div class="card">
				<h3 class="card-header">
					<span>Importing using profile (<?php echo $profile?>)...</span>
					<span><input id="cancel_button" class="btn btn-danger btn-sm" type="button" value="Cancel" onclick="cancelImport()"></span>
					<span id="endimport_div" class="log_info float-right" style="display: none"></span>
					<span id="startimport_div" class="log_info float-right mr-2" style="display: none"></span>
				</h3>
				<div class="card-body">
					<div id="progress_container" class="progress mb-4">
						<div id="import_current" class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" aria-valuemin="0" aria-valuemax="100"></div>
					</div>
					<div id="runlog"></div>
				</div>
			</div>
		</div>
	</div>
</div>

<script type="text/javascript">
	var pcall=0;

	updateTime=function(tdiv,xprefix)
	{
		new Ajax.Updater(tdiv,'ajax_gettime.php',{parameters:{prefix:xprefix},
			onComplete:function(){$(tdiv).show();}});
	};

	endImport=function(t)
	{
		if(window.upd!=null)
		{
			$('cancel_button').hide();
			window.upd.stop();
			window.upd=null;
			updateTime('endimport_div','Ended');
			if(window._sr!=null)
			{
				window._sr.transport.abort();
				window._sr=null;
			}
		}
	};

	startProgress=function(imp_params)
	{
		window.upd=new Ajax.PeriodicalUpdater("runlog","magmi_progress.php",{frequency:1,evalScripts:true,parameters:{
		logfile:imp_params['logfile']}});
	};

	startImport=function(imp_params)
	{

		if(window._sr==null)
		{
			updateTime('startimport_div','Started');
			var rq=new Ajax.Request('magmi_run.php',{method:'post',
								 parameters:imp_params,
								onCreate:function(r){window._sr=r;},
								onLoading:function(r){
													 startProgress.delay(0.3,imp_params);
													}});
		}
	};

	setProgress=function(pc)
	{
		$('import_current').setStyle({width:''+pc+'%'});
		$('import_current').setAttribute('aria-valuenow', pc);
		$('import_current').update(''+pc+'%');
	};

	cancelImport=function()
	{
		var rq=new Ajax.Request("magmi_cancel.php",{method:'get'});
		/*if(window._sr!=null)
		{
			window._sr.transport.abort();
			window._sr=null;
		}*/
				new Ajax.Updater("runlog","magmi_progress.php",{evalScripts:true,
					parameters:{logfile:imp_params['logfile']}
				});;
	};

	if(imp_params.mode!==null)
	{
		startImport(imp_params);
	}
	else
	{
		startProgress(imp_params);
	}
</script>
