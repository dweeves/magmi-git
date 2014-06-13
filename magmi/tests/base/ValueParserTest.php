<?php

require_once (__DIR__."/../../inc/magmi_valueparser.php");
require_once (__DIR__."/../../inc/magmi_utils.php");

class ValueParserTest extends PHPUnit_Framework_TestCase
{
    protected $_dictarray;
    
    public function setUp()
    {
        $item = array("sku"=>"123","description"=>"toto","name"=>"titi de test");
        $params = array("imagename"=>"test.jpg","new"=>"1");        
        $this->_dictarray = array("item"=>$item,"meta"=>$params);
        
    }
        
   public function testBasicReplace()
   {
       $v = Magmi_ValueParser::parseValue("{item.sku}", $this->_dictarray);
       $this->assertEquals($v,'123');
   }
   
   public function testAdvancedReplace()
   {
       $v = Magmi_ValueParser::parseValue("test-{{Slugger::slug({item.name})}}", $this->_dictarray);
       $this->assertEquals($v,'test-titi-de-test');
   }
   
   public function testMultiDictReplace()
   {
       $v = Magmi_ValueParser::parseValue("{item.sku}-{meta.imagename}", $this->_dictarray);
       $this->assertEquals($v,'123-test.jpg');
   }

}