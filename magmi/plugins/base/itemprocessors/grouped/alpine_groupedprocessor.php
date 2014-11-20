<?php

/**
 * The MIT License (MIT)
 * Copyright (c) 2012 Alpine Consulting, Inc
 *
 * Permission is hereby granted, free of charge, to any person obtaining
 * a copy of this software and associated documentation files (the
 * "Software"), to deal in the Software without restriction, including
 * without limitation the rights to use, copy, modify, merge, publish,
 * distribute, sublicense, and/or sell copies of the Software, and to
 * permit persons to whom the Software is furnished to do so, subject to
 * the following conditions:
 *
 * The above copyright notice and this permission notice shall be included
 * in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
 * OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
 * MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
 * NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS
 * BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN
 * ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN
 * CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 *
 * Grouped Item processor
 * Based on magmi_configurableprocessor by dweeves
 *
 * @author Alpine Consulting, Inc
 *
 * This imports grouped products and associates the simple products to
 * the group
 */
class Magmi_GroupedItemProcessor extends Magmi_ItemProcessor
{
    public static $_VERSION = '1.4.1';
    private $_use_defaultopc = false;
    private $_optpriceinfo = array();
    private $_currentgrouped = array();
    private $_linktype = null;
    private $_link_type_id;
    private $_super_pos_attr_id;

    public function initialize($params)
    {
        $sql = "SELECT link_type_id FROM " . $this->tablename("catalog_product_link_type") . " WHERE code=?";
        $this->_link_type_id = $this->selectone($sql, array("super"), "link_type_id");
        $sql = "SELECT product_link_attribute_id FROM " . $this->tablename("catalog_product_link_attribute") .
             " WHERE link_type_id=? AND product_link_attribute_code=?";
        $this->_super_pos_attr_id = $this->selectone($sql, array($this->_link_type_id,'position'), 
            'product_link_attribute_id');
        $sql = "SELECT product_link_attribute_id FROM " . $this->tablename("catalog_product_link_attribute") .
             " WHERE link_type_id=? AND product_link_attribute_code=?";
        $this->_super_qty_attr_id = $this->selectone($sql, array($this->_link_type_id,'qty'), 
            'product_link_attribute_id');
    }

    public function getPluginUrl()
    {
        return $this->pluginDocUrl('Grouped_Item_processor');
    }

    public function getPluginVersion()
    {
        return self::$_VERSION;
    }

    public function getPluginName()
    {
        return 'Grouped Item processor';
    }

    public function getPluginAuthor()
    {
        return 'Alpine Consulting, Inc & Dweeves';
    }

    /**
     * Links the simple products to the group
     *
     * @param type $pid            
     * @param type $cond            
     * @param type $conddata            
     */
    public function dolink($pid, $cond, $gr = true, $conddata = array())
    {
        $cpl = $this->tablename("catalog_product_link");
        $cpsl = $this->tablename("catalog_product_super_link");
        $cpr = $this->tablename("catalog_product_relation");
        $cpe = $this->tablename("catalog_product_entity");
        $cplt = $this->tablename("catalog_product_link_type");
        $cplai = $this->tablename("catalog_product_link_attribute_int");
        // create association table for sku/positions
        $sskus = array();
        $qtys = array();
        $ccond = count($conddata);
        for ($i = 0; $i < $ccond; $i++)
        {
            $skuinfo = explode("::", $conddata[$i]);
            if (count($skuinfo) > 2)
            {
                $qtys[$skuinfo[0]] = $skuinfo[2];
            }
            $sskus[$skuinfo[0]] = count($skuinfo) > 1 ? $skuinfo[1] : $i;
            $conddata[$i] = $skuinfo[0];
        }
        
        // if group reset
        if ($gr)
        {
            $sql = "DELETE cpsl.*,cpsr.* FROM $cpsl as cpsl
            	JOIN $cpr as cpsr ON cpsr.parent_id=cpsl.parent_id
            	WHERE cpsl.parent_id=?";
            $this->delete($sql, array($pid));
            $sql = "DELETE FROM $cpl
            	WHERE product_id=?";
            $this->delete($sql, array($pid));
        }
        // recreate associations
        $sql = "INSERT IGNORE INTO $cpsl (`parent_id`,`product_id`) 
        	SELECT cpec.entity_id as parent_id,cpes.entity_id  as product_id
            FROM $cpe as cpec
            JOIN $cpe as cpes ON cpes.sku $cond
            WHERE cpec.entity_id=?";
        $this->insert($sql, array_merge($conddata, array($pid)));
        if ($this->_linktype == NULL)
        {
            $sql = "select link_type_id from $cplt where code=?";
            $this->_linktype = $this->selectone($sql, 'super', 'link_type_id');
        }
        $sql = "INSERT IGNORE INTO $cpl (`product_id`,`linked_product_id`, `link_type_id`) 
        	SELECT cpec.entity_id as parent_id,cpes.entity_id  as product_id, ?
            FROM $cpe as cpec
            JOIN $cpe as cpes ON cpes.sku $cond
            WHERE cpec.entity_id=?";
        $this->insert($sql, array_merge(array($this->_linktype), $conddata, array($pid)));
        $sql = "INSERT IGNORE INTO $cpr (`parent_id`,`child_id`) 
        	SELECT cpec.entity_id as parent_id,cpes.entity_id  as child_id
            FROM $cpe as cpec
            JOIN $cpe as cpes ON cpes.sku $cond
            WHERE cpec.entity_id=?";
        $this->insert($sql, array_merge($conddata, array($pid)));
        // positions
        $cw = $this->arr2case($sskus, 'cpes.sku');
        $sql = "INSERT INTO $cplai (product_link_attribute_id,link_id,value) SELECT ?,cpl.link_id,$cw as value
        FROM $cpl as cpl
        JOIN $cpe as cpes ON cpes.sku $cond
        WHERE cpl.linked_product_id=cpes.entity_id AND cpl.product_id=?
        ON DUPLICATE KEY UPDATE `value`=values(`value`)";
        $this->insert($sql, array_merge(array($this->_super_pos_attr_id), $conddata, array($pid)));
        unset($sskus);
        // qties
        if (count($qtys) > 0)
        {
            $qw = $this->arr2case($qtys, 'cpes.sku');
            $cplad = $this->tablename('catalog_product_link_attribute_decimal');
            $sql = "INSERT INTO $cplad (product_link_attribute_id,link_id,value) SELECT ?,cpl.link_id,$qw as value
        	FROM $cpl as cpl
        	JOIN $cpe as cpes ON cpes.sku $cond
        	WHERE cpl.linked_product_id=cpes.entity_id AND cpl.product_id=?
        	ON DUPLICATE KEY UPDATE `value`=values(`value`)";
            $this->insert($sql, array_merge(array($this->_super_qty_attr_id), $conddata, array($pid)));
        }
        unset($qtys);
        unset($conddata);
    }

