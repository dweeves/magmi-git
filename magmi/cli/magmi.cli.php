<?php

/**
 * MAGENTO MASS IMPORTER CLI SCRIPT
 * 
 * version : 0.1
 * author : S.BRACQUEMONT aka dweeves
 * updated : 2010-08-02
 * 
 */
require_once (dirname(dirname(__FILE__)) . "/inc/magmi_defs.php");
require_once ('magmi_loggers.php');
$script = array_shift($argv);

/**
 * Builing option dictionnary from command line
 * 
 * @param array $argv            
 * @return dictionnary with option list
 */
function buildOptions($argv)
{
    $options = array();
    foreach ($argv as $option)
    {
        $isopt = $option[0] == "-";
        
        if ($isopt)
        {
            $optarr = explode("=", substr($option, 1), 2);
            $optname = $optarr[0];
            if (count($optarr) > 1)
            {
                $optval = $optarr[1];
            }
            else
            {
                $optval = 1;
            }
            $options[$optname] = $optval;
        }
    }
    return $options;
}

/**
 * returns an instance of given class , using filename(without php):classname syntax
 * 
 * @param string $cval
 *            , filename (without php):classname value
 * @param string $cdir
 *            , directory to search class in
 * @return instance of class or die with error
 */
function getClassInstance($cval, $cdir = ".")
{
    $cdef = explode(":", $cval);
    $cname = $cdef[0];
    $cclass = $cdef[1];
    $cinst = null;
    $cfile = "$cdir/$cname.php";
    if (file_exists($cfile))
    {
        require_once ($cfile);
        if (class_exists($cclass))
        {
            $cinst = new $cclass();
        }
    }
    if ($cinst == null)
    {
        die("Invalid class definition : " . $cval);
    }
    return $cinst;
}

/**
 * Returns the wanted engine from an option list
 * If engine not set in option list, uses default magmi product import engine
 * 
 * @param array $options            
 * @return Engine instance or die if not found
 */
function getEngineInstance($options)
{
    if (!isset($options["engine"]))
    {
        $options["engine"] = "magmi_productimportengine:Magmi_ProductImportEngine";
    }
    $enginst = getClassInstance($options["engine"], dirname(dirname(__FILE__)) . "/engines");
    return $enginst;
}

// Building option list from command line
$options = buildOptions($argv);
// Getting engine
$importer = getEngineInstance($options);
if (isset($importer))
{
    // if logger set, use it or use FileLogger by default
    $loggerclass = isset($options['logger']) ? $options['logger'] : "FileLogger";
    $importer->setLogger(new $loggerclass());
    // a chain is a multiple profile run with the following syntax
    // [profilename]:[modename],[profilename]:[modename]
    // if no workflow chain is defined, create a new one
    if (!isset($options["chain"]))
    {
        
        $options["chain"] = isset($options["profile"]) ? $options["profile"] : "";
        $options["chain"] .= isset($options["mode"]) ? ":" . $options["mode"] : "";
    }
    // parsing the workflow chain
    $pdefs = explode(",", $options["chain"]);
    // for each import in the workflow
    foreach ($pdefs as $pdef)
    {
        $pm = explode(":", $pdef);
        $eargv = array();
        // parse workflow definition
        // recreate profile & mode parameters
        if (!empty($pm[0]))
        {
            $eargv[] = "-profile=" . $pm[0];
        }
        if (isset($pm[1]))
        {
            $eargv[] = "-mode=" . $pm[1];
        }
        // build options based on profile & mode
        $eoptions = buildOptions($eargv);
        // launch import
        $importer->run(array_merge($eoptions, $options));
    }
}
?>