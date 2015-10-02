<?php

/**
 * <p>This class can replace a CSVReader as a datasource for the updateGeneric() function of AttributeSetImporter,
 * it simply does not read data from a CSV file but from an array.</p>
 *
 */
class ArrayReader
{
    protected $_array;
    protected $_leftKeys;
    protected $_currentKey=null;
    protected $_columnNames = array();

    public function initialize($array)
    {
        $this->_array = $array;
        $this->_leftKeys = array_keys($array);
        $this->_currentKey = array_shift($this->_leftKeys);
        if (isset($currentKey)) {
            $this->_columnNames = array_keys($this->_array[$this->_currentKey]);
        }
    }

    public function getColumnNames($prescan = false)
    {
        return $this->_columnNames;
    }

    public function getLinesCount()
    {
        return sizeof($this->_array);
    }

    public function getNextRecord()
    {
        if (!isset($this->_currentKey)) {
            return null;
        } else {
            $record = $this->_array[$this->_currentKey];
            $this->_currentKey = array_shift($this->_leftKeys);
            return $record;
        }
    }

    public function onException($e)
    {
    }
}
