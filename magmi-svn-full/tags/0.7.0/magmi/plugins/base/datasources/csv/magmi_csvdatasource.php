<?php
class CSVException extends Exception
{
	
}

class Magmi_CSVDataSource extends Magmi_Datasource
{
	protected $_filename;
	protected $_fh;
	protected $_cols;
	protected $_csep;
	protected $_dcsep;
	
	protected $_buffersize;
	protected $_curline;
	protected $_nhcols;
	
	public function initialize($params)
	{
		$this->_filename=$this->getParam("CSV:filename");
		$this->_csep=$this->getParam("CSV:separator",",");
		$this->_dcsep=$this->_csep;
		
		if($this->_csep=="\\t")
		{
			$this->_csep="\t";
		}
		
		$this->_cenc=$this->getParam("CSV:enclosure",'"');
		$this->_buffersize=$this->getParam("CSV:buffer",0);
		$this->_curline=0;
		ini_set("auto_detect_line_endings",true);
		if(!isset($this->_filename))
		{
			throw new CSVException("No csv file set");
		}
		if(!file_exists($this->_filename))
		{
			throw new CSVException("{$this->_filename} not found");
		}
		
	}
	
	public function getPluginParamNames()
	{
		return array('CSV:filename','CSV:enclosure','CSV:separator');
	}
	public function getPluginInfo()
	{
		return array("name"=>"CSV Datasource",
					 "author"=>"Dweeves",
					 "version"=>"1.0.4");
	}
	
	public function getRecordsCount()
	{
		//open csv file
		$f=fopen($this->_filename,"rb");
		//get records count
		$count=-1;
		while(fgetcsv($f,$this->_buffersize,$this->_csep,$this->_cenc))
		{
			$count++;
		}
		fclose($f);
		return $count;
	}
	
	public function getAttributeList()
	{
		
	}
	
	public function beforeImport()
	{
		$this->log("Importing CSV : $this->_filename using separator [ $this->_dcsep ] enclosing [ $this->_cenc ]","startup");
	}
	
	public function afterImport()
	{
		
	}
	
	public function startImport()
	{
		//open csv file
		$this->_fh=fopen($this->_filename,"rb");
	}
	
	public function getColumnNames()
	{
		$this->_cols=fgetcsv($this->_fh,$this->_buffersize,$this->_csep,$this->_cenc);
		$this->_nhcols=count($this->_cols);
		$this->log("$this->_nhcols CSV headers columns found","startup");
		return $this->_cols;
	}
	
	public function endImport()
	{
		fclose($this->_fh);	
	}

	public function isemptyline($row) {
  		return ( !isset($row[1]) && empty($row[0]) );
	}
	public function getNextRecord()
	{
		$row=null;
		while($row!==false && count($row)!=count($this->_cols))
		{
			$row=fgetcsv($this->_fh,$this->_buffersize,$this->_csep,$this->_cenc);
			$this->_curline++;			
			if(!$this->isemptyline($row) && count($row)!=$this->_nhcols)
			{				
				$this->log("warning: line $this->_curline , wrong column number : $rcols found over $this->_nhcols, line skipped","warning");
			}			
		}
		//create product attributes values array indexed by attribute code
		$record=(is_array($row)?array_combine($this->_cols,$row):false);
		unset($row);
		return $record;
	}
	

}