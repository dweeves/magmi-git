<?php
class CategoryImporter extends Magmi_ItemProcessor
{
	protected $_idcache=array();
	protected $_catattr=array();
	protected $_cattrinfos=array();
	
	public function initialize($params)
	{
		$this->_cattrinfos=array("varchar"=>array("name"=>array()),
						 "int"=>array("is_active"=>array(),"is_anchor"=>array(),"include_in_menu"=>array()));
		foreach($this->_cattrinfos as $catype=>$attrlist)
		{
			foreach(array_keys($attrlist) as $catatt)
			{
				$this->_cattrinfos[$catype][$catatt]=$this->getCatAttributeInfos($catatt);
			}
		}
	}
	
	public function getCatAttributeInfos($attcode)
	{
		$sql="SELECT * FROM eav_attribute WHERE entity_type_id=3 AND attribute_code=?";
		$info=$this->selectAll($sql,$attcode);
		return $info[0];
	}

	public function categoryExists($parentpath,$cattr)
	{
			
	}
	
	public function getCache($cdef)
	{
		return $this->_idcache[$cdef];
	}
	public function isInCache($cdef)
	{
		return isset($this->_idcache[$cdef]);
	}
	
	public function putInCache($cdef,$idarr)
	{
		$this->_idcache[$cdef]=$idarr;
	}
	
	public function getPluginInfo()
	{
		return array(
            "name" => "On the fly category creator/importer",
            "author" => "Dweeves",
            "version" => "0.0.4"
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
		$catid=$this->getExistingCategory($parentpath,$cattrs);
		if($catid!=null)
		{
			return $catid;
		}
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
		$sql="INSERT INTO $cet 	(entity_type_id,attribute_set_id,parent_id,position,level) VALUES (?,?,?,?,?)";
		
		$data=array($info["entity_type_id"],$info["attribute_set_id"],$parentid,$info["position"],$info["level"]);		
		$catid=$this->insert($sql,$data);
		unset($data);
		//set category path
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
			
				$inserts[]="(?,?,?,?,?)";
				$data[]=$info["entity_type_id"];
				$data[]=$attdata["attribute_id"];
				$data[]=0;//store id 0 for categories
				$data[]=$catid;
				$data[]=$cattrs[$attrcode];
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
		$cdefs=explode("/",$catdef);
		$odefs=array();
		$clist=array();
		foreach($cdefs as $cdef)
		{
			$attrs=array();
			$parts=explode("::",$cdef);
			$cp=count($parts);
			$cname=trim($parts[0]);
			$attrs=array("name"=>$cname,"is_active"=>($cp>1)?$parts[1]:1,"is_anchor"=>($cp>2)?$parts[2]:1,"include_in_menu"=>$cp>3?$parts[3]:1);
			$odefs[]=$cname;
			$clist[]=$attrs;
		}
		$catdef=implode("/",$odefs);
		return $clist;
	}
	
	public function getCategoryIdsFromDef($catdef)
	{
		$catattributes=$this->extractCatAttrs($catdef);
		$catparts=explode("/",$catdef);
		$level=1;
		$path="1/2";
		$pdef=array();
		//if full def is in cache, use it
		if($this->isInCache($catdef))
		{
			return $this->getCache($catdef);
		}
		else
		{
			//else
			$catids=array();
			$lastcached=array();
			foreach($catparts as $catpart)
			{
				$pdef[]=$catpart;
				$ptest=implode("/",$pdef);
				if($this->isInCache($ptest))
				{
					$catids=$this->getCache($ptest);
					$lastcached=$pdef;
				}
			}
			$curpath=array_merge(array(1,2),$catids);	
			//iterate on missing levels.
			for($i=count($catids);$i<count($catparts);$i++)
			{
				$catid=$this->getCategoryId($curpath,$catattributes[$i]);
				$catids[]=$catid;
				$curpath[]=$catid;
				//cache newly created levels
				$lastcached[]=$catparts[$i];
			
				$this->putInCache(implode("/",$lastcached),$catids);
				
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
	
	public function processItemAfterId(&$item,$params=null)
	{
		if(isset($item["categories"]))
		{
			$catlist=explode(",",$item["categories"]);
			$catids=array();
			foreach($catlist as $catdef)
			{
				$root=$this->getParam("CAT:baseroot","");
				if($root!="")
				{
					$catdef="$root/$catdef";
				}
				$catids=array_unique(array_merge($catids,$this->getCategoryIdsFromDef($catdef)));
			}
			$item["category_ids"]=implode(",",$catids);
		}
		return true;
	}
	
	public function getPluginParamNames()
	{
		return array('CAT:baseroot');
	}
	
	public function onImportEnd($params)
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