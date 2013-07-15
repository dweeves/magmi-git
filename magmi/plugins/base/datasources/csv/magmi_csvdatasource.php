<?php
require_once("magmi_csvreader.php");


class Magmi_CSVDataSource extends Magmi_Datasource
{
	protected $_csvreader;
	
	public function initialize($params)
	{
		$this->_csvreader=new Magmi_CSVReader();
		$this->_csvreader->bind($this);
		$this->_csvreader->initialize();
		
	}
	
	public function getAbsPath($path)
	{
		
		return abspath($path,$this->getScanDir());
		
	}
	
	public function getScanDir($resolve=true)
	{
		$scandir=$this->getParam("CSV:basedir","var/import");
		if(!isabspath($scandir))
		{
			$scandir=abspath($scandir,Magmi_Config::getInstance()->getMagentoDir(),$resolve);
		}
		return $scandir;	
	}
	
	public function getCSVList()
	{
		$scandir=$this->getScanDir();
		$files=glob("$scandir/*.csv");
		return $files;
	}
	
	public function getPluginParams($params)
	{
		$pp=array();
		foreach($params as $k=>$v)
		{
			if(preg_match("/^CSV:.*$/",$k))
			{
				$pp[$k]=$v;
			}
		}
		return $pp;
	}
	
	public function getPluginInfo()
	{
		return array("name"=>"CSV Datasource",
					 "author"=>"Dweeves",
					 "version"=>"1.3");
	}
	
	public function getRecordsCount()
	{
		return $this->_csvreader->getLinesCount();
	}
	
	public function getAttributeList()
	{
		
	}
	
  public function getRemoteFile($url,$creds=null,$authmode=null,$cookies=null)
  {
	$ch = curl_init($url);
	$this->log("Fetching CSV: $url","startup");
			//output filename (current dir+remote filename)
	$csvdldir=dirname(__FILE__)."/downloads";
	if(!file_exists($csvdldir))
	{
		@mkdir($csvdldir);
		@chmod($csvdldir, Magmi_Config::getInstance()->getDirMask());
	}
	
		$outname=$csvdldir."/".basename($url);
		$ext = substr(strrchr($outname, '.'), 1);
  		if($ext!=".txt" && $ext!=".csv")
  		{
  			$outname=$outname.".csv";
  		}
		//open file for writing
		if(file_exists($outname))
		{
			unlink($outname);
		}
		$fp = fopen($outname, "w");
		if($fp==false)
		{
			throw new Exception("Cannot write file:$outname");
		}
	if(substr($url,0,4)=="http")
	{
		$lookup=1;
                
  	  $lookup_opts= array(CURLOPT_RETURNTRANSFER=>true,
							     CURLOPT_HEADER=>true,
							     CURLOPT_NOBODY=>true,
							     CURLOPT_FOLLOWLOCATION=>true,
							     CURLOPT_FILETIME=>true,
							     CURLOPT_CUSTOMREQUEST=>"HEAD");
							  
    	$dl_opts=array(CURLOPT_FILE=>$fp,
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
			$lookup=0;
			$dl_opts=array(CURLOPT_FILE=>$fp);
		}
	}
	
	
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
		//lookup , using HEAD request
		$ok=curl_setopt_array($ch,$lookup_opts);
		$res=curl_exec($ch);
		if($res!==false)
		{
			$lm=curl_getinfo($ch);
			if(curl_getinfo($ch,CURLINFO_HTTP_CODE)!=200)
			{
				$resp = explode("\n\r\n", $res);
				$this->log("http header:<pre>".$resp[0]."</pre>","error");
				throw new Exception("Cannot fetch $url");
				
			}
		}
		else
		{
			$lm=curl_getinfo($ch);
			throw new  Exception("Cannot fetch $url");
		}

	}
	
	$res=array("should_dl"=>true,"reason"=>"");

	if($res["should_dl"])
	{
	    //clear url options
		$ok=curl_setopt_array($ch, array());
		
		//Download the file , force expect to nothing to avoid buffer save problem
	    curl_setopt_array($ch,$dl_opts);
		curl_exec($ch);
		if(curl_error($ch)!="")
		{
			$this->log(curl_error($ch),"error");
			throw new Exception("Cannot fetch $url");
		}
		else
		{
			$lm=curl_getinfo($ch);
			
			$this->log("CSV Fetched in ".$lm['total_time']. "secs","startup");
		}
		curl_close($ch);
		fclose($fp);
		
	}
	else
	{
	    curl_close($ch);
	    //bad file or bad hour, no download this time
		$this->log("No dowload , ".$res["reason"],"info");
	}
    //return the csv filename
	return $outname;
}
	public function beforeImport()
	{
		if($this->getParam("CSV:importmode","local")=="remote")
		{
			$url=$this->getParam("CSV:remoteurl","");
			$creds="";
			$authmode="";
			if($this->getParam("CSV:remoteauth",false)==true)
			{
				$user=$this->getParam("CSV:remoteuser");
				$pass=$this->getParam("CSV:remotepass");
				
				$authmode=$this->getParam("CSV:authmode");
				$creds="$user:$pass";
			}
			$cookies=$this->getParam("CSV:remotecookie");
			$outname=$this->getRemoteFile($url,$creds,$authmode,$cookies);
			$this->setParam("CSV:filename", $outname);
			$this->_csvreader->initialize();
		}
		return $this->_csvreader->checkCSV();
	}
	
	public function afterImport()
	{
		
	}
	
	public function startImport()
	{
		$this->_csvreader->openCSV();
	}
	
	public function getColumnNames($prescan=false)
	{
		return $this->_csvreader->getColumnNames($prescan);
	}
	
	public function endImport()
	{
		$this->_csvreader->closeCSV();
	}

	
	public function getNextRecord()
	{
		return $this->_csvreader->getNextRecord();
	}
	

}