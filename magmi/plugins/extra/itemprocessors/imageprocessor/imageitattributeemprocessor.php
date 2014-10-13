<?php

class ImageAttributeItemProcessor extends Magmi_ItemProcessor
{
    protected $forcename = null;
    protected $magdir = null;
    protected $imgsourcedirs = array();
    protected $errattrs = array();
    protected $_errorimgs = array();
    protected $_handled_attributes = array();
    protected $_img_baseattrs = array("image","small_image","thumbnail");
    protected $_active = false;
    protected $_newitem;
    protected $_mdh;
    protected $_remoteroot = "";
    protected $_wildcard = false;
    protected $debug;

    public function initialize($params)
    {
        // declare current class as attribute handler
        $this->registerAttributeHandler($this, array("frontend_input:(media_image|gallery)"));
        $this->magdir = Magmi_Config::getInstance()->getMagentoDir();
        $this->_mdh = MagentoDirHandlerFactory::getInstance()->getHandler($this->magdir);
        $this->_mdh->setRemoteGetterId("image");
        // remote root
        if ($this->getParam("IMG:remoteroot", ""))
        {
            if ($this->getParam("IMG:remoteauth", false) == true)
            {
                $this->_mdh->setRemoteCredentials($this->getParam("IMG:remoteuser"), $this->getParam("IMG:remotepass"));
            }
            $this->_remoteroot = $this->getParam("IMG:remoteroot");
        }
        $this->forcename = $this->getParam("IMG:renaming");
        foreach ($params as $k => $v)
        {
            if (preg_match_all("/^IMG_ERR:(.*)$/", $k, $m))
            {
                $this->errattrs[$m[1][0]] = $params[$k];
            }
        }
        $this->debug = $this->getParam("IMG:debug", 0);
		$this->_wildcard = $this->getParam("IMG:wildcard", false);
    }

    public function getPluginInfo()
    {
        return array("name"=>"Image attributes processor","author"=>"Dweeves","version"=>"1.0.30",
            "url"=>$this->pluginDocUrl("Image_attributes_processor"));
    }

    public function isErrorImage($img)
    {
        return isset($this->_errorimgs[$img]);
    }

    public function cachesort($v1, $v2)
    {
        return $v2 - $v1;
    }

    public function setErrorImg($img)
    {
        $mxerrcache = $this->getParam("IMG:maxerrorcache", 100);
        // remove limit => 10%
        $removelimit = intval($mxerrcache / 10);
        if (count($this->_errorimgs) > $mxerrcache)
        {
            uasort($this->_errorimgs, array($this,"cachesort"));
            array_splice($this->_errorimgs, $removelimit, count($this->_errorimgs));
        }
        $this->_errorimgs[$img] = microtime(true);
    }
    
    // Image removal feature
    public function handleRemoveImages($pid, &$item, $ivalue)
    {
        $gal_attinfo = $this->getAttrInfo("media_gallery");
        $t = $this->tablename('catalog_product_entity_media_gallery');
        $tv = $this->tablename('catalog_product_entity_media_gallery_value');
        $rimgs = explode(";", $ivalue);
        $rivals = array();
        foreach ($rimgs as $rimg)
        {
            $rivals[] = '/' . implode('/', array($rimg[0],$rimg[1],$rimg));
        }
        
        $sql = "DELETE $t.* FROM $t
		WHERE $t.entity_id=? AND $t.attribute_id=? AND $t.value IN (" . $this->arr2values($rivals) . ")";
        $this->delete($sql, array_merge(array($pid,$gal_attinfo["attribute_id"]), $rivals));
    }

