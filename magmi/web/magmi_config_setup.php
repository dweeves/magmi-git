<?php
require_once('security.php');
require_once('magmi_config.php');
require_once('magmi_statemanager.php');
require_once('dbhelper.class.php');
$conf = Magmi_Config::getInstance();
$conf->load();
$conf_ok = 1;
?>

<?php
$profile = '';
if (isset($_REQUEST['profile'])) {
    $profile = $_REQUEST['profile'];
} else {
    if (isset($_SESSION['last_runned_profile'])) {
        $profile = $_SESSION['last_runned_profile'];
    }
}
if ($profile == '') {
    $profile = 'default';
}
$eplconf = new EnabledPlugins_Config($profile);
$eplconf->load();
if (!$eplconf->hasSection('PLUGINS_DATASOURCES')) {
    $conf_ok = 0;
}
?>

<?php $zipok=class_exists('ZipArchive'); ?>
<div class="container mb-4">
<div class="row">
<div id="magmi-update" class="magmi-update col-12 mb-4">
<!-- MAGMI UPLOADER DISABLED FOR SECURITY REASONS -->
<div class="card">
	<h3 class="card-header subtitle">
		<span>Update Magmi</span>
	</h3>
<?php if (false) { ?>
<form method="post" enctype="multipart/form-data" action="magmi_upload.php">
	<div class="card-body">
		<h3>Update Magmi Release</h3>
		<input type="file" name="magmi_package"></input> <input type="submit" value="Upload Magmi release"></input>
		<?php
    	if (isset($_SESSION['magmi_install'])) {
        	$type = $_SESSION['magmi_install'][0];
        	$msg = $_SESSION['magmi_install'][1]; ?>
		<div class="mgupload_<?php echo $type?>">
				<?php echo $msg; ?>
		</div>
		<?php
        unset($_SESSION["magmi_install"]);
    } ?>
	</div>
	</form>
</div>

<!--  PLUGIN UPLOADER -->
<div class="card-body">
	<form method="post" enctype="multipart/form-data"
		action="plugin_upload.php">
		<div class="col-12">
			<h3>Upload New Plugins</h3>
			<input type="file" name="plugin_package"></input> <input
				type="submit" value="Upload Plugins"></input>
<?php

    if (isset($_SESSION["plugin_install"])) {
        $type = $_SESSION["plugin_install"][0];
        $msg = $_SESSION["plugin_install"][1]; ?>
<div class="plupload_$type">
<?php echo $msg; ?>
</div>
<?php unset($_SESSION["magmi_install"]);
    } ?>
</div>
	</form>
<?php
} else {
        ?>
<div class="card-body">
	<h3>Update Disabled</h3>
	<div class="error">Upgrade/Upload function are disabled for security reasons</div>
</div>
<?php
    }?>
</div>
</div>

<div id="magmi-run" class="magmi-run col-12 mb-4">
<div class="card">
	<h3 class="card-header subtitle">
		<span>Run Magmi</span>
	</h3>
<div class="card-body">
<?php if (!$conf_ok) {
        ?>
<span class="float-right saveinfo log_warning"><b>No Profile saved yet, Run disabled!</b></span>
<?php
    }?>
<form method="POST" id="runmagmi"
	action="magmi.php?ts=<?php echo time()?>" <?php if (!$conf_ok) {
        ?>
	style="display: none" <?php
    }?>>
	<input type="hidden" name="run" class="form-group" value="import"></input> <input
		type="hidden" name="logfile" class="form-group"
		value="<?php echo Magmi_StateManager::getProgressFile()?>"></input>
		<div id="directrun">
			<h3>Directly run magmi with existing profile</h3>
			<div class="formline">
				<label for="profile">Run Magmi With Profile:</label>
				<?php $profilelist = $conf->getProfileList(); ?>
				<select name="profile" id="runprofile">
					<option <?php if (null == $profile) {
        ?> selected="selected" <?php
    }?>
						value="default">Default</option>
					<?php foreach ($profilelist as $profilename) {
        ?>
					<option <?php if ($profilename == $profile) {
            ?> selected="selected"
						<?php
        } ?> value="<?php echo $profilename?>"><?php echo $profilename?></option>
					<?php
    }?>
				</select> <label for="mode">Using mode:</label> <select name="mode" id="mode">
					<option value="update">Update existing items only and skip new ones</option>
					<option value="create">Create new items and update existing ones</option>
					<option value="xcreate">Create new items only and skip existing ones</option>

				</select> <input type="submit" value="Run Import" class="btn btn-primary btn-lg btn-block active mt-2"
					<?php if (!$conf_ok) {
        ?> disabled="disabled" <?php
    }?>></input>
			</div>
	</div>
