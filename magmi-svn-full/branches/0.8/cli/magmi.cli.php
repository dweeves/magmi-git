<?php

/**
 * MAGENTO MASS IMPORTER CLI SCRIPT
 * 
 * version : 0.1
 * author : S.BRACQUEMONT aka dweeves
 * updated : 2010-08-02
 * 
 */

require_once("../inc/magmi_defs.php");
require_once("magmi_pluginhelper.php");

$script=array_shift($argv);
$options=array();

foreach($argv as $option)
{
	$isopt=$option[0]=="-";

	if($isopt)
	{
		$optarr=explode("=",substr($option,1),2);
		$optname=$optarr[0];
		if(count($optarr)>1)
		{
			$optval=$optarr[1];
		}
		else
		{
			$optval=1;
		}
		$options[$optname]=$optval;
	}
}

class CLILogger
{
	public function log($data,$type)
	{
		echo("$type:$data\n");
	}	
}

function getEngineInstance($options)
{
	if(!isset($options["engine"]))
	{
		$options["engine"]="magmi_productimportengine::Magmi_ProductImportEngine";
	}
	$ph=Magmi_PluginHelper::getInstance($options["profile"]);
	$ph->setEngineClass($options['engine']);
	$enginst=$ph->getEngine();
	/*$optname=$options["engine"];
	$engdef=explode(":",$optname);
	$engine_name=$engdef[0];
	$engine_class=$engdef[1];
	$enginst=null;
	if(file_exists("../engines/$engine_name.php"))
	{
		require_once("../engines/$engine_name.php");
		if(class_exists($engine_class))
		{
			$enginst=new $engine_class();				
		}
	}
	if($enginst==null)
	{
	 die("Invalid engine definition : ".$optname);
	}*/
	
	return $enginst;
}
$importer=getEngineInstance($options);
$importer->setLogger(new CLILogger());
$importer->run($options);
?>