    public function handleGalleryTypeAttribute($pid, &$item, $storeid, $attrcode, $attrdesc, $ivalue)
    {
        // do nothing if empty
        if ($ivalue == "")
        {
            return false;
        }
        // use ";" as image separator
        $images = explode(";", $ivalue);
        $imageindex = 0;
        // for each image
        foreach ($images as $imagefile)
        {
            // trim image file in case of spaced split
            $imagefile = trim($imagefile);
            // handle exclude flag explicitely
            $exclude = $this->getExclude($imagefile, false);
            $infolist = explode("::", $imagefile);
            $label = null;
            if (count($infolist) > 1)
            {
                $label = $infolist[1];
                $imagefile = $infolist[0];
            }
            unset($infolist);
            $extra=array("store"=>$storeid,"attr_code"=>$attrcode,"imageindex"=>$imageindex == 0 ? "" : $imageindex);
            // copy it from source dir to product media dir
            $imagefiles = $this->copyImageFile($imagefile, $item, $extra);
			unset($extra);
			
			// add to gallery
			$targetsids = $this->getStoreIdsForStoreScope($item["store"]);
			foreach($imagefiles as $file){
				$vid = $this->addImageToGallery($pid, $storeid, $attrdesc, $file, $targetsids, $label, $exclude);
				$imageindex++;
			}
        }
        unset($images);
        // we don't want to insert after that
        $ovalue = false;
        return $ovalue;
    }

    public function removeImageFromGallery($pid, $storeid, $attrdesc)
    {
        $t = $this->tablename('catalog_product_entity_media_gallery');
        $tv = $this->tablename('catalog_product_entity_media_gallery_value');
        
        $sql = "DELETE $tv.* FROM $tv 
			JOIN $t ON $t.value_id=$tv.value_id AND $t.entity_id=? AND $t.attribute_id=?
			WHERE  $tv.store_id=?";
        $this->delete($sql, array($pid,$attrdesc["attribute_id"],$storeid));
    }

    public function getExclude(&$val, $default = true)
    {
        $exclude = $default;
        if ($val[0] == "+" || $val[0] == "-")
        {
            $exclude = $val[0] == "-";
            $val = substr($val, 1);
        }
        return $exclude;
    }

	/** Cang Luo - 07/10/2014
	 * If the relative image path is a wildcard path,  find and return all match image (absolute) paths as array
	 * If it is not a wildcard path, find and return the absolute path as an array (of 1 element)
	 * @param $ivalue : relative image path, allows wildcard (*)
	 * @return : an array of absolute image paths on success, false if not found
	 */
    public function findImageFile($ivalue)
    {
        // do no try to find remote image
        if (is_remote_path($ivalue))
        {
            return $ivalue;
        }
        // if existing, return it directly
        if (realpath($ivalue))
        {
            return $ivalue;
        }
        
		//Cang Luo - 7/10/2014 Check if ivalue is a valid wildcard path
		$isWild = false;
		$path = '';
		$name = $ivalue;
		//if the image path is a valid wildcard path, split the path at the last /
		if ($this->_wildcard && preg_match("/.*\*\w*\.(jpg|jpeg|png|gif)$/", $ivalue)){
			//get the index of the last /
			$isWild = true;
			$pos = strrpos ($ivalue, '/', -0);
			if($pos !== false){
				$path = substr($ivalue, 0, $pos+1);
				$name = substr($ivalue, $pos+1);
			}
			//replace * with .* (turn name into regular expression) 
			$name = str_replace('.', '\.', $name);
			$name = str_replace('*', '.*', $name);
		}
		
        // ok , so it's a relative path
        $imgfile = false;
        $scandirs = explode(";", $this->getParam("IMG:sourcedir"));
        $cscandirs = count($scandirs);
        // iterate on image sourcedirs, trying to resolve file name based on input value and current source dir
        for ($i = 0; $i < $cscandirs && $imgfile === false; $i++)
        {
            $sd = $scandirs[$i];
            // scandir is relative, use mdh
            if ($sd[0] != "/")
            {
                $sd = $this->_mdh->getMagentoDir() . "/" . $sd;
            }
			
			//Cang Luo - 7/10/2014 if it is a wildcard path,
			if($isWild){ 
				$sd .= $path;
				$sd = realpath($sd);
				
				if($sd){ 
					$imgfile = array();
					
					// scan directory for files that matched the regex
					if ($handle = opendir($sd)) {
						while (false !== ($entry = readdir($handle))) {
							if (preg_match("/^$name$/i", $entry)) {
								$imgfile[] = abspath($entry,$sd);
							}
						}
						closedir($handle);
				    }
					if(empty($imgfile)){
						//if no match, set imgfile back to false, to scan the next source directory;
						$imgfile = false;
					}
				}
			}else{ //ordinary path
				if($imgfile=abspath($ivalue,$sd)){
					$imgfile=array($imgfile);
				}
			}
        }
        return $imgfile; //returns array or false
    }

