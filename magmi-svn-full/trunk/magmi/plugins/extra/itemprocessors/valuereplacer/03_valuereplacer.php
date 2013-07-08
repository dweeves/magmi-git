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
	protected $_before=array("sku","attribute_set","type");
    public function getPluginInfo()
    {
        return array(
            "name" => "Value Replacer",
            "author" => "Dweeves",
            "version" => "0.0.7a",
					 "url"=>$this->pluginDocUrl("Value_Replacer")
        );
    }
	
	
    public function parseval($pvalue,$item,$params)
	{
		$matches=array();
		$ik=array_keys($item);
		$rep="";
		/* TODO : FINISH dbitem syntax
		//do we have , db item reference in formula
		if(preg_match("|\{dbitem\.(.*?)\}|",$pvalue))
		{
			//step 1, list wanted field values
			while(preg_match("|\{dbitem\.(.*?)\}|",$pvalue,$matches))
			{
					if($match!=$matches[0])
					{
						$fields[$match]=$match;
					}
			}
			//step 2, build select
			foreach($fields as $attcode=>$dummy)
			{
				$attrinfo=$this->getAttrInfo($attcode);
				if($attrinfo)
			}
		
		}*/
		
		
		
		
		
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
					//escape potential "{{xxx}}" values in interpreted target
					//so that they won't be reparsed in next round
					$rep=preg_replace("|\{\{\s*(.*?)\s*\}\}|", "____$1____", $rep);
					$pvalue=str_replace($matches[0],$rep,$pvalue);							
				}				
			}
		}
		
		//unsecape matches
		$pvalue=preg_replace("|____(.*?)____|",'{{$1}}',$pvalue);
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
		//only check for "before" compatible fields
		for($i=0;$i<count($this->_before);$i++)
		{	
			$attname=$this->_before[$i];
			if(isset($this->_rvals[$attname]))
			{
				$item[$attname]=$this->parseval($this->_rvals[$attname],$item,$params);
			}
		}	
		return true;
	}

	public function processItemAfterId(&$item,$params=null)
	{
		foreach($this->_rvals as $attname=>$pvalue)
		{
			//do not reparse "before" fields
			if(!in_array($attname,$this->_before))
			{
				$item[$attname]=$this->parseval($pvalue,$item,$params);
			}
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
		$base_cols=$cols;
		$cols=array_unique(array_merge($cols,explode(",",$this->getParam("VREP:columnlist"))));
		$newcols=array_diff($cols, $base_cols);
		if(count($newcols)>0)
		{
			$this->log("Added columns : ".implode(",",$newcols),"startup");
		}
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