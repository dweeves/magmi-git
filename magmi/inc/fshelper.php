<?php
class FSHelper
{
	public static function isDirWritable($dir)
	{
		$test=@fopen("$dir/__testwr__","w");
		if($test==false)
		{
			return false;
		}
		else
		{
			fclose($test);
			unlink("$dir/__testwr__");
		}
		return true;
	}

}



class MagentoDirHandlerFactory
{
	protected $_handlers=array();
	protected static $_instance;

	public function __construct()
	{
	}

	public static function getInstance()
	{
		if(!isset(self::$_instance))
		{
			self::$_instance=new MagentoDirHandlerFactory();
		}
		return self::$_instance;
	}

	public function registerHandler($obj)
	{
		$cls=get_class($obj);
		if(!isset($this->_handlers[$cls]))
		{
			$this->_handlers[$cls]=$obj;
		}
	}

	public function getHandler($url)
	{
		foreach($this->_handlers as $cls=>$handler)
		{
			if ($handler->canHandle($url))
			{
				return $handler;
			}
		}

	}

}

abstract class RemoteFileGetter
{
	protected $_errors;
	public abstract function urlExists($url);
	public abstract function copyRemoteFile($url,$dest);
	public function getErrors()
	{
		return $this->_errors;
	}
}

class CURL_RemoteFileGetter extends RemoteFileGetter
{
	protected $_curlh;

	public function createContext($url)
	{
		if($this->_curlh==NULL)
		{
			$curl_url=str_replace(" ","%20",$url);
			$context = curl_init($curl_url);
			$this->_curlh=$context;
		}
		return $this->_curlh;
	}

	public function destroyContext($url)
	{
		if($this->_curlh!=NULL)
		{
			curl_close($this->_curlh);
			$this->_curlh=NULL;
		}
	}


	public function urlExists($remoteurl)
	{
		$context=$this->createContext($remoteurl);
		//optimized lookup through curl
		/* head */
		curl_setopt($context,  CURLOPT_HEADER, TRUE);
		curl_setopt( $context, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $context, CURLOPT_CUSTOMREQUEST, 'HEAD' );
		curl_setopt( $context, CURLOPT_NOBODY, true );

		/* Get the HTML or whatever is linked in $url. */
		$response = curl_exec($context);

		/* Check for 404 (file not found). */
		$httpCode = curl_getinfo($context, CURLINFO_HTTP_CODE);
		$exists = ($httpCode<400);
		/* retry on error */
			
		if($httpCode==503 or $httpCode==403)
		{
			/* wait for a half second */
			usleep(500000);
			$response = curl_exec($context);
			$httpCode = curl_getinfo($context, CURLINFO_HTTP_CODE);
			$exists = ($httpCode<400);
		}
		return $exists;
	}

	public function copyRemoteFile($url,$dest)
	{
		$this->_errors=array();
		$ret=true;
		$context=$this->createContext($url);
		if(!$this->urlExists($url))
		{
			$this->_errors=array("type"=>"download error","message"=>"URL $url is unreachable");
			return false;
		}
		$fp=fopen($dest,"w");
		//add support for https urls
		curl_setopt($context, CURLOPT_SSL_VERIFYPEER ,false);
		curl_setopt($context, CURLOPT_RETURNTRANSFER, false);
		curl_setopt( $context, CURLOPT_CUSTOMREQUEST, 'GET' );
		curl_setopt( $context, CURLOPT_NOBODY, false);
		curl_setopt($context, CURLOPT_FILE, $fp);
		curl_setopt($context, CURLOPT_HEADER, 0);
		curl_setopt($context,CURLOPT_FAILONERROR,true);
		if(!ini_get('safe_mode'))
		{
			curl_setopt($context, CURLOPT_FOLLOWLOCATION, 1);
		}
		curl_exec($context);
		if(curl_getinfo($context,CURLINFO_HTTP_CODE)>=400)
		{
			$this->_errors=array("type"=>"download error","message"=>curl_error($context));
			$ret=false;
		}
		fclose($fp);
		return $ret;
	}
}

class URLFopen_RemoteFileGetter extends RemoteFileGetter
{
	public function urlExists($url)
	{
		$fname=$url;
		$h=@fopen($fname,"r");
		if($h!==false)
		{
			$exists=true;
			fclose($h);
		}
		unset($h);
	}

	public function copyRemoteFile($url,$dest)
	{
		if(!$this->urlExists($url))
		{
			$this->_errors=array("type"=>"target error","message"=>"URL $remoteurl is unreachable");
			return false;
		}

		$ok=@copy($url,$dest);
		if(!$ok)
		{
			$this->_errors= error_get_last();
		}
		return $ok;
	}
}

