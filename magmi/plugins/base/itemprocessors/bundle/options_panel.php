<?php
/**
 * The MIT License (MIT)
 * Copyright (c) 2014 Limora Oldtimer GmbH & Co. KG
 *
 * Permission is hereby granted, free of charge, to any person obtaining
 * a copy of this software and associated documentation files (the
 * "Software"), to deal in the Software without restriction, including
 * without limitation the rights to use, copy, modify, merge, publish,
 * distribute, sublicense, and/or sell copies of the Software, and to
 * permit persons to whom the Software is furnished to do so, subject to
 * the following conditions:
 *
 * The above copyright notice and this permission notice shall be included
 * in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
 * OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
 * MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
 * NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS
 * BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN
 * ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN
 * CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 *
 */
?>
<div class="plugin_description">This plugins handles bundle item import
</div>

<ul class="formline">
	<li class="label" style="width: 360px"><label for="bndl_option_type">Default
			Option Type</label></li>
	<li class="value"><select id="bndl_option_type" name="BNDL:option_type">
			<option value="select"
				<?php if ($plugin->getConfiguredDefault('option', 'type') == 'select') { ?>
				selected="selected" <?php } ?>>Drop-down</option>
			<option value="radio"
				<?php if ($plugin->getConfiguredDefault('option', 'type') == 'radio') { ?>
				selected="selected" <?php } ?>>Radio Buttons</option>
			<option value="checkbox"
				<?php if ($plugin->getConfiguredDefault('option', 'type') == 'checkbox') { ?>
				selected="selected" <?php } ?>>Checkbox</option>
			<option value="multi"
				<?php if ($plugin->getConfiguredDefault('option', 'type') == 'multi') { ?>
				selected="selected" <?php } ?>>Multiple Select</option>
	</select></li>
</ul>

<ul class="formline">
	<li class="label" style="width: 360px"><label
		for="bndl_option_required">Default Option Is Required</label></li>
	<li class="value"><select id="bndl_option_required"
		name="BNDL:option_required">
			<option value="1"
				<?php if ($plugin->getConfiguredDefault('option', 'required') == '1') { ?>
				selected="selected" <?php } ?>>Yes</option>
			<option value="0"
				<?php if ($plugin->getConfiguredDefault('option', 'required') == '0') { ?>
				selected="selected" <?php } ?>>No</option>
	</select></li>
</ul>

<ul class="formline">
	<li class="label" style="width: 360px"><label
		for="bndl_option_position">Default Option Position</label></li>
	<li class="value"><input type="number" id="bndl_option_position"
		name="BNDL:option_position"
		value="<?php echo $plugin->getConfiguredDefault('option', 'position') ?>" />
	</li>
</ul>

<ul class="formline">
	<li class="label" style="width: 360px"><label
		for="bndl_sku_selection_qty">Default Selection Qty</label></li>
	<li class="value"><input type="number" id="bndl_sku_selection_qty"
		name="BNDL:sku_selection_qty"
		value="<?php echo $plugin->getConfiguredDefault('sku', 'selection_qty') ?>" />
	</li>
</ul>

<ul class="formline">
	<li class="label" style="width: 360px"><label
		for="bndl_sku_selection_can_change_qty">Default Selection User Defined
			Qty</label></li>
	<li class="value"><select id="bndl_sku_selection_can_change_qty"
		name="BNDL:sku_selection_can_change_qty">
			<option value="1"
				<?php if ($plugin->getConfiguredDefault('sku', 'selection_can_change_qty') == '1') { ?>
				selected="selected" <?php } ?>>Yes</option>
			<option value="0"
				<?php if ($plugin->getConfiguredDefault('sku', 'selection_can_change_qty') == '0') { ?>
				selected="selected" <?php } ?>>No</option>
	</select></li>
</ul>

<ul class="formline">
	<li class="label" style="width: 360px"><label for="bndl_sku_position">Default
			Selection Position</label></li>
	<li class="value"><input type="number" id="bndl_sku_position"
		name="BNDL:sku_position"
		value="<?php echo $plugin->getConfiguredDefault('sku', 'position') ?>" />
	</li>
</ul>

<ul class="formline">
	<li class="label" style="width: 360px"><label for="bndl_sku_is_default">Default
			Selection "Is Default"</label></li>
	<li class="value"><select id="bndl_sku_is_default"
		name="BNDL:sku_is_default">
			<option value="1"
				<?php if ($plugin->getConfiguredDefault('sku', 'is_default') == '1') { ?>
				selected="selected" <?php } ?>>Yes</option>
			<option value="0"
				<?php if ($plugin->getConfiguredDefault('sku', 'is_default') == '0') { ?>
				selected="selected" <?php } ?>>No</option>
	</select></li>
</ul>

<ul class="formline">
	<li class="label" style="width: 360px"><label
		for="bndl_sku_selection_price_value">Default Selection Price</label></li>
	<li class="value"><input type="number"
		id="bndl_sku_selection_price_value"
		name="BNDL:sku_selection_price_value"
		value="<?php echo $plugin->getConfiguredDefault('sku', 'selection_price_value') ?>" />
	</li>
</ul>

<ul class="formline">
	<li class="label" style="width: 360px"><label
		for="bndl_sku_selection_price_type">Default Selection Price Type</label></li>
	<li class="value"><select id="bndl_sku_selection_price_type"
		name="BNDL:sku_selection_price_type">
			<option value="0"
				<?php if ($plugin->getConfiguredDefault('sku', 'selection_price_type') == '0') { ?>
				selected="selected" <?php } ?>>Fixed</option>
			<option value="1"
				<?php if ($plugin->getConfiguredDefault('sku', 'selection_price_type') == '1') { ?>
				selected="selected" <?php } ?>>Percent</option>
	</select></li>
</ul>
