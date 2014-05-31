<?php
require_once ("magmi_productimportengine.php");

class Magmi_ProductImport_DataPump
{
    protected $_engine = null;
    protected $_params = array();
    protected $_logger = null;
    protected $_importcolumns = array();
    protected $_defaultvalues = array();
    protected $_stats;
    protected $_crow;
    protected $_rstep = 100;
    protected $_mdpatched = false;

    public function __construct()
    {
        $this->_engine = new Magmi_ProductImportEngine();
        $this->_engine->setBuiltinPluginClasses("*datasources", 
            dirname(__FILE__) . DIRSEP . "magmi_datapumpdatasource.php::Magmi_DatapumpDS");
        
        $this->_stats["tstart"] = microtime(true);
        // differential
        $this->_stats["tdiff"] = $this->_stats["tstart"];
    }

    public function setReportingStep($rstep)
    {
        $this->_rstep = $rstep;
    }

    public function beginImportSession($profile, $mode, $logger = null)
    {
        $this->_engine->setLogger($logger);
        $this->_engine->initialize();
        $this->_params = array("profile"=>$profile,"mode"=>$mode);
        $this->_engine->engineInit($this->_params);
        $this->_engine->initImport($this->_params);
        // intermediary report step
        $this->_engine->initDbqStats();
        $pstep = $this->_engine->getProp("GLOBAL", "step", 0.5);
        // read each line
        $this->_stats["lastrec"] = 0;
        $this->_stats["lastdbtime"] = 0;
        $this->crow = 0;
    }

    public function getEngine()
    {
        return $this->_engine;
    }

    public function setDefaultValues($dv = array())
    {
        $this->_defaultvalues = $dv;
    }

    public function ingest($item = array())
    {
        $item = array_merge($this->_defaultvalues, $item);
        $diff = array_diff(array_keys($item), $this->_importcolumns);
        if (count($diff) > 0)
        {
            $this->_importcolumns = array_keys($item);
            // process columns
            $this->_engine->callPlugins("itemprocessors", "processColumnList", $this->_importcolumns);
            $this->_engine->initAttrInfos($this->_importcolumns);
        }
        $res = $this->_engine->processDataSourceLine($item, $this->_rstep, $this->_stats["tstart"], 
            $this->_stats["tdiff"], $this->_stats["lastdbtime"], $this->stats["lastrec"]);
        return $res;
    }

    public function endImportSession()
    {
        $this->_engine->reportStats($this->_engine->getCurrentRow(), $this->_stats["tstart"], $this->_stats["tdiff"], 
            $this->_stats["lastdbtime"], $this->stats["lastrec"]);
        $skustats = $this->_engine->getSkuStats();
        $this->_engine->log("Skus imported OK:" . $skustats["ok"] . "/" . $skustats["nsku"], "info");
        if ($skustats["ko"] > 0)
        {
            $this->_engine->log("Skus imported KO:" . $skustats["ko"] . "/" . $skustats["nsku"], "warning");
        }
        
        $this->_engine->exitImport();
    }
}
