<?php
class ImportLimiter extends Magmi_ItemProcessor
{
	protected $_recranges;
	protected $_max;
	protected $_filters;
	
	public function getPluginInfo()
	{
		return array("name"=>"Magmi Import Limiter",
					 "author"=>"Dweeves",
					 "version"=>"0.0.2");
	}
	
	
	public function filtermatch($item,$fltdef)
	{
		$negate=0;
		$field=$fltdef[0];
		if($field[0]=="!")
		{
			$field=substr($field,1);
			$negate=1;
		}
		$re=$fltdef[1];
		if(isset($item[$field]))
		{
			$v=$item[$field];
			$match=preg_match("|$re|",$v);
			if($negate)
			{
				$match=!$match;
			}
		}
		return $match;
	}
	public function processItemBeforeId(&$item,$params=null)
	{
		$crow=$this->getCurrentRow();
		$ok=false;
		if($this->_rmax>-1 && $crow==$this->_rmax)
		{
			$this->setLastItem($item);		
		}
		foreach($this->_recranges as $rr)
		{
			$ok=($crow>=$rr[0] && ($crow<=$rr[1] || $rr[1]==-1));
			if($ok)
			{
				break;
			}
		}
		if($ok)
		{
			$ok=true;
			foreach($this->_filters as $fltdef)
			{
				//negative filters
				$ok=$ok && (!$this->filtermatch($item,$fltdef));
				if(!$ok)
				{
					break;
				}
			}
		}
		return $ok;
	}
	
	public function parseFilters($fltstr)
	{
		$this->_filters=array();
		$fltlist=explode(";;",$fltstr);
		foreach($fltlist as $fltdef)
		{
			$fltinf=explode("::",$fltdef);
			$this->_filters[]=$fltinf;			
		}
	}
	
	public function parseRanges($rangestr)
	{
		$this->_recranges=array();
		$rangelist=explode(",",$rangestr);
		foreach($rangelist as $rdef)
		{
			$rlist=explode("-",$rdef);
			if($rlist[0]=="")
			{
				$rlist[0]=-1;
			}
			else
			{
				$rmin=$rlist[0];
			}
			if(count($rlist)>1)
			{
				if($rlist[1]=="")
				{
					$rlist[1]=-1;
				}
				else
				{
					$rmax=$rlist[1];
					if($rmax>$this->_max && $this->_max!=-1)
					{
						$this->_max=$rmax;
					}
				}
			}
			else
			{
				$rmax=$rmin;
			}
			$this->_recranges[]=array($rmin,$rmax);
		}
	}
	
	public function initialize($params)
	{
		$this->parseRanges($this->getParam("LIMITER:ranges",""));
		$this->parseFilters($this->getParam("LIMITER:filters",""));
		return true;
		
	}
	
	public function getPluginParamNames()
	{
		return array('LIMITER:ranges','LIMITER:filters');
	}
}