<?php
require_once("security.php");
require_once("../inc/magmi_statemanager.php");
require_once("progress_parser.php");

if (isset($_REQUEST["logfile"])) {
    $logfile = $_REQUEST["logfile"];
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
			$(dtype+'_link').update("Show details");
		}
		else
		{
			new Ajax.Updater(detdiv,'progress_details.php',
					{parameters:{'key':dtype,'PHPSESSID':'<?php echo session_id()?>'},
					 onComplete:function(f){
							$(detdiv).addClassName("loaded");
							$(dtype+'_link').update("Hide details");
							$(detdiv).show();
						},evalScripts:true});

		}
	};
</script>

<div>
	<h4>Plugins</h4>
	<ul>
		<?php foreach ($parser->getData("plugins") as $pinfo): ?>
		<li class="log_standard"><?php echo $pinfo["name"] ?> <i>(<?php echo $pinfo["ver"] ?>)</i></li>
		<?php endforeach ?>
	</ul>
</div>

<div>
	<h4>Startup</h4>
	<ul>
		<?php foreach ($parser->getData("startup") as $sinfo): ?>
		<li class="log_standard"><?php echo $sinfo ?></li>
		<?php endforeach ?>
	</ul>
</div>

<script type="text/javascript">setProgress(<?php echo $percent?>);</script>

<?php if ($count):?>
<h4>Global statistics</h4>
<table class="log_itime table table-sm">
	<thead>
		<tr>
			<th>Imported</th>
			<th>Elapsed</th>
			<th>Recs/min</th>
			<th>Attrs/min</th>
			<th>Last <?php echo $step ?></th>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td><?php echo $count ?> items (<?php echo $percent ?>%)</td>
			<td><?php echo $parser->getData("itime:elapsed") ?></td>
			<td><?php echo $parser->getData("itime:speed") ?></td>
			<td><?php echo $parser->getData("itime:speed") * $parser->getData("columns") ?></td>
			<td><?php echo $parser->getData("itime:incelapsed") ?></td>
		</tr>
	</tbody>
</table>
<h4>Database statistics</h4>
<table class="log_dbtime table table-sm">
	<thead>
		<tr>
			<th>Requests</th>
			<th>Elapsed</th>
			<th>Speed</th>
			<th>Avg Reqs</th>
			<th>Efficiency</th>
			<th>Last <?php echo $step ?></th>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td><?php echo $parser->getData("dbtime:count") ?></td>
			<td><?php echo $parser->getData("dbtime:elapsed") ?></td>
			<td><?php echo $parser->getData("dbtime:speed") ?> reqs/min</td>
			<td><?php echo round($parser->getData("dbtime:count") / $parser->getData("itime:count"), 2) ?>/item</td>
			<td><?php echo round(($parser->getData("dbtime:elapsed") * 100 / $parser->getData("itime:elapsed")), 2) ?>%</td>
			<td><?php echo $parser->getData("dbtime:lastcount") ?> reqs</td>
		</tr>
	</tbody>
</table>
<?php endif?>

<?php foreach (array("error", "warning") as $gtype):
    $arr = $parser->getData($gtype);
    if (count($arr) > 0) {
        ?>
<div class="log_<?php echo $gtype ?>" role="alert">
	<?php echo count($arr)." $gtype(s) found" ?>
	<a href="javascript:loadDetails('<?php echo $gtype ?>');" class="alert-link" id="<?php echo $gtype ?>_link">Show details</a>
</div>
<div id="log_<?php echo $gtype?>_details"></div>
<?php} ?>
<?php endforeach ?>

<?php $info = $parser->getData("info");
if (count($info) > 0):?>
<div>
	<h4>Runtime infos</h4>
	<div class="runtime_info">
		<ul>
			<?php foreach ($parser->getData("info") as $info): ?>
			<li class="log_standard" role="alert"><?php echo $info ?></li>
			<?php endforeach ?>
		</ul>
	</div>
</div>
<?php endif?>


<?php
$skipped = $parser->getData("skipped");
if (!is_array($skipped) && $skipped > 0)
:
    ?>
<div class="log_info" role="alert">Skipped <?php echo $parser->getData("skipped")?> records</div>
<?php endif?>

<?php if (Magmi_StateManager::getState() == "canceled"):?>
<div class="log_warning" role="alert">Canceled by user</div>
<div class="log_warning" role="alert">
	<span><a href='magmi.php'>Back to Configuration Page</a></span>
</div>
<script type="text/javascript">endImport();</script>
<?php else:?>
	<?php if ($parser->getData("ended")):?>

<div class="log_end">
	<a href="magmi.php" role="button" class="btn btn-primary">Back</a>
</div>

<script type="text/javascript">endImport();</script>
<?php endif?>
<?php endif?>
