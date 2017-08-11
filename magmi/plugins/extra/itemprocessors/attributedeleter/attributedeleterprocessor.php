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
 * Attribute Deleter
 *
 * @author Björn Tantau <bjoern.tantau@limora.com>
 *
 * This allows select and multiselect attributes to be deleted in create mode.
 */
class AttributeDeleter extends Magmi_ItemProcessor
{
    public static $_VERSION = '1.0';

    public function initialize($params)
    {
        // declare current class as attribute handler
        $this->registerAttributeHandler($this, array("frontend_input:(select|multiselect)"));
    }

    public function getPluginUrl()
    {
        return $this->pluginDocUrl('Attribute_Deleter');
    }

    public function getPluginVersion()
    {
        return self::$_VERSION;
    }

    public function getPluginName()
    {
        return 'Attribute Deleter';
    }

    public function getPluginAuthor()
    {
        return 'Björn Tantau';
    }

    public function getShortDescription()
    {
        return 'This plugin deletes select/multiselect attributes with a value of "__MAGMI_DELETE__".';
    }

    /**
     * attribute handler for Int typed attributes
     *
     * @param int $pid
     *            : product id
     * @param array $item
     *            : item to inges
     * @param int $storeid
     *            : store for attribute value storage
     * @param int $attrcode
     *            : attribute code
     * @param array $attrdesc
     *            : attribute metadata
     * @param mixed $ivalue
     *            : input value to import
     * @return new int value to set
     *
     *         Many attributes are int typed, so we need to handle all cases like :
     *         - select
     *         - tax id
     *         - boolean
     *         - status
     *         - visibility
     */
    public function handleIntAttribute($pid, &$item, $storeid, $attrcode, $attrdesc, $ivalue)
    {
        if ($attrdesc["frontend_input"] == "select" && $ivalue == '__MAGMI_DELETE__')
        {
            return $ivalue;
        }
        return null;
    }
}