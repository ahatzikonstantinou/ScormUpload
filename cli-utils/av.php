#!/usr/bin/php

<?php

require_once __DIR__ . '/../vendor/autoload.php';

use ahat\ScormUpload\UploadClass;

if( $argc != 2 )
{
    echo "virus check using clamav\n";
    echo "Usage: $argv[0] file \n";
    echo "  file: path/to/file/to/check\n";
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


$upload = new UploadClass;

$file = $argv[1];
$result = $upload->virusCheck( $file );
print_r( $result );