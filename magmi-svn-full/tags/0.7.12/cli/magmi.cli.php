<?php

/**
 * MAGENTO MASS IMPORTER CLI SCRIPT
 * 
 * version : 0.1
 * author : S.BRACQUEMONT aka dweeves
 * updated : 2010-08-02
 * 
 */

require_once("../engines/magmi_productimportengine.php");

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
$importer=new Magmi_ProductImportEngine();
$importer->setLogger(new CLILogger());
$importer->run($options);
?>