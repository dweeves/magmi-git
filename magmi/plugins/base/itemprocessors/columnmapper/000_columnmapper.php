<?php

/**
 * Class SampleItemProcessor
 * @author dweeves
 *
 * This class is a sample for item processing
*/
class ColumnMappingItemProcessor extends Magmi_ItemProcessor
{
    protected $_dcols = array();

    public function getPluginInfo()
    {
        return array("name"=>"Column mapper","author"=>"Dweeves","version"=>"0.0.3b",
            "url"=>$this->pluginDocUrl("Column_mapper"));
    }

    /**
     * you can add/remove columns for the item passed since it is passed by reference
     *
     * @param Magmi_Engine $mmi
     *            : reference to magmi engine(convenient to perform database operations)
     * @param unknown_type $item
     *            : modifiable reference to item before import
     *            the $item is a key/value array with column names as keys and values as read from csv file.
     * @return bool :
     *         true if you want the item to be imported after your custom processing
     *         false if you want to skip item import after your processing
     */
    public function processColumnList(&$cols, $params = null)
    {
        $icols = $cols;
        $ocols = array();
        $scols = array();
        foreach ($icols as $cname) {
            if (isset($this->_dcols[$cname])) {
                $mlist = array_unique(explode(",", $this->_dcols[$cname]));
                $ncol = array_shift($mlist);
                $ocols[] = $ncol;
                if ($ncol != $cname) {
                    $this->log("Replacing Column $cname by $ncol", "startup");
                }
                if (count($mlist) > 0) {
                    $scols = array_merge($scols, $mlist);
                    $this->log("Replicating Column $cname to " . implode(",", $mlist), "startup");
                }
            } else {
                $ocols[] = $cname;
            }
        }
        $ocols = array_unique(array_merge($ocols, $scols));
        $cols = $ocols;
        return true;
    }

    public function processItemBeforeId(&$item, $params = null)
    {
        foreach ($this->_dcols as $oname => $mnames) {
            if (isset($item[$oname])) {
                $mapped = explode(",", $mnames);
                foreach ($mapped as $mname) {
                    $mnane = trim($mname);
                    $item[$mname] = $item[$oname];
                }
                if (!in_array($oname, $mapped)) {
                    unset($item[$oname]);
                }
            }
        }
        return true;
    }

    public function initialize($params)
    {
        foreach ($params as $k => $v) {
            if (preg_match_all("/^CMAP:(.*)$/", $k, $m) && $k != "CMAP:columnlist") {
                $colname = rawurldecode($m[1][0]);
                $this->_dcols[$colname] = $params[$k];
            }
        }
    }

    public function getPluginParams($params)
    {
        $pp = array();
        foreach ($params as $k => $v) {
            if (preg_match("/^CMAP:.*$/", $k)) {
                $pp[$k] = $v;
            }
        }
        return $pp;
    }

    public static function getCategory()
    {
        return "Input Data Preprocessing";
    }
}
