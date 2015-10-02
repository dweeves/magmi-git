<?php

/**
 * <p>Extension of Magmi_CSVReader which allows to manually add records.</p>
 *
 */
class AdditionalDataCSVReader extends Magmi_CSVReader
{
    /**
     * Only used when using addWildcardData functions.
     * Stores all used values in original (CSV) data for each column.
     * @var array (column name => array of values)
     */
    private $_availableValuesPerColumn=null;

    /**
     * Array storing the manually added data.
     * @var array of records( array ( column name => column data ) )
     */
    private $_addedData=array();

    /**
     * Index for iteration.
     * @var integer
     */
    private $_currentIndex = 0;

    /**
     * Reads all CSV data into $this->_availableValuesPerColumn.
     */
    private function readValues()
    {
        $this->openCSV();
        $this->getColumnNames();
        $this->_availableValuesPerColumn=[];
        while ($record = $this->getNextRecord()) {
            foreach ($record as $columnName => $value) {
                if (!isset($this->_availableValuesPerColumn[$columnName])) {
                    $this->_availableValuesPerColumn[$columnName] = array();
                }
                $this->_availableValuesPerColumn[$columnName][$value] = 1;
            }
        }
        $this->closeCSV();
    }

    /**
     * <p>Takes a single record and if one of the column values of the record
     * has a value of "*", mutliples the record by generating records with the same values
     * but replacing the "*" value with each used value for the same column from the CSV source. Then returns
     * an array with all generated records.
     * Calls expandWildcards recursively on the generated records, to replace/expand asterisk values in other columns as well.
     * If none of the values is "*", then an array containing only the given record is returned.</p>
     * @param array $record as associative array (column name => column value)
     * @return array of generated/expanded records
     */
    private function inflateWildcardsSingle($record)
    {
        foreach ($record as $columnName => $value) {
            if ($value === "*") {
                $expandedRecords = array();
                if (isset($this->_availableValuesPerColumn[$columnName])) {
                    foreach ($this->_availableValuesPerColumn[$columnName] as $value => $ignore) {
                        $newRecord = $record;
                        $newRecord[$columnName] = $value;
                        $expandedRecords[] = $newRecord;
                    }
                }
                return $this->inflateWildcards($expandedRecords);
            }
        }
        return array($record);
    }

    /**
     * <p>Calls expandWildcardsSingle for each record in the given array and merges the results into one array.</p>
     * @param array $records array of records
     * @return array of expanded records
     */
    private function inflateWildcards($records)
    {
        $newRecords = array();
        foreach ($records as $record) {
            $newRecords = array_merge($newRecords, $this->inflateWildcardsSingle($record));
        }
        return $newRecords;
    }

    /**
     * <p>Adds the given data (given as a csv formatted string with headers in first line)
     * to the CSV Reader data. Wildcards ("*") are allowed to inflate data for every possible value in this column.</p>
     *
     * @param string $csv data to add as a csv formatted multiline string
     */
    public function addWildcardDataCSV($csv)
    {
        $records = array();
        $lines = explode("\n", $csv);
        $firstLine = array_shift($lines);
        $columnNames = str_getcsv($firstLine);
        while (sizeof($lines) > 0) {
            $currentLine = array_shift($lines);
            $data = str_getcsv($currentLine);
            $records[] = array_combine($columnNames, $data);
        }
        $this->addWildcardData($records);
    }

    /**
     * <p>Adds the given data (given as an array of records (each by itself an associative array of the form column name => column value)) to the CSV Reader data.
     * Wildcards ("*") are allowed to inflate data for every possible value in this column.</p>
     *
     * @param string $csv data to add as a csv formatted multiline string
     */
    public function addWildcardData($data)
    {
        if (!isset($this->_availableValuesPerColumn)) {
            $this->readValues();
        }
        $this->_addedData = $this->inflateWildcards($data);
    }

    /**
     * (non-PHPdoc)
     * @see Magmi_CSVReader::getLinesCount()
     */
    public function getLinesCount()
    {
        return (parent::getLinesCount()+sizeof($this->_addedData));
    }

    /**
     * (non-PHPdoc)
     * @see Magmi_CSVReader::openCSV()
     */
    public function openCSV()
    {
        $this->_currentIndex = 0;
        return parent::openCSV();
    }

    /**
     * (non-PHPdoc)
     * @see Magmi_CSVReader::getNextRecord()
     */
    public function getNextRecord()
    {
        $result = parent::getNextRecord();
        if (!$result && sizeof($this->_addedData) > $this->_currentIndex) {
            $result = $this->_addedData[$this->_currentIndex];
            $this->_currentIndex++;
        }
        return $result;
    }
}
