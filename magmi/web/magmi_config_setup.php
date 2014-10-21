<?php
require_once ("magmi_config.php");
require_once ("magmi_statemanager.php");
require_once ("dbhelper.class.php");
$conf = Magmi_Config::getInstance();
$conf->load();
$conf_ok = 1;
?>
<?php

$profile = "";
if (isset($_REQUEST["profile"]))
{
    $profile = $_REQUEST["profile"];
}
else
{
    
    if (isset($_SESSION["last_runned_profile"]))
    {
        $profile = $_SESSION["last_runned_profile"];
    }
}
if ($profile == "")
{
    $profile = "default";
}
$eplconf = new EnabledPlugins_Config($profile);
$eplconf->load();
if (!$eplconf->hasSection("PLUGINS_DATASOURCES"))
{
    $conf_ok = 0;
}
?>
<!-- MAGMI UPLOADER DISABLED FOR SECURITY REASONS -->
<?php $zipok=class_exists("ZipArchive");?>
<div class="container_12">
	<div class="grid_12 subtitle">
		<span>Update Magmi</span>
	</div>
</div>
<div class="container_12">
<?php if(false){?>
<form method="post" enctype="multipart/form-data"
		action="magmi_upload.php">
		<div class="grid_12 col">
			<h3>Update Magmi Release</h3>
			<input type="file" name="magmi_package"></input> <input type="submit"
				value="Upload Magmi Release"></input>
		<?php
    
    if (isset($_SESSION["magmi_install"]))
    {
        $type = $_SESSION["magmi_install"][0];
        $msg = $_SESSION["magmi_install"][1];
        ?>	
		<div class="mgupload_<?php echo $type?>">
				<?php echo $msg;?>
		</div>
		<?php
        unset($_SESSION["magmi_install"]);
    }
    ?>
	</div>
	</form>
</div>

<!--  PLUGIN UPLOADER -->
<div class="container_12">
	<form method="post" enctype="multipart/form-data"
		action="plugin_upload.php">
		<div class="grid_12 col">
			<h3>Upload New Plugins</h3>
			<input type="file" name="plugin_package"></input> <input
				type="submit" value="Upload Plugins"></input>
<?php
    
    if (isset($_SESSION["plugin_install"]))
    {
        $type = $_SESSION["plugin_install"][0];
        $msg = $_SESSION["plugin_install"][1];
        ?>
<div class="plupload_$type">
<?php echo $msg;?>
</div>
<?php unset($_SESSION["magmi_install"]); }?>
</div>
	</form>
<?php } else {?>
<div class="grid_12 col">
		<h3>Update Disabled</h3>
		<div class="error">Upgrade/Upload function
			are disabled for security reasons</div>
	</div>
<?php }?>
</div>
<div class="container_12">
	<div class="grid_12 subtitle">
		<span>Run Magmi</span>
<?php if(!$conf_ok){?>
<span class="saveinfo log_warning"><b>No Profile saved yet, Run
				disabled!!</b></span>
<?php }?>
</div>
</div>
<form method="POST" id="runmagmi"
	action="magmi.php?ts=<?php echo time() ?>" <?php if(!$conf_ok){?>
	style="display: none" <?php }?>>
	<input type="hidden" name="run" value="import"></input> <input
		type="hidden" name="logfile"
		value="<?php echo Magmi_StateManager::getProgressFile()?>"></input>
	<div class="container_12">
		<div class="grid_12 col" id="directrun">
			<h3>Directly run magmi with existing profile</h3>
			<div class="formline">
				<span class="label">Run Magmi With Profile:</span>
				<?php $profilelist=$conf->getProfileList(); ?>
				<select name="profile" id="runprofile">
					<option <?php if(null==$profile){?> selected="selected" <?php }?>
						value="default">Default</option>
					<?php foreach($profilelist as $profilename){?>
					<option <?php if($profilename==$profile){?> selected="selected"
						<?php }?> value="<?php echo $profilename?>"><?php echo $profilename?></option>
					<?php }?>
				</select> <span>using mode:</span> <select name="mode" id="mode">
					<option value="update">Update existing items only,skip new ones</option>
					<option value="create">create new items &amp; update existing ones</option>
					<option value="xcreate">create new items only, skip existing ones</option>

				</select> <input type="submit" value="Run Import"
					<?php if(!$conf_ok){?> disabled="disabled" <?php }?>></input>
			</div>
		</div>
	</div>
