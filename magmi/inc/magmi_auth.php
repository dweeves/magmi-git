<?php

require_once("dbhelper.class.php");
require_once("magmi_config.php");

/**
 * This class is the new authentication class for magmi
 * Alot of the methods are based on code from Magento
 *
 * @author liam-wiltshire
 *
 */

class Magmi_Auth extends DBHelper {
    
    private $user;
    private $pass;
    private $tablename;
    protected $_conf;
	protected $_hasDB = false;
    
    public function __construct($user,$pass){
        parent::__construct();
        $this->user = $user;
        $this->pass = $pass;
        $this->_conf = Magmi_Config::getInstance();
        $this->_conf->load();
        
        $host = $this->_conf->get("DATABASE","host","localhost");
        $dbname = $this->_conf->get("DATABASE","dbname","magento");
        $user = $this->_conf->get("DATABASE","user");
        $pass = $this->_conf->get("DATABASE","password");
        $port = $this->_conf->get("DATABASE","port", "3306");
		$socket = $this->_conf->get("DATABASE","unix_socket", false);
        
		try {
			$this->initDb($host, $dbname, $user, $pass, $port, $socket);        
			$this->tablename = $this->_conf->get("DATABASE", "table_prefix") . "admin_user";
			$this->_hasDB = true;
		}catch (Exception $e){
			$this->_hasDB = false;
		}
        
    }
    
    public function authenticate(){
		if (!$this->_hasDB) return ($this->user == 'magmi' && $this->pass == 'magmi');
		
        $result = $this->select("SELECT * FROM {$this->tablename} WHERE username = ?",array($this->user))->fetch(PDO::FETCH_ASSOC);
        return $this->validatePass($result['password'],$this->pass);
    }
    
    private function validatePass($hash,$pass){
        $hash = explode(":",$hash);
        
        $check = md5($hash[1] . $pass);
        
        return $check == $hash[0];
    }
    
}