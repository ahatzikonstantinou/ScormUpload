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
        $result = $this->validateFile( 'valid_manifest.zip', true ) ;
        // var_dump( $result );


        //test no manifest
        $result = $this->validateFile( 'eicar_com.zip' ) ;
        // var_dump( $result );

        
        $result = $this->validateFile( 'empty_manifest.zip' ) ;
        // var_dump( $result );


        $result = $this->validateFile( 'invalid_xml_manifest.zip' ) ;
        // var_dump( $result );
        
        
        $result = $this->validateFile( 'no_version_manifest.zip' ) ;
        // var_dump( $result );
        

        $result = $this->validateFile( 'no_resources_manifest.zip' ) ;
        // var_dump( $result );


        $result = $this->validateFile( 'no_launcher_manifest.zip' ) ;
        // var_dump( $result );


        $result = $this->validateFile( 'wrong_schemaversion_manifest.zip' ) ;
        // var_dump( $result );


        //test valid captivate package
        $result = $this->validateFile( 'CodexData_test_LearnWorlds.zip', true ) ;
        // var_dump( $result );


        $result = $this->validateFile( 'no_schemaVersion_property_project.zip' ) ;
        // var_dump( $result );


        $result = $this->validateFile( 'wrong_schemaVersion_project.zip' ) ;
        // var_dump( $result );

        
        $result = $this->validateFile( 'no_launchFile_property_project.zip' ) ;
        // var_dump( $result );


        $result = $this->validateFile( 'empty_launchFile_project.zip' );
        // var_dump( $result );
    }

    private function validateFile( $zip, $assertTrue = false )
    {
        $upload = new UploadClass;
        $result = $upload->validate( './tests/testfiles/' . $zip, true, true );

        if( $assertTrue ) {
            $this->assertTrue( $result['valid'], $zip . ' is valid' );
        } else {
            $this->assertFalse( $result['valid'], $zip . ' is not valid' );
        }

        return $result;
    }
}