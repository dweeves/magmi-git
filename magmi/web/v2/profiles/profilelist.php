<div class="panel panel-default">
    <div class="panel-heading">Magmi Profiles</div>
        <div class="panel-body">
            <p>Magmi profiles define which magmi plugins you want to use during a Magmi import.
            Within each profile,Each plugin can be configured independently.</p>
            <p>It is recommended to create one profile per import type</p>
        </div>
        <table class="table">
            <tbody>
            <?php
                $conf=getSessionConfig();
                $pflist=$conf->getProfileList();
                array_push($pflist,'Default');
                foreach($pflist as $prof){?>
                    <tr>
                        <td><?php echo $prof?></td>
                        <td class="profactions">
                                <a href="javascript:void(0)" data-profilename="<?php echo $prof?>" data-action="summary" title="Summary"><span aria-hidden="true" class="glyphicon glyphicon-eye-open"></span></a>

                                <a href="javascript:void(0)" data-profilename="<?php echo $prof?>" data-action="edit" title="Configure"><span aria-hidden="true" class="glyphicon glyphicon-edit"></span></a>
                                <a href="javascript:void(0)" data-profilename="<?php echo $prof?>" data-action="run" title="Run"><span aria-hidden="true" class="glyphicon glyphicon-circle-arrow-right"></span></a>
                                <a href="javascript:void(0)" data-profilename="<?php echo $prof?>" data-action="delete" title="Delete"><span aria-hidden="true" class="glyphicon glyphicon-trash"></span></a>
                        </td>
                      </tr>
                <?php }?>
            </tbody>
        </table>
    <form role="form" class="inline-form profactionform" method="post" id="doaction_form" action="profiles/do_profile_action.php">
    <input type="hidden" name="profilename" value="" id="profactionform_name"/>
    <input type="hidden" name="profaction" value="" id="profactionform_action"/>
    </form>

</div>
</div>
<script type="text/javascript">
       $('td.profactions a').each(function () {
           $(this).click(function ()
           {
            $('#profactionform_name').val($(this).data('profilename'));
            $('#profactionform_action').val($(this).data('action'));
            $('#doaction_form').submit();
           })
       });
</script>