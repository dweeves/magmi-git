<?php
class CustomerAdressProcessor extends Magmi_ItemProcessor
{
	protected $_addrcols=array("billing"=>array(),"shipping"=>array());
	protected $_colaggregates;
	protected $_colsbytype=array("billing"=>array(),"shipping"=>array());
    protected $_attrinfo=array();
    protected $_caetype;
    protected $_countrycache=array();
    
	public function getPluginInfo()
    {
        return array(
            "name" => "Customer Adresses importer",
            "author" => "Dweeves",
            "version" => "0.0.1",
        );
    }
    
    public function initAdressAttrInfos($cols)
    {
    	$toscan=$cols;
    	$etypes=array(2);
		if(count($toscan)>0)
		{
			//create statement parameter string ?,?,?.....
			$qcolstr=$this->arr2values($toscan);

			$tname=$this->tablename("eav_attribute");
				
				
			$sql="SELECT `$tname`.* FROM `$tname` WHERE ($tname.attribute_code IN ($qcolstr)) AND (entity_type_id IN (".$this->arr2values($etypes)."))";
				
			$toscan=array_merge($toscan,array_values($etypes));
			$result=$this->selectAll($sql,$toscan);

			$attrinfs=array();
			//create an attribute code based array for the wanted columns
			foreach($result as $r)
			{
				$attrinfs[$r["attribute_code"]]=$r;
			}
			unset($result);
			$this->_attrinfo=array_merge($this->_attrinfo,$attrinfs);
		}
		
    }
    
    public function getAddressAttrInfo($attcode)
    {
    	$attrinf=isset($this->_attrinfo[$attcode])?$this->_attrinfo[$attcode]:null;
		if($attrinf==null)
		{
			$this->initAdressAttrInfos(array($attcode));

		}
		if(count($this->_attrinfo[$attcode])==0)
		{

			$attrinf=null;
			unset($this->_attrinfo[$attcode]);
		}
		else
		{
			$attrinf=$this->_attrinfo[$attcode];
		}
		return $attrinf;
    }
    
	public function processColumnList(&$cols,$params=null)
	{
		$zcols=array_unique(array_merge($cols,array('billing_region_id','shipping_region_id',)));
		for($i=0;$i<count($zcols);$i++)
		{
			$col=$zcols[$i];
			$aak=null;
			if(substr($col,0,7)=="billing")
			{
				$aak="billing";
			}
			if(substr($col,0,8)=="shipping")
			{
				$aak="shipping";
			}
			if($aak!=null)
			{
				$icol=$col;
				$last=substr($col,-1);
				if(intval($last)>0)
				{
					$idx=intval($last)-1;
					$colaggrname=substr($col,0,strlen($col)-1);
					if(!isset($this->_colaggregates[$colaggrname]))
					{
						$this->_colaggregates[$colaggrname]=array();
					}
					$this->_colaggregates[$colaggrname][]=array($idx,$col);
					$icol=$colaggrname;
				}
				$attname=substr($icol,strlen($aak)+1);
				if($attname=='street_full')
				{
					continue;
				}
				if(!isset($this->_addrcols[$aak][$icol]))
				{
					if($attname=='country')
					{
						$attname='country_id';
					}
					$attinf=$this->getAddressAttrInfo($attname);
					if($attinf!=null)
					{
						$this->_addrcols[$aak][$icol]=$attinf;
						if(!isset($this->_colsbytype[$aak][$attinf['backend_type']]))
						{
							$this->_colsbytype[$aak][$attinf['backend_type']]=array();
						}
						$this->_colsbytype[$aak][$attinf['backend_type']][]=$icol;
					}
				}
			}
			
		}	
	}
	
	public function createAdress($cid,$item,$addrtype)
	{
		$data=array();
		$data[]=2;
		$data[]=$cid;
		$sql="INSERT INTO customer_address_entity (entity_type_id,parent_id) VALUES (".$this->arr2values($data).")";
		$addrid=$this->insert($sql,$data);
		$this->updateAddress($cid,$item, $addrtype, $addrid);
		return $addrid;
	}
	
	public function setDefaultAdress($addrid,$addrtype,$cid)
	{
		$custeid=$this->getEntityTypeId('customer');
		$attrinfo=$this->getAttrInfo("default_$addrtype",true,$custeid);
		$data=array(
		 $custeid,
		 $attrinfo['attribute_id'],
		 $cid,
		 $addrid
		);
		//set adress as default
		$sql="INSERT INTO ".$this->tablename('customer_entity_int')." (entity_type_id,attribute_id,entity_id,value) VALUES (".$this->arr2values($data).")
		ON DUPLICATE KEY UPDATE value=values(value)";
		$this->insert($sql,$data);
		
		
	}
	public function getCountryId($item,$country)
	{
		$country=trim($country);
		$clen=strlen($country);
		$field=$clen==2?"iso2_code":($clen==3?"iso3_code":null);
		if($field!=null)
		{
			if(!isset($this->_countrycache[$country]))
			{
				$sql="SELECT country_id FROM ".$this->tablename('directory_country')." WHERE $field=?";
				$countryid=$this->selectone($sql,array($country),'country_id');
			}
		}
		return $countryid;
	}
	
