<?php

/**
 * Class Tier price processor
 * @author dweeves
 *
 * This imports tier prices for columns names called "tier_price:"
 */
class TierpriceProcessor extends Magmi_ItemProcessor
{
    protected $_tpcol = array();
    protected $_singlestore = 0;
    protected $__pricescope = 2;

    public function getPluginInfo()
    {
        return array("name"=>"Tier price importer","author"=>"Dweeves,bepixeld","version"=>"0.0.9a",
            "url"=>$this->pluginDocUrl("Tier_price_importer"));
    }

    /**
     * you can add/remove columns for the item passed since it is passed by reference
     *
     * @param Magmi_Engine $mmi
     *            : reference to magmi engine instance (convenient to perform database operations)
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
        
        $tpn = $this->tablename("catalog_product_entity_tier_price");
        $tpcol = array_intersect(array_keys($this->_tpcol), array_keys($item));
        // do nothing if item has no tier price info or has not change
        if (count($tpcol) == 0)
        {
            return true;
        }
        else
        {
            
            // it seems that magento does not handle "per website" tier price on single store deployments , so force it to "default"
            // so we test wether we have single store deployment or not.
            // bepixeld patch : check pricescope from general config
            if ($this->_singlestore == 0 && $this->_pricescope != 0)
            {
                $wsids = $this->getItemWebsites($item);
            }
            else
            {
                $wsids = array(0);
            }
            $wsstr = $this->arr2values($wsids);
            // clear all existing tier price info for existing customer groups in csv
            $cgids = array();
            foreach ($tpcol as $k)
            {
                $tpinf = $this->_tpcol[$k];
                if ($tpinf["id"] != null)
                {
                    $cgids[] = $tpinf["id"];
                }
                else
                {
                    $cgids = array();
                    break;
                }
            }
            
            // if we have specific customer groups
            if (count($cgids) > 0)
            {
                // delete only for thos customer groups
                $instr = $this->arr2values($cgids);
                
                // clear tier prices for selected tier price columns
                $sql = "DELETE FROM $tpn WHERE entity_id=? AND customer_group_id IN ($instr) AND website_id IN ($wsstr)";
                $this->delete($sql, array_merge(array($pid), $cgids, $wsids));
            }
            else
            {
                // delete for all customer groups
                $sql = "DELETE FROM $tpn WHERE entity_id=? AND website_id IN ($wsstr)";
                $this->delete($sql, array_merge(array($pid), $wsids));
            }
        }
        
        foreach ($tpcol as $k)
        {
            
            // get tier price column info
            $tpinf = $this->_tpcol[$k];
            // now we've got a customer group id
            $cgid = $tpinf["id"];
            // add tier price
            $sql = "INSERT INTO $tpn
			(entity_id,all_groups,customer_group_id,qty,value,website_id) VALUES ";
            $inserts = array();
            $data = array();
            
            if ($item[$k] == "")
            {
                continue;
            }
            $tpvals = explode(";", $item[$k]);
            
            foreach ($wsids as $wsid)
            {
                // for each tier price value definition
                foreach ($tpvals as $tpval)
                {
                    // split on ":"
                    $tpvinf = explode(":", $tpval);
                    // if we have only one item
                    if (count($tpvinf) == 1)
                    {
                        // set qty to one
                        array_unshift($tpvinf, 1.0);
                    }
                    // if more thant 1, qty first,price second
                    $tpquant = $tpvinf[0];
                    $tpprice = str_replace(",", ".", $tpvinf[1]);
                    if ($tpprice == "")
                    {
                        continue;
                    }
                    if (substr($tpprice, -1) == "%")
                    {
                        // if no reference price,skip % tier price
                        if (!isset($item["price"]))
                        {
                            $this->warning("No price define, cannot apply % on tier price");
                            continue;
                        }
                        $fp = (float) (str_replace(",", ".", $item["price"]));
                        $pc = (float) (substr($tpprice, 0, -1));
                        $m = ($pc < 0 ? (100 + $pc) : $pc);
                        $tpprice = strval(($fp * ($m)) / 100.0);
                    }
                    $inserts[] = "(?,?,?,?,?,?)";
                    $data[] = $pid;
                    // if all , set all_groups flag
                    $data[] = (isset($cgid) ? 0 : 1);
                    $data[] = (isset($cgid) ? $cgid : 0);
                    $data[] = $tpquant;
                    $data[] = $tpprice;
                    $data[] = $wsid;
                }
            }
            if (count($inserts) > 0)
            {
                $sql .= implode(",", $inserts);
                $sql .= " ON DUPLICATE KEY UPDATE `value`=VALUES(`value`)";
                $this->insert($sql, $data);
            }
        }
        return true;
    }

    public function processColumnList(&$cols, $params = null)
    {
        // inspect column list for getting tier price columns info
        foreach ($cols as $col)
        {
            if (preg_match("|tier_price:(.*)|", $col, $matches))
            {
                $tpinf = array("name"=>$matches[1],"id"=>null);
                
                // if specific tier price
                if ($tpinf["name"] !== "_all_")
                {
                    // get tier price customer group id
                    $sql = "SELECT customer_group_id from " . $this->tablename("customer_group") .
                         " WHERE customer_group_code=?";
                    $cgid = $this->selectone($sql, $tpinf["name"], "customer_group_id");
                    $tpinf["id"] = $cgid;
                }
                else
                {
                    $tpinf["id"] = null;
                }
                $this->_tpcol[$col] = $tpinf;
            }
        }
        return true;
    }

    public function initialize($params)
    {
        $sql = "SELECT COUNT(store_id) as cnt FROM " . $this->tablename("core_store") . " WHERE store_id!=0";
        $ns = $this->selectOne($sql, array(), "cnt");
        if ($ns == 1)
        {
            $this->_singlestore = 1;
        }
        // bepixeld patch : check pricescope from general config
        $sql = "SELECT value FROM " . $this->tablename('core_config_data') . " WHERE path=?";
        $this->_pricescope = intval($this->selectone($sql, array('catalog/price/scope'), 'value')); // 0=global, 1=website
    }
}
