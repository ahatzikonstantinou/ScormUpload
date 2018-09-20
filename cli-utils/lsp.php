#!/usr/bin/php

<?php

require_once __DIR__ . '/../vendor/autoload.php';

use ahat\ScormUpload\GCSClass;

if( $argc != 2 )
{
    echo "List all packages inside a folder\n";
    echo "Usage: $argv[0] folderId\n";
    echo "  folderId: string\n";
    die;
}

putenv( 'GOOGLE_CLOUD_STORAGE_BUCKET=scorm-214819.appspot.com' );
putenv( 'GOOGLE_APPLICATION_CREDENTIALS=' . __DIR__ . '/../private-key.json' );

$gcs = new GCSClass( getenv( 'GOOGLE_CLOUD_STORAGE_BUCKET' ) );


$folderId = $argv[1];
$packages = $gcs->listPackages( $folderId );
print_r( $packages );