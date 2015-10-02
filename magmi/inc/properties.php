<?php
if (!defined("DIRSEP")) {
    define("DIRSEP", DIRECTORY_SEPARATOR);
}

class FileNotFoundException extends Exception
{
}

class InvalidPropertiesException extends Exception
{
}

class Properties
{
    protected $_props;
    public $inifile;
    protected $_specialchars = array('"'=>":DQUOTE:","'"=>":SQUOTE:",'\\t'=>"TAB");

    public function __construct()
    {
        $this->inifile = null;
        $this->_props = array();
    }

    public function setPropsFromFlatArray($flatarr)
    {
        $this->_props = $this->getIniStruct($flatarr);
    }

    public function setProps($proparr)
    {
        $this->_props = $proparr;
    }

    public function load($file)
    {
        if (!file_exists($file)) {
            return;
            // throw new FileNotFoundException();
        }
        try {
            $this->inifile = $file;
            $this->_props = parse_ini_file($this->inifile, true);
            foreach ($this->_props as $sec => $data) {
                foreach ($data as $k => $v) {
                    foreach ($this->_specialchars as $spch => $alias) {
                        $newv = str_replace($alias, $spch, $v);
                        if ($newv != $v) {
                            break;
                        }
                    }
                    $this->_props[$sec][$k] = $newv;
                }
            }
        } catch (Exception $e) {
            throw new InvalidPropertiesException();
        }
    }

    public function getIniStruct($arr)
    {
        $conf = array();
        foreach ($arr as $k => $v) {
            list($section, $value) = explode(":", $k, 2);
            if (!isset($conf[$section])) {
                $conf[$section] = array();
            }
            $conf[$section][$value] = $v;
        }
        return $conf;
    }

    public function save($fname = null)
    {
        if ($fname == null) {
            $fname = $this->inifile;
        }
        return $this->write_ini_file($this->_props, $fname, true);
    }

    public function esc($str)
    {
        foreach ($this->_specialchars as $spch => $alias) {
            $str = str_replace($spch, $alias, $str);
        }
        return $str;
    }

    public function write_ini_file($assoc_arr, $path, $has_sections = false)
    {
        $content = "";
        if (count($assoc_arr) > 0) {
            if ($has_sections) {
                foreach ($assoc_arr as $key => $elem) {
                    $content .= "[" . $key . "]\n";
                    foreach ($elem as $key2 => $elem2) {
                        if (is_array($elem2)) {
                            $celem2 = count($elem2);
                            for ($i = 0; $i < $celem2; $i++) {
                                $content .= $key2 . "[] = \"" . $this->esc($elem2[$i]) . "\"\n";
                            }
                        } elseif ($elem2 == "") {
                            $content .= $key2 . " = \n";
                        } else {
                            $content .= $key2 . " = \"" . $this->esc($elem2) . "\"\n";
                        }
                    }
                }
            } else {
                foreach ($assoc_arr as $key => $elem) {
                    if (is_array($elem)) {
                        $celem = count($elem);
                        for ($i = 0; $i < $celem; $i++) {
                            $content .= $key . "[] = \"" . $this->esc($elem[$i]) . "\"\n";
                        }
                    } elseif ($elem == "") {
                        $content .= $key . " = \n";
                    } else {
                        $content .= $key . " = \"" . $this->esc($elem) . "\"\n";
                    }
                }
            }
        }

        if (!$handle = fopen($path, 'w')) {
            return false;
        }
        if (!fwrite($handle, $content)) {
            return false;
        }
        @chmod($path, 0664);
        fclose($handle);
        return true;
    }

    public function set($secname, $pname, $val)
    {
        $this->_props[$secname][$pname] = $val;
    }

    /**
     * retrieve property value with default if not found
     *
     * @param string $secname
     *            section name
     * @param string $pname
     *            property name
     * @param string $default
     *            default value if not found (null if not set)
     * @return string value if found or default if not found
     */
    public function get($secname, $pname, $default = null)
    {
        if (isset($this->_props[$secname]) && isset($this->_props[$secname][$pname])) {
            $v = $this->_props[$secname][$pname];
            return $v;
        } else {
            return $default;
        }
    }

    public function getsection($secname)
    {
        if (isset($this->_props[$secname])) {
            return $this->_props[$secname];
        } else {
            return array();
        }
    }

    public function hasSection($secname)
    {
        return isset($this->_props[$secname]);
    }

    public function removeSection($secname)
    {
        if ($this->hasSection($secname)) {
            unset($this->_props[$secname]);
        }
    }
}
