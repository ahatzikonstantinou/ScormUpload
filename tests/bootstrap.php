<?php

$_SERVER[ 'CLAM_TCP_ADDRESS' ] = 'tcp://172.17.0.2:3310';
$_SERVER[ 'CLAM_BINDMOUNT_VOLUME' ] = '/tmp';
$_SERVER[ 'TMP_UNZIP_DIR' ] = '/tmp';
$_SERVER[ 'SCHEMA_VERSION_SEPARATOR' ] = '|';
$_SERVER[ 'SCORM_SCHEMA_VERSION' ] = 'CAM 1.3|1.2';
$_SERVER[ 'SCORM_SCHEMA_VERSION_CASE_SENSITIVE' ] = false;
$_SERVER[ 'CAPTIVATE_SCHEMA_VERSION' ] = 'CAM 1.3|';
$_SERVER[ 'CAPTIVATE_SCHEMA_VERSION_CASE_SENSITIVE' ] = false;

// CAUTION: Do NOT use $_ENV for the following variables or else authentication to google cloud storage will fail
putenv( 'GOOGLE_CLOUD_STORAGE_PROJECT_ID=819215810916' );
putenv( 'GOOGLE_CLOUD_STORAGE_BUCKET=learnworlds_packages' );
putenv( 'GOOGLE_APPLICATION_CREDENTIALS=/home/antonis/Projects/learnworlds/ScormUpload/Scorm-9d50eec8f95f.json' );

