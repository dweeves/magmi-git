<?php
/**
 * 
 * This class is a Database Operation Helper based on PDO library
 * It provides shortcuts for common DB CRUD operations and some advanced templated requests operations
 * @author dweeves
 *
 */
include_once ("timecounter.php");

class DBHelper
{
    protected $_db;
    protected $_debug;
    protected $_laststmt;
    protected $_use_stmt_cache = true;
    protected $_nreq;
    protected $_indbtime;
    protected $_intrans = false;
    protected $prepared = array();
    protected $_timecounter = null;
    protected $_tcats;

    public function __construct()
    {
        $this->_timecounter = new TimeCounter(get_class($this));
        $this->_tcats = "db";
        $this->_timecounter->initTimingCats(array($this->_tcats));
        $this->_timecounter->addCounter("requests");
    }

    /**
     * Intializes database connection
     *
     * @param string $host
     *            : hostname
     * @param string $dbname
     *            : database name
     * @param string $user
     *            : username
     * @param string $pass
     *            : password
     * @param bool $debug
     *            : debug mode
     */
    public function initDb($host, $dbname, $user, $pass, $port = 3306, $socket = "/tmp/mysql.sock", $conntype = "net", $debug = false)
    {
        // intialize connection with PDO
        // fix by Mr Lei for UTF8 special chars
        if ($conntype == "socket")
        {
            $pdostr = "mysql:unix_socket=$socket;dbname=$dbname;charset=utf8";
        }
        else
        {
        	$pdostr = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8";
        }
        
        $this->_db = new PDO($pdostr, $user, $pass, array(PDO::MYSQL_ATTR_INIT_COMMAND=>"SET NAMES utf8"));
        // use exception error mode
        $this->_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->_db->setAttribute(PDO::ATTR_ORACLE_NULLS, PDO::NULL_NATURAL);
        $this->_db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        
        // set database debug mode to trace if necessary
        $this->_debug = $debug;
        $this->prepared = array();
    }

    /**
     * store output in some debug file
     *
     * @param unknown_type $data            
     */
    public function logdebug($data)
    {
        if ($this->_debug)
        {
            $f = fopen($this->_debugfile, "a");
            fwrite($f, microtime());
            fwrite($f, $data);
            fwrite($f, "\n");
            fclose($f);
        }
    }

    /**
     * Sets or unsets the usage of internal prepared statement cache for reuse
     *
     * @param boolean $uc
     *            true:use cache,false:do not use cache
     */
    public function usestmtcache($uc)
    {
        $this->_use_stmt_cache = $uc;
    }

    /**
     * releases database connection
     */
    public function exitDb()
    {
        // clear PDO resource
        $this->_db = NULL;
    }

    /**
     * Helper method to try to guess mysql socket based on some tricky phpinfo analysis
     *
     * @throws Exception if something got wrong during detection
     */
    public static function getMysqlSocket()
    {
        $mysqlsock = "";
        $old_track = ini_set('track_errors', '1');
        try
        {
            $mysqlsock = ini_get("mysql.default_socket");
            
            if (isset($mysqlsock) && !@file_exists($mysqlsock))
            {
                if (error_get_last() !== null)
                {
                    throw new Exception();
                }
                ob_start();
                phpinfo();
                $data = ob_get_contents();
                ob_end_clean();
                $cap = preg_match("/MYSQL_SOCKET.*?<td .*?>(.*?)<\/td>/msi", $data, $matches);
                if ($cap)
                {
                    $mysqlsock = $matches[1];
                }
            }
            if (isset($mysqlsock) && !@file_exists($mysqlsock))
            {
                $mysqlsock = "";
            }
        }
        catch (Exception $e)
        {}
        if (error_get_last() !== null)
        {
            $mysqlsock = false;
        }
        ini_set('track_errors', $old_track);
        return $mysqlsock;
    }

    /**
     * Initializes database requests stats counters
     */
    public function initDbqStats()
    {
        $this->_nreq = 0;
        $this->_indbtime = 0;
    }

    /**
     * Returns database statistics
     *
     * @param unknown_type $nbreq            
     */
    public function collectDbqStats(&$nbreq)
    {
        return $this->_nreq;
    }

