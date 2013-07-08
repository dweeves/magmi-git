<?php
ini_set("magic_quotes_gpc",0);
require_once("magmi_web_utils.php");
$profile=getWebParam("profile");
$dslist=getWebParam("PLUGINS_DATASOURCES:class");
$genlist=getWebParam("PLUGINS_GENERAL:classes");
$iplist=getWebParam("PLUGINS_ITEMPROCESSORS:classes");
$eng=getWebParam("engine");
if(!isset($iplist))
{
	$iplist="";
}
if(!isset($genlist))
{
	$genlist="";
}
$pflist=array();

foreach(explode(",",$dslist) as $pclass)
{
	$pflist[$pclass]="datasources";		
}

foreach(explode(",",$genlist) as $pclass)
{
	$pflist[$pclass]="general";		
}

foreach(explode(",",$iplist) as $pclass)
{
	$pflist[$pclass]="itemprocessors";		
}


require_once("../inc/magmi_pluginhelper.php");
require_once("../inc/magmi_config.php");
//saving plugin selection
$ph=Magmi_PluginHelper::getInstance($profile);
$ph->setEngineClass($eng);

$epc=new EnabledPlugins_Config($ph->getEngine()->getProfilesDir(), $profile);
$epc->setPropsFromFlatArray(array("PLUGINS_DATASOURCES:class"=>$dslist,
								  "PLUGINS_GENERAL:classes"=>$genlist,
								  "PLUGINS_ITEMPROCESSORS:classes"=>$iplist));
if($epc->save())
{
	

//saving plugins params
foreach($pflist as $pclass=>$pfamily)
{
	$pparams=getWebParams();
	if($pclass!="")
	{
		$plinst=$ph->createInstance($pfamily,$pclass,$pparams);
		$paramlist=$plinst->getPluginParamNames();
		$sarr=$plinst->getPluginParams($pparams);
		$parr=$plinst->getPluginParamsNoCurrent($pparams);
		
		foreach($paramlist as $pname)
		{
			if(!isset($parr[$pname]))
			{
				$parr[$pname]=0;
			}
		}
		$farr=array_merge($sarr,$parr);
		if(!$plinst->persistParams($farr))
		{
			$lasterr=error_get_last();
			echo "<div class='error'>".$lasterr['message']."</div>";
		}
	}
}
$date=filemtime($epc->getConfFile());
echo "Profile $profile saved (".strftime("%c",$date).")";
}
else
{
	$lasterr=error_get_last();
	echo "<div class='error'>".$lasterr['message']."</div>";
}
