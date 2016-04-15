
<div class="plugin_description">
	This plugin enables magmi to generate items from a json template.<br/>
	The template is compatible with value replacer syntax (expressions & field replacement)
</div>
<div>
	<ul class="formline">
		<li class="label">Number of items to generate</li>
		<li class="value"><input name="ITG:nbitems" value="<?php echo $this->getParam("ITG:nbitems")?>" type="text"/></li>
	</ul>

	<ul class="formline">
		<li class="label">JSON Template</li>
		<li class="value"><textarea name="ITG:template" rows="20" cols="50"><?php echo $this->getParam("ITG:template");?></textarea></li>
	</ul>
	<ul class="formline"></ul>
</div>
