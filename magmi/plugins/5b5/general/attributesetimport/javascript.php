<script type="text/javascript">
<?php if ($withCsvOptions) {
    ?>
handle_auth=function()
	{
		if($('<?php echo $prefix ?>:remoteauth').checked)
		{
			$('<?php echo $prefix ?>:remoteauth').show();
		}
		else
		{
			$('<?php echo $prefix ?>:remoteauth').hide();
		}
	}

	$('<?php echo $prefix ?>:basedir').observe('blur',function()
			{
			new Ajax.Updater('<?php echo $prefix ?>:csvds_filelist','ajax_pluginconf.php',{
			parameters:{file:'csvds_filelist.php',
						plugintype:'datasources',
                        pluginclass:'<?php echo get_class($plugin)?>',
					    profile:'<?php echo $self->getConfig()->getProfile()?>',
					    '<?php echo $prefix ?>:basedir':$F('<?php echo $prefix ?>:basedir')}});
			});
	$('<?php echo $prefix ?>:malformed_cb').observe('click',function(ev){
		if($('<?php echo $prefix ?>:malformed_cb').checked)
		{
			$('<?php echo $prefix ?>:malformed').show();
		}
		else
		{
			$('<?php echo $prefix ?>:malformed').hide();
		}
	});
	$('<?php echo $prefix ?>:headerline').observe('blur',function()
	{
		var wellformed=($F('<?php echo $prefix ?>:headerline')=="1" || $F('<?php echo $prefix ?>:headerline')=="");
		if(wellformed)
		{
			$('<?php echo $prefix ?>:malformed_cb').checked=false;
			$('<?php echo $prefix ?>:malformed').hide();
			$('<?php echo $prefix ?>:headerline').value="";
		}
	});
	$('<?php echo $prefix ?>:importmode').observe('change',function()
			{
				if($F('<?php echo $prefix ?>:importmode')=='local')
				{
					$('<?php echo $prefix ?>:localcsv').show();
					$('<?php echo $prefix ?>:remotecsv').hide();
				}
				else
				{
					$('<?php echo $prefix ?>:localcsv').hide();
					$('<?php echo $prefix ?>:remotecsv').show();
				}
			});
	$('<?php echo $prefix ?>:remoteauth').observe('click',handle_auth);
	$('<?php echo $prefix ?>:remoteurl').observe('blur',handle_auth);
<?php 
} ?>

	$('<?php echo $prefix ?>:prune_cb').observe('click',function() {
		if($('<?php echo $prefix ?>:prune_cb').checked)
			$('<?php echo $prefix ?>:prune_opts').show();
		else
			$('<?php echo $prefix ?>:prune_opts').hide();
	});
	if($('<?php echo $prefix ?>:enable')) {
    	$('<?php echo $prefix ?>:enable').observe('click',function() {
    		if($('<?php echo $prefix ?>:enable_cb').checked) {
    			$('<?php echo $prefix ?>:enabled').show();
    		} else {
    			$('<?php echo $prefix ?>:enabled').hide();
    		}
    	});
	}
<?php if ($prefix == '5B5ASI') {
    ?>
	showHideAttributeGroups = function() {
		if($('<?php echo $prefix ?>:create_cb').checked || $('<?php echo $prefix ?>:update_cb').checked) {
			$('<?php echo $prefix ?>:attribute_groups').show();
		} else {
			$('<?php echo $prefix ?>:attribute_groups').hide();
		}
	};
	$('<?php echo $prefix ?>:create_cb').observe('click',showHideAttributeGroups);
	$('<?php echo $prefix ?>:update_cb').observe('click',showHideAttributeGroups);
<?php 
} ?>
</script>