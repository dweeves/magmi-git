<?php
require_once('security.php');
if (isset($_REQUEST['profile'])) {
    $profile = strip_tags($_REQUEST['profile']);
} else {
    if (isset($_SESSION['last_runned_profile'])) {
        $profile = $_SESSION['last_runned_profile'];
    }
}
if ($profile == '') {
    $profile = 'default';
}
$profilename = ($profile != 'default' ? $profile : 'Default');
$eplconf = new EnabledPlugins_Config($profile);
$eplconf->load();
$conf_ok = $eplconf->hasSection("PLUGINS_DATASOURCES");
?>

<div id="magmi-profile" class="magmi-profile col-12 mb-4">
	<script type="text/javascript">var profile="<?php echo $profile; ?>";</script>
	<div class="card">
		<h3 class="card-header subtitle">
			<span>Profile (<?php echo $profilename; ?>)</span>
			<span class="float-right saveinfo<?php if (!$conf_ok) {
    echo 'log_warning';
} ?>" id="profileconf_msg">
			<?php
            if ($conf_ok) {
                echo 'Saved:' . $eplconf->getLastSaved('%c');
            } else {
                echo $profilename . 'Profile config not saved yet';
            }
            ?>
			</span>
		</h3>
		<div class="card-body">
			<form action="magmi_chooseprofile.php" method="POST" id="chooseprofile">
				<h5>Profile to configure</h5>
				<div class="formline row">
					<div class="col-12 col-md-6">
						<label>Current profile:</label>
						<select name="profile" onchange="$('chooseprofile').submit();">
							<option <?php if (null == $profile) {
                echo 'selected="selected"';
            } ?> value="default">Default</option>
							<?php foreach ($profilelist as $profname) { ?>
							<option <?php if ($profname == $profile) {
                echo 'selected="selected"';
            } ?> value="<?php echo $profname; ?>"><?php echo $profname; ?></option>
							<?php } ?>
						</select>
					</div>
					<div class="col-12 col-md-6">
						<label>Copy selected profile to:</label>
						<input type="text" name="newprofile">
					</div>
				</div>
				<input type="submit" class="btn btn-primary float-right mt-2" value="Copy profile & switch">
			<?php
            require_once('magmi_pluginhelper.php');
            $order = array('datasources','general','itemprocessors');
            $plugins = Magmi_PluginHelper::getInstance('main')->getPluginClasses($order);
            $pcats = array();
            foreach ($plugins as $k => $pclasslist) {
                foreach ($pclasslist as $pclass) {
                    // Invoke static method, using call_user_func (5.2 compat mode)
                    $pcat = call_user_func(array($pclass, 'getCategory'));
                    if (!isset($pcats[$pcat])) {
                        $pcats[$pcat] = array();
                    }
                    $pcats[$pcat][] = $pclass;
                }
            }
            ?>
			</form>
		</div>
	</div>
</div>

</div>
</div>

