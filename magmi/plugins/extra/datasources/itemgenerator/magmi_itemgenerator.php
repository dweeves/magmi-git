<?php
require_once(MAGMI_INCDIR."/magmi_valueparser.php");

class Magmi_ItemGenerator extends Magmi_DataSource
{
    protected $_tpl;
    protected $_loop;
    protected $_maxloop;

    public function initialize($params)
    {
        $this->_tpl=json_decode($params["ITG:template"],true);
        $this->_maxloop=intval($params["ITG:nbitems"]);
    }

    public function getPluginParamNames()
    {
        return array("ITG:template","ITG:nbitems");
    }
    public function startImport()
    {

        $this->_loop=0;
    }

    public function endImport()
    {

    }

    public function getItemFromTemplate($loopnum)
    {
        $item=array();
        $item["##loop##"]=$loopnum;
        $toparse=array_keys($this->_tpl);
        // parse static or simple templated values first
        foreach($toparse as $k)
        {
            $v=$this->_tpl[$k];
            $newv=str_replace("##loop##",$this->_loop,$v);
           $pi=Magmi_ValueParser::getParseInfo($newv,array("item"=>$item));

            if(count($pi))
            {
                $newv=Magmi_ValueParser::parseValue($this->_tpl[$k],array("item"=>$item));
            }
            $item[$k]=$newv;
        }
        return $item;
    }

    public function getColumnNames($prescan = false)
    {
        return array_keys($this->_tpl);
    }

    public function getRecordsCount()
    {
        return $this->getParam("ITG:nbitems");
    }

    public function getNextRecord()
    {
        if($this->_loop<$this->_maxloop)
        {
            $this->_loop++;
            return $this->getItemFromTemplate($this->_loop);
        }
        return false;
    }

    public function onException($e)
    {
    }

    public function getPluginInfo()
    {
        return array("name"=>"Item Generator","author"=>"Dweeves","version"=>"0.0.1");
    }
}