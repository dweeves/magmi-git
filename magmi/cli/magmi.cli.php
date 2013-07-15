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
require_once('magmi_loggers.php');
$script=array_shift($argv);

function buildOptions($argv)
{
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
	return $options;
}


function getClassInstance($cval,$cdir=".")
{
	$cdef=explode(":",$cval);
	$cname=$cdef[0];
	$cclass=$cdef[1];
	$cinst=null;
	$cfile="$cdir/$cname.php";
	if(file_exists($cfile))
	{
		require_once($cfile);
		if(class_exists($cclass))
		{
			$cinst=new $cclass();				
		}
	}
	if($cinst==null)
	{
	 die("Invalid class definition : ".$cval);
	}
	return $cinst;
	
}

function getEngineInstance($options)
{
	if(!isset($options["engine"]))
	{
		$options["engine"]="magmi_productimportengine:Magmi_ProductImportEngine";
	}
	$enginst=getClassInstance($options["engine"],dirname(dirname(__FILE__))."/engines");
	return $enginst;
}

$options=buildOptions($argv);
$importer=getEngineInstance($options);
if(isset($importer))
{
	$loggerclass=isset($options['logger'])?$options['logger']:"FileLogger";
	$importer->setLogger(new $loggerclass());
	if(!isset($options["chain"]))
	{
		$options["chain"]=isset($options["profile"])?$options["profile"]:"";
		$options["chain"].=isset($options["mode"])?":".$options["mode"]:"";
	}
	$pdefs=explode(",",$options["chain"]);
	foreach($pdefs as $pdef)
	{
		 $pm=explode(":",$pdef);
		 $eargv=array();
		 if(!empty($pm[0]))
		 {
			 $eargv[]="-profile=".$pm[0];
		 }
		 if(isset($pm[1]))
		 {
		 	$eargv[]="-mode=".$pm[1];
		 }
		$eoptions=buildOptions($eargv);
		$importer->run(array_merge($eoptions,$options));
	}
}
?>