<?php
if(session_id()==null) {
    session_start();
    session_write_close();
}
if(!isset($_REQUEST["token"]) || !isset($_SESSION["token"]) || $_REQUEST["token"]!==$_SESSION["token"])
{

    header("HTTP/1.0 404 Not Found");
    echo "STK:".$_SESSION["token"];
    echo "RTK:".$_REQUEST["token"];
    exit;
}