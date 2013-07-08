<?php
require_once("magmi_config.php");

class Magmi_PluginHelper
{
	
	
	static $_plugins_cache=array();
	static $_instances=array();
	public $base_dir;
	public $plugin_dir;
	protected $_profile;
	protected $_plmeta=array("datasources"=>array("Magmi_Datasource","*/*"),
							 "itemprocessors"=>array("Magmi_ItemProcessor","*/*"),
							 "customerprocessors"=>array("Magmi_CutomerProcessor","*/*"),
							 "general"=>array("Magmi_GeneralImportPlugin","*/*"),
							 "utilities"=>array("Magmi_UtilityPlugin","utilities"));
	protected $_eng;
	protected $_engclass;
	public function __construct($profile=null)
	{
		$this->_profile=$profile;
		$this->base_dir=dirname(__FILE__);
		$this->plugin_dir=realpath(dirname(dirname(__FILE__)).DS."plugins");
		$this->_eng=null;
		//set include path to inclue plugins inc & base dir
		set_include_path(ini_get("include_path").PATH_SEPARATOR."$this->plugin_dir/inc".PATH_SEPARATOR."$this->base_dir");
		//add base classes in context
		require_once("magmi_item_processor.php");
		require_once("magmi_datasource.php");
		require_once("magmi_generalimport_plugin.php");
		require_once("magmi_utility_plugin.php");
		
	}
	
	public function setEngineClass($engclass="magmi_productimportengine::Magmi_ProductImportEngine")
	{
		$this->_engclass=$engclass;
		$enginfo=explode("::",$engclass);
		$engfile=dirname(dirname(__FILE__))."/engines/".$enginfo[0].".php";
		if(file_exists($engfile))
		{
			include_once($engfile);
			$engcls=$enginfo[1];		
			if(class_exists($engcls))
			{
				$this->setEngineInstance(new $engcls());
				$this->_eng->setProfile($this->_profile);
			}
		
		}
	}
	
	public function getEngineClass()
	{
		if($this->_engclass==null)
		{
			$this->_engclass="magmi_productimportengine::Magmi_ProductImportEngine";
		}
		return $this->_engclass;
	}
	
	public  function getEnginePluginClasses()
	{
		return $this->getPluginClasses($this->_eng);
	}
	
	public function setEngineInstance($enginst)
	{
		$this->_eng=$enginst;
	}
	
	public static function getInstance($profile=null)
	{
		$key=($profile==null?"default":$profile);
		if(!isset(self::$_instances[$key]))
		{
			self::$_instances[$key]=new Magmi_PluginHelper($profile);
		}
		return self::$_instances[$key];
	}
	
	public static function fnsort($f1,$f2)
	{
		return strcmp(basename($f1),basename($f2));	
	}
		
 
	public function initPluginInfos($baseclass,$basedir="*/*")
	{
		$candidates=glob("$this->plugin_dir/$basedir/*/*.php");
		usort($candidates,array("Magmi_PluginHelper","fnsort"));
		$pluginclasses=array();
		foreach($candidates as $pcfile)
		{
			$dirname=dirname(substr($pcfile,strlen($this->plugin_dir)));
			if(substr(basename($dirname),0,2)!='__')
			{
				$content=file_get_contents($pcfile);
				if(preg_match_all("/class\s+(.*?)\s+extends\s+$baseclass/mi",$content,$matches,PREG_SET_ORDER))
				{
					require_once($pcfile);				
					foreach($matches as $match)
					{
						$pluginclasses[]=array("class"=>$match[1],"dir"=>$dirname,"file"=>basename($pcfile));
					}
				}
			}
		}
		return $pluginclasses;
	}

	public function getPluginClasses($eng)
	{
		return self::getPluginsInfo($eng->getPluginFamilies(),"class");
	}
	
