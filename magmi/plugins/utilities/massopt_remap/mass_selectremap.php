<?php
require_once ("magmi_csvreader.php");

class MassOptionRemapper extends Magmi_UtilityPlugin
{
    protected $_csvreader;
    protected $_attrinfos;
    protected $_storeids;

    public function getPluginInfo()
    {
        return array("name"=>"Mass Select Option Remapper","author"=>"Dweeves","version"=>"1.0.0");
    }

    public function getStoreId($sc)
    {
        if (!isset($this->_storeids[$sc]))
        {
            $cs = $this->tablename("core_store");
            $sid = $this->selectOne("SELECT store_id FROM $cs WHERE code=?", array($sc), "store_id");
            $this->_storeids[$sc] = $sid;
        }
        return $this->_storeids[$sc];
    }

    public function getOptAttributeInfos($attrcode)
    {
        if (!isset($this->_attrinfos[$attrcode]))
        {
            $ea = $this->tablename("eav_attribute");
            $sql = "SELECT * FROM $ea WHERE attribute_code=? AND entity_type_id=4 AND frontend_input IN ('select')";
            $attrinfos = $this->selectAll($sql, $attrcode);
            if (count($attrinfos) == 0)
            {
                
                $attrinfos = array();
            }
            else
            {
                $attrinfos = $attrinfos[0];
            }
            $this->_attrinfos[$attrcode] = $attrinfos;
        }
        
        return $this->_attrinfos[$attrcode];
    }

    public function remapAttrVal($attid, $from, $to, $cs = true)
    {
        $cpei = $this->tablename("catalog_product_entity_int");
        $eao = $this->tablename("eav_attribute_option");
        $eaov = $this->tablename("eav_attribute_option_value");
        if ($cs)
        {
            $csmode = "COLLATE utf8_bin";
        }
        if (!preg_match("/re::(.*)/", $from, $matches))
        {
            $where = "(SELECT eao.option_id FROM 
			$eao as eao 
			JOIN $eaov as eaov ON eaov.option_id=eao.option_id
			WHERE eao.attribute_id=? and eaov.value REGEXP ? $csmode)";
            $from = $matches[1];
        }
        else
        {
            $where = "(SELECT eao.option_id FROM 
			$eao as eao 
			JOIN $eaov as eaov ON eaov.option_id=eao.option_id
			WHERE eao.attribute_id=? and eaov.value=? $csmode)";
        }
        $sql = "UPDATE $cpei SET value=(SELECT eao.option_id 
			FROM $eao as eao 
			JOIN $eaov as eaov ON eaov.option_id=eao.option_id
			WHERE eao.attribute_id=? and eaov.value=? COLLATE utf8_bin)
			WHERE value IN $where";
        $this->update($sql, array($attid,$to,$attid,$from));
    }

    public function runUtility()
    {
        $params = $this->getPluginParams($this->_params);
        $this->persistParams($params);
        $attcode = trim($this->getParam("SREMAP:attrcode"));
        $attinfos = $this->getOptAttributeInfos($attcode);
        if (count($attinfos) == 0)
        {
            $this->log("$attcode is not of type select", "error");
            return false;
        }
        $this->_csvreader = new Magmi_CSVReader();
        $this->_csvreader->bind($this);
        $this->_csvreader->initialize();
        $this->_csvreader->checkCSV();
        $this->_csvreader->openCSV();
        $colnames = $this->_csvreader->getColumnNames();
        if (count(array_diff($colnames, array("src_value","dest_value"))) > 0)
        {
            $this->log("invalid csv : column names must be src_value & dest_value");
            return false;
        }
        
        while ($item = $this->_csvreader->getNextRecord())
        {
            
            $this->remapAttrVal($attinfos["attribute_id"], $item["src_value"], $item["dest_value"]);
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
        if (!isabspath($scandir))
        {
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
            'CSV:allowtrunc','SREMAP:attrcode');
    }

    public function getShortDescription()
    {
        return "This Utility performs mass replacement attribute values with another ones that already exist";
    }
}