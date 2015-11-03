<?php
require_once(dirname(dirname(__FILE__))."/inc/magmi_auth.php");
function authenticate($username="",$password=""){

    $auth = new Magmi_Auth($username,$password);

    return $auth->authenticate();
}

if (!isset($_SERVER['PHP_AUTH_USER'])) {
    header('WWW-Authenticate:Basic realm="Magmi"');
    header('HTTP/1.0 401 Unauthorized');
    echo 'You must be logged in to use Magmi';
    die();
} else {
    if (!authenticate($_SERVER['PHP_AUTH_USER'],$_SERVER['PHP_AUTH_PW'])){
        header('WWW-Authenticate: Basic realm="Magmi"');
        header('HTTP/1.0 401 Unauthorized');
        echo 'You must be logged in to use Magmi';
        die();
    }

}