<div class="bs-callout bs-callout-info">
    <h4>Host/Port Connectivity</h4>

    <p>Magmi will use manually entered informations to connect with magento Database</p>
    <p>Please fill the following fields and click on the "save" button.</p>
    <p>The connection will be tested on save.</p>
</div>

<form role="form">
    <div class="input-group">
         <span class="input-group-addon" id="dbconf_net_dbname_label">Database name</span>
         <input type="text" id="magconf" name="magconf" class="form-control"
                placeholder="leave blank to reset to default" aria-describedby="dbconf_net_dbname_label" value="<?php echo $cf ?>">
     </div>
</form>