<div id="profile_cfg" class="container mb-4">
	<form action="" method="POST" id="saveprofile_form" class="row">
		<input type="hidden" name="profile" id="curprofile" value="<?php echo $profile; ?>">
		<?php foreach ($order as $k) { ?>
		<input type="hidden" id="plc_<?php echo strtoupper($k); ?>" value="<?php echo implode(',', $eplconf->getEnabledPluginClasses($k)); ?>" name="PLUGINS_<?php echo strtoupper($k); ?>:classes">
			<div class="col-12 mb-4">
				<div class="card">
					<h3 class="card-header">
						<span><?php echo ucfirst($k); ?></span>
					</h3>
					<?php if ($k == 'datasources') { ?>
					<?php $pinf = $plugins[$k]; ?>
					<?php if (count($pinf) > 0) { ?>
	    			<div class="card-body">
						<div class="pluginselect">
							<select name="PLUGINS_DATASOURCES:class" class="mb-2 pl_<?php echo $k; ?>">
							<?php
                            $sinst = null;
                            foreach ($pinf as $pclass) {
                                $pinst = Magmi_PluginHelper::getInstance($profile)->createInstance($k, $pclass);
                                if ($sinst == null) {
                                    $sinst = $pinst;
                                }
                                $pinfo = $pinst->getPluginInfo();
                                if ($eplconf->isPluginEnabled($k, $pclass)) {
                                    $sinst = $pinst;
                                } ?>
								<option value="<?php echo $pclass; ?>" <?php if ($sinst == $pinst) {
                                    echo 'selected="selected"';
                                } ?>><?php echo $pinfo['name'] . 'v' . $pinfo['version']; ?></option>
							<?php
                            } ?>
							</select>
						</div>
						<?php if (isset($pinfo["url"])) { ?>
						<div class="plugindoc">
							<a href="<?php echo $pinfo['url']; ?>" target="magmi_doc">documentation</a>
						</div>
						<?php } ?>
						<div class="pluginconfpanel selected"><?php echo $sinst->getOptionsPanel()->getHtml(); ?></div>
					</div>
					<?php } else {
                                $conf_ok = 0;
                                echo 'Magmi needs a datasource plugin, please install one';
                            } ?>
					<?php } else {
                                foreach ($pcats as $pcat => $pclasslist) { ?>
							<?php
                            $catopen = false;
                                $pinf = $plugins[$k]; ?>
							<?php
                            foreach ($pinf as $pclass) {
                                if (!in_array($pclass, $pclasslist)) {
                                    continue;
                                } else { ?> <?php if (!$catopen) {
                                    $catopen = true; ?>
					<div class="card-body">
						<h5><?php echo $pcat; ?></h5>
						<ul class="list-group"><?php
                                } ?>
						<?php
                        $pinst = Magmi_PluginHelper::getInstance($profile)->createInstance($k, $pclass);
                        $pinfo = $pinst->getPluginInfo();
                        $info = $pinst->getShortDescription();
                        $plrunnable = $pinst->isRunnable();
                        $enabled = $eplconf->isPluginEnabled($k, $pclass);
                        ?>
							<li class="list-group-item">
								<label class="form-check-label pluginselect">
									<?php if ($plrunnable[0]) { ?>
									<input type="checkbox" class="form-check-input pl_<?php echo $k?>" name="<?php echo $pclass; ?>"
									<?php if ($eplconf->isPluginEnabled($k, $pclass)) { ?> checked="checked" <?php } ?>>
									<?php } else { ?>
									<input type="checkbox" class="form-check-input pl_<?php echo $k?>" name="<?php echo $pclass; ?>" disabled="disabled">
									<?php } ?>
									<?php echo $pinfo["name"]; ?> <i>(v<?php echo $pinfo["version"]; ?>)</i>
								</label>

								<span class="badge badge-secondary" data-toggle="modal" data-target="#<?php echo preg_replace('#([^a-z0-9-])#', '-', strtolower(str_replace(' ', '-', $pinfo["name"]))); ?>">Info</span>

								<div class="plugininfo modal fade" id="<?php echo preg_replace('#([^a-z0-9-])#', '-', strtolower(str_replace(' ', '-', $pinfo["name"]))); ?>" tabindex="-1" role="dialog" aria-labelledby="exampleModalLongTitle" aria-hidden="true">
									<div class="plugininfohover modal-dialog" role="document">
										<div class="plugindata modal-content">
											<div class="modal-header">
												<?php $sp = isset($pinfo["sponsorinfo"]); foreach ($pinfo as $pik => $piv) { ?>
												<h5 class="modal-title" id="exampleModalLongTitle"><?php if ($pik == 'name') {
                            echo $piv;
                        } ?></h5>
												<?php } ?>
											</div>
											<div class="modal-body">
												<?php $sp = isset($pinfo["sponsorinfo"]); foreach ($pinfo as $pik => $piv) { ?>
												<div class="<?php if (isset($sp)) {
                            echo 'sponsored';
                        } ?>">
													<?php if ($pik == 'url') { ?>
													<span><b class="title"><?php echo $pik; ?>: </b>
														<a href="<?php echo $piv; ?>" target="_blank">Wiki entry</a>
													</span>
													<?php } elseif ($pik == 'sponsorinfo') { ?>
													<span class="sponsor">
														<b class="title">sponsor: </b>
														<?php if (isset($piv['url'])) { ?>
														<a href="<?php echo $piv['url'] ?>" target="_blank"><?php echo $piv['name']; ?></a>
														<?php } else { ?>
														<span><?php echo $piv['name']; ?></span>
														<?php } ?>
													</span>
													<?php } else { ?>
													<span><b class="title"><?php echo $pik; ?>:</b> <?php echo $piv; ?></span>
													<?php } ?>
												</div>
												<?php } ?>
												<div class="minidoc mt-2"><?php echo $info; ?></div>
											</div>
											<div class="modal-footer">
												<button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
											</div>
										</div>
										<?php if (!$plrunnable[0]) { ?>
										<div class="error"><pre><?php echo $plrunnable[1]; ?></pre></div>
										<?php } ?>
									</div>
								</div>

								<?php if (isset($pinfo['url'])) { ?>
								<div class="plugindoc float-right">
									<a href="<?php echo $pinfo['url']; ?>" class="btn btn-outline-secondary btn-sm" target="magmi_doc"><i class="fa fa-book" aria-hidden="true"></i> <span>Documentation</span></a>
								</div>
								<?php } ?>
								<div class="pluginconf float-right mr-1" <?php if (!$enabled) {
                            echo 'style="display: none;"';
                        } ?>>
									<a href="javascript:void(0)" class="btn btn-outline-primary btn-sm"><i class="fa fa-gear" aria-hidden="true"></i> <span>Configure</span></a>
								</div>
								<div class="pluginconfpanel"><?php if ($enabled) {
                            echo $pinst->getOptionsPanel()->getHtml();
                        } ?></div>
							</li>
							<?php } ?>
							<?php
                            } ?>
						</ul>
						<?php if ($catopen) { ?>
					</div>
					<?php } ?>
					<?php }
                            } ?>
				</div>
			</div>
		<?php } ?>
		<div class="col-12">
			<a id="saveprofile" class="actionbutton btn btn-primary float-right" href="javascript:void(0)"<?php if (!$conf_ok) {
                                echo 'disabled="disabled"';
                            } ?>><i class="fa fa-floppy-o" aria-hidden="true"></i> Save Profile (<?php echo $profilename; ?>)
			</a>
		</div>
	</form>
