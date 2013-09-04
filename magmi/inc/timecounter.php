<?php
class TimeCounter
{
	
	protected $_timingcats=array();
	protected $_defaultsrc="";
	protected $_timingcontext=array();
	
	public function __construct($defaultsrc="*")
	{
		$this->_defaultsrc=$defaultsrc;
	}
	public function initTimingCats($tcats)
	{
		foreach($tcats as $tcat)
		{
			$this->_timingcats[$tcat]=array("_counters"=>array(),"_timers"=>array());
		}
	}

	public function getTimingCategory($cat)
	{
		return $this->_timingcats[$cat];
	}
	
	public function getTimers()
	{
		$timers=array();
		foreach($this->_timingcats as $cname=>$info)
		{
			$timers[$cname]=$info['_timers'];
		}
		return $timers;
	}
	
	public function getCounters()
	{
		$counters=array();
		foreach($this->_timingcats as $cname=>$info)
		{
			$counters[$cname]=$info['_counters'];
		}
		return $counters;
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
				$this->_timingcats[$tcat]=array("_counters"=>array(),"_timers"=>array());
			}
			$this->_timingcats[$tcat]["_counters"][$cname]=0;
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
	
	public function initTime($phase="global",$src=null,$tcat=null)
	{
		if(isset($src) )
		{
			array_push($this->_timingcontext,$src);
			$this->_timingcontext=array_values(array_unique($this->_timingcontext));
		}
		if(count($this->_timingcontext)==0)
			return;
		
		
		if(!isset($tcat))
		{
			$tcats=$this->_timingcats;
		}
		else
		{
			$tcats=array($tcat=>$this->_timingcats[$tcat]);
		}
				$t=microtime(true);
		
		foreach($tcats as $tcat=>$dummy)
		{
			if(!isset($this->_timingcats[$tcat]["_timers"][$phase]))
			{
				$this->_timingcats[$tcat]["_timers"][$phase]=array();
			}
			$ctxc=count($this->_timingcontext);
			for($i=0;$i<$ctxc;$i++)
			{
				$src=$this->_timingcontext[$i];
				if(!isset($this->_timingcats[$tcat]["_timers"][$phase][$src]))
				{
					$this->_timingcats[$tcat]["_timers"][$phase][$src]=array("init"=>$t,"dur"=>0);
				}
				$this->_timingcats[$tcat]["_timers"][$phase][$src]["start"]=$t;
			}
		}
}

	public function exitTime($phase,$src=null,$tcat=null)
	{
		$targets=$this->_timingcontext;
		if(count($targets)==0)
		{
			return;
		}
		if(isset($src) && in_array($src,$this->_timingcontext))
		{
			$targets=array($src);
		}
		
		$ctargets=count($targets);

		if($ctargets==0)
			return;
		if($tcat==null)
		{
			$tcats=$this->_timingcats;
		}
		else
		{
			$tcats=array($tcat=>$this->_timingcats[$tcat]);
		}
		$end=microtime(true);
		foreach($tcats as $tcat=>$phasetimes)
		{
			for($i=0;$i<$ctargets;$i++)
			{
				$src=$targets[$i];
				if(isset($this->_timingcats[$tcat]["_timers"][$phase][$src]))
				{
					$this->_timingcats[$tcat]["_timers"][$phase][$src]["end"]=$end;
					$this->_timingcats[$tcat]["_timers"][$phase][$src]["dur"]+=$end-$this->_timingcats[$tcat]["_timers"][$phase][$src]["start"];
				}
				else
				{
					echo "Invalid timing source : $src";
				}
			}
		
		}
		$this->_timingcontext=array_diff($this->_timingcontext, $targets);
	}


}