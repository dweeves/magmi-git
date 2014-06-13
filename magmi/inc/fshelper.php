<?php
require_once ('remotefilegetter.php');

/**
 * Class FSHelper
 *
 * File System Helper
 * Gives several utility methods for filesystem testing
 * 
 * @author dweeves
 *        
 */
class FSHelper
{

    /**
     * Checks if a directory has write rights
     * 
     * @param string $dir
     *            directory to test
     * @return boolean wether directory is writable
     */
    public static function isDirWritable($dir)
    {
        // try to create a new file
        $test = @fopen("$dir/__testwr__", "w");
        if ($test == false)
        {
            return false;
        }
        else
        {
            // if succeeded, remove test file
            fclose($test);
            unlink("$dir/__testwr__");
        }
        return true;
    }

    /**
     * Tries to find a suitable way to execute processes
     * 
     * @return string NULL method to execute process
     */
    public static function getExecMode()
    {
        $is_disabled = array();
        // Check for php disabled functions
        $disabled = explode(',', ini_get('disable_functions'));
        foreach ($disabled as $disableFunction)
        {
            $is_disabled[] = trim($disableFunction);
        }
        // try the following if not disabled,return first non disabled
        foreach (array("popen","shell_exec") as $func)
        {
            if (!in_array($func, $is_disabled))
            {
                return $func;
            }
        }
        return null;
    }
}

/**
 * Factory for magento directory handle
 *
 * @author dweeves
 *        
 */
class MagentoDirHandlerFactory
{
    protected $_handlers = array();
    protected static $_instance;

    public function __construct()
    {}

    /**
     * Singleton getInstance method
     * 
     * @return MagentoDirHandlerFactory
     */
    public static function getInstance()
    {
        if (!isset(self::$_instance))
        {
            self::$_instance = new MagentoDirHandlerFactory();
        }
        return self::$_instance;
    }

    /**
     * Registers a new object to handle magento directory
     * 
     * @param unknown $obj            
     */
    public function registerHandler($obj)
    {
        $cls = get_class($obj);
        if (!isset($this->_handlers[$cls]))
        {
            $this->_handlers[$cls] = $obj;
        }
    }

    /**
     * Return a handler for a given url
     * 
     * @param unknown $url            
     * @return unknown
     */
    public function getHandler($url)
    {
        // Iterates on declared handlers , return first matching url
        foreach ($this->_handlers as $cls => $handler)
        {
            if ($handler->canHandle($url))
            {
                return $handler;
            }
        }
    }
}

/**
 * Magento Directory Handler
 *
 * Provides methods for filesystem operations & command execution
 * Mother abstract class to be derived either for local operation or remote (for performing operations on remote systems)
 * 
 * @author dweeves
 *        
 */
abstract class MagentoDirHandler
{
    protected $_magdir;
    protected $_lasterror;
    protected $_exec_mode;

    /**
     * Constructor from a magento directory url
     * 
     * @param unknown $magurl
     *            magento base directory url
     */
    public function __construct($magurl)
    {
        $this->_magdir = $magurl;
        $this->_lasterror = array();
        $this->_exec_mode = FSHelper::getExecMode();
    }

    /**
     * Returns magento directory
     * 
     * @return string
     */
    public function getMagentoDir()
    {
        return $this->_magdir;
    }

    /**
     * Returns available execution mode
     * 
     * @return Ambigous <string, NULL>
     */
    public function getexecmode()
    {
        return $this->_exec_mode;
    }

    /**
     * Wether current handler is compatible with given url
     * 
     * @param unknown $url            
     */
    public abstract function canhandle($url);

    /**
     * File exists
     * 
     * @param unknown $filepath            
     */
    public abstract function file_exists($filepath);

    /**
     * Mkdir
     * 
     * @param unknown $path            
     * @param string $mask            
     * @param string $rec            
     */
    public abstract function mkdir($path, $mask = null, $rec = false);

    /**
     * File Copy
     * 
     * @param unknown $srcpath            
     * @param unknown $destpath            
     */
    public abstract function copy($srcpath, $destpath);

    /**
     * File Deletion
     * 
     * @param unknown $path            
     */
    public abstract function unlink($path);

    /**
     * Chmod
     * 
     * @param unknown $path            
     * @param unknown $mask            
     */
    public abstract function chmod($path, $mask);

    /**
     * Check if we can execute processes
     * 
     * @return boolean
     */
    public function isExecEnabled()
    {
        return $this->_exec_mode != null;
    }

    /**
     * Executes a process
     * 
     * @param unknown $cmd            
     * @param unknown $params            
     * @param string $workingdir            
     */
    public abstract function exec_cmd($cmd, $params, $workingdir = null);
}

/**
 * Local Magento Dir Handler.
 *
 * Handle Magento related filesystem operations for a given local directory
 * 
 * @author dweeves
 *        
 */
class LocalMagentoDirHandler extends MagentoDirHandler
{
    protected $_rfgid;

    /**
     * Constructor
     * 
     * @param unknown $magdir            
     */
    public function __construct($magdir)
    {
        parent::__construct($magdir);
        // Registers itself in the factory
        MagentoDirHandlerFactory::getInstance()->registerHandler($this);
        $this->_rfgid = "default";
    }