class RemoteFileGetterFactory
{

	public static function getFGInstance()
	{
		$fginst=NULL;
		if(function_exists("curl_init"))
		{
			$fginst=new CURL_RemoteFileGetter();
		}
		else
		{
			$fginst=new URLFopen_RemoteFileGetter();
		}
		return $fginst;
	}

}

abstract class MagentoDirHandler
{
	protected $_magdir;
	protected $_lasterror;
	public function __construct($magurl)
	{
		$this->_magdir=$magurl;
		$this->_lasterror=array();
	}
	public function getMagentoDir()
	{
		return $this->_magdir;
	}
	public abstract function canhandle($url);
	public abstract function file_exists($filepath);
	public abstract function mkdir($path,$mask=null,$rec=false);
	public abstract function copy($srcpath,$destpath);
	public abstract function unlink($path);
	public abstract function chmod($path,$mask);
	public abstract function exec_cmd($cmd,$params,$workingdir = null);
}

class LocalMagentoDirHandler extends MagentoDirHandler
{
	public function __construct($magdir)
	{
		parent::__construct($magdir);
		MagentoDirHandlerFactory::getInstance()->registerHandler($this);
	}

	public function canHandle($url)
	{
		return (preg_match("|^.*?://.*$|",$url)==false);
	}

	public function file_exists($filename)
	{
		$mp=str_replace("//","/",$this->_magdir."/".str_replace($this->_magdir, '', $filename));

		return file_exists($mp);
	}

	public function mkdir($path,$mask=null,$rec=false)
	{
		$mp=str_replace("//","/",$this->_magdir."/".str_replace($this->_magdir, '', $path));

		if($mask==null)
		{
			$mask=octdec('755');
		}
		$ok=@mkdir($mp,$mask,$rec);
		if(!$ok)
		{
			$this->_lasterror=error_get_last();
		}
		return $ok;
	}

	public function chmod($path,$mask)
	{
		$mp=str_replace("//","/",$this->_magdir."/".str_replace($this->_magdir, '', $path));

		if($mask==null)
		{
			$mask=octdec('755');
		}
		$ok=@chmod($mp,$mask);
		if(!$ok)
		{
			$this->_lasterror=error_get_last();
		}
		return $ok;
	}

	public function getLastError()
	{
		return $this->_lasterror;
	}

	public function unlink($path)
	{
		$mp=str_replace("//","/",$this->_magdir."/".str_replace($this->_magdir, '', $path));
		return @unlink($mp);
	}

	public function copyFromRemote($remoteurl,$destpath)
	{
		$rfg=RemoteFileGetterFactory::getFGInstance();
		$mp=str_replace("//","/",$this->_magdir."/".str_replace($this->_magdir, '', $destpath));
		$ok=$rfg->copyRemoteFile($remoteurl,$mp);
		if(!$ok)
		{
			$this->_lasterror=$rfg->getErrors();
		}
		unset($rfg);
		return $ok;
	}

	public function copy($srcpath,$destpath)
	{
		$result=false;
		$destpath=str_replace("//","/",$this->_magdir."/".str_replace($this->_magdir, '', $destpath));
		if(preg_match('|^.*?://.*$|', $srcpath))
		{
			$result=$this->copyFromRemote($srcpath,$destpath);
		}
		else
		{
				
			$result=@copy($srcpath,$destpath);
			if(!$result)
			{
				$this->_lasterror=error_get_last();
			}
		}
		return $result;
	}

	public function exec_cmd($cmd,$params, $working_dir = null)
	{
		$full_cmd = $cmd." ".$params;
		$curdir=false;
		$precmd="";
		// If a working directory has been specified, switch to it
		// before running the requested command
		if(!empty($working_dir))
		{
			$curdir=getcwd();
			$wdir=realpath($working_dir);
			//get current directory
			if($curdir!=$wdir && $wdir!==false)
			{
				//trying to change using chdir
				if(!@chdir($wdir))
				{
					//if no success, use cd from shell
					$precmd="cd $wdir && ";
				}
			}
		}
		$full_cmd = $precmd. $full_cmd;

		$out=@shell_exec($full_cmd);

		//restore old directory if changed
		if($curdir)
		{
			@chdir($curdir);
		}

		if($out==null)
		{
			$this->_lasterror=array("type"=>" execution error","message"=>error_get_last());
			return false;
		}
		return $out;
	}
}