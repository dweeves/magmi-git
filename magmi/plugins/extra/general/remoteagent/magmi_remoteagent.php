<?php
// REMOTE AGENT IS A HTTP API ENABLING REMOTE FILE SAVING AND OTHER EXECUTION PROXYING
// THIS IS USING A SINGLE BIG FILE ON PURPOSE , MAYBE LATER MORE COMPLEX STRUCTURE WILL BE USED
class MRA_FSHelper
{
    public static function isDirWritable($dir)
    {
        $test = @fopen("$dir/__testwr__", "w");
        if ($test == false) {
            return false;
        } else {
            fclose($test);
            unlink("$dir/__testwr__");
        }
        return true;
    }

    public static function getExecMode()
    {
        $is_disabled = array();
        $disabled = explode(',', ini_get('disable_functions'));
        foreach ($disabled as $disableFunction) {
            $is_disabled[] = trim($disableFunction);
        }
        foreach (array("popen", "shell_exec") as $func) {
            if (!in_array($func, $is_disabled)) {
                return $func;
            }
        }
        return null;
    }
}

class MRA_MagentoDirHandlerFactory
{
    protected $_handlers = array();
    protected static $_instance;

    public function __construct()
    {
    }

    public static function getInstance()
    {
        if (!isset(self::$_instance)) {
            self::$_instance = new MagentoDirHandlerFactory();
        }
        return self::$_instance;
    }

    public function registerHandler($obj)
    {
        $cls = get_class($obj);
        if (!isset($this->_handlers[$cls])) {
            $this->_handlers[$cls] = $obj;
        }
    }

    public function getHandler($url)
    {
        foreach ($this->_handlers as $cls => $handler) {
            if ($handler->canHandle($url)) {
                return $handler;
            }
        }
    }
}

abstract class MRA_RemoteFileGetter
{
    protected $_errors;

    abstract public function urlExists($url);

    abstract public function copyRemoteFile($url, $dest);

    public function getErrors()
    {
        return $this->_errors;
    }
}

class MRA_CURL_RemoteFileGetter extends RemoteFileGetter
{
    protected $_curlh;

    public function createContext($url)
    {
        if ($this->_curlh == null) {
            $curl_url = str_replace(" ", "%20", $url);
            $context = curl_init($curl_url);
            $this->_curlh = $context;
        }
        return $this->_curlh;
    }

    public function destroyContext($url)
    {
        if ($this->_curlh != null) {
            curl_close($this->_curlh);
            $this->_curlh = null;
        }
    }