    public function handleImageTypeAttribute($pid, &$item, $storeid, $attrcode, $attrdesc, $ivalue)
    {
        // remove attribute value if empty
        if ($ivalue == "" || $ivalue == "__NULL__")
        {
            $this->removeImageFromGallery($pid, $storeid, $attrdesc);
            return "__MAGMI_DELETE__";
        }
        
        // add support for explicit exclude, default to false (include image)
        $exclude = $this->getExclude($ivalue, false);
        $imagefile = trim($ivalue);
        
        // else copy image file
        $imagefiles = $this->copyImageFile($imagefile, $item, array("store"=>$storeid,"attr_code"=>$attrcode));
        $ovalue = false;
        // add to gallery as excluded
        if(count($imagefiles) > 0){
            $label = null;
            if (isset($item[$attrcode . "_label"]))
            {
                $label = $item[$attrcode . "_label"];
            }
            $targetsids = $this->getStoreIdsForStoreScope($item["store"]);
			$ovalue=$imagefiles[0];
            $vid = $this->addImageToGallery($pid, $storeid, $attrdesc, $ovalue, $targetsids, $label, $exclude, 
                $attrdesc["attribute_id"]);
        }else{
			//TODO test log
			$this->log("Image not found: $ovalue","warning");
		}
        return $ovalue;
    }

    public function handleVarcharAttribute($pid, &$item, $storeid, $attrcode, $attrdesc, $ivalue)
    {
        if (trim($ivalue) == "")
        {
            return trim($ivalue);
        }
        // trimming
        $ivalue = trim($ivalue);
        // If not already a remote image & force remote root, set it & set authentication
        if (!is_remote_path($ivalue))
        {
            if ($this->_remoteroot != "")
            {
                $ivalue = $this->_remoteroot . str_replace("//", "/", "/$ivalue");
            }
        }
        if (is_remote_path($ivalue))
        {
            // Amazon images patch , remove SLXXXX part
            if (strpos($ivalue, 'amazon.com/images/I') !== false)
            {
                $pattern = '/\bSL[0-9]+\./i';
                $ivalue = preg_replace($pattern, '', $ivalue);
            }
        }
        
        // if it's a gallery
        switch ($attrdesc["frontend_input"])
        {
            case "gallery":
                
                $ovalue = $this->handleGalleryTypeAttribute($pid, $item, $storeid, $attrcode, $attrdesc, $ivalue);
                break;
            case "media_image":
                $ovalue = $this->handleImageTypeAttribute($pid, $item, $storeid, $attrcode, $attrdesc, $ivalue);
                break;
            default:
                $ovalue = "__MAGMI_UNHANDLED__";
        }
        return $ovalue;
    }

    /**
     * imageInGallery
     *
     * @param int $pid
     *            : product id to test image existence in gallery
     * @param string $imgname
     *            : image file name (relative to /products/media in magento dir)
     * @return bool : if image is already present in gallery for a given product id
     */
    public function getImageId($pid, $attid, $imgname, $refid = null)
    {
        $t = $this->tablename('catalog_product_entity_media_gallery');
        
        $sql = "SELECT $t.value_id FROM $t ";
        if ($refid != null)
        {
            $vc = $this->tablename('catalog_product_entity_varchar');
            $sql .= " JOIN $vc ON $t.entity_id=$vc.entity_id AND $t.value=$vc.value AND $vc.attribute_id=?
					WHERE $t.entity_id=?";
            $imgid = $this->selectone($sql, array($refid,$pid), 'value_id');
        }
        else
        {
            $sql .= " WHERE value=? AND entity_id=? AND attribute_id=?";
            $imgid = $this->selectone($sql, array($imgname,$pid,$attid), 'value_id');
        }
        
        if ($imgid == null)
        {
            // insert image in media_gallery
            $sql = "INSERT INTO $t
				(attribute_id,entity_id,value)
				VALUES
				(?,?,?)";
            
            $imgid = $this->insert($sql, array($attid,$pid,$imgname));
        }
        else
        {
            $sql = "UPDATE $t
				 SET value=?
				 WHERE value_id=?";
            $this->update($sql, array($imgname,$imgid));
        }
        return $imgid;
    }

