<?php

class CustomSQLUtility extends Magmi_UtilityPlugin
{
    protected $_magdbh;

    public function getPluginInfo()
    {
        return array("name"=>"Custom Magento SQL Utility","author"=>"Dweeves","version"=>"1.0.3");
    }

    public function getShortDescription()
    {
        return "This Utility enables to perform custom parameterized sql scripts on magento DB";
    }

    public function getRequestFileList()
    {
        $files = glob(dirname(__file__) . "/prequests/*.sql");
        return $files;
    }

    public function getRequestParameters($file, $noprefix = false)
    {
        $params = array();
        $content = file_get_contents($file);
        $hasp = preg_match_all('|\[\[(.*?)\]\]|msi', $content, $matches);
        if ($hasp) {
            $params = $matches[1];
        }
        $outparams = array();
        foreach ($params as $param) {
            $pinfo = explode("/", $param);
            $plabel = (count($pinfo) == 1) ? $pinfo[0] : $pinfo[1];
            $pdefault = (count($pinfo) > 2 ? $pinfo[2] : "");
            $pname = $pinfo[0];
            $epar = explode(":", $pname);
            $addit = $noprefix && count($epar) == 1 || !$noprefix;
            if ($addit) {
                $outparams[$plabel] = array("name"=>$pname,"default"=>$pdefault);
            }
        }
        return $outparams;
    }

    public function fillPrefixedParameters($stmt, &$params)
    {
        $pparams = array();
        $hasnps = preg_match_all('|\[\[(\w+?)/.*?\]\]|msi', $stmt, $matches);
        if ($hasnps) {
            $namedparams = $matches[1];
            foreach ($params as $k => $v) {
                if (!in_array($k, $namedparams)) {
                    unset($params[$k]);
                }
            }
        }

        $hasp = preg_match_all('|\[\[(tn:.*?)\]\]|msi', $stmt, $matches);
        if ($hasp) {
            $pparams = $matches[1];
        }
        foreach ($pparams as $pparam) {
            $info = explode(":", $pparam);
            switch ($info[0]) {
                case "tn":
                    $params[$pparam] = $this->tablename($info[1]);
            }
        }
    }

    public function getRequestInfo($file)
    {
        if (file_exists("$file.desc")) {
            return file_get_contents("$file.desc");
        } else {
            return basename($file);
        }
    }

    public function getPluginParams($params)
    {
        $pp = array();
        foreach ($params as $k => $v) {
            if (preg_match("/^UTCSQL:.*$/", $k)) {
                $pp[$k] = $v;
            }
        }
        return $pp;
    }

    public function runUtility()
    {
        $this->connectToMagento();
        $params = $this->getPluginParams($this->_params);
        $this->persistParams($params);
        $rqfile = $params["UTCSQL:queryfile"];
        unset($params["UTCSQL:queryfile"]);
        if (!isabspath($rqfile)) {
            $rqfile = dirname(__FILE__) . "/prequests/$rqfile";
        }
        $sql = file_get_contents($rqfile);
        $rparams = array();
        foreach ($params as $pname => $pval) {
            $rpname = substr($pname, strlen("UTCSQL:"));
            $rparams[$rpname] = $pval;
        }
        $this->fillPrefixedParameters($sql, $rparams);
        $results = $this->multipleParamRequests($sql, $rparams, true);
        foreach ($results as $rq => $res) {
            $cres = count($res);
            if ($cres == 0) {
                $this->log("No records found", "info");
            } else {
                for ($i = 0; $i < $cres; $i++) {
                    $str = "";
                    foreach ($res[$i] as $k => $v) {
                        $str .= "$k=$v;";
                    }
                    $this->log($str, "info");
                }
            }
        }
        $this->disconnectFromMagento();
    }
}
