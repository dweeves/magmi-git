<?php
/**
 * The MIT License (MIT)
 * Copyright (c) 2012 Alpine Consulting, Inc
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
<div class="plugin_description">This plugins handles grouped item import
</div>

<ul class="formline">
	<li class="label" style="width: 360px">Perform simples/group link</li>
	<li class="value"><select name="APIGRP:nolink">
			<option value="0" <?php if ($this->getParam("APIGRP:nolink",0)==0){?>
				selected="selected" <?php }?>>Yes</option>
			<option value="1" <?php if ($this->getParam("APIGRP:nolink",0)==1){?>
				selected="selected" <?php }?>>No</option>
	</select></li>
</ul>
<ul class="formline">
	<li class="label" style="width: 360px">auto match simples skus before
		grouped</li>
	<li class="value"><select name="APIGRP:groupedbeforegrp">
			<option value="0"
				<?php if ($this->getParam("APIGRP:groupedbeforegrp")==0){?>
				selected="selected" <?php }?>>No</option>
			<option value="1"
				<?php if ($this->getParam("APIGRP:groupedbeforegrp")==1){?>
				selected="selected" <?php }?>>Yes</option>
	</select></li>
</ul>
<ul class="formline">
	<li class="label">Force simples visibility</li>
	<li class="value">
<?php $v=$this->getParam("APIGRP:updgroupedvis",0)?>
<select name="APIGRP:updgroupedvis">
			<option value="0" <?php if($v==0){?> selected="selected" <?php }?>>No</option>
			<option value="1" <?php if($v==1){?> selected="selected" <?php }?>>Not
				Visible Individually</option>
			<option value="2" <?php if($v==2){?> selected="selected" <?php }?>>Catalog</option>
			<option value="3" <?php if($v==3){?> selected="selected" <?php }?>>Search</option>
			<option value="4" <?php if($v==4){?> selected="selected" <?php }?>>Catalog,
				Search</option>
	</select>
	</li>
</ul>