    public function urlExists($remoteurl)
    {
        $context = $this->createContext($remoteurl);
        // optimized lookup through curl
        /* head */
        curl_setopt($context, CURLOPT_HEADER, true);
        curl_setopt($context, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($context, CURLOPT_CUSTOMREQUEST, 'HEAD');
        curl_setopt($context, CURLOPT_NOBODY, true);

        /* Get the HTML or whatever is linked in $url. */
        $response = curl_exec($context);

        /* Check for 404 (file not found). */
        $httpCode = curl_getinfo($context, CURLINFO_HTTP_CODE);
        $exists = ($httpCode == 200);
        /* retry on error */

        if ($httpCode == 503 or $httpCode == 403) {
            /* wait for a half second */
            usleep(500000);
            $response = curl_exec($context);
            $httpCode = curl_getinfo($context, CURLINFO_HTTP_CODE);
            $exists = ($httpCode == 200);
        }
        return $exists;
    }

    public function copyRemoteFile($url, $dest)
    {
        $this->_errors = array();
        $ret = true;
        $context = $this->createContext($url);
        if (!$this->urlExists($url)) {
            $this->_errors = array("type"=>"download error","message"=>"URL $url is unreachable");
            return false;
        }
        $fp = fopen($dest, "w");
        // add support for https urls
        curl_setopt($context, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($context, CURLOPT_RETURNTRANSFER, false);
        curl_setopt($context, CURLOPT_CUSTOMREQUEST, 'GET');
        curl_setopt($context, CURLOPT_NOBODY, false);
        curl_setopt($context, CURLOPT_FILE, $fp);
        curl_setopt($context, CURLOPT_HEADER, 0);
        curl_setopt($context, CURLOPT_FAILONERROR, true);
        if (!ini_get('safe_mode')) {
            curl_setopt($context, CURLOPT_FOLLOWLOCATION, 1);
        }
        curl_exec($context);
        if (curl_getinfo($context, CURLINFO_HTTP_CODE) >= 400) {
            $this->_errors = array("type"=>"download error","message"=>curl_error($context));
            $ret = false;
        }
        fclose($fp);
        return $ret;
    }
}

class MRA_URLFopen_RemoteFileGetter extends RemoteFileGetter
{
    public function urlExists($url)
    {
        $fname = $url;
        $h = @fopen($fname, "r");
        if ($h !== false) {
            $exists = true;
            fclose($h);
        }
        unset($h);
    }

    public function copyRemoteFile($url, $dest)
    {
        if (!$this->urlExists($url)) {
            $this->_errors = array("type"=>"target error","message"=>"URL $remoteurl is unreachable");
            return false;
        }

        $ok = @copy($url, $dest);
        if (!$ok) {
            $this->_errors = error_get_last();
        }
        return $ok;
    }
}

class MRA_RemoteFileGetterFactory
{
    public static function getFGInstance()
    {
        $fginst = null;
        if (function_exists("curl_init")) {
            $fginst = new MRA_CURL_RemoteFileGetter();
        } else {
            $fginst = new MRA_URLFopen_RemoteFileGetter();
        }
        return $fginst;
    }
}

abstract class MRA_MagentoDirHandler
{
    protected $_magdir;
    protected $_lasterror;
    protected $_exec_mode;

    public function __construct($magurl)
    {
        $this->_magdir = $magurl;
        $this->_lasterror = array();
        $this->_exec_mode = MRA_FSHelper::getExecMode();
    }

    abstract public function canhandle($url);

    abstract public function file_exists($filepath);

    abstract public function mkdir($path, $mask = null, $rec = false);

    abstract public function copy($srcpath, $destpath);

    abstract public function unlink($filepath);

    abstract public function chmod($filepath, $mask);

    abstract public function exec_cmd($cmd, $params);

    public function isExecEnabled()
    {
        return $this->_exec_mode != null;
    }
}

class MRA_LocalMagentoDirHandler extends MRA_MagentoDirHandler
{
    public function __construct($magdir)
    {
        parent::__construct($magdir);
        MRA_MagentoDirHandlerFactory::getInstance()->registerHandler($this);
    }

    public function canHandle($url)
    {
        return (preg_match("|^.*?://.*$|", $url) == false);
    }

    public function file_exists($filename)
    {
        $mp = str_replace("//", "/", $this->_magdir . "/" . str_replace($this->_magdir, '', $filename));

        return file_exists($mp);
    }

    public function mkdir($path, $mask = null, $rec = false)
    {
        $mp = str_replace("//", "/", $this->_magdir . "/" . str_replace($this->_magdir, '', $path));

        if ($mask == null) {
            $mask = octdec('755');
        }
        $ok = @mkdir($mp, $mask, $rec);
        if (!$ok) {
            $this->_lasterror = error_get_last();
        }
        return $ok;
    }

    public function chmod($path, $mask)
    {
        $mp = str_replace("//", "/", $this->_magdir . "/" . str_replace($this->_magdir, '', $path));

        if ($mask == null) {
            $mask = octdec('755');
        }
        $ok = @chmod($mp, $mask);
        if (!$ok) {
            $this->_lasterror = error_get_last();
        }
        return $ok;
    }

    public function getLastError()
    {
        return $this->_lasterror;
    }

    public function unlink($path)
    {
        $mp = str_replace("//", "/", $this->_magdir . "/" . str_replace($this->_magdir, '', $path));
        return @unlink($mp);
    }

    public function copyFromRemote($remoteurl, $destpath)
    {
        $rfg = RemoteFileGetterFactory::getFGInstance();
        $mp = str_replace("//", "/", $this->_magdir . "/" . str_replace($this->_magdir, '', $destpath));
        $ok = $rfg->copyRemoteFile($remoteurl, $mp);
        if (!$ok) {
            $this->_lasterror = $rfg->getErrors();
        }
        unset($rfg);
        return $ok;
    }

    public function copy($srcpath, $destpath)
    {
        $result = false;
        if (preg_match('|^.*?://.*$|', $srcpath)) {
            $result = $this->copyFromRemote($srcpath, $destpath);
        } else {
            $result = @copy($srcpath, $destpath);
            if (!$result) {
                $this->_lasterror = error_get_last();
            }
        }
        return $result;
    }

    public function exec_cmd($cmd, $params)
    {
        $mp = str_replace("//", "/", $this->_magdir . "/" . str_replace($this->_magdir, '', $cmd));
        $full_cmd = $cmd . " " . $params;
        switch ($this->_exec_mode) {
            case "popen":
                $x = popen($full_cmd, "r");
                $out = "";
                while (!feof($x)) {
                    $data = fread($x, 1024);
                    $out .= $data;
                    usleep(100000);
                }
                fclose($x);
                break;
            case "shell_exec":
                $out = @shell_exec($full_cmd);
                break;
        }
        if ($out === false || $out == null) {
            $this->_lasterror = error_get_last();
            return false;
        }
        return $out;
    }
}

class Magmi_RemoteAgent
{
    private static $_instance;
    private $_mdh;
    private $_lasterror;
    public static $apidesc = array("getVersion"=>null,"copy"=>array("src","dest"),"mkdir"=>array("path","mask"),
        "chmod"=>array("path","mask"),"unlink"=>array("path"),"file_exists"=>array("path"),
        "exec_cmd"=>array("cmd","args"));

    public function __construct()
    {
        $this->_mdh = new MRA_LocalMagentoDirHandler(dirname(__FILE__));
    }

    public static function getStaticVersion()
    {
        return "1.0.2";
    }

    public function wrapResult($res)
    {
        if ($this->_lasterror == null) {
            return array("result"=>$res);
        } else {
            return array("error"=>$this->getLastError());
        }
    }

    public function getVersion()
    {
        return $this->wrapResult(array("version"=>self::getStaticVersion()));
    }

    public static function checkParams($params, $api)
    {
        $missing = array();
        $plist = Magmi_RemoteAgent::$apidesc[$api];
        for ($i = 0; $i < count($plist); $i++) {
            if (!isset($params[$plist[$i]])) {
                $missing[] = $plist[$i];
            }
        }
        return $missing;
    }

    public function getLastError()
    {
        $err = $this->_lasterror;
        $this->_lasterror = null;
        return $err;
    }

    public function copy($params)
    {
        $ok = $this->_mdh->copy($params['src'], $params['dest']);
        if (!$ok) {
            $this->_lasterror = $this->_mdh->getLastError();
        }
        return $this->wrapResult($ok);
    }

    public function file_exists($params)
    {
        $ok = $this->_mdh->file_exists($params['path']);
        return $this->wrapResult($ok);
    }

    public function mkdir($params)
    {
        $rec = isset($params['rec']);
        $ok = $this->_mdh->mkdir($params['path'], $params['mask'], $rec);
        if (!$ok) {
            $this->_lasterror = $this->_mdh->getLastError();
        }
        return $this->wrapResult($ok);
    }

    public function chmod($params)
    {
        $ok = $this->_mdh->chmod($params['path'], $params['mask']);
        if (!$ok) {
            $this->_lasterror = $this->_mdh->getLastError();
        }
        return $this->wrapResult($ok);
    }

    public function unlink($params)
    {
        $ok = $this->_mdh->unlink($params['path']);
        if (!$ok) {
            $this->_lasterror = $this->_mdh->getLastError();
        }
        return $this->wrapResult($ok);
    }

    public static function getInstance()
    {
        if (!isset(self::$_instance)) {
            self::$_instance = new Magmi_RemoteAgent();
        }
        return self::$_instance;
    }

    public function exec_cmd($params)
    {
        $out = $this->_mdh->exec_cmd($params['cmd'], $params['args']);
        if ($out === false) {
            $this->_lasterror = $this->_mdh->getLastError();
        }
        return $this->wrapResult($out);
    }
}

function sendResponse($calltype, $result)
{
    header("Content-type: application/json");
    echo json_encode(array($calltype=>$result));
}

function buildError($errname, $errdata)
{
    return array("error"=>array($errname,$errdata));
}

if (!class_exists('Magmi_Plugin')) {
    if (!isset($_REQUEST['api'])) {
        header('Status 406 : Unauthorized call', true, 406);
        exit();
    }

    $api = $_REQUEST['api'];

    if (!in_array($api, array_keys(Magmi_RemoteAgent::$apidesc))) {
        header('Status 406 : Unauthorized call', true, 406);
        exit();
    }

    $missing = Magmi_RemoteAgent::checkParams($_REQUEST, $api);
    if (count($missing) > 0) {
        header('Status 400 : Invalid parameters', true, 400);
        $error = buildError("missing mandatory parameters", implode(",", $missing));
        sendResponse($api, $error);
    } else {
        $mra = Magmi_RemoteAgent::getInstance();
        $result = $mra->$api($_REQUEST);
        sendResponse($api, $result);
    }
}
