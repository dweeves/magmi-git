<?php
require_once("../engines/magmi_productimportengine.php");

class Magmi_ProductImport_DataPump
{
	protected $_engine=null;
	protected $_params=array();	
	protected $_logger=null;
	protected $_importcolumns=array();
	protected $_defaultvalues=array();
	
	public function __construct()
	{
		$this->_engine=new Magmi_ProductImportEngine();
 		$this->_engine->setBuiltinPluginClasses("*datasources",dirname(__FILE__).DS."magmi_datapumpdatasource.php::Magmi_DatapumpDS");
		
	}
	
	public function beginImportSession($profile,$mode,$logger=null)
	{
		$this->_engine->setLogger($logger);
		$this->_engine->initialize();
		$this->_params=array("profile"=>$profile,"mode"=>$mode);
 		$this->_engine->engineInit($this->_params);
		$this->_engine->initImport($this->_params);
 		
	}
	
	public function setDefaultValues($dv=array())
	{
		$this->_defaultvalues=$dv;
	}
	
	
	public function ingest($item=array())
	{
		$item=array_merge($this->_defaultvalues,$item);
		$diff=array_diff(array_keys($item),$this->_importcolumns);
		if(count($diff)>0)
		{
			$this->_importcolumns=array_keys($item);
			//process columns
			$this->_engine->callPlugins("itemprocessors","processColumnList",$this->_importcolumns);
			$this->_engine->initAttrInfos($this->_importcolumns);			
		}
		$this->_engine->importItem($item);
	}
 
	public function endImportSession()
	{
 		$this->_engine->exitImport();
	}
	
}