<?php
namespace ahat\ScormUpload\Tests;

use PHPUnit\Framework\TestCase;
use ahat\ScormUpload\UnzipClass;

class UnzipClassTest extends TestCase
{
    protected function setUp()
    {
        $this->stack = [];
    }


    public function testUnzipRemove()
    {
        $file = './tests/testfiles/eicar_com.zip';
        $destination = UnzipClass::getTempUnzipDir() . DIRECTORY_SEPARATOR . basename( $file );
        $unzip = new UnzipClass;
        $unzip->unzip( $file, $destination );
        $this->assertDirectoryExists( $destination, $file . ' unzip to '. $destination . ' failed.' );
        $unzip->removeZip( $destination );
        $this->assertDirectoryNotExists( $destination, $destination . ' removal failed.' );
    }

}