<?php
$file = $_REQUEST["file"];
$f = @fopen($file, "r");
$err = error_get_last();
if ($f !== false)
{
    @fclose($f);
}
if ($err == null)
{
    // Extract the type of file which will be sent to the browser as a header
    $type = filetype($file);
    
    // Get a date and timestamp
    $today = date("F j, Y, g:i a");
    $time = time();
    
    // Send file headers
    header("Content-type: $type");
    header("Content-Disposition: attachment;filename=" . basename($file));
    header("Content-Transfer-Encoding: binary");
    header('Pragma: no-cache');
    header('Expires: 0');
    // Send the file contents.
    set_time_limit(0);
    readfile($file);
}
else
{
    print_r($err);
}
