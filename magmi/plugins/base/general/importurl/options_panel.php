<div class="plugin_description">
	This plugin provides a textarea that gives the wget or curl command
	line (or just url if you want) that will run magmi with current
	settings.
	<p>this area is refreshed each time you leave any field of this import
		config page.</p>
</div>
<select id="GETURL:mode">
	<option value="wget">wget</option>
	<option value="wget_auth">wget (authentified)</option>
	<option value="curl">curl</option>
	<option value="curl_auth">curl (authentified)</option>
	<option value="rawurl">just url</option>
	<option value="cli">magmi cli</option>
</select>

<div id="GETURL:urlcontainer">
	<textarea id="GETURL:url" cols="100" rows="5"></textarea>
</div>
<div class="fieldinfo"></div>
<script type="text/javascript">

magmi_getimporturl=function()
	{
		var mode=$('GETURL:mode').value;
		var old_action=$('runmagmi').action;
		$('runmagmi').action="./magmi_run.php";
		var url=$('runmagmi').action+'?'+Form.serializeElements([$('mode'),$('runprofile')])+'&engine=magmi_productimportengine:Magmi_ProductImportEngine';
		$('runmagmi').action=old_action;
		var content="";
		switch(mode)
		{
			case "wget_auth":
				url=url.replace("://","://your_username:your_pass@");
			case "wget":
				content='wget "'+url+'" -O /dev/null';
				break;
			case "curl_auth":
				url=url.replace("://","://your_username:your_pass@");
			case "curl":
				content='curl -o /dev/null "'+url+'"';
				break;
			case "rawurl":
				content=url;
				break;
			case "cli":
				content='magmi.cli.php -mode='+$F('mode')+' -profile='+$F('runprofile');
				break;
			default:
				content=url;
		}
		$('GETURL:url').update(content);
	}


	$('runmagmi').getElements().each(function(it){
				it.observe('blur',magmi_getimporturl);
		});
	$('GETURL:mode').observe('change',magmi_getimporturl);
	magmi_getimporturl();



</script>