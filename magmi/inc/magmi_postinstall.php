<?php
require_once("magmi_version.php");

function magmi_post_install()
{
	$out="";
	if(version_compare(Magmi_Version::$version,"0.7.17")<=0)
	{
		$delcds=array_merge(glob("../integration/inc/*.*"),glob("../integration/samples/*.*"));
		$todelete=array();
		foreach($delcds as $fname)
		{
			$todelete[]=basename($fname);
		}
		$allfiles=glob("../integration/*.*");
		if($allfiles!==false)
		{
			foreach($allfiles as $fname)
			{
				if(in_array(basename($fname),$todelete))
				{
				    $out.="deleting $fname (new dir struct)<br>";
					unlink($fname);
				}
				else 
				{
				    $out.="moving $fname to migrated (custom script)<br>";
				    @mkdir("../integration/scripts/migrated/");
					copy($fname,"../integration/scripts/migrated/".basename($fname));
					unlink($fname);
				}
			}
		}
		else
		{
		  $out="nothing to do";
		}
		
	}
	return array("OK"=>$out);
}