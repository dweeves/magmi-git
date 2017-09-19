<?php

/**
 * The MIT License (MIT)
 * Copyright (c) 2014 Limora Oldtimer GmbH & Co. KG
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
 *
 * @author Björn Tantau <bjoern.tantau@limora.com>
 *
 * This imports bundle products and associates simple products and options with the bundle
 */
class Magmi_BundleItemProcessor extends Magmi_ItemProcessor
{
    public static $_VERSION = '1.1';

    /**
     * Defaults to use when nothing is configured.
     *
     * @var $_defaults array
     */
    protected $_defaults = array('option'=>array('type'=>'select','required'=>'1','position'=>'0'),
        'sku'=>array('selection_qty'=>'1','selection_can_change_qty'=>'1','position'=>'0','is_default'=>'0',
            'selection_price_value'=>'0','selection_price_type'=>'0'));

    /**
     * @var _mfields : fields to fill in item in order to allow frontend display & correct structure if not set in csv
     */
    protected $_mfields=array('sku_type'=>1,'shipment_type'=>0,'options_container'=>'container1','weight_type'=>1,
        'price_type'=>1,'price_view'=>1);

    public function getPluginUrl()
    {
        return $this->pluginDocUrl('Bundle_Item_processor');
    }

    public function getPluginVersion()
    {
        return self::$_VERSION;
    }

    public function getPluginName()
    {
        return 'Bundle Item processor';
    }

    public function getPluginAuthor()
    {
        return 'Björn Tantau,dweeves,igi8819';
    }

    public function processItemAfterId(&$item, $params = null)
    {
        // if item is not a bundle, nothing to do
        if ($item["type"] !== "bundle") {
            return true;
        }

        if (!empty($item['bundle_skus'])) {
            $options = $this->_createOptions($item, $params);
            $this->_linkProducts($item, $params, $options);
            $this->fillBundleMandatoryFields($item);
        }

        return true;
    }

    /**
     * @param $item Fill extra item fields for bundle
     */
    public function fillBundleMandatoryFields(&$item)
    {
        foreach ($this->_mfields as $k=>$v) {
            if (!isset($item[$k])) {
                $item[$k]=$v;
            }
        }
    }

    public function getPluginParamNames()
    {
        $params = array();
        foreach ($this->_defaults as $type => $fields) {
            foreach ($fields as $field => $default) {
                $params[] = 'BNDL:' . $type . '_' . $field;
            }
        }
        return $params;
    }

    public static function getCategory()
    {
        return "Product Type Import";
    }

    /**
     * Get Default value from config or internal defaults.
     *
     * @param string $type
     * @param string $field
     *
     * @return string
     */
    public function getConfiguredDefault($type, $field)
    {
        $default = null;
        if (isset($this->_defaults[$type][$field])) {
            $default = $this->_defaults[$type][$field];
        }

        return $this->getParam("BNDL:{$type}_{$field}", $default);
    }

