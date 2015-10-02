<?php

class importlimiter extends Magmi_ItemProcessor
{
    protected $_recranges;
    protected $_rmax = -1;
    protected $_filters;
    protected $_col_filter = null;

    public function getPluginInfo()
    {
        return array("name"=>"Magmi Import Limiter","author"=>"Dweeves","version"=>"0.0.7",
            "url"=>$this->pluginDocUrl("Magmi_Import_Limiter"));
    }

    /**
     * @param $item item to match filter against
     * @param $fltdef filters
     * @return bool|int if item matches filter
     */
    public function filtermatch($item, $fltdef)
    {
        $negate = 0;
        $field = $fltdef[0];
        $match = false;
        if ($field[0] == "!") {
            $field = substr($field, 1);
            $negate = 1;
        }
        $re = $fltdef[1];
        if (in_array($field, array_keys($item))) {
            $v = $item[$field];
            $match = preg_match("|$re|", $v);
            if ($negate) {
                $match = !$match;
            }
            if ($match) {
                $this->log("skipping sku {$item['sku']} => Filter '$field::$re'", "info");
            }
        }
        return $match;
    }

    /**
     * Processing callback, before any database identification
     * @param $item item to check for import limit
     * @param null $params meta parameters
     * @return bool true : continue processing, false: skip item
     */
    public function processItemBeforeId(&$item, $params = null)
    {
        $ok=true;
        //filtering row
        if (count($this->_recranges)>0) {
            $crow = $this->getCurrentRow();
            // check if we are at the last wanted line by range list
            if ($this->_rmax > -1 && $crow == $this->_rmax) {
                $this->setLastItem($item);
            }
            //iterating on allowed row ranges
            foreach ($this->_recranges as $rr) {
                $ok = ($crow >= $rr[0] && ($crow <= $rr[1] || $rr[1] == -1));
                if ($ok) {
                    break;
                }
            }
            //if filtered, log it
            if (!$ok) {
                $this->log("Filtered row $crow not in range " . $this->getParam("LIMITER:ranges", ""), "info");
            }
        }

        //filtering based on values
        if ($ok && count($this->_filters)>0) {
            foreach ($this->_filters as $fltdef) {
                // negative filters
                $ok = $ok && (!$this->filtermatch($item, $fltdef));
                if (!$ok) {
                    break;
                }
            }
        }

        //filtering importable columns if not skipped by another.
        if (count($this->_col_filter)>0 && $ok) {
            $item=array_intersect_key($item, array_flip($this->_col_filter));
        }

        return $ok;
    }

    public function parseFilters($fltstr)
    {
        $this->_filters = array();
        if ($fltstr == "") {
            return;
        }
        $fltlist = explode(";;", $fltstr);
        foreach ($fltlist as $fltdef) {
            $fltinf = explode("::", $fltdef);
            $this->_filters[] = $fltinf;
        }
    }

    public function parseRanges($rangestr)
    {
        $this->_recranges = array();
        if ($rangestr == "") {
            return;
        }
        $rangelist = explode(",", $rangestr);
        foreach ($rangelist as $rdef) {
            $rlist = explode("-", $rdef);
            if ($rlist[0] == "") {
                $rlist[0] = -1;
            } else {
                $rmin = $rlist[0];
            }
            if (count($rlist) > 1) {
                if ($rlist[1] == "") {
                    $rlist[1] = -1;
                } else {
                    $rmax = $rlist[1];
                    if ($rmax > $this->_rmax && $this->_rmax != -1) {
                        $this->_rmax = $rmax;
                    }
                }
            } else {
                $rmax = $rmin;
            }
            $this->_recranges[] = array($rmin,$rmax);
        }
    }

    public function processColumnList(&$cols, $params = null)
    {
        if (count($this->_col_filter) > 0) {
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

    public static function getCategory()
    {
        return "Input Data Preprocessing";
    }
}
