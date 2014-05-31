<?php

/**
 * Class SampleItemProcessor
 * @author dweeves
 *
 * This class is a sample for item processing   
*/
class ValueReplacerItemProcessor extends Magmi_ItemProcessor
{
    protected $_rvals = array();
    protected $_before = array("sku","attribute_set","type");

    public function getPluginInfo()
    {
        return array("name"=>"Value Replacer","author"=>"Dweeves","version"=>"0.0.8a",
            "url"=>$this->pluginDocUrl("Value_Replacer"));
    }

    public function processItemBeforeId(&$item, $params = null)
    {
        $cbefore = count($this->_before);
        
        // only check for "before" compatible fields
        
        for ($i = 0; $i < $cbefore; $i++)
        {
            $attname = $this->_before[$i];
            if (isset($this->_rvals[$attname]))
            {
                $item[$attname] = $this->parseCalculatedValue($this->_rvals[$attname], $item, $params);
            }
        }
        return true;
    }

    public function processItemAfterId(&$item, $params = null)
    {
        foreach ($this->_rvals as $attname => $pvalue)
        {
            // do not reparse "before" fields
            if (!in_array($attname, $this->_before))
            {
                $item[$attname] = $this->parseCalculatedValue($pvalue, $item, $params);
            }
        }
        return true;
    }

    public function initHelpers()
    {
        $helperdir = dirname(__FILE__) . "/helper";
        $files = glob($helperdir . "/*.php");
        foreach ($files as $f)
        {
            require_once ($f);
        }
    }

    public function initialize($params)
    {
        foreach ($params as $k => $v)
        {
            if (preg_match_all("/^VREP:(.*)$/", $k, $m) && $k != "VREP:columnlist")
            {
                $colname = rawurldecode($m[1][0]);
                $this->_rvals[$colname] = $params[$k];
            }
        }
        $this->initHelpers();
    }
    
    // auto add columns if not set
    public function processColumnList(&$cols)
    {
        $base_cols = $cols;
        $cols = array_unique(array_merge($cols, explode(",", $this->getParam("VREP:columnlist"))));
        $newcols = array_diff($cols, $base_cols);
        if (count($newcols) > 0)
        {
            $this->log("Added columns : " . implode(",", $newcols), "startup");
        }
    }

    public function getPluginParams($params)
    {
        $pp = array();
        foreach ($params as $k => $v)
        {
            if (preg_match("/^VREP:.*$/", $k))
            {
                $pp[$k] = $v;
            }
        }
        return $pp;
    }

    static public function getCategory()
    {
        return "Input Data Preprocessing";
    }
}