<?php
require_once(dirname(dirname(__FILE__))."/inc/magmi_auth.php");
function authenticate($username="",$password=""){

    $auth = new Magmi_Auth($username,$password);

    return $auth->authenticate();
}

if (!isset($_SERVER['PHP_AUTH_USER'])) {
    /*
     * php-cgi/fpm under Apache does not pass HTTP Basic user/pass to PHP by default
     * For this workaround to work, add these lines to your .htaccess file:
     * RewriteEngine On
     * RewriteCond %{HTTP:Authorization} ^(.+)$
     * RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]
     * @see https://github.com/symfony/symfony/blob/master/src/Symfony/Component/HttpFoundation/ServerBag.php#L47
     */
    $authorizationHeader = null;
    if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $authorizationHeader = $_SERVER['HTTP_AUTHORIZATION'];
    } elseif (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
        $authorizationHeader = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
    }
    if (null !== $authorizationHeader) {
        if (0 === stripos($authorizationHeader, 'basic ')) {
            // Decode AUTHORIZATION header into PHP_AUTH_USER and PHP_AUTH_PW when authorization header is basic
            $exploded = explode(':', base64_decode(substr($authorizationHeader, 6)), 2);
            if (count($exploded) == 2) {
                list($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']) = $exploded;
            }
        } elseif (empty($_SERVER['PHP_AUTH_DIGEST']) && (0 === stripos($authorizationHeader, 'digest '))) {
            // In some circumstances PHP_AUTH_DIGEST needs to be set
            $_SERVER['PHP_AUTH_DIGEST'] = $authorizationHeader;
        }
    }
}

if (!isset($_SERVER['PHP_AUTH_USER'])) {
    header('WWW-Authenticate:Basic realm="Magmi"');
    header('HTTP/1.0 401 Unauthorized');
    echo 'You must be logged into magento admin to use Magmi';
    die();
} else {
    if (!authenticate($_SERVER['PHP_AUTH_USER'],$_SERVER['PHP_AUTH_PW'])){
        header('WWW-Authenticate: Basic realm="Magmi"');
        header('HTTP/1.0 401 Unauthorized');
        echo 'You must be logged in to use Magmi';
        die();
    }

}