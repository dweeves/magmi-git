<?php
require_once ("magmi_plugin.php");

abstract class Magmi_GeneralImportPlugin extends Magmi_Plugin
{

    public function beforeImport()
    {
        return true;
    }

    public function afterImport()
    {
        return true;
    }
}