<?php
/*
 * Initial test requirements
 */
require_once(__DIR__ . "/../../inc/magmi_defs.php");
require_once("magmi_config.php");

require_once(__DIR__ . "/../../integration/inc/magmi_datapump.php");

/*
 * Will use datapump for testing specific cases
 */

class Suite1Test extends PHPUnit_Framework_TestCase
{

    public static function setupBeforeClass()
    {
        //copying magmi config to current test directory
        copy(__DIR__ . "/../../conf/magmi.ini", __DIR__ . "/test.ini");
    }

    public static function tearDownAfterClass()
    {
        //remove test.ini
        unlink(__DIR__ . "/test.ini");
    }

    public function testBasicItem()
    {
        $conf = Magmi_Config::getInstance();
        $conf->load(__DIR__ . "/test.ini");
        $dp = Magmi_DataPumpFactory::getDataPumpInstance("productimport");
        $item = array("sku" => "I0001",
            "name" => "test item",
            "description" => "test description",
            "short_description" => "test short desc",
            "weight" => 0,
            "price" => 10,
            "qty" => 1,
            "category_ids" => "2");
        $dp->beginImportSession("baseprofile", "create", new FileLogger(__DIR__ . "/log_" . __FUNCTION__ . ".txt"));
        $dp->ingest($item);
        $dp->endImportSession();
    }


    public function testImgDL()
    {
        $conf = Magmi_Config::getInstance();
        $conf->load(__DIR__ . "/test.ini");

        $dp = Magmi_DataPumpFactory::getDataPumpInstance("productimport");
        $item = array("sku" => "I0001_img",
            "name" => "test item with image",
            "description" => "test description",
            "short_description" => "test short desc",
            "weight" => 0,
            "price" => 10,
            "qty" => 1,
            "category_ids" => "2",
            "image" => "http://be.eurocircuits.com/imgdownload.aspx?id=120291-91&type=articleimage&index=0&size=Large&name=120291-91",
            "small_image" => "http://be.eurocircuits.com/imgdownload.aspx?id=120291-91&type=articleimage&index=0&size=Large&name=120291-91",
            "thumbnail" => "http://be.eurocircuits.com/imgdownload.aspx?id=120291-91&type=articleimage&index=0&size=Large&name=120291-91");
        $dp->beginImportSession("multiplugins", "create", new FileLogger(__DIR__ . "/log_" . __FUNCTION__ . ".txt"));
        $dp->ingest($item);
        $dp->endImportSession();
    }

    public function testImgDLBad()
    {
        $conf = Magmi_Config::getInstance();
        $conf->load(__DIR__ . "/test.ini");

        $dp = Magmi_DataPumpFactory::getDataPumpInstance("productimport");
        $item = array("sku" => "I0001_img",
            "name" => "test item with image",
            "description" => "test description",
            "short_description" => "test short desc",
            "weight" => 0,
            "price" => 10,
            "qty" => 1,
            "category_ids" => "2",
            "image" => "badimage.jpg",
        );
        $dp->beginImportSession("multiplugins", "create", new FileLogger(__DIR__ . "/log_" . __FUNCTION__ . ".txt"));
        $dp->ingest($item);
        $dp->endImportSession();
    }

    public function testImgDLMulti()
    {
        $conf = Magmi_Config::getInstance();
        $conf->load(__DIR__ . "/test.ini");

        $dp = Magmi_DataPumpFactory::getDataPumpInstance("productimport");
        $dp->beginImportSession("multiplugins", "create", new FileLogger(__DIR__ . "/log_" . __FUNCTION__ . ".txt"));
        for ($i = 0; $i < 50; $i++) {
            $item = array("sku" => "I" . str_pad($i, 4, "0", STR_PAD_LEFT) . "_img",
                "name" => "test item with image $i",
                "description" => "test description",
                "short_description" => "test short desc",
                "weight" => 0,
                "price" => 10,
                "qty" => 1,
                "category_ids" => "2",
                "image" => "http://be.eurocircuits.com/imgdownload.aspx?id=120291-91&type=articleimage&index=0&size=Large&name=120291-91&fmt=.png",
                "small_image" => "http://be.eurocircuits.com/imgdownload.aspx?id=120291-91&type=articleimage&index=0&size=Medium&name=120291-91&fmt=.png",
                "thumbnail" => "http://be.eurocircuits.com/imgdownload.aspx?id=120291-91&type=articleimage&index=0&size=Small&name=120291-91&fmt=.png");

            $dp->ingest($item);
            unset($item);
        }
        $dp->endImportSession();

    }