    /**
     * reset product gallery
     *
     * @param int $pid
     *            : product id
     */
    public function resetGallery($pid, $storeid, $attid)
    {
        $tgv = $this->tablename('catalog_product_entity_media_gallery_value');
        $tg = $this->tablename('catalog_product_entity_media_gallery');
        $sql = "DELETE emgv,emg FROM `$tgv` as emgv 
			JOIN `$tg` AS emg ON emgv.value_id = emg.value_id AND emgv.store_id=?
			WHERE emg.entity_id=? AND emg.attribute_id=?";
        $this->delete($sql, array($storeid,$pid,$attid));
    }

    /**
     * adds an image to product image gallery only if not already exists
     *
     * @param int $pid
     *            : product id to test image existence in gallery
     * @param array $attrdesc
     *            : product attribute description
     * @param string $imgname
     *            : image file name (relative to /products/media in magento dir)
     */
    public function addImageToGallery($pid, $storeid, $attrdesc, $imgname, $targetsids, $imglabel = null, $excluded = false, 
        $refid = null)
    {
        $gal_attinfo = $this->getAttrInfo("media_gallery");
        $tg = $this->tablename('catalog_product_entity_media_gallery');
        $tgv = $this->tablename('catalog_product_entity_media_gallery_value');
        $vid = $this->getImageId($pid, $gal_attinfo["attribute_id"], $imgname, $refid);
        if ($vid != null)
        {
            
            // et maximum current position in the product gallery
            $sql = "SELECT MAX( position ) as maxpos
					 FROM $tgv AS emgv
					 JOIN $tg AS emg ON emg.value_id = emgv.value_id AND emg.entity_id = ?
					 WHERE emgv.store_id=?
			 		 GROUP BY emg.entity_id";
            $pos = $this->selectone($sql, array($pid,$storeid), 'maxpos');
            $pos = ($pos == null ? 0 : $pos + 1);
            // nsert new value (ingnore duplicates)
            
            $vinserts = array();
            $data = array();
            
            foreach ($targetsids as $tsid)
            {
                $vinserts[] = "(?,?,?,?," . ($imglabel == null ? "NULL" : "?") . ")";
                $data = array_merge($data, array($vid,$tsid,$pos,$excluded ? 1 : 0));
                if ($imglabel != null)
                {
                    $data[] = $imglabel;
					//if image label is not specified, do not update the label to null.
					$updatelabel = "label=VALUES(`label`),";
                }
            }
            
            if (count($data) > 0)
            {
                $sql = "INSERT INTO $tgv
					(value_id,store_id,position,disabled,label)
					VALUES " . implode(",", $vinserts) . " 
					ON DUPLICATE KEY UPDATE $updatelabel disabled=VALUES(`disabled`)";
                $this->insert($sql, $data);
            }
            unset($vinserts);
            unset($data);
        }
    }

    public function parsename($info, $item, $extra)
    {
        $info = $this->parseCalculatedValue($info, $item, $extra);
        return $info;
    }

    public function getPluginParams($params)
    {
        $pp = array();
        foreach ($params as $k => $v)
        {
            if (preg_match("/^IMG(_ERR)?:.*$/", $k))
            {
                $pp[$k] = $v;
            }
        }
        return $pp;
    }

    public function fillErrorAttributes(&$item)
    {
        foreach ($this->errattrs as $k => $v)
        {
            $this->addExtraAttribute($k);
            $item[$k] = $v;
        }
    }

