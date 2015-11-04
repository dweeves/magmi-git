<?php
require_once("security.php");
require_once("../inc/magmi_statemanager.php");
$tid = $_REQUEST["traceid"];
$tracefile = Magmi_StateManager::getTraceFile();
$f = fopen($tracefile, "r");
$display = false;
$startout = false;
while (!feof($f)) {
    $line = fgets($f);

    if (preg_match("/--- TRACE :\s+(\d+).*?/", $line, $match)) {
        $trid = $match[1];
        if ($trid == $tid) {
            $startout = true;
            $display = true;
        }
    }
    if (preg_match("/--- ENDTRACE :\s+(\d+).*?/", $line, $match)) {
        $startout = false;
        break;
    }
    if ($startout) {
        echo '<p class="trace">' . $line . "</p>";
    }
}
fclose($f);
if (!$display) {
    echo "Trace not found";
}