    /**
     * Create options defined in bundle_options and bundle_skus
     *
     * @param array $item
     * @param array $params
     *
     * @return array
     */
    protected function _createOptions($item, $params)
    {
        $options = $this->_deleteOptions($this->_extractOptions($item), $params['product_id']);
        $sids=$this->getItemStoreIds($item);
        $storeId = array_pop($sids);

        $opt = $this->tablename('catalog_product_bundle_option');
        $optv = $this->tablename('catalog_product_bundle_option_value');

        foreach ($options as $code => $option) {
            $option['parent_id'] = $params['product_id'];
            $option['store_id'] = $storeId;
            $existingOption = $this->_getExistingOption($option['code'], $storeId, $params['product_id']);

            if (!$this->_arraysEqual($existingOption, $option, 'option_id')) {
                if (!empty($existingOption['option_id'])) {
                    $option['option_id'] = $existingOption['option_id'];
                    $sql = "UPDATE $opt AS opt SET opt.required = :required, opt.position = :position, opt.type = :type WHERE opt.option_id = :option_id";
                    $bind = array('required'=>$option['required'],'position'=>$option['position'],
                        'type'=>$option['type'],'option_id'=>$option['option_id']);
                    $this->update($sql, $bind);

                    if (!empty($option['title'])) {
                        if (empty($existingOption['title'])) {
                            $sql = "INSERT INTO $optv (option_id, store_id, title) VALUES(:option_id, :store_id, :title)";
                            $bind = array('option_id'=>$option['option_id'],'store_id'=>$option['store_id'],
                                'title'=>$option['title']);
                            $this->insert($sql, $bind);
                        } elseif ($existingOption['title'] != $option['title']) {
                            $sql = "UPDATE $optv SET title = :title WHERE option_id = :option_id AND store_id = :store_id";
                            $bind = array('option_id'=>$option['option_id'],'store_id'=>$option['store_id'],
                                'title'=>$option['title']);
                            $this->update($sql, $bind);
                        }
                    }
                } else {
                    $sql = "INSERT INTO $opt (parent_id, required, position, type) VALUES(:parent_id, :required, :position, :type)";
                    $bind = array('parent_id'=>$option['parent_id'],'required'=>$option['required'],
                        'position'=>$option['position'],'type'=>$option['type']);
                    $optionId = $this->insert($sql, $bind);
                    $option['option_id'] = $optionId;

                    $sql = "INSERT INTO $optv (option_id, store_id, title) VALUES(:option_id, :store_id, :title)";
                    $bind = array('option_id'=>$option['option_id'],'store_id'=>0,'title'=>$option['code']);
                    $this->insert($sql, $bind);

                    if (!empty($option['title']) && $option['store_id'] != 0) {
                        $bind = array('option_id'=>$option['option_id'],'store_id'=>$option['store_id'],
                            'title'=>$option['title']);
                        $this->insert($sql, $bind);
                    }
                }
            } else {
                $option['option_id'] = $existingOption['option_id'];
            }
            $options[$code] = $option;
        }

        return $options;
    }

    /**
     * Delete options prefixed with "-".
     * Delete all options if an option with a code of "-*" is defined.
     *
     * @param array $options
     * @param int $productId
     *
     * @return array
     */
    protected function _deleteOptions($options, $productId)
    {
        $opt = $this->tablename('catalog_product_bundle_option');
        $optv = $this->tablename('catalog_product_bundle_option_value');

        $deleteAll = false;

        if (isset($options['-*'])) {
            $sql = "DELETE opt FROM $opt AS opt
                WHERE
                    opt.parent_id = :parent_id
            ";

            $bind = array('parent_id'=>$productId);
            $this->delete($sql, $bind);
            unset($options['-*']);
            $deleteAll = true;
        }

        foreach ($options as $code => $option) {
            if (substr($code, 0, 1) === '-') {
                if (!$deleteAll) {
                    $sql = "DELETE opt FROM $opt AS opt
                        JOIN $optv AS optv ON optv.option_id = opt.option_id AND optv.store_id = 0
                        WHERE
                            opt.parent_id = :parent_id
                            AND optv.title = :code
                    ";

                    $bind = array('parent_id'=>$productId,'code'=>substr($code, 1));
                    $this->delete($sql, $bind);
                }

                unset($options[$code]);
            }
        }

        return $options;
    }

    /**
     * Extract all options to create from bundle_options and bundle_skus.
     *
     * @param array $item
     *
     * @return array
     */
    protected function _extractOptions($item)
    {
        $options = array();
        if (!empty($item['bundle_options'])) {
            $bundleOptions = explode(';', $item['bundle_options']);
            foreach ($bundleOptions as $bundleOption) {
                if (!empty($bundleOption)) {
                    $option = array();
                    $bundleOption = explode(':', $bundleOption);
                    $bundleOption = $this->_trimArray($bundleOption);

                    $option['code'] = $bundleOption[0];
                    $option['title'] = isset($bundleOption[1]) && $bundleOption[1] !== '' ? $bundleOption[1] : $bundleOption[0];
                    $option['type'] = isset($bundleOption[2]) && $bundleOption[2] !== '' ? $bundleOption[2] : $this->getConfiguredDefault(
                        'option', 'type');
                    $option['required'] = isset($bundleOption[3]) && $bundleOption[3] !== '' ? $bundleOption[3] : $this->getConfiguredDefault(
                        'option', 'required');
                    $option['position'] = isset($bundleOption[4]) && $bundleOption[4] !== '' ? $bundleOption[4] : $this->getConfiguredDefault(
                        'option', 'position');

                    $options[$option['code']] = $option;
                }
            }
        }

        if (!empty($item['bundle_skus'])) {
            $bundleSkus = explode(';', $item['bundle_skus']);
            foreach ($bundleSkus as $sku) {
                if (!empty($sku)) {
                    $code = current(explode(':', $sku, 2));
                    if (empty($options[$code])) {
                        $options[$code] = array('code'=>$code,'title'=>null,
                            'type'=>$this->getConfiguredDefault('option', 'type'),
                            'required'=>$this->getConfiguredDefault('option', 'required'),
                            'position'=>$this->getConfiguredDefault('option', 'position'));
                    }
                }
            }
        }

        return $options;
    }