    public function getImagenameComponents($fname, $formula, $extra)
    {
        $matches = array();
        $xname = $fname;
        if (preg_match("|re::(.*)::(.*)|", $formula, $matches))
        {
            $rep = $matches[2];
			$pattern = $matches[1];
			if ('\\' === DIRECTORY_SEPARATOR)
				$pattern = str_replace('/', '\\\\', $pattern);
            $xname = preg_replace("|$pattern|", $rep, $xname);
            $extra['parsed'] = true;
        }
        $xname = basename($xname);
        $m = preg_match("/(.*)\.(jpg|png|gif)$/i", $xname, $matches);
        if ($m)
        {
            $extra["imagename"] = $xname;
            $extra["imagename.ext"] = $matches[2];
            $extra["imagename.noext"] = $matches[1];
        }
        else
        {
            $uid = uniqid("img", true);
            $extra = array_merge($extra, array("imagename"=>"$uid.jpg","imagename.ext"=>"jpg","imagename.noext"=>$uid));
        }
        
        return $extra;
    }

    public function getTargetName($fname, $item, $extra)
    {
        $cname = basename($fname);
        if (isset($this->forcename) && $this->forcename != "")
        {
            $extra = $this->getImagenameComponents($fname, $this->forcename, $extra);
            $pname = (isset($extra['parsed']) ? $extra['imagename'] : $this->forcename);
            $cname = $this->parsename($pname, $item, $extra);
        }
        $cname = strtolower(preg_replace("/%[0-9][0-9|A-F]/", "_", rawurlencode($cname)));
        
        return $cname;
    }

    public function saveImage($imgfile, $target)
    {
        $result = $this->_mdh->copy($imgfile, $target);
        return $result;
    }

    /**
     * copy image file(s) from source directory to product media directory
     *
     * @param $imgfile :
     *            relative image file path (may contain wildcard) in source directory
     * @return : an array of image file names relative to magento catalog media dir, including leading
     *         	directories made of first char & second char of image file name;
     */
    public function copyImageFile($imgfile, &$item, $extra)
    {
        if ($imgfile == "__NULL__" || $imgfile == null)
        {
            return false;
        }
        
        // check for source image in error
        if ($this->isErrorImage($imgfile))
        {
            if ($this->_newitem)
            {
                $this->fillErrorAttributes($item);
            }
            ;
            return false;
        }
        
        $files = $this->findImageFile($imgfile);
        if ($files == false)
        {
            $this->log("$imgfile cannot be found in images path", "warning");
            // last image in error,add it to error cache
            $this->setErrorImg($imgfile);
            return false;
        }
		
		$media_paths = array();
		foreach ($files as $file){
			$bimgfile=$this->getTargetName($file,$item,$extra);
			//source file exists
			$i1=$bimgfile[0];
			$i2=$bimgfile[1];
			
			// magento image value (relative to media catalog)
			$path = "/$i1/$i2/$bimgfile";
			
			// target directory;
			$l2d="media/catalog/product/$i1/$i2";
			$targetpath="$l2d/$bimgfile";
			
			
			/* test if imagefile comes from export */
			if(!$this->_mdh->file_exists("$targetpath") || $this->getParam("IMG:writemode")=="override")
			{
				// if we already had problems with this target,assume we'll get others.
				if (!$this->isErrorImage($path))
				{
					$_continue = true;
					/* try to recursively create target dir */
					if(!$this->_mdh->file_exists("$l2d"))
					{
						$tst=$this->_mdh->mkdir($l2d,Magmi_Config::getInstance()->getDirMask(),true);
						if(!$tst)
						{
							$errors=$this->_mdh->getLastError();
							$this->log("error creating $l2d: {$errors["type"]},{$errors["message"]}","warning");
							unset($errors);
							$this->setErrorImg($path);
							$_continue = false;
						}
					}

					if($_continue && !$this->saveImage($file,"$l2d/$bimgfile"))
					{
						$errors=$this->_mdh->getLastError();
						$this->fillErrorAttributes($item);
						$this->log("error copying $file TO $l2d/$bimgfile : {$errors["type"]},{$errors["message"]}","warning");
						unset($errors);
						$this->setErrorImg($path);
						$_continue = false;
					}
					else
					{
						$this->_mdh->chmod("$l2d/$bimgfile",Magmi_Config::getInstance()->getFileMask());
						$media_paths[]=$path;		
					}
				}
			}else{
				$media_paths[]=$path;
			}
		}
		/* return an array of image file names relative to media dir (with leading / ) */
		return $media_paths;
    }