	public function getRegionId($item,$region,$country_id)
	{
		if(!isset($this->_regids[$region]))
		{
			$sql="SELECT region_id FROM ".$this->tablename('directory_country_region')." WHERE (default_name=? OR code=?) AND country_id=?";
			$this->_regids[$region]=$this->selectone($sql,array($region,$region,$country_id),'region_id');
			if($this->_regids[$region]==NULL)
			{
				$this->log($item["email"].":Invalid region found","warning");
				$this->_regids[$region]=0;
			}
		}
		return $this->_regids[$region];
	}
	
	public function updateAddress($cid,&$item,$addrtype,$addrid)
	{
		//handle aggregates
		$aval=array();
		foreach($this->_colaggregates as $colaggrname=>$inf)
		{
			if(substr($colaggrname, 0,strlen($addrtype))==$addrtype)
			{
				for($i=0;$i<count($inf);$i++)
				{
					$idx=$inf[$i][0];
					$col=$inf[$i][1];
					if(trim($item[$col])!="")
					{
						$aval[$idx]=$item[$col];
					}
					unset($item[$col]);
				}
				$item[$colaggrname]=implode(chr(10),$aval);
			}
		}

		$countryid=$this->getCountryId($item,$item[$addrtype.'_country']);
		$item[$addrtype.'_country_id']=$countryid;
		if($countryid==null)
		{
			$this->log($item['email'].":Invalid country -  cannot create/update $addrtype address","error");
		}
		else
		{
			$item[$addrtype.'_region_id']=$this->getRegionId($item,$item[$addrtype.'_region'],$item[$addrtype.'_country_id']);
		}
		//process adress attributes
		foreach($this->_colsbytype[$addrtype] as $tp=>$colnames)
		{
			$tname=$this->tablename("customer_address_entity_$tp");
			$ins=array();
			$ins_v=array();
			//force add region id
			$colnames=array_unique(array_merge($colnames,array($addrtype.'_region_id')));
			for($i=0;$i<count($colnames);$i++)
			{
				$itemcol=$colnames[$i];
				$inf=$this->_addrcols[$addrtype][$itemcol];
				$ins[]=$inf['entity_type_id'];
				$ins[]=$inf['attribute_id'];
				$ins[]=$addrid;
				$itematt=$addrtype.'_'.$inf['attribute_code'];
				$ins[]=$item[$itematt];
				$ins_v[]='(?,?,?,?)';
			}
			
			$sql="INSERT INTO $tname (entity_type_id,attribute_id,entity_id,value) VALUES ".implode(',',$ins_v)."
			ON DUPLICATE KEY UPDATE value=values(value)";
			$this->insert($sql,$ins);
		}		
	}
	
	public function getDefinedAdressTypes($item)
	{
		$akeys=array();
		if(trim($item['billing_city'])!='')
		{
			$akeys[]='billing';
		}
		if(trim($item['shipping_city'])!='')
		{
			$akeys[]='shipping';
		}
		return $akeys;
		
	}
	
	public function removeemptyCols(&$item,$addrtype)
	{
		$akeys=$this->getDefinedAdressTypes($item);
		$ovcols=array('prefix','firstname','middlename','lastname');
		foreach($ovcols as $col)
		{		
			$acol=$addrtype."_$col";
			if(isset($item[$acol]) && trim($item[$acol])=='')
			{
				unset($item[$acol]);
			}
			$item[$acol]=$item[$col];
		}
	}
	
	public function createAdresses(&$item,$cid)
	{
		$akeys=$this->getDefinedAdressTypes($item);
		foreach($akeys as $addrtype)
		{
			if(count($this->_addrcols[$addrtype])>0)
			{
				$this->removeemptyCols($item,$addrtype);
				
				$addrid=$this->findAddressId($cid,$item, 'default_'.$addrtype);
				if($addrid==null)
				{
					$addrid=$this->createAdress($cid,$item, $addrtype);
					$this->setDefaultAdress($addrid, $addrtype, $cid);
					//if one adress only is defined
					//set it as default for other address type
				
				}
				else
				{
					$this->updateAddress($cid,$item, $addrtype, $addrid);
					$this->setDefaultAdress($addrid, $addrtype, $cid);
				}
				if(count($akeys)==1)
				{
					if($addrtype=='billing')
					{
						$this->setDefaultAdress($addrid, 'shipping', $cid);	
					}
					if($addrtype=='shipping')
					{
						$this->setDefaultAdress($addrid, 'billing', $cid);	
					}
				}
			}
		}
	}
	
	static public function getCompatibleEngines()
	{
		return "Magmi_CustomerImportEngine";	
	}
	
	public function processItemAfterId(&$item,$params=null)
	{
		$cid=$params['customer_id'];
		$this->createAdresses($item,$cid);
	}
	
    public function findAddressId($cid,$item,$attcode)
	{
		$sql="SELECT cae.entity_id FROM ".$this->tablename('customer_address_entity')." as cae
			  JOIN ".$this->tablename('eav_entity_type')." as eat ON  eat.entity_type_code='customer'
			  JOIN ".$this->tablename('eav_attribute')." as ea ON ea.attribute_code=? AND ea.entity_type_id=eat.entity_type_id
              JOIN ".$this->tablename('customer_entity')." as ce ON ce.entity_id=cae.parent_id AND ce.entity_id=?
              JOIN ".$this->tablename('customer_entity_int')." as cei ON cei.attribute_id=ea.attribute_id 
              AND cei.entity_id=ce.entity_id AND cei.value=cae.entity_id";
		
		$addrid=$this->selectone($sql,array($attcode,$cid),'entity_id');
		return $addrid;
	}
}