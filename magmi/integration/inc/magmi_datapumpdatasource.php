<?php

/**
 * This is a fake datasource for datapump, it just does nothing ;)
 * @author dweeves
 *
 */
class Magmi_DatapumpDS extends Magmi_Datasource
{
    public function getPluginInfo()
    {
        return array("name"=>"DataPump Datasource","author"=>"Dweeves","version"=>"1.0.0");
    }
}
