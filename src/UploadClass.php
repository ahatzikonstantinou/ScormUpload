<?php

namespace ahat\ScormUpload;

require_once 'vendor/autoload.php';

use ahat\ScormUpload\AntivirusClass;
use ahat\ScormUpload\UnzipClass;
use ahat\ScormUpload\ScormValidatorClass;
use Socket\Raw\Socket;
use Exception;
use ZipArchive;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class UploadClass
{

    /**
     * Checks a single file for virus.
     * 
     * @param string $file The fullpath of the file to check
     * @param object $socket The socket to use for client communication to clam av
     * 
     * @return associative array $result {
     *  string ['filename']: the fullpath of the checked file,
     *  string ['reason']: Information on the virus found, NULL if no virus
     *  string ['status']: 'FOUND' if virus found, 'OK' otherwise
     * }
     */
    public function virusCheck( $file, $socket = null )
    {
        $av = new AntivirusClass;
        return $av->virusCheck( $file, $socket );
    }

    /**
     * Checks multiple files for virus
     * 
     * @param array of strings $files Array of the fullpath of the files to check
     * @param object $socket The socket to use for client communication to clam av      
     * 
     * @return array of associative array $result {
     *  string ['filename']: the fullpath of the checked file,
     *  string ['reason']: Information on the virus found, NULL if no virus
     *  string ['status']: 'FOUND' if virus found, 'OK' otherwise
     * }
     */
    public function virusMultiCheck( $files, $socket = null )
    {
        $av = new AntivirusClass;
        return $av->virusMultiCheck( $files, $socket );
    }

    /**
     * Validates a Scorm package
     * 
     * @param string $file
     * 
     * @return array $result {
     * string ['error']: text descripton of the error, NULL if no error
     * boolean ['valid']: true if valid, false otherwise
     * }
     */
    public function validate( $file )
    {        
        $validator = new ScormValidatorClass;
        return $validator->validate( $file );
    }

    /**
     * Unzips a zip file, and uploads the contents to Google Storage Cloud
     * 
     * @param string $bucketName the name of your Google Cloud bucket.
     * @param string $file the full path to the file to upload.
     * 
     */
    public function uploadZip( $bucketName, $file )
    {

    }

}