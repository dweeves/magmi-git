<div class="plugin_description">
	This plugin enables to change column names from datasource before they
	are handled by magmi. enter columns to set new name for in mapped
	column list field, separated by commas (,) when leaving the field, new
	fields will be inserted for filling new column names. <b>You can put
		several values (comma separated) in the mapped column names,doing so ,
		the column mapper will replicate values of column to map to all mapped
		columns !!!</b>
</div>
<?php $clist=$this->fixListParam($this->getParam("CMAP:columnlist"))?>
<div>
	<ul class="formline">
		<li class="label">Mapped columns list</li>
		<li class="value"><input type="text" id="CMAP:columnlist"
			name="CMAP:columnlist" size="80" value="<?php echo $clist?>"
			onblur="cmap_mf.buildparamlist()"></input></li>
	</ul>
	<div id="CMAP:columnsetup"></div>
</div>
<script type="text/javascript">
var cm_vals=<?php echo tdarray_to_js($this,'CMAP:columnlist','CMAP')?>;
var cm_linetpl='<ul class="formline"><li class="label">New name for col {fieldname}</li><li class="value"><input type="text" name="CMAP:{fieldname.enc}" value="{value}"></input></li></ul>';
cmap_mf=new magmi_multifield('CMAP:columnlist','CMAP:columnsetup',cm_linetpl,cm_vals);
cmap_mf.buildparamlist();
</script>
