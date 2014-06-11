<?php

class SkuFinderItemProcessor extends Magmi_ItemProcessor
{
    private $_compchecked = FALSE;

    public function getPluginInfo()
    {
        return array("name"=>"SKU Finder","author"=>"Dweeves","version"=>"0.0.2",
            "url"=>$this->pluginDocUrl("SKU_Finder"));
    }

    public function processItemBeforeId(&$item, $params = null)
    {
        $matchfield = trim($this->getParam("SKUF:matchfield"));
        // protection from tricky testers ;)
        if ($matchfield == "sku")
        {
            return true;
        }
        $attinfo = $this->getAttrInfo($matchfield);
        if ($this->_compchecked == FALSE)
        {
            // Checking attribute compatibility with sku matching
            if ($attinfo == NULL)
            {
                $this->log("$matchfield is not a valid attribute", "error");
                $item["__MAGMI_LAST__"] = 1;
                return false;
            }
            if ($attinfo["is_unique"] == 0 || $attinfo["is_global"] == 0)
            {
                $this->log("sku matching attribute $matchfield must be unique & global scope");
                $item["__MAGMI_LAST__"] = 1;
                return false;
            }
            if ($attinfo["backend_type"] == "static")
            {
                $this->log("$matchfield is " . $attinfo["backend_type"] . ", it cannot be used as sku matching field.", 
                    "error");
                $item["__MAGMI_LAST__"] = 1;
                return false;
            }
            if ($attinfo["frontend_input"] == "select" || $attinfo["frontend_input"] == "multiselect")
            {
                $this->log(
                    "$matchfield is " . $attinfo["frontend_input"] . ", it cannot be used as sku matching field.", 
                    "error");
                $item["__MAGMI_LAST__"] = 1;
                return false;
            }
            $this->_compchecked = true;
        }
        
        // no item data for selected matching field, skipping
        if (!isset($item[$matchfield]) && trim($item["matchfield"]) !== '')
        {
            $this->log("No value for $matchfield in datasource", "error");
            return false;
        }
        // now find sku
        $cpebt = $this->tablebname("catalog_product_entity_" . $attinfo["backend_type"]);
        $sql = "SELECT sku FROM " . $this->tablename("catalog_product_entity") . " as cpe JOIN
		$cpebt as cpebt ON cpebt.value=? AND cpebt.attribute_id=? AND cpebt.entity_id=cpe.entity_id";
        $stmt = $this->select($sql, array($item[$matchfield],$attinfo["attribute_id"]));
        $n = 0;
        while ($result = $stmt->fetch())
        {
            // if more than one result, cannot match single sku
            if ($n > 1)
            {
                $this->log("Several skus match $matchfield value : " . $item[$matchfield], "error");
                return false;
            }
            else
            {
                $item["sku"] = $result["sku"];
            }
            $n++;
        }
        // if no item found, warning & skip
        if ($n == 0)
        {
            $this->log("No sku found matching $matchfield value : " . $item[$matchfield], "warning");
            return false;
        }
        // found a single sku ! item sku is in place, continue with processor chain
        return true;
    }

    static public function getCategory()
    {
        return "Input Data Preprocessing";
    }
}