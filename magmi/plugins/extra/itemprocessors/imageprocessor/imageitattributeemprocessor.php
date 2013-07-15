<?php
class ImageAttributeItemProcessor extends Magmi_ItemProcessor
{

	protected $forcename=null;
	protected $magdir=null;
	protected $imgsourcedirs=array();
	protected $errattrs=array();
	protected $_lastnotfound="";
	protected $_lastimage="";
    protected $_handled_attributes=array();
	protected $_img_baseattrs=array("image","small_image","thumbnail");
	protected $_active=false;
	protected $_newitem;
	protected $_mdh;
	protected $debug;
	
	public function initialize($params)
	{
		//declare current class as attribute handler
		$this->registerAttributeHandler($this,array("frontend_input:(media_image|gallery)"));
		$this->magdir=Magmi_Config::getInstance()->getMagentoDir();
		$this->_mdh=MagentoDirHandlerFactory::getInstance()->getHandler($this->magdir);
		
		$this->forcename=$this->getParam("IMG:renaming");
		foreach($params as $k=>$v)
		{
			if(preg_match_all("/^IMG_ERR:(.*)$/",$k,$m))
			{
				$this->errattrs[$m[1][0]]=$params[$k];
			}
		}
		$this->debug=$this->getParam("IMG:debug",0);
	}

	public function getPluginInfo()
	{
		return array(
            "name" => "Image attributes processor",
            "author" => "Dweeves",
            "version" => "1.0.26",
			"url"=>$this->pluginDocUrl("Image_attributes_processor")
            );
	}
	
	public function handleGalleryTypeAttribute($pid,&$item,$storeid,$attrcode,$attrdesc,$ivalue)
	{
		//do nothing if empty
		if($ivalue=="")
		{
			return false;
		}
		//use ";" as image separator
		$images=explode(";",$ivalue);
		$imageindex=0;
		//for each image
		foreach($images as $imagefile)
		{
			//trim image file in case of spaced split
			$imagefile=trim($imagefile);
			//handle exclude flag explicitely
			$exclude=$this->getExclude($imagefile,false); 
			$infolist=explode("::",$imagefile);
			$label=null;
			if(count($infolist)>1)
			{
				$label=$infolist[1];
				$imagefile=$infolist[0];
			}
			unset($infolist);
			//copy it from source dir to product media dir
			$imagefile=$this->copyImageFile($imagefile,$item,array("store"=>$storeid,"attr_code"=>$attrcode,"imageindex"=>$imageindex==0?"":$imageindex));
			if($imagefile!==false)
			{
				//add to gallery
				$targetsids=$this->getStoreIdsForStoreScope($item["store"]);
				$vid=$this->addImageToGallery($pid,$storeid,$attrdesc,$imagefile,$targetsids,$label,$exclude);
			}
			$imageindex++;
		}
		unset($images);
		//we don't want to insert after that
		$ovalue=false;
		return $ovalue;
	}

	public function removeImageFromGallery($pid,$storeid,$attrdesc)
	{
		$t=$this->tablename('catalog_product_entity_media_gallery');
		$tv=$this->tablename('catalog_product_entity_media_gallery_value');
		
		$sql="DELETE $tv.* FROM $tv 
			JOIN $t ON $t.value_id=$tv.value_id AND $t.entity_id=? AND $t.attribute_id=?
			WHERE  $tv.store_id=?";
		$this->delete($sql,array($pid,$attrdesc["attribute_id"],$storeid));
		
	}
	
	public function getExclude(&$val,$default=true)
	{
		$exclude=$default;
		if($val[0]=="+" || $val[0]=="-")
		{
			$exclude=$val[0]=="-";
			$val=substr($val,1);
		}
		return $exclude;
	}
	
	public function findImageFile($ivalue)
	{
		//do no try to find remote image
		if(is_remote_path($ivalue))
		{
			return $ivalue;
		}
		//if existing, return it directly
		if(realpath($ivalue))
		{
			return $ivalue;
		}
		
		//ok , so it's a relative path
		$imgfile=false;
		$scandirs=explode(";",$this->getParam("IMG:sourcedir"));
		
		//iterate on image sourcedirs, trying to resolve file name based on input value and current source dir
		for($i=0;$i<count($scandirs) && $imgfile===false;$i++)
		{
			$sd=$scandirs[$i];
			//scandir is relative, use mdh
			if($sd[0]!="/")
			{
				$sd=$this->_mdh->getMagentoDir()."/".$sd;
			}
			$imgfile=abspath($ivalue,$sd);
		}
		return $imgfile;
	}
	
