<?php

namespace ahat\ScormUpload;

require_once 'vendor/autoload.php';

use ahat\ScormUpload\UnzipClass;
use ahat\ScormUpload\ValidatorInterface;
use Exception;

class CaptivateValidatorClass implements ValidatorInterface
{
    private $project = null;

    /**
     * Validates a Captivate package
     * 
     * @param string $file
     * 
     * @return array $result {
     * string ['error']: text descripton of the error, NULL if no error
     * boolean ['valid']: true if valid, false otherwise
     * }
     */
    public function validate( $file, $removeOnInvalid = true )
    {        
        //first unzip the file
        $unzip = new UnzipClass;
        $destination = $unzip->unzip( $file );

        $projectTxt = 'project.txt';

        //project.txt exists
        if ( !file_exists($destination . DIRECTORY_SEPARATOR . $projectTxt ) ) {
            $unzip->removeZip( $destination );
            return array( 'error' => 'No ' . $projectTxt . ' found', 'valid' => false );
        }
        
        //project is valid json
        try
        {
            $string = file_get_contents( $destination . DIRECTORY_SEPARATOR . $projectTxt );
            $this->project = json_decode( $string, true );

            if ( !$this->project ) {
                $unzip->removeZip( $destination );
                return array( 'error' => $projectTxt . ' json parse error', 'valid' => false );
            }
        }
        catch (Exception $e)
        {
            $unzip->removeZip( $destination );
            return array( 'error' => $projectTxt . ' json parse error. [' . $e->getMessage() . ']' , 'valid' => false );
        }

        $validVersions = explode( $_SERVER['SCHEMA_VERSION_SEPARATOR'],  $_SERVER[ 'CAPTIVATE_SCHEMA_VERSION' ] );
        if ( !isset( $this->project['metadata'] ) ||
            !isset( $this->project['metadata']['schemaVersion'] ) ) {
                $unzip->removeZip( $destination );
                return array( 'error' => $projectTxt . ' has no version metadata', 'valid' => false );
        } else {
            $caseSensitive = $_SERVER[ 'CAPTIVATE_SCHEMA_VERSION_CASE_SENSITIVE' ];
            if( !in_array( trim( $this->project['metadata']['schemaVersion'] ), $validVersions, $caseSensitive ) ) {
                $unzip->removeZip( $destination );
                return array( 'error' => $projectTxt . ' has wrong schema version', 'valid' => false );    
            }
        }

        if ( !isset( $this->project['metadata']['launchFile'] ) ||
            empty( $this->project['metadata']['launchFile'] ) ) {
            $unzip->removeZip( $destination );
            return array( 'error' => $projectTxt . ' has no launcher', 'valid' => false );
        }         

        $unzip->removeZip( $destination );
        
        return array( 'error' => null, 'valid' => true );
    }

}