    /**
     * Can Handle any non remote urls
     * 
     * @param unknown $url            
     * @return boolean
     */
    public function canHandle($url)
    {
        return (preg_match("|^.*?://.*$|", $url) == false);
    }

    /**
     * Cleans a bit input filename, ensures filename will be located under magento directory if not already
     * 
     * @see MagentoDirHandler::file_exists()
     */
    public function file_exists($filename)
    {
        $mp = str_replace("//", "/", $this->_magdir . "/" . str_replace($this->_magdir, '', $filename));
        
        return file_exists($mp);
    }

    /**
     * Specific, set remote operation credentials for local file download
     */
    public function setRemoteCredentials($user, $passwd)
    {
        $fginst = RemoteFileGetterFactory::getFGInstance($this->_rfgid);
        $fginst->setCredentials($user, $passwd);
    }

    /**
     * Handles a remote file getter id
     * 
     * @param unknown $rfgid            
     */
    public function setRemoteGetterId($rfgid)
    {
        $this->_rfgid = $rfgid;
    }

    /**
     * ensures dirname will be located under magento directory if not already
     * 
     * @see MagentoDirHandler::mkdir()
     */
    public function mkdir($path, $mask = null, $rec = false)
    {
        $mp = str_replace("//", "/", $this->_magdir . "/" . str_replace($this->_magdir, '', $path));
        
        if ($mask == null)
        {
            $mask = octdec('755');
        }
        $ok = @mkdir($mp, $mask, $rec);
        if (!$ok)
        {
            $this->_lasterror = error_get_last();
        }
        return $ok;
    }

    /**
     * ensures path will be located under magento directory if not already
     * 
     * @see MagentoDirHandler::chmod()
     */
    public function chmod($path, $mask)
    {
        $mp = str_replace("//", "/", $this->_magdir . "/" . str_replace($this->_magdir, '', $path));
        
        if ($mask == null)
        {
            $mask = octdec('755');
        }
        $ok = @chmod($mp, $mask);
        if (!$ok)
        {
            $this->_lasterror = error_get_last();
        }
        return $ok;
    }

    /**
     * Returns last error
     * 
     * @return Ambigous <multitype:, multitype:string multitype: >
     */
    public function getLastError()
    {
        return $this->_lasterror;
    }

    /**
     * ensures filename will be located under magento directory if not already
     * 
     * @see MagentoDirHandler::unlink()
     */
    public function unlink($path)
    {
        $mp = str_replace("//", "/", $this->_magdir . "/" . str_replace($this->_magdir, '', $path));
        return @unlink($mp);
    }

    /**
     * Download a file into local filesystem
     * ensures local filename will be located under magento directory if not already
     * 
     * @param unknown $remoteurl            
     * @param unknown $destpath            
     * @return unknown
     */
    public function copyFromRemote($remoteurl, $destpath)
    {
        $rfg = RemoteFileGetterFactory::getFGInstance($this->_rfgid);
        $mp = str_replace("//", "/", $this->_magdir . "/" . str_replace($this->_magdir, '', $destpath));
        $ok = $rfg->copyRemoteFile($remoteurl, $mp);
        if (!$ok)
        {
            $this->_lasterror = $rfg->getErrors();
        }
        return $ok;
    }

    /**
     * ensures filename will be located under magento directory if not already
     * 
     * @see MagentoDirHandler::copy()
     */
    public function copy($srcpath, $destpath)
    {
        $result = false;
        $destpath = str_replace("//", "/", $this->_magdir . "/" . str_replace($this->_magdir, '', $destpath));
        if (preg_match('|^.*?://.*$|', $srcpath))
        {
            $result = $this->copyFromRemote($srcpath, $destpath);
        }
        else
        {
            
            $result = @copy($srcpath, $destpath);
            if (!$result)
            {
                $this->_lasterror = error_get_last();
            }
        }
        return $result;
    }

    /**
     * execute command, performs some execution directory check
     * uses available command execution method
     * 
     * @see MagentoDirHandler::exec_cmd()
     */
    public function exec_cmd($cmd, $params, $working_dir = null)
    {
        $full_cmd = $cmd . " " . $params;
        $curdir = false;
        $precmd = "";
        // If a working directory has been specified, switch to it
        // before running the requested command
        if (!empty($working_dir))
        {
            $curdir = getcwd();
            $wdir = realpath($working_dir);
            // get current directory
            if ($curdir != $wdir && $wdir !== false)
            {
                // trying to change using chdir
                if (!@chdir($wdir))
                {
                    // if no success, use cd from shell
                    $precmd = "cd $wdir && ";
                }
            }
        }
        $full_cmd = $precmd . $full_cmd;
        // Handle Execution
        $emode = $this->getexecmode();
        switch ($emode)
        {
            case "popen":
                $x = popen($full_cmd, "r");
                $out = "";
                while (!feof($x))
                {
                    $data = fread($x, 1024);
                    $out .= $data;
                    usleep(100000);
                }
                fclose($x);
                break;
            case "shell_exec":
                $out = shell_exec($full_cmd);
                break;
        }
        
        // restore old directory if changed
        if ($curdir)
        {
            @chdir($curdir);
        }
        
        if ($out == null)
        {
            $this->_lasterror = array("type"=>" execution error","message"=>error_get_last());
            return false;
        }
        return $out;
    }
}