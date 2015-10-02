<?php require_once('../utils.php') ?>
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
                array_push($pflist, 'Default');
                foreach ($pflist as $prof) {
                    ?>
                    <tr>
                        <td><?php echo $prof?></td>
                        <td>
                                <a href="profile_edit.php" title="Edit"><span aria-hidden="true" class="glyphicon glyphicon-pencil"></span></a>
                        </td>
                    </tr>
                <?php 
                }?>
            </tbody>
        </table>
</div>
</div>