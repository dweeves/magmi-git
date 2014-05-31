<?php
require_once ("dbhelper.class.php");

class ExtDBHelper extends DBHelper
{

    public function initDBMysql($dbname, $host, $user, $pass)
    {
        $this->_db = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    }

    public function initDBPDOStr($user, $pass, $pdostr)
    {
        // fix by Mr Lei for UTF8 special chars
        $this->_db = new PDO("$pdostr", $user, $pass);
    }
}

class SQL_Datasource extends Magmi_Datasource
{
    public $dbh;
    public $stmt;
    public $extractsql;
    public $sqlfile;

    public function initialize($params)
    {
        $this->dbh = new ExtDBHelper();
        $cdbtype = $this->getParam("SQL:dbtype");
        $cdbusr = $this->getParam("SQL:dbuser");
        $cdbpass = $this->getParam("SQL:dbpass");
        if ($cdbtype == "other")
        {
            $cdbpdostr = $this->getParam("SQL:pdostr", "");
            
            $this->dbh->initDBPDOStr($cdbusr, $cdbpass, $cdbpdostr);
        }
        else
        {
            $cdbname = $this->getParam("SQL:dbname");
            $cdbhost = $this->getParam("SQL:dbhost");
            $extra = $this->getParam("SQL:dbextra");
            $this->dbh->initDbMysql($cdbname, $cdbhost, $cdbusr, $cdbpass);
        }
        // handle extra initial commands
        if (isset($extra) && $extra != "")
        {
            foreach (explode(";\n", $extra) as $st)
            {
                if ($st != "")
                {
                    $this->dbh->exec_stmt($st);
                }
            }
        }
        $this->stmt = null;
        $this->sqlfile = $this->getParam("SQL:queryfile");
        $this->extractsql = file_get_contents($this->sqlfile);
    }

    public function getPluginInfo()
    {
        return array("name"=>"Generic SQL Datasource","author"=>"Dweeves","version"=>"1.0.3");
    }

    public function getPluginParamNames()
    {
        return array("SQL:dbtype","SQL:dbname","SQL:dbhost","SQL:dbuser","SQL:dbpass","SQL:dbextra","SQL:queryfile",
            "SQL:pdostr");
    }

    public function startImport()
    {}

    public function getSQLFileList()
    {
        $files = glob(dirname(__file__) . "/requests/*.sql");
        return $files;
    }

    public function getRecordsCount()
    {
        $sql = null;
        // optimized count query
        if (file_exists($this->sqlfile . ".count"))
        {
            $sql = file_get_contents($this->sqlfile . ".count");
        }
        if (!isset($sql))
        {
            $sql = "SELECT COUNT(*) as cnt FROM (" . str_replace("\n", " ", $this->extractsql) . ") as t1";
        }
        $cnt = $this->dbh->selectone($sql, null, "cnt");
        
        return $cnt;
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
        if (!isset($this->stmt))
        {
            $this->stmt = $this->dbh->select($this->extractsql);
        }
        $data = $this->stmt->fetch();
        if (!$data)
        {
            return false;
        }
        return $data;
    }

    public function endImport()
    {}

    public function afterImport()
    {}
}