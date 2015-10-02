<?php
require_once("utils.php");
if (session_id()==null) {
    session_start();
}
/**
 * Created by PhpStorm.
 * User: seb
 * Date: 26/03/15
 * Time: 19:04
 */
function setMessage($type, $data, $target='_global')
{
    if (!isset($_SESSION["message"][$target])) {
        $_SESSION["message"][$target]=array();
    }
    if (!isset($_SESSION["message"][$target][$type])) {
        $_SESSION["message"][$target][$type]=array();
    }
    $_SESSION["message"][$target][$type][]=$data;
}

function clearMessages($target="_global")
{
    if (isset($_SESSION["message"][$target])) {
        $_SESSION["message"][$target]=array("OK"=>array(),
        "WARN"=>array(),
        "ERROR"=>array());
    }
}

function hasMessages($type, $target="_global")
{
    return isset($_SESSION["message"][$target]) && isset($_SESSION["message"][$target][$type]) && count($_SESSION["message"][$target][$type])>0;
}

function getMessages($type, $target="_global")
{
    return $_SESSION["message"][$target][$type];
}

function show_messages($target="_global")
{
    $mtypes = array("ERROR" => "danger", "WARN" => "warning", "OK" => "success");
    foreach ($mtypes as $mt => $mcl) {
        if (hasMessages($mt, $target)) {
            $msgs = getMessages($mt, $target);
            for ($i = 0; $i < count($msgs); $i++) {
                ?>
                    <div class="alert alert-<?php echo $mcl ?>" role="alert"><?php echo $msgs[$i] ?></div>
                <?php 
            }
        }
    }
    clearMessages($target);
}?>