    /**
     * Get existing option from database.
     * Return empty array if none found.
     *
     * @param string $code
     * @param int $storeId
     * @param int $productId
     *
     * @return array
     */
    protected function _getExistingOption($code, $storeId, $productId)
    {
        $opt = $this->tablename('catalog_product_bundle_option');
        $optv = $this->tablename('catalog_product_bundle_option_value');

        $sql = "SELECT opt.*, optv.title AS code, optvs.title AS title, optvs.store_id AS store_id FROM $opt AS opt
            JOIN $optv AS optv ON optv.option_id = opt.option_id AND optv.store_id = 0 AND optv.title = :code
            LEFT JOIN $optv AS optvs ON optvs.option_id = opt.option_id AND optvs.store_id = :store_id
            WHERE
                opt.parent_id = :parent_id
            ORDER BY optv.store_id DESC
        ";

        $bind = array('parent_id'=>$productId,'code'=>$code,'store_id'=>$storeId);
        $existingOptions = $this->selectAll($sql, $bind);

        if (!empty($existingOptions)) {
            return $existingOptions[0];
        }

        return array();
    }

    /**
     * Associate products with options.
     *
     * @param array $item
     * @param array $params
     * @param array $options
     */
    protected function _linkProducts($item, $params, $options)
    {
        $skus = $this->_extractSkus($item);
        $cpbs = $this->tablename('catalog_product_bundle_selection');
        $cpr = $this->tablename("catalog_product_relation");

        foreach ($skus as $sku) {
            $optionCode = $sku['option_code'];
            $sku['option_id'] = $options[$optionCode]['option_id'];
            $sku['parent_product_id'] = $params['product_id'];
            $existingSku = $this->_getExistingSku($sku['option_id'], $sku['product_id']);

            if (!$this->_arraysEqual($existingSku, $sku, 'selection_id')) {
                if (!empty($existingSku['selection_id'])) {
                    $sku['selection_id'] = $existingSku['selection_id'];
                    $sql = "UPDATE $cpbs AS cpbs SET cpbs.position = :position, cpbs.is_default = :is_default, cpbs.selection_qty = :selection_qty, cpbs.selection_can_change_qty = :selection_can_change_qty, cpbs.selection_price_value = :selection_price_value, cpbs.selection_price_type = :selection_price_type WHERE cpbs.selection_id = :selection_id";
                    $bind = array('position'=>$sku['position'],'is_default'=>$sku['is_default'],
                        'selection_qty'=>$sku['selection_qty'],
                        'selection_can_change_qty'=>$sku['selection_can_change_qty'],
                        'selection_price_value'=>$sku['selection_price_value'],
                        'selection_price_type'=>$sku['selection_price_type'],'selection_id'=>$sku['selection_id']);
                    $this->update($sql, $bind);
                } else {
                    $sql = "INSERT INTO $cpbs (option_id, parent_product_id, product_id, position, is_default, selection_qty, selection_can_change_qty, selection_price_value, selection_price_type) VALUES(:option_id, :parent_product_id, :product_id, :position, :is_default, :selection_qty, :selection_can_change_qty, :selection_price_value, :selection_price_type)";
                    $bind = array('option_id'=>$sku['option_id'],'parent_product_id'=>$sku['parent_product_id'],
                        'product_id'=>$sku['product_id'],'position'=>$sku['position'],'is_default'=>$sku['is_default'],
                        'selection_qty'=>$sku['selection_qty'],
                        'selection_can_change_qty'=>$sku['selection_can_change_qty'],
                        'selection_price_value'=>$sku['selection_price_value'],
                        'selection_price_type'=>$sku['selection_price_type']);
                    $selectionId = $this->insert($sql, $bind);
                    $sku['selection_id'] = $selectionId;
                }
            } else {
                $sku['selection_id'] = $existingSku['selection_id'];
            }
             //show in frontend fix (thx igi8819)


            $sql = "INSERT IGNORE INTO $cpr (parent_id, child_id) VALUES(:parent_id, :child_id)";
            $bind = array(
             'parent_id' => $sku['parent_product_id'],
            'child_id' => $sku['product_id']
             );
            $this->insert($sql, $bind);
        }
    }

