<?php
/**
 * Class SampleItemProcessor
 * @author dweeves
 *
 * This class is a sample for item processing   
*/ 
class ValueTrimItemProcessor extends Magmi_ItemProcessor
{

    public function getPluginInfo()
    {
        return array(
            "name" => "Value Trimmer for select/multiselect",
            "author" => "Dweeves",
            "version" => "0.0.1"
        );
    }
	
	/**
	 * you can add/remove columns for the item passed since it is passed by reference
	 * @param MagentoMassImporter $mmi : reference to mass importer (convenient to perform database operations)
	 * @param unknown_type $item : modifiable reference to item before import
	 * the $item is a key/value array with column names as keys and values as read from csv file.
	 * @return bool : 
	 * 		true if you want the item to be imported after your custom processing
	 * 		false if you want to skip item import after your processing
	 */
	
	
	public function processItemBeforeId(&$item,$params=null)
	{
		//return true , enable item processing
		return true;
	}
	
	public function processItemAfterId(&$item,$params=null)
	{
		return true;
	}
	
	/*
	public function processItemException(&$item,$params=null)
	{
		
	}*/
	
	public function initialize($params)
	{
		return true;
	}
	
	public function processColumnList(&$cols,$params=null)
	{
		foreach($cols as $col)
		{
			$ainfo=$this->getAttrInfo($col);
			if(isset($ainfo))
			{
				if($ainfo["frontend_input"]=="select" || $ainfo["frontend_input"]=="multiselect")
				{
					$item[$col]=trim($item[$col]);
				}
			}
		}
        return true;
	}
}