</form>
<a href="magmi_utilities.php" class="btn btn-secondary" role="button">Advanced Utilities</a>
</div>
</div>
</div>

<?php
$cansock = true;
$dmysqlsock = DBHelper::getMysqlSocket();
$cansock = !($dmysqlsock === false);
?>
<div id="magmi-parameters" class="magmi-parameters col-12 mb-4">
	<div class="card">
	<h3 class="card-header">
		<span>Configure Global Parameters</span> <span id="commonconf_msg" class="float-right saveinfo">
		Saved:<?php echo $conf->getLastSaved("%c")?>
		</span>
	</h3>

	<div class="card-body">
<form method="post" action="magmi_saveconfig.php" id="commonconf_form">
		<div class="card-group row">
		<div class="col-12 col-md-4 mb-4">
		<div class="card">
			<h3 class="card-header">Database</h3>
			<div class="card-body">
			<div id="connectivity" class="form-group">
	<?php $curconn = $conf->get("DATABASE", "connectivity", "net");?>
				<label for="DATABASE:connectivity">Connectivity</label>
				<select name="DATABASE:connectivity" id="DATABASE:connectivity">
					<option value="net" <?php if ($curconn === "net") {
    ?>
						selected="selected" <?php
} ?>>Using host/port</option>
					<?php if ($cansock) {
        ?>
					<option value="socket" <?php if ($curconn == "socket") {
            ?>
						selected="selected" <?php
        } ?>>Using local socket</option>
					<?php
    }?>
					<option value="localxml" <?php echo $curconn == "localxml" ? 'selected="selected"' : '' ?>>Using magento.xml</option>
				</select>
				</div>
			<div id="connectivity:net" class="form-group connectivity"
				<?php if ($curconn != "net") {
        ?> style="display: none" <?php
    }?>>

					<label for="DATABASE:host">Host:</label>
					<input type="text" name="DATABASE:host" value="<?php echo $conf->get("DATABASE", "host", "localhost")?>"></input>

					<label for="DATABASE:port">Port:</label>
					<input type="text" name="DATABASE:port" value="<?php echo $conf->get("DATABASE", "port", "3306")?>"></input>
			</div>
			<div id="connectivity:localxml" class="connectivity" <?php echo $curconn != 'localxml' ? 'style="display: none;"' : '' ?>>
					<label for="DATABASE:host">Resource:</label>
					<select id="select_localxml_resources" name="DATABASE:resource">
						<option value="<?php echo $conf->get('DATABASE', 'resource', 'default_setup'); ?>"><?php echo $conf->get('DATABASE', 'resource', 'default_setup'); ?></option>
					</select>
			</div>
			<?php if ($cansock) {
        ?>
				<div id="connectivity:socket" class="connectivity"
							<?php if ($curconn != "socket") {
            ?> style="display: none" <?php
        } ?>>

								<label for="DATABASE:host">Unix Socket:</label>

					<?php
                        $mysqlsock = $conf->get("DATABASE", "unix_socket", $dmysqlsock);
        if (!file_exists($mysqlsock)) {
            $mysqlsock = $dmysqlsock;
        } ?>
					<input type="text" name="DATABASE:unix_socket" value="<?php echo $mysqlsock?>"></input>
			</div>
			<?php
    }?>
			<div id="connectivity_extra" <?php echo $curconn == 'localxml' ? 'style="display: none;"' : ''; ?>>
				<hr/>
				<label for="DATABASE:dbname">DB Name:</label>
				<input type="text" name="DATABASE:dbname" value="<?php echo $conf->get("DATABASE", "dbname")?>"></input>

				<label for="DATABASE:user">Username:</label>
				<input type="text" name="DATABASE:user" value="<?php echo $conf->get("DATABASE", "user")?>"></input>

				<label for="DATABASE:password">Password:</label>
				<input type="password" name="DATABASE:password" value="<?php echo $conf->get("DATABASE", "password")?>"></input>

				<label for="DATABASE:table_prefix">Table prefix:</label>
				<input type="text" name="DATABASE:table_prefix" value="<?php echo $conf->get("DATABASE", "table_prefix")?>"></input>
			</div>
		</div>
		</div>
		</div>

		<div class="col-12 col-md-4 mb-4">
		<div class="card">
			<h3 class="card-header">Magento</h3>
			<div class="card-body">
				<label for="MAGENTO:version">Version:</label>
				<select name="MAGENTO:version">
			<?php foreach (array("1.9.x", "1.8.x", "1.7.x", "1.6.x", "1.5.x", "1.4.x", "1.3.x") as $ver) {
        ?>
				<option value="<?php echo $ver?>"
							<?php if ($conf->get("MAGENTO", "version") == $ver) {
            ?>
							selected=selected <?php
        } ?>><?php echo $ver?></option>
			<?php
    }?>
		</select>
				<label for="MAGENTO:basedir">Filesystem Path to magento directory:</label>
				<input type="text" name="MAGENTO:basedir" value="<?php echo $conf->get("MAGENTO", "basedir")?>"></input>
			</div>
		</div>
		</div>

		<div class="col-12 col-md-4 mb-4">
		<div class="card omega">
			<h3 class="card-header">Global</h3>
			<div class="card-body" id="globstep">
				<label for="GLOBAL:step">Reporting step in %:</label>
				<input type="text" name="GLOBAL:step" size="5" value="<?php echo $conf->get("GLOBAL", "step")?>"></input>

				<label for="GLOBAL:multiselect_sep">Multiselect value separator:</label>
				<input type="text" name="GLOBAL:multiselect_sep" size="3" value="<?php echo $conf->get("GLOBAL", "multiselect_sep", ",")?>"></input>

			<h6 class="card-subtitle mb-2 mt-2 text-muted">Dir &amp; File permissions</h6>

				<label for="GLOBAL:dirmask">Directory permissions:</label>
				<input type="text" name="GLOBAL:dirmask" size="3" value="<?php echo $conf->get("GLOBAL", "dirmask", "755")?>"></input>

				<label for="GLOBAL:filemask">File permissions:</label>
				<input type="text" name="GLOBAL:filemask" size="3" value="<?php echo $conf->get("GLOBAL", "filemask", "644")?>"></input>

			<h6 class="card-subtitle mb-2 mt-2 text-muted">Backward compatibility</h6>

			<div class="form-check">
				<label class="form-check-label">
					<input type="checkbox" id="noattsetupdate_cb" class="form-check-input"
						<?php if ($conf->get("GLOBAL", "noattsetupdate", "off") == "on") {
        ?>
						checked="checked" <?php
    }?>>
					Disable attribute set update:</label>
						<input type="hidden" id="noattsetupdate_hf" name="GLOBAL:noattsetupdate" class="form-check-input" value="<?php echo $conf->get("GLOBAL", "noattsetupdate", "off") ?>"/>
						<script type="text/javascript">
						$('noattsetupdate_cb').observe('click',function(){
							if($('noattsetupdate_cb').checked) {
								$('noattsetupdate_hf').value = 'on';
							} else {
								$('noattsetupdate_hf').value = 'off';
							}
						});
					</script>
			</div>

		</div>
		</div>
		</div>
		<div class="col-12">
			<a id="save_commonconf" class="btn btn-primary btn-lg btn-block" role="button" aria-pressed="true" href="#">Save global parameters</a>
		</div>
	</div>
	<?php if ($conf->get("USE_ALTERNATE", "file", "") != "") {
        ?>
	<input type="hidden" name="USE_ALTERNATE:file"
		value="<?php echo $conf->get("USE_ALTERNATE", "file"); ?>">
	<?php
    }?>
