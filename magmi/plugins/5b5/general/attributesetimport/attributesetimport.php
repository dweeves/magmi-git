<?php
require_once("magmi_csvreader.php");
require_once("fshelper.php");
require_once("additional_data_csv_reader.php");
require_once("array_reader.php");
require_once("multi_dim_array.php");
require_once("name2id_decoding.php");

/**
 * Attribute/Attribute Set/Attribute Set Associations importer plugin for Magmi framework
 *
 * This Plugin imports attributes, attribute sets with groups and the corresponding attribute-to-set associations from 3 different csv files to the magento database before the product update will start.
 * It lets you choose for each entity type (attributes, sets, associations) if you want to update existing, create new, delete marked records and/or prune all records which were not given in your import data.
 *
 * @author 5byfive GmbH (T.Rosenstiel) based on code (Magmi framework, particularly CSVReader and CSV Options) by Dweeves
 *
 * Copyright (C) 2015 by 5byfive GmbH (T. Rosenstiel) and Dweeves (S.BRACQUEMONT)
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */
class AttributeSetImporter extends Magmi_GeneralImportPlugin
{
    /**
     * Config parameters for call to updateGeneric when processing attributes.
     * @var array
     */
    private $ATTRIBUTE_ARGS = [
                    'entityName'=>"attribute",
                    'tables'=>['eav_attribute','catalog_eav_attribute'],
                    'idColName'=>'attribute_id',
                    'nameColNames'=>['attribute_code'],
                    'elementPrefix'=>'5B5ATI',
                    'verbose'=>true,
                    'fetchSystemAttributeIdsSql'=>"select attribute_id from ##eav_attribute## where is_user_defined = 0"
            ];

    /**
     * Config parameters for call to updateGeneric when processing attribute sets.
     * @var array
     */
    private $ATTRIBUTE_SET_ARGS = [
                    'entityName'=>"attribute set",
                    'tables'=>['eav_attribute_set'],
                    'idColName'=>'attribute_set_id',
                    'nameColNames'=>['attribute_set_name'],
                    'elementPrefix'=>'5B5ASI',
                    'verbose'=>true,
                    'inner' => [
                            'magmi:groups' => [
                                    'label' => 'Groups',
                                    'recordSeparator' => ',',
                                    'valueSeparator' => ':',
                                    'columnNames' => [
                                            'attribute_group_name',
                                            'default_group_id',
                                            'sort_order'
                                    ],
                                    'applyDefaultsFromParent' => ['attribute_set_id'],
                                    'applyConditionsFromParent' => ['attribute_set_id'],
                                    'config' => [
                                            'entityName'=>"attribute group",
                                            'tables'=>['eav_attribute_group'],
                                            'idColName'=>'attribute_group_id',
                                            'nameColNames'=>['attribute_group_name'],
                                            'elementPrefix'=>'5B5AGI',
                                            'verbose'=>false
                                    ]
                            ]
                    ]
    ];

    /**
     * Config parameters for call to updateGeneric when processing attribute set associations.
     * @var array
     */
    private $ATTRIBUTE_SET_ASSOCIATION_ARGS = [
                    'entityName'=>"attribute association",
                    'tables'=>['eav_entity_attribute'],
                    'idColName'=>'entity_attribute_id',
                    'nameColNames'=>['attribute_set_id','attribute_id','attribute_group_id'],
                    'elementPrefix'=>'5B5AAI',
                    'verbose'=>true,
                    'fetchSystemAttributeIdsSql'=>"select entity_attribute_id from ##eav_entity_attribute## where attribute_id in (select attribute_id from ##eav_attribute## where is_user_defined = 0)"
    ];

    /**
     * Configuration for the Name2IdDecoder used for attribute associations "decoding".
     * @var array
     */
    private $ATTRIBUTE_SET_ASSOCIATION_DECODER_ARGS = [
                            'attribute_set_name'
                                =>[
                                    'tableName' => 'eav_attribute_set',
                                    'nameColumnName' => 'attribute_set_name',
                                    'idColumnName' => 'attribute_set_id'
                                ],
                            'attribute_code'
                                =>[
                                    'tableName' => 'eav_attribute',
                                    'nameColumnName' => 'attribute_code',
                                    'idColumnName' => 'attribute_id'
                                ],
                            'attribute_group_name'
                                =>[
                                    'tableName' => 'eav_attribute_group',
                                    'nameColumnName' => 'attribute_group_name',
                                    'idColumnName' => 'attribute_group_id',
                                    'additionalIdColumn' => 'attribute_set_id'
                                ]
    ];

    public function initialize($params)
    {
    }

    /**
     * Copied from CSV_Datasource (redesign class structure?)
     *
     * @param string $prefix the prefix to use when retreiving options values from configuration (without ':')
     * @param string $resolve if set to true,return associated realpath
     */
    public function getScanDir($prefix, $resolve = true)
    {
        $scandir = $this->getParam($prefix.":basedir", "var/import");
        if (!isabspath($scandir)) {
            $scandir = abspath($scandir, Magmi_Config::getInstance()->getMagentoDir(), $resolve);
        }
        return $scandir;
    }

