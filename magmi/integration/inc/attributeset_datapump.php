<?php
require_once("productimport_datapump.php");
require_once("array_reader.php");

class Magmi_AttributeSet_DataPump extends Magmi_ProductImport_DataPump
{
    public function ingestAttributes($items = array())
    {
        $reader = new ArrayReader();
        $reader->initialize($items);
        $this->_engine->callPlugins("general", "importAttributes", $reader);
    }

    public function ingestAttributeSets($items = array())
    {
        $reader = new ArrayReader();
        $reader->initialize($items);
        $this->_engine->callPlugins("general", "importAttributeSets", $reader);
    }

    public function ingestAttributeAsociations($items = array())
    {
        $reader = new ArrayReader();
        $reader->initialize($items);
        $this->_engine->callPlugins("general", "importAttributeAssociations", $reader);
    }

    public function cleanupAttributes()
    {
        $this->_engine->callPlugins("general", "deleteUnreferencedAttributes");
    }
}