    /**
     * cache sorting comparison method
     *
     * @param unknown_type $a            
     * @param unknown_type $b            
     */
    public function cachesort($a, $b)
    {
        return $b[1] - $a[1];
    }

    /**
     * Garbages statement cache if above 500 , removes less used statements    
     */
    public function garbageStmtCache()
    {
        if (count($this->prepared) >= 500)
        {
            uasort($this->prepared, array($this,"cachesort"));
            array_splice($this->prepared, 350, count($this->prepared));
        }
    }

    /**
     * executes an sql statement
     *
     * @param string $sql
     *            : sql statement (may include ? placeholders or named variables)
     * @param array $params
     *            : parameters to replace placeholders (can be null)
     * @param boolean $close
     *            : auto close cursor after statement execution (defaults to true)
     * @return PDOStatement : statement for further processing if needed
     */
    public function exec_stmt($sql, $params = null, $close = true)
    {
        $this->_nreq++;
        $this->_timecounter->initTime("indb", null, $this->_tcats);
        $this->_timecounter->incCounter("requests");
        $t0 = microtime(true);
        if ($this->_use_stmt_cache && strpos($sql, "'") == false)
        {
            // if sql not in statement cache
            if (!isset($this->prepared[$sql]))
            {
                $this->garbageStmtCache();
                // create new prepared statement
                $stmt = $this->_db->prepare($sql);
                // cache prepare statement
                $this->prepared[$sql] = array($stmt,1);
            }
            else
            {
                // get from statement cache
                $this->prepared[$sql][1]++;
                $stmt = $this->prepared[$sql][0];
            }
        }
        else
        {
            // create new prepared statement
            $stmt = $this->_db->prepare($sql);
        }
        $this->_laststmt = $stmt;
        if ($params != null)
        {
            if (!$this->is_assoc($params))
            {
                $params = is_array($params) ? $params : array($params);
                $stmt->execute($params);
            }
            else
            {
                foreach ($params as $pname => $pval)
                {
                    if (count(explode(":", $pname)) == 1)
                    {
                        $val = strval($pval);
                        $stmt->bindValue(":$pname", $val);
                    }
                }
                $stmt->execute();
            }
        }
        else
        {
            
            $stmt->execute();
        }
        if ($close)
        {
            $stmt->closeCursor();
        }
        $this->_timecounter->exitTime("indb", null, $this->_tcats);
        $t1 = microtime(true);
        $this->_indbtime += $t1 - $t0;
        //$this->logdebug("$sql\n" . print_r($params, true));
        unset($params);
        return $stmt;
    }

    /**
     * Perform a delete statement, sql should be "DELETE"
     *
     * @param string $sql
     *            : DELETE statement sql (placeholders allowed)
     * @param array $params
     *            : placeholder replacements (can be null)
     */
    public function delete($sql, $params = null)
    {
        $this->exec_stmt($sql, $params);
    }

    /**
     * Performs an update statement
     *
     * @param string $sql
     *            UPDATE statement sql (placeholder allowed)
     * @param array $params
     *            parameter values if placeholders in SQL
     */
    public function update($sql, $params = null)
    {
        $this->exec_stmt($sql, $params);
    }

    /**
     * Perform an insert , sql should be "INSERT"
     *
     * @param string $sql
     *            :INSERT statement SQL (placeholders allowed)
     * @param array $params
     *            : placeholder replacements (can be null)
     * @return mixed : last inserted id
     */
    public function insert($sql, $params = null)
    {
        $this->exec_stmt($sql, $params);
        $liid = $this->_db->lastInsertId();
        return $liid;
    }

    /**
     * Perform a select ,sql should be "SELECT"
     *
     * @param string $sql
     *            :SELECT statement SQL (placeholders allowed)
     * @param array $params
     *            : placeholder replacements (can be null)
     * @return PDOStatement : statement instance for further processing
     */
    public function select($sql, $params = null)
    {
        return $this->exec_stmt($sql, $params, false);
    }

