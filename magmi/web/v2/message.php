<?php
require_once("utils.php");
if(session_id()==null)
{
    session_start();
}
/**
 * Created by PhpStorm.
 * User: seb
 * Date: 26/03/15
 * Time: 19:04
 */
$mtypes=array("ERROR"=>"danger","WARN"=>"warning","OK"=>"success");
foreach($mtypes as $mt=>$mcl)
{
    if(hasMessages($mt)) {
        $msgs = getMessages($mt);
        for ($i = 0; $i < count($msgs); $i++) {
            ?>
            <div class="alert alert-<?php echo $mcl?>" role="alert"><?php echo $msgs[$i]?></div>
        <?php }
    }
}
  clearMessages();
?>
