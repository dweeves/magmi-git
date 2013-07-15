<?php 
class FileLogger
	{
		protected $_fname;
		
		public function __construct($fname=null)
		{
			if($fname==null)
			{
				$fname=Magmi_StateManager::getProgressFile(true);
			}
			$this->_fname=$fname;
			$f=fopen($this->_fname,"w");
			if($f==false)
			{
				throw new Exception("CANNOT WRITE PROGRESS FILE ");
			}
			fclose($f);
		}

		public function log($data,$type)
		{
			
			$f=fopen($this->_fname,"a");
			if($f==false)
			{
				throw new Exception("CANNOT WRITE PROGRESS FILE ");
			}
			$data=preg_replace ("/(\r|\n|\r\n)/", "<br>", $data);
			fwrite($f,"$type:$data\n");
			fclose($f);
		}
		
	}
	
	class EchoLogger
	{
		public function log($data,$type)
		{
			$info=explode(";",$type);
			$type=$info[0];
			echo('<p class="logentry log_'.$type.'">'.$data."</p>");
		}
		
	}
	class CLILogger
	{
		public function log($data,$type)
		{
			echo("$type:$data\n");
		}
	}
?>
