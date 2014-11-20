<?php
/*
 * Initial test requirements
 */
require_once(__DIR__."/../../inc/magmi_defs.php");
require_once("magmi_config.php");
require_once("magmi_csvreader.php");
require_once("magmi_loggers.php");
require_once(__DIR__."/../../integration/inc/magmi_datapump.php");

/*
 * Will use datapump for testing specific cases
 */

class PluginSuiteTest extends  PHPUnit_Framework_TestCase
{
	
	public static function setupBeforeClass()
	{
		//copying magmi config to current test directory
		copy(__DIR__."/../../conf/magmi.ini",__DIR__."/test.ini");
	}
	
	public  static function tearDownAfterClass()
	{
		//remove test.ini
		unlink(__DIR__."/test.ini");
	}
	
    public function testBundle()
    {
        $conf=Magmi_Config::getInstance();
        $conf->load(__DIR__."/test.ini");
        $dp=Magmi_DataPumpFactory::getDataPumpInstance("productimport");
		$reader=new Magmi_CSVReader();
		$reader->initialize(array("CSV:filename"=>__DIR__.'/bundle/test_bundle_product.csv'));
		$reader->openCSV();
		$reader->getColumnNames();
        $eng=$dp->getEngine();
        $dp->beginImportSession("bundleconf","create",new FileLogger(__DIR__.'/test.log'));
        while ($item=$reader->getNextRecord())
		{
			$item["category_ids"]=2;
        	$dp->ingest($item);
		}
        $dp->endImportSession();
        $reader->closeCSV();
    }

    public function testCatMultiRoot()
    {
        $conf=Magmi_Config::getInstance();
        $conf->load(__DIR__."/test.ini");
        $dp=Magmi_DataPumpFactory::getDataPumpInstance("productimport");
      	$reader=new Magmi_CSVReader();
      	$reader->initialize(array("CSV:filename"=>__DIR__.'/categories/category_multiroot.csv'));
      	$reader->openCSV();
      	$reader->getColumnNames();
        $dp->beginImportSession("catconf","create",new FileLogger(__DIR__.'/test.log'));
        while ($item=$reader->getNextRecord()) {
            $dp->ingest($item);
        }
        $dp->endImportSession();
        $reader->closeCSV();
    }


   
}
