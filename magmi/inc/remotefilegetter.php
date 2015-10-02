<?php

abstract class RemoteFileGetter
{
    protected $_errors;
    protected $_user;
    protected $_password;
    protected $_logger = null;

    public function setLogger($logger)
    {
        $this->_logger = $logger;
    }

    public function log($data)
    {
        if ($this->_logger != null) {
            $this->_logger->log($data);
        }
    }

    abstract public function urlExists($url);

    abstract public function copyRemoteFile($url, $dest);

    // using credentials
    public function setCredentials($user = null, $passwd = null)
    {
        $this->_user = $user;
        $this->_password = $passwd;
    }

    public function getErrors()
    {
        return $this->_errors;
    }
}

class CURL_RemoteFileGetter extends RemoteFileGetter
{
    protected $_cookie;
    protected $_lookup_opts;
    protected $_dl_opts;
    protected $_lookup;
    protected $_protocol;
    protected $_creds;
    protected $_opts;
    protected $_user;
    protected $_password;

    public function __construct()
    {
        $this->_opts=array(
           'http'=>array('lookup'=>$this->initBaseOptions('http', 'lookup'),
                          'dl'=>$this->initBaseOptions('http', 'dl')),
            'https'=>array('lookup'=>$this->initBaseOptions('https', 'lookup'),
                           'dl'=>$this->initBaseOptions('https', 'dl')),
            'ftp'=>array('dl'=>$this->initBaseOptions('ftp', 'dl'))
        );
    }

    public function initBaseOptions($protocol, $mode)
    {
        $curlopts=array();
        switch ($protocol) {
            case 'http':
            case 'https':
                switch ($mode) {
                    case 'lookup':
                        $curlopts=array(
                               // we want the response
                               CURLOPT_RETURNTRANSFER=>true,
                               // we want the headers
                               CURLOPT_HEADER=>true,
                               // we don't want the body
                               CURLOPT_NOBODY=>true,
                               // some stats on target
                                CURLOPT_FILETIME=>true);
                        break;
                    case 'dl':
                        $curlopts=array(
                            // force get
                            CURLOPT_HTTPGET=>true,
                            // no header
                            CURLOPT_HEADER=>false,
                            // we want body
                            CURLOPT_NOBODY=>false,
                            // handle 100 continue
                            CURLOPT_HTTPHEADER=>array('Expect:'),
                            // we don't want the response as we will store it in a file
                            CURLOPT_RETURNTRANSFER=>false,
                            //use binary
                            CURLOPT_BINARYTRANSFER=>true
                        );
                        break;
                    default:
                        break;
                }
                //fix for some servers not able to follow location & failing downloads
                //only set follow location if compatible with PHP settings
                if (ini_get('open_basedir') == '' && ini_get('safe_mode' == 'Off')) {
                    $curlopts[CURLOPT_FOLLOWLOCATION]=1;
                }
                break;
                /*
        	     * Initializing for ftp
        	    */
            case 'ftp':
                $curlopts = array(
                    //longer timeouts for big files
                    CURLOPT_TIMEOUT =>300,
                    //use binary
                    CURLOPT_BINARYTRANSFER=>true,
                    CURLOPT_FOLLOWLOCATION=> 1,
                    //Better compatibility with some FTP Servers
                    CURLOPT_FTP_USE_EPSV=>0,
                    //no need to return anything, we'll have a file pointer
                    CURLOPT_RETURNTRANSFER=>0);
                break;
        }
        return $curlopts;
    }

    public function setAuthOptions($context, &$opts, $user=null, $pass=null)
    {
        $creds="";
        if ($user == null) {
            $user=$this->_user;
            $pass=$this->_password;
        }

        if ($user) {
            $creds=$user.":";
        }

        if ($pass) {
            $creds.=$pass;
        }

        if (!is_null($creds) && $creds != "" && !isset($opts[CURLOPT_USERPWD])) {
            if (substr($context['scheme'], 0, 4) == "http") {
                $opts[CURLOPT_HTTPAUTH] = CURLAUTH_ANY;
                $opts[CURLOPT_UNRESTRICTED_AUTH] = true;
            }
            $opts[CURLOPT_USERPWD] = "$creds";
        }
    }

    /*
     * Creating a CURL context with adequate options from an URL For a given URL host/port/user , the same context is reused for optimizing performance
     */
    public function createContext($url)
    {
        // parsing url components
        $comps = parse_url($url);
        if ($comps == false || !isset($this->_opts[$comps['scheme']])) {
            throw new Exception("Unsupported URL : $url");
        }

        // create a curl context
        $ch = curl_init();
        $opts=$this->_opts[$comps['scheme']];
        $ctx=array("curlhandle"=>$ch,"opts"=>$opts,"scheme"=>$comps['scheme']);

        /*
         * Inline user/pass if in url
        */
        if (isset($comps['user'])) {
            $ctx["creds"]=array($comps['user'],$comps['password']);
        }
        return $ctx;
    }

