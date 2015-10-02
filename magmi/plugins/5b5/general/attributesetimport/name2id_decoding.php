<?php
/**
 * <p>Class that takes a number of configurations to replace column values with other values based on a mapping taken from a database table.</p>
 * <p>e.g. if the following configuration ist given:<br/>
 * <code>['attr_set_name' => [<br/>
 *    'tableName' => 'eav_attribute_set',<br/>
 *    'nameColumnName' => 'attribute_set_name',<br/>
 *    'idColumnName' => 'attribute_set_id'],<br/>
 *    'attr_name' => [<br/>
 *    'tableName' => 'eav_attribute',<br/>
 *    'nameColumnName' => 'attribute_code',<br/>
 *    'idColumnName' => 'attribute_id']<br/>
 * ]<br/></code>
 * the decoder will remove values for field 'attr_set_name' in given array (function decode) and add a corresponding 'attribute_set_id' taken from table 'eav_attribute_set' by selecting the record having
 * column 'attribute_set_name' equal to the given 'attr_set_name'. 'attr_name' will be replaced by 'attribute_id's taken from table 'eav_attribute' by mapping 'attr_name' via column 'attribute_code' to an 'attribute_id'.</p>
 * <p>Therefore the decoder builds up cached mapping tables upon initialization and replaces the record data using the cached data when "decode" is called.</p>
 * <p>Per configuration one additional id column may be given, which has the effect, that the corresponding record in mapping data will be selected by comparing both the name column and the additional column with the value from the given record.</p>
 *
 */
class Name2IdDecoder
{
    protected $_mappingTables;
    protected $_mappedColumnNames;
    protected $_additionalIdColumns;

    /**
     * <p>Contructs the object instance with the given configuration. (also see class comment!)</p>
     * @param array $mappings associative array using fieldnames to be replaced as keys having an associative array as value, which has assigned values for 'tableName', 'nameColumnName', 'idColumnName' and, optionally, 'additionalIdColumn'
     */
    public function __construct($importer, $mappings)
    {
        $this->_mappingTables = array();
        $this->_mappedColumnNames = array();
        $this->_additionalIdColumns = array();
        foreach ($mappings as $columnName => $mappingInfo) {
            $nameColumns = array($mappingInfo['nameColumnName']);
            if (isset($mappingInfo['additionalIdColumn'])) {
                array_push($nameColumns, $mappingInfo['additionalIdColumn']);
            }
            $this->_mappingTables[$columnName] = $importer->fetchName2Ids($mappingInfo['tableName'], $mappingInfo['idColumnName'], $nameColumns, isset($mappingInfo['conditions'])?$mappingInfo['conditions']:null);
            $this->_mappedColumnNames[$columnName] = $mappingInfo['idColumnName'];
            if (isset($mappingInfo['additionalIdColumn'])) {
                $this->_additionalIdColumns[$columnName] = $mappingInfo['additionalIdColumn'];
            }
        }
    }

    /**
     * <p>Perform decoding of values in given array.<p>
     * <p>Returns the mapped record, which means that mapped keys are removed and resulting keys will be added to result. Unmapped keys stay the same and retain their value.</p>
     * @param unknown $record
     */
    public function decode($record)
    {
        // is $record an associative or indexed array?
        $indexed = false;
        if (!(bool)count(array_filter(array_keys($record), 'is_string'))) {
            // indexed! -> make associative!
            $indexed=true;
            // keys are column names
            $keys = array_keys($this->_mappedColumnNames);
            // values is record
            $values = $record;
            // make keys and values same size (by cutting off overlapping keys/values)
            $keys = array_slice($keys, 0, sizeof($values));
            $values = array_slice($values, 0, sizeof($keys));
            // make an associative array out of it
            $record = array_combine($keys, $values);
        }
        $indexedResult = array();
        foreach ($this->_mappedColumnNames as $columnName => $idColumnName) {
            if (isset($record[$columnName])) {
                $mappingTable = $this->_mappingTables[$columnName];
                $values = array($record[$columnName]);
                if (isset($this->_additionalIdColumns[$columnName])) {
                    $addtnlColumnName = $this->_additionalIdColumns[$columnName];
                    $addtnlIdValue = $record[$addtnlColumnName];
                    array_push($values, $addtnlIdValue);
                }
                if (isset($mappingTable[$values])) {
                    $record[$idColumnName] = $mappingTable[$values];
                    unset($record[$columnName]);
                    $indexedResult[] = $mappingTable[$values];
                } else {
                    $indexedResult[] = $record[$columnName];
                    error_log("Mapped value not found for column $columnName for name(s): ".print_r($values, true));
                }
            }
        }
        if ($indexed) {
            // was indexed?? -> return indexed result
            return $indexedResult;
        } else {
            // return associative result
            return $record;
        }
    }

    /**
     * Returns the given list of column names, but replacing mapped column names with their corresponding mapping target names.
     * @return the given list of column names, but replacing mapped column names with their corresponding mapping target names.
     */
    public function decodeColumnNames($columnNames)
    {
        $newColumnNames = array();
        foreach ($columnNames as $columnName) {
            if (isset($this->_mappedColumnNames[$columnName])) {
                $newColumnNames[] = $this->_mappedColumnNames[$columnName];
            } else {
                $newColumnNames[] = $columnName;
            }
        }
        return $newColumnNames;
    }
}
