<?php

require_once __DIR__ . '/../vendor/autoload.php';

use ahat\ScormUpload\UploadClass;

if( $argc != 4 )
{
    echo "Upload a package to folderId/scormId\n";
    echo "Usage: $argv[0] folderId scormId package\n";
    echo "  folderId: string\n";
    echo "  scormId: string\n";
    echo "  string full path to the package to upload\n";
    die;
}

$_SERVER[ 'CLAM_TCP_ADDRESS' ] = 'tcp://172.17.0.2:3310';
$_SERVER[ 'CLAM_BINDMOUNT_VOLUME' ] = '/tmp';
$_SERVER[ 'TMP_UNZIP_DIR' ] = '/tmp';
$_SERVER[ 'SCHEMA_VERSION_SEPARATOR' ] = '|';
$_SERVER[ 'SCORM_SCHEMA_VERSION' ] = 'CAM 1.3|1.2';
$_SERVER[ 'SCORM_SCHEMA_VERSION_CASE_SENSITIVE' ] = false;
$_SERVER[ 'CAPTIVATE_SCHEMA_VERSION' ] = 'CAM 1.3|';
$_SERVER[ 'CAPTIVATE_SCHEMA_VERSION_CASE_SENSITIVE' ] = false;

putenv( 'GOOGLE_CLOUD_STORAGE_BUCKET=scorm-214819.appspot.com' );
putenv( 'GOOGLE_APPLICATION_CREDENTIALS=' . __DIR__ . '/../private-key.json' );

$upload = new UploadClass;

$folderId = $argv[1];
$scormId = $argv[2];
$zip = $argv[3];
$result = $upload->uploadZip( getenv( 'GOOGLE_CLOUD_STORAGE_BUCKET' ), $folderId, $scormId, $zip );
print_r( $result );