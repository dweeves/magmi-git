<?php
session_start();

function extractZipDir($zip, $bdir, $zdir)
{
    $files = array();
    for ($i = 0; $i < $zip->numFiles; $i++)
    {
        $entry = $zip->getNameIndex($i);
        if (preg_match("|^$zdir/(.*)|", $entry, $matches))
        {
            if ($matches[1] == '')
            {
                $zip->deleteIndex($i);
            }
            else
            {
                $zip->renameIndex($i, $matches[1]);
                // Add the entry to our array if it in in our desired directory
                $files[] = $matches[1];
            }
        }
    }
    
    if (count($files) > 0)
    {
        $ok = $zip->extractTo($bdir, $files);
    }
    else
    {
        $ok = false;
    }
    return $ok;
}

unset($_SESSION["magmi_install_error"]);
$zip = new ZipArchive();
$res = $zip->open($_FILES["magmi_package"]["tmp_name"]);
try
{
    $info = $zip->statName('magmi/conf/magmi.ini.default');
    
    if ($res === TRUE && $info !== FALSE)
    {
        $ok = extractZipDir($zip, dirname(dirname(__FILE__)), "magmi");
        $zip->close();
        
        $_SESSION["magmi_install"] = array("info","Magmi updated");
    }
    else
    {
        $zip->close();
        $_SESSION["magmi_install"] = array("error","Invalid Magmi Archive");
    }
    if (!$ok)
    {
        $_SESSION["magmi_install"] = array("error","Cannot unzip Magmi Archive");
    }
    session_write_close();
}
catch (Exception $e)
{
    session_write_close();
    die($e->getMessage());
}
header("Location: ./magmi.php");