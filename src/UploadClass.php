<?php

namespace ahat\ScormUpload;

require_once 'vendor/autoload.php';

use ahat\ScormUpload\AntivirusClass;
use ahat\ScormUpload\ValidatorProvider;
use ahat\ScormUpload\GCSClass;
use ahat\ScormUpload\UnzipClass;
use Exception;

class UploadClass implements ValidatorInterface
{

    /**
     * Checks a single file for virus.
     * 
     * @param string $file The fullpath of the file to check
     * @param object $socket The socket to use for client communication to clam av
     * 
     * @return array-of-object $result {</br>
     *  string ['filename']: the fullpath of the checked file</br>,
     *  string ['reason']: Information on the virus found, NULL if no virus</br>
     *  string ['status']: 'FOUND' if virus found, 'OK' otherwise</br>
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
     * @return array-of-array  $result {
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
    public function validate( $file, $removeOnValid = false, $removeOnInvalid = true )
    {   
        try
        {     
            $validator = ValidatorProvider::getValidator( $file ); // new ScormValidatorClass;
        }
        catch ( Exception $e )
        {
            return array( 'error' => 'Could not get a validator for ' . $file . ': ' . $e->getMessage() , 'valid' => false );
        }
        return $validator->validate( $file, $removeOnValid, $removeOnInvalid );
    }

    /**
     * Unzips a zip file, and uploads the contents to Google Storage Cloud. In case of validation
     * error the unzipped folder is removed.
     * 
     * @param string $bucketName the name of your Google Cloud bucket.
     * @param string $folderId the GCS folder where to upload the file.
     * @param string $file the full path to the file to upload.
     * 
     * @return array $result {
     *  string ['filename']: the fullpath of the checked file,
     *  object ['virusCheck']: the ruselt returned by the virusCheck function
     *  object ['validation']: the result returned by the validation function
     *  integer ['uploaded']: count of uploaded files
     *  boolean ['success']: true if everything went ok, false otherwise
    */
    public function uploadZip( $bucketName, $folderId, $file )
    {
        $result = array( 'filename' => $file, 'virusCheck' => null, 'validation' => null, 'uploaded' => 0, 'success' => false );

        $result['virusCheck'] = $this->virusCheck( $file );
        // var_dump( $result['virusCheck'] );
        if ( $result['virusCheck']['status'] === 'FOUND' ) {            
            return $result ;
        }

        $result['validation'] = $this->validate( $file );
        // var_dump( $result['validation'] );
        if( !$result['validation']['valid'] ) {
            return $result;
        }

        //NOTE: the unzip folder is returned in the validation result
        $unzippedPackage = $result['validation']['destination'];

        $gcs = new GCSClass( getenv( 'GOOGLE_CLOUD_STORAGE_BUCKET' ) );
        $result['uploaded'] = $gcs->uploadPackage( $folderId, $unzippedPackage );
        $result['success'] = true;
        // var_dump( $result );

        //cleanup the remaining unzip folder
        $unzip = new UnzipClass;
        $unzip->removeZip( $unzippedPackage );

        return $result;
    }

    /**
     * Replaces an existing package with a new one, provided the new package passes virus and validation checks.
     * In case of validation error the unzipped folder is removed.
     * 
     * @param string $bucketName the name of your Google Cloud bucket.
     * @param string $folderId the GCS folder where to upload the file.
     * @param string $file the full path to the file to upload.
     * 
     * @return array $result {
     *  string ['folderId']: the GCS folder where the old package is,
     *  string ['old']: the name of the old package to replace,
     *  string ['new']: the local fullpath of the new package file,
     *  object ['virusCheck']: the ruselt returned by the virusCheck function
     *  object ['validation']: the result returned by the validation function
     *  integer ['uploaded']: count of uploaded files
     *  boolean ['success']: true if everything went ok, false otherwise
    */
    public function replacePackage( $bucketName, $folderId, $oldPackage, $newPackage )
    {
        $result = array( 'folderId' => $folderId, 'old' => $oldPackage, 'new' => $newPackage, 'virusCheck' => null, 'validation' => null, 'uploaded' => 0, 'success' => false );

        $result['virusCheck'] = $this->virusCheck( $newPackage );
        // var_dump( $result['virusCheck'] );
        if ( $result['virusCheck']['status'] === 'FOUND' ) {            
            return $result ;
        }

        $result['validation'] = $this->validate( $newPackage );
        // var_dump( $result['validation'] );
        if( !$result['validation']['valid'] ) {
            return $result;
        }

        $gcs = new GCSClass( $bucketName );
        $deleted = $gcs->removePackage( $folderId, $oldPackage );

        //NOTE: the unzip folder is returned in the validation result
        $unzippedPackage = $result['validation']['destination'];

        $result['uploaded'] = $gcs->uploadPackage( $folderId, $unzippedPackage );
        $result['success'] = true;
        // var_dump( $result );

        //cleanup the remaining unzip folder
        $unzip = new UnzipClass;
        $unzip->removeZip( $unzippedPackage );

        return $result;        
    }


    /**
     * Delete a package from a specified folder
     * 
     * @param string $folderId The folder where the package is located
     * @param string $packageName The name of the package to remove
     * 
     * @return integer The number of deleted files
     */
    public function removePackage( $bucketName, $folderId, $packageName )
    {
        $gcs = new GCSClass( $bucketName );
        return $gcs->removePackage( $folderId, $packageName );
    }

}