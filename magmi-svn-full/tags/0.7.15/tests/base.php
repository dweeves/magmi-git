<?php
 require_once("../inc/magmi_importer.php");
 $mmi=new MagentoMassImporter();
 $mmi->import(array("filename"=>"/media/Data/magento/var/import/cpupdate.csv","mode"=>"update"));