</form>
<div class="container_12">
	<div class="grid_12">
		<a href="magmi_utilities.php">Advanced Utilities</a>
	</div>
</div>
<div class="container_12">
	<div class="grid_12 subtitle">
		<span>Configure Global Parameters</span> <span id="commonconf_msg"
			class="saveinfo">
Saved:<?php echo $conf->getLastSaved("%c")?>
</span>
	</div>
</div>
<?php
$cansock = true;
$dmysqlsock = DBHelper::getMysqlSocket();
$cansock = !($dmysqlsock === false);
?>
<div class="clear"></div>
<form method="post" action="magmi_saveconfig.php" id="commonconf_form">
	<div class="container_12" id="common_config">
		<div class="grid_4 col">
			<h3>Database</h3>
	
	<?php $curconn=$conf->get("DATABASE","connectivity","net");?>
			<ul class="formline">
				<li class="label">Connectivity</li>
				<li class="value"><select name="DATABASE:connectivity" id="DATABASE:connectivity">
					<option value="net" <?php if($curconn=="net") { ?>
						selected="selected" <?php } ?>>Using host/port</option>
					<?php if($cansock) { ?>
					<option value="socket" <?php if($curconn=="socket") { ?>
						selected="selected" <?php } ?>>Using local socket</option>
					<?php }?>
					<option value="localxml" <?php echo $curconn == "localxml" ? 'selected="selected"' : '' ?>>Using magento.xml</option>
				</select></li>
			</ul>

			<div id="connectivity:net" class="connectivity"
				<?php if($curconn != "net"){?> style="display: none" <?php }?>>
				<ul class="formline">
					<li class="label">Host:</li>
					<li class="value"><input type="text" name="DATABASE:host"
						value="<?php echo $conf->get("DATABASE","host","localhost")?>"></input></li>
				</ul>
				<ul class="formline">
					<li class="label">Port:</li>
					<li class="value"><input type="text" name="DATABASE:port"
						value="<?php echo $conf->get("DATABASE","port","3306")?>"></input></li>
				</ul>
			</div>
			<div id="connectivity:localxml" class="connectivity" <?php echo $curconn != 'localxml' ? 'style="display: none;"' : '' ?>>
				<ul class="formline">
					<li class="label">Resource:</li>
					<li class="value"><select id="select_localxml_resources" name="DATABASE:resource">
						<option value="<?php echo $conf->get('DATABASE', 'resource', 'default_setup'); ?>"><?php echo $conf->get('DATABASE', 'resource', 'default_setup'); ?></option>
					</select>
				</ul>
			</div>
			<?php if($cansock){?>
				<div id="connectivity:socket" class="connectivity"
							<?php if($curconn != "socket"){?> style="display: none" <?php  }?>>
							<ul class="formline">
								<li class="label">Unix Socket:</li>
					
					<?php
					    $mysqlsock = $conf->get("DATABASE", "unix_socket", $dmysqlsock);
					    if (!file_exists($mysqlsock))
					    {
					        $mysqlsock = $dmysqlsock;
					    }
				    ?>
					<li class="value"><input type="text" name="DATABASE:unix_socket"
									value="<?php echo $mysqlsock?>"></input></li>
					</ul>
			</div>
			<?php }?>	
			<div id="connectivity_extra" <?php echo $curconn == 'localxml' ? 'style="display: none;"' : ''; ?>>
				<hr />
				<ul class="formline">
					<li class="label">DB Name:</li>
					<li class="value"><input type="text" name="DATABASE:dbname"
						value="<?php echo $conf->get("DATABASE","dbname")?>"></input></li>
				</ul>
	
				<ul class="formline">
					<li class="label">Username:</li>
					<li class="value"><input type="text" name="DATABASE:user"
						value="<?php echo $conf->get("DATABASE","user")?>"></input></li>
				</ul>
				<ul class="formline">
					<li class="label">Password:</li>
					<li class="value"><input type="password" name="DATABASE:password"
						value="<?php echo $conf->get("DATABASE","password")?>"></input></li>
				</ul>
				<ul class="formline">
					<li class="label">Table prefix:</li>
					<li class="value"><input type="text" name="DATABASE:table_prefix"
						value="<?php echo $conf->get("DATABASE","table_prefix")?>"></input></li>
				</ul>
			</div>
		</div>
		<div class="grid_4 col">
			<h3>Magento</h3>
			<ul class="formline">
				<li class="label">Version:</li>
				<li class="value"><select name="MAGENTO:version">
			<?php foreach(array("1.9.x","1.8.x","1.7.x","1.6.x","1.5.x","1.4.x","1.3.x") as $ver){?>
				<option value="<?php echo $ver?>"
							<?php if($conf->get("MAGENTO","version")==$ver){?>
							selected=selected <?php }?>><?php echo $ver?></option>
			<?php }?>
		</select></li>
			</ul>
			<ul class="formline" style="height: 40px">
				<li class="label">Filesystem Path to magento directory:</li>
				<li class="value"><input type="text" name="MAGENTO:basedir"
					value="<?php echo $conf->get("MAGENTO","basedir")?>"></input></li>
			</ul>
		</div>
		<div class="grid_4 col omega">
			<h3>Global</h3>
			<ul class="formline" id="globstep">
				<li class="label">Reporting step in %:</li>
				<li class="value"><input type="text" name="GLOBAL:step" size="5"
					value="<?php echo $conf->get("GLOBAL","step")?>"></input></li>
			</ul>
			<ul class="formline" id="mssep">
				<li class="label">Multiselect value separator:</li>
				<li class="value"><input type="text" name="GLOBAL:multiselect_sep"
					size="3"
					value="<?php echo $conf->get("GLOBAL","multiselect_sep",",")?>"></input></li>
			</ul>
			<h3>Dir &amp; File permissions</h3>
			<ul class="formline" id="dirperms">
				<li class="label">Directory permissions:</li>
				<li class="value"><input type="text" name="GLOBAL:dirmask" size="3"
					value="<?php echo $conf->get("GLOBAL","dirmask","755")?>"></input></li>
			</ul>
			<ul class="formline" id="fileperms">
				<li class="label">File permissions:</li>
				<li class="value"><input type="text" name="GLOBAL:filemask" size="3"
					value="<?php echo $conf->get("GLOBAL","filemask","644")?>"></input></li>
			</ul>

		</div>
		<div class="clear"></div>

		<div class="container_12">
			<div class="grid_12">
				<div style="float: right">
					<a id="save_commonconf" class="actionbutton" href="#">Save global
						parameters</a>
				</div>
			</div>
		</div>
	</div>
	<?php if($conf->get("USE_ALTERNATE","file","")!=""){?>
	<input type="hidden" name="USE_ALTERNATE:file"
		value="<?php echo $conf->get("USE_ALTERNATE","file");?>">
	<?php }?>
</form>

<div class="clear"></div>
<script type="text/javascript">

$('save_commonconf').observe('click',function()
{
	new Ajax.Updater('commonconf_msg',
				 "magmi_saveconfig.php",
				 {parameters:$('commonconf_form').serialize('true'),
				  onSuccess:function(){$('commonconf_msg').show();}
	  			});							
});
<?php if($conf_ok){?>
$('runprofile').observe('change',function(ev)
		{
			document.location='magmi.php?profile='+Event.element(ev).value;
		});
<?php }?>	

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