    public function testConfig()
    {
        $conf = Magmi_Config::getInstance();
        $conf->load(__DIR__ . "/test.ini");

        $dp = Magmi_DataPumpFactory::getDataPumpInstance("productimport");
        $dp->beginImportSession("configurable", "create", new FileLogger(__DIR__ . "/log_" . __FUNCTION__ . ".txt"));
        //import 10 simples
        $stores = array("en", "us");
        foreach ($stores as $st) {
            for ($i = 0; $i < 10; $i++) {
                $basecolor = "c" . ($i % 10);
                $item = array("store" => $st,
                    "sku" => "S" . str_pad($i, 4, "0", STR_PAD_LEFT) . "_item",
                    "name" => "simple item $i",
                    "description" => "test description",
                    "short_description" => "test short desc",
                    "type" => "simple",
                    "attribute_set" => "apparel",
                    "weight" => 0,
                    "price" => 10,
                    "qty" => 1,
                    "color" => $basecolor,
                    "category_ids" => "2",
                    "visibility" => "1");
                $dp->ingest($item);
                unset($item);
            }
        }
        //import configurable
        $item = array("sku" => "C000",
            "name" => "config item 0",
            "description" => "config desc",
            "short_description" => "config short",
            "type" => "configurable",
            "price" => 20,
            "is_in_stock" => 1,
            "attribute_set" => "apparel",
            "configurable_attributes" => "color",
            "simples_skus" => "S0001_item,S0003_item,S0005_item",
            "super_attribute_pricing" => "color::c1:10;c3:15;c5:18",
            "category_ids" => "2",
            "visibility" => "4");
        $dp->ingest($item);
        $dp->endImportSession();
    }


    public function testBaseOptions()
    {
        $conf = Magmi_Config::getInstance();
        $conf->load(__DIR__ . "/test.ini");

        $dp = Magmi_DataPumpFactory::getDataPumpInstance("productimport");
        $dp->beginImportSession("baseprofile", "create", new FileLogger(__DIR__ . "/log_" . __FUNCTION__ . ".txt"));
        //import 100 simples
        for ($i = 0; $i < 100; $i++) {
            $basecolor = "c" . ($i % 10);
            $item = array("store" => "admin",
                "sku" => "S" . str_pad($i, 4, "0", STR_PAD_LEFT) . "_item",
                "name" => "simple item $i",
                "description" => "test description",
                "short_description" => "test short desc",
                "type" => "simple",
                "attribute_set" => "apparel",
                "weight" => 0,
                "price" => 10,
                "qty" => 1,
                "color" => $basecolor,
                "category_ids" => "2",
                "visibility" => "4");
            $dp->ingest($item);
            unset($item);
        }

        $dp->endImportSession();
    }


    public function testPosOptions()
    {
        $conf = Magmi_Config::getInstance();
        $conf->load(__DIR__ . "/test.ini");

        $dp = Magmi_DataPumpFactory::getDataPumpInstance("productimport");
        $dp->beginImportSession("baseprofile", "create", new FileLogger(__DIR__ . "/log_" . __FUNCTION__ . ".txt"));
        //import 100 simples
        for ($i = 0; $i < 100; $i++) {
            $basecolor = "c" . ($i % 10);
            $item = array("store" => "admin",
                "sku" => "S" . str_pad($i, 4, "0", STR_PAD_LEFT) . "_item",
                "name" => "simple item $i",
                "description" => "test description",
                "short_description" => "test short desc",
                "type" => "simple",
                "attribute_set" => "apparel",
                "weight" => 0,
                "price" => 10,
                "qty" => 1,
                "color" => $basecolor . "||" . ($i % 10),
                "category_ids" => "2",
                "visibility" => "4");
            $dp->ingest($item);
            unset($item);
        }
        //inverting positions
        for ($i = 0; $i < 10; $i++) {
            $basecolor = "c" . ($i % 10);
            $item = array("store" => "admin",
                "sku" => "S" . str_pad($i, 4, "0", STR_PAD_LEFT) . "_item",
                "color" => $basecolor . "||" . (9 - $i));
            $dp->ingest($item);
            unset($item);
        }

        $dp->endImportSession();
    }

    public function testTranslatedOptions()
    {
        $conf = Magmi_Config::getInstance();
        $conf->load(__DIR__ . "/test.ini");

        $dp = Magmi_DataPumpFactory::getDataPumpInstance("productimport");
        $dp->beginImportSession("baseprofile", "create", new FileLogger(__DIR__ . "/log_" . __FUNCTION__ . ".txt"));
        //import 10 simples
        $stores = array("en", "us");
        foreach ($stores as $st) {
            for ($i = 0; $i < 100; $i++) {
                $basecolor = "c" . ($i % 10);
                $item = array("store" => $st,
                    "sku" => "S" . str_pad($i, 4, "0", STR_PAD_LEFT) . "_item",
                    "name" => "simple item toption $i",
                    "description" => "test description",
                    "short_description" => "test short desc",
                    "type" => "simple",
                    "attribute_set" => "apparel",
                    "weight" => 0,
                    "price" => 10,
                    "qty" => 1,
                    "color" => $basecolor . "_" . $st . "::[$basecolor]",
                    "category_ids" => "2",
                    "visibility" => "4");
                $dp->ingest($item);
                unset($item);
            }
        }
        $dp->endImportSession();
    }

