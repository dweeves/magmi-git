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

    public static $_VERSION = '1.1';
    private $_use_defaultopc = false;
    private $_optpriceinfo = array();
    private $_currentgrouped = array();
	private $_linktype=null;
	
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
    public function dolink($pid, $cond, $conddata = array())
    {
        $cpl = $this->tablename("catalog_product_link");
        $cpsl = $this->tablename("catalog_product_super_link");
        $cpr = $this->tablename("catalog_product_relation");
        $cpe = $this->tablename("catalog_product_entity");
        $cplt = $this->tablename("catalog_product_link_type");

        $sql = "DELETE cpsl.*,cpsr.* FROM $cpsl as cpsl
            JOIN $cpr as cpsr ON cpsr.parent_id=cpsl.parent_id
            WHERE cpsl.parent_id=?";
        $this->delete($sql, array($pid));
        $sql = "DELETE FROM $cpl
            WHERE product_id=?";
        $this->delete($sql, array($pid));
        //recreate associations
        $sql = "INSERT INTO $cpsl (`parent_id`,`product_id`) 
        	SELECT cpec.entity_id as parent_id,cpes.entity_id  as product_id
            FROM $cpe as cpec
            JOIN $cpe as cpes ON cpes.sku $cond
            WHERE cpec.entity_id=?";
        $this->insert($sql, array_merge($conddata, array($pid)));
        if($this->_linktype==NULL)
        {
       		 $sql = "select link_type_id from $cplt where code=?";
       	 	 $this->_linktype = $this->selectone($sql, 'super', 'link_type_id');
        }
        $sql = "INSERT INTO $cpl (`product_id`,`linked_product_id`, `link_type_id`) 
        	SELECT cpec.entity_id as parent_id,cpes.entity_id  as product_id, ?
            FROM $cpe as cpec
            JOIN $cpe as cpes ON cpes.sku $cond
            WHERE cpec.entity_id=?";
        $this->insert($sql, array_merge(array($this->_linktype),$conddata, array($pid)));
        $sql = "INSERT INTO $cpr (`parent_id`,`child_id`) 
        	SELECT cpec.entity_id as parent_id,cpes.entity_id  as child_id
            FROM $cpe as cpec
            JOIN $cpe as cpes ON cpes.sku $cond
            WHERE cpec.entity_id=?";
        $this->insert($sql, array_merge($conddata, array($pid)));
        unset($conddata);
    }

    /**
     * Wrapper for dolink
     *
     * @see dolink($pid, $cond, $conddata = array())
     * @param type $pid
     */
    public function autoLink($pid)
    {
        $this->dolink($pid, "LIKE CONCAT(cpec.sku,'%')");
    }

    public function updSimpleVisibility($pid)
    {
        $vis = $this->getParam("APIGRP:updgroupedvis", 0);
        if ($vis != 0) {
            $attinfo = $this->getAttrInfo("visibility");
            $sql = "UPDATE " . $this->tablename("catalog_product_entity_int") . " as cpei
                JOIN " . $this->tablename("catalog_product_super_link") . " as cpsl ON cpsl.parent_id=?
                JOIN " . $this->tablename("catalog_product_entity") . " as cpe ON cpe.entity_id=cpsl.product_id
                SET cpei.value=?
                WHERE cpei.entity_id=cpe.entity_id AND attribute_id=?";
            $this->update($sql, array($pid, $vis, $attinfo["attribute_id"]));
        }
    }

    /**
     * Wrapper for dolink
     *
     * @see dolink($pid, $cond, $conddata = array())
     * @param type $pid
     * @param type $skulist
     */
    public function fixedLink($pid, $skulist)
    {
        $this->dolink($pid, "IN (" . $this->arr2values($skulist) . ")", $skulist);
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
        if ($this->getParam('APIGRP:nolink', 0)) {
            $matchmode = "none";
        } else {
            if ($this->getParam("APIGRP:groupedbeforegrp") == 1) {
                $matchmode = "cursimples";
            }
            if (isset($item["grouped_skus"]) && trim($item["grouped_skus"]) != "") {
                $matchmode = "fixed";
            }
        }
        return $matchmode;
    }

    public function processItemAfterId(&$item, $params = null) {
        //if item is not grouped, nothing to do
        if ($item["type"] !== "grouped") {
            if ($this->getParam("APIGRP:groupedbeforegrp") == 1) {
                $this->_currentgrouped[] = $item["sku"];
            }
            return true;
        }

        $pid = $params["product_id"];
        $matchmode = $this->getMatchMode($item);
        switch ($matchmode)
        {
        case "none":
            break;
        case "auto":
            //destroy old associations
            $this->autoLink($pid);
            $this->updSimpleVisibility($pid);
            break;
        case "cursimples":
            $this->fixedLink($pid, $this->_currentgrouped);
            $this->updSimpleVisibility($pid);
            break;
        case "fixed":
            $sskus = explode(",", $item["grouped_skus"]);
            $this->trimarray($sskus);
            $this->fixedLink($pid, $sskus);
            $this->updSimpleVisibility($pid);
            unset($item["simples_skus"]);
            break;
        default:
            break;
        }
        //always clear current simples
        if (count($this->_currentgrouped) > 0) {
            unset($this->_currentgrouped);
            $this->_currentgrouped = array();
        }
        return true;
    }

    public function getPluginParamNames() {
        return array("APIGRP:groupedbeforegrp", "APIGRP:updgroupedvis", "APIGRP:nolink");
    }

    static public function getCategory() {
        return "Product Type Import";
    }

    private function trimarray(&$arr) {
        for ($i=0;$i<count($arr);$i++) {
            $arr[$i]=trim($arr[$i]);
        }
    }
}