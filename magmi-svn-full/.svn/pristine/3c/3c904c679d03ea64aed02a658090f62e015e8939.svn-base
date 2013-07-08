<?php
 require_once("./magmi_datapump.php");
 $dp=Magmi_DataPumpFactory::getDataPumpInstance("productimport");
 $dp->setDefaultValues(array("visibility"=>2));
 $dp->beginImportSession("test_ptj","create");
 $dp->ingest(array("sku"=>"00000","name"=>"item1","description"=>"test","price"=>"10.00"));
 $dp->ingest(array("sku"=>"00001","name"=>"item2","description"=>"test2","price"=>"11.00","us_skus"=>"00000"));
 $dp->ingest(array("sku"=>"00002","name"=>"item3","description"=>"test3","price"=>"12.00","re_skus"=>"00000","cs_skus"=>"00001"));

 $dp->endImportSession();
 