    /**
     * Copied from CSV_Datasource (redesign class structure?)
     * @param string $prefix the prefix to use when retreiving options values from configuration (without ':')
     */
    public function getCSVList($prefix)
    {
        $scandir = $this->getScanDir($prefix);
        $files = glob("$scandir/*.csv");
        return $files;
    }

    public function getPluginParams($params)
    {
        $pp = array();
        foreach ($params as $k => $v) {
            // This plugin is using multiple plugin param prefixes!
            if (preg_match("/^5B5A[TSAG]I:.*$/", $k)) {
                $pp[$k] = $v;
            }
        }
        return $pp;
    }

    public function getPluginInfo()
    {
        return array("name"=>"Attribute Set Importer","author"=>"5byfive GmbH","version"=>"0.0.2","url"=>$this->pluginDocUrl("Attribute_set_importer"));
    }

    /**
     * Copied from CSV_Datasource (redesign class structure?)
     * @param string $prefix the prefix to use when retreiving options values from configuration (without ':')
     * @param string $url url to fetch
     */
    public function getRemoteFile($prefix, $url)
    {
        $fg = RemoteFileGetterFactory::getFGInstance();
        if ($this->getParam($prefix.":remoteauth", false) == true) {
            $user = $this->getParam($prefix.":remoteuser");
            $pass = $this->getParam($prefix.":remotepass");
            $fg->setCredentials($user, $pass);
        }
        $cookies = $this->getParam($prefix.":remotecookie");
        if ($cookies) {
            $fg->setCookie($cookies);
        }

        $this->log("Fetching $prefix: $url", "startup");
        // output filename (current dir+remote filename)
        $csvdldir = dirname(__FILE__) . "/downloads";
        if (!file_exists($csvdldir)) {
            @mkdir($csvdldir);
            @chmod($csvdldir, Magmi_Config::getInstance()->getDirMask());
        }

        $outname = $csvdldir . "/" . basename($url);
        $ext = substr(strrchr($outname, '.'), 1);
        if ($ext != "txt" && $ext != "csv") {
            $outname = $outname . ".csv";
        }
        // open file for writing
        if (file_exists($outname)) {
            if ($this->getParam($prefix.":forcedl", false) == true) {
                unlink($outname);
            } else {
                return $outname;
            }
        }
        $fg->copyRemoteFile($url, $outname);

        // return the csv filename
        return $outname;
    }


    /**
     * <p>Gets the options for the import file from the plugin options (uses options with given prefix),
     * initializes and opens the CSVReader for further processing.
     * For attribute association import also initializes the AdditionalDataCSVReader as a wrapper for the CSVReader.</p>
     * @param string $prefix the prefix to use when retreiving options values from configuration (without ':')
     */
    private function prepareCSV($prefix)
    {
        $csvreader = new Magmi_CSVReader();

        // for attribute asociations use AdditionalDataCSVReader
        if ($prefix == '5B5AAI') {
            $csvreader = new AdditionalDataCSVReader();
        }
        $csvreader->bind($this);
        $csvreader->initialize($this->getParams(), $prefix);
        if ($this->getParam($prefix.":importmode", "local") == "remote") {
            $url = $this->getParam($prefix.":remoteurl", "");
            $outname = $this->getRemoteFile($prefix, $url);
            $this->setParam($prefix.":filename", $outname);
            $csvreader->initialize($this->_params);
        }
        $csvreader->checkCSV();

        // add additional (wildcarded) data for attribute associations
        if ($prefix == '5B5AAI') {
            $this->log('Appending/inflating data with configured data...', 'startup');
            $csvreader->addWildcardDataCSV($this->getParam('5B5AAI:default_rows', ''));
            $this->log('Finished appending data', 'startup');
        }
        $csvreader->openCSV();
        $csvreader->getColumnNames();
        return $csvreader;
    }

    /**
     * Performs the attribute import.
     */
    public function importAttributes($csvreader)
    {
        // condition to restrict on product entity type (will be given as default and fetchConditions to updateGeneric() function
        $etiCondition = ['entity_type_id' => $this->getProductEntityType()];
        $this->updateGeneric($csvreader, $this->ATTRIBUTE_ARGS, $etiCondition, $etiCondition);
    }


    /**
     * Performs the attribute set import.
     */
    public function importAttributeSets($csvreader)
    {
        // condition to restrict on product entity type (will be given as default and fetchConditions to updateGeneric() function
        $etiCondition = ['entity_type_id' => $this->getProductEntityType()];
        $this->updateGeneric($csvreader, $this->ATTRIBUTE_SET_ARGS, $etiCondition, $etiCondition);
    }

