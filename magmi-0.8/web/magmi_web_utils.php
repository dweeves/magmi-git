<?php
function tdarray_to_js($container,$mainarr,$prefix)
{
	$varr=array();
	$vlist=explode(",",$container->getParam($mainarr));
	foreach($vlist as $k)
	{
		$v=$container->getParam("$prefix:".rawurlencode($k));
		$v=addslashes($v);
		$varr[]="\"$k\":\"$v\"";
	}
	return "{".implode(",",$varr)."}";
}

function initSession()
{
	if(!isset($_SESSION))
	{
		$sessid=isset($_REQUEST['PHPSESSID'])?$_REQUEST['PHPSESSID']:null;
		if($sessid)
		{
			session_id($sessid);
		}
		session_start();
		if($sessid==null )
		{
			$_SESSION=array();
		}
	}
	
}


function setEngineAndProfile($ph,$engclass,$profile)
{
	$ph->setEngineClass($engclass);
	$profilelist=$ph->getEngine()->getProfileList();
	if(!in_array($profile,$profilelist))
	{
		if(count($profilelist)>0)
		{
			$profile=$profilelist[0];
			$_SESSION["profile"]=$profile;
		}
		else
		{
			$profile=null;
		}
		$ph=Magmi_PluginHelper::getInstance($profile);
		$ph->setEngineClass($engclass);	
	}
	
}

function getWebParam($name,$default=null)
{
	initSession();
	$out=isset($_REQUEST[$name])?$_REQUEST[$name]:(isset($_SESSION[$name])?$_SESSION[$name]:$default);
	return $out;		
}

function getWebParams()
{
	initSession();
	return array_merge($_SESSION,$_REQUEST);
}