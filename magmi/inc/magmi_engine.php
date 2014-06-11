<?php
require_once ("dbhelper.class.php");
require_once ("magmi_config.php");
require_once ("magmi_version.php");
require_once ("magmi_utils.php");
require_once ("magmi_statemanager.php");
require_once ("magmi_pluginhelper.php");

/**
 * This class is the mother class for magmi engines
 * A magmi engine is a class that performs operations on DB
 *
 * @author dweeves
 *        
 */
abstract class Magmi_Engine extends DbHelper
{
    protected $_conf;
    protected $_initialized = false;
    protected $_exceptions = array();
    public $tprefix;
    protected $_connected;
    protected $_activeplugins;
    protected $_pluginclasses;
    protected $_builtinplugins = array();
    protected $_ploop_callbacks = array();
    private $_excid = 0;
    public $logger = null;
    protected $_timingcats = array();

    /**
     * Engine Metadata Table access
     */
    public function getEngineInfo()
    {
        return array("name"=>"Generic Magmi Engine","version"=>"1.1","author"=>"dweeves");
    }

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
        // force PHP internal encoding as UTF 8
        mb_internal_encoding("UTF-8");
    }

    /**
     * Engine initialization @param params : key/value array of initialization parameters
     */
    public final function initialize($params = array())
    {
        try
        {
            // Retrieving master config file
            $this->_conf = Magmi_Config::getInstance();
            $this->_conf->load();
            // Intializing members
            $this->tprefix = $this->_conf->get("DATABASE", "table_prefix");
            $this->_excid = 0;
            $this->_initialized = true;
            $this->_exceptions = array();
        }
        catch (Exception $e)
        {
            die("Error initializing Engine:{$this->_conf->getConfigFilename()} \n" . $e->getMessage());
        }
    }

    /**
     * Returns magento directory
     */
    public function getMagentoDir()
    {
        return $this->_conf->getMagentoDir();
    }

    /**
     * returns magento version
     */
    public function getMagentoVersion()
    {
        return $this->_conf->get("MAGENTO", "version");
    }

    /**
     * Plugin loop callback registration
     */
    protected function _registerPluginLoopCallback($cbtype, $cb)
    {
        $this->_ploop_callbacks[$cbtype] = $cb;
    }

    /**
     * Plugin loop callback deregistration
     */
    protected function _unregisterPluginLoopCallback($cbtype)
    {
        unset($this->_ploop_callbacks[$cbtype]);
    }

    /**
     * Generic implementation of plugin families, empty for this mother class
     */
    public function getPluginFamilies()
    {
        return array();
    }

    /**
     * return the list of enabled plugin classes for a given profile @param $profile : profile name to check
     */
    public function getEnabledPluginClasses($profile)
    {
        $enabledplugins = new EnabledPlugins_Config($profile);
        $enabledplugins->load();
        return $enabledplugins->getEnabledPluginFamilies($this->getPluginFamilies());
    }

    /**
     * initializes Plugin instances for a given profile @param $profile : profile to initialize plugins for , defaults to null (Default Profile)
     */
    public function initPlugins($profile = null)
    {
        // reset _active plugins in case of Engine reuse
        $this->_activeplugins = array();
        $this->_pluginclasses = $this->getEnabledPluginClasses($profile);
    }

    /**
     * Returns a list of class names for "Builtin" plugins
     */
    public function getBuiltinPluginClasses()
    {
        $bplarr = array();
        
        foreach ($this->_builtinplugins as $pfamily => $pdef)
        {
            $plinfo = explode("::", $pdef);
            $pfile = $plinfo[0];
            $pclass = $plinfo[1];
            require_once ($pfile);
            if (!isset($bplarr[$pfamily]))
            {
                $bplarr[$pfamily] = array();
            }
            $bplarr[$pfamily][] = $pclass;
        }
        return $bplarr;
    }

    /**
     * Return the list of enabled plugin classes
     */
    public function getPluginClasses()
    {
        return $this->_pluginclasses;
    }
    
    /*
     * Return the list of active plugin instances for a given plugin family @param $family : plugin family to get instances from, defaults to null (all plugins)
     */
    public function getPluginInstances($family = null)
    {
        $pil = null;
        // if no family set, return all active plugins
        if ($family == null)
        {
            $pil = $this->_activeplugins();
        }
        else
        // filter active plugins by family
        {
            $pil = (isset($this->_activeplugins[$family]) ? $this->_activeplugins[$family] : array());
        }
        return $pil;
    }
    
    /*
     * Force Builtin plugin classes list with a list of classes for a given plugin family @param $family : family of builtin plugins to set @param $pclasses : array of plugin class names to set as buitin for this engine
     */
    public function setBuiltinPluginClasses($pfamily, $pclasses)
    {
        $this->_builtinplugins[$pfamily] = $pclasses;
    }
    
    /*
     * Plugin sorting callback for call order in the same execution step. Sorts by filename
     */
    public function sortPlugins($p1, $p2)
    {
        $m1 = $p1->getPluginMeta();
        if ($m1 == null)
        {
            return 1;
        }
        $m2 = $p2->getPluginMeta();
        if ($m2 == null)
        {
            return -1;
        }
        return strcmp($m1["file"], $m2["file"]);
    }
    
    /*
     * Create plugin instances for a given profile @param $profile : profile name to create plugins for @param $params : configuration parameters for the profile (all plugins)
     */
    public function createPlugins($profile, $params)
    {
        // Get Plugin Helper instance
        $plhelper = Magmi_PluginHelper::getInstance($profile);
        // Merge Builtin Plugin classes with current plugin classes
        $this->_pluginclasses = array_merge_recursive($this->_pluginclasses, $this->getBuiltinPluginClasses());
        // Iterate on plugin classes by family
        foreach ($this->_pluginclasses as $pfamily => $pclasses)
        {
            // If family name starts with *
            if ($pfamily[0] == "*")
            {
                // use the real family name (after *)
                $this->_pluginclasses[substr($pfamily, 1)] = $pclasses;
                // clear the * pseudo family
                unset($this->_pluginclasses[$pfamily]);
            }
        }
        
        // Iterate on final plugin classes list
        foreach ($this->_pluginclasses as $pfamily => $pclasses)
        {
            // If there is no active plugins in the current family
            if (!isset($this->_activeplugins[$pfamily]))
            {
                // initialize active plugins for plugin family
                $this->_activeplugins[$pfamily] = array();
            }
            // For all plugin classes in current family
            foreach ($pclasses as $pclass)
            {
                // Create a new instance of plugin with parameters
                // Add it to the list of active plugins in the current family
                $this->_activeplugins[$pfamily][] = $plhelper->createInstance($pfamily, $pclass, $params, $this);
            }
            // Sort family plugins with plugin sorting callback
            usort($this->_activeplugins[$pfamily], array(&$this,"sortPlugins"));
        }
    }
    
    /*
     * Retrieve all active plugins instances for a give plugin class name
     */
    public function getPluginInstanceByClassName($pfamily, $pclassname)
    {
        $inst = null;
        if (isset($this->_activeplugins[$pfamily]))
        {
            foreach ($this->_activeplugins[$pfamily] as $pinstance)
            {
                if (get_class($pinstance) == $pclassname)
                {
                    $inst = $pinstance;
                    break;
                }
            }
        }
        return $inst;
    }
    
    /*
     * Get a plugin instance in a family based on it's execution order
     */
    public function getPluginInstance($family, $order = -1)
    {
        if ($order < 0)
        {
            $order += count($this->_activeplugins[$family]);
        }
        return $this->_activeplugins[$family][$order];
    }
    
    /*
     * Plugin call generic callback for engine @param $types : plugin types to call @param $callback : processing step to call @param $data : (reference) , data to pass to plugin processing @param $params : extra parameters for processing step @param $break : flag to stop calling chain at first plugin returning false (defaults to true)
     */
    public function callPlugins($types, $callback, &$data = null, $params = null, $break = true)
    {
        $result = true;
        
        // If plugin type list is not an array , process it as string
        if (!is_array($types))
        {
            // If plugin is not wildcard , build array of types based on comma separated string
            if ($types != "*")
            {
                $types = explode(",", $types);
            }
            else
            {
                $types = array_keys($this->_activeplugins);
            }
        }
        
        // Timing initialization (global processing step)
        $this->_timecounter->initTime($callback, get_class($this));
        
        // Iterate on plugin types (families)
        foreach ($types as $ptype)
        {
            // If there is at least one active plugin in this family
            if (isset($this->_activeplugins[$ptype]))
            {
                // For all instances in the family
                foreach ($this->_activeplugins[$ptype] as $pinst)
                {
                    // If the plugin has a hook for the defined processing step
                    if (method_exists($pinst, $callback))
                    {
                        // Timing initialization for current plugin in processing step
                        $this->_timecounter->initTime($callback, get_class($pinst));
                        // Perform plugin call
                        // either with or without parameters,or parameters & data
                        // store execution result
                        $callres = ($data == null ? ($params == null ? $pinst->$callback() : $pinst->$callback($params)) : $pinst->$callback(
                            $data, $params));
                        // End Timing for current plugin in current step
                        $this->_timecounter->exitTime($callback, get_class($pinst));
                        // if plugin call result is false with data set
                        if ($callres === false && $data != null)
                        {
                            // final result is false
                            $result = false;
                        }
                        // If there is a register callback for the plugin processing loop
                        if (isset($this->_ploop_callbacks[$callback]))
                        {
                            $cb = $this->_ploop_callbacks[$callback];
                            // Call the plugin processing loop callback , time it
                            $this->_timecounter->initTime($callback, get_class($pinst));
                            $this->$cb($pinst, $data, $result);
                            $this->_timecounter->exitTime($callback, get_class($pinst));
                        }
                        // if last result plugin is false & break flag
                        if ($result === false && $break)
                        {
                            // End timing
                            $this->_timecounter->exitTime($callback, get_class($this));
                            // return false
                            return $result;
                        }
                    }
                }
            }
        }
        // Nothing broke, end timing
        $this->_timecounter->exitTime($callback, get_class($this));
        // Return plugin call result
        return $result;
    }

    public function getParam($params, $pname, $default = null)
    {
        return isset($params[$pname]) ? $params[$pname] : $default;
    }

    public function setLogger($logger)
    {
        $this->logger = $logger;
    }

    /**
     * logging function
     *
     * @param string $data
     *            : string to log
     * @param string $type
     *            : log type
     */
    public function microDateTime()
    {
        list($microSec,$timeStamp) = explode(" ", microtime());
        return date('Y-m-d h:i:', $timeStamp) . (date('s', $timeStamp) + $microSec);
    }

    public function log($data, $type = "default", $logger = null)
    {
        $usedlogger = ($logger == null ? $this->logger : $logger);
        if (isset($usedlogger))
        {
            $usedlogger->log($data, $type);
        }
    }

    public function logException($e, $data = "", $logger = null)
    {
        $this->trace($e, $data);
        $this->log($this->_excid . ":" . $e->getMessage() . " - " . $data, "error", $logger);
    }

    public function getExceptionTrace($tk, &$traces)
    {
        $this->_excid++;
        $trstr = "";
        foreach ($traces as $trace)
        {
            if (isset($trace["file"]))
            {
                $fname = str_replace(dirname(dirname(__FILE__)), "", $trace["file"]);
                $trstr .= $fname . ":" . (isset($trace["line"]) ? $trace["line"] : "?") . " - ";
                if (isset($trace["class"]))
                {
                    $trstr .= $trace["class"] . "->";
                    if (isset($trace["function"]))
                    {
                        $trstr .= $trace["function"];
                    }
                    $trstr .= "\n----------------------------------------\n";
                    if (isset($trace["args"]))
                    {
                        $trstr .= print_r($trace["args"], true);
                    }
                    $trstr .= "\n";
                }
            }
        }
        if (!isset($this->_exceptions[$tk]))
        {
            $this->_exceptions[$tk] = array(0,$this->_excid);
        }
        $this->_exceptions[$tk][0]++;
        $trstr = "************************************\n$tk\n*************************************\n$trstr";
        return array($trstr,$this->_exceptions[$tk][0] == 1,$this->_exceptions[$tk][1]);
    }

    public function trace($e, $data = "")
    {
        $traces = $e->getTrace();
        $tk = $e->getMessage();
        $traceinfo = $this->getExceptionTrace($tk, $traces);
        $f = fopen(Magmi_StateManager::getTraceFile(), "a");
        fwrite($f, "---- TRACE : $this->_excid -----\n");
        fwrite($f, "---- DATE : " . date('Y-m-d H:i:s') . " ------\n");
        try
        {
            if ($traceinfo[1] == true)
            {
                fwrite($f, $traceinfo[0]);
                fwrite($f, "+++++++++++++++++++++++++++++\nCONTEXT DUMP\n+++++++++++++++++++++++++++++\n");
                fwrite($f, print_r($this, true));
                fwrite($f, "\n+++++++++++++++++++++++++++++\nEND CONTEXT DUMP\n+++++++++++++++++++++++++++++\n");
            }
            else
            {
                $tnum = $traceinfo[2];
                fwrite($f, "Duplicated exception - same trace as TRACE : $tnum\n");
            }
        }
        catch (Exception $te)
        {
            fwrite($f, "Exception occured during trace:" . $te->getMessage());
        }
        fwrite($f, "---- ENDTRACE : $this->_excid -----\n");
        fclose($f);
    }

    /**
     * Engine run method
     *
     * @param array $params
     *            - run parameters
     */
    public final function run($params = array())
    {
        try
        {
            $f = fopen(Magmi_StateManager::getTraceFile(), "w");
            fclose($f);
            $enginf = $this->getEngineInfo();
            $this->log("MAGMI by dweeves - version:" . Magmi_Version::$version, "title");
            $this->log("Running {$enginf["name"]} v${enginf["version"]} by ${enginf["author"]}", "startup");
            if (!$this->_initialized)
            {
                $this->initialize($params);
            }
            $this->connectToMagento();
            $this->engineInit($params);
            $this->engineRun($params);
            $this->disconnectFromMagento();
        }
        catch (Exception $e)
        {
            $this->disconnectFromMagento();
            
            $this->handleException($e);
        }
    }

    public function handleException($e)
    {
        $this->logException($e);
        if (method_exists($this, "onEngineException"))
        {
            $this->onEngineException($e);
        }
    }

    /**
     * shortcut method for configuration properties get
     */
    public function getProp($sec, $val, $default = null)
    {
        return $this->_conf->get($sec, $val, $default);
    }

    /**
     * Initialize Connection with Magento Database
     */
    public function connectToMagento()
    {
        // et database infos from properties
        if (!$this->_connected)
        {
            $host = $this->getProp("DATABASE", "host", "localhost");
            $dbname = $this->getProp("DATABASE", "dbname", "magento");
            $user = $this->getProp("DATABASE", "user");
            $pass = $this->getProp("DATABASE", "password");
            $debug = $this->getProp("DATABASE", "debug");
            $conn = $this->getProp("DATABASE", "connectivity", "net");
            $port = $this->getProp("DATABASE", "port", "3306");
            $socket = $this->getProp("DATABASE", "unix_socket");
            $this->initDb($host, $dbname, $user, $pass, $port, $socket, $conn, $debug);
            // suggested by pastanislas
            $this->_db->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
        }
    }
    /*
     * Disconnect Magento db
     */
    public function disconnectFromMagento()
    {
        if ($this->_connected)
        {
            $this->exitDb();
        }
    }

    /**
     * returns prefixed table name
     *
     * @param string $magname
     *            : magento base table name
     */
    public function tablename($magname)
    {
        return $this->tprefix != "" ? $this->tprefix . "$magname" : $magname;
    }

    public abstract function engineInit($params);

    public abstract function engineRun($params);
}