</form>
</div>
</div>

<script type="text/javascript">

$('save_commonconf').observe('click',function()
{
	new Ajax.Updater('commonconf_msg',
				 "magmi_saveconfig.php",
				 {parameters:$('commonconf_form').serialize('true'),
				  onSuccess:function(){$('commonconf_msg').show();}
	  			});
});
<?php if ($conf_ok) {
        ?>
$('runprofile').observe('change',function(ev)
		{
			document.location='magmi.php?profile='+Event.element(ev).value;
		});
<?php
    }?>

$('DATABASE:connectivity').observe('change',function(ev)
		{
			var clist=$$('.connectivity');
					clist.each(function(it)
					{
						var el=it;
						if(el.id=='connectivity:'+$F('DATABASE:connectivity'))
						{
							el.show();
						}
						else
						{
							el.hide();
						}
					});
			if ($F('DATABASE:connectivity') != 'localxml') {
				$('connectivity_extra').show();
			} else {
				$('connectivity_extra').hide();
				new Ajax.Updater('select_localxml_resources',
					'ajax_readlocalxml.php', {
						parameters: $('commonconf_form').serialize('true')
					}
				);
			}
		});
if ($('DATABASE:connectivity').value == 'localxml') {
	new Ajax.Updater('select_localxml_resources',
		'ajax_readlocalxml.php', {
			parameters: $('commonconf_form').serialize('true')
		}
	);
}
</script>
</div>
