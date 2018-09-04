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
        // var_dump( $result );
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
        $scormId = 'id3';
        $result = $upload->uploadZip( getenv( 'GOOGLE_CLOUD_STORAGE_BUCKET' ), 'test3', $scormId, $zip );
        // var_dump( $result );
        $this->assertTrue( $result['success'], $zip . ' upload failed.'  );

        // //clean up
        // $gcs = new GCSClass( getenv( 'GOOGLE_CLOUD_STORAGE_BUCKET' ) );
        // $gcs->removePackage( $folderId, $scormId );

        
        $zip = './tests/testfiles/IFRS-for-SMEs-e-learning-module.zip';
        $scormId = 'id4';
        $result = $upload->uploadZip( getenv( 'GOOGLE_CLOUD_STORAGE_BUCKET' ), 'test3', $scormId, $zip );
        // var_dump( $result );
        $this->assertFalse( $result['success'], $zip . ' upload succeeded although ' . $zip . ' is not a recognizablke package!!!'  );


    }

    public function testReplace()
    {   
        $folderId = 'test3';
        $scormId = 'id3';
        $oldZip = './tests/testfiles/CodexData_test_LearnWorlds.zip';
        $new = './tests/testfiles/Airport Known Supplier - Storyline output.zip';

        $gcs = new GCSClass( getenv( 'GOOGLE_CLOUD_STORAGE_BUCKET' ) );

        //ensure we start clean
        $deleted = $gcs->removePackage( $folderId, $scormId );
        $remaining = count( $gcs->listFolder( $folderId . '/' . $scormId ) );
        $this->assertTrue( $remaining == 0, 'Removal of ' . $scormId . ' failed. ' . $remaining . ' files remaining.' );

        //upload the old package
        $upload = new UploadClass;
        $result = $upload->uploadZip( getenv( 'GOOGLE_CLOUD_STORAGE_BUCKET' ),  $folderId, $scormId, $oldZip );
        $this->assertTrue( $result['success'], $oldZip . ' upload failed.'  );
        
        //replace with new
        $result = $upload->replacePackage( getenv( 'GOOGLE_CLOUD_STORAGE_BUCKET' ), $folderId, $scormId, $new );
        // var_dump( $result );
        $this->assertTrue( $result['success'], 'Replacement with ' . $new . ' failed. ' . $result['uploaded'] . ' were uploaded' );

        $current = count( $gcs->listFolder( $folderId . '/' . $scormId ) );
        $this->assertTrue( $current == $result['uploaded'], 'Replacement of ' . $scormId . ' seems to have failed. ' . $current . ' files found instead of ' . $result['uploaded'] . ' uploaded.' );

        //clean up
        $gcs->removePackage( $folderId, $scormId );
    }

    public function testRemove()
    {
        $upload = new UploadClass;

        $zip = './tests/testfiles/CodexData_test_LearnWorlds.zip';
        $package = pathinfo( $zip, PATHINFO_FILENAME );
        $folderId = 'test2';
        $scormId = 'id2';
        $result = $upload->uploadZip( getenv( 'GOOGLE_CLOUD_STORAGE_BUCKET' ), $folderId, $scormId, $zip );
        // var_dump( $result );
        $this->assertTrue( $result['success'], $zip . ' upload failed.'  );

        $upload->removePackage( getenv( 'GOOGLE_CLOUD_STORAGE_BUCKET' ), $folderId, $scormId );

        $gcs = new GCSClass( getenv( 'GOOGLE_CLOUD_STORAGE_BUCKET' ) );
        $remaining = count( $gcs->listFolder( $folderId . '/' . $scormId ) );

        $this->assertTrue( $remaining == 0, 'Removal of ' . $package . ' failed. ' . $remaining . ' files remaining.' );
    }
}