<?php
require_once('session.php');
function testSecurity()
{
    $url="http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
    $url=str_replace(basename($url),'test.php',$url);
    if(function_exists("curl_init"))
    {
        $req=curl_init($url);
        curl_setopt($req,CURLOPT_FRESH_CONNECT,1);
        curl_setopt($req,CURLOPT_NOBODY,1);
        curl_exec($req);
        $code=curl_getinfo($req,CURLINFO_HTTP_CODE);
        $secure=($code==401)?1:0;

        curl_close($req);
    }
    return $secure;
}
unset($_SESSION['IS_SECURE']);
if(!isset($_SESSION['IS_SECURE']))
{
    $_SESSION['IS_SECURE']=testSecurity();
}