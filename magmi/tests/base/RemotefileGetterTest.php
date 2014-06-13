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
        $exists = $rfg->urlExists('http://clarastream.com.s3.amazonaws.com/5395fd5bdd919a25008b4567/01497212-_1.jpg', 
            self::$_dldir . '/amazon.jpg');
        $this->assertTrue($exists);
        $this->assertFileExists(self::$_dldir . '/globe.png');
    }

    public function testHttpAmazonS3NoLookup()
    {
        $rfg = self::$_rfg;
        $rfg->copyRemoteFile('http://clarastream.com.s3.amazonaws.com/5395fd5bdd919a25008b4567/01497212-_1.jpg', 
            self::$_dldir . '/amazon.jpg');
        $errs = $rfg->getErrors();
        $this->assertCount(0, $errs);
        $this->assertFileExists(self::$_dldir . '/globe.png');
    }
}