    public function destroyContext($context)
    {
        curl_close($context["curlhandle"]);
    }


    public function urlExists($remoteurl)
    {
        $context = $this->createContext($remoteurl);
        // assume existing urls
        if (!isset($context["opts"]["lookup"])) {
            return true;
        }
        $ch=$context["curlhandle"];
        $opts=$context["opts"]["lookup"];
        $this->setAuthOptions($context, $opts);
        //adding url to curl
        $this->setURLOptions($remoteurl, $opts);
        // optimized lookup through curl
        curl_setopt_array($ch, $opts);


        /* Get the HTML or whatever is linked in $url. */
        $response = curl_exec($ch);
        if ($context['scheme'] == "http" || $context['scheme'] == "https") {
            /* Check for 404 (file not found). */
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $exists = ($httpCode < 400);
            /* retry on error */

            if ($httpCode == 503 or $httpCode == 403) {
                /* wait for a half second */
                usleep(500000);
                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $exists = ($httpCode < 400);
            }
        }
        $this->destroyContext($context);
        return $exists;
    }

    // using credentials
    public function setCredentials($user = null, $passwd = null)
    {
        $this->_user = $user;
        $this->_password = $passwd;
    }

    // using cookie
    public function setCookie($cookie = null)
    {
        $this->_cookie = $cookie;
    }

    public function copyRemoteFile($url, $dest)
    {
        $result = false;
        $this->_errors=array();
        try {
            $result = $this->getRemoteFile($url, $dest, $this->_cookie);
        } catch (Exception $e) {
            $this->_errors = array("type"=>"source error","message"=>$e->getMessage(),"exception"=>$e);
        }
        return $result;
    }

    public function setURLOptions($url, &$optab)
    {
        // handle spaces in url
        $curl_url = str_replace(" ", "%20", $url);
        $optab[CURLOPT_URL] = $curl_url;
    }


    public function getRemoteFile($url, $dest, $authmode = null, $cookies = null)
    {
        $context = $this->createContext($url);
        $ch=$context['curlhandle'];
        $dl_opts = $context['opts']['dl'];
        $outname = $dest;

        if ($cookies) {
            if (substr($url, 0, 4) == "http") {
                $dl_opts[CURLOPT_COOKIE] = $cookies;
            }
        }

        $fp = fopen($outname, "w");
        if ($fp == false) {
            $this->destroyContext($context);
            throw new Exception("Cannot write file:$outname");
        }
        $dl_opts[CURLOPT_FILE] = $fp;
        $this->setURLOptions($url, $dl_opts);
        $this->setAuthOptions($context, $dl_opts);


        // Download the file , force expect to nothing to avoid buffer save problem
        curl_setopt_array($ch, $dl_opts);
        $inf = curl_getinfo($ch);
        if (!curl_exec($ch)) {
            if (curl_error($ch) != "") {
                $err = "Cannot fetch $url :" . curl_error($ch);
            } else {
                $err = "CURL Error downloading $url";
            }
            $this->destroyContext($context);
            fclose($fp);
            unlink($dest);
            throw new Exception($err);
        } else {
            $proto=$context['scheme'];
            if ($proto=='http' || $proto=='https') {
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $ok = ($httpCode < 400);
                if (!$ok) {
                    fclose($fp);
                    @unlink($outname);
                    throw new Exception('Cannot fetch URL :'.$url);
                }
            }
        }

        fclose($fp);
        $this->destroyContext($context);

        return true;
    }
}

class URLFopen_RemoteFileGetter extends RemoteFileGetter
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
            $this->_errors = array("type"=>"target error","message"=>"URL $url is unreachable");
            return false;
        }

        $ok = @copy($url, $dest);
        if (!$ok) {
            $this->_errors = error_get_last();
        }
        return $ok;
    }
}

class RemoteFileGetterFactory
{
    private static $__fginsts = array();

    public static function getFGInstance($id = "default")
    {
        if (!isset(self::$__fginsts[$id])) {
            if (function_exists("curl_init")) {
                self::$__fginsts[$id] = new CURL_RemoteFileGetter();
            } else {
                self::$__fginsts[$id] = new URLFopen_RemoteFileGetter();
            }
        }
        return self::$__fginsts[$id];
    }
}