    /**
     * Performs the attribute associations import.
     */
    public function importAttributeAssociations($csvreader)
    {
        // condition to restrict on product entity type (will be given as default and fetchConditions to updateGeneric() function
        $etiCondition = ['entity_type_id' => $this->getProductEntityType()];

        // dynamically add product entity type id to decoder options
        $decoderArgs = array_merge_recursive($this->ATTRIBUTE_SET_ASSOCIATION_DECODER_ARGS,
                ['attribute_set_name'=>['conditions' =>['entity_type_id' => $this->getProductEntityType()]],
                        'attribute_code'=>['conditions' =>['entity_type_id' => $this->getProductEntityType()]]]);

        $decoder = new Name2IdDecoder($this, $decoderArgs);
        $this->updateGeneric($csvreader, $this->ATTRIBUTE_SET_ASSOCIATION_ARGS, $etiCondition, $etiCondition, $decoder);
    }

    /**
     * (non-PHPdoc)
     * The import logic is implemented in beforeImport because the attribute import should be done BEFORE importing the products.
     * @see Magmi_GeneralImportPlugin::beforeImport()
     */
    public function beforeImport()
    {
        $this->log('Attribute Set Importer started...', 'startup');
        $startTime = microtime(true);

        // perform attribute import (if enabled)
        if ($this->getParam('5B5ATI:enable', 'on')=='on') {
            $csvreader = $this->prepareCSV('5B5ATI');
            $this->importAttributes($csvreader);
            $csvreader->closeCSV();
            unset($csvreader);
        }

        // perform attribute set import (if enabled)
        if ($this->getParam('5B5ASI:enable', 'on')=='on') {
            $csvreader = $this->prepareCSV('5B5ASI');
            $this->importAttributeSets($csvreader);
            $csvreader->closeCSV();
            unset($csvreader);
        }

        // perform attribute associations import (if enabled)
        if ($this->getParam('5B5AAI:enable', 'on')=='on') {
            $csvreader = $this->prepareCSV('5B5AAI');
            $this->importAttributeAssociations($csvreader);
            $csvreader->closeCSV();
            unset($myreader);
            unset($csvreader);
        }

        // and that's it!
        $timediff = microtime(true) - $startTime;
        $this->log("Attribute Set Importer finished after $timediff seconds.", 'startup');
    }

    /**
     * <p>Fetches data from given table returning an associative MultiDimArray which uses the values of the columns given in  $nameColumnNames as keys
     * and the value from $idColumnName as values.</p>
     * @param string $tableName name of the table to fetch data from (without table prefix)
     * @param string $idColumnName name of table column to use as values for resulting array
     * @param array $nameColumnNames names of columns to use as keys for resulting array
     * @param array $conditions associative array defining conditions key is the column name, value the condition value (SQL: ... WHERE $key = $value... )
     */
    public function fetchName2Ids($tableName, $idColumnName, $nameColumnNames, $conditions)
    {

        // prepare array names $allColumns with all coumns to fetch from database
        $allColumns = $nameColumnNames;
        array_unshift($allColumns, $idColumnName);

        // fetch data
        $data = $this->fetch($tableName, $allColumns, $conditions);

        // prepare resulting array
        // MutliDimArray to store result
        $resultByName = new MultiDimArray();
        foreach ($data as $item) {
            $names = array();
            foreach ($nameColumnNames as $nameColumn) {
                $names[] = $item[$nameColumn];
            }
            $resultByName[$names] = $item[$idColumnName];
        }
        unset($data);
        return $resultByName;
    }

    /**
     * <p>Executes a select which could be described with the following preudo SQL:<br/>
     * <code>SELECT $columns FROM $tableName WHERE [for each entry in $conditions:] $key = $value</code><br/>
     * and returns the result.</p>
     * @param string $tableName name of the table to fetch data from. (without table prefix)
     * @param array $columns names of the table columns to fetch.
     * @param array $conditions associative array defining conditions key is the column name, value the condition value (SQL: ... WHERE $key = $value... )
     */
    private function fetch($tableName, $columns, $conditions)
    {
        $sql = "SELECT ".(isset($columns)?implode(',', $columns):"*")." FROM ".$this->tablename($tableName);
        $values = array();
        if (isset($conditions) && sizeof($conditions) > 0) {
            $stringConditions = array();
            foreach ($conditions as $fieldName => $value) {
                $stringConditions[] = "$fieldName = ?";
                $values[] = $value;
            }
            $sql .= " WHERE ".implode(" AND ", $stringConditions);
        }
        $data = $this->selectAll($sql, $values);
        return $data;
    }

