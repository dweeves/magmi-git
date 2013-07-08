<?php
class CategoryImporter extends Magmi_ItemProcessor
{
	protected $_idcache=array();
	protected $_catattr=array();
	protected $_cattrinfos=array();
	protected $_catroots=array();
	protected $_catrootw=array();
	protected $_cat_eid=null;
	protected $_tsep;
	public function initialize($params)
	{

		$this->initCats();
		$this->_cattrinfos=array("varchar"=>array("name"=>array(),"url_key"=>array(),"url_path"=>array()),
						 "int"=>array("is_active"=>array(),"is_anchor"=>array(),"include_in_menu"=>array()));
		foreach($this->_cattrinfos as $catype=>$attrlist)
		{
			foreach(array_keys($attrlist) as $catatt)
			{
				$this->_cattrinfos[$catype][$catatt]=$this->getCatAttributeInfos($catatt);
			}
		}
		$this->_tsep=$this->getParam("CAT:treesep","/");
			
	}
	
	public function initCats()
	{
		// zioigor - 20110426 missing call to tablename method for table_prfix
		$t=$this->tablename("catalog_category_entity");
		$csg=$this->tablename("core_store_group");
		$cs=$this->tablename("core_store");
		$ccev=$t."_varchar";
		$ea=$this->tablename("eav_attribute");
		$result=$this->selectAll("SELECT cs.store_id,csg.website_id,cce.entity_type_id,cce.path,ccev.value as name
								FROM $cs as cs 
								JOIN $csg as csg on csg.group_id=cs.group_id
								JOIN $t as cce ON cce.entity_id=csg.root_category_id
								JOIN $ea as ea ON ea.attribute_code='name' AND ea.entity_type_id=cce.entity_type_id
								JOIN $ccev as ccev ON ccev.attribute_id=ea.attribute_id AND ccev.entity_id=cce.entity_id
		 ");
		foreach($result as $row)
		{
			$rootinfo=array("path"=>$row["path"],"etid"=>$row["entity_type_id"],"name"=>$row["name"],"rootarr"=>explode("/",$row["path"]));
			$this->_catroots[$row["store_id"]]=$rootinfo;
			$this->_catrootw[$row["website_id"]][]=$row["store_id"];
			if($this->_cat_eid==null)
			{
				$this->_cat_eid=$row["entity_type_id"];
			}
		}
		
	}
	
	
	public function getCatAttributeInfos($attcode)
	{
		$t=$this->tablename("eav_attribute");
		$sql="SELECT * FROM $t WHERE entity_type_id=? AND attribute_code=?";
		$info=$this->selectAll($sql,array($this->_cat_eid,$attcode));
		return $info[0];
	}

	
	public function getCache($cdef,$bp)
	{
		$ck="$bp::$cdef";
		return $this->_idcache[$ck];
	}
	public function isInCache($cdef,$bp)
	{
		$ck="$bp::$cdef";
		return isset($this->_idcache[$ck]);
	}
	
	public function putInCache($cdef,$bp,$idarr)
	{
		$ck="$bp::$cdef";
		$this->_idcache[$ck]=$idarr;
		
	}
	
	public function getPluginInfo()
	{
		return array(
            "name" => "On the fly category creator/importer",
            "author" => "Dweeves",
            "version" => "0.1.7",
			"url" => "http://sourceforge.net/apps/mediawiki/magmi/index.php?title=On_the_fly_category_creator/importer"
            );
	}
	
	
	public function getExistingCategory($parentpath,$cattr)
	{
		$cet=$this->tablename("catalog_category_entity");
		$cetv=$this->tablename("catalog_category_entity_varchar");
		$parentid=array_pop($parentpath);
		$sql="SELECT cet.entity_id FROM $cet as cet
			  JOIN $cetv as cetv ON cetv.entity_id=cet.entity_id AND cetv.attribute_id=? AND cetv.value=?
			  WHERE cet.parent_id=? ";
		$catid=$this->selectone($sql,array($this->_cattrinfos["varchar"]["name"]["attribute_id"],$cattr["name"],$parentid),"entity_id");
		return $catid;
	}
	
	public function getCategoryId($parentpath,$cattrs)
	{
		//get exisiting cat id
		$catid=$this->getExistingCategory($parentpath,$cattrs);
		//if found , return it
		if($catid!=null)
		{
			return $catid;
		}
		//otherwise, get new category values from parent & siblings
		$cet=$this->tablename("catalog_category_entity");
		$path=implode("/",$parentpath);
		$parentid=array_pop($parentpath);
		//get child info using parent data
		$sql="SELECT cce.entity_type_id,cce.attribute_set_id,cce.level+1 as level,COALESCE(MAX(eac.position),0)+1 as position
		FROM $cet as cce
		LEFT JOIN  $cet as eac ON eac.parent_id=cce.entity_id
		WHERE cce.entity_id=?
		GROUP BY eac.parent_id";
		$info=$this->selectAll($sql,$parentid);
		$info=$info[0];
		//insert new category
		$sql="INSERT INTO $cet 	(entity_type_id,attribute_set_id,parent_id,position,level,path,children_count) VALUES (?,?,?,?,?,?,?)";
		//insert empty path until we get category id
		$data=array($info["entity_type_id"],$info["attribute_set_id"],$parentid,$info["position"],$info["level"],"",0);		
		//insert in db,get cat id
		$catid=$this->insert($sql,$data);
		
		unset($data);
		//set category path with inserted category id
		$sql="UPDATE $cet SET path=?,created_at=NOW(),updated_at=NOW() WHERE entity_id=?";
		$data=array("$path/$catid",$catid);
		$this->update($sql,$data);
		unset($data);
		//set category attributes
		foreach($this->_cattrinfos as $tp=>$attinfo)
		{
			$inserts=array();
			$data=array();
			$tb=$this->tablename("catalog_category_entity_$tp");
			
			foreach($attinfo as $attrcode=>$attdata)
			{
				if(isset($attdata["attribute_id"]))
				{
					$inserts[]="(?,?,?,?,?)";
					$data[]=$info["entity_type_id"];
					$data[]=$attdata["attribute_id"];
					$data[]=0;//store id 0 for categories
					$data[]=$catid;
					$data[]=$cattrs[$attrcode];
				}
			}
			
			$sql="INSERT INTO $tb (entity_type_id,attribute_id,store_id,entity_id,value) VALUES ".implode(",",$inserts).
			" ON DUPLICATE KEY UPDATE value=VALUES(`value`)";
			$this->insert($sql,$data);
			unset($data);
			unset($inserts);
		}
		return $catid;
	}
	
	public function extractCatAttrs(&$catdef)
	{
		$cdefs=explode($this->_tsep,$catdef);
		$odefs=array();
		$clist=array();
		foreach($cdefs as $cdef)
		{
			$attrs=array();
			$parts=explode("::",$cdef);
			$cp=count($parts);
			$cname=trim($parts[0]);
			$odefs[]=$cname;
			$attrs=array("name"=>$cname,"is_active"=>($cp>1)?$parts[1]:1,
						 "is_anchor"=>($cp>2)?$parts[2]:1,
						 "include_in_menu"=>$cp>3?$parts[3]:1,
						 "url_key"=>Slugger::slug($cname),
						 "url_path"=>Slugger::slug(implode("/",$odefs),true).$this->getParam("CAT:urlending",".html"));
			$clist[]=$attrs;
		}
		$catdef=implode($this->_tsep,$odefs);
		return $clist;
	}
	
	public function getCategoryIdsFromDef($catdef,$basepath)
	{
		
		
		//if full def is in cache, use it
		if($this->isInCache($catdef,$basepath))
		{
			$catids=$this->getCache($catdef,$basepath);
		}
		else
		{
			//category ids 
			$catids=array();
			$lastcached=array();
			//get cat tree branches names
			$catparts=explode($this->_tsep,$catdef);
			//path as array , basepath is always "/" separated
			$basearr=explode("/",$basepath);
			//for each cat tree branch		
			$pdef=array();	
			foreach($catparts as $catpart)
			{
				//add it to the current tree level
				$pdef[]=$catpart;
				$ptest=implode($this->_tsep,$pdef);
				//test for tree level in cache
				if($this->isInCache($ptest,$basepath))
				{
					//if yes , set current known cat ids to corresponding cached branch 
					$catids=$this->getCache($ptest,$basepath);
					//store last cached branch
					$lastcached=$pdef;
				}				
				else
				//no more tree info in cache,stop further retrieval, we need to create missing levels
				{
				  break;
				}

			}
			//add store tree root to category path 
			$curpath=array_merge($basearr,$catids);
			//get categories attributes
			$catattributes=$this->extractCatAttrs($catdef);
			
			//iterate on missing levels.
			for($i=count($catids);$i<count($catparts);$i++)
			{
				//retrieve category id (by creating it if needed from categories attributes)
				$catid=$this->getCategoryId($curpath,$catattributes[$i]);
				//add newly created level to item category ids
				$catids[]=$catid;
				//add newly created level to current paths
				$curpath[]=$catid;
				//cache newly created levels
				$lastcached[]=$catparts[$i];				
				$this->putInCache(implode($this->_tsep,$lastcached),$basepath,$catids);
			
			}
		}
		return $catids;
	}
	
	public function processColumnList(&$cols,$params)
	{
		$cols[]="category_ids";
		$cols=array_unique($cols);
		return true;
	}
	
	public function getStoreRootPaths(&$item)
	{
		$rootpaths=array();
		$sids=$this->getItemStoreIds($item,2);
		$trimroot="";
		//remove admin from store ids (no category root on it)
		if($sids[0]==0)
		{
			array_shift($sids);
		}
		//only admin store set,use websites store roots
		if(count($sids)==0)
		{
			$wsids=$this->getItemWebsites($item);
			foreach($wsids as $wsid)
			{
				$sids=array_merge($sids,$this->_catrootw[$wsid]);
			}
		}
		foreach($sids as $sid)
		{
			$srp=$this->_catroots[$sid];
			$cmatch=true;
			//If using explicit root assignment , identify which root it is
			if(preg_match("|^\[(.*)?\].*$|",$item["categories"],$matches))
			{
				$cmatch=(trim($matches[1])==$srp["name"]);
				if($cmatch)
				{
					$trimroot=$matches[1];
				}
			}
			if($cmatch)
			{
				$rootpaths[$srp["path"]]=$srp["rootarr"];
			}
		}
		if($trimroot!="")
		{
			$item["categories"]=substr($item["categories"],strlen($trimroot)+2+strlen($this->_tsep));
		}
		return $rootpaths;
	}
	
	public function processItemAfterId(&$item,$params=null)
	{
		if(isset($item["categories"]))
		{
			
			$rootpaths=$this->getStoreRootPaths($item);
			if(count($rootpaths)==0)
			{
				if(preg_match("|^\[(.*)?\].*$|",$item["categories"],$matches))
				{
					$this->log("Cannot find site root with name : ".$matches[1],"error");
					return false;
				}
			}
			$catlist=explode(";;",$item["categories"]);
			$catids=array();
			foreach($catlist as $catdef)
			{
				if($catdef!="")
				{
					$root=$this->getParam("CAT:baseroot","");
					if($root!="")
					{
						$catdef=$root.$this->_tsep.$catdef;
					}
					foreach(array_keys($rootpaths) as $rp)
					{
						$cdef=$this->getCategoryIdsFromDef($catdef,$rp);
						if($this->getParam("CAT:lastonly",0)==1)
						{
							$cdef=array($cdef[count($cdef)-1]);
						}
						$catids=array_unique(array_merge($catids,$cdef));
					}
				}
			}
			$item["category_ids"]=implode(",",$catids);
		}
		return true;
	}
	
	public function getPluginParamNames()
	{
		return array('CAT:baseroot','CAT:lastonly','CAT:urlending','CAT:treesep');
	}
	
	public function afterImport()
	{
		//automatically update all children_count for catalog categories
		$cce=$this->tablename("catalog_category_entity");
		$sql="UPDATE  $cce as cce
		LEFT JOIN 
			(SELECT s1.entity_id as cid, COALESCE( COUNT( s2.entity_id ) , 0 ) AS cnt
				FROM $cce AS s1
				LEFT JOIN $cce AS s2 ON s2.parent_id = s1.entity_id
			GROUP BY s1.entity_id) as sq ON sq.cid=cce.entity_id
			SET cce.children_count=sq.cnt";
		$this->update($sql);
		return true;
	}
}