<?php
/*
 * Initial test requirements
 */
require_once(__DIR__."/../../inc/magmi_defs.php");
require_once("magmi_config.php");

require_once(__DIR__."/../../integration/inc/magmi_datapump.php");

/*
 * Will use datapump for testing specific cases
 */

class Suite1Test extends  PHPUnit_Framework_TestCase
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
	
    public function testBasicItem()
    {
        $conf=Magmi_Config::getInstance();
        $conf->load(__DIR__."/test.ini");
        $dp=Magmi_DataPumpFactory::getDataPumpInstance("productimport");
        $item=array("sku"=>"I0001",
                    "name"=>"test item",
                    "description"=>"test description",
                    "short_description"=>"test short desc",
                    "weight"=>0,
                    "price"=>10,
                    "qty"=>1,
                    "category_ids"=>"2");
        $dp->beginImportSession("baseprofile","create");
        $dp->ingest($item);
        $dp->endImportSession();
    }
    
        
    public function testImgDL()
    {
    	$conf=Magmi_Config::getInstance();
    	$conf->load(__DIR__."/test.ini");
    	
        $dp=Magmi_DataPumpFactory::getDataPumpInstance("productimport");
        $item=array("sku"=>"I0001_img",
                    "name"=>"test item with image",
                    "description"=>"test description",
                    "short_description"=>"test short desc",
                    "weight"=>0,
                    "price"=>10,
                    "qty"=>1,
                    "category_ids"=>"2",
        			"image"=>"http://be.eurocircuits.com/imgdownload.aspx?id=120291-91&type=articleimage&index=0&size=Large&name=120291-91",
        			"small_image"=>"http://be.eurocircuits.com/imgdownload.aspx?id=120291-91&type=articleimage&index=0&size=Large&name=120291-91",
           			"thumbnail"=>"http://be.eurocircuits.com/imgdownload.aspx?id=120291-91&type=articleimage&index=0&size=Large&name=120291-91");
        $dp->beginImportSession("multiplugins","create");
        $dp->ingest($item);
        $dp->endImportSession();
    }
    
    public function testImgDLMulti()
    {
    	$conf=Magmi_Config::getInstance();
    	$conf->load(__DIR__."/test.ini");
    	 
    	$dp=Magmi_DataPumpFactory::getDataPumpInstance("productimport");
    	$dp->beginImportSession("multiplugins","create");
    	for($i=0;$i<50;$i++)
    	{
    	$item=array("sku"=>"I".str_pad($i, 4,"0",STR_PAD_LEFT)."_img",
    			"name"=>"test item with image $i",
    			"description"=>"test description",
    			"short_description"=>"test short desc",
    			"weight"=>0,
    			"price"=>10,
    			"qty"=>1,
    			"category_ids"=>"2",
    			"image"=>"http://be.eurocircuits.com/imgdownload.aspx?id=120291-91&type=articleimage&index=0&size=Large&name=120291-91&fmt=.png",
    			"small_image"=>"http://be.eurocircuits.com/imgdownload.aspx?id=120291-91&type=articleimage&index=0&size=Medium&name=120291-91&fmt=.png",
    			"thumbnail"=>"http://be.eurocircuits.com/imgdownload.aspx?id=120291-91&type=articleimage&index=0&size=Small&name=120291-91&fmt=.png");
    	
    	$dp->ingest($item);
    	unset($item);
    	}
    	$dp->endImportSession();
    	 
    }
}
