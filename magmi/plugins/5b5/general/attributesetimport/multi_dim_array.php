<?php
/**
 * <p>An array class which allows to use (one-dimensional, primitive-type arrays) as keys.<p>
 * <p>Acts very much like you would expect (in most cases ):
 * <code><br/>
 * $mda = new MultiDimArray();<br/>
 * <br/>
 * // Setting values:<br/>
 * $mda->offsetSet(array(1,2,3),"Test");<br/>
 * $mda->offsetSet(array(3,2),"Test2");<br/>
 * $mda[array(1,2,3)] = "Test" DOES NOT WORK because arrays are not allowed as index in this notation :(<br/>
 * <br/>
 * // Getting values:<br/>
 * $mda[array(1,2,3)] -> "Test"<br/>
 * $mda[array(1,2)] -> MultiDimArray() [ 3 => "Test" ]<br/>
 * $mda[array(1)] -> MultiDimArray() [ 2 => MultiDimArray () [3 => "Test" ]]<br/>
 * $mda[1] -> MultiDimArray() [ 2 => MultiDimArray () [3 => "Test" ]]<br/>
 * $mda[1][2][3] -> "Test"<br/>
 * <br/>
 * // isset / offsetExists<br/>
 * isset($mda[array(1,2,3)]) -> true<br/>
 * isset($mda[array(1,2,3,4)]) -> false<br/>
 * isset($mda[array(1,2)]) -> true (because $mda[array(1,2)]) also returns a value)<br/>
 * isset($mda[array(1)]) -> true  (same as above)<br/>
 * isset($mda[1]) -> true  (see above)
 * isset($mda[1][2][3]) -> true
 * isset($mda[array(2)]) -> false (keys are ordered!)<br/>
 *
 * $mda->offsetExistsPartly(array(1,2,3,4)) -> true (part of the offset exists)<br/>
 * $mda->offsetExistsPartly(array(1,2,4)) -> false<br/>
 * <br/>
 * // iterating:<br/>
 * // "foreach" does not allow arrays as keys :(<br/>
 * $mda->rewind();<br/>
 * while($mda->valid()) {<br/>
 *  $key = $mda->key(); // -> 1. iteration: array (1,2,3), 2. iteration: array (3,2)<br/>
 *  $value = $mda->current(); // -> 1. iteration:"Test", 2. iteration: "Test2"<br/>
 *  $mda->next();<br/>
 * }<br/>
 * </code></p>
 */
class MultiDimArray extends ArrayIterator
{
    private $_inner;
    private $_current;
    private $_currentKey;

    /**
     * (non-PHPdoc)
     * @see ArrayIterator::offsetGet()
     */
    public function offsetGet($name)
    {
        if (!is_array($name)) {
            return parent::offsetGet($name);
        } else {
            $key = array_shift($name);
            if (!parent::offsetExists($key)) {
                return null;
            }
            $element = parent::offsetGet($key);
            if (sizeof($name) == 0) {
                return $element;
            } else {
                if (is_a($element, 'MultiDimArray')) {
                    return $element->offsetGet($name);
                } else {
                    return $element;
                }
            }
        }
    }

    /**
     * (non-PHPdoc)
     * @see ArrayIterator::offsetSet()
     */
    public function offsetSet($name, $value)
    {
        if (!is_array($name)) {
            return parent::offsetSet($name, $value);
        } else {
            $key = array_shift($name);
            if (sizeof($name) == 0) {
                parent::offsetSet($key, $value);
            } else {
                $childArray = parent::offsetExists($key)?parent::offsetGet($key):new MultiDimArray();
                if (!is_a($childArray, 'MultiDimArray')) {
                    $childArray = new MultiDimArray();
                }
                parent::offsetSet($key, $childArray);
                $childArray->offsetSet($name, $value);
            }
        }
    }

    /**
     * Returns true, if the given offset exists. Offsets can also be given
     * partly e.g.:<br/>
     * <code>
     * offsetExists(array(1,2)) will return true, too, if offset array(1,2,3) is set to a value<br/>
     * but<br/>
     * offsetExists(array(1,2,3,4)) will return false<br/></code>
     * (non-PHPdoc)
     * @see ArrayIterator::offsetExists()
     */
    public function offsetExists($name)
    {
        return $this->offsetExistsPartly($name, false);
    }

    /**
     * If $partlyOk is set to false, works exactly like offsetExists().
     * If $partlyOk is set to true, it also returns true, if
     * only a part of the given $name array exists e.g.:<br/>
     * <code>
     * offsetExistsPartly(array(1,2,3,4),true) will additionally return true if one of the following offsets exists: array(1), array(1,2), array(1,2,3)<br/></code>
     *
     * @param unknown $name
     * @param string $partlyOk
     * @return void|boolean|string
     */
    public function offsetExistsPartly($name, $partlyOk=true)
    {
        if (!is_array($name)) {
            return parent::offsetExists($name);
        } else {
            $key = array_shift($name);
            if (sizeof($name) == 0) {
                return parent::offsetExists($key);
            } else {
                if (!parent::offsetExists($key)) {
                    return false;
                } else {
                    $object = parent::offsetGet($key);
                    if (is_a($object, 'MultiDimArray')) {
                        return $object->offsetExists($name);
                    } else {
                        return $partlyOk;
                    }
                }
            }
        }
    }

    public function offsetUnset($name)
    {
        if (!is_array($name)) {
            return parent::offsetUnset($name);
        } else {
            $key = array_shift($name);
            if (sizeof($name) == 0) {
                return parent::offsetUnset($key);
            } else {
                if (parent::offsetExists($key)) {
                    $object = parent::offsetGet($key);
                    if (is_a($object, 'MutliDimArray')) {
                        $object->offsetUnset($name);
                        if ($object->sizeof()==0) {
                            parent::offsetUnset($key);
                        }
                    } else {
                        parent::offsetUnset($key);
                    }
                }
            }
        }
    }

    public function rewind()
    {
        $this->_iterator = null;
        parent::rewind();
        $this->setInner();
    }

    public function current()
    {
        if (isset($this->_inner)) {
            return $this->_inner->current();
        } else {
            return parent::current();
        }
    }

    private function setInner()
    {
        if (parent::valid() && is_a(parent::current(), 'MultiDimArray')) {
            $this->_inner = parent::current();
            $this->_inner->rewind();
        }
    }

    private function doNext()
    {
        $this->inner = null;
        parent::next();
        $this->setInner();
    }

    public function next()
    {
        if (isset($this->_inner)) {
            $this->_inner->next();
            if (!$this->_inner->valid()) {
                $this->doNext();
            }
        } else {
            $this->doNext();
        }
    }
    public function key()
    {
        if (isset($this->_inner)) {
            $innerKey = $this->_inner->key();
            array_unshift($innerKey, parent::key());
            return $innerKey;
        } else {
            return array(parent::key());
        }
    }

    public function valid()
    {
        return parent::valid();
    }

    public function multiSet($keyArrays, $value)
    {
        foreach ($keyArrays as $keyArray) {
            if (is_array($keyArray) && sizeof($keyArray) == 0) {
                // ignore
            } else {
                $this->offsetSet($keyArray, $value);
            }
        }
    }

    public function count($mode = null)
    {
        $count = 0;
        foreach ($this as $item) {
            $count++;
        }
        return $count;
    }
}
