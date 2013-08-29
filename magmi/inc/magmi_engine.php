<?php
require_once("dbhelper.class.php");
require_once("magmi_config.php");
require_once("magmi_version.php");
require_once("magmi_utils.php");
require_once("magmi_statemanager.php");
require_once("magmi_pluginhelper.php");

/**
 * 
 * This class is the mother class for magmi engines
 * A magmi engine is a class that performs operations on DB
 * @author dweeves
 *
 */
abstract class Magmi_Engine extends DbHelper
{
	protected $_conf;
	protected $_initialized=false;
	protected $_exceptions=array();
	public $tprefix;
	protected $_connected;
	protected $_activeplugins;
	protected $_pluginclasses;
	protected $_builtinplugins=array();
	protected $_ploop_callbacks=array();
	private $_excid=0;
	public $logger=null;
	protected $_timingcats=array();
	
	public function getEngineInfo()
	{
		return array("name"=>"Generic Magmi Engine","version"=>"1.1","author"=>"dweeves");
	}
	
	public function __construct()
	{
		parent::__construct();
		//force PHP internal encoding as UTF 8
		mb_internal_encoding("UTF-8");
	}
	
	
	public final  function initialize($params=array())
	{
		try
		{
			$this->_conf=Magmi_Config::getInstance();
			$this->_conf->load();
			
			$this->tprefix=$this->_conf->get("DATABASE","table_prefix");
			$this->_excid=0;
			$this->_initialized=true;
			$this->_exceptions=array();
		}
		catch(Exception $e)
		{
			die("Error initializing Engine:{$this->_conf->getConfigFilename()} \n".$e->getMessage());
		}
		
	}
	
	/**
	 * Returns magento directory
	 */
	public function getMagentoDir()
	{
		return $this->_conf->getMagentoDir();
	}
	public function getMagentoVersion()
	{
		return $this->_conf->get("MAGENTO","version");
	}
	
	protected function _registerPluginLoopCallback($cbtype,$cb)
	{
	  	$this->_ploop_callbacks[$cbtype]=$cb;
	}
	
	protected function _unregisterPluginLoopCallback($cbtype)
	{
		unset($this->_ploop_callbacks[$cbtype]);
	}
	
	public function getPluginFamilies()
	{
		return array();
	}
	
	
	public function getEnabledPluginClasses($profile)
	{
		$enabledplugins=new EnabledPlugins_Config($profile);
		$enabledplugins->load();
		return $enabledplugins->getEnabledPluginFamilies($this->getPluginFamilies());
	}
	
	public function initPlugins($profile=null)
	{
		
		$this->_pluginclasses=$this->getEnabledPluginClasses($profile);
	}
	
	public function getBuiltinPluginClasses()
	{
		$bplarr=array();
		
		foreach($this->_builtinplugins as $pfamily=>$pdef)
		{
			$plinfo=explode("::",$pdef);
			$pfile=$plinfo[0];
			$pclass=$plinfo[1];
			require_once($pfile);
			if(!isset($bplarr[$pfamily]))
			{
				$bplarr[$pfamily]=array();
			}
			$bplarr[$pfamily][]=$pclass;
		}
		return $bplarr;
	}
	
	public function getPluginClasses()
	{
		return $this->_pluginclasses;
	}
	
	public function getPluginInstances($family=null)
	{
		$pil=null;
		if($family==null)
		{
			$pil=$this->_activeplugins();
		}
		else
		{
			$pil=(isset($this->_activeplugins[$family])?$this->_activeplugins[$family]:array());
		}
		return $pil;
	}
	
	public function setBuiltinPluginClasses($pfamily,$pclasses)
	{
		$this->_builtinplugins[$pfamily]=$pclasses;
	}
	
