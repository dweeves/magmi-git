<?php
class ImageAttributeItemProcessor extends Magmi_ItemProcessor
{

	protected $forcename=null;
	protected $magdir=null;
	protected $imgsourcedir=null;
	protected $errattrs=array();
	protected $_lastnotfound="";
	protected $_lastimage="";
	
	public function initialize($params)
	{
		//declare current class as attribute handler
		$this->registerAttributeHandler($this,array("frontend_input:(media_image|gallery)"));
		$this->magdir=$this->getMagentoDir();
		$this->imgsourcedir=realpath($this->magdir."/".$this->getParam("IMG:sourcedir"));
		if(!file_exists($this->imgsourcedir))
		{
			$this->imgsourcedir=$this->getParam("IMG:sourcedir");
		}
		$this->forcename=$this->getParam("IMG:renaming");
		foreach($params as $k=>$v)
		{
			if(preg_match_all("/^IMG_ERR:(.*)$/",$k,$m))
			{
				$this->errattrs[$m[1][0]]=$params[$k];
			}
		}	
	}

	public function getPluginInfo()
	{
		return array(
            "name" => "Image attributes processor",
            "author" => "Dweeves",
            "version" => "0.1.0"
            );
	}
	public function handleGalleryTypeAttribute($pid,&$item,$storeid,$attrcode,$attrdesc,$ivalue)
	{
		//do nothing if empty
		if($ivalue=="")
		{
			return false;
		}
		$attid=$attrdesc["attribute_id"];
		$this->resetGallery($pid,$storeid,$attid);
		//use ";" as image separator
		$images=explode(";",$ivalue);
		//for each image
		foreach($images as $imagefile)
		{
			//copy it from source dir to product media dir
			$imagefile=$this->copyImageFile($imagefile,$item,array("store"=>$storeid,"attr_code"=>$attrcode));
			if($imagefile!==false)
			{
				//add to gallery
				$vid=$this->addImageToGallery($pid,$storeid,$attrdesc,$imagefile);
			}
		}
		unset($images);
		//we don't want to insert after that
		$ovalue=false;
	}

	public function handleImageTypeAttribute($pid,&$item,$storeid,$attrcode,$attrdesc,$ivalue)
	{
		//do nothing if empty
		if($ivalue=="")
		{
			return false;
		}
		//else copy image file
		$imagefile=$this->copyImageFile($ivalue,$item,array("store"=>$storeid,"attr_code"=>$attrcode));
		$ovalue=$imagefile;
		//add to gallery as excluded
		if($imagefile!==false)
		{
			$vid=$this->addImageToGallery($pid,$storeid,$attrdesc,$imagefile,true);
		}
		return $ovalue;
	}


	public function handleVarcharAttribute($pid,&$item,$storeid,$attrcode,$attrdesc,$ivalue)
	{

		//if it's a gallery
		switch($attrdesc["frontend_input"])
		{
			case "gallery":
				$ovalue=$this->handleGalleryTypeAttribute($pid,$item,$storeid,$attrcode,$attrdesc,$ivalue);
				break;
			case "media_image":
				$ovalue=$this->handleImageTypeAttribute($pid,$item,$storeid,$attrcode,$attrdesc,$ivalue);
				break;
			default:
				$ovalue="__MAGMI_UNHANDLED__";
		}
		return $ovalue;
	}

	/**
	 * imageInGallery
	 * @param int $pid  : product id to test image existence in gallery
	 * @param string $imgname : image file name (relative to /products/media in magento dir)
	 * @return bool : if image is already present in gallery for a given product id
	 */
	public function getImageId($pid,$attid,$imgname)
	{
		$t=$this->tablename('catalog_product_entity_media_gallery');
		$imgid=$this->selectone("SELECT value_id FROM $t WHERE value=? AND entity_id=? AND attribute_id=?" ,
		array($imgname,$pid,$attid),
								'value_id');
		if($imgid==null)
		{
			// insert image in media_gallery
			$sql="INSERT INTO $t
				(attribute_id,entity_id,value)
				VALUES
				(?,?,?)";

			$imgid=$this->insert($sql,array($attid,$pid,$imgname));
		}
		return $imgid;
	}

