<?php
header('Pragma: public'); // required
header('Expires: -1'); // no cache
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
header('Cache-Control: private', false);

require_once("header.php");
require_once("magmi_config.php");
require_once("magmi_statemanager.php");

require_once("fshelper.php");
require_once("magmi_web_utils.php");
$badrights = array();
// checking post install procedure

$postinst = "../inc/magmi_postinstall.php";
if (file_exists($postinst)) {
    require_once("$postinst");
    if (function_exists("magmi_post_install")) {
        $result = magmi_post_install();

        if ($result["OK"] != "") {
            ?>
<div class="container_12">
	<div class="grid_12 subtitle">
		<span>Post install procedure</span>
	</div>
	<div class="grid_12 col">
		<h3>Post install output</h3>
		<div class="mgupload_info" style="margin-top: 5px">
	 <?php echo $result["OK"]?>
	 </div>
	</div>
</div>
<?php

        }
        rename($postinst, $postinst . "." . strval(time()));
    }
}
foreach (array("../state", "../conf", "../plugins") as $dirname) {
    if (!FSHelper::isDirWritable($dirname)) {
        $badrights[] = $dirname;
    }
}
if (count($badrights) == 0) {
    $state = Magmi_StateManager::getState();

    if ($state == "running" || (isset($_REQUEST["run"]) && $_REQUEST["run"] == "import")) {
        require_once("magmi_import_run.php");
    } else {
        Magmi_StateManager::setState("idle", true);
        require_once("magmi_config_setup.php");
        require_once("magmi_profile_config.php");
    }
} else {
    ?>

<div class="container_12">
	<div class="grid_12">
		<div class="magmi_error" style="margin-top: 5px">
			Directory permissions not compatible with Mass Importer operations
			<ul>
		<?php

    foreach ($badrights as $dirname) {
        $trname = str_replace("..", "magmi", $dirname);
        ?>
			<li><?php echo $trname?> not writable!</li>
		<?php 
    }
    ?>
		</ul>
		</div>
	</div>
</div>
<?php

}
?>
<?php require_once("footer.php");?>
<div id="overlay" style="display: none">
	<div id="overlaycontent"></div>
</div>