</div>

<div id="paramchanged" style="display: none">
	<div class="subtitle">
		<h3>Parameters changed</h3>
	</div>
	<div class="changedesc">
		<b>You changed parameters without saving profile, would you like to:</b>
	</div>
	<ul>
		<li><input type="radio" name="paramcr" value="saveprof">Save chosen Profile (<?php echo $profilename; ?>) with current parameters</input></li>
		<li><input type="radio" name="paramcr" value="applyp" checked="checked">Apply current parameters as profile overridewithout saving</input></li>
		<li><input type="radio" name="paramcr" value="useold">Discard changes &amp; apply last saved <?php echo $profilename; ?> profile values</input></li>
	</ul>
	<div class="actionbuttons">
		<a class="actionbutton" href="javascript:handleRunChoice('paramcr',comparelastsaved());" id="paramchangeok">Run with selected option</a> <a class="actionbutton" href="javascript:cancelimport();" id="paramchangecancel">Cancel run</a>
	</div>
</div>

<div id="pluginschanged" class="modal fade" style="display: none" tabindex="-1" role="dialog" aria-hidden="true">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header">	
				<h5 class="subtitle modal-title">Plugin selection changed</h5>
			</div>
			<div class="changedesc modal-body">
				<p><b>You changed selected plugins without saving profile, would you like to:</b></p>
				<input type="radio" name="plugselcr" value="saveprof" checked="checked">Save chosen Profile (<?php echo $profilename; ?>) with current parameters</input><br>
				<input type="radio" name="plugselcr" value="useold">Discard changes &amp; apply last saved <?php echo $profilename; ?> profile values</input>
			</div>
			<div class="actionbuttons modal-footer">
				<a id="plchangeok" class="actionbutton btn btn-primary" href="javascript:handleRunChoice('plugselcr',comparelastsaved());">Run with selected option</a>
				<a id="plchangecancel" class="actionbutton btn btn-secondary" href="javascript:cancelimport();">Cancel</a>
			</div>
		</div>
	</div>
</div>

<script type="text/javascript">
window.lastsaved = {};
handleRunChoice = function(radioname, changeinfo) {
    var changed = changeinfo.changed;
    var sval = $$('input:checked[type="radio"][name="' + radioname + '"]').pluck('value');
    if (sval == 'saveprof') {
        saveProfile(1, function() {
            $('runmagmi').submit();
        });
    }
    if (sval == 'useold') {
        $('runmagmi').submit();
    }
    if (sval == 'applyp') {
        changed.each(function(it) {
            $('runmagmi').insert({
                bottom: '<input type="hidden" name="' + it.key + '" value="' + it.value + '">'
            });
        });
        $('runmagmi').submit();
    }
}

cancelimport = function() {
	$('overlay').hide();
	$('pluginschanged').addClassName('hide');
	$j('#pluginschanged.hide').modal('hide');
};

updatelastsaved = function() {
    gatherclasses(['DATASOURCES', 'GENERAL', 'ITEMPROCESSORS']);
    window.lastsaved = $H($('saveprofile_form').serialize(true));
};

