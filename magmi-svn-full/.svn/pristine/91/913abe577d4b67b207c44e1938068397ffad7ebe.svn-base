	<?php 
	ini_set('magic_gpc_quotes',0);
	$profile=getWebParam("profile",'default');
	$engclass=getWebParam('engineclass','magmi_productimportengine:Magmi_ProductImportEngine');
	$logfile=getWebParam("logfile","magmi_progress.txt");
	$wp=getWebParams();
	$wp['PHPSESSID']=session_id();
	session_write_close();
	?>
	<script type="text/javascript">
	var imp_params={engineclass:'<?php echo $engclass?>',logfile:'<?php echo $logfile?>'};
	<?php 
		foreach($wp as $k=>$v)
		{
			echo "imp_params['$k']='$v';\n";	
		}
	?>
	</script>
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
		loaddiv(tdiv,'ajax_gettime.php',decodeURIComponent($.param({prefix:xprefix})),function(){$(tdiv).show()});
	};
	
	endImport=function(t)
	{		
		updateTime('#endimport_div','Import Ended');
		setProgress(100);
		$('#cancel_button').hide();
	};
	
	updateProgress=function()
	{
		loaddiv('#runlog','magmi_progress.php',imp_params);
	}
		
	
	startImport=function(imp_params)
	{
		
		if(window._sr==null)
		{
			updateTime('#startimport_div','Import Started');
			window._sr=$.ajax({type:'POST',
							  url:'magmi_run.php',
								 data:$.param(imp_params),
								 dataType:"text",
								 beforeSend:function(jqXhr){
										window.loop=window.setInterval(updateProgress,1000);
													},
								complete:function(){
									updateProgress();
									clearInterval(window.loop);
								}}
													);
		}
	};
	
	setProgress=function(pc)
	{
		$('#import_current').css('width',''+pc+'%');
		$('#import_progress').html(''+pc+'%');
	};

	cancelImport=function()
	{
		geturl("magmi_cancel.php");
		if(window._rq!=null)
		{
			window._rq.abort();
			window._rq=null;
		}
		clearInterval(window.loop);
		
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
