<?php

class ProgressParser
{
    protected $_simpleparsers = array();
    protected $_complexparsers = array();
    protected $_parsers = array();
    protected $_data;

    public function addParser($name, $parser, $ltype = null)
    {
        if (method_exists($parser, "logtypeIsHandled")) {
            $this->_complexparsers[] = $parser;
        } else {
            if (!isset($this->_simpleparsers[$ltype])) {
                $this->_simpleparsers[$ltype] = array();
            }
            $this->_simpleparsers[$ltype][] = $parser;
        }
        $this->_parsers[$name] = $parser;
    }

    public function setFile($parsedfile)
    {
        $this->_data = file_get_contents($parsedfile);
    }

    public function parse()
    {
        $lines = explode("\n", $this->_data);
        foreach ($lines as $line) {
            if ($line != "") {
                list($type, $info) = explode(":", $line, 2);
                foreach ($this->_complexparsers as $cp) {
                    if ($cp->logTypeIsHandled($type)) {
                        $cp->parseData($type, $info);
                    }
                }
                if (isset($this->_simpleparsers[$type])) {
                    foreach ($this->_simpleparsers[$type] as $lparser) {
                        $lparser->parseData($type, $info);
                    }
                }
            }
        }
    }
}

abstract class ProgressLineParser
{
    abstract public function parseData($type, $info);
}

class DefaultProgressLineParser extends ProgressLineParser
{
    public $stored = array();

    public function storeData($type, $data)
    {
        if (!isset($this->stored[$type])) {
            $this->stored[$type] = array();
        }
        $this->stored[$type][] = $data;
    }

    public function accData($type, $data)
    {
        if (!isset($this->stored[$type])) {
            $this->stored[$type] = 0;
        }
        $this->stored[$type] += $data;
    }

    public function setData($type, $data)
    {
        $this->stored[$type] = $data;
    }

    public function getData($type)
    {
        return (isset($this->stored[$type]) ? $this->stored[$type] : array());
    }

    public function logtypeIsHandled($type)
    {
        return true;
    }

    public function parseData($type, $info)
    {
        if (preg_match_all("/plugin;(\w+);(\w+)$/", $type, $m)) {
            $plclass = $m[1][0];
            $type = $m[2][0];
        }
        switch ($type) {
            case "pluginhello":
                list($name, $ver, $auth) = explode("-", $info);
                $this->storeData("plugins", array("name"=>$name, "ver"=>$ver, "auth"=>$auth));
                break;
            case "lookup":
                $this->setData("lookup", array_combine(array("nlines", "time"), explode(":", $info)));
                break;
            case "step":
                $this->setData("step", array_combine(array("label", "value"), explode(":", $info)));
                break;
            case "dbtime":
            case "itime":
                $parts = explode("-", $info);
                list($dcount, $delapsed, $dlastinc) = array(trim($parts[0]),trim($parts[1]),trim($parts[2]));
                if (count($parts) > 3) {
                    $this->setData("$type:lastcount", trim($parts[3]));
                }
                $this->setData("$type:count", $dcount);
                if ($delapsed > 0) {
                    $this->setData("$type:speed", ceil(($dcount * 60) / $delapsed));
                } else {
                    $this->setData("$type:speed", 0);
                }

                $this->setData("$type:elapsed", round($delapsed, 4));
                $this->setData("$type:incelapsed", round($dlastinc, 4));
                break;
            case "columns":
                $this->setData("columns", $info);
                break;
            case "end":
                $this->setData("ended", 1);
                break;
            case "skip":
                $this->accData("skipped", 1);
                break;
            default:
                $this->storeData($type, $info);
        }
    }
}

class DefaultProgressParser extends ProgressParser
{
    public function __construct()
    {
        $this->addParser("default", new DefaultProgressLineParser());
    }

    public function getData($type)
    {
        return $this->_parsers["default"]->getData($type);
    }
}