    /**
     * Wrapper for dolink
     *
     * @see dolink($pid, $cond, $conddata = array())
     * @param type $pid            
     */
    public function autoLink($pid, $gr = true)
    {
        $cpe = $this->tablename("catalog_product_entity");
        $sql = "SELECT cpes.sku
    	 FROM $cpe as cpec
    	JOIN $cpe as cpes  ON cpes.sku LIKE CONCAT(cpec.sku,'%') AND cpes.sku!=cpec.sku
    	WHERE cpec.entity_id=?";
        $res = $this->selectAll($sql, array($pid));
        $sskus = array();
        $cres = count($res);
        for ($i = 0; $i < $cres; $i++)
        {
            $sskus[$i] = $res[$i]["sku"];
        }
        unset($res);
        $this->fixedlink($pid, $sskus, $gr);
        unset($sskus);
    }

    public function updSimpleVisibility($pid)
    {
        $vis = $this->getParam("APIGRP:updgroupedvis", 0);
        if ($vis != 0)
        {
            $attinfo = $this->getAttrInfo("visibility");
            $sql = "UPDATE " . $this->tablename("catalog_product_entity_int") . " as cpei
                JOIN " . $this->tablename("catalog_product_super_link") . " as cpsl ON cpsl.parent_id=?
                JOIN " . $this->tablename("catalog_product_entity") . " as cpe ON cpe.entity_id=cpsl.product_id
                SET cpei.value=?
                WHERE cpei.entity_id=cpe.entity_id AND attribute_id=?";
            $this->update($sql, array($pid,$vis,$attinfo["attribute_id"]));
        }
    }

    /**
     * Wrapper for dolink
     *
     * @see dolink($pid, $cond, $conddata = array())
     * @param type $pid            
     * @param type $skulist            
     */
    public function fixedLink($pid, $skulist, $gr = true)
    {
        if(!empty($skulist)) {
            $this->dolink($pid, "IN (" . $this->arr2values($skulist) . ")", $gr, $skulist);
        }
    }

    /**
     * Determines which method is being used for linking the simple products
     * to the group
     *
     * @param type $item            
     * @return string
     */
    public function getMatchMode($item)
    {
        $matchmode = "auto";
        if ($this->getParam('APIGRP:nolink', 0))
        {
            $matchmode = "none";
        }
        else
        {
            if ($this->getParam("APIGRP:groupedbeforegrp") == 1)
            {
                $matchmode = "cursimples";
            }
            //fix for empty grouped skus support => no link
            if (isset($item["grouped_skus"]))
            {

                $matchmode = (trim($item["grouped_skus"]) != "")?"fixed":"none";
            }
        }
        return $matchmode;
    }

    public function processItemAfterId(&$item, $params = null)
    {
        // if item is not grouped, nothing to do
        if ($item["type"] !== "grouped")
        {
            if ($this->getParam("APIGRP:groupedbeforegrp") == 1)
            {
                $this->_currentgrouped[] = $item["sku"];
            }
            return true;
        }
        
        $pid = $params["product_id"];
        $groupreset = !isset($item['group_reset']) || $item['group_reset'] == 1;
        $matchmode = $this->getMatchMode($item);
        switch ($matchmode)
        {
            case "none":
                break;
            case "auto":
                // destroy old associations
                $this->autoLink($pid, $groupreset);
                $this->updSimpleVisibility($pid);
                break;
            case "cursimples":
                $this->fixedLink($pid, $this->_currentgrouped, $groupreset);
                $this->updSimpleVisibility($pid);
                break;
            case "fixed":
                $sskus = explode(",", $item["grouped_skus"]);
                $this->trimarray($sskus);
                $this->fixedLink($pid, $sskus, $groupreset);
                $this->updSimpleVisibility($pid);
                unset($item["grouped_skus"]);
                unset($sskus);
                break;
            default:
                break;
        }
        // always clear current simples
        if (count($this->_currentgrouped) > 0)
        {
            unset($this->_currentgrouped);
            $this->_currentgrouped = array();
        }
        return true;
    }

    public function getPluginParamNames()
    {
        return array("APIGRP:groupedbeforegrp","APIGRP:updgroupedvis","APIGRP:nolink");
    }

    static public function getCategory()
    {
        return "Product Type Import";
    }

    private function trimarray(&$arr)
    {
        $carr = count($arr);
        for ($i = 0; $i < $carr; $i++)
        {
            $arr[$i] = trim($arr[$i]);
        }
    }
}
