<?php

/**
 * Class SampleItemProcessor
 * @author dweeves
 *
 * This class is a sample for item processing
*/
class ValueTrimItemProcessor extends Magmi_ItemProcessor
{
    protected $_totrim = array();
    protected $_scanned = false;

    public function getPluginInfo()
    {
        return array("name"=>"Value Trimmer for select/multiselect","author"=>"Dweeves","version"=>"0.0.3",
            "url"=>$this->pluginDocUrl("Value_Trimmer_for_select/multiselect"));
    }

    /**
     * you can add/remove columns for the item passed since it is passed by reference
     *
     * @param Magmi_Engine $mmi
     *            : reference to magmi engine instance (convenient to perform database operations)
     * @param unknown_type $item
     *            : modifiable reference to item before import
     *            the $item is a key/value array with column names as keys and values as read from csv file.
     * @return bool :
     *         true if you want the item to be imported after your custom processing
     *         false if you want to skip item import after your processing
     */
    public function getTrimmableCols($item)
    {
        if (!$this->_scanned) {
            foreach (array_keys($item) as $col) {
                $ainfo = $this->getAttrInfo($col);
                if (count($ainfo) > 0) {
                    if ($ainfo["frontend_input"] == "select" || $ainfo["frontend_input"] == "multiselect") {
                        $this->_totrim[$col] = $ainfo["frontend_input"];
                    }
                }
            }
            $this->_scanned = true;
        }
        return $this->_totrim;
    }

    public function processItemBeforeId(&$item, $params = null)
    {
        // get list of trimmable columns
        $tc = $this->getTrimmableCols($item);
        foreach ($tc as $col => $mode) {
            // for select, just trim value
            if ($mode == "select") {
                $item[$col] = trim($item[$col]);
            } else {
                // for multiselect, recompose trimmed value list

                $sep = Magmi_Config::getInstance()->get("GLOBAL", "mutiselect_sep", ",");
                $vt = explode($sep, $item[$col]);
                foreach ($vt as &$v) {
                    $v = trim($v);
                }
                $item[$col] = implode($sep, $vt);
                unset($vt);
            }
        }
        return true;
    }

    public function initialize($params)
    {
        $this->_scanned = false;
        $this->_totrim = array();
        return true;
    }

    public static function getCategory()
    {
        return "Input Data Preprocessing";
    }
}
