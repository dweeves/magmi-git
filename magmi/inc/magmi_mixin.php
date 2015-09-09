<?php

class Magmi_Mixin
{
    protected $_callers;

    public function bind($caller)
    {
        $this->_callers[] = $caller;
        $this->_callers = array_unique($this->_callers);
    }

    public function unbind($caller)
    {
        $ks = array_keys($this->_callers, $caller);
        if (count($ks) > 0)
        {
            foreach ($ks as $k)
            {
                unset($this->_callers[$k]);
            }
        }
    }

    public function __call($data, $arg)
    {
        if (substr($data, 0, 8) == "_caller_")
        {
            $data = substr($data, 8);
        }
        $ccallers = count($this->_callers);
        for ($i = 0; $i < $ccallers; $i++)
        {
            if (method_exists($this->_callers[$i], $data))
            {
                return call_user_func_array(array($this->_callers[$i],$data), $arg);
            }
            else
            {
                die("Invalid Method Call: $data - Not found in Caller");
            }
        }
    }
}