    /**
     * <p>Fetches data from given tables returning an associative MultiDimArray which uses the values of the columns given in $nameColumnNames as keys
     * and an associative array (column name => column value) with merged data from all tables as values.</p>
     * <p><b>The first table must own all columns given in $nameColNames, all given tables must share the same $idColName. Columns used in conditions MUST also belong to first tableå!å</b></p>
     * @param array $tables names of the tables to fetch data from (without table prefix)
     * @param string $idColumnName name of table column of the primary key column (in ALL tables!)
     * @param array $nameColumnNames names of columns to use as keys for resulting array (all of which must belong to first table)
     * @param array $conditions associative array defining conditions key is the column name, value the condition value (SQL: ... WHERE $key = $value... ). All used columns MUST belong to first table!
     */
    private function fetchGeneric($tables, $idColName, $nameColNames, $conditions)
    {
        $resultByNames = new MultiDimArray();
        $namesById = array();
        $isFirstTable = true;
        foreach ($tables as $tableName) {
            $columnNames = $this->cols($tableName);
            $data = $this->fetch($tableName, null, ($isFirstTable?$conditions:array()));
            foreach ($data as $item) {
                $id = $item[$idColName];
                if ($isFirstTable) {
                    $names = array();
                    foreach ($nameColNames as $nameColName) {
                        $names[] = $item[$nameColName];
                    }
                    $namesById[$id] = $names;
                    $resultByNames[$names] = $item;
                } else {
                    if (isset($namesById[$id])) {
                        $names = $namesById[$id];
                        foreach ($item as $columnName => $value) {
                            $storedItem = $resultByNames[$names];
                            $storedItem[$columnName] = $value;
                            $resultByNames[$names] = $storedItem;
                        }
                    }
                }
            }
            unset($data);
            $isFirstTable=false;
        }
        unset($namesById);
        return $resultByNames;
    }

