<h2>Magmi Base Configuration File</h2>
<?php require_once('../message.php');?>
<?php $custconf=isset($_SESSION['MAGMI_CONFIG_FILE']);?>
<ul class="nav nav-pills">
     <li class="<?php echo $custconf?'':'active'?>"><a href="javascript:void(0)" id="stdconf_btn">Standard (conf/magmi.ini)</a></li>
     <li class="<?php echo $custconf?'active':''?>"><a href="javascript:void(0)" id="custconf_btn">Custom conf file</a></li>
</ul>

<div class="<?php echo !$custconf?'collapse':''?>" id="magmi_custom_conf">
<div class="input-group">
   <span class="input-group-addon" id="magmiconf">Custom Magmi Configuration</span>
 <input type="text" id="magconf" name="magconf" class="form-control" placeholder="leave blank to reset to default" aria-describedby="magmiconf" value="<?php echo $cf?>">
</div>
</div>
<div id="chooseconf_msg"><?php show_messages("magmiconf");?></div>
<div class="bs-callout bs-callout-info">
        <h4>Magmi Base Configuration Files</h4>
        <p>Magmi has its standard config file (&lt;magmi dir&gt;/conf/magmi.ini), but it can also use alternate ones.</p>
           In the latter case, magmi would consider the "conf" folder , containing also profiles, to be the parent folder
            of the alternate config file.
        </p>
        <p>All settings that would be defined in the following Setup sections would be saved in the chosen config file.</p>
    </div>
<script>
    $('#custconf_btn').click(function(){
        $('#custconf_btn').parent('li').addClass('active');
        $('#stdconf_btn').parent('li').removeClass('active');
        $('#magmi_custom_conf').show();
    })
    $('#stdconf_btn').click(function()
    {
        $('#stdconf_btn').parent('li').addClass('active');
        $('#custconf_btn').parent('li').removeClass('active');
        $.post('magmi_changeconf.ajax.php',{'magmiconf':''});
        $('#magmi_custom_conf').hide();
    });
</script>