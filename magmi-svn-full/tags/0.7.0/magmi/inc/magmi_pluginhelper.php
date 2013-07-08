<?php
require_once("magmi_config.php");

class Magmi_PluginHelper
{
	
	static $_plugins_cache=null;
	static $_instances=array();
	public $base_dir;
	public $plugin_dir;
	protected $_profile;
	
	public function __construct($profile=null)
	{
		$this->_profile=$profile;
		$this->base_dir=dirname(__FILE__);
		$this->plugin_dir=realpath(dirname(dirname(__FILE__)).DS."plugins");
		//set include path to inclue plugins inc & base dir
		set_include_path(ini_get("include_path").PATH_SEPARATOR."$this->plugin_dir/inc".PATH_SEPARATOR."$this->base_dir");
		//add base classes in context
		require_once("magmi_item_processor.php");
		require_once("magmi_datasource.php");
		require_once("magmi_generalimport_plugin.php");
		
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
		
  	public function savePluginsConfig($params,$dir)
  	{
  		$this->scanPlugins();
  		foreach(self::_plugins_cache as $k=>$pinfoarr)
  		{
  			$class=$pinfoarr["class"];
			$plinst=$this->createInstance($class,$params);
			$plinst->persistParams($plinst->getPluginParams($params)); 				
  		}
  	}
  	
	public function initPluginInfos($baseclass)
	{
		$candidates=glob("$this->plugin_dir/*/*/*/*.php");
		usort($candidates,array("Magmi_PluginHelper","fnsort"));
		$pluginclasses=array();
		foreach($candidates as $pcfile)
		{
			$content=file_get_contents($pcfile);
			if(preg_match_all("/class\s+(.*?)\s+extends\s+$baseclass/mi",$content,$matches,PREG_SET_ORDER))
			{
				
				require_once($pcfile);				
				foreach($matches as $match)
				{
					$pluginclasses[]=array("class"=>$match[1],"dir"=>dirname(substr($pcfile,strlen($this->plugin_dir))));
				}
			}
		}
		return $pluginclasses;
	}

	public function getPluginClasses()
	{
		return self::getPluginsInfo("class");
	}
	
	public function getPluginsInfo($filter=null)
	{
		if(self::$_plugins_cache==null)
		{
			self::scanPlugins();
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
					$out[$k][]=$desc[$filter];
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
	public function scanPlugins()
	{
		if(!isset(self::$_plugins_cache))
		{
			self::$_plugins_cache=array("itemprocessors"=>self::initPluginInfos("Magmi_ItemProcessor"),
				"datasources"=>self::initPluginInfos("Magmi_Datasource"),
				"general"=>self::initPluginInfos("Magmi_GeneralImportPlugin"));
		}
	}
	
	
	public function createInstance($pclass,$params=null,$mmi=null)
	{
	
		if(self::$_plugins_cache==null)
		{
			self::scanPlugins();
		}
		$plinst=new $pclass();
		$plinst->pluginInit($mmi,$this->getPluginDir($plinst),$params,($mmi!=null),$this->_profile);
		return $plinst;
	}
	
	public function getPluginDir($pinst)
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
					return "$this->plugin_dir"."{$pdesc["dir"]}";
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
     }	
	
	
	public function removePlugin($pgpath)
	{
		unlink($pgpath);
	}
}