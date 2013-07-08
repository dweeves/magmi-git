<?php

/**
 * MAGENTO MASS IMPORTER CLI SCRIPT
 * 
 * version : 0.1
 * author : S.BRACQUEMONT aka dweeves
 * updated : 2010-08-02
 * 
 */

require_once(dirname(dirname(__FILE__))."/inc/magmi_defs.php");

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
		$options["engine"]="magmi_productimportengine:Magmi_ProductImportEngine";
	}

	$optname=$options["engine"];
	$engdef=explode(":",$optname);
	$engine_name=$engdef[0];
	$engine_class=$engdef[1];
	$enginst=null;
	$engfile=dirname(dirname(__FILE__))."/engines/$engine_name.php";
	if(file_exists($engfile))
	{
		require_once($engfile);
		if(class_exists($engine_class))
		{
			$enginst=new $engine_class();				
		}
	}
	if($enginst==null)
	{
	 die("Invalid engine definition : ".$optname);
	}
	return $enginst;
}
$importer=getEngineInstance($options);
$loggerclass=isset($options['logger'])?$options['logger']:"CLILogger";
$importer->setLogger(new $loggerclass());
$importer->run($options);
?>