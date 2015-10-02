<?php

/**
 * Attributes Cleanup.
 * 
 * Removes attributes for products, which are not in the prodoct's attribute set.
 * This is particularly useful if
 * a) updating products with different attribute sets (which normally results in "old" attributes from old set still in set) or
 * b) changing attribute sets e.g with Attribute Set Importer Plugin.
 *
 * @author 5byfive GmbH (T.Rosenstiel) based on code (Magmi framework) by Dweeves
 * 
 * Copyright (C) 2015 by 5byfive GmbH (T. Rosenstiel) and Dweeves (S.BRACQUEMONT)
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */
class AttributeCleanup extends Magmi_GeneralImportPlugin
{
    public function initialize($params)
    {
    }

    public function getPluginInfo()
    {
        return array("name"=>"Attribute Cleanup","author"=>"5byfive GmbH","version"=>"0.0.1","url"=>$this->pluginDocUrl("Attribute_cleanup"));
    }

    public function beforeImport()
    {
        // intentionally left blank...
    }
    

    public function afterImport()
    {
        $this->deleteUnreferencedAttributes();
    }
    

    public function deleteUnreferencedAttributes()
    {
        $this->log('Will delete unreferenced attribute values.', 'startup');
        $tables = array(
        //                 'catalog_category_entity_datetime'
        //                 ,'catalog_category_entity_decimal'
        //                 ,'catalog_category_entity_int'
        //                 ,'catalog_category_entity_text'
        //                 ,'catalog_category_entity_varchar',
                'catalog_product_entity_datetime'
                ,'catalog_product_entity_decimal'
                ,'catalog_product_entity_gallery'
                ,'catalog_product_entity_int'
                ,'catalog_product_entity_text'
                ,'catalog_product_entity_varchar'
                //                 ,'customer_address_entity_datetime'
        //                 ,'customer_address_entity_decimal'
        //                 ,'customer_address_entity_int'
        //                 ,'customer_address_entity_text'
        //                 ,'customer_address_entity_varchar'
        //                 ,'customer_entity_datetime'
        //                 ,'customer_entity_decimal'
        //                 ,'customer_entity_int'
        //                 ,'customer_entity_text'
        //                 ,'customer_entity_varchar'
                ,'eav_entity_datetime'
                ,'eav_entity_decimal'
                ,'eav_entity_int'
                ,'eav_entity_text'
                ,'eav_entity_varchar'
                ,'weee_tax'
                ,'catalog_product_entity_media_gallery'
                ,'catalog_product_index_eav'
                ,'catalog_product_index_eav_decimal'
                ,'catalog_product_index_eav_decimal_idx'
                ,'catalog_product_index_eav_decimal_tmp'
                ,'catalog_product_index_eav_idx'
                ,'catalog_product_index_eav_tmp'
                ,['catalog_product_super_attribute','product_id']
        );

        foreach ($tables as $table) {
            $tableEntityFieldName = "entity_id";
            if (is_array($table)) {
                $tableEntityFieldName = $table[1];
                $table = $table[0];
            }
            $deleteSql = "
            DELETE      ##$table##
            FROM        ##$table##
            INNER JOIN  ##eav_attribute## on ##eav_attribute##.attribute_id = ##$table##.attribute_id
            INNER JOIN  ##catalog_product_entity## on ##catalog_product_entity##.entity_id = ##$table##.$tableEntityFieldName
            LEFT OUTER JOIN ##eav_entity_attribute## on (##eav_entity_attribute##.attribute_set_id = ##catalog_product_entity##.attribute_set_id AND ##eav_entity_attribute##.attribute_id = ##$table##.attribute_id)
            WHERE       ##eav_attribute##.entity_type_id = ?
            AND         ##eav_attribute##.is_user_defined = 1
            AND         ##eav_entity_attribute##.attribute_id IS NULL";
            $sql = preg_replace_callback('/(##[a-zA-Z_]*##)/Uis', function ($ms) { foreach ($ms as $m) {
    return str_replace('##', '', $this->tablename($m));
}}, $deleteSql);
    
            $count = $this->delete($sql, array($this->getProductEntityType()));
            if ($count > 0) {
                $this->log("Deleted $count records from table $table.", 'startup');
            }
        }
        $this->log('Done deleting unreferenced attribute values.', 'startup');
    }
    
    
    /**
     * Calls the engine's trace function with the plugin's name and version as a prefix.
     * @param Exception $e the exception to trace
     * @param string $message message
     */
    public function trace($e, $message="no message")
    {
        $pinf = $this->getPluginInfo();
        $data = "{$pinf["name"]} v{$pinf["version"]} - ".$message;
        $this->_caller_trace($e, $data);
    }
}
