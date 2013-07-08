<?php
require_once("../inc/magmi_statemanager.php");
$tid=$_REQUEST["traceid"];
$tracefile=Magmi_StateManager::getTraceFile();
$c=file_get_contents($tracefile);
if(preg_match("/---- TRACE : $tid -----(.*?)---- ENDTRACE : $tid -----/msi",$c,$match))
{
echo nl2br(trim($match[1]));
}
else
{
	echo "Trace not found";
}