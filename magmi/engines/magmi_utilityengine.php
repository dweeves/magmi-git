<?php

/**
 * MAGENTO MASS IMPORTER CLASS
 *
 * version : 0.6
 * author : S.BRACQUEMONT aka dweeves
 * updated : 2010-10-09
 *
 */

/* use external file for db helper */
require_once ("magmi_engine.php");
require_once ("magmi_pluginhelper.php");

/* Magmi ProductImporter is now a Magmi_Engine instance */
class Magmi_UtilityEngine extends Magmi_Engine
{

    /**
     * constructor
     *
     * @param string $conffile
     *            : configuration .ini filename
     */
    public function __construct()
    {
        parent::__construct();
    }

    public function getEnabledPluginClasses($profile)
    {
        $clist = Magmi_PluginHelper::getInstance("main")->getPluginsInfo("utilities", "class");
        return $clist;
    }

    public function getEngineInfo()
    {
        return array("name"=>"Magmi Utilities Engine","version"=>"1.0.1","author"=>"dweeves");
    }

    /**
     * load properties
     *
     * @param string $conf
     *            : configuration .ini filename
     */
    public function getPluginFamilies()
    {
        return array("utilities");
    }

    public function engineInit($params)
    {
        $this->initPlugins(null);
    }

    public function engineRun($params)
    {
        $this->log("Magento Mass Importer by dweeves - version:" . Magmi_Version::$version, "title");
        // initialize db connectivity
        Magmi_StateManager::setState("running");
        // force only one class to run
        $this->_pluginclasses = array("utilities"=>array($params["pluginclass"]));
        
        $this->createPlugins("__utilities__", $params);
        foreach ($this->_activeplugins["utilities"] as $pinst)
        {
            try
            {
                $pinst->runUtility();
            }
            catch (Exception $e)
            {
                $this->logException($e);
            }
        }
        
        Magmi_StateManager::setState("idle");
    }

    public function onEngineException($e)
    {
        Magmi_StateManager::setState("idle");
    }
}