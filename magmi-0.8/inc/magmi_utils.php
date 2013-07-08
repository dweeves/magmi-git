<?php
//utilities function
// return null for empty string
function nullifempty($val)
{
	return (isset($val)?(trim($val)==""?null:$val):null);
}
// return false for empty string
function falseifempty($val)
{
	return (isset($val)?(strlen($val)==0?false:$val):false);
}
//test for empty string
function testempty($arr,$val)
{
	
	return !isset($arr[$val]) || strlen(trim($arr[$val]))==0;
}

function deleteifempty($val)
{
	return (isset($val)?(trim($val)==""?"__MAGMI_DELETE__":$val):"__MAGMI_DELETE__");
}

function csl2arr($cslarr,$sep=",")
{
	$arr=explode($sep,$cslarr);
	for($i=0;$i<count($arr);$i++)
	{
		$arr[$i]=trim($arr[$i]);		
	}
	return $arr;
}

function trimarray(&$arr)
{
	for($i=0;$i<count($arr);$i++)
	{
		$arr[$i]=trim($arr[$i]);		
	}
	
}

function getRelative(&$val)
{
	$dir="+";
	if($val[0]=="-")
	{
		$val=substr($val,1);
		$dir="-";
	}
	else 
	if($val[0]=="+")
	{
		$val=substr($val,1);
	}
	return $dir;
}

function abspath($path,$basepath="",$resolve=true)
{
	if($basepath=="")
	{
		$basepath=dirname(dirname(__FILE__));
	}
	$cpath=$basepath."/".$path;
	if($resolve)
	{
		$abs=realpath($cpath);
	}
	else
	{
		$abs=preg_replace('|\w+/\.\.\/|', '',$cpath );
		$abs=preg_replace('|\./|','',$abs);
	
	}
	return $abs;
}

function isabspath($path)
{
	 return ($path[0]=="." || (substr(PHP_OS,3)=="WIN" && strlen($path)>1)?$path[1]==":":$path[0]=="/");
}


class Slugger
{
	static protected $_translit=array(
    'Š'=>'S', 'š'=>'s', 'Ð'=>'Dj','Ž'=>'Z', 'ž'=>'z', 'À'=>'A', 'Á'=>'A', 'Â'=>'A', 'Ã'=>'A', 'Ä'=>'A',
    'Å'=>'A', 'Æ'=>'A', 'Ç'=>'C', 'È'=>'E', 'É'=>'E', 'Ê'=>'E', 'Ë'=>'E', 'Ì'=>'I', 'Í'=>'I', 'Î'=>'I',
    'Ï'=>'I', 'Ñ'=>'N', 'Ò'=>'O', 'Ó'=>'O', 'Ô'=>'O', 'Õ'=>'O', 'Ö'=>'O', 'Ø'=>'O', 'Ù'=>'U', 'Ú'=>'U',
    'Û'=>'U', 'Ü'=>'U', 'Ý'=>'Y', 'Þ'=>'B', 'ß'=>'Ss','à'=>'a', 'á'=>'a', 'â'=>'a', 'ã'=>'a', 'ä'=>'a',
    'å'=>'a', 'æ'=>'a', 'ç'=>'c', 'è'=>'e', 'é'=>'e', 'ê'=>'e', 'ë'=>'e', 'ì'=>'i', 'í'=>'i', 'î'=>'i',
    'ï'=>'i', 'ð'=>'o', 'ñ'=>'n', 'ò'=>'o', 'ó'=>'o', 'ô'=>'o', 'õ'=>'o', 'ö'=>'o', 'ø'=>'o', 'ù'=>'u',
    'ú'=>'u', 'û'=>'u', 'ý'=>'y', 'ý'=>'y', 'þ'=>'b', 'ÿ'=>'y', 'ƒ'=>'f'
		);
	
	public static function stripAccents($text){
		
		return strtr($text,self::$_translit);
	}

	public static function slug($str,$allowslash=false)
	{
      $str = strtolower(self::stripAccents(trim($str)));
      $rerep=$allowslash?'[^a-z0-9-/]':'[^a-z0-9-]';
      $str = preg_replace("|$rerep|", '-', $str);
      $str = preg_replace('|-+|', "-", $str);
      $str = preg_replace('|-$|', "", $str);
      return $str;
	}
}