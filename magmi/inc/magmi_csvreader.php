<?php
require_once ("magmi_mixin.php");
require_once ("magmi_utils.php");

class Magmi_CSVException extends Exception
{
}

class Magmi_CSVReader extends Magmi_Mixin
{
    protected $_filename;
    protected $_fh;
    protected $_cols;
    protected $_csep;
    protected $_dcsep;
    protected $_params;
    protected $_buffersize;
    protected $_curline;
    protected $_nhcols;
    protected $_ignored = array();

    public function initialize()
    {
        $this->_filename = $this->getParam("CSV:filename");
        $this->_csep = $this->getParam("CSV:separator", ",");
        $this->_dcsep = $this->_csep;
        
        if ($this->_csep == "\\t")
        {
            $this->_csep = "\t";
        }
        
        $this->_cenc = $this->getParam("CSV:enclosure", '"');
        $this->_buffersize = $this->getParam("CSV:buffer", 0);
        $this->_ignored = explode(",", $this->getParam("CSV:ignore"));
    }

    public function getLinesCount()
    {
        // open csv file
        $f = fopen($this->_filename, "rb");
        if ($this->getParam("CSV:noheader", false) == true)
        {
            $count = 0;
        }
        else
        {
            $count = -1;
        }
        $linenum = 0;
        if ($f != false)
        {
            $line = 1;
            while ($line < $this->getParam("CSV:headerline", 1))
            {
                $line++;
                $dummy = fgetcsv($f, $this->_buffersize, $this->_csep, $this->_cenc);
            }
            // get records count
            while (fgetcsv($f, $this->_buffersize, $this->_csep, $this->_cenc))
            {
                if (!in_array($line, $this->_ignored))
                {
                    $count++;
                }
            }
            fclose($f);
        }
        else
        {
            $this->log("Could not read $this->_filename , check permissions", "error");
        }
        return $count;
    }

    public function checkCSV()
    {
        $this->_curline = 0;
        ini_set("auto_detect_line_endings", true);
        if (!isset($this->_filename))
        {
            throw new Magmi_CSVException("No csv file set");
        }
        if (!file_exists($this->_filename))
        {
            throw new Magmi_CSVException("{$this->_filename} not found");
        }
        $this->log("Importing CSV : $this->_filename using separator [ $this->_dcsep ] enclosing [ $this->_cenc ]", 
            "startup");
    }

    public function openCSV()
    {
        $utf8bom = chr(0xEF) . chr(0xBB) . chr(0xBF);
        // open csv file
        $this->_fh = fopen($this->_filename, "rb");
        // check for UTF8 BOM
        $bomtest = fread($this->_fh, 3);
        // if 3 first bytes of file are not utf8 bom
        if ($bomtest != $utf8bom)
        {
            // rewind to first byte;
            $this->log("No BOM detected, assuming File is UTF8 without BOM...", "startup");
            fseek($this->_fh, 0, SEEK_SET);
        }
        else
        {
            $this->log("File is UTF8 (BOM Detected)", "startup");
        }
    }

    public function getColumnNames($prescan = false)
    {
        if ($prescan == true)
        {
            $this->openCSV();
            $this->_csep = $this->getParam("CSV:separator", ",");
            $this->_dcsep = $this->_csep;
            
            if ($this->_csep == "\\t")
            {
                $this->_csep = "\t";
            }
            
            $this->_cenc = $this->getParam("CSV:enclosure", '"');
            $this->_buffersize = $this->getParam("CSV:buffer", 0);
        }
        
        $line = 1;
        
        while ($line < $this->getParam("CSV:headerline", 1))
        {
            $line++;
            $dummy = fgetcsv($this->_fh, $this->_buffersize, $this->_csep, $this->_cenc);
            $this->log("skip line $line:" . implode($this->_csep, $dummy), "info");
        }
        $cols = fgetcsv($this->_fh, $this->_buffersize, $this->_csep, $this->_cenc);
        // if csv has no headers,use column number as column name
        if ($this->getParam("CSV:noheader", false) == true)
        {
            $kl = array_merge(array("dummy"), $cols);
            unset($kl[0]);
            $cols = array();
            foreach (array_keys($kl) as $c)
            {
                $cols[] = "col" . $c;
            }
            // reset file pointer
            fseek($this->_fh, 0);
        }
        $this->_cols = $cols;
        $this->_nhcols = count($this->_cols);
        // trim column names
        for ($i = 0; $i < $this->_nhcols; $i++)
        {
            $this->_cols[$i] = trim($this->_cols[$i]);
        }
        
        if ($prescan == true)
        {
            fclose($this->_fh);
        }
        else
        {
            $this->log("$this->_nhcols CSV headers columns found", "startup");
        }
        return $this->_cols;
    }

    public function closeCSV()
    {
        fclose($this->_fh);
    }

    public function isemptyline($row)
    {
        return (!isset($row[1]) && empty($row[0]));
    }

    public function getNextRecord()
    {
        $row = null;
        $allowtrunc = $this->getParam("CSV:allowtrunc", false);
        while ($row !== false)
        {
            $row = fgetcsv($this->_fh, $this->_buffersize, $this->_csep, $this->_cenc);
            if ($row !== false)
            {
                $this->_curline++;
                // skip empty lines
                if ($this->isemptyline($row))
                {
                    continue;
                }
                $rcols = count($row);
                if (!$allowtrunc && $rcols != $this->_nhcols)
                {
                    // if strict matching, warning & continue
                    $this->log(
                        "warning: line $this->_curline , wrong column number : $rcols found over $this->_nhcols, line skipped", 
                        "warning");
                    continue;
                }
                break;
            }
        }
        // if we read something
        if (is_array($row))
        {
            // strict mode
            if (!$allowtrunc)
            {
                // create product attributes values array indexed by attribute code
                $record = array_combine($this->_cols, $row);
            }
            else
            {
                
                // relax mode, recompose keys from read columns , others will be left unset
                $ncols = count($row);
                $cols = array_slice($this->_cols, 0, $ncols);
                $record = array_combine($cols, $row);
            }
        }
        else
        {
            $record = false;
        }
        unset($row);
        return $record;
    }
}