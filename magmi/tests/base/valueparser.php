<?php
require_once ("../../inc/magmi_valueparser.php");
require_once ("../../inc/magmi_utils.php");

$item = array("sku" => "123","description" => "toto","name" => "titi de test");
$params = array("imagename" => "test.jpg","new" => "1");

$dictarray = array("item" => $item,"meta" => $params);

$v1 = Magmi_ValueParser::parseValue("{item.sku}", $dictarray);
$v2 = Magmi_ValueParser::parseValue("test-{{Slugger::slug({item.name})}}", $dictarray);
$v3 = Magmi_ValueParser::parseValue("{item.sku}-{meta.imagename}", $dictarray);