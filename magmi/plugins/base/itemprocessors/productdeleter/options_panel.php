<div class="plugin_description">This plugins should be used to delete
	existing products</div>
<ul class="formline">
	<li><input type="checkbox" name="PDEL:delsimples"
		<?php if($this->getParam("PDEL:delsimples",false)==true){?>
		checked="checked" <?php }?>>Delete children products</li>

</ul>