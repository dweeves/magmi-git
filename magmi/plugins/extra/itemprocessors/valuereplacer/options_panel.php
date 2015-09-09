<div class="plugin_description">This plugin enables to change attribute
	values from datasource before they are handled by magmi. enter
	attributes to set new value for in replaced attributes field, separated
	by commas (,) when leaving the field, new fields will be inserted for
	filling new column names.</div>
<?php $clist=$this->fixListParam($this->getParam("VREP:columnlist"))?>

<ul class="formline">
	<li class="label">Replaced attributes</li>
	<li class="value"><input type="text" id="VREP:columnlist"
		name="VREP:columnlist" size="80" value="<?php echo $clist?>"
		onblur="vrep_mf.buildparamlist()"></input></li>
</ul>
<div id="VREP:columnsetup"></div>

<div class="formline ifield">
	<div class="fieldhelp"></div>
	<div class="fieldinfo">
		<div style="height: 24px">You can use "dynamic variables" to fill
			above fields.</div>
		<div class="fieldsyntax" style="display: none">
			<hr />

			<ul>
				<li>{item.[some item field]} and {{some expression}} are supported.</li>
				<li>[some item field] has to be present either as datasource column
					or default value setter/column mapper added column</li>
				<li>examples below assume sku=sku0 &amp; cost=8.00 &amp; margin=15
					(here margin may not even be an attribute)</li>
			</ul>
			<hr />
			<ul>
				<li><b>dynamic field Examples</b></li>
				<li>with sku in the list of replaced fields, if xxx-{item.sku} for
					replacing value , the sku inserted will be xxx-sku0</li>
				<li>you can put any number of {item.[some item field]} references in
					the replacing value</li>
			</ul>
			<hr />
			<ul>
				<li><b>Expression examples:</b></li>
				<li>lets say you want to calculate cost from cost &amp; margin</li>
				<li>just put {{ {item.cost}*(1+({item.margin}/100)) }} in the
					replaced value for price</li>
			</ul>
			<hr />
			<ul>
				<li><b>Advanced expressions</b></li>
				<li>expressions use php eval() function, so beware of what you're
					doing but it can be also very powerful as:</li>
				<li>{{ substr("{item.sku}",0,4) }}</li>
			</ul>
		</div>
	</div>
</div>
<script type="text/javascript">
var vr_vals=<?php echo tdarray_to_js($this,'VREP:columnlist','VREP')?>;
var vr_linetpl='<ul class="formline"><li class="label">New value for {fieldname}</li><li class="value"><input type="text" name="VREP:{fieldname.enc}" value="{value}"></input></li></ul>';
vrep_mf=new magmi_multifield('VREP:columnlist','VREP:columnsetup',vr_linetpl,vr_vals);
vrep_mf.buildparamlist();
</script>
