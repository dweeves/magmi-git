<?php
require_once("security.php");
require_once("../inc/magmi_statemanager.php");
require_once("progress_parser.php");

if (isset($_REQUEST["logfile"])) {
    $logfile=$_REQUEST["logfile"];
}
if (!isset($logfile)) {
    $logfile = Magmi_StateManager::getProgressFile();
}
$logfile = Magmi_StateManager::getStateDir() . DIRSEP . $logfile;
if (file_exists($logfile)) {
    $parser = new DefaultProgressParser();
    $parser->setFile($logfile);
    $parser->parse();

    $count = $parser->getData("itime:count");
    if ($count) {
        $lu = $parser->getData("lookup");
        $percent = round(((float) $count * 100 / $lu["nlines"]), 2);
        $stepd = $parser->getData("step");
        $step = $stepd["value"];
    } else {
        $percent = 0;
    }
    $errors = $parser->getData("error");
    $warnings = $parser->getData("warning");
    session_start();
    $_SESSION["log_error"] = $errors;
    $_SESSION["log_warning"] = $warnings;
    session_write_close();
} else {
    die("NO FILE");
}
?>
<script type="text/javascript">
	loadDetails=function(dtype)
	{
		var detdiv='log_'+dtype+'_details';
		if($(detdiv).hasClassName("loaded"))
		{
			$(detdiv).hide();
			$(detdiv).removeClassName("loaded");
			$(dtype+'_link').update("Show Details");
		}
		else
		{
			new Ajax.Updater(detdiv,'progress_details.php',
					{parameters:{'key':dtype,'PHPSESSID':'<?php echo session_id()?>'},
					 onComplete:function(f){var sb = new ScrollBox($(detdiv),{auto_hide:true});
						$(detdiv).addClassName("loaded");
						$(dtype+'_link').update("Hide Details");
						$(detdiv).show();
						},evalScripts:true});

		}
	};
</script>

<div class="col">
	<h3>Plugins</h3>
<?php foreach ($parser->getData("plugins") as $pinfo):?>
	<div class="log_standard"><?php echo $pinfo["name"]?> (<?php echo $pinfo["ver"]?>) by <?php echo $pinfo["auth"]?></div>
<?php endforeach?>
</div>

<div class="col">
	<h3>Startup</h3>

<?php foreach ($parser->getData("startup") as $sinfo):?>
<div class="log_standard"><?php echo $sinfo?></div>
<?php endforeach?>
</div>

<script type="text/javascript">setProgress(<?php echo $percent?>);</script>
<?php if ($count):?>
<div class="col">
	<h3>Global Stats</h3>
	<div class='log_itime'>
		<table>
			<thead>
				<tr>
					<td>Imported</td>
					<td>Elapsed</td>
					<td>Recs/min</td>
					<td>Attrs/min</td>
					<td>Last <?php echo $step?></td>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td><?php echo $count?> items (<?php echo $percent?>%)</td>
					<td><?php echo $parser->getData("itime:elapsed")?></td>
					<td><?php echo $parser->getData("itime:speed")?></td>
					<td><?php echo $parser->getData("itime:speed")*$parser->getData("columns")?></td>
					<td><?php echo $parser->getData("itime:incelapsed")?></td>
				</tr>
			</tbody>
		</table>
	</div>
</div>
<div class="col">
	<h3>DB Stats</h3>
	<div class='log_dbtime'>
		<table>
			<thead>
				<tr>
					<td>Requests</td>
					<td>Elapsed</td>
					<td>Speed</td>
					<td>Avg Reqs</td>
					<td>Efficiency</td>
					<td>Last <?php echo $step?></td>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td><?php echo $parser->getData("dbtime:count")?></td>
					<td><?php echo $parser->getData("dbtime:elapsed")?></td>
					<td><?php echo $parser->getData("dbtime:speed")?> reqs/min</td>
					<td><?php echo round($parser->getData("dbtime:count")/$parser->getData("itime:count"), 2)?>/item</td>
					<td><?php echo round(($parser->getData("dbtime:elapsed")*100/$parser->getData("itime:elapsed")), 2)?>%</td>
					<td><?php echo $parser->getData("dbtime:lastcount")?> reqs</td>
				</tr>
			</tbody>
		</table>
	</div>
</div>
<?php endif?>

<?php

foreach (array("error", "warning") as $gtype)
:
    $arr = $parser->getData($gtype);
    if (count($arr) > 0) {
        ?>
<div class="log_<?php echo $gtype?>">
		<?php echo count($arr)." $gtype(s) found"?>
			<a href="javascript:loadDetails('<?php echo $gtype?>');"
		id="<?php echo $gtype?>_link">Show Details</a>
</div>
<div id="log_<?php echo $gtype?>_details"></div>
<?php 
    }?>
<?php endforeach?>

<?php
$info=$parser->getData("info");
if (count($info)>0):?>
<div class="col">
	<h3>Runtime infos</h3>
	<div class="runtime_info">
	<?php  foreach ($parser->getData("info") as $info):?>
		<div class="log_standard"><?php echo $info?></div>
	<?php endforeach?>
	</div>
</div>
<?php endif?>


<?php
$skipped = $parser->getData("skipped");
if (!is_array($skipped) && $skipped > 0)
:
    ?>
<div class='log_info'>Skipped <?php echo $parser->getData("skipped")?> records</div>
<?php endif?>

<?php if (Magmi_StateManager::getState()=="canceled"):?>
<div class='log_warning'>Canceled by user</div>
<div class='log_warning'>
	<span><a href='magmi.php'>Back to Configuration Page</a></span>
</div>
<script type="text/javascript">endImport();</script>
<?php else:?>
	<?php if ($parser->getData("ended")):?>
<div
	class='log_end <?php if (count($parser->getData("error"))>0) {
    ?> log_error<?php 
}?>'>
	<span><a href='magmi.php'>Back to Configuration Page</a></span>
</div>
<script type="text/javascript">endImport();</script>
<?php endif?>
<?php endif?>