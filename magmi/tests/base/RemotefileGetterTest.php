<?php
require_once (__DIR__ . '/../../inc/remotefilegetter.php');

function rmrf($dir)
{
    $it = new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS);
    $files = new RecursiveIteratorIterator($it, RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($files as $file)
    {
        if ($file->getFilename() === '.' || $file->getFilename() === '..')
        {
            continue;
        }
        if ($file->isDir())
        {
            rmdir($file->getRealPath());
        }
        else
        {
            unlink($file->getRealPath());
        }
    }
    rmdir($dir);
}

class RemoteFileGetterTest extends PHPUnit_Framework_TestCase
{
    protected static $_rfg;
    protected static $_dldir;

    public static function setupBeforeClass()
    {
        self::$_rfg = new CURL_RemoteFileGetter();
        self::$_dldir = __DIR__ . '/tmp';
        @mkdir(self::$_dldir, 0755);
    }
    
    public static function tearDownAfterClass()
    {
        rmrf(self::$_dldir);
    }

    public function testInvalidCopy()
    {
        $rfg = self::$_rfg;
        $rfg->copyRemoteFile('invalid', 'toto');
        $errs = $rfg->getErrors();
        $this->assertArrayHasKey('exception', $errs);
    }

    public function testHttpFileCopyOK()
    {
        $rfg = self::$_rfg;
        $rfg->copyRemoteFile('http://softarchconsulting.com/img/globe.png', self::$_dldir . '/globe.png');
        $errs = $rfg->getErrors();
        $this->assertCount(0, $errs);
        $this->assertFileExists(self::$_dldir . '/globe.png');
    }

    public function testHttpFileCopyKO()
    {
        $rfg = self::$_rfg;
        $rfg->copyRemoteFile('http://softarchconsulting.com/img/globe2.png', self::$_dldir . '/globe2.png');
        $errs = $rfg->getErrors();
        $this->assertArrayHasKey('exception', $errs);
        $this->assertFileNotExists(self::$_dldir . '/globe2.png');
    }

    public function testHttpAmazonS3Lookup()
    {
        $rfg = self::$_rfg;
        $exists = $rfg->urlExists('https://s3-eu-west-1.amazonaws.com/dweeves/magmitest/Logo.png');
        $this->assertTrue($exists);
        $this->assertFileExists(self::$_dldir . '/globe.png');
    }

    public function testHttpAmazonS3NoLookup()
    {
        $rfg = self::$_rfg;
        $rfg->copyRemoteFile('https://s3-eu-west-1.amazonaws.com/dweeves/magmitest/Logo.png', 
            self::$_dldir . '/Logo.png');
        $errs = $rfg->getErrors();
        $this->assertCount(0, $errs);
        $this->assertFileExists(self::$_dldir . '/Logo.png');
    }
    
    public function testFtpAuthenticatedKO()
    {
        $rfg = self::$_rfg;
        $rfg->setCredentials('testftp','badpass');
        $rfg->copyRemoteFile('ftp://www.softarchconsulting.com/ruby-2.1.1.tar.gz',
            self::$_dldir . '/ruby-2.1.1.tar.gz');
        $errs = $rfg->getErrors();
        $this->assertArrayHasKey('exception', $errs);
        $this->assertFileNotExists(self::$_dldir . '/ruby-2.1.1.tar.gz');
    }
    
    public function testFtpAuthenticatedOK()
    {
        $rfg = self::$_rfg;
        $rfg->setCredentials('testftp','test123');
        $rfg->copyRemoteFile('ftp://www.softarchconsulting.com/ruby-2.1.1.tar.gz',
            self::$_dldir . '/ruby-2.1.1.tar.gz');
        $errs = $rfg->getErrors();
        if(isset($errs["message"]))
        {
            echo $errs["message"];
        }
        $this->assertCount(0, $errs);
        
        $this->assertFileExists(self::$_dldir . '/ruby-2.1.1.tar.gz');
        
    }
}