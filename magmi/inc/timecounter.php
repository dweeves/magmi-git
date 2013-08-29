<?php
class TimeCounter
{
	
	protected $_timingcats=array();
	protected $_defaultsrc="";
	
	public function __construct($defaultsrc="*")
	{
		$this->_defaultsrc=$defaultsrc;
	}
	public function initTimingCats($tcats)
	{
		foreach($tcats as $tcat)
		{
			$this->_timingcats[$tcat]=array("_counters"=>array(),"global"=>array());
		}
	}

	public function getTimingCategory($cat)
	{
		return $this->_timingcats[$cat];
	}
	
	public function addCounter($cname,$tcats=null)
	{
		if($tcats==null)
		{
			$tcats=array_keys($this->_timingcats);
		}
		foreach($tcats as $tcat)
		{
			if(!isset($this->_timingcats[$tcat]))
			{
				$this->_timingcats[$tcat]=array("_counters"=>array());
			}
			$this->_timingcats[$tcat]["_counters"][$cname]=0;
		}
	}
	
	
	public function addTimedPhases($tfarr,$tcats=null)
	{
		if(!is_array($tfarr))
		{
			$tfarr=array($tfarr);
		}

		if($tcats==null)
		{
			$tcats=array_keys($this->_timingcats);
		}

		foreach($tcats as $tcat)
		{
			foreach($tfarr as $tp)
			{
				if(!isset($this->_timingcats[$tcat][$tp]))
				{
					$this->_timingcats[$tcat][$tp]=array();
				}
			}
		}	
}

	public function initCounter($cname,$tcats=null)
	{
		if($tcats==null)
		{
			$tcats=array_keys($this->_timingcats);
		}
		foreach($this->_timingcats as $tcat=>$dummy)
		{
			$this->_timingcats[$tcat]["_counters"][$cname]=0;
		}
	}
	
	public function incCounter($cname,$tcats=null)
	{
		if($tcats==null)
		{
			$tcats=array_keys($this->_timingcats);
		}
			foreach($this->_timingcats as $tcat=>$dummy)
		{
			if(!isset($this->_timingcats[$tcat]["_counters"][$cname]))
			{
				$this->_timingcats[$tcat]["_counters"][$cname]=0;
			}
			$this->_timingcats[$tcat]["_counters"][$cname]++;
		}
	}
	
	public function initTime($phase="global",$src=null)
	{
		if($src==null)
		{
			$src=$this->_defaultsrc;
		}
		$t=microtime(true);
		foreach($this->_timingcats as $tcat=>$dummy)
		{
			if(!isset($this->_timingcats[$tcat][$phase]))
			{
				$this->_timingcats[$tcat][$phase]=array();
			}
			if(!isset($this->_timingcats[$tcat][$phase][$src]))
			{
				$this->_timingcats[$tcat][$phase][$src]=array("init"=>$t,"dur"=>0);
			}
			$this->_timingcats[$tcat][$phase][$src]["start"]=$t;
		}
}

	public function exitTime($phase,$src=null)
	{
		if($src==null)
		{
			$src=$this->_defaultsrc;
		}
		$end=microtime(true);
		foreach($this->_timingcats as $tcat=>$phasetimes)
		{
			$this->_timingcats[$tcat][$phase][$src]["end"]=$end;
			$this->_timingcats[$tcat][$phase][$src]["dur"]+=$end-$phasetimes[$phase][$src]["start"];
		}
	}

	public function getPhaseTimes($tcat=null)
	{
		if($cat==null)
			return $this->_timingcats;
			return $this->_timingcats[$tcat];
	}
}