    public function testMultiSelect()
    {
        $conf = Magmi_Config::getInstance();
        $conf->load(__DIR__ . "/test.ini");
        $mselvals = array("v1" => 1, "v2" => 1, "v3" => 1, "v4" => 1, "v5" => 1, "v6" => 1, "v7" => 1);
        $dp = Magmi_DataPumpFactory::getDataPumpInstance("productimport");
        $dp->beginImportSession("baseprofile", "create", new FileLogger(__DIR__ . "/log_" . __FUNCTION__ . ".txt"));
        //import 100 simples
        for ($i = 0; $i < 100; $i++) {
            $selvals = array_rand($mselvals, 3);
            $item = array("sku" => "TM" . str_pad($i, 4, "0", STR_PAD_LEFT) . "_item",
                "name" => "simple item msel $i",
                "description" => "test description",
                "short_description" => "test short desc",
                "type" => "simple",
                "attribute_set" => "apparel",
                "weight" => 0,
                "price" => 10,
                "qty" => 1,
                "test_multiselect" => implode(",", $selvals),
                "visibility" => "2",
            );
            $dp->ingest($item);
            unset($item);
        }


    }


    public function testUrlKey()
    {
        $conf = Magmi_Config::getInstance();
        $conf->load(__DIR__ . "/test.ini");

        $dp = Magmi_DataPumpFactory::getDataPumpInstance("productimport");
        $dp->beginImportSession("baseprofile", "create", new FileLogger(__DIR__ . "/log_" . __FUNCTION__ . ".txt"));
        //import 10 simples
        for ($i = 0; $i < 10; $i++) {
            $item = array("sku" => "SG" . str_pad($i, 4, "0", STR_PAD_LEFT) . "_item",
                "name" => "simple item $i",
                "description" => "test description",
                "short_description" => "test short desc",
                "type" => "simple",
                "attribute_set" => "apparel",
                "weight" => 0,
                "price" => 10,
                "qty" => 1,
                "visibility" => $i % 2 == 0 ? "1" : "4",
                "category_ids" => 2,
                "url_rewrite" => 1,
                "url_key" => "item");
            $dp->ingest($item);
            unset($item);
        }


    }

    public function testMassItem()
    {
        $conf = Magmi_Config::getInstance();
        $conf->load(__DIR__ . "/test.ini");

        $dp = Magmi_DataPumpFactory::getDataPumpInstance("productimport");
        $dp->beginImportSession("grouped", "create", new FileLogger(__DIR__ . "/log_" . __FUNCTION__ . ".txt"));
        //import 10 simples
        for ($i = 0; $i < 10000; $i++) {
            $item = array("sku" => "S" . str_pad($i, 4, "0", STR_PAD_LEFT) . "_item",
                "name" => "simple item $i",
                "description" => "test description",
                "short_description" => "test short desc",
                "type" => "simple",
                "attribute_set" => "apparel",
                "weight" => 0,
                "price" => 10,
                "qty" => 1,
                "visibility" => "1",
                "url_key" => "item " . $i);
            $dp->ingest($item);
            unset($item);
        }
    }

    public function testGrouped()
    {
        $conf = Magmi_Config::getInstance();
        $conf->load(__DIR__ . "/test.ini");

        $dp = Magmi_DataPumpFactory::getDataPumpInstance("productimport");
        $dp->beginImportSession("grouped", "create", new FileLogger(__DIR__ . "/log_" . __FUNCTION__ . ".txt"));
        //import 10 simples
        for ($i = 0; $i < 10; $i++) {
            $item = array("sku" => "SG" . str_pad($i, 4, "0", STR_PAD_LEFT) . "_item",
                "name" => "simple item $i",
                "description" => "test description",
                "short_description" => "test short desc",
                "type" => "simple",
                "attribute_set" => "apparel",
                "weight" => 0,
                "price" => 10,
                "qty" => 1,
                "visibility" => "1");
            $dp->ingest($item);
            unset($item);
        }
        //import configurable
        $item = array("sku" => "G000",
            "name" => "grouped item 0",
            "description" => "config desc",
            "short_description" => "config short",
            "type" => "grouped",
            "is_in_stock" => 1,
            "attribute_set" => "apparel",
            "configurable_attributes" => "color",
            "grouped_skus" => "SG0001_item,SG0003_item,SG0005_item",
            "category_ids" => "2",
            "visibility" => "4");
        $dp->ingest($item);
        $dp->endImportSession();
    }
}