	public function handleImageTypeAttribute($pid,&$item,$storeid,$attrcode,$attrdesc,$ivalue)
	{
		//remove attribute value if empty
		if($ivalue=="")
		{
			$this->removeImageFromGallery($pid,$storeid,$attrdesc);
			return "__MAGMI_DELETE__";
		}
		
		//add support for explicit exclude
		$exclude=$this->getExclude($ivalue,true); 
		
	
		//else copy image file
		$imagefile=$this->copyImageFile($ivalue,$item,array("store"=>$storeid,"attr_code"=>$attrcode));
		$ovalue=$imagefile;
		//add to gallery as excluded
		if($imagefile!==false)
		{
			$label=null;
			if(isset($item[$attrcode."_label"]))
			{
				$label=$item[$attrcode."_label"];
			}
			$targetsids=$this->getStoreIdsForStoreScope($item["store"]);
			$vid=$this->addImageToGallery($pid,$storeid,$attrdesc,$imagefile,$targetsids,$label,$exclude,$attrdesc["attribute_id"]);
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
	public function getImageId($pid,$attid,$imgname,$refid=null)
	{
		$t=$this->tablename('catalog_product_entity_media_gallery');
	
		$sql="SELECT $t.value_id FROM $t ";
		if($refid!=null)
		{
			$vc=$this->tablename('catalog_product_entity_varchar');
			$sql.=" JOIN $vc ON $t.entity_id=$vc.entity_id AND $t.value=$vc.value AND $vc.attribute_id=?
					WHERE $t.entity_id=?";		
			$imgid=$this->selectone($sql,array($refid,$pid),'value_id');
		}
		else
		{	
			$sql.=" WHERE value=? AND entity_id=? AND attribute_id=?";
			$imgid=$this->selectone($sql,array($imgname,$pid,$attid),'value_id');
		}
	
		if($imgid==null)
		{
			// insert image in media_gallery
			$sql="INSERT INTO $t
				(attribute_id,entity_id,value)
				VALUES
				(?,?,?)";

			$imgid=$this->insert($sql,array($attid,$pid,$imgname));
		}
		else
		{
			$sql="UPDATE $t
				 SET value=?
				 WHERE value_id=?";
			$this->update($sql,array($imgname,$imgid));
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
		$sql="DELETE emgv,emg FROM `$tgv` as emgv 
			JOIN `$tg` AS emg ON emgv.value_id = emg.value_id AND emgv.store_id=?
			WHERE emg.entity_id=? AND emg.attribute_id=?";
		$this->delete($sql,array($storeid,$pid,$attid));

	}
	/**
	 * adds an image to product image gallery only if not already exists
	 * @param int $pid  : product id to test image existence in gallery
	 * @param array $attrdesc : product attribute description
	 * @param string $imgname : image file name (relative to /products/media in magento dir)
	 */
	public function addImageToGallery($pid,$storeid,$attrdesc,$imgname,$targetsids,$imglabel=null,$excluded=false,$refid=null)
	{
		$gal_attinfo=$this->getAttrInfo("media_gallery");
		$tg=$this->tablename('catalog_product_entity_media_gallery');
		$tgv=$this->tablename('catalog_product_entity_media_gallery_value');
		$vid=$this->getImageId($pid,$gal_attinfo["attribute_id"],$imgname,$refid);
		if($vid!=null)
		{
		
			#get maximum current position in the product gallery
			$sql="SELECT MAX( position ) as maxpos
					 FROM $tgv AS emgv
					 JOIN $tg AS emg ON emg.value_id = emgv.value_id AND emg.entity_id = ?
					 WHERE emgv.store_id=?
			 		 GROUP BY emg.entity_id";
			$pos=$this->selectone($sql,array($pid,$storeid),'maxpos');
			$pos=($pos==null?0:$pos+1);
			#insert new value (ingnore duplicates)
				
			$vinserts=array();
			$data=array();
			 
			foreach($targetsids as $tsid)
			{
				$vinserts[]="(?,?,?,?,".($imglabel==null?"NULL":"?").")";
				$data=array_merge($data,array($vid,$tsid,$pos,$excluded?1:0));
				if($imglabel!=null)
				{
					$data[]=$imglabel;
				}
			}
			
			if(count($data)>0)
			{
				$sql="INSERT INTO $tgv
					(value_id,store_id,position,disabled,label)
					VALUES ".implode(",",$vinserts)." 
					ON DUPLICATE KEY UPDATE label=VALUES(`label`),disabled=VALUES(`disabled`)";
				$this->insert($sql,$data);
			}
			unset($vinserts);
			unset($data);
		}
	}

	public function parsename($info,$item,$extra)
	{
		$info=$this->parseCalculatedValue($info,$item,$extra);
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
	
	
	public function getImagenameComponents($fname,$formula,$extra)
	{
		$matches=array();
		$xname=$fname;
		if(preg_match("|re::(.*)::(.*)|",$formula,$matches))
		{
			$rep=$matches[2];
			$xname=preg_replace("|".$matches[1]."|",$rep,$xname);
			$extra['parsed']=true;
		}
		$xname=basename($xname);
		$m=preg_match("/(.*)\.(jpg|png|gif)$/i",$xname,$matches);
		if($m)
		{
			$extra["imagename"]=$xname;
			$extra["imagename.ext"]=$matches[2];
			$extra["imagename.noext"]=$matches[1];
		}
		else
		{
			$uid=uniqid("img",true);
			$extra=array_merge($extra,array("imagename"=>"$uid.jpg","imagename.ext"=>"jpg","imagename.noext"=>$uid));
		}
			
		return $extra;
	}
	public function getTargetName($fname,$item,$extra)
	{
		$cname=basename($fname);
		if(isset($this->forcename) && $this->forcename!="")
		{
			$extra=$this->getImagenameComponents($fname,$this->forcename,$extra);
			$pname=($extra['parsed']?$extra['imagename']:$this->forcename);
			$cname=$this->parsename($pname,$item,$extra);
		}
		$cname=strtolower(preg_replace("/%[0-9][0-9|A-F]/","_",rawurlencode($cname)));
		
		return $cname;
	}
	
	public function saveImage($imgfile,$target)
	{
		$result=$this->_mdh->copy($imgfile,$target);
		return $result;		
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
			if($this->_newitem){
				$this->fillErrorAttributes($item);
			};
			return false;
		}
		
		$source=$this->findImageFile($imgfile);
		if($source==false)
		{
			$this->log("$imgfile cannot be found in images path","warning");
			return false;
		}
		$imgfile=$source;
		$checkexist= ($this->getParam("IMG:existingonly")=="yes");
		$curlh=false;
		$bimgfile=$this->getTargetName($imgfile,$item,$extra);
		//source file exists
		$i1=$bimgfile[0];
		$i2=$bimgfile[1];
		$l2d="media/catalog/product/$i1/$i2";
		$te="$l2d/$bimgfile";
		$result="/$i1/$i2/$bimgfile";
		/* test for same image */
		if($result==$this->_lastimage)
		{
			return $result;
		}
		/* test if imagefile comes from export */
		if(!$this->_mdh->file_exists("$te") || $this->getParam("IMG:writemode")=="override")
		{
			/* try to recursively create target dir */
			if(!$this->_mdh->file_exists("$l2d"))
			{
				$tst=$this->_mdh->mkdir($l2d,Magmi_Config::getInstance()->getDirMask(),true);
				if(!$tst)
				{
					$errors=$this->_mdh->getLastError();
					$this->log("error creating $l2d: {$errors["type"]},{$errors["message"]}","warning");
					unset($errors);
					return false;
				}
			}

			if(!$this->saveImage($imgfile,"$l2d/$bimgfile"))
			{
				$errors=$this->_mdh->getLastError();
				$this->fillErrorAttributes($item);
				$this->log("error copying $l2d/$bimgfile : {$errors["type"]},{$errors["message"]}","warning");
				unset($errors);
				return false;
			}
			else
			{
				@$this->_mdh->chmod("$l2d/$bimgfile",Magmi_Config::getInstance()->getFileMask());			
			}
		}
		$this->_lastimage=$result;
		/* return image file name relative to media dir (with leading / ) */
		return $result;
	}

	
	public function updateLabel($attrdesc,$pid,$sids,$label)
	{
		$tg=$this->tablename('catalog_product_entity_media_gallery');
		$tgv=$this->tablename('catalog_product_entity_media_gallery_value');
		$vc=$this->tablename('catalog_product_entity_varchar');
		$sql="UPDATE $tgv as emgv 
		JOIN $tg as emg ON emg.value_id=emgv.value_id AND emg.entity_id=?
		JOIN $vc  as ev ON ev.entity_id=emg.entity_id AND ev.value=emg.value and ev.attribute_id=? 
		SET label=? 
		WHERE emgv.store_id IN (".implode(",",$sids).")";
		$this->update($sql,array($pid,$attrdesc["attribute_id"],$label));
	}
	
	public function processItemAfterId(&$item,$params=null)
	{
		
		if(!$this->_active)
		{
			return true;
		}
		$this->_newitem=$params["new"];
		$pid=$params["product_id"];
		foreach($this->_img_baseattrs as $attrcode)
		{
			//if only image/small_image/thumbnail label is present (ie: no image field)
			if(isset($item[$attrcode."_label"]) && !isset($item[$attrcode]))
			{
				//force label update
				$attrdesc=$this->getAttrInfo($attrcode);
				$this->updateLabel($attrdesc,$pid,$this->getItemStoreIds($item,$attr_desc["is_global"]),$item[$attrcode."_label"]);		
				unset($attrdesc);
			}
		}
		//Reset media_gallery
		$galreset=!(isset($item["media_gallery_reset"])) || $item["media_gallery_reset"]==1;
		$forcereset = (isset($item["media_gallery_reset"])) && $item["media_gallery_reset"]==1;
		
		if( (isset($item["media_gallery"]) && $galreset) || $forcereset)
		{
			$gattrdesc=$this->getAttrInfo("media_gallery");
			$sids=$this->getItemStoreIds($item,$gattrdesc["is_global"]);
			foreach($sids as $sid)
			{
				$this->resetGallery($pid,$sid,$gattrdesc["attribute_id"]);
			}
		}
		return true;
			
	}
	public function processColumnList(&$cols,$params=null)
	{
		//automatically add modified attributes if not found in datasource
		
		//automatically add media_gallery for attributes to handle		
		$imgattrs=array_intersect(array_merge($this->_img_baseattrs,array('media_gallery')),$cols);
		if(count($imgattrs)>0)
		{
			$this->_active=true;
			$cols=array_unique(array_merge(array_keys($this->errattrs),$cols,$imgattrs));
		}
		else
		{
			$this->log("no image attributes found in datasource, disabling image processor","startup");
		}
		return true;
	}
	
	//Cleanup gallery from removed images if no more image values are present in any store 
	public function endImport()
	{
		
		if(!$this->_active)
		{
			return;
		}
		$attids=array();
		foreach($this->_img_baseattrs as $attrcode)
		{
			$inf=$this->getAttrInfo($attrcode);
			if(count($inf)>0)
			{
				$attids[]=$inf["attribute_id"];
			}
		}
		if(count($attids)>0)
		{
			$tg=$this->tablename('catalog_product_entity_media_gallery');
			$tgv=$this->tablename('catalog_product_entity_media_gallery_value');
			$sql="DELETE emg.* FROM $tg as emg
			LEFT JOIN (SELECT emg.value_id,count(emgv.value_id) as cnt FROM  $tgv as emgv JOIN $tg as emg  ON emg.value_id=emgv.value_id GROUP BY emg.value_id ) as t1 ON t1.value_id=emg.value_id
			WHERE attribute_id IN (".implode(",",$attids).") AND t1.cnt IS NULL";
			$this->delete($sql);
		}
		else
		{
			$this->log("Unexpected problem in image attributes retrieval","warning");
		}	
		unset($attids);
	}
	

}