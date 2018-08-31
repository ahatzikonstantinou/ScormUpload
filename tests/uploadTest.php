<?php
namespace ahat\ScormUpload\Tests;

use PHPUnit\Framework\TestCase;
use ahat\ScormUpload\UploadClass;
use ahat\ScormUpload\GCSClass;
use Exception;

class UploadClassTest extends TestCase
{
    protected function setUp()
    {
        $this->stack = [];
    }

    public function testVirusCheck()
    {
        $upload = new UploadClass;
        $result = $upload->virusCheck( 'eicar_com.zip' );
        // var_dump( $result );
        $this->assertEquals( 'FOUND', $result['status'] );
    }

    public function testVirusMultiCheck()
    {
        $upload = new UploadClass;
        $results = $upload->virusMultiCheck( ['eicar_com.zip','clean_file.com', 'corrupt_file.zip'] );

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

        //IFRS-for-SMEs-e-learning-module.zip does not contain imsmanifest.xml or project.txt
        $result = $this->validateFile( 'IFRS-for-SMEs-e-learning-module.zip' );
        // var_dump( $result );

        $result = $this->validateFile( 'Airport Known Supplier - Storyline output.zip', true );
        // var_dump( $result );

        $result = $this->validateFile( 'A-CMP300 Ver9.zip', true );
        
    }

    private function validateFile( $zip, $assertTrue = false )
    {
        $upload = new UploadClass;
        $result = $upload->validate( './tests/testfiles/' . $zip, true, true );

        // var_dump( $result );

        if( $assertTrue ) {
            $this->assertTrue( $result['valid'], $zip . ' is not valid' );
        } else {
            $this->assertFalse( $result['valid'], $zip . ' is valid' );
        }

        return $result;
    }

    public function testUpload()
    {        
        $upload = new UploadClass;

        $zip = './tests/testfiles/CodexData_test_LearnWorlds.zip';
        $result = $upload->uploadZip( getenv( 'GOOGLE_CLOUD_STORAGE_BUCKET' ), 'test3', $zip );
        // var_dump( $result );
        $this->assertTrue( $result['success'], $zip . ' upload failed.'  );
        
        $zip = './tests/testfiles/IFRS-for-SMEs-e-learning-module.zip';
        $result = $upload->uploadZip( getenv( 'GOOGLE_CLOUD_STORAGE_BUCKET' ), 'test3', $zip );
        // var_dump( $result );
        $this->assertFalse( $result['success'], $zip . ' upload failed because ' . $zip . ' is not a recognizablke package.'  );


    }

    public function testReplace()
    {   
        $folderId = 'test3';
        $old = 'CodexData_test_LearnWorlds';
        $oldZip = './tests/testfiles/' . $old . '.zip';
        $new = './tests/testfiles/Airport Known Supplier - Storyline output.zip';

        $gcs = new GCSClass( getenv( 'GOOGLE_CLOUD_STORAGE_BUCKET' ) );

        //ensure we start clean
        $deleted = $gcs->removePackage( $folderId, $old );
        $remaining = count( $gcs->listFolder( $folderId . '/' . $old ) );
        $this->assertTrue( $remaining == 0, 'Removal of ' . $old . ' failed. ' . $remaining . ' files remaining.' );

        //upload the old package
        $upload = new UploadClass;
        $result = $upload->uploadZip( getenv( 'GOOGLE_CLOUD_STORAGE_BUCKET' ),  $folderId, $oldZip );
        $this->assertTrue( $result['success'], $oldZip . ' upload failed.'  );
        
        //replace with new
        $result = $upload->replacePackage( getenv( 'GOOGLE_CLOUD_STORAGE_BUCKET' ), $folderId, $old, $new );
        // var_dump( $result );
        $this->assertTrue( $result['success'], 'Replacement with ' . $new . ' failed. ' . $result['uploaded'] . ' were uploaded' );

        $remaining = count( $gcs->listFolder( $folderId . '/' . $old ) );
        $this->assertTrue( $remaining == 0, 'Replacement of ' . $old . ' failed. ' . $remaining . ' old files remaining.' );
    }

    public function testRemove()
    {
        $upload = new UploadClass;

        $zip = './tests/testfiles/CodexData_test_LearnWorlds.zip';
        $package = pathinfo( $zip, PATHINFO_FILENAME );
        $folderId = 'test2';
        $result = $upload->uploadZip( getenv( 'GOOGLE_CLOUD_STORAGE_BUCKET' ), $folderId, $zip );
        // var_dump( $result );
        $this->assertTrue( $result['success'], $zip . ' upload failed.'  );

        $upload->removePackage( getenv( 'GOOGLE_CLOUD_STORAGE_BUCKET' ), $folderId, $package );

        $gcs = new GCSClass( getenv( 'GOOGLE_CLOUD_STORAGE_BUCKET' ) );
        $remaining = count( $gcs->listFolder( $folderId . '/' . $package ) );

        $this->assertTrue( $remaining == 0, 'Removal of ' . $package . ' failed. ' . $remaining . ' files remaining.' );
    }
}