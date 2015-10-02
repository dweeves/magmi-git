<?php
require_once("magmi_engine.php");

class Magmi_DSEngine extends Magmi_Engine
{
    public function engineInit($params)
    {
    }

    public function engineRun($params)
    {
    }
}

class Magmi_MagentoDatasource extends Magmi_Datasource
{
    public function initialize($params)
    {
        $this->engine = new Magmi_DSEngine();
        $this->extractSQL = $this->buildSQL();
    }

    public function buildSQL()
    {
    }

    public function getPluginInfo()
    {
        return array("name"=>"Magento Products Datasource","author"=>"Dweeves","version"=>"1.0.0");
    }

    public function getPluginParamNames()
    {
        return array("MAGDS:fields");
    }

    public function startImport()
    {
    }

    public function getRecordsCount()
    {
        $sql = null;
        // optimized count query
        $sql = "SELECT COUNT(*) as cnt FROM (" . str_replace("\n", " ", $this->extractsql) . ") as t1";
    }

    public function getColumnNames($prescan = false)
    {
        $s = $this->dbh->select($this->extractsql);
        $test = $s->fetch();
        $s->closeCursor();
        unset($s);
        $cl = array_keys($test);
        return $cl;
    }

    public function getNextRecord()
    {
        if (!isset($this->stmt)) {
            $this->stmt = $this->dbh->select($this->extractsql);
        }
        $data = $this->stmt->fetch();
        if (!$data) {
            return false;
        }
        return $data;
    }

    public function endImport()
    {
    }

    public function afterImport()
    {
    }
}
