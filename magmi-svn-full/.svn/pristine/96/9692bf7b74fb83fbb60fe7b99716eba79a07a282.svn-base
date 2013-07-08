<?php
class FSHelper
{
	 public static function isDirWritable($dir)
	 {
		clearstatcache();
		$info=stat($dir);
		$uid=$info["uid"];
		$gid=$info["gid"];
		if(function_exists("posix_getuid"))
		{
			$puid=posix_getuid();
			$pgid=posix_getgid();
			
		}
		else
		{
			$puid=getmyuid();
			$pgid=getmygid();
		}
		list($owner,$grp,$other)=substr(decoct($info["mode"]),2);
		$v1=($uid==$puid && (intval($owner) & 2)==2);
		$v2=($gid==$pgid && (intval($grp) & 2)==2);
		$v3=(intval($other) & 2)==2;
	 	$ok=( $v1|| $v2 || $v3);
	 	return $ok;
	 }
	 
}