<?php

/**
 * Time counter class
 * This class provides a way to measure :
 *
 * - time
 * - counters
 *
 * store results into many categories for many timing aspects
 *
 * It is based on 2 levels :
 * - sources : labels under which timing categories will be stored, it is a label, most of the time, a method name
 * - categories : for each source, you can store many infos like "inDB","processing" and so on....
 *
 * time measure can be divided into phases, so you can measure different subparts of your processing like "initialization","lookup" aso....
 * many counters can also be defined at the same category leve
 * At the end, the timecounter will give this kind of results
 *
 *   + cat1
 *     +timers
 *       + phase1 => time for phase 1 of cat1 of source 1
 *       + phase2 => time for phase 2 of cat1 of source 1
 *     +counters
 *       + counter1 => value of counter 1 of cat 1 of source 1
 *   + cat2
 *
 * Sources, categories & phases could be added dynamically, each time a new phase/counter/category or source is
 * declared trough calls to addCounter, initTime,exitTime that does not exist yet, a container is created for it.
 *
 * sources are tags. categories are more like containers
 */
class timecounter
{
    protected $_timingcats = array();
    protected $_defaultsrc = "";
    protected $_timingcontext = array();

    /**
     * Constructor
     *
     * @param string $defaultsrc
     *            : default timing source
     */
    public function __construct($defaultsrc = "*")
    {
        $this->_defaultsrc = $defaultsrc;
    }

    /**
     * Initializes default timing categories to use
     *
     * @param unknown $tcats
     *            array of timing categories
     */
    public function initTimingCats($tcats)
    {
        foreach ($tcats as $tcat) {
            $this->_timingcats[$tcat] = array("_counters"=>array(),"_timers"=>array());
        }
    }

    /**
     * returns the content for a given timing category name
     *
     * @param string $cat
     *            : timing category name
     * @return array informations for given category
     */
    public function getTimingCategory($cat)
    {
        return $this->_timingcats[$cat];
    }

    /**
     * return all timers
     *
     * @return all timers info by category
     */
    public function getTimers()
    {
        $timers = array();
        foreach ($this->_timingcats as $cname => $info) {
            $timers[$cname] = $info['_timers'];
        }
        return $timers;
    }

    /**
     * return all counters
     *
     * @return all counters info by category
     */
    public function getCounters()
    {
        $counters = array();
        foreach ($this->_timingcats as $cname => $info) {
            $counters[$cname] = $info['_counters'];
        }
        return $counters;
    }

    /**
     * creates a new counter
     *
     * @param string $cname
     *            : counter name
     * @param string $tcats
     *            : array of category names to add counter to, if null => all categories
     */
    public function addCounter($cname, $tcats = null)
    {
        if ($tcats == null) {
            $tcats = array_keys($this->_timingcats);
        }

        foreach ($tcats as $tcat) {
            if (!isset($this->_timingcats[$tcat])) {
                $this->_timingcats[$tcat] = array("_counters"=>array(),"_timers"=>array());
            }
            $this->_timingcats[$tcat]["_counters"][$cname] = 0;
        }
    }

    /**
     * initializes a new counter
     *
     * @param string $cname
     *            : counter name
     * @param string $tcats
     *            : array of category names to initialize counter for, if null => all categories
     */
    public function initCounter($cname, $tcats = null)
    {
        if ($tcats == null) {
            $tcats = array_keys($this->_timingcats);
        }
        foreach ($tcats as $tcat) {
            $this->_timingcats[$tcat]["_counters"][$cname] = 0;
        }
    }

    /**
     * increments a counter
     *
     * @param string $cname
     *            : counter name
     * @param string $tcats
     *            : array of category names to initialize counter for, if null => all categories
     */
    public function incCounter($cname, $tcats = null)
    {
        if ($tcats == null) {
            $tcats = array_keys($this->_timingcats);
        }
        foreach ($tcats as $tcat) {
            if (!isset($this->_timingcats[$tcat]["_counters"][$cname])) {
                $this->_timingcats[$tcat]["_counters"][$cname] = 0;
            }
            $this->_timingcats[$tcat]["_counters"][$cname]++;
        }
    }

    /**
     * Initializes a timer
     *
     * @param string $phase
     *            : timer phase to initialize
     * @param string $src
     *            : source tag
     * @param string $tcat
     *            : timing category to initialize timer for
     */
    public function initTime($phase = "global", $src = null, $tcat = null)
    {
        if (isset($src)) {
            array_push($this->_timingcontext, $src);
            $this->_timingcontext = array_values(array_unique($this->_timingcontext));
        }
        if (count($this->_timingcontext) == 0) {
            return;
        }

        if (!isset($tcat)) {
            $tcats = $this->_timingcats;
        } else {
            $tcats = array($tcat=>$this->_timingcats[$tcat]);
        }
        $t = microtime(true);

        foreach ($tcats as $tcat => $dummy) {
            if (!isset($this->_timingcats[$tcat]["_timers"][$phase])) {
                $this->_timingcats[$tcat]["_timers"][$phase] = array();
            }
            $ctxc = count($this->_timingcontext);
            for ($i = 0; $i < $ctxc; $i++) {
                $src = $this->_timingcontext[$i];
                if (!isset($this->_timingcats[$tcat]["_timers"][$phase][$src])) {
                    $this->_timingcats[$tcat]["_timers"][$phase][$src] = array("init"=>$t,"dur"=>0);
                }
                $this->_timingcats[$tcat]["_timers"][$phase][$src]["start"] = $t;
            }
        }
    }

    /**
     * closes a timer for a given phase on a given category for a given source
     *
     * @param string $phase
     *            : time phase to exit
     * @param string $src
     *            : source tag
     * @param string $tcat
     *            : timing category
     */
    public function exitTime($phase, $src = null, $tcat = null)
    {
        $targets = $this->_timingcontext;
        if (count($targets) == 0) {
            return;
        }
        if (isset($src) && in_array($src, $this->_timingcontext)) {
            $targets = array($src);
        }

        $ctargets = count($targets);

        if ($ctargets == 0) {
            return;
        }
        if ($tcat == null) {
            $tcats = $this->_timingcats;
        } else {
            $tcats = array($tcat=>$this->_timingcats[$tcat]);
        }
        $end = microtime(true);
        foreach ($tcats as $tcat => $phasetimes) {
            for ($i = 0; $i < $ctargets; $i++) {
                $src = $targets[$i];
                if (isset($this->_timingcats[$tcat]["_timers"][$phase][$src])) {
                    $this->_timingcats[$tcat]["_timers"][$phase][$src]["end"] = $end;
                    $this->_timingcats[$tcat]["_timers"][$phase][$src]["dur"] += $end -
                         $this->_timingcats[$tcat]["_timers"][$phase][$src]["start"];
                } else {
                    echo "Invalid timing source : $src";
                }
            }
        }
        $this->_timingcontext = array_diff($this->_timingcontext, $targets);
    }
}
