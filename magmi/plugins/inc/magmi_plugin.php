<?php
require_once ("magmi_config.php");
require_once ("magmi_mixin.php");

class Magmi_PluginConfig extends ProfileBasedConfig
{
    protected $_prefix;
    protected $_conffile;

    public function __construct($pname, $profile = null)
    {
        $this->_prefix = $pname;
        parent::__construct("$this->_prefix.conf", $profile);
    }

    public function getConfDir()
    {
        return dirname($this->_confname);
    }

    public function load($name = null)
    {
        $cname = ($name == null ? $this->_confname : $name);
        if (file_exists($cname))
        {
            parent::load($cname);
        }
    }

    public function getIniStruct($arr)
    {
        $conf = array();
        foreach ($arr as $k => $v)
        {
            $k = $this->_prefix . ":" . $k;
            list($section,$value) = explode(":", $k, 2);
            if (!isset($conf[$section]))
            {
                $conf[$section] = array();
            }
            $conf[$section][$value] = $v;
        }
        return $conf;
    }

    public function getConfig()
    {
        return parent::getsection($this->_prefix);
    }
}

class Magmi_PluginOptionsPanel
{
    private $_plugin;
    private $_defaulthtml = "";
    private $_file = null;

    public function __construct($pinst, $file = null)
    {
        $this->_plugin = $pinst;
        $this->_file = ($file == null ? "options_panel.php" : $file);
        $this->initDefaultHtml();
    }

    public function getFile()
    {
        return $this->_file;
    }

    public final function initDefaultHtml()
    {
        $panelfile = dirname(__FILE__) . "/magmi_default_options_panel.php";
        ob_start();
        require ($panelfile);
        $this->_defaulthtml = ob_get_contents();
        ob_end_clean();
    }

    public function getHtml()
    {
        $plugin = $this->_plugin;
        $pdir = Magmi_PluginHelper::getInstance()->getPluginDir($this->_plugin);
        $panelfile = "$pdir/" . $this->getFile();
        $content = "";
        if (!file_exists($panelfile))
        {
            $content = $this->_defaulthtml;
        }
        else
        {
            ob_start();
            require ($panelfile);
            $content = ob_get_contents();
            ob_end_clean();
        }
        return $content;
    }

    public function __call($data, $arg)
    {
        return call_user_func_array(array($this->_plugin,$data), $arg);
    }
}

abstract class Magmi_Plugin extends Magmi_Mixin
{
    protected $_class;
    protected $_plugintype;
    protected $_plugindir;
    protected $_config;
    protected $_magmiconfig;
    protected $_pluginmeta;

    public function __construct()
    {}

    public function pluginDocUrl($urlk)
    {
        return "http://wiki.magmi.org/index.php?title=" . $urlk;
    }

    public function getParam($pname, $default = null)
    {
        return (isset($this->_params[$pname]) && $this->_params[$pname] != "") ? $this->_params[$pname] : $default;
    }

    public function setParam($pname, $value)
    {
        $this->_params[$pname] = $value;
    }

    public function fixListParam($pvalue)
    {
        $iarr = explode(",", $pvalue);
        $oarr = array();
        foreach ($iarr as $v)
        {
            if ($v != "")
            {
                $oarr[] = $v;
            }
        }
        $val = implode(",", $oarr);
        unset($iarr);
        unset($oarr);
        return $val;
    }

    public function getPluginParamNames()
    {
        return array();
    }

    public function getPluginInfo()
    {
        return array("name"=>$this->getPluginName(),"version"=>$this->getPluginVersion(),
            "author"=>$this->getPluginAuthor(),"url"=>$this->getPluginUrl());
    }

    public function getPluginUrl()
    {
        return null;
    }

    public function getPluginVersion()
    {
        return null;
    }

    public function getPluginName()
    {
        return null;
    }

    public function getPluginAuthor()
    {
        return null;
    }

    public function log($data, $type = 'std', $useprefix = true)
    {
        $pinf = $this->getPluginInfo();
        if ($useprefix)
        {
            $data = "{$pinf["name"]} v{$pinf["version"]} - " . $data;
        }
        $this->_caller_log($data, "plugin;$this->_class;$type");
    }

    public function pluginHello()
    {
        $info = $this->getPluginInfo();
        $hello = array(!isset($info["name"]) ? "" : $info["name"]);
        $hello[] = !isset($info["version"]) ? "" : $info["version"];
        $hello[] = !isset($info["author"]) ? "" : $info["author"];
        $hello[] = !isset($info["url"]) ? "" : $info["url"];
        $hellostr = implode("-", $hello);
        $base = get_parent_class($this);
        $this->log("$hellostr ", "pluginhello", false);
    }

    public function initialize($params)
    {}

    public function getConfig()
    {
        return $this->_config;
    }

    public function getMagmiConfig()
    {
        return $this->_magmiconfig;
    }

    public final function pluginInit($mmi, $meta, $params = null, $doinit = true, $profile = null)
    {
        $this->bind($mmi);
        $this->_pluginmeta = $meta;
        $this->_class = get_class($this);
        $this->_config = new Magmi_PluginConfig(get_class($this), $profile);
        $this->_config->load();
        $this->_magmiconfig = Magmi_Config::getInstance();
        
        $this->_params = ($params != null ? array_merge($this->_config->getConfig(), $params) : $this->_config->getConfig());
        
        if (isset($mmi))
        {
            $this->pluginHello();
        }
        
        if ($doinit)
        {
            $this->initialize($this->_params);
        }
    }

    public function getPluginParamsNoCurrent($params)
    {
        $arr = array();
        $paramkeys = $this->getPluginParamNames();
        foreach ($paramkeys as $pk)
        {
            if (isset($params[$pk]))
            {
                $arr[$pk] = $params[$pk];
            }
            else
            {
                $arr[$pk] = 0;
            }
        }
        return $arr;
    }

    public function getPluginParams($params)
    {
        $arr = array();
        $paramkeys = $this->getPluginParamNames();
        foreach ($paramkeys as $pk)
        {
            if (isset($params[$pk]))
            {
                $arr[$pk] = $params[$pk];
            }
            else
            {
                if (isset($this->_params[$pk]))
                {
                    $arr[$pk] = $this->_params[$pk];
                }
            }
        }
        return $arr;
    }

    public function persistParams($plist)
    {
        if (count($plist) > 0)
        {
            $this->_config->setPropsFromFlatArray($plist);
            return $this->_config->save();
        }
        return true;
    }

    public function getOptionsPanel($file = null)
    {
        return new Magmi_PluginOptionsPanel($this, $file);
    }

    public function getShortDescription()
    {
        $panel = $this->getOptionsPanel()->getHtml();
        $info = null;
        if (preg_match('|<div class="plugin_description">(.*?)</div>|smi', $panel, $match))
        {
            
            $info = $match[1];
            $delims = array(".",":");
            foreach ($delims as $delim)
            {
                $p = strpos($info, $delim);
                if ($p !== false)
                {
                    $info = substr($info, 0, $p);
                    break;
                }
            }
        }
        return $info;
    }

    static public function getCategory()
    {
        return "common";
    }

    public function getPluginDir()
    {
        return $this->_pluginmeta["dir"];
    }

    public function getPluginMeta()
    {
        return $this->_pluginmeta;
    }

    public function getPluginClass()
    {
        return $this->_class;
    }

    public function isRunnable()
    {
        return array(true,"");
    }
}