<?php

require_once("magmi_engine.php");

/**
 * This class is the new authentication class for magmi
 * Alot of the methods are based on code from Magento
 *
 * @author liam-wiltshire
 *
 */

class Magmi_Auth extends Magmi_Engine {
    
    private $user;
    private $pass;

    public function __construct($user,$pass){
        parent::__construct();
        $this->user = $user;
        $this->pass = $pass;
        $this->initialize();
        try {
			$this->connectToMagento();
			$this->_hasDB = true;
            $this->disconnectFromMagento();
		}catch (Exception $e){
			$this->_hasDB = false;
		}
        
    }

    
    public function authenticate(){
		if (!$this->_hasDB) return ($this->user == 'magmi' && $this->pass == 'magmi');
		$tn=$this->tablename('admin_user');
        $result = $this->select("SELECT * FROM $tn WHERE username = ?",array($this->user))->fetch(PDO::FETCH_ASSOC);
        return $result && $this->validatePass($result['password'],$this->pass);
    }
    
    private function validatePass($hash,$pass){
        #first try : standard CE magento hash

        $hash = explode(":",$hash);
        $cecheck = md5($hash[1] . $pass);
        $eecheck = hash('sha256',$hash[1] . $pass);
        $eecheckArgo = $this->getArgonHash($pass, $hash[1]);
        $valid = ($cecheck == $hash[0] || $eecheck == $hash[0] || $eecheckArgo == $hash[0]);

        return $valid;
    }
	
    /**
     * Generate Argon2ID13 hash.
     * Got from \Magento\Framework\Encryption\Encryptor
     *
     * @param string $data
     * @param string $salt
     * @return string      
     */	
    private function getArgonHash($data, $salt = ''){
        $salt = empty($salt) ?
            random_bytes(SODIUM_CRYPTO_PWHASH_SALTBYTES) :
            substr($salt, 0, SODIUM_CRYPTO_PWHASH_SALTBYTES);

        if (strlen($salt) < SODIUM_CRYPTO_PWHASH_SALTBYTES) {
            $salt = str_pad($salt, SODIUM_CRYPTO_PWHASH_SALTBYTES, $salt);
        }

        return bin2hex(sodium_crypto_pwhash(
            SODIUM_CRYPTO_SIGN_SEEDBYTES,
            $data,
            $salt,
            SODIUM_CRYPTO_PWHASH_OPSLIMIT_INTERACTIVE,
            SODIUM_CRYPTO_PWHASH_MEMLIMIT_INTERACTIVE,
            SODIUM_CRYPTO_PWHASH_ALG_ARGON2ID13
        ));
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