    /**
     * <p>Uses data given via a CSVReader or another class implementing getLinesCount(), getColumnNames() and getNextRecord() to update / insert / delete / and prune
     * data into/from table(s) given in $config, according to the configured plugin options.</p>
     *
     * <p>Generally, this is done in the following steps:
     * <ol>
     * <li>Fetch already existing data from database table(s) (see fetchGeneric()) into an associative array having the records "names" as key. (see section "names concept" below)</li>
     * <li>Update/Insert Loop<br/>
     *     This loop iterates over the given records (from CSV file) and searches its counterpart in DB data
     *     <ol>
     *     <li>if magmi:delete flag is set in CSV record (and option "PREFIX:magmi_delete" is active) -> delete record in DB (if it exists)</li>
     *     <li>if record does not exist in DB right now -> create it (if option "PREFIX:create" is active)</li>
     *     <li>if record is already existing in DB -> compare contents of records and update if necessary (if option "PREFIX:udpate" is active)</li>
     *     <li>meanwhile store the identifying columns ("names") of all given records (except deleted ones) as keys of a MultiDimArray for faster acces in second
     *           (prune) loop. ($givenNameValues)</li>
     *     </ol></li>
     * <li>Prune Loop (if option "PREFIX:prune" is switched on)<br/>
     *     This loop iterates over the given records from DATABASE and checks, if the same record (with the same name(s)) has been given by datasource in this update
     *     <ol>
     *     <li>if the DB record's 'names' are not in $givenNameValues, the record will be deleted, except
     *         <ol>
     *         <li>the names are given partly or fully (see below) in option "PREFIX:prune_keep" or</li>
     *         <li>option "PREFIX:prune_keep_system_attributes" is enabled and the records ID was found among the ids returned by the $fetchSystemAttributeIdsSql statement.</li>
     *         </ol></li>
     *     </ol></li>
     * <li>DONE!</li>
     * </ol></p>
     *
     * <h3>The "names" concept:</h3>
     * <p>As records in import files are normally given without a primary key database id (and even cannot be given with an id if the record is new ;) ) there
     * must be some sort of concept to identify, which record in import data corresponds to which record in the database table.
     * In some cases this is quite easy: The attribute_code column identifies an attribute, the attribute_set_name column identifies an attribute set.
     * In some cases it is more difficult: An attribute group still can be identified by a single column (attribute_group_name) but only within one single attribute set!
     * But an attribute association can only be identified by three columns ('attribute_set_id','attribute_id' and 'attribute_group_id') if you don't know the 'entity_attribute_id'.
     * Therefore the records are matched between import datasource and database using a "names array" which holds the values of all identifying fields (in most cases just one but sometimes three).</p>
     *
     * <h3>The $config parameter</h3>
     * <p>The $config parameter is an associative array which contains most options needed for this function:
     * <ul>
     * <li><b>entityName</b> (string): The name of the current entity type (e.g. "attribute set") to print in log statements and error messages.</li>
     * <li><b>tables</b> (indexed array): List of tables to update/insert into/delete from etc. All given tables must share the same id column (idColName). The table with the name column must be given as first element.</li>
     * <li><b>idColName</b> (string): Name of the primary key column (must be the same for ALL given tables)</li>
     * <li><b>nameColNames</b> (indexed array): List of identifying name columns (see "name concept"). All name columns must be in FIRST TABLE.</li>
     * <li><b>elementPrefix</b> (string): Prefix of plugin config parameter names (without ':').</li>
     * <li><b>verbose</b> (boolean): If true, logs status messages to startup log.</li>
     * <li><b>fetchSystemAttributeIdsSql</b> (string): Sql statement that returns the primary key ids of those elements, which should be kept if option "PREFIX:prune_keep_system_attributes is switched on. (write table names as "##tablename##" to apply table prefix if configured.)
     * </ul>
     * @param Magmi_CSVReader $csvreader a Magmi_CSVReader or an instance of any other class implementing the functions getLinesCount(), getColumnNames() and getNextRecord(). This object provides the data which should be imported.
     * @param array $config config in form of an associative array, see above!
     * @param array $defaults associative array, keys are the column names, values are the default values for the resprective field/column. Default values will be applied to each record from $csvreader if there is no value for the respective column.
     * @param array $fetchConditions same format as $defaults, only that the values are used as condition when fetching existing data from the database
     * @param Name2IdDecoder $decoder instance of Name2IdDecoder, that decodes all given names into ids before data is handled, may be null for "no decoding", necessary when names have to fetched from different tables to process.
     * @return Statistics object containing the amounts of updated/inserted/deleted... records.
     */
    private function updateGeneric($csvreader, &$config, $defaults, $fetchConditions, $decoder=null)
    {
        // extract the following variables from $config:
        // entityName,tables,idColName,nameColNames,elementPrefix,verbose,fetchSystemAttributeIdsSql,inner
        extract($config);

        $givenRecordCount = $csvreader->getLinesCount();
        if ($verbose) {
            $this->log("Will update ${entityName}s...($givenRecordCount records given)", 'startup');
        }

        // fetch data from database: result is a MultiDimArray with the record "names" as key(s)
        $dbDataByName = $this->fetchGeneric($tables, $idColName, $nameColNames, $fetchConditions); // fetch attribute sets from db
        $dbRecordCount = sizeof($dbDataByName);
        if ($verbose) {
            $this->log("Fetched $dbRecordCount existing ".$entityName."s.", 'startup');
        }

        // merge default values into $mergedDefaults, defaults are given from two sources: from user config parameter PREFIX:default_values (in JSON format)
        // and from function parameter $defaults
        $mergedDefaults = [];
        $paramDefaults = json_decode($this->getParam($elementPrefix.":default_values", "{}"), true);
        if (isset($defaults) || isset($paramDefaults)) {
            $mergedDefaults = array_merge((array)$paramDefaults, (array)$defaults);
        }

        /* ----------------------------------------------------------------------------------------------------------------------------------------------------------------
        *  Insert/update loop
        *  ---------------------------------------------------------------------------------------------------------------------------------------------------------------- */
        $statistics = new Statistics(); // keeps statistics information
        $innerStats = array();
        if (isset($inner)) {
            foreach ($inner as $innerColName => $innerConfig) {
                $innerStats[$innerColName] = new Statistics(); // accumulates statistics information for "inner" updates
            }
        }
        $lastReportTime = time(); // initialize report time -> to be able to report progress every second
        $currentRecordNo = 0; // just for counting...
        $givenNameValues = new MultiDimArray(); // store given Attribute names for faster pruning in second loop

        // iterate over all given records from CSV
        while ($record = $csvreader->getNextRecord()) {
            try {
                // counters and helper variables for statistics
                // (using booleans for statistics to only count one record once in multi-table updates (having more than one
                // table configured in $tables)
                $currentRecordNo++;
                $updatedRecord = false;
                $deletedRecord = false;
                $insertedRecord = false;
                $nothingToUpdateRecord = false;
                $doubledRecord = false;

                // apply default values to current record
                foreach ($mergedDefaults as $key => $value) {
                    // don't overwrite, only set if not previously given
                    if (!isset($record[$key])) {
                        $record[$key] = $value;
                    }
                }

                // if a decoder is given, decode given names to corresponding ids first
                // default values are given as names so this must be done AFTER defaults are applied,
                // otherwise defaults would have to be given as ids.
                $originalRecord = null;
                if (isset($decoder)) {
                    $originalRecord = $record;
                    $record = $decoder->decode($record);
                }

                // prepare the $currentNames array -> this array contains the record's "name" value(s)
                $currentNames = array();
                foreach ($nameColNames as $nameColName) {
                    $currentNames[] = $record[$nameColName];
                }

                // was a record with same "names" already given?
                // if yes -> log the names to simplify searching doubled entries or errors in CSV generation
                // (logged to php error_log to avoid spamming the normal "startup" log entries)
                if (isset($givenNameValues[$currentNames])) {
                    error_log("Doubled record: ". print_r($currentNames, true));
                    $doubledRecord = true; // just for statistics to inform user
                }

                // if magmi_delete option is set and current record has magmi:delete set to 1 delete record from database
                if ($this->getParam($elementPrefix.":magmi_delete", "off")=="on" && isset($record['magmi:delete']) && $record['magmi:delete'] == 1) {

                    // record existing in database?
                    $dbRecord = $dbDataByName[$currentNames];
                    if (isset($dbRecord)) {
                        // yes .. delete it (in all tables from $tables)!
                        foreach ($tables as $tableName) {
                            $columnNames = $this->cols($tableName);
                            $sql = "DELETE FROM ".$this->tablename($tableName)." WHERE $idColName=?";
                            $values = array($dbRecord[$idColName]);
                            $this->delete($sql, $values);
                            $deletedRecord=true;
                        }
                    }
                } else {
                    // found a record (which will not be deleted) so add it to the $givenNameValues
                    $givenNameValues[$currentNames] = 1;

                    // names existing in database? -> if yes this is an update else an insert
                    if (!isset($dbDataByName[$currentNames])) {
                        // record not existing yet, is create option enabled?
                        if ($this->getParam($elementPrefix.":create", 'on')=='on') {

                            // create option is enabled, so create the record in each table from $tables
                            foreach ($tables as $tableName) {
                                $columnNames = $this->cols($tableName);
                                $usedColumnNames = array();
                                $usedValues = array();
                                $questionMarks = array();
                                foreach ($columnNames as $columnName) {
                                    if (isset($record[$columnName])) {
                                        $usedColumnNames[] = $columnName;
                                        $usedValues[] = $record[$columnName];
                                        $questionMarks[] = "?";
                                    }
                                }

                                // store database ID of created record (if there are more than one table in $tables, the id is needed for further tables)
                                // therefore the "main" table must always be the FIRST one in $tables
                                $newId = $this->insert("INSERT INTO ".$this->tablename($tableName)." (".implode(",", $usedColumnNames).") VALUES (".implode(",", $questionMarks).")", $usedValues);
                                $insertedRecord=true;
                                // is there already a database id set in $record ? If no, store it in $record right now.
                                if (!isset($record[$idColName])) {
                                    $record[$idColName] = $newId;
                                }
                            }
                            
                            // if configured, perform "inner" import 
                            if (isset($inner)) {
                                foreach ($inner as $innerColName => $innerConfig) {
                                    if (isset($record[$innerColName])) {
                                        $result = $this->updateInner($record, $record[$innerColName], $innerConfig);
                                        $innerStats[$innerColName]->add($result);
                                    }
                                }
                            }
                        }
                    } else {
                        // record is already existing in database.. this is an update
                        // is update option enabled?
                        if ($this->getParam($elementPrefix.":update", 'on')=='on') {
                            // get database id of existing database record
                            $id = $dbDataByName[$currentNames][$idColName];
                            $record[$idColName] = $id;
                            // now update all tables from $tables
                            foreach ($tables as $tableName) {
                                $columnNames = $this->cols($tableName);

                                // put together values and set for current table
                                // (only with changed columns)
                                $setClauses = array();
                                $usedValues = array();
                                foreach ($columnNames as $columnName) {
                                    if (isset($record[$columnName]) && $record[$columnName] != $dbDataByName[$currentNames][$columnName]) {
                                        $setClauses[] = $columnName." = ?";
                                        $usedValues[] = $record[$columnName];
                                    }
                                }

                                // is there at least one setClause (has at least one column changed?)
                                // -> then update!
                                if (sizeof($setClauses) > 0) {
                                    $usedValues[] = $id;
                                    $sql = "UPDATE ".$this->tablename($tableName)." SET ".implode(",", $setClauses)." WHERE $idColName = ?";
                                    $this->update($sql, $usedValues);
                                    $updatedRecord=true;
                                } else {
                                    $nothingToUpdateRecord=true;
                                }
                            }

                            // if configured, perform "inner" import 
                            if (isset($inner)) {
                                foreach ($inner as $innerColName => $innerConfig) {
                                    if (isset($record[$innerColName])) {
                                        $result = $this->updateInner($record, $record[$innerColName], $innerConfig);
                                        $innerStats[$innerColName]->add($result);
                                    }
                                }
                            }
                        }
                    }
                }

                // now update statistics values
                if ($insertedRecord) {
                    $statistics->inserted++;
                }
                if ($deletedRecord) {
                    $statistics->deleted++;
                }
                if ($doubledRecord) {
                    $statistics->doubled++;
                }

                // only increase nothingToUpdate if no $updateRecord is not set
                // (which means for multi-table updates none of the tables has been updated)
                if ($updatedRecord) {
                    $statistics->updated++;
                } elseif ($nothingToUpdateRecord) {
                    $statistics->nothingToUpdate++;
                }

                // if there is a time() difference between now and $lastReportTime (which means
                // $lastReportTime was at least one second ago (because time() uses seconds))
                // then output the progress
                if (time()-$lastReportTime != 0 || $currentRecordNo == $givenRecordCount) {
                    $lastReportTime = time();
                    if ($verbose) {
                        $this->log("Insert & Update loop processed $currentRecordNo/$givenRecordCount records.", 'startup');
                    }
                }
            } catch (Exception $e) {
                // exception within loop -> log Exception
                $this->log("Exception in update/insert loop for entity '$entityName' in record no $currentRecordNo: ".$e->getMessage()."\nrecord data:".print_r($record, true).(isset($originalRecord)?"\noriginal record data:".print_r($originalRecord, true):"")."\nsee trace log!", 'startup');
                $this->trace($e, "Exception in update/insert loop for entity '$entityName' in record no $currentRecordNo: ".$e->getMessage()."\nrecord data:".print_r($record, true).(isset($originalRecord)?"\noriginal record data:".print_r($originalRecord, true):""));
            }
        }


        /* ----------------------------------------------------------------------------------------------------------------------------------------------------------------
         *  Prune loop
         *  ---------------------------------------------------------------------------------------------------------------------------------------------------------------- */

        // is prune option switched on?
        if ($this->getParam($elementPrefix.":prune", "on")=="on") {

            // parse option "PREFIX:prune_keep".
            // For entities identified by a single name this is easy: The names to keep are given in a simple comma-separated list.
            // For entities identified by more than one name/column, it is more complex:
            // * Either a comma-separated list is given, which means, only the first "name" will be compared, and if it matches
            //   the record will be kept
            //   for attribute set associations (which uses mor than one name) this will translate to
            //   "keep all associations for attribute sets with given names", because order of nameColNames is ['attribute_set_id','attribute_id','attribute_group_id']
            //   -> attribute_set_name is first!
            // * or a JSON Array (in fact an array of arrays) is given, which allows to give mor than one name per entry
            //   e.g. if for attribute associations the following array would be given:
            //     [["Default"],["Set1","name"],["Set2","name","Group1"]]
            //     this could be translated to keep all associations for set "Default", keep all associations for attribute "name" in "Set1" (regardless of group)
            //     and keep association for attribute "name" in "Set2" if it is in group "Group1"

            // fetch value from option "PREFIX:prune_keep
            $keepNamesString = trim($this->getParam($elementPrefix.":prune_keep", ""));
            // if value starts with a '[' this is a JSON array...
            if (substr($keepNamesString, 0, 1) == '[') {
                $keepNames = json_decode($keepNamesString);
            } else {
                // "normal" comma-separated string: transform to same
                // format than it would be converted from JSON: array of arrays
                $keepNames = array();
                foreach (explode(",", $keepNamesString) as $part) {
                    $keepNames[] = array(trim($part));
                }
            }

            // now set all elements of given array of arrays (-> each single array entry)
            // in a new MultiDimArray to 1 for easy and fast comparison
            // see documentation of "multiSet" and "offsetExistsPartly" functions of MultiDimArray
            $keepNamesArray = new MultiDimArray();
            $keepNamesArray->multiSet($keepNames, 1);

            // if there is a name to id $decoder set
            // decode the keys of the $keepNamesArray to the respective database ids
            if (isset($decoder)) {
                $newKeepNamesArray = new MultiDimArray();

                // iterate over all array keys of MultiDimArray
                // (using rewind(), valid(), next() offsetSet() because the short notation and foreach do not like array indexes)
                $keepNamesArray->rewind();
                while ($keepNamesArray->valid()) {
                    $entry = $keepNamesArray->key();
                    $newKeepNamesArray->offsetSet($decoder->decode($entry), 1);
                    $keepNamesArray->next();
                }
                $keepNamesArray = $newKeepNamesArray;
            }


            // prepare array with database ids as keys for entries which should not be pruned as the elements are related to system attributes
            $keepSystemIds = array();
            if ($this->getParam($elementPrefix.":prune_keep_system_attributes", "off") == "on") {
                $sql = preg_replace_callback('/(##[a-zA-Z_]*##)/Uis', function ($ms) { foreach ($ms as $m) {
    return str_replace('##', '', $this->tablename($m));
}}, $fetchSystemAttributeIdsSql);
                $idData = $this->select($sql);
                foreach ($idData as $record) {
                    $keepSystemIds[reset($record)] = 1;
                }
            }

            // just for counting records...
            $currentRecordNo = 0;

            // now loop aver all records from database...
            // again use rewind(), valid(), next() and offsetSet() because the short notation and foreach do not like array indexe
            $dbDataByName->rewind();
            while ($dbDataByName->valid()) {
                try {
                    $currentRecordNo++;
                    $currentNames = $dbDataByName->key();
                    $dbRecord = $dbDataByName->current();
                    // database id of current record
                    $currentId = $dbRecord[$idColName];

                    // statistics flags
                    $prunedRecord = false;
                    $keptRecord = false;

                    // check if conditions for pruning are matched (see above,1.) except a.) and b.) )
                    if (!$givenNameValues->offsetExists($currentNames) && !$keepNamesArray->offsetExistsPartly($currentNames) && !isset($keepSystemIds[$currentId])) {
                        // delete in each configured table
                        foreach ($tables as $tableName) {
                            $columnNames = $this->cols($tableName);
                            $sql = "DELETE FROM ".$this->tablename($tableName)." WHERE $idColName=?";
                            $this->delete($sql, array($currentId));
                            $prunedRecord=true;
                        }
                    } else {
                        $keptRecord=true;
                    }

                    // update statistics
                    if ($prunedRecord) {
                        $statistics->pruned++;
                    } elseif ($keptRecord) {
                        $statistics->kept++;
                    }

                    // again, if there is a time() difference between now and $lastReportTime (which means
                    // $lastReportTime was at least one second ago (because time() uses seconds))
                    // then output the progress
                    if (time()-$lastReportTime != 0 || $currentRecordNo == $dbRecordCount) {
                        $lastReportTime = time();
                        if ($verbose) {
                            $this->log("Prune loop processed $currentRecordNo/$dbRecordCount records.", 'startup');
                        }
                    }
                } catch (Exception $e) {
                    // exception within loop -> log Exception
                    $this->log("Exception in prune loop for entity '$entityName' in record no $currentRecordNo: ".$e->getMessage()."\nrecord data:".print_r($dbRecord, true)."\nsee trace log!", 'startup');
                    $this->trace($e, "Exception in prune loop for entity '$entityName' in record no $currentRecordNo: ".$e->getMessage()."\nrecord data:".print_r($dbRecord, true));
                }
                $dbDataByName->next();
            }
        }
        if ($verbose) {
            $this->log("Finished updating ".$entityName."s: $statistics", 'startup');
        }
        if ($verbose && isset($inner)) {
            foreach ($inner as $innerColName => $innerConfig) {
                $this->log($innerConfig['label'].": ".$innerStats[$innerColName], 'startup');
            }
        }
        return $statistics;
    }

