<?php
    require_once('../../inc/utils.php');
    $conf=getSessionConfig();
    $dbhost=$conf->get('DATABASE','host','localhost');
    $dbport=$conf->get('DATABASE','port','3306');
    $dbuser=$conf->get('DATABASE','user','');
    $dbpass=$conf->get('DATABASE','password','');
    $dbname=$conf->get('DATABASE','dbname','');
    $dbprefix=$conf->get('DATABASE','prefix','');
  ?>
<div class="bs-callout bs-callout-info">
    <h4>Host/Port Connectivity</h4>

    <p>Magmi will use manually entered informations to connect with magento Database</p>
    <p>Please fill the following fields and click on the "save" button.</p>
    <p>The connection will be tested on save.</p>
</div>

<form role="form" id="dbnet_settings_form" method="POST" action="javascript:void(0)">
    <div class="input-group">
            <span class="input-group-addon" id="dbconf_net_host_label">Host</span>
            <input type="text" id="dbconf_net_host" name="host" class="form-control"
                   placeholder="DB Server Name" aria-describedby="dbconf_net_host_label" value="<?php echo $dbhost?>">
        </div>

    <div class="input-group">
         <span class="input-group-addon" id="dbconf_net_port_label">Port</span>
         <input type="text" id="dbconf_net_dbname" name="port" class="form-control"
                placeholder="DB Server Port" aria-describedby="dbconf_net_port_label" value="<?php echo $dbport?>">
     </div>

 <div class="input-group">
         <span class="input-group-addon" id="dbconf_net_user_label">User Name</span>
         <input type="text" id="dbconf_net_dbname" name="user" class="form-control"
                placeholder="User name" aria-describedby="dbconf_net_user_label" value="<?php echo $dbuser?>">
     </div>
 <div class="input-group">
         <span class="input-group-addon" id="dbconf_net_password_label">Password</span>
         <input type="password" id="dbconf_net_dbname" name="password" class="form-control"
                placeholder="Password" aria-describedby="dbconf_net_password_label" value="<?php echo $dbpass?>">
     </div>

    <div class="input-group">
             <span class="input-group-addon" id="dbconf_net_dbname_label">Database name</span>
             <input type="text" id="dbconf_net_dbname" name="dbname" class="form-control"
                    placeholder="Database Name" aria-describedby="dbconf_net_dbname_label" value="<?php echo $dbname?>">
         </div>

<div class="input-group">
         <span class="input-group-addon" id="dbconf_net_dbprefix_label">Table Prefix</span>
         <input type="text" id="dbconf_net_dbprefix" name="table_prefix" class="form-control"
                placeholder="Table Prefix (leave blank if None)" aria-describedby="dbconf_net_dbprefix_label" value="<?php echo $dbprefix?>">
     </div>
    <button class="btn btn-outline right" id="save_db_settings_btn">Save DB Settings</button>
</form>
<script type="text/javascript">
    $(document).ready(function(){
    $('#save_db_settings_btn').click(function()
        {
            $.post('db/save_db_conf.ajax.php',$('#dbnet_settings_form').serialize());
        }
    );});
</script>