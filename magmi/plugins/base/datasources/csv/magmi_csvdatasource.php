<?php
require_once ("magmi_csvreader.php");
require_once ("fshelper.php");

class Magmi_CSVDataSource extends Magmi_Datasource
{
    protected $_csvreader;

    public function initialize($params)
    {
        $this->_csvreader = new Magmi_CSVReader();
        $this->_csvreader->bind($this);
        $this->_csvreader->initialize();
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

    public function getPluginParams($params)
    {
        $pp = array();
        foreach ($params as $k => $v)
        {
            if (preg_match("/^CSV:.*$/", $k))
            {
                $pp[$k] = $v;
            }
        }
        return $pp;
    }

    public function getPluginInfo()
    {
        return array("name"=>"CSV Datasource","author"=>"Dweeves","version"=>"1.3.1");
    }

    public function getRecordsCount()
    {
        return $this->_csvreader->getLinesCount();
    }

    public function getAttributeList()
    {}

    public function getRemoteFile($url)
    {
        $fg = RemoteFileGetterFactory::getFGInstance();
        if ($this->getParam("CSV:remoteauth", false) == true)
        {
            $user = $this->getParam("CSV:remoteuser");
            $pass = $this->getParam("CSV:remotepass");
            $fg->setCredentials($user, $pass);
        }
        $cookies = $this->getParam("CSV:remotecookie");
        if ($cookies)
        {
            $fg->setCookie($cookies);
        }
        
        $this->log("Fetching CSV: $url", "startup");
        // output filename (current dir+remote filename)
        $csvdldir = dirname(__FILE__) . "/downloads";
        if (!file_exists($csvdldir))
        {
            @mkdir($csvdldir);
            @chmod($csvdldir, Magmi_Config::getInstance()->getDirMask());
        }
        
        $outname = $csvdldir . "/" . basename($url);
        $ext = substr(strrchr($outname, '.'), 1);
        if ($ext != "txt" && $ext != "csv")
        {
            $outname = $outname . ".csv";
        }
        // open file for writing
        if (file_exists($outname))
        {
            if ($this->getParam("CSV:forcedl", false) == true)
            {
                unlink($outname);
            }
            else
            {
                return $outname;
            }
        }
        $fg->copyRemoteFile($url, $outname);
        
        // return the csv filename
        return $outname;
    }

    public function beforeImport()
    {
        if ($this->getParam("CSV:importmode", "local") == "remote")
        {
            $url = $this->getParam("CSV:remoteurl", "");
            $outname = $this->getRemoteFile($url);
            $this->setParam("CSV:filename", $outname);
            $this->_csvreader->initialize();
        }
        return $this->_csvreader->checkCSV();
    }

    public function afterImport()
    {}

    public function startImport()
    {
        $this->_csvreader->openCSV();
    }

    public function getColumnNames($prescan = false)
    {
        return $this->_csvreader->getColumnNames($prescan);
    }

    public function endImport()
    {
        $this->_csvreader->closeCSV();
    }

    public function getNextRecord()
    {
        return $this->_csvreader->getNextRecord();
    }
}