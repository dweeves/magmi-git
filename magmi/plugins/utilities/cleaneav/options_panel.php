<div class="plugin_description">This plugins checks &amp; deletes
	unnecessary values in the magento EAV tables.</div>
<div class="actionbutton">
	<a href="javascript:checkValues()">Check values</a>
</div>
<div id="stats">No check done yet.</div>
<script type="text/javascript">
checkValues=function()
{
	new Ajax.Updater('stats','ajax_pluginconf.php',{
	parameters:{file:'stats_panel.php',
						plugintype:'utilities',
					    pluginclass:'<?php echo get_class($this->_plugin)?>',
					    profile:'<?php echo $this->getConfig()->getProfile()?>',
					    engine:'magmi_utilityengine:Magmi_UtilityEngine'}});
}
</script>
