<div class="plugin_description">This plugin is made to limit magmi
	import to selected record ranges or matching values. ranges are ranges
	of rows to be imported filters are regexps or strings that if matched
	will exclude record from import</div>

<div class="ifield">
	<span class="">Column filter:</span><input type="text"
		name="LIMITER:col_filter" size="80"
		value="<?php echo $this->getParam("LIMITER:col_filter")?>"></input>
	<div class="fieldhelp"></div>
	<div class="fieldinfo">
		This field defines what columns should be imported
		<div class="fieldsyntax" style="display: none">
			<pre>
You should put column names, comma separated ie : sku,qty
</pre>
		</div>
	</div>
</div>

<div class="ifield">
	<span class="">Limiter ranges:</span><input type="text"
		name="LIMITER:ranges" size="80"
		value="<?php echo $this->getParam("LIMITER:ranges")?>"></input>
	<div class="fieldhelp"></div>
	<div class="fieldinfo">
		This field defines what lines should be imported
		<div class="fieldsyntax" style="display: none">
			<pre>
1-100 : for the first 100 records of csv
100-  : for all records after 100 (including 100th)
1-10,40-50,67,78,89 : for records 1 to 10,40 to 50 , 67 , 78 &amp; 89
</pre>
		</div>
	</div>
</div>
<div class="ifield">

	<span class="">Limiter filters:</span><input type="text"
		name="LIMITER:filters" size="80"
		value="<?php echo $this->getParam("LIMITER:filters")?>"></input>
	<div class="fieldhelp"></div>
	<div class="fieldinfo">
		This field defines what content should not be imported with a regexp
		like syntax.
		<div class="fieldsyntax" style="display: none">
			<pre>
sku::00.*  : exclude all skus that begin with 00
!name::.*blue.* : exclude all items with name not blue (see the  ! before the "name" field to negate the filter)
sku:00.*;;!name::.*blue.* : exclude all items with skus that begin with 00 which name does not contain blue
</pre>
		</div>
	</div>
</div>