    /**
     * <p>Updates data from a single field taken from the parent record by applying the "innerConfig" (so the contents of the field can be seen as a complete CSV datasource by themselves):</p>
     * <p>innerConfig is an associative array with the following keys:<ul>
     * <li>label: human-readable identifier, used for statistics output only</li>
     * <li>recordSeparator: String used in field content to separate records, e.g. ';'</li>
     * <li>valueSeparator: String used in field content to separate values from one another (values must be given in order of 'columnNames'</li>
     * <li>columnNames: array containing columnNames for evaluation of contents</li>
     * <li>applyDefaultsFromParent: array containing field names for which the default value will be take from the parent record.</li>
     * <li>applyConditionsFromParent: array containing field names for which there will be a condition with the value from the parent record.</li>
     * <li>config: config array to be passed on to the inner updateGeneric call. (see updateGeneric documentation)</li>
     * @param array $parentRecord the parent record's data as an associative array
     * @param string $fieldContent the string content of the field
     * @param array $innerConfig the innerConfig (see above)
     */
    private function updateInner(&$parentRecord, $fieldContent, &$innerConfig)
    {
        extract($innerConfig);
        // extract label,recordSeparator,valueSeparator,columnNames,applyDefaultsFromParent,applyConditionsFromParent,config

        // datasource will be filled with the appropriate values and will later on serve as datasource for the updateGeneric() call
        $data = array();
        // separate field content into substrings for each record and iterate over the results
        foreach (explode($recordSeparator, $fieldContent) as $entireRecord) {
            $recorddata = explode($valueSeparator, $entireRecord);
            $index = 0;
            $record = array();
            foreach ($recorddata as $value) {
                if (is_numeric($value)) {
                    $value = 0+$value;
                }
                $record[$columnNames[$index]] = $value;
                $index++;
            }
            $data[] = $record;
        }

        // prepare ArrayReader out of $givenGroups
        $datasource = new ArrayReader();
        $datasource->initialize($data);

        $conditions = array();
        foreach ($applyConditionsFromParent as $columnName) {
            $conditions[$columnName] = $parentRecord[$columnName];
        }
        $defaults = array();
        foreach ($applyDefaultsFromParent as $columnName) {
            $defaults[$columnName] = $parentRecord[$columnName];
        }

        // call updateGeneric with prepared data
        return $this->updateGeneric($datasource, $config, $defaults, $conditions);
    }

