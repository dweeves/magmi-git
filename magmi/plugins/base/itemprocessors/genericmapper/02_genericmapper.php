<?php

/**
 * Class SampleItemProcessor
 * @author dweeves
 *
 * This class is a sample for item processing
 */
class GenericMapperProcessor extends Magmi_ItemProcessor
{
    protected $_mapping;

    public function getPluginInfo()
    {
        return array("name"=>"Generic mapper","author"=>"Dweeves","version"=>"0.0.6a",
            "url"=>$this->pluginDocUrl("Generic_mapper"));
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
    public function processItemBeforeId(&$item, $params = null)
    {
        foreach (array_keys($item) as $k) {
            $mapped = false;
            if (isset($this->_mapping["$k.csv"])) {
                $mpd = $this->_mapping["$k.csv"]["DIRECT"];
                if (isset($mpd[$item[$k]])) {
                    $item[$k] = $mpd[$item[$k]];
                    $mapped = true;
                } else {
                    $mpr = $this->_mapping["$k.csv"]["RE"];
                    foreach ($mpr as $re => $value) {
                        if (preg_match("|$re|msi", $item[$k])) {
                            $item[$k] = preg_replace("|$re|", $value, $item[$k]);
                            $mapped = true;
                            break;
                        }
                    }
                }
            }
            // f not found,try common mappings
            if (!$mapped) {
                $mpd = $this->_mapping["__common__.csv"]["DIRECT"];
                if (isset($mpd[$item[$k]])) {
                    $item[$k] = $mpd[$item[$k]];
                }
            }
        }
        return true;
    }

    public function initialize($params)
    {
        $this->_mapping = array();

        $dlist = glob(dirname(__file__) . "/mappings/default/*.csv");
        if ($dlist == false) {
            $dlist = array();
            $this->log("No default mapping found", "warning");
        }
        $slist = glob(dirname(__file__) . "/mappings/*.csv");
        if ($slist == false) {
            $slist = array();
            $this->log("No custom mapping found", "startup");
        }
        $flist = array_merge($dlist, $slist);
        foreach ($flist as $fname) {
            $idx = basename($fname);
            if (!isset($this->_mapping[$idx])) {
                $this->_mapping[$idx] = array("DIRECT"=>array(),"RE"=>array());
            }
            $mf = fopen("$fname", "r");
            while (($data = fgetcsv($mf, 1000, ",")) !== false) {
                if (substr($data[0], 0, 4) == "_RE:") {
                    $target = "RE";
                    $key = substr($data[0], 4);
                } else {
                    $target = "DIRECT";
                    $key = $data[0];
                }
                $this->_mapping[$idx][$target][$key] = $data[1];
            }
        }
    }

    public static function getCategory()
    {
        return "Input Data Preprocessing";
    }
}
