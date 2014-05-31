<?php
require_once (dirname(__FILE__) . "/magmi_remoteagent_proxy.php");
require_once (dirname(__FILE__) . "/magmi_remoteagent.php");

class Magmi_RemoteAgentPlugin extends Magmi_GeneralImportPlugin
{
    protected $_raproxy;
    protected $_active;

    public function __construct()
    {
        $_active = false;
    }

    public function getPluginInfo()
    {
        return array("name"=>"Remote Agent Plugin","author"=>"Dweeves","version"=>"0.0.1",
            "sponsorinfo"=>array("name"=>"Eydun Lamhauge","url"=>"http://www.admind.fo/"),
            "url"=>$this->pluginDocUrl("Remote_Agent"));
    }

    public function initialize($params)
    {
        $mbdir = Magmi_Config::getInstance()->get('MAGENTO', 'basedir');
        if (is_remote_path($mbdir))
        {
            $this->_raproxy = new Magmi_RemoteAgent_Proxy($mbdir, $this->getParam("MRAGENT:baseurl"));
        }
        if (isset($this->_raproxy))
        {
            $this->log("Remote agent activated for remote magento path : $mbdir", "startup");
        }
    }

    public function checkPluginVersion()
    {
        $pv = $this->_raproxy->getVersion();
        if ($pv == '0.0.0')
        {
            $this->log("Remote Agent Not found at " . $this->_raproxy->getRemoteAgentUrl(), "startup");
        }
        else
        {
            $this->log("Remote Agent v$pv found at " . $this->_raproxy->getRemoteAgentUrl(), "startup");
        }
        $cv = Magmi_RemoteAgent::getStaticVersion();
        if ($pv < $cv)
        {
            $this->log("Deploying latest v$cv");
            $ok = $this->deployPlugin(Magmi_Config::getInstance()->getMagentoDir());
            if ($ok)
            {
                $cpv = $this->_raproxy->getVersion();
                $this->log("Remote Agent v$cpv deployed at " . $this->_raproxy->getRemoteAgentUrl(), "startup");
            }
        }
        $this->_active = true;
    }

    public function deployPlugin($url)
    {
        $sep = (substr($url, -1) == "/" ? "" : "/");
        $ctx = stream_context_create(array('ftp'=>array('overwrite'=>true)));
        
        $ok = @copy(dirname(__FILE__) . "/magmi_remoteagent.php", $url . $sep . "magmi_remoteagent.php", $ctx);
        if ($ok == false)
        {
            $err = error_get_last();
            $this->log(
                "Cannot deploy Remote agent to $url (" . $err['message'] . "), remote file operation & indexing disabled", 
                "warning");
        }
        return $ok;
    }

    public function beforeImport()
    {
        $this->checkPluginVersion();
    }

    public function getPluginParamNames()
    {
        return array("MRAGENT:baseurl");
    }
}
?>