<?php

require_once("magmi_engine.php");

/**
 * This class is the new authentication class for magmi
 * Alot of the methods are based on code from Magento
 *
 * @author liam-wiltshire
 *
 */

class Magmi_Auth extends Magmi_Engine
{
    private $user;
    private $pass;

    public function __construct($user, $pass)
    {
        parent::__construct();
        $this->user = $user;
        $this->pass = $pass;
        $this->initialize();
        try {
            $this->connectToMagento();
            $this->_hasDB = true;
            $this->disconnectFromMagento();
        } catch (Exception $e) {
            $this->_hasDB = false;
        }
    }


    public function authenticate()
    {
        if (!$this->_hasDB) {
            die("Please create magmi.ini file in magmi/conf directory , by copying & editing magmi.ini.default file and filling appropriate values");
        }
        $tn = $this->tablename('admin_user');
        $result = $this->select("SELECT * FROM $tn WHERE username = ?", array($this->user))->fetch(PDO::FETCH_ASSOC);
        return $this->validatePass($result['password'], $this->pass);
    }

    /**
     * @param string $dbHash from magento db
     * @param string $pass provided by user
     * @return bool
     */
    private function validatePass($dbHash, $pass)
    {
        #first try : standard CE magento hash

        $hash = explode(":", $dbHash);
        $hashSuffix = $hash[1] ?? '';
        $cecheck = md5($hashSuffix . $pass);
        $eecheck = hash('sha256', $hashSuffix . $pass);
        $valid = ($cecheck == $hash[0] || $eecheck == $hash[0]);

        return $valid;
    }

    public function engineInit($params)
    {
        // TODO: Implement engineInit() method.
    }

    public function engineRun($params)
    {
        // TODO: Implement engineRun() method.
    }
}
