<?php
namespace ahat\ScormUpload\Tests;

use PHPUnit\Framework\TestCase;
use ahat\ScormUpload\UploadClass;

class UploadClassTest extends TestCase
{
    protected function setUp()
    {
        $this->stack = [];
    }

    public function testVirusCheck()
    {
        $upload = new UploadClass;
        $result = $upload->virusCheck( '/tmp/eicar_com.zip' );
        // var_dump( $result );
        $this->assertEquals( 'FOUND', $result['status'] );
    }

    public function testVirusMultiCheck()
    {
        $upload = new UploadClass;
        $results = $upload->virusMultiCheck( ['/tmp/eicar_com.zip','/tmp/clean_file.com', '/tmp/corrupt_file.zip'] );

        // var_dump( $results );

        $this->assertEquals( 'FOUND', $results[0]['status'] );
        $this->assertEquals( 'OK', $results[1]['status'] );
        $this->assertEquals( 'OK', $results[2]['status'] );
    }
}