<?php

namespace ahat\ScormUpload;

require_once 'vendor/autoload.php';

use ahat\ScormUpload\UnzipClass;
use ahat\ScormUpload\ValidatorInterface;
use Exception;

class ScormValidatorClass implements ValidatorInterface
{
    private $imsManifest = null;

    /**
     * Validates a Scorm package
     * 
     * @param string $file
     * @param boolean $removeOnValid If true the unzip folder will be removed if package is valid
     * @param boolean $removeOnInvalid If true the unzip folder will be removed if package is invalid
     * 
     * @return array $result {
     * string ['destination']: the destination folder where the package file was extracted, NULL if it was removed
     * string ['version']: the version of the package as described by metadata schemaVersion
     * string ['launcher']: the launcher file of the package
     * string ['error']: text descripton of the error, NULL if no error
     * boolean ['valid']: true if valid, false otherwise
     * }
     */
    public function validate( $file, $removeOnValid = false, $removeOnInvalid = true )
    {        
        //first unzip the file
        $unzip = new UnzipClass;
        $destination = $unzip->unzip( $file );


        //imsmanifest exists
        if(!file_exists($destination.'/imsmanifest.xml')){
            if( $removeOnInvalid ) {
                $unzip->removeZip( $destination );
                $destination = null;
            }
            return array( 'destination' => $destination, 'version' => null, 'launcher' => null, 'error' => 'No imsmanifest.xml found', 'valid' => false );
        }
        
        //imsmanifest is valid xml
        try
        {
            $this->imsManifest = simplexml_load_file($destination.'/imsmanifest.xml');
            if(!$this->imsManifest){
                if( $removeOnInvalid ) {
                    $unzip->removeZip( $destination );
                    $destination = null;
                }
                return array( 'destination' => $destination, 'version' => null, 'launcher' => null, 'error' => 'imsmanifest.xml XML parse error', 'valid' => false );
            }
        }
        catch (Exception $e)
        {
            if( $removeOnInvalid ) {
                $unzip->removeZip( $destination );
                $destination = null;
            }
            return array( 'destination' => $destination, 'version' => null, 'launcher' => null, 'error' => 'imsmanifest.xml XML parse error. [' . $e->getMessage() . ']' , 'valid' => false );
        }

        $validVersions = explode( $_SERVER['SCHEMA_VERSION_SEPARATOR'],  $_SERVER[ 'SCORM_SCHEMA_VERSION' ] );
        if ( !isset( $this->imsManifest->metadata ) ||
            !isset( $this->imsManifest->metadata->schemaversion ) ) {
                if( $removeOnInvalid ) {
                    $unzip->removeZip( $destination );
                    $destination = null;
                }
                return array( 'destination' => $destination, 'version' => null, 'launcher' => null, 'error' => 'imsmanifest.xml has no version metadata', 'valid' => false );
        } else {
            $caseSensitive = $_SERVER[ 'SCORM_SCHEMA_VERSION_CASE_SENSITIVE' ];
            if( !in_array( trim( $this->imsManifest->metadata->schemaversion ), $validVersions, $caseSensitive ) ) {
                if( $removeOnInvalid ) {
                    $unzip->removeZip( $destination );
                    $destination = null;
                }
                return array( 'destination' => $destination, 'version' => trim( $this->imsManifest->metadata->schemaversion ), 'launcher' => null, 'error' => 'imsmanifest.xml has wrong schema version', 'valid' => false );    
            }
        }

        if ( !isset($this->imsManifest->resources ) ) {
            if( $removeOnInvalid ) {
                $unzip->removeZip( $destination );
                $destination = null;
            }
            return array( 'destination' => $destination, 'version' => trim( $this->imsManifest->metadata->schemaversion ), 'launcher' => null, 'error' => 'imsmanifest.xml has no resources', 'valid' => false );
        }
        
        if ( !isset( $this->imsManifest->resources->resource ) ||
             !isset( $this->imsManifest->resources->resource->attributes()->href )
        ) {
            if( $removeOnInvalid ) {
                $unzip->removeZip( $destination );
                $destination = null;
            }
            return array( 'destination' => $destination, 'version' => trim( $this->imsManifest->metadata->schemaversion ), 'launcher' => null, 'error' => 'imsmanifest.xml has no launcher', 'valid' => false );
        }         

        if( $removeOnValid ) {
            $unzip->removeZip( $destination );
            $destination = null;
        }
        
        return array( 'destination' => $destination, 'version' => trim( $this->imsManifest->metadata->schemaversion ), 'launcher' => (string) $this->imsManifest->resources->resource->attributes()->href, 'error' => null, 'valid' => true );
    }

}