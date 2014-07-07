<?php
require_once(__DIR__."/../../inc/magmi_defs.php");
require_once("magmi_config.php");

require_once(__DIR__."/../../integration/inc/magmi_datapump.php");

class ConfigTest extends  PHPUnit_Framework_TestCase
{
    public function testConfigFromCustomFile()
    {
        $conf=Magmi_Config::getInstance();
        $conf->load(__DIR__."/test.ini");
        $this->assertEquals($conf->getMagentoDir(), "/data/mag_18");
    }
    
    
    public function testProfileConfigFromCustomFile()
    {
        $conf=Magmi_Config::getInstance();
        $conf->load(__DIR__."/test.ini");
        $dp=Magmi_DataPumpFactory::getDataPumpInstance("productimport");
        $dp->beginImportSession("testdummyprofile","create");
        $dp->endImportSession(); 
        $this->assertFileExists(__DIR__."/testdummyprofile/plugins.conf");
    }
    
    public function testExistingProfileFromCustomFile()
    {
        $conf=Magmi_Config::getInstance();
        $conf->load(__DIR__."/test.ini");
        $dp=Magmi_DataPumpFactory::getDataPumpInstance("productimport");
        $dp->beginImportSession("xmlimport","create");
        $ep=$dp->getEngine()->getPluginClasses();
        $this->assertContains('CategoryImporter',$ep['itemprocessors']);
        $this->assertContains('ImageAttributeItemProcessor',$ep['itemprocessors']);
        $this->assertContains('ItemIndexer',$ep['itemprocessors']);
        $this->assertContains('GenericMapperProcessor',$ep['itemprocessors']);
         
        $dp->endImportSession();
         
    }
}