	public function getEngineList()
	{
		$path=dirname(dirname(__FILE__))."/engines/*.php";
		$eflist=glob($path);
		$elist=array();
		foreach($eflist as $efile)
		{
			$k=substr(basename($efile),0,-4);
			$ct=file_get_contents($efile);
			if(preg_match_all("|class\s+(.*?)\s+extends\s+Magmi_Engine|mi",$ct,$matches,PREG_SET_ORDER))
			{
				require_once($efile);
				$cname=$matches[0][1];
				$eng=new $cname();
				$elist["$k::$cname"]=$eng->getEngineInfo();
			}
		}
		return $elist;
	}
	
	public function getPluginsInfo($pltypes,$filter=null)
	{
		if(self::$_plugins_cache==null)
		{
			self::scanPlugins($pltypes);
		}
		
		if(isset($filter))
		{
			$out=array();
			foreach(self::$_plugins_cache as $k=>$arr)
			{
				if(!isset($out[$k]))
				{
					$out[$k]=array();
				}
				foreach($arr as $desc)
				{
					$pclass=$desc["class"];
					$pcomp=call_user_func(array($pclass,"getCompatibleEngines"));
					if(preg_match('/'.$pcomp.'/',get_class($this->_eng)))
					{
						$out[$k][]=$desc[$filter];
					}
				}
			}	
			$plugins=$out;
		}
		else
		{
			$plugins=self::$_plugins_cache;
		}
		return $plugins;
		
	}
	
	public function getEngine()
	{
		return $this->_eng;
	}
	public function scanPlugins($pltypes)
	{
		if(!is_array($pltypes))
		{
			$pltypes=array($pltypes);
		}
		foreach($pltypes as $pltype)
		{
			if(!isset(self::$_plugins_cache[$pltype]))
			{
				self::$_plugins_cache[$pltype]=self::initPluginInfos($this->_plmeta[$pltype][0],$this->_plmeta[$pltype][1]);
			}
		}
	}
	

	public function createInstance($ptype,$pclass,$params=null,$init=false)
	{
	
		if(!isset(self::$_plugins_cache[$ptype]))
		{
			self::scanPlugins($ptype);
		}
		$plinst=new $pclass();
		$plinst->pluginInit($this->_eng,$this->getPluginMeta($plinst),$params,$init,$this->_profile);
		return $plinst;
	}
	
	public function getPluginDir($pinst)
	{
		$mt=$this->getPluginMeta($pinst);
		return $mt["dir"];	
	}
	
	public function getPluginMeta($pinst)
	{
		if(self::$_plugins_cache==null)
		{
			self::scanPlugins();
		}
		
		foreach(self::$_plugins_cache as $t=>$l)
		{
			foreach($l as $pdesc)
			{
				if($pdesc["class"]==get_class($pinst))
				{
					$out=$pdesc;
					$out["dir"]=$this->plugin_dir.$pdesc["dir"];
					return $out;
				}
			}
		}
	}
	
	public function installPluginPackage($pkgname)
	{
		$zip = new ZipArchive();
     	$res = $zip->open($pkgname);
     	if ($res === TRUE) 
     	{
         $zip->extractTo($this->plugin_dir);
         $zip->close();
         return array("plugin_install"=>"OK");
     	} 
     	else 
     	{
     		return array("plugin_install"=>"ERROR",
     					 "ERROR"=>"Invalid Plugin Package Archive");
     	}
     	$packages=glob("$this->plugin_dir/*");
     	foreach($packages as $pdir)
     	{
     		if(file_exists($pdir.DS."obsolete.txt"))
     		{
     			$content=file_get_contents($pdir.DS."obsolete.txt");
     			$obsolete=explode("\n",$content);
     			foreach($obsolete as $todelete)
     			{
     				if($todelete!="")
     				{
     					@unlink($pdir.DS.$todelete);
     				}
     			}
     			unlink($pdir.DS."obsolete.txt");
     		}
     	}		
	}
	
	public function removePlugin($pgpath)
	{
		unlink($pgpath);
	}
}