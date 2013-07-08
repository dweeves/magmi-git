<?php
/**
 * Class SampleItemProcessor
 * @author dweeves
 *
 * This class is a sample for item processing   
*/ 
class ValueReplacerItemProcessor extends Magmi_ItemProcessor
{

	protected $_rvals=array();
	
    public function getPluginInfo()
    {
        return array(
            "name" => "Value Replacer",
            "author" => "Dweeves",
            "version" => "0.0.5",
					 "url"=>"http://sourceforge.net/apps/mediawiki/magmi/index.php?title=Value_Replacer"
        );
    }
	
	
    public function parseval($pvalue,$item,$params)
	{
		$matches=array();
		$ik=array_keys($item);
		$rep="";
		while(preg_match("|\{item\.(.*?)\}|",$pvalue,$matches))
		{
			foreach($matches as $match)
			{
				if($match!=$matches[0])
				{
					if(in_array($match,$ik))
					{
						$rep='$item["'.$match.'"]';
					}
					else
					{
						$rep="";
					}
					$pvalue=str_replace($matches[0],$rep,$pvalue);
				}
			}
		}
		unset($matches);
		$meta=$params;
		
		while(preg_match("|\{meta\.(.*?)\}|",$pvalue,$matches))
		{
			foreach($matches as $match)
			{
				if($match!=$matches[0])
				{
					if(in_array($match,$ik))
					{
						$rep='$meta["'.$match.'"]';
					}
					else
					{
						$rep="";
					}
					$pvalue=str_replace($matches[0],$rep,$pvalue);
				}
			}
		}
		unset($matches);
	
		//replacing expr values
		while(preg_match("|\{\{\s*(.*?)\s*\}\}|",$pvalue,$matches))
		{
			foreach($matches as $match)
			{
				if($match!=$matches[0])
				{
					$code=trim($match);
					$rep=eval("return ($code);");
					$pvalue=str_replace($matches[0],$rep,$pvalue);							
				}				
			}
		}
		
		//replacing single values not in complex values
		while(preg_match('|\$item\["(.*?)"\]|',$pvalue,$matches))
		{
			foreach($matches as $match)
			{
				if($match!=$matches[0])
				{
					if(in_array($match,$ik))
					{
						$rep=$item[$match];
					}
					else
					{
						$rep="";
					}
					$pvalue=str_replace($matches[0],$rep,$pvalue);
				}
			}
		}
		
		unset($matches);
		return $pvalue;
	}
	
	public function processItemBeforeId(&$item,$params=null)
	{
		foreach($this->_rvals as $attname=>$pvalue)
		{
			$item[$attname]=$this->parseval($pvalue,$item,$params);
		}
		return true;
	}
	
	public function initialize($params)
	{
		foreach($params as $k=>$v)
		{
			if(preg_match_all("/^VREP:(.*)$/",$k,$m) && $k!="VREP:columnlist")
			{
				$colname=rawurldecode($m[1][0]);
				$this->_rvals[$colname]=$params[$k];
			}
		}
	}
	
	//auto add columns if not set 
	public function processColumnList(&$cols)
	{
		$cols=array_unique(array_merge($cols,explode(",",$this->getParam("VREP:columnlist"))));
	}
	
	public function getPluginParams($params)
	{
		$pp=array();
		foreach($params as $k=>$v)
		{
			if(preg_match("/^VREP:.*$/",$k))
			{
				$pp[$k]=$v;
			}
		}	
		return $pp;
	}	
	
	static public function getCategory()
	{
		return "Input Data Preprocessing";
	}
}