<?php

/**
 * Class Ecotaxe (weee tax) processor
 * @author Garbocom with dweeves help and debuging
 *
 * This imports Ecotaxe (weee tax) for columns names called "ecotaxe"
 */
class WeeetaxItemProcessor extends Magmi_ItemProcessor
{

    public function getPluginInfo()
    {
        return array("name"=>"Weee Tax importer","author"=>"Garbocom & Dweeves","version"=>"0.0.5");
    }

    /**
     * you can add/remove columns for the item passed since it is passed by reference
     *
     * @param MagentoMassImporter $this
     *            : reference to mass importer (convenient to perform database operations)
     * @param unknown_type $item
     *            : modifiable reference to item before import
     *            the $item is a key/value array with column names as keys and values as read from csv file.
     * @return bool :
     *         true if you want the item to be imported after your custom processing
     *         false if you want to skip item import after your processing
     */
    public function processItemAfterId(&$item, $params = null)
    {
        $pid = $params["product_id"];
        // get plugin param value WEEE:attributes,defaulting to ecotaxe
        $weeattrnames = $this->getParam("WEEE:attributes", "ecotaxe");
        $wsids = $this->getItemWebsites($item);
        
        $wattrs = explode(",", $weeattrnames);
        $data = array();
        $inserts = array();
        foreach ($wattrs as $wattr)
        {
            if (isset($item[$wattr]) && $item[$wattr] != "")
            {
                // get attribute metadata
                $attrinfo = $this->getAttrinfo($wattr);
                if (count($attrinfo) == 0)
                {
                    $this->log("Invalid attribute code for weee tax ($wattr)", "warning");
                    continue;
                }
                else
                {
                    $country = $this->getParam("WEEE:country", "FR");
                    // ask mmi for custom module table name (takes into account table prefix
                    $tname = $this->tablename("weee_tax");
                    
                    // Delete all weee tax for this product before update. If you use this plugin,
                    $sql = "DELETE FROM $tname WHERE entity_id=? AND attribute_id=? AND country=?";
                    $this->delete($sql, array($pid,$attrinfo["attribute_id"],$country));
                    
                    // handle wee tax value for all defined websites in import row
                    foreach ($wsids as $wsid)
                    {
                        $inserts[] = "(?,?,?,?,?,?,?)";
                        $data = array_merge($data, 
                            array($wsid,$pid,$country,$item[$wattr],'*',$attrinfo["attribute_id"],
                                $this->getProductEntityType()));
                    }
                }
            }
        }
        if (count($data) > 0)
        {
            $sql = "INSERT IGNORE INTO $tname (website_id,entity_id,country,value,state,attribute_id,entity_type_id) VALUES " .
                 implode(",", $inserts);
            $this->insert($sql, $data);
        }
        else
        {
            $this->log("No weee data found", "warning");
        }
        unset($data);
        unset($inserts);
        return true;
    }

    public function getPluginParamNames()
    {
        return array("WEEE:country","WEEE:attributes");
    }

    public function initialize($params)
    {}
} 