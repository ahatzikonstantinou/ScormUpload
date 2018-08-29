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

    public function testValidate()
    {
        //test valid manifest
        //
        $zip = 'valid_manifest.zip';
        $file = 'imsmanifest.xml';
        $result = $this->validateFile( $zip, $file ) ;

        // var_dump( $result );

        $this->assertTrue( $result['valid'], 'valid_manifest.zip is valid' );


        //test no manifest
        //
        $zip = 'eicar_com.zip';
        $file = 'imsmanifest.xml';
        $result = $this->validateFile( $zip, $file ) ;

        // var_dump( $result );

        $this->assertFalse( $result['valid'], 'eicar_com.zip is not valid' );


        //test empty manifest
        //
        $zip = 'empty_manifest.zip';
        $file = 'imsmanifest.xml';
        $result = $this->validateFile( $zip, $file ) ;

        // var_dump( $result );

        $this->assertFalse( $result['valid'], 'empty_manifest.zip is not valid' );


        //test invalid xml manifest
        //
        $zip = 'invalid_xml_manifest.zip';
        $file = 'imsmanifest.xml';
        $result = $this->validateFile( $zip, $file ) ;

        // var_dump( $result );

        $this->assertFalse( $result['valid'], 'invalid_xml_manifest.zip is not valid' );


        //test manifest no version
        //
        $zip = 'no_version_manifest.zip';
        $file = 'imsmanifest.xml';
        $result = $this->validateFile( $zip, $file ) ;

        // var_dump( $result );

        $this->assertFalse( $result['valid'], 'no_version_manifest.zip is not valid' );


        //test manifest no resources
        //
        $zip = 'no_resources_manifest.zip';
        $file = 'imsmanifest.xml';
        $result = $this->validateFile( $zip, $file ) ;

        // var_dump( $result );

        $this->assertFalse( $result['valid'], 'no_resources_manifest.zip is not valid' );


        //test manifest no launcher
        //
        $zip = 'no_launcher_manifest.zip';
        $file = 'imsmanifest.xml';
        $result = $this->validateFile( $zip, $file ) ;

        // var_dump( $result );

        $this->assertFalse( $result['valid'], 'no_launcher_manifest.zip is not valid' );


        //test manifest wrong schema version
        //
        $zip = 'wrong_schemaversion_manifest.zip';
        $file = 'imsmanifest.xml';
        $result = $this->validateFile( $zip, $file ) ;

        // var_dump( $result );

        $this->assertFalse( $result['valid'], 'wrong_schemaversion_manifest.zip is not valid' );
    }

    private function validateFile( $zip, $file )
    {
        $upload = new UploadClass;
        return $upload->validate( './tests/testfiles/' . $zip );
    }
}