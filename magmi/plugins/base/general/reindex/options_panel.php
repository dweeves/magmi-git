
<div class="plugin_description">This plugin calls magento reindex script
	via calling php cli. please ensure security configuration enable
	"shell_exec()" calls from php</div>

<div class="formline">
	<span class="label">PHP CLI command</span> <span class="value"><input
		type="text" name="REINDEX:phpcli"
		value="<?php echo $this->getParam("REINDEX:phpcli", "php")?>"></input></span>
</div>
<hr />
<input type="hidden" name="REINDEX:indexes" id="indexes"
	value="<?php echo $this->getParam("REINDEX:indexes")?>"></input>
<div>
	<a name="REINDEX:config"></a> <span>Indexing:</span><a
		href="#REINDEX:config" onclick="fcheck(1);">All</a>&nbsp;<a
		href="#REINDEX:config" onclick="fcheck(0)">None</a>
	<ul>
	<?php
$idxarr = explode(",", $this->_plugin->getIndexList());
$indexes = explode(",", $this->getParam("REINDEX:indexes"));
foreach ($idxarr as $indexname) {
    ?>
		<li><input type="checkbox" name="<?php echo $indexname?>"
			class="_magindex" <?php if (in_array($indexname, $indexes)) {
    ?>
			checked=checked <?php 
}
    ?>><?php echo $indexname?></li>
	<?php 
}?>
	</ul>
</div>
<script type="text/javascript">
		getIndexes=function()
		{
			var outs=[];
			$$('._magindex').each(function(it){if(it.checked){outs.push(it.name)}});
			return outs.join(",");
		};



		fcheck=function(t)
		{
			$$('._magindex').each(function(it){it.checked=t});
			updateIndexes();

		}

		updateIndexes=function()
		{
			$('indexes').value=getIndexes();
		}

		$$('._magindex').each(function(it){it.observe('click',updateIndexes)});
	</script>