comparelastsaved = function() {
    gatherclasses(['DATASOURCES', 'GENERAL', 'ITEMPROCESSORS']);
    var curprofvals = $H($('saveprofile_form').serialize(true));
    var changeinfo = {
        changed: false,
        target: ''
    };
    var out = "";
    var diff = {};
    changeinfo.target = 'paramchanged';
    curprofvals.each(function(kv) {
        var lastval = window.lastsaved.get(kv.key);
        if (kv.value != lastval) {
            diff[kv.key] = kv.value;
            if (kv.key.substr(0, 8) == 'PLUGINS_') {
                changeinfo.target = 'pluginschanged';
            }
        }
    });

    changeinfo.changed = $H(diff);
    if (changeinfo.changed.size() == 0) {
        changeinfo.changed = false;
    }
    return changeinfo;
};

addclass = function(it, o) {
    if (it.checked) {
        this.arr.push(it.name);
    }
};

gatherclasses = function(tlist) {
    tlist.each(function(t, o) {
        var context = {
            arr: []
        };
        $$(".pl_" + t.toLowerCase()).each(addclass, context);
        var target = $("plc_" + t);
        target.value = context.arr.join(",");
    });
};

initConfigureLink = function(maincont) {
    var cfgdiv = maincont.select('.pluginconf');
    if (cfgdiv.length > 0) {
        cfgdiv = cfgdiv[0];
        var confpanel = maincont.select('.pluginconfpanel');
        confpanel = confpanel[0];
        cfgdiv.stopObserving('click');
        cfgdiv.observe('click', function(ev) {
            confpanel.toggleClassName('selected');
            confpanel.select('.ifield').each(function(it) {
                it.select('.fieldhelp').each(function(fh) {
                    fh.observe('click', function(ev) {
                        it.select('.fieldsyntax').each(function(el) {
                            el.toggle();
                        })
                    });
                });
            });
        });
    }
};

showConfLink = function(maincont) {
    var cfgdiv = maincont.select('.pluginconf');
    if (cfgdiv.length > 0) {

        cfgdiv = cfgdiv[0];
        cfgdiv.show();
    }
};

loadConfigPanel = function(container, profile, plclass, pltype) {
    new Ajax.Updater({
        success: container
    }, 'ajax_pluginconf.php', {
        parameters: {
            profile: profile,
            plugintype: pltype,
            pluginclass: plclass
        },
        evalScripts: true,
        onComplete: function() {
            showConfLink($(container.parentNode));
            initConfigureLink($(container.parentNode));
        }
    });
};

removeConfigPanel = function(container) {
    var cfgdiv = $(container.parentNode).select('.pluginconf');
    cfgdiv = cfgdiv[0];
    cfgdiv.stopObserving('click');
    cfgdiv.hide();
    container.removeClassName('selected');
    container.update('');
};

initAjaxConf = function(profile) {
    // foreach plugin selection
    $$('.pluginselect').each(function(pls) {
        var del = pls.firstDescendant();
        var evname = (del.tagName == "SELECT" ? 'change' : 'click');

        // Check the click
        del.observe(evname, function(ev) {
            var el = Event.element(ev);
            var plclass = (el.tagName == "SELECT") ? el.value : el.name;
            var elclasses = el.classNames();
            var pltype = "";
            elclasses.each(function(it) {
                if (it.substr(0, 3) == "pl_") {
                    pltype = it.substr(3);
                }
            });
            var doload = (el.tagName == "SELECT") ? true : el.checked;
            var targets = $(pls.parentNode).select(".pluginconfpanel");
            var container = targets[0];
            if (doload) {
                loadConfigPanel(container, profile, plclass, pltype);
            } else {
                removeConfigPanel(container);
            }
        });
    });
};

initDefaultPanels = function() {
    $$('.pluginselect').each(function(it) {
        initConfigureLink($(it.parentNode));
    });
    updatelastsaved();
};

saveProfile = function(confok, onsuccess) {
    gatherclasses(['DATASOURCES', 'GENERAL', 'ITEMPROCESSORS']);
    updatelastsaved();
    new Ajax.Updater('profileconf_msg',
        "magmi_saveprofile.php", {
            parameters: $('saveprofile_form').serialize('true'),
            onSuccess: function() {
                if (confok) {
                    onsuccess();
                } else {
                    $('profileconf_msg').show();
                }
            }
        });

};

initAjaxConf('<?php echo $profile?>');
initDefaultPanels();

$('saveprofile').observe('click', function() {
    saveProfile(<?php echo $conf_ok ? 1 : 0 ?>, function() {
        $('chooseprofile').submit();
    });
});

$('runmagmi').observe('submit', function(ev) {

    var ls = comparelastsaved();
    if (ls.changed !== false) {
        $('overlaycontent').update($(ls.target));
        $$('#overlaycontent > div').each(function(el) {
            el.show();
        });
		$('overlay').show();
		$('pluginschanged').addClassName('show');
		$j('#pluginschanged.show').modal('show');
        ev.stop();
    }
});
</script>