    /**
     * Selects one unique value from one single row
     *
     * @param $sql :
     *            SELECT statement SQL (placeholders allowed)
     * @param $params :placeholder
     *            replacements (can be null)
     * @param $col :
     *            column value to retrieve
     * @return mixed : null if not result , wanted column value if match
     */
    public function selectone($sql, $params, $col)
    {
        $stmt = $this->select($sql, $params);
        $this->_timecounter->initTime("indb", null, $this->_tcats);
        $t0 = microtime(true);
        
        $r = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        $this->_timecounter->exitTime("indb", null, $this->_tcats);
        
        $t1 = microtime(true);
        
        $this->_indbtime += $t1 - $t0;
        $v = (is_array($r) ? $r[$col] : null);
        unset($r);
        return $v;
    }

    /**
     * Selects all values from a statement into a php array
     *
     * @param unknown_type $sql
     *            sql select to execute
     * @param unknown_type $params
     *            placeholder replacements (can be null)
     */
    public function selectAll($sql, $params = null)
    {
        $stmt = $this->select($sql, $params);
        $this->_timecounter->initTime("indb", null, $this->_tcats);
        
        $t0 = microtime(true);
        
        $r = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        $this->_timecounter->exitTime("indb", null, $this->_tcats);
        
        $t1 = microtime(true);
        $this->_indbtime += $t1 - $t0;
        return $r;
    }

    /**
     * test if value exists (test should be compatible with unique select)
     *
     * @param $sql :
     *            SELECT statement SQL (placeholders allowed)
     * @param $params :placeholder
     *            replacements (can be null)
     * @param $col :
     *            column value to retrieve
     * @return boolean : true if value found, false otherwise
     */
    public function testexists($sql, $params, $col)
    {
        return $this->selectone($sql, $params, $col) != null;
    }

    /**
     * Quote array values in order to be used as parameters (handy if array used directly in explode in a IN condition)
     *
     * @param array $arr
     *            array of values to be quoted
     */
    public function quotearr($arr)
    {
        $arrout = array();
        foreach ($arr as $v)
        {
            $arrout[] = $this->_db->quote($v);
        }
        return $arrout;
    }

    /**
     * transforms an array in a comma separated list of enclosed column names for request
     *
     * @param array $arr
     *            list of names to enclose
     */
    public function arr2columns($arr)
    {
        $arrout = array();
        foreach ($arr as $cname)
        {
            $arrout[] = "`" . $cname . "`";
        }
        $colstr = implode(",", $arrout);
        unset($arrout);
        return $colstr;
    }

    /**
     * transform an array of values into equivalent comma separated list of unnamed placeholders.
     *
     * @param array $arr            
     */
    public function arr2values($arr)
    {
        $str = substr(str_repeat("?,", count($arr)), 0, -1);
        return $str;
    }

    /**
     * transform a list of values into static select to use it as SQL static resultset
     *
     * @param array $arr
     *            list of values to use as SQL dataset
     * @param string $cname
     *            sql column name to use to represent dataset
     */
    public function arr2select($arr, $cname = "id")
    {
        $rpt = str_repeat("? AS $cname UNION SELECT ", count($arr));
        $subsel = substr($rpt, 0, -1 * strlen(" UNION SELECT "));
        return "(SELECT $subsel)";
    }

    /**
     * transform associative array into CASE sub statement
     */
    public function arr2case($arr, $casevar)
    {
        $sql = "(CASE ";
        foreach ($arr as $k => $v)
        {
            $sql .= "WHEN $casevar='$k' THEN '$v'\n";
        }
        $sql .= "END)";
        return $sql;
    }

    /**
     * transform a associative array into a list of update prepared placeholders
     *
     * @param array $arr
     *            associative array to prepare for update , array keys used as column to update
     */
    public function arr2update($arr)
    {
        $arrout = array();
        foreach ($arr as $k => $v)
        {
            $arrout[] = "$k=?";
        }
        $updstr = implode(",", $arrout);
        unset($arrout);
        return $updstr;
    }

    /**
     * Filters a key value array over a list of keys , replacing __NULL__ magic value with true null
     *
     * @param unknown_type $kvarr            
     * @param unknown_type $keys            
     */
    public function filterkvarr($kvarr, $keys)
    {
        $out = array();
        foreach ($keys as $k)
        {
            $out[$k] = (isset($kvarr[$k]) && $kvarr[$k] !== '__NULL__') ? $kvarr[$k] : null;
        }
        return $out;
    }

