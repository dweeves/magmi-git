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
	
	public static function getExecMode() {
		
		$is_disabled=array();
		$disabled = explode(',', ini_get('disable_functions'));
		foreach ($disabled as $disableFunction) {
			$is_disabled[] = trim($disableFunction);
		}
		foreach(array("popen","shell_exec") as $func)
		{
			if(!in_array($func, $is_disabled))
			{
				return $func;
			}
		}
		return null;
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
	protected $_user;
	protected $_password;
	protected $_logger=null;
	
	public function setLogger($logger)
	{
		$this->_logger=$logger;
	}
	
	public function log($data)
	{
		if($this->_logger!=null)
		{
			$this->_logger->log($data);
		}
	}
	
	public abstract function urlExists($url);
	public abstract function copyRemoteFile($url,$dest);
	//using credentials
	public function setCredentials($user=null,$passwd=null)
	{
		$this->_user=$user;
		$this->_password=$password;
	}
	public function getErrors()
	{
		return $this->_errors;
	}
}

class CURL_RemoteFileGetter extends RemoteFileGetter
{
	protected $_cookie;
	protected $_lookup_opts;
	protected $_dl_opts;
	protected $_lookup;
	protected $_protocol;
	protected $_contexts;
	
	public function __construct()
	{
		$this->_contexts=array();
	}
	
	/*
	 * Creating a CURL context with adequate options from an URL
	 * For a given URL host/port/user , the same context is reused for optimizing performance
	 */
	public function createContext($url)
	{
		$curl_url=str_replace(" ","%20",$url);
		$context = curl_init();
		
		if(substr($url,0,4)=="http")
		{
			$this->_lookup=1;
			$this->_protocol="http";
			$this->_lookup_opts= array(CURLOPT_RETURNTRANSFER=>true,
					CURLOPT_HEADER=>true,
					CURLOPT_NOBODY=>true,
					CURLOPT_FOLLOWLOCATION=>true,
					CURLOPT_FILETIME=>true,
					CURLOPT_CUSTOMREQUEST=>"HEAD");
		
			$this->_dl_opts=array(
					CURLOPT_CUSTOMREQUEST=>"GET",
					CURLOPT_HEADER=>false,
					CURLOPT_NOBODY=>false,
					CURLOPT_FOLLOWLOCATION=>true,
					CURLOPT_UNRESTRICTED_AUTH=>true,
					CURLOPT_HTTPHEADER=> array('Expect:'));
			
		}
		else
		{
			if(substr($url,0,3)=="ftp")
			{
				$this->_protocol="ftp";
				$this->_lookup=0;
				$this->_dl_opts=array(CURLOPT_FTP_USE_EPSV=>0);
					
			}
		}
		
		return $context;
	}

	public function destroyContext($context)
	{
		curl_close($context);
	}

	public function __destruct()
	{
		foreach($this->_contexts as $k=>$ctx)
		{
			curl_close($this->_contexts[$k]);
		}
	}
	
	public function urlExists($remoteurl)
	{
		
		$context=$this->createContext($remoteurl);
		//assume existing urls
		if(!$this->_lookup)
		{
			return true;
		}
		//optimized lookup through curl
		curl_setopt_array($context, $this->_lookup_opts);
		
		/* Get the HTML or whatever is linked in $url. */
		$response = curl_exec($context);
		if($this->_protocol=="http")
		{
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
		}
		curl_close($context);
		return $exists;
	}

	//using credentials
	public function setCredentials($user=null,$passwd=null)
	{
		$this->_user=$user;
		$this->_password=$passwd;	
	}
	
	//using  cookie
	public function setCookie($cookie=null)
	{
		$this->_cookie=$cookie;	
	}
	
	public function copyRemoteFile($url,$dest)
	{
		$result=false;
		if($this->_user!=null)
		{
			$creds=$this->_user;
		}
		if($this->_password!=null)
		{
			$creds.=":".$this->_password;
		}
		try {
			$result=$this->getRemoteFile($url,$dest,$creds,$this->_cookie);
		}
		catch(Exception $e)
		{
			$this->_errors=array("type"=>"source error","message"=>$e->getMessage());
		}
		return $result;
	}

