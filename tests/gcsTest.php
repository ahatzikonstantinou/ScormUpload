<?php
namespace ahat\ScormUpload\Tests;

use PHPUnit\Framework\TestCase;
use Google\Cloud\Storage\StorageClient;
use ahat\ScormUpload\GCSClass;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use RecursiveTreeIterator;

class GCSClassTest extends TestCase
{
    protected function setUp()
    {
        $this->stack = [];
    }

    public function testUpload()
    {
        $gcs = new GCSClass( getenv( 'GOOGLE_CLOUD_STORAGE_BUCKET' ) );

        $packagePath = './tests/testfiles/CodexData_test_LearnWorlds_unzipped';
        $folderId = 'test1';
        $scormId = 'id1';
        $uploaded = $gcs->uploadPackage( $folderId, $scormId, $packagePath );
        $objects = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $packagePath, RecursiveDirectoryIterator::SKIP_DOTS ) );
        $count = 0;
        foreach($objects as $object){
            $count ++;
        }
        // echo "Uploaded $uploaded objects\n";
        $this->assertTrue( $count == $uploaded, "Upload of package " . $packagePath . ' in GCS folder ' . $folderId . '/' . $scormId . ' failed. Uploaded ' . $uploaded . ' of ' . $count . ' files.'  );
    }

    public function testRemovePackage()
    {
        $gcs = new GCSClass( getenv( 'GOOGLE_CLOUD_STORAGE_BUCKET' ) );
        $folderId = 'test1';
        $scormId = 'id1';
        $deleted = $gcs->removePackage( $folderId, $scormId );
        // echo "Deleted $deleted objects\n";

        $remaining = count( $gcs->listFolder( $folderId . '/' . $scormId ) );

        $this->assertTrue( $remaining == 0, 'Removal of ' . $scormId . ' failed. ' . $remaining . ' files remaining.' );
    }

    public function testSignedUrl()
    {
        $gcs = new GCSClass( getenv( 'GOOGLE_CLOUD_STORAGE_BUCKET' ) );
        $folderId = 'test3';
        $scormId = 'id3';
        $file = 'index.html';
        $signedURL = $gcs->signedUrl( $folderId, $scormId, $file );
        echo "\n\n$signedURL\n\n";

        $page = file_get_contents( $signedURL );
        // echo "\n\n$page\n\n";
        $this->assertTrue( strpos( $page, 'function initializeCP(' ) !== false, 'Signed url access for ' . $folderId . '/' . $scormId . '/'. $file . ' faield.' );
    }

    /* The following tests are used for inspection only and do not contain any proper assertions*/

    public function testListBucket()
    {
        $gcs = new GCSClass( getenv( 'GOOGLE_CLOUD_STORAGE_BUCKET' ) );

        $gcs->listBucket();

        $this->assertTrue( true );
    }

    public function testListPackages()
    {
        $gcs = new GCSClass( getenv( 'GOOGLE_CLOUD_STORAGE_BUCKET' ) );
        $folderId = 'test1';

        $gcs->listPackages( $folderId );
        
        $this->assertTrue( true );
    }

    public function testListFolders()
    {
        $gcs = new GCSClass( getenv( 'GOOGLE_CLOUD_STORAGE_BUCKET' ) );
        $gcs->listFolders();
        $this->assertTrue( true );
    }

    public function testDownloadObject()
    {
        $folderId = 'test1';
        $scormId = 'id1';
        $file = 'index.html';
        $storage = new StorageClient();
        $bucket = $storage->bucket( getenv( 'GOOGLE_CLOUD_STORAGE_BUCKET' ) );
        $object = $bucket->object( $folderId . '/' . $scormId . '/'. $file );

        $content = $object->downloadAsString();
        echo "content:\n$content\n";
        $this->assertNotEmpty( $content, "No content downloaded for " .$folderId . '/' . $scormId . '/'. $file . "\n" );
    }
    
}
