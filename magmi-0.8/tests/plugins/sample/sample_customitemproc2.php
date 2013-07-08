<?php
/**
 * Class SampleItemProcessor
 * @author dweeves
 *
 * This class is a sample for item processing
*/
class SampleCustomItemProcessor2 extends Magmi_ItemProcessor
{

	public function getPluginInfo()
    {
        return array(
            "name" => "Sample Custom2",
            "author" => "Your Name",
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
		/* example code 1 */
		/* i've added some non item attribute values in my csv (cp_id,cp_price) but
		 * these values are meant to be inserted into a custom module table
		 * so , i get the following code (commented)
		 */

		/*
		 //if my special column cp_id exists
		  if(isset($item['cp_id']))
		{
			//if its value is not empty
			if($item['cp_id']!="")
			{
				//ask mmi for custom module table name (takes into account table prefix
				$tname=$mmi->tablename("book_categoryprice");
				//parameterized sql for insert (ignore doubles)
				$sql="INSERT IGNORE INTO $tname (cp_id,price) VALUES (?,?)";
				$data=array($item["cp_id"],$item["cp_price"]);
				//ask mmi to perform insert
				$mmi->insert($sql,$data);
			}
			//remove my special columns from item
			unset($item["cp_id"]);
			unset($item["cp_price"]);
		}*/

		/** example code 2 **/
		/*
		 * //if we have qty column & are in create mode & reset, do not import items with 0 qty
		 	if(isset($item['qty']) && $item['qty']==0 && $mmi->mode=="create" && $mmi->reset)
		 	{
		 	 	return false;
		 	}
		 * /
		 */
		//return true , enable item processing
		return true;
	}

	public function processItemAfterId(&$item,$params=null)
	{
		return true;
	}

	/*
	public function processItemException($mmi,&$item,$params=null)
	{

	}*/

	public function initialize($params)
	{
        return true;
	}

    public function processColumnList(&$cols,$params=null)
	{
        return true;
	}
}