	/**
	 * reset product gallery
	 * @param int $pid : product id
	 */
	public function resetGallery($pid,$storeid,$attid)
	{
		$tgv=$this->tablename('catalog_product_entity_media_gallery_value');
		$tg=$this->tablename('catalog_product_entity_media_gallery');
		$sql="DELETE emgv,emg FROM `$tgv` as emgv JOIN `$tg` AS emg ON emgv.value_id = emg.value_id AND emgv.store_id=?
		WHERE emg.entity_id=? AND emg.attribute_id=?";
		$this->delete($sql,array($storeid,$pid,$attid));

	}
	/**
	 * adds an image to product image gallery only if not already exists
	 * @param int $pid  : product id to test image existence in gallery
	 * @param array $attrdesc : product attribute description
	 * @param string $imgname : image file name (relative to /products/media in magento dir)
	 */
	public function addImageToGallery($pid,$storeid,$attrdesc,$imgname,$excluded=false)
	{
		$gal_attinfo=$this->getAttrInfo("media_gallery");
		$vid=$this->getImageId($pid,$gal_attinfo["attribute_id"],$imgname);
		$tg=$this->tablename('catalog_product_entity_media_gallery');
		$tgv=$this->tablename('catalog_product_entity_media_gallery_value');
		#get maximum current position in the product gallery
		$sql="SELECT MAX( position ) as maxpos
				 FROM $tgv AS emgv
				 JOIN $tg AS emg ON emg.value_id = emgv.value_id AND emg.entity_id = ?
				 WHERE emgv.store_id=?
		 		 GROUP BY emg.entity_id";
		$pos=$this->selectone($sql,array($pid,$storeid),'maxpos');
		$pos=($pos==null?0:$pos+1);
		#insert new value (ingnore duplicates)
		$sql="INSERT IGNORE INTO $tgv
			(value_id,store_id,position,disabled)
			VALUES(?,?,?,?)";	
		$data=array($vid,$storeid,$pos,$excluded?1:0);
		$this->insert($sql,$data);
		unset($data);
	}

	public function parsename($info,$item,$extra)
	{
		while(preg_match("|\{item\.(.*?)\}|",$info,$matches))
		{
			foreach($matches as $match)
			{
				if($match!=$matches[0])
				{
					if(isset($item[$match]))
					{
						$rep=$item[$match];
					}
					else
					{
						$rep="";
					}
					$info=str_replace($matches[0],$rep,$info);
				}
			}
		}
		while(preg_match("|\{magmi\.(.*?)\}|",$info,$matches))
		{
			foreach($matches as $match)
			{
				if($match!=$matches[0])
				{
					if(isset($extra[$match]))
					{
						$rep=$extra[$match];
					}
					else
					{
						$rep="";
					}
					$info=str_replace($matches[0],$rep,$info);
				}
			}
		}
		
		return $info;
	}
	
	public function getPluginParams($params)
	{
		$pp=array();
		foreach($params as $k=>$v)
		{
			if(preg_match("/^IMG(_ERR)?:.*$/",$k))
			{
				$pp[$k]=$v;
			}
		}	
		return $pp;
	}
	
	public function fillErrorAttributes(&$item)
	{
		foreach($this->errattrs as $k=>$v)
		{
			$this->addExtraAttribute($k);
			$item[$k]=$v;
		}
	}
	public function getTargetName($fname,$item,$extra)
	{
		if(isset($this->forcename) && $this->forcename!="")
		{
			$m=preg_match("/(.*?)\.(jpg|png|gif)$/i",$cname,$matches);	
			$extra["imagename"]=$cname;
			$extra["imagename.ext"]=$matches[2];
			$extra["imagename.noext"]=$matches[1];
			$cname=$this->parsename($this->forcename,$item,$extra);
		}
		else
		{
			$cname=$fname;	
		}
		$cname=strtolower(preg_replace("/%[0-9][0-9|A-F]/","_",rawurlencode($cname)));
		
		return $cname;
	}
	
