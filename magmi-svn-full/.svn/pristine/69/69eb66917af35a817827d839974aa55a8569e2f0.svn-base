<?php 
	$params=$_REQUEST;
	print_r($params);
	ini_set("display_errors",1);
	require_once("../inc/magmi_statemanager.php");
	require_once("../inc/magmi_importer.php");
	class FileLogger
	{
		protected $_fname;
		
		public function __construct($fname)
		{
			$this->_fname=$fname;
			$f=fopen($this->_fname,"w");
			fclose($f);
		}

		public function log($data,$type)
		{
			
			$f=fopen($this->_fname,"a");
			fwrite($f,"$type:$data\n");
			fclose($f);
		}
		
	}
	
	class EchoLogger
	{
		public function log($data,$type)
		{
			echo("$type:$data<br>");
		}
		
	}
	if(Magmi_StateManager::getState()!=="running")
	{
		Magmi_StateManager::setState("idle");
		set_time_limit(0);
		$mmi_imp=new MagentoMassImporter();
		$logfile=isset($params["logfile"])?$params["logfile"]:null;
		if(isset($logfile) && $logfile!="")
		{
			echo "set logfile to:".$logfile;
			$mmi_imp->setLogger(new FileLogger($logfile));
		}	
		else
		{
			$mmi_imp->setLogger(new EchoLogger());
		
		}
		$mmi_imp->import($params);
		
	}
?>
