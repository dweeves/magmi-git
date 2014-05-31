<?php

function tdarray_to_js($container, $mainarr, $prefix)
{
    $varr = array();
    $vlist = explode(",", $container->getParam($mainarr));
    foreach ($vlist as $k)
    {
        $v = $container->getParam("$prefix:" . rawurlencode($k));
        $v = addslashes($v);
        $varr[] = "\"$k\":\"$v\"";
    }
    return "{" . implode(",", $varr) . "}";
}