    public function afterImport()
    {
        // intentionally left blank...
    }

    /**
     * Calls the engine's trace function with the plugin's name and version as a prefix.
     * @param Exception $e the exception to trace
     * @param string $message message
     */
    public function trace($e, $message="no message")
    {
        $pinf = $this->getPluginInfo();
        $data = "{$pinf["name"]} v{$pinf["version"]} - ".$message;
        $this->_caller_trace($e, $data);
    }
}

/**
 * <p>Container for statistics data. Keeps track of the amount of updated, inserted, deleted, ... records.</p>
 */
class Statistics
{
    public $pruned=0,$kept=0,$inserted=0,$updated=0,$nothingToUpdate=0,$deleted=0,$doubled=0;

    /**
     * <p>Adds the calues of another Statistics instance to thie instance's counters.</p>
     * @param Statistics $otherStatistics Statistics instance with values to add to $this instance's values.
     */
    public function add(Statistics $otherStatistics)
    {
        $this->pruned+=$otherStatistics->pruned;
        $this->kept+=$otherStatistics->kept;
        $this->inserted+=$otherStatistics->inserted;
        $this->updated+=$otherStatistics->updated;
        $this->nothingToUpdate+=$otherStatistics->nothingToUpdate;
        $this->deleted+=$otherStatistics->deleted;
        $this->doubled+=$otherStatistics->doubled;
    }

    /**
     * <p>Returns a string containing all values for output/logging purposes.</p>
     * @return string
     */
    public function __toString()
    {
        return "Deleted: $this->deleted, Updated: $this->updated, Doubled: $this->doubled, Nothing to update: $this->nothingToUpdate, Inserted: $this->inserted, Pruned: $this->pruned, Kept: $this->kept";
    }
}
