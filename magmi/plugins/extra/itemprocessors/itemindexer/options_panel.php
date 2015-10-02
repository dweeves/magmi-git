<div class="plugin_description">This plugin fills some magento index
	tables on the fly while importing item.</div>
<div>
    <?php $useurlendings=$this->getParam("OTFI:useurlending", 1)==1?>
	<ul class="formline">
		<li class="label">URL endings</li>
		<li class="value">Use Url Endings<select name="OTFI:useurlending">
                    <option value="1" <?php echo $useurlendings?"selected='selected'":"" ?>>Yes</option>
                    <option value="0" <?php echo !$useurlendings?"selected='selected'":"" ?>>No</option>
            </select>
            <input type="text" name="OTFI:urlending" value="<?php echo $this->getParam("OTFI:urlending", ".html")?>"></li>
	</ul>
	<ul class="formline">
		<li class="label">Use Categories in url</li>
		<li class="value">
 		<?php $usecat=$this->getParam("OTFI:usecatinurl", 1);?>
 		<select name="OTFI:usecatinurl">
				<option value="1" <?php if ($usecat==1) {
    ?> selected="selected"
					<?php 
}?>>Yes</option>
				<option value="0" <?php if ($usecat==0) {
    ?> selected="selected"
					<?php 
}?>>No</option>
		</select>
		</li>
	</ul>

</div>