	public function sortPlugins($p1,$p2)
	{
		
		$m1=$p1->getPluginMeta();
		if($m1==null)
		{
			return 1;
		}
		$m2=$p2->getPluginMeta();
		if($m2==null)
		{
			return -1;	
		}
		return strcmp($m1["file"],$m2["file"]);
	}
	public function createPlugins($profile,$params)
	{
		$plhelper=Magmi_PluginHelper::getInstance($profile);
		$this->_pluginclasses = array_merge_recursive($this->_pluginclasses,$this->getBuiltinPluginClasses());
		foreach($this->_pluginclasses as $pfamily=>$pclasses)
		{
			if($pfamily[0]=="*")
			{
				$this->_pluginclasses[substr($pfamily,1)]=$pclasses;
				unset($this->_pluginclasses[$pfamily]);
			}
		}
		foreach($this->_pluginclasses as $pfamily=>$pclasses)
		{
			if(!isset($this->_activeplugins[$pfamily]))
			{
				$this->_activeplugins[$pfamily]=array();
			}
			foreach($pclasses as $pclass)
			{
				$this->_activeplugins[$pfamily][]=$plhelper->createInstance($pfamily,$pclass,$params,$this);
				
			}
			usort($this->_activeplugins[$pfamily],array(&$this,"sortPlugins"));
		}
		
	}
	
	public function getPluginInstanceByClassName($pfamily,$pclassname)
	{
		$inst=null;
		if(isset($this->_activeplugins[$pfamily]))
		{
			foreach($this->_activeplugins[$pfamily] as $pinstance)
			{
				if(get_class($pinstance)==$pclassname)
				{
					$inst=$pinstance;
					break;
				}
			}
		}
		return $inst;
	}
	
	public function getPluginInstance($family,$order=-1)
	{
		if($order<0)
		{
			$order+=count($this->_activeplugins[$family]);
		}
		return $this->_activeplugins[$family][$order];	
	}
	

	
	public function callPlugins($types,$callback,&$data=null,$params=null,$break=true)
	{
		$result=true;
		if(!is_array($types))
		{
			if($types!="*")
			{
				$types=explode(",",$types);
			}
			else
			{
				$types=array_keys($this->_activeplugins);
			}
		}
		
		$this->_timecounter->initTime($callback,get_class($this));
		
		foreach($types as $ptype)
		{
			if(isset($this->_activeplugins[$ptype]))
			{
				foreach($this->_activeplugins[$ptype] as $pinst)
				{
					if(method_exists($pinst,$callback))
					{
						$this->_timecounter->initTime($callback,get_class($pinst));
						$callres=($data==null?($params==null?$pinst->$callback():$pinst->$callback($params)):$pinst->$callback($data,$params));
						$this->_timecounter->exitTime($callback,get_class($pinst));
						
						if($callres===false && $data!=null)
						{
						
						  $result=false;
								
						}
						if(isset($this->_ploop_callbacks[$callback]))
						{
							$cb=$this->_ploop_callbacks[$callback];
							$this->_timecounter->initTime($callback,get_class($pinst));
							$this->$cb($pinst,$data,$result);
							$this->_timecounter->exitTime($callback,get_class($pinst));
							
						}
						if($result===false && $break)
						{
						$this->_timecounter->exitTime($callback,get_class($this));
							return $result;
						}					
					}
				}
			}
		}
		$this->_timecounter->exitTime($callback,get_class($this));
		return $result;
	}
	
	public function getParam($params,$pname,$default=null)
	{
		return isset($params[$pname])?$params[$pname]:$default;
	}
	
	public function setLogger($logger)
	{
		$this->logger=$logger;
	}
/**
	 * logging function
	 * @param string $data : string to log
	 * @param string $type : log type
	 */
	public function microDateTime()
	{
		  list($microSec, $timeStamp) = explode(" ", microtime());
 		  return date('Y-m-d h:i:', $timeStamp) . (date('s', $timeStamp) + $microSec);
	}
		
	public function log($data,$type="default",$logger=null)
	{
		$usedlogger=($logger==null?$this->logger:$logger);
		if(isset($usedlogger))
		{
			$usedlogger->log($data,$type);
		}
	}
	
	public function logException($e,$data="",$logger=null)
	{
		$this->trace($e,$data);
		$this->log($this->_excid.":".$e->getMessage()." - ".$data,"error",$logger);
	}
	
