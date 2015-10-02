<?php
if (session_id()==null) {
    session_start();
    $CLOSE_AFTER_SECURITY_CHECK = true;
} else {
    $CLOSE_AFTER_SECURITY_CHECK = false;
}
if (!isset($_REQUEST["token"]) || !isset($_SESSION["token"]) || $_REQUEST["token"]!==$_SESSION["token"]) {
    header("HTTP/1.0 404 Not Found");
    echo "STK:".$_SESSION["token"];
    echo "RTK:".$_REQUEST["token"];
    exit;
}
if ($CLOSE_AFTER_SECURITY_CHECK) {
    session_write_close();
}
