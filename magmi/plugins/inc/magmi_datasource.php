<?php
/**
 * Class Magmi_Datasource
 * @author dweeves
 *
 * This class enables to perform input format support for ingested data
 */
require_once("magmi_generalimport_plugin.php");

abstract class Magmi_DataSource extends Magmi_GeneralImportPlugin
{
    public function getColumnNames($prescan = false)
    {
    }

    public function getRecordsCount()
    {
    }

    public function getNextRecord()
    {
    }

    public function onException($e)
    {
    }
}
