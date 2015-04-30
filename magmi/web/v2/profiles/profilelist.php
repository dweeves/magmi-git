<?php
require_once('profile_utils.php');
$profdata=buildProfilesPluginList();
?>
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
                foreach($profdata as $prof=>$pldata){?>
                    <tr>
                        <td><?php echo $prof?></td>
                        <td class="profactions">
                                <a href="javascript:void(0)" data-profilename="<?php echo $prof?>" data-url="profile_summary.ajax.php" data-toggle="modal" data-target="#summaryModal" data-action="summary" title="Summary"><span aria-hidden="true" class="glyphicon glyphicon-eye-open"></span></a>
                                <a href="javascript:void(0)" data-profilename="<?php echo $prof?>" data-action="edit" title="Configure"><span aria-hidden="true" class="glyphicon glyphicon-edit"></span></a>
                            <?php if(isset($pldata["datasource"])){?>
                                <a href="javascript:void(0)" data-profilename="<?php echo $prof?>" data-action="run" title="Run"><span aria-hidden="true" class="glyphicon glyphicon-circle-arrow-right"></span></a>
                             <?php }?>
                                <a href="javascript:void(0)" data-profilename="<?php echo $prof?>" data-action="delete" title="Delete"><span aria-hidden="true" class="glyphicon glyphicon-trash"></span></a>
                        </td>
                      </tr>
                <?php }?>
            </tbody>
        </table>
    <form role="form" class="inline-form profactionform" method="post" id="doaction_form" action="do_profile_action.php">
    <input type="hidden" name="profilename" value="" id="profactionform_name"/>
    <input type="hidden" name="profaction" value="" id="profactionform_action"/>
    </form>
    <!-- Modal -->
    <div class="modal fade" id="summaryModal" tabindex="-1" role="dialog" aria-labelledby="summary" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            <h4 class="modal-title" id="summary">Profile summary</h4>
          </div>
          <div class="modal-body" id="prof_summary_body">
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
          </div>
        </div>
      </div>
    </div>
</div>
</div>
<script type="text/javascript">
       $('td.profactions a').each(function () {
           $(this).click(function ()
           {
               var target=$(this).data('target');
               if(target==undefined) {
                   var action=$(this).data('action');

                   $('#profactionform_name').val($(this).data('profilename'));
                   $('#profactionform_action').val(action);
                   $('#doaction_form').submit();
               }
               else
               {
                   var title=$(this).data('profilename')+" "+$(this).attr('title');
                   $(target+' .modal-title').html(title);
                   $(target+' .modal-body').load($(this).data('url'),{'profilename':$(this).data('profilename')});
               }
           })
       });
</script>