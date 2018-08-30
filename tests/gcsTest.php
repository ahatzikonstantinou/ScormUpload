<?php
namespace ahat\ScormUpload\Tests;

use PHPUnit\Framework\TestCase;
use ahat\ScormUpload\GCSClass;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use RecursiveTreeIterator;

class UploadClassTest extends TestCase
{
    protected function setUp()
    {
        $this->stack = [];
    }

    public function testListBucket()
    {
        $gcs = new GCSClass( 'learnworlds_packages' );

        $gcs->listBucket();

        $this->assertTrue( true );
    }

    public function testUpload()
    {
        $gcs = new GCSClass( 'learnworlds_packages' );

        $packagePath = './tests/testfiles/CodexData_test_LearnWorlds';
        $folderId = 'test1';
        $uploaded = $gcs->uploadPackage( $folderId, $packagePath );
        $objects = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $packagePath, RecursiveDirectoryIterator::SKIP_DOTS ) );
        $count = 0;
        foreach($objects as $object){
            $count ++;
        }
        echo "Uploaded $uploaded objects\n";
        $this->assertTrue( $count == $uploaded, "Upload of package " . $packagePath . ' in GCS folder ' . $folderId . ' failed. Uploaded ' . $uploaded . ' of ' . $count . ' files.'  );
    }

    public function testRemovePackage()
    {
        $gcs = new GCSClass( 'learnworlds_packages' );
        $package = 'CodexData_test_LearnWorlds';
        $folderId = 'test1';
        $deleted = $gcs->removePackage( $folderId, $package );
        echo "Deleted $deleted objects\n";

        $remaining = count( $gcs->listFolder( $folderId . '/' . $package ) );

        $this->assertTrue( $remaining == 0, 'Removal of ' . $package . ' failed. ' . $remaining . ' files remaining.' );
    }

    public function testListPackages()
    {
        $gcs = new GCSClass( 'learnworlds_packages' );
        $folderId = 'test1';

        $gcs->listPackages( $folderId );
        
        $this->assertTrue( true );
    }

    public function testListFolders()
    {
        $gcs = new GCSClass( 'learnworlds_packages' );
        $gcs->listFolders();
        $this->assertTrue( true );
    }
}
