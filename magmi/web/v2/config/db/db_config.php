<?php
/**
 * Created by PhpStorm.
 * User: seb
 * Date: 30/03/15
 * Time: 18:55
 */
$conf=getSessionConfig();
$dbconf=$conf->get('DATABASE','connectivity');
?>
<h2>DataBase Configuration</h2>
<ul class="nav nav-pills">
    <li class="<?php echo $dbconf=='localxml' ?'active':'' ?>"><a href="javascript:void(0)" id="dbconf_localxml" class="dbconflink">Using local.xml</a></li>
    <li class="<?php echo $dbconf=='net' ? 'active' : '' ?>"><a href="javascript:void(0)" id="dbconf_net" class="dbconflink">Host/Port</a></li>
    <li class="<?php echo $dbconf=='socket' ? 'active' : '' ?>"><a href="javascript:void(0)" id="dbconf_socket" class="dbconflink">Unix Socket</a></li>
</ul>
<div id="dbconf_content">
    <?php require_once("dbconf_".$dbconf.".php");?>
</div>
<script type="text/javascript">
    $(document).ready()
    {
        $('a.dbconflink').each(function()
            {
                var linkid=$(this).attr("id");
                var scname=linkid.substr(7);
                $(this).click(function()
                    {
                        $('a.dbconflink').parent().removeClass('active');
                        $(this).parent().addClass('active');
                        $('#dbconf_content').load("db/dbconf_"+scname+".php");
                    }
                );
            }
        )
    }
</script>