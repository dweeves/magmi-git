<?php

class ValueRemapper
{
    protected static $_inst;
    protected $_maps = array();
    protected $_cimaps = array();
    protected $_curmap = null;

    public function __construct()
    {
    }

    public static function getInstance()
    {
        if (self::$_inst == null) {
            self::$_inst = new ValueRemapper();
        }
        return self::$_inst;
    }

    public static function use_csv($csv)
    {
        $inst = self::getInstance();
        $inst->setMap($csv);
        $inst->_curmap = $csv;
        return $inst;
    }

    public function map($val, $ci = false)
    {
        $tval = trim($val);
        // remapper case insensitive fix
        if ($ci) {
            $tval = strtoupper($val);
            $targetmap = $this->_cimaps[$this->_curmap];
        } else {
            $targetmap = $this->_maps[$this->_curmap];
        }
        return isset($targetmap[$tval]) ? $targetmap[$tval] : $val;
    }

    /*
     * Map a multivalue given a separator
     */
    public function mapmulti($val, $sep = ',', $ci = false)
    {
        $vals = explode($sep, $val);
        for ($i = 0; $i < count($vals); $i++) {
            $vals[$i] = $this->map($vals[$i], $ci);
        }
        return implode($sep, $vals);
    }

    public function setMap($csv)
    {
        if (!isset($this->_maps[$csv])) {
            $this->_maps[$csv] = array();
            $this->_cimaps[$csv] = array();
            if (file_exists($csv)) {
                $lines = file($csv);
                foreach ($lines as $line) {
                    $kv = explode(";", trim($line));
                    $this->_maps[$csv][$kv[0]] = $kv[1];
                    $this->_cimaps[$csv][strtoupper($kv[0])] = $kv[1];
                }
            }
        }
    }
}
