<div class="plugin_description">This plugins handles configurable import
</div>

<ul class="formline">
	<li class="label" style="width: 360px">Perform simples/configurable
		link</li>
	<li class="value"><select name="CFGR:nolink">
			<option value="0" <?php if ($this->getParam("CFGR:nolink",0)==0){?>
				selected="selected" <?php }?>>Yes</option>
			<option value="1" <?php if ($this->getParam("CFGR:nolink",0)==1){?>
				selected="selected" <?php }?>>No</option>
	</select></li>
</ul>
<ul class="formline">
	<li class="label" style="width: 360px">auto match simples skus before
		configurable</li>
	<li class="value"><select name="CFGR:simplesbeforeconf">
			<option value="0"
				<?php if ($this->getParam("CFGR:simplesbeforeconf")==0){?>
				selected="selected" <?php }?>>No</option>
			<option value="1"
				<?php if ($this->getParam("CFGR:simplesbeforeconf")==1){?>
				selected="selected" <?php }?>>Yes</option>
	</select></li>
</ul>
<ul class="formline">
	<li class="label">Force simples visibility</li>
	<li class="value">
<?php $v=$this->getParam("CFGR:updsimplevis",0)?>
<select name="CFGR:updsimplevis">
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

<ul class="formline">
	<li class="label" style="width: 360px">Auto assign images to configurable product</li>
	<?php $v=$this->getParam("CFGR:addsimpleimages",0)?>
	<li class="value"><select name="CFGR:addsimpleimages">
			<option value="0" <?php if ($v==0){?> selected="selected" <?php }?>>Disable</option>
			<option value="1" <?php if ($v==1){?> selected="selected" <?php }?>>Gallery Only</option>
			<option value="2" <?php if ($v==2){?> selected="selected" <?php }?>>All</option>
	</select></li>
</ul>
<div class="fieldinfo" >If 'All' is selected, it will assign the base image of the first associated product to be the base image, thumbnail and small image of the configurable product, and add all gallery images of associated products to the gallery of configurable product.</div>

<ul class="formline">
	<li class="label" style="width:300px; margin-left:60px">Back Image Label support</li>
	<?php $v=$this->getParam("CFGR:backimage",0)?>
	<li class="value"><select name="CFGR:backimage">
			<option value="0" <?php if ($v==0){?> selected="selected" <?php }?>>No</option>
			<option value="1" <?php if ($v==1){?> selected="selected" <?php }?>>Yes</option>
	</select></li>
	<div class="clear"></div>
</ul>
<div class="fieldinfo" style="margin-left:60px">Depends on the Auto Assign Images option. If yes, When auto adding images to the gallery of configurable, it will clear any existing 'back' label and set the label of the base image of the second associated product to 'back'. The labels of simple products will not be affected. </div>