	public function getExceptionTrace($tk,&$traces)
	{
		$this->_excid++;
		$trstr="";
		foreach($traces as $trace)
		{
			if(isset($trace["file"]))
			{
				$fname=str_replace(dirname(dirname(__FILE__)),"",$trace["file"]);
				$trstr.= $fname.":".(isset($trace["line"])?$trace["line"]:"?")." - ";
				if(isset($trace["class"]))
				{
					$trstr.=$trace["class"]."->";
					if(isset($trace["function"]))
					{
						$trstr.=$trace["function"];
					}
					$trstr.="\n----------------------------------------\n";
					if(isset($trace["args"]))
					{
						$trstr.=print_r($trace["args"],true);
					}
					$trstr.="\n";
				}
			}
		}
		if(!isset($this->_exceptions[$tk]))
		{
			$this->_exceptions[$tk]=array(0,$this->_excid);
		}
		$this->_exceptions[$tk][0]++;
		$trstr="************************************\n$trstr";
		return array($trstr,$this->_exceptions[$tk][0]==1,$this->_exceptions[$tk][1]);
	}
	
	public function trace($e,$data="")
	{		
		$traces=$e->getTrace();
		$tk=$e->getMessage();
		$traceinfo=$this->getExceptionTrace($tk,$traces);
		$f=fopen(Magmi_StateManager::getTraceFile(),"a");	
		fwrite($f,"---- TRACE : $this->_excid -----\n");
		try
		{
			if($traceinfo[1]==true)
			{
				fwrite($f,$traceinfo[0]);	
				fwrite($f,"+++++++++++++++++++++++++++++\nCONTEXT DUMP\n+++++++++++++++++++++++++++++\n");
				fwrite($f,print_r($this,true));
				fwrite($f,"\n+++++++++++++++++++++++++++++\nEND CONTEXT DUMP\n+++++++++++++++++++++++++++++\n");			
			}
			else
			{
				$tnum=$traceinfo[2];
				fwrite($f,"Duplicated exception - same trace as TRACE : $tnum\n");
			}
		}
		catch(Exception $te)
		{
			fwrite($f,"Exception occured during trace:".$te->getMessage());
		}
		fwrite($f,"---- ENDTRACE : $this->_excid -----\n");
		fclose($f);
	}
	
	
	/**
	 * Engine run method
	 * @param array $params - run parameters
	 */
	public final function run($params=array())
	{
		try
		{
			$f=fopen(Magmi_StateManager::getTraceFile(),"w");
			fclose($f);
			$enginf=$this->getEngineInfo();
			$this->log("MAGMI by dweeves - version:".Magmi_Version::$version,"title");
			$this->log("Running {$enginf["name"]} v${enginf["version"]} by ${enginf["author"]}","startup");
			if(!$this->_initialized)
			{
				$this->initialize($params);
			}
			$this->connectToMagento();
			$this->engineInit($params);
			$this->engineRun($params);
			$this->disconnectFromMagento();
		}
		catch(Exception $e)
		{
			$this->disconnectFromMagento();
			
			$this->handleException($e);
		}
	
	}
	
	public function handleException($e)
	{
		$this->logException($e);
		if(method_exists($this, "onEngineException"))
		{
			$this->onEngineException($e);
		}
	}
	
	/**
		shortcut method for configuration properties get
	 */
	public function getProp($sec,$val,$default=null)
	{
		return $this->_conf->get($sec,$val,$default);
	}
	
	/**
	 * Initialize Connection with Magento Database
	 */
	public function connectToMagento()
	{
		#get database infos from properties
		if(!$this->_connected)
		{
			$host=$this->getProp("DATABASE","host","localhost");
			$dbname=$this->getProp("DATABASE","dbname","magento");
			$user=$this->getProp("DATABASE","user");
			$pass=$this->getProp("DATABASE","password");
			$debug=$this->getProp("DATABASE","debug");
			$conn=$this->getProp("DATABASE","connectivity","net");
			$port=$this->getProp("DATABASE","port","3306");
			$socket=$this->getProp("DATABASE","unix_socket");
			$this->initDb($host,$dbname,$user,$pass,$port,$socket,$conn,$debug);
			//suggested by pastanislas
			$this->_db->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY,true);
		}
	}
	/*
	 * Disconnect Magento db
	 */
	public function disconnectFromMagento()
	{
		if($this->_connected)
		{
			$this->exitDb();
		}
	}
	
	/**
	 * returns prefixed table name
	 * @param string $magname : magento base table name
	 */
	public function tablename($magname)
	{
		return $this->tprefix!=""?$this->tprefix."$magname":$magname;
	}
	
	
	public abstract function engineInit($params);
	public abstract function engineRun($params);
	
}