    /**
     * begins a transaction
     */
    public function beginTransaction()
    {
        $this->_db->beginTransaction();
        $this->_intrans = true;
        //$this->logdebug("-- TRANSACTION BEGIN --");
    }

    /**
     * commits the current transaction
     */
    public function commitTransaction()
    {
        $this->_db->commit();
        $this->_intrans = false;
        //$this->logdebug("-- TRANSACTION COMMIT --");
    }

    /**
     * rollback the current transaction
     */
    public function rollbackTransaction()
    {
        if ($this->_intrans)
        {
            $this->_db->rollBack();
            $this->_intrans = false;
           // $this->logdebug("-- TRANSACTION ROLLBACK --");
        }
    }

    /**
     * Sets debug management
     *
     * @param bool $debug
     *            debug flag
     * @param string $debugfname
     *            debug file name to use
     */
    public function setDebug($debug, $debugfname)
    {
        $this->_debug = $debug;
        $this->_debugfile = $debugfname;
    }

    /**
     * Replaces named params in a descriptive parameterized request
     * Descriptive parameterized request have parameters defined as
     * [namespace:name/label/default value] , this parameters may represent table names or any request parameter
     * namespace is optional, as label & default value
     * - NameSpaces:
     * tn : tablename, this namespace ensures replacement of given name with defined DB prefix so, parameterized request can use generic names to define their ops
     *
     * @param unknown_type $stmt            
     * @param unknown_type $rparams            
     */
    public function replaceParams(&$stmt, &$rparams)
    {
        $params = array();
        $hasp = preg_match_all('|\[\[(.*?)\]\]|msi', $stmt, $matches);
        if ($hasp)
        {
            $pdefs = $matches[0];
            $params = $matches[1];
        }
        $cparams = count($params);
        for ($i = 0; $i < $cparams; $i++)
        {
            $param = $params[$i];
            $pdef = $pdefs[$i];
            $pinfo = explode("/", $param);
            $pname = $pinfo[0];
            $epar = explode(":", $pname);
            if (count($epar) > 1)
            {
                $stmt = str_replace($pdef, $rparams[$pname], $stmt);
            }
            else
            {
                $stmt = str_replace($pdef, ":$pname", $stmt);
            }
        }
        for ($i = 0; $i < $cparams; $i++)
        {
            $param = $params[$i];
            $pinfo = explode("/", $param);
            $pname = $pinfo[0];
            $epar = explode(":", $pname);
            if (count($epar) > 1)
            {
                unset($rparams[$pname]);
            }
        }
    }

    /**
     * Checks wether an array is associative
     *
     * @param mixed $var
     *            array or variable to test
     */
    public function is_assoc($var)
    {
        return is_array($var) && array_keys($var) !== range(0, sizeof($var) - 1);
    }

    /**
     * This method handled mutiple statements in a single SQL (PDO cannot do it by itself)
     * Statements have to be separed by ; & line return.
     *
     * @param string $sql
     *            multiple statements
     * @param array $params
     *            values to use for parameter placeholder, in case of named parameters,array has to have array keys aligned with parameter names
     */
    public function multipleParamRequests($sql, $params, $return = false)
    {
        // ensure windows/mac compatibility for user made requests
        $sql = str_replace("\r\n", "\n", $sql);
        $sqllines = explode("--", $sql);
        foreach ($sqllines as $sqlline)
        {
            if ($sqlline != "")
            {
                $subs = explode(";\n", "--" . $sqlline);
                foreach ($subs as $sub)
                {
                    
                    if (trim($sub) != "" && substr($sub, 0, 2) != "--")
                    {
                        $stmts[] = $sub;
                    }
                }
            }
        }
        $results = array();
        foreach ($stmts as $stmt)
        {
            $zparams = $params;
            $this->replaceParams($stmt, $zparams);
            if ($return)
            {
                if (substr(trim($stmt), 0, 6) == "SELECT")
                {
                    $results[$stmt] = $this->selectAll($stmt, $zparams);
                    continue;
                }
            }
            $this->exec_stmt($stmt, $zparams);
        }
        if ($return)
        {
            return $results;
        }
    }
}
