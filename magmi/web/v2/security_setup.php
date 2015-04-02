<?php
/**
 * Created by PhpStorm.
 * User: seb
 * Date: 01/04/15
 * Time: 22:43
 */
require_once('utils.php');
function getWebServerHelper()
{
    $wst=getWebServerType();
    $classname=class_exists(ucfirst($wst["Server"]."ServerHelper"));
    if(class_exists($classname))
    {
        $helperinst=new $classname($wst["Version"]);
        return $helperinst;
    }
    else
    {
        return null;
    }
}

abstract class WebServerHelper
{
    protected $_version;
    protected $_user="magmi";
    protected $_pass="magmi";
    protected $_templatesdir;
    protected $_signature="#MAGMI SECURITY FILE";
    public function __construct($version)
    {
        $this->_version=$version;
        $this->_templatesdir=dirname(__FILE__)."/securitytpl";
    }

    public function setCredentials($user,$pass)
    {
        $this->$_user=$user;
        $this->$_pass=$pass;
    }

    public function getWebUI()
    {
        return "WEBUI from ".get_class($this)."!!!!";
    }

    public abstract function secureServer();

}


class ApacheServerHelper extends WebServerHelper
{
    protected $_passfile="../../../magmipass";

    public function __construct($version)
    {
        parent::__construct($version);
    }

    public function secureServer()
    {
        if(version_compare("2.4",$this->version,">="))
        {
            $this->generateFiles_24();
        }
        else
        {
            $this->generateFiles_22();
        }
    }

    public function copyOrInsertTemplate($tplname,$dest)
    {

          $tplcontent=file_get_contents($this->_templatesdir."/$tplname");
          $cf=fopen($dest,"a");
          fwrite($cf,$tplcontent);
          fclose($cf);
    }

    public function generateFiles_24()
    {
        $sfname=".htaccess";
        //check if we have already a .htaccess
        //generating "main dir" .htaccess
        $this->copyOrInsertTemplate("main.htaccess",dirname(__FILE__)."/.htaccess");
        $this->copyOrInsertTemplate("images.htaccess",dirname(__FILE__)."/images/.htaccess");

    }
}

class NginxServerHelper
{
    public function __construct($version)
    {

    }
}
