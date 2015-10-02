<?php

class Magmi_ImportUrlPlugin extends Magmi_GeneralImportPlugin
{
    public function getPluginInfo()
    {
        return array("name"=>"Magmi Import Url UI","author"=>"Dweeves","version"=>"1.0.3",
            "url"=>$this->pluginDocUrl("Magmi_Import_Url_UI"));
    }

    public function initialize($params)
    {
    }
}
