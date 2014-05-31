<?php
require_once ("properties.php");

class Magmi_DataPumpFactory
{
    protected static $_factoryprops = null;

    static function getDataPumpInstance($pumptype)
    {
        if (self::$_factoryprops == null)
        {
            self::$_factoryprops = new Properties();
            self::$_factoryprops->load(dirname(__FILE__) . DIRSEP . "pumpfactory.ini");
        }
        $pumpinfo = self::$_factoryprops->get("DATAPUMPS", $pumptype, "");
        $arr = explode("::", $pumpinfo);
        if (count($arr) == 2)
        {
            $pumpfile = $arr[0];
            $pumpclass = $arr[1];
            
            try
            {
                require_once (dirname(__FILE__) . DIRSEP . "$pumpfile.php");
                $pumpinst = new $pumpclass();
            }
            catch (Exception $e)
            {
                $pumpinst = null;
            }
        }
        else
        {
            echo "Invalid Pump Type";
        }
        return $pumpinst;
    }
}