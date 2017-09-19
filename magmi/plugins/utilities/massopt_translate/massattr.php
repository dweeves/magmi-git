<?php
require_once("magmi_csvreader.php");

class MassOptionAttributeValImporter extends Magmi_UtilityPlugin
{
    protected $_csvreader;
    protected $_attrinfos;
    protected $_storeids;

    public function getPluginInfo()
    {
        return array("name"=>"Mass Select/Multiselect Attribute value translater","author"=>"Dweeves",
            "version"=>"1.0.1");
    }

    public function getStoreId($sc)
    {
        if (!isset($this->_storeids[$sc])) {
            $cs = $this->tablename("core_store");
            $sid = $this->selectOne("SELECT store_id FROM $cs WHERE code=?", array($sc), "store_id");
            $this->_storeids[$sc] = $sid;
        }
        return $this->_storeids[$sc];
    }

    public function getOptAttributeInfos($attrcode)
    {
        if (!isset($this->_attrinfos[$attrcode])) {
            $sql = "SELECT * FROM eav_attribute WHERE attribute_code=? AND entity_type_id=4 AND frontend_input IN ('select','multiselect')";
            $attrinfos = $this->selectAll($sql, $attrcode);
            if (count($attrinfos) == 0) {
                $attrinfos = array();
            } else {
                $attrinfos = $attrinfos[0];
            }
            $this->_attrinfos[$attrcode] = $attrinfos;
        }

        return $this->_attrinfos[$attrcode];
    }

    public function initOptIdSIdIndex()
    {
        $eaov = $this->tablename("eav_attribute_option_value");
        $sql = "CREATE UNIQUE INDEX 'MAGMI_OPTID_STOREID_IDX' ON $eaov (option_id,store_id)";
        try {
            $this->exec_stmt($sql);
            $this->log("Created Unique Store id/Option id index", "info");
        } catch (Exception $e) {
            $this->log("Unique Store id/Option id index already exists", "info");
        }
    }

    public function runUtility()
    {
        $params = $this->getPluginParams($this->_params);
        $this->persistParams($params);
        $this->_csvreader = new Magmi_CSVReader();
        $this->_csvreader->bind($this);
        $this->initOptIdSIdIndex();
        $this->_csvreader->initialize();
        $this->_csvreader->checkCSV();
        $this->_csvreader->openCSV();
        $this->_csvreader->getColumnNames();
        while ($item = $this->_csvreader->getNextRecord()) {
            $attinfos = $this->getOptAttributeInfos(trim($item["attribute_code"]));

            if (count($attinfos) > 0) {
                $attid = $attinfos["attribute_id"];
                $storevals = array();
                foreach ($item as $k => $v) {
                    if (preg_match("|store:(.*)|", $k, $matches)) {
                        $storevals[$matches[1]] = $v;
                    }
                }
                if (!isset($item["store:admin"])) {
                    $svk = array_keys($storevals);
                    $item["store:admin"] = $storevals[$svk[0]];
                }
                $this->setAttrOptionVal($attid, $item["store:admin"], $storevals, $item["position"]);
            } else {
                $this->log("no select/multiselect attribute found with code :" . $item["attribute_code"], "warning");
            }
        }
        $this->_csvreader->closeCSV();
        $this->_csvreader->unbind($this);
    }

    public function getAbsPath($path)
    {
        return abspath($path, $this->getScanDir());
    }

    public function getScanDir($resolve = true)
    {
        $scandir = $this->getParam("CSV:basedir", "var/import");
        if (!isabspath($scandir)) {
            $scandir = abspath($scandir, Magmi_Config::getInstance()->getMagentoDir(), $resolve);
        }
        return $scandir;
    }

    public function getCSVList()
    {
        $scandir = $this->getScanDir();
        $files = glob("$scandir/*.csv");
        return $files;
    }

    public function getPluginParamNames()
    {
        return array('CSV:filename','CSV:enclosure','CSV:separator','CSV:basedir','CSV:headerline','CSV:noheader',
            'CSV:allowtrunc');
    }

    public function createOption($attid, $pos = 0)
    {
        $t = $this->tablename('eav_attribute_option');
        $optid = $this->insert("INSERT INTO $t (attribute_id,sort_order) VALUES (?,?)", array($attid, $pos));
        return $optid;
    }

    public function updateOptionPos($optid, $pos = 0)
    {
        $t = $this->tablename('eav_attribute_option');
        $this->update("UPDATE $t SET sort_order=? WHERE option_id=?", array($pos, $optid));
    }

    public function setAttrOptionVal($attid, $valadm, $storevals, $pos = 0)
    {
        $eao = $this->tablename("eav_attribute_option");
        $eaov = $this->tablename("eav_attribute_option_value");
        $sql = "SELECT eaov.option_id FROM $eaov AS eaov JOIN $eao as eao ON eaov.option_id=eao.option_id AND eao.attribute_id=?
		WHERE eaov.store_id=0 AND eaov.value=?";
        $optid = $this->selectOne($sql, array($attid, $valadm), "option_id");
        $new = false;
        if (!isset($optid)) {
            $optid = $this->createOption($attid, $pos);
            $new = true;
        } else {
            $this->updateOptionPos($optid, $pos);
        }

        $values = array();
        $ins = array();

        $values = array_merge($values, array($optid, 0, $valadm));
        $ins[] = "(?,?,?)";

        foreach ($storevals as $store_code => $sval) {
            $store_id = $this->getStoreId($store_code);
            if (isset($store_id)) {
                $values = array_merge($values, array($optid, $store_id, $sval));
                $ins[] = "(?,?,?)";
            }
        }

        $valstr = $this->arr2values($values);
        $sql = "INSERT INTO $eaov (option_id,store_id,value)
		VALUES " . join(",", $ins) . " ON DUPLICATE KEY UPDATE value=VALUES(`value`)";
        $this->insert($sql, $values);
    }

    public function getShortDescription()
    {
        return "This Utility performs mass creation/translation of select/multiselect attribute values";
    }
}
