<?php

class ImportLimiter extends Magmi_ItemProcessor
{
    protected $_recranges;
    protected $_rmax = -1;
    protected $_filters;
    protected $_col_filter = NULL;

    public function getPluginInfo()
    {
        return array("name"=>"Magmi Import Limiter","author"=>"Dweeves","version"=>"0.0.6",
            "url"=>$this->pluginDocUrl("Magmi_Import_Limiter"));
    }

    public function filtermatch($item, $fltdef)
    {
        $negate = 0;
        $field = $fltdef[0];
        $match = false;
        if ($field[0] == "!")
        {
            $field = substr($field, 1);
            $negate = 1;
        }
        $re = $fltdef[1];
        if (in_array($field, array_keys($item)))
        {
            $v = $item[$field];
            $match = preg_match("|$re|", $v);
            if ($negate)
            {
                $match = !$match;
            }
            if ($match)
            {
                $this->log("skipping sku {$item['sku']} => Filter '$field::$re'", "info");
            }
        }
        return $match;
    }

    public function processItemBeforeId(&$item, $params = null)
    {
        $crow = $this->getCurrentRow();
        $ok = (count($this->_recranges) == 0);
        
        if (!$ok)
        {
            if ($this->_rmax > -1 && $crow == $this->_rmax)
            {
                $this->setLastItem($item);
            }
            foreach ($this->_recranges as $rr)
            {
                $ok = ($crow >= $rr[0] && ($crow <= $rr[1] || $rr[1] == -1));
                if ($ok)
                {
                    break;
                }
            }
        }
        
        if ($ok)
        {
            foreach ($this->_filters as $fltdef)
            {
                // negative filters
                $ok = $ok && (!$this->filtermatch($item, $fltdef));
                if (!$ok)
                {
                    break;
                }
            }
        }
        else
        {
            $this->log("Filtered row $crow not in range " . $this->getParam("LIMITER:ranges", ""));
        }
        
        return $ok;
    }

    public function parseFilters($fltstr)
    {
        $this->_filters = array();
        if ($fltstr == "")
        {
            return;
        }
        $fltlist = explode(";;", $fltstr);
        foreach ($fltlist as $fltdef)
        {
            $fltinf = explode("::", $fltdef);
            $this->_filters[] = $fltinf;
        }
    }

    public function parseRanges($rangestr)
    {
        $this->_recranges = array();
        if ($rangestr == "")
        {
            return;
        }
        $rangelist = explode(",", $rangestr);
        foreach ($rangelist as $rdef)
        {
            $rlist = explode("-", $rdef);
            if ($rlist[0] == "")
            {
                $rlist[0] = -1;
            }
            else
            {
                $rmin = $rlist[0];
            }
            if (count($rlist) > 1)
            {
                if ($rlist[1] == "")
                {
                    $rlist[1] = -1;
                }
                else
                {
                    $rmax = $rlist[1];
                    if ($rmax > $this->_rmax && $this->_rmax != -1)
                    {
                        $this->_rmax = $rmax;
                    }
                }
            }
            else
            {
                $rmax = $rmin;
            }
            $this->_recranges[] = array($rmin,$rmax);
        }
    }

    public function processColumnList(&$cols, $params = null)
    {
        if (count($this->_col_filter) > 0)
        {
            $this->log("limiting columns to :" . implode(",", $this->_col_filter), "startup");
            $cols = $this->_col_filter;
        }
    }

    public function initialize($params)
    {
        $this->parseRanges($this->getParam("LIMITER:ranges", ""));
        $this->parseFilters($this->getParam("LIMITER:filters", ""));
        $this->_col_filter = explode(",", $this->getParam("LIMITER:col_filter"));
        return true;
    }

    public function getPluginParamNames()
    {
        return array('LIMITER:ranges','LIMITER:filters','LIMITER:col_filter');
    }

    static public function getCategory()
    {
        return "Input Data Preprocessing";
    }
}