	public function createUrlContext($url)
	{
		if(function_exists("curl_init"))
		{
			$handle = curl_init(str_replace(" ","%20",$url));
		}
		return $handle;
	}
	public function destroyUrlContext($context)
	{
		if($context!=false)
		{
			curl_close($context);
		}
	}
	//Url testing
	public function Urlexists($url,$context)
	{
		//optimized lookup through curl
		if($context!==false)
		{
			
			curl_setopt($context,  CURLOPT_HEADER, TRUE);
			curl_setopt( $context, CURLOPT_RETURNTRANSFER, true ); 
			curl_setopt( $context, CURLOPT_CUSTOMREQUEST, 'HEAD' ); 
			curl_setopt( $context, CURLOPT_NOBODY, true );

			/* Get the HTML or whatever is linked in $url. */
			$response = curl_exec($context);

			/* Check for 404 (file not found). */
			$httpCode = curl_getinfo($context, CURLINFO_HTTP_CODE);
			$exists = ($httpCode==200);
		}
		else
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
		return $exists;
	}
	
	
	public function saveImage($fname,$target,$context)
	{
		if($context==false)
		{
			if(!@copy($fname,$target))
			{
				return false;
			}
		}
		else
		{
			$fp = @fopen($target, 'wb');
			if($fp!==false)
			{
				curl_setopt($context, CURLOPT_RETURNTRANSFER, false);
				curl_setopt( $context, CURLOPT_CUSTOMREQUEST, 'GET' ); 
				curl_setopt( $context, CURLOPT_NOBODY, false);
				curl_setopt($context, CURLOPT_FILE, $fp);
				curl_setopt($context, CURLOPT_HEADER, 0);
				curl_setopt($context, CURLOPT_FOLLOWLOCATION, 1);
				
				curl_exec($context);
				return true;
			}
			else
			{
				$errors= error_get_last();
				$this->fillErrorAttributes($item);
				$this->log("error copying $target : {$errors["type"]},{$errors["message"]}","warning");
				return false;
			}
		}
		return true;
	}
	/**
	 * copy image file from source directory to
	 * product media directory
	 * @param $imgfile : name of image file name in source directory
	 * @return : name of image file name relative to magento catalog media dir,including leading
	 * directories made of first char & second char of image file name.
	 */
	public function copyImageFile($imgfile,&$item,$extra)
	{
		if($imgfile==$this->_lastnotfound)
		{
			return false;
		}
		
		$curlh=false;
		$bimgfile=$this->getTargetName(basename($imgfile),$item,$extra);
		//source file exists
		$i1=$bimgfile[0];
		$i2=$bimgfile[1];
		$l1d="$this->magdir/media/catalog/product/$i1";
		$l2d="$l1d/$i2";
		$te="$l2d/$bimgfile";
		$result="/$i1/$i2/$bimgfile";
		/* test for same image */
		if($imgfile==$this->_lastimage)
		{
			return $result;
		}
		/* test if imagefile comes from export */
		if(!file_exists("$te") || $this->getParam("IMG:writemode")=="override")
		{
			$exists=false;
			$fname=$imgfile;
			if(preg_match("|.*?://.*|",$imgfile))
			{
				$imgfile=str_replace($bimgfile,urlencode($bimgfile),$imgfile);
				
				$curlh=$this->createUrlContext($imgfile);
				$exists=$this->Urlexists($imgfile,$curlh);
			}
			else
			{
				$tfile=($imgfile[0]==DIRECTORY_SEPARATOR?substr($imgfile,1):$imgfile);
				$fname=$this->imgsourcedir.DIRECTORY_SEPARATOR.$tfile;
				$exists=(realpath($fname)!==false);
			}
			if(!$exists)
			{
				$this->log("$fname not found, skipping image","warning");
				$this->fillErrorAttributes($item);
				$this->_lastnotfound=$imgfile;
				$this->destroyUrlContext($context);
				return false;
			}
			/* test if 1st level product media dir exists , create it if not */
			if(!file_exists("$l1d"))
			{
				$result=@mkdir($l1d,0777);
				if(!$result)
				{
					$errors= error_get_last();
					$this->log("error creating $l1d: {$errors["type"]},{$errors["message"]}","warning");
					$this->destroyUrlContext($context);
					return false;
				}
			}
			/* test if 2nd level product media dir exists , create it if not */
			if(!file_exists("$l2d"))
			{
				$result=@mkdir($l2d,0777);
				if(!$result)
				{
					$errors= error_get_last();
					$this->log("error creating $l2d: {$errors["type"]},{$errors["message"]}","warning");
					$this->destroyUrlContext($context);
					return false;
				}
			}

			/* test if image already exists ,if not copy from source to media dir*/
			if(!file_exists("$l2d/$bimgfile"))
			{
				if(!$this->saveImage($imgfile,"$l2d/$bimgfile",$curlh))
				{
					$errors=error_get_last();
					$this->fillErrorAttributes($item);
					$this->log("error copying $l2d/$bimgfile : {$errors["type"]},{$errors["message"]}","warning");
					$this->destroyUrlContext($context);
					return false;
				}
				$this->destroyUrlContext($context);
				@chmod("$l2d/$bimgfile",0664);
			}
		}
		$this->_lastimage=$imgfile;
		/* return image file name relative to media dir (with leading / ) */
		return $result;
	}

	public function processColumnList(&$cols,$params=null)
	{
		//automatically add modified attributes if not found in datasource
		//automatically add media_gallery for attributes to handle
		$cols=array_unique(array_merge(array_keys($this->errattrs),array_merge($cols,array("media_gallery"))));
		return true;
	}
	

}