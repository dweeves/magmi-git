<?php
session_start();
unset($_SESSION["magmi_install_error"]);
$zip = new ZipArchive();
$res = $zip->open($_FILES["magmi_package"]["tmp_name"]);
try
{
	if ($res === TRUE) 
    {
         $zip->extractTo("../..");
         $zip->close();
         $_SESSION["magmi_install"]="OK";
    } 
    else 
    {
    	$_SESSION["magmi_install"]=array("ERROR"=>"Invalid Magmi Archive");
    }
    session_write_close();
}
catch(Exception $e)
{
	session_write_close();
	die($e->getMessage());
}
header("Location: ./magmi.php");