<?php
/*
 * Database helper using pdo
 */
class DBHelper
{
	protected $_db;
	protected $_debug;
	protected $_laststmt;
	protected $_use_stmt_cache=true;
	protected $_nreq;
	protected $_indbtime;
	protected $_intrans=false;
	/**
	 * Intializes database connection
	 * @param string $host : hostname
	 * @param string $dbname : database name
	 * @param string $user : username
	 * @param string $pass : password
	 * @param bool $debug : debug mode
	 */
	public function initDb($host,$dbname,$user,$pass,$debug=false)
	{
		//intialize connection with PDO
		//fix by Mr Lei for UTF8 special chars
		$this->_db=new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass,array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"));
		//use exception error mode
		$this->_db->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
		//use fetch assoc as default fetch mode
		$this->_db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE,PDO::FETCH_ASSOC);
		//set database debug mode to trace if necessary
		$this->_debug=$debug;
		$this->prepared=array();

	}

	public function logdebug($data)
	{
		if($this->_debug)
		{
			$f=fopen($this->_debugfile,"a");
			fwrite($f,microtime());
			fwrite($f,$data);
			fwrite($f,"\n");
			fclose($f);
		}
	}
	public function usestmtcache($uc)
	{
		$this->_use_stmt_cache=$uc;
	}
	/**
	 * releases database connection
	 */
	public function exitDb()
	{
		//clear PDO resource
		$this->_db=NULL;
			
	}

	public function initDbqStats()
	{
		$this->_nreq=0;
		$this->_indbtime=0;
	}

	public function collectDbqStats(&$nbreq)
	{
		return $this->_nreq;
	}
	/**
	 * executes an sql statement
	 * @param string $sql : sql statement (may include ? placeholders)
	 * @param array $params : parameters to replace placeholders (can be null)
	 * @param boolean $close : auto close cursor after statement execution (defaults to true)
	 * @return PDOStatement : statement for further processing if needed
	 */
	public function exec_stmt($sql,$params=null,$close=true)
	{
		$this->_nreq++;
		$t0=microtime(true);
		if($this->_use_stmt_cache)
		{
			//if sql not in statement cache
			if(!isset($this->prepared[$sql]))
			{
				//create new prepared statement
				$stmt=$this->_db->prepare($sql);
				//cache prepare statement
				$this->prepared[$sql]=$stmt;
			}
			else
			{
				//get from statement cache
				$stmt=$this->prepared[$sql];
			}
		}
		else
		{
			//create new prepared statement
			$stmt=$this->_db->prepare($sql);
		}
		$this->_laststmt=$stmt;
		if($params!=null)
		{
			$params=is_array($params)?$params:array($params);
			$stmt->execute($params);
		}
		else
		{

			$stmt->execute();
		}
		if($close)
		{
			$stmt->closeCursor();
		}
		$t1=microtime(true);
		$this->_indbtime+=$t1-$t0;
		$this->logdebug("$sql\n".print_r($params,true));
		unset($params);
		return $stmt;
	}

	/**
	 * Perform a delete statement, sql should be "DELETE"
	 * @param string $sql : DELETE statement sql (placeholders allowed)
	 * @param array $params : placeholder replacements (can be null)
	 */
	public function delete($sql,$params=null)
	{
			$this->exec_stmt($sql,$params);
	}

	public function update($sql,$params=null)
	{
			$this->exec_stmt($sql,$params);
	}
	/**
	 * Perform an insert , sql should be "INSERT"
	 * @param string $sql :INSERT statement SQL (placeholders allowed)
	 * @param array $params : placeholder replacements (can be null)
	 * @return mixed : last inserted id
	 */
	public function insert($sql,$params=null)
	{
		$this->exec_stmt($sql,$params);
		$liid=$this->_db->lastInsertId();
		return $liid;
	}

	/**
	 * Perform a select ,sql should be "SELECT"
	 * @param string $sql :SELECT statement SQL (placeholders allowed)
	 * @param array $params : placeholder replacements (can be null)
	 * @return PDOStatement : statement instance for further processing
	 */
	public function select($sql,$params=null)
	{
		return $this->exec_stmt($sql,$params,false);
	}

	/**
	 * Selects one unique value from one single row
	 * @param $sql : SELECT statement SQL (placeholders allowed)
	 * @param $params :placeholder replacements (can be null)
	 * @param $col : column value to retrieve
	 * @return mixed : null if not result , wanted column value if match
	 */
	public function selectone($sql,$params,$col)
	{
		$stmt=$this->select($sql,$params);
		$t0=microtime(true);

		$r=$stmt->fetch();
		$stmt->closeCursor();
		$t1=microtime(true);
		$this->_indbtime+=$t1-$t0;
		$v=(is_array($r)?$r[$col]:null);
		unset($r);
		return $v;
	}

	/**
	 * Selects all values from a statement into a php array
	 * @param unknown_type $sql sql select to execute
	 * @param unknown_type $params placeholder replacements (can be null)
	 */
	public function selectAll($sql,$params=null)
	{
		$stmt=$this->select($sql,$params);
		$t0=microtime(true);

		$r=$stmt->fetchAll();
		$stmt->closeCursor();
		$t1=microtime(true);
		$this->_indbtime+=$t1-$t0;
		return $r;
	}

	/**
	 * test if value exists (test should be compatible with unique select)
	 * @param $sql : SELECT statement SQL (placeholders allowed)
	 * @param $params :placeholder replacements (can be null)
	 * @param $col : column value to retrieve
	 * @return boolean : true if value found, false otherwise
	 */
	public function testexists($sql,$params,$col)
	{
		return $this->selectone($sql,$params,$col)!=null;
	}

	/**
	 * begins a transaction
	 */
	public function beginTransaction()
	{
		$this->_db->beginTransaction();
		$this->_intrans=true;
		$this->logdebug("-- TRANSACTION BEGIN --");
		
	}

	/**
	 * commits the current transaction
	 */
	public function commitTransaction()
	{
		$this->_db->commit();
		$this->_intrans=false;
		$this->logdebug("-- TRANSACTION COMMIT --");
		
	}

	/**
	 * rollback the current transaction
	 */
	public function rollbackTransaction()
	{
		if($this->_intrans)
		{
			$this->_db->rollBack();
			$this->_intrans=false;
			$this->logdebug("-- TRANSACTION ROLLBACK --");
			
		}
		
	}
	
	public function setDebug($debug,$debugfname)
	{
		$this->_debug=$debug;	
		$this->_debugfile=$debugfname;
	}
	
}
