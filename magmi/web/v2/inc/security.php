<?php
require_once('session.php');

function parse_phpinfo()
{
    ob_start();
    phpinfo(INFO_MODULES);
    $s = ob_get_contents();
    ob_end_clean();
    $s = strip_tags($s, '<h2><th><td>');
    $s = preg_replace('/<th[^>]*>([^<]+)<\/th>/', '<info>\1</info>', $s);
    $s = preg_replace('/<td[^>]*>([^<]+)<\/td>/', '<info>\1</info>', $s);
    $t = preg_split('/(<h2[^>]*>[^<]+<\/h2>)/', $s, -1, PREG_SPLIT_DELIM_CAPTURE);
    $r = array();
    $count = count($t);
    $p1 = '<info>([^<]+)<\/info>';
    $p2 = '/'.$p1.'\s*'.$p1.'\s*'.$p1.'/';
    $p3 = '/'.$p1.'\s*'.$p1.'/';
    for ($i = 1; $i < $count; $i++) {
        if (preg_match('/<h2[^>]*>([^<]+)<\/h2>/', $t[$i], $matchs)) {
            $name = trim($matchs[1]);
            $vals = explode("\n", $t[$i + 1]);
            foreach ($vals as $val) {
                if (preg_match($p2, $val, $matchs)) { // 3cols
                    $r[$name][trim($matchs[1])] = array(trim($matchs[2]), trim($matchs[3]));
                } elseif (preg_match($p3, $val, $matchs)) { // 2cols
                    $r[$name][trim($matchs[1])] = trim($matchs[2]);
                }
            }
        }
    }
    return $r;
}

function testSecurity()
{
    //trick for docker hosted server accessed from host
   // unset($_SESSION['SERVERHOST']);
    if (!isset($_SESSION['SERVERHOST'])) {
        $_SESSION['SERVERHOST']=$_SERVER['HTTP_HOST'];
        //use php info parsing code
        $info=parse_phpinfo();
        //if we have an apache2handler
        /*if(isset($info['apache2handler']))
        {

            $hnp=$info['apache2handler']['Hostname:Port'];
            //if not the same than server host
            if($hnp!=$_SERVER['HTTP_HOST']) {
                $_SESSION['SERVERHOST'] = $hnp;
            }
        }*/
    }
    $pinghost=$_SESSION['SERVERHOST'];

    $url="http://$pinghost".$_SERVER['REQUEST_URI'];

    if (strpos(basename($url), '.php')!==false) {
        $url=str_replace(basename($url), 'test.php', $url);
    } else {
        $url=$url."test.php";
    }

    if (function_exists("curl_init")) {
        $req=curl_init($url);
        curl_setopt($req, CURLOPT_FRESH_CONNECT, 1);
        curl_setopt($req, CURLOPT_NOBODY, 1);
        curl_exec($req);
        $code=curl_getinfo($req, CURLINFO_HTTP_CODE);
        $secure=($code==401)?1:0;

        curl_close($req);
    } else {
        $hd=get_headers($url, 1);
        $secure=(strpos($hd[0], 401)!==false)?0:1;
    }
    return $secure;
}
unset($_SESSION['IS_SECURE']);
if (!isset($_SESSION['IS_SECURE'])) {
    $_SESSION['IS_SECURE']=testSecurity();
}