	public function setURLOptions($url,&$optab)
	{
			$optab[CURLOPT_URL]=$url;
	}
	
	
	public function getRemoteFile($url,$dest,$creds=null,$authmode=null,$cookies=null)
	{
		$ch=$this->createContext($url);
		$dl_opts=$this->_dl_opts;
		$lookup_opts=$this->_lookup_opts;
		$lookup=$this->_lookup;
		$outname=$dest;
		
		if($creds!="")
		{
			if($lookup!=0)
			{
				if(substr($url,0,4)=="http")
				{
					$lookup_opts[CURLOPT_HTTPAUTH]=CURLAUTH_ANY;
					$lookup_opts[CURLOPT_UNRESTRICTED_AUTH]=true;
				}
				$lookup_opts[CURLOPT_USERPWD]="$creds";
			}
	
	
			if(substr($url,0,4)=="http")
			{
				$dl_opts[CURLOPT_HTTPAUTH]=CURLAUTH_ANY;
				$dl_opts[CURLOPT_UNRESTRICTED_AUTH]=true;
			}
			$dl_opts[CURLOPT_USERPWD]="$creds";
		}
	
		if($cookies)
		{
			if($lookup!=0)
			{
				if(substr($url,0,4)=="http")
				{
					$lookup_opts[CURLOPT_COOKIE]=$cookies;
				}
			}
	
			if(substr($url,0,4)=="http")
			{
				$dl_opts[CURLOPT_COOKIE]=$cookies;
			}
		}
	
		if($lookup)
		{
			setURLOptions($url,$lookup_opts);
			//lookup , using HEAD request
			$ok=curl_setopt_array($ch,$lookup_opts);
			$res=curl_exec($ch);
			if($res!==false)
			{
				$lm=curl_getinfo($ch);
				if(curl_getinfo($ch,CURLINFO_HTTP_CODE)>400)
				{
					$resp = explode("\n\r\n", $res);
					$this->destroyContext($ch);
					throw new Exception("Cannot fetch $url :".$err);
	
				}
			}
			else
			{
				$lm=curl_getinfo($ch);
				$err=curl_error($ch);
				$this->destroyContext($ch);
				throw new  Exception("Cannot fetch $url : ".$err);
			}
	
		}
	
		$res=array("should_dl"=>true,"reason"=>"");
	
		if($res["should_dl"])
		{
			//clear url options
			$fp = fopen($outname, "w");
			if($fp==false)
			{
				$this->destroyContext($ch);
				throw new Exception("Cannot write file:$outname");
			}
			$dl_opts[CURLOPT_FILE]=$fp;	
			$ok=curl_setopt_array($ch, array());
			$this->setURLOptions($url,$dl_opts);
			
			//Download the file , force expect to nothing to avoid buffer save problem
			curl_setopt_array($ch,$dl_opts);
			$inf=curl_getinfo($ch);
			if(!curl_exec($ch))
			{
				if(curl_error($ch)!="")
				{
						$err="Cannot fetch $url :".curl_error($ch);
				}
				else {
						$err="CURL Error downloading $url";
				}
				$this->destroyContext($ch);
				fclose($fp);
				unlink($dest);
				throw new Exception($err);
			}
			
			fclose($fp);
	
		}

		$this->destroyContext($ch);
		
		//return the csv filename
		return true;
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
	private static $__fginsts=array();
	
	public static function getFGInstance($id="default")
	{
		if(!isset(self::$__fginsts[$id]))
		{
			if(function_exists("curl_init"))
			{
				self::$__fginsts[$id]=new CURL_RemoteFileGetter();
			}
			else
			{
				self::$__fginsts[$id]=new URLFopen_RemoteFileGetter();
			}
		}
		return self::$__fginsts[$id];
	}

}

abstract class MagentoDirHandler
{
	protected $_magdir;
	protected $_lasterror;
	protected $_exec_mode;
	public function __construct($magurl)
	{
		$this->_magdir=$magurl;
		$this->_lasterror=array();
		$this->_exec_mode=FSHelper::getExecMode();
	}
	
	public function getMagentoDir()
	{
		return $this->_magdir;
	}
	public function getexecmode()
	{
		return $this->_exec_mode;
	}
	
	public abstract function canhandle($url);
	public abstract function file_exists($filepath);
	public abstract function mkdir($path,$mask=null,$rec=false);
	public abstract function copy($srcpath,$destpath);
	public abstract function unlink($path);
	public abstract function chmod($path,$mask);
	public function isExecEnabled()
	{
		return $this->_exec_mode!=null;
	}
	public abstract function exec_cmd($cmd,$params,$workingdir = null);
}

class LocalMagentoDirHandler extends MagentoDirHandler
{
	protected $_rfgid;
	
	public function __construct($magdir)
	{
		parent::__construct($magdir);
		MagentoDirHandlerFactory::getInstance()->registerHandler($this);
		$this->_rfgid="default";
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
	
	
	public function setRemoteCredentials($user,$passwd)
	{
		$fginst=RemoteFileGetterFactory::getFGInstance($this->_rfgid);
		$fginst->setCredentials($user,$passwd);
	}
	
	public function setRemoteGetterId($rfgid)
	{
		$this->_rfgid=$rfgid;
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
		$rfg=RemoteFileGetterFactory::getFGInstance($this->_rfgid);
		$mp=str_replace("//","/",$this->_magdir."/".str_replace($this->_magdir, '', $destpath));
		$ok=$rfg->copyRemoteFile($remoteurl,$mp);
		if(!$ok)
		{
			$this->_lasterror=$rfg->getErrors();
		}
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
		//Handle Execution
		$emode=$this->getexecmode();
		switch($emode)
		{
			case "popen":
				$x=popen($full_cmd,"r");
				$out="";
				while(!feof($x))
				{
					$data=fread($x, 1024);
					$out.=$data;
					usleep(100000);
				}
				fclose($x);
				break;
			case "shell_exec":
				$out=shell_exec($full_cmd);
				break;
		}
		 
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