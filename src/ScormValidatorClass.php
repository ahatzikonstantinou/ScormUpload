<?php

namespace ahat\ScormUpload;

require_once 'vendor/autoload.php';

use ahat\ScormUpload\UnzipClass;
use Exception;

class ScormValidatorClass
{

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
        //first unzip the file
        $unzip = new UnzipClass;
        $destination = $unzip->unzip( $file );


        //imsmanifest exists
        if(!file_exists($destination.'/imsmanifest.xml')){
            $unzip->removeZip( $destination );
            return array( 'error' => 'No imsmanifest.xml found', 'valid' => false );
        }
        
        //imsmanifest is valid xml
        try
        {
            $this->imsManifest = simplexml_load_file($destination.'/imsmanifest.xml');
            if(!$this->imsManifest){
                $unzip->removeZip( $destination );
                return array( 'error' => 'imsmanifest.xml XML parse error', 'valid' => false );
            }
        }
        catch (Exception $e)
        {
            $unzip->removeZip( $destination );
            return array( 'error' => 'imsmanifest.xml XML parse error. [' . $e->getMessage() . ']' , 'valid' => false );
        }


        if ( !isset( $this->imsManifest->metadata ) ||
            !isset( $this->imsManifest->metadata->schemaversion ) ||
            empty( $this->imsManifest->metadata->schemaversion ) ) {
                $unzip->removeZip( $destination );
                return array( 'error' => 'imsmanifest.xml has no version', 'valid' => false );
        } else {
            if ( $_SERVER[ 'SCHEMA_VERSION_CASE_SENSITIVE' ] ) {
                if( !in_array( trim( $this->imsManifest->metadata->schemaversion ), $_SERVER[ 'SCHEMA_VERSION' ], true ) ) {
                    $unzip->removeZip( $destination );
                    return array( 'error' => 'imsmanifest.xml has wrong schema version', 'valid' => false );    
                }
            } else {
                if( !in_array( trim( $this->imsManifest->metadata->schemaversion ), $_SERVER[ 'SCHEMA_VERSION' ], false ) ) {
                    $unzip->removeZip( $destination );
                    return array( 'error' => 'imsmanifest.xml has wrong schema version', 'valid' => false );    
                }
            }
        }

        if ( !isset($this->imsManifest->resources ) ) {
            $unzip->removeZip( $destination );
            return array( 'error' => 'imsmanifest.xml has no resources', 'valid' => false );
        }
        
        if ( !isset( $this->imsManifest->resources->resource ) ||
             !isset( $this->imsManifest->resources->resource->attributes()->href )
        ) {
            $unzip->removeZip( $destination );
            return array( 'error' => 'imsmanifest.xml has no launcher', 'valid' => false );
        }         

        $unzip->removeZip( $destination );
        
        return array( 'error' => null, 'valid' => true );
    }

}