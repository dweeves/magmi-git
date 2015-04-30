<?php
/**
 * Created by PhpStorm.
 * User: seb
 * Date: 30/03/15
 * Time: 18:30
 */
require_once("../utils.php");
$conf=getSessionConfig();
$magdir=$conf->getMagentoDir();
$localxml=$magdir.'/app/etc/local.xml';
$entries=array("host","username","password","dbname");
if(file_exists($localxml))
{
    $doc=simplexml_load_file($localxml);
    $cnxp=$doc->xpath("//default_setup/connection");
    if(count($cnxp)>0)
    {
        $cnx=$cnxp[0];
        foreach($cnx->children() as $entry)
        {
            $en=$entry->getName();
            if(in_array($en,$entries))
            {
                $conf->set("DATABASE",$en,"".$entry);
            }
        }
    }
    $conf->save();
}