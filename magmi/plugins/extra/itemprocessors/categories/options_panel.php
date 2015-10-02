<div class="plugin_description">
	This plugin let you create categories on the fly. it enable creation or
	use of full category tree. multiple categories supported :)
	<p>
		the column (in csv or mapped) should be <b>categories</b>
	</p>
	<div class="fieldinfo">
		<p>"light" syntax for the values:
			level1_category/level2_category/level3_category,level1_category2/level2_category2</p>
		<p>"verbose" syntax for the values:</p>
		<p>all category levels separated by configurable tree separator ,
			defaulting to /</p>
		<p>in each level you can put [category name]:[x]:[y]:[z] (each of x,y
			or z being optional) with</p>
		<ul>
			<li>x: 0 or 1 , is_active</li>
			<li>y: 0 or 1 , is_anchor</li>
			<li>x: 0 or 1 , include_in_menu</li>
		</ul>
	</div>
</div>
<div class="formline">
<?php $lastonly=$this->getParam("CAT:lastonly", 0)?>
<span>Assign product to :</span><select name="CAT:lastonly">
		<option value="0" <?php if ($lastonly==0) {
    ?> selected="selected"
			<?php 
}?>>all categories in tree</option>
		<option value="1" <?php if ($lastonly==1) {
    ?> selected="selected"
			<?php 
}?>>last category of each branch</option>
	</select>
	<div class="fieldinfo">When checked, this options will assign product
		only to the categories that are located at the last level of the
		defined trees</div>
</div>
<div class="formline">
	<span class="label">Tree level separator:</span> <span class="value"><input
		type="text" name="CAT:treesep" maxlength=3 size=3
		" value="<?php echo $this->getParam("CAT:treesep", "/")?>"></input></span>
</div>
<div class="formline">
	<span>base category tree:</span><input type="text" name="CAT:baseroot"
		size="80" value="<?php echo $this->getParam("CAT:baseroot", "")?>"></input>
	<div class="fieldinfo">
		this enable you to import the categories prepending a base root tree
		to the values found in csv (use same syntax as described above)<br />
		<b>IMPORTANT , use tree level separator defined above in case of
			multilevel base tree</b>
	</div>
</div>
<div class="formline">
	<span>url ending:</span><input type="text" name="CAT:urlending"
		size="80"
		value="<?php echo $this->getParam("CAT:urlending", ".html")?>"></input>
	<div class="fieldinfo">Choose what url ending to put on category page
		(defaults to .html)</div>
</div>

