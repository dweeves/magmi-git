<?php
/**
 * Created by PhpStorm.
 * User: seb
 * Date: 30/03/15
 * Time: 16:01
 */

require_once(dirname(dirname(__DIR__)).'/utils.php');
$conf=getSessionConfig();
$mdir=$conf->getMagentoDir();

require_once($mdir.'/app/Mage.php');

?>
<h4>Magento Version</h4>
<?php echo Mage::getEdition() .' '.Mage::getVersion();?>