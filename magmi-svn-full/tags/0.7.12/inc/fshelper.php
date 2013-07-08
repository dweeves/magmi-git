<?php
class FSHelper
{
	 public static function isDirWritable($dir)
	 {
			$test=@fopen("$dir/__testwr__","w");
			if($test==false)
			{
				return false;
			}
			else
			{
				fclose($test);
				unlink("$dir/__testwr__");
			}
			return true;
	 }
	 
}