    /**
     * Extract skus and settings from $item['bundle_skus'].
     *
     * @param array $item
     *
     * @return array
     */
    protected function _extractSkus($item)
    {
        $skus = array();
        if (!empty($item['bundle_skus'])) {
            $bundleSkus = explode(';', $item['bundle_skus']);
            foreach ($bundleSkus as $bundleSku) {
                if (!empty($bundleSku)) {
                    $sku = array();
                    $bundleSku = explode(':', $bundleSku);
                    $bundleSku = $this->_trimArray($bundleSku);

                    $sku['option_code'] = $bundleSku[0];
                    $sku['sku'] = $bundleSku[1];
                    $sku['selection_qty'] = isset($bundleSku[2]) && $bundleSku[2] !== '' ? $bundleSku[2] : $this->getConfiguredDefault(
                        'sku', 'selection_qty');
                    $sku['selection_can_change_qty'] = isset($bundleSku[3]) && $bundleSku[3] !== '' ? $bundleSku[3] : $this->getConfiguredDefault(
                        'sku', 'selection_can_change_qty');
                    $sku['position'] = isset($bundleSku[4]) && $bundleSku[4] !== '' ? $bundleSku[4] : $this->getConfiguredDefault(
                        'sku', 'position');
                    $sku['is_default'] = isset($bundleSku[5]) && $bundleSku[5] !== '' ? $bundleSku[5] : $this->getConfiguredDefault(
                        'sku', 'is_default');
                    $sku['selection_price_value'] = isset($bundleSku[6]) && $bundleSku[6] !== '' ? $bundleSku[6] : $this->getConfiguredDefault(
                        'sku', 'selection_price_value');
                    $sku['selection_price_type'] = isset($bundleSku[7]) && $bundleSku[7] !== '' ? $bundleSku[7] : $this->getConfiguredDefault(
                        'sku', 'selection_price_type');
                    $cids = $this->getProductIds($sku['sku']);
                    $sku['product_id'] = $cids['pid'];

                    $skus[] = $sku;
                }
            }
        }

        return $skus;
    }

    /**
     * Get existing selection from database.
     * Return empty array if none found.
     *
     * @param int $option_id
     * @param int $product_id
     *
     * @return array
     */
    protected function _getExistingSku($option_id, $product_id)
    {
        $cpbs = $this->tablename('catalog_product_bundle_selection');

        $sql = "SELECT * FROM $cpbs
            WHERE
                option_id = :option_id AND
                product_id = :product_id
        ";

        $bind = array('option_id'=>$option_id,'product_id'=>$product_id);
        $existingSkus = $this->selectAll($sql, $bind);

        if (!empty($existingSkus)) {
            return $existingSkus[0];
        }

        return array();
    }

    /**
     * See if all fields in $dbArray are the same as in $csvArray.
     * Except for the field indicated by $idField.
     *
     * @param array $dbArray
     * @param array $csvArray
     * @param string $idField
     *
     * @return boolean
     */
    protected function _arraysEqual($dbArray, $csvArray, $idField = 'entity_id')
    {
        if (!is_array($dbArray) || !is_array($csvArray)) {
            return false;
        }
        if (empty($dbArray) && !empty($csvArray)) {
            return false;
        }

        foreach ($dbArray as $key => $value) {
            if ($key === $idField) {
                continue;
            }
            if (!isset($csvArray[$key]) || $csvArray[$key] != $value) {
                return false;
            }
        }
        return true;
    }

    /**
     * Trim all values in the array.
     *
     * @param array $arr
     *
     * @return array
     */
    protected function _trimArray(array $arr)
    {
        foreach ($arr as $key => $value) {
            if (is_string($value)) {
                $arr[$key] = trim($value);
            } elseif (is_array($value)) {
                $arr[$key] = $this->_trimArray($value);
            }
        }

        return $arr;
    }
}