    public function updateLabel($attrdesc, $pid, $sids, $label)
    {
        $tg = $this->tablename('catalog_product_entity_media_gallery');
        $tgv = $this->tablename('catalog_product_entity_media_gallery_value');
        $vc = $this->tablename('catalog_product_entity_varchar');
        $sql = "UPDATE $tgv as emgv 
		JOIN $tg as emg ON emg.value_id=emgv.value_id AND emg.entity_id=?
		JOIN $vc  as ev ON ev.entity_id=emg.entity_id AND ev.value=emg.value and ev.attribute_id=? 
		SET label=? 
		WHERE emgv.store_id IN (" . implode(",", $sids) . ")";
        $this->update($sql, array($pid,$attrdesc["attribute_id"],$label));
    }

    public function processItemAfterId(&$item, $params = null)
    {
        if (!$this->_active)
        {
            return true;
        }
        $this->_newitem = $params["new"];
        $pid = $params["product_id"];
        foreach ($this->_img_baseattrs as $attrcode)
        {
            // if only image/small_image/thumbnail label is present (ie: no image field)
            if (isset($item[$attrcode . "_label"]) && !isset($item[$attrcode]))
            {
                // force label update
                $attrdesc = $this->getAttrInfo($attrcode);
                $this->updateLabel($attrdesc, $pid, $this->getItemStoreIds($item, $attr_desc["is_global"]), 
                    $item[$attrcode . "_label"]);
                unset($attrdesc);
            }
        }
        // Reset media_gallery
        $galreset = !(isset($item["media_gallery_reset"])) || $item["media_gallery_reset"] == 1;
        $forcereset = (isset($item["media_gallery_reset"])) && $item["media_gallery_reset"] == 1;
        
        if ((isset($item["media_gallery"]) && $galreset) || $forcereset)
        {
            $gattrdesc = $this->getAttrInfo("media_gallery");
            $sids = $this->getItemStoreIds($item, $gattrdesc["is_global"]);
            foreach ($sids as $sid)
            {
                $this->resetGallery($pid, $sid, $gattrdesc["attribute_id"]);
            }
        }
        if (isset($item["image_remove"]))
        {
            $this->handleRemoveImages($pid, $item, $item["image_remove"]);
        }
        return true;
    }

    public function processColumnList(&$cols, $params = null)
    {
        // automatically add modified attributes if not found in datasource
        
        // automatically add media_gallery for attributes to handle
        $imgattrs = array_intersect(array_merge($this->_img_baseattrs, array('media_gallery')), $cols);
        if (count($imgattrs) > 0)
        {
            $this->_active = true;
            $cols = array_unique(array_merge(array_keys($this->errattrs), $cols, $imgattrs));
        }
        else
        {
            $this->log("no image attributes found in datasource, disabling image processor", "startup");
        }
        return true;
    }
    
    // Cleanup gallery from removed images if no more image values are present in any store
    public function endImport()
    {
        if (!$this->_active)
        {
            return;
        }
        $attids = array();
        foreach ($this->_img_baseattrs as $attrcode)
        {
            $inf = $this->getAttrInfo($attrcode);
            if (count($inf) > 0)
            {
                $attids[] = $inf["attribute_id"];
            }
        }
        if (count($attids) > 0)
        {
            $tg = $this->tablename('catalog_product_entity_media_gallery');
            $tgv = $this->tablename('catalog_product_entity_media_gallery_value');
            $sql = "DELETE emg.* FROM $tg as emg
			LEFT JOIN (SELECT emg.value_id,count(emgv.value_id) as cnt FROM  $tgv as emgv JOIN $tg as emg  ON emg.value_id=emgv.value_id GROUP BY emg.value_id ) as t1 ON t1.value_id=emg.value_id
			WHERE attribute_id IN (" . implode(",", $attids) . ") AND t1.cnt IS NULL";
            $this->delete($sql);
        }
        else
        {
            $this->log("Unexpected problem in image attributes retrieval", "warning");
        }
        unset($attids);
    }
}