<?php
require_once ("fshelper.php");

class RAResponse
{
    public $body;
    public $status;
    public $parsed;
    public $is_error;
    public $result;
    public $error;
    public $op;

    public function __construct($arr, $op)
    {
        $this->status = $arr['status'][1];
        $this->body = $arr['body'];
        $this->parsed = json_decode($this->body, true);
        $this->op = $op;
        $this->parsed = $this->parsed[$op];
        if (isset($this->parsed['result']))
        {
            $this->result = $this->parsed['result'];
            $this->is_error = false;
        }
        else
        {
            $this->error = $this->parsed['error'];
            $this->is_error = true;
        }
    }
}

class RAError extends RAResponse
{

    public function __construct($err)
    {
        $this->is_error = true;
        $this->error = array("type"=>"proxy error","code"=>"0","message"=>$err);
    }
}

class Magmi_RemoteAgent_Proxy extends MagentoDirHandler
{
    protected $_raurl = NULL;

    public function __construct($magurl, $raurl)
    {
        parent::__construct($magurl);
        $sep = (substr($raurl, -1) == "/" ? "" : "/");
        $this->_raurl = $raurl . $sep . "magmi_remoteagent.php";
        MagentoDirHandlerFactory::getInstance()->registerHandler($this);
    }

    public function getRemoteAgentUrl()
    {
        return $this->_raurl;
    }

    public function doPost($url, $params, $optional_headers = null)
    {
        $ctxparams = array('http'=>array('method'=>'POST','content'=>http_build_query($params)));
        if ($optional_headers !== null)
        {
            $ctxparams['http']['header'] = $optional_headers;
        }
        $ctx = stream_context_create($ctxparams);
        $fp = @fopen($url, 'rb', false, $ctx);
        if (!$fp)
        {
            return false;
        }
        $meta = stream_get_meta_data($fp);
        $httpinfo = explode(' ', $meta['wrapper_data'][0]);
        $response = @stream_get_contents($fp);
        if ($response === false)
        {}
        return array("status"=>$httpinfo,"body"=>$response);
    }

    public function doOperation($op, $params = array())
    {
        $hresp = $this->doPost($this->_raurl, array_merge(array("api"=>$op), $params));
        if ($hresp)
        {
            $resp = new RAResponse($hresp, $op);
        }
        else
        {
            $resp = new RAError("No connection to proxy");
        }
        return $resp;
    }

    public function getVersion()
    {
        if ($r = $this->doOperation('getVersion'))
        {
            return $r->result['version'];
        }
        else
        {
            return "0.0.0";
        }
    }

    public function file_exists($filepath)
    {
        $r = $this->doOperation('file_exists', array('path'=>$filepath));
        return $r->result;
    }

    public function mkdir($path, $mask = 0755, $rec = false)
    {
        $r = $this->doOperation('mkdir', array('path'=>$path,'mask'=>$mask,'rec'=>$rec));
        if ($r->is_error)
        {
            $this->_lasterror = $r->error;
        }
        return !$r->is_error;
    }

    public function unlink($filepath)
    {
        $r = $this->doOperation('unlink', array('path'=>$filepath));
        if ($r->is_error)
        {
            $this->_lasterror = $r->error;
        }
        return !$r->is_error;
    }

    public function exec_cmd($cmd, $params, $workingdir = null)
    {
        $r = $this->doOperation('exec_cmd', array('cmd'=>$cmd,'args'=>$params));
        if ($r->is_error)
        {
            $this->_lasterror = $r->error;
            return $r->error['message'];
        }
        
        return $r->result;
    }

    public function chmod($path, $mask)
    {
        $r = $this->doOperation('chmod', array('path'=>$path,'mask'=>$mask));
        if ($r->is_error)
        {
            $this->_lasterror = $r->error;
        }
        return !$r->is_error;
    }

    public function copy($srcpath, $destpath)
    {
        $r = $this->doOperation('copy', array('src'=>$srcpath,'dest'=>$destpath));
        if ($r->is_error)
        {
            $this->_lasterror = $r->error;
        }
        return !$r->is_error;
    }

    public function getLastError()
    {
        return $this->_lasterror;
    }

    public function canHandle($url)
    {
        return preg_match('|^.*://.*$|', $url);
    }

    public function patchFSHelper()
    {
        MagentoDirHandlerFactory::registerHandler($this);
    }
}