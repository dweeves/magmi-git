<?php $clist=$this->fixListParam($this->getParam("DEFAULT:columnlist"))?>
<div class="plugin_description">This plugin enables to set some default
	item values if not found in input source. enter columns to set default
	value for in default attribute list field, separated by commas (,) when
	leaving the field, new fields will be inserted for filling default
	values.</div>
<div>
	<ul class="formline">
		<li class="label">Default attribute list</li>
		<li class="value"><input type="text" id="DEFAULT:columnlist"
			name="DEFAULT:columnlist" size="80" value="<?php echo $clist?>"
			onblur="default_mf.buildparamlist()"></input></li>
	</ul>
	<div style="position: relative">
		<div id="DEFAULT:columnsetup"></div>
	</div>
</div>
<script type="text/javascript">
var df_vals=<?php echo tdarray_to_js($this,'DEFAULT:columnlist','DEFAULT')?>;
var df_linetpl='<ul class="formline"><li class="label">Default {fieldname}</li><li class="value"><input type="text" name="DEFAULT:{fieldname.enc}" value="{value}"></input></li></ul>';
default_mf=new magmi_multifield('DEFAULT:columnlist','DEFAULT:columnsetup',df_linetpl,df_vals);
default_mf.buildparamlist();
</script>
