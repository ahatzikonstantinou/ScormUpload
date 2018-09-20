<?php

namespace ahat\ScormUpload;

require_once __DIR__ . '/../vendor/autoload.php';

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
     * @param boolean $removeOnValid If true the unzip folder will be removed if package is valid
     * @param boolean $removeOnInvalid If true the unzip folder will be removed if package is invalid
     * 
     * @return array $result {
     * string ['destination']: the destination folder where the package file was extracted, NULL if it was removed
     * string ['version']: the version of the package as described by metadata schemaVersion, NULL if not found
     * string ['launcher']: the launcher file of the package, NULL if not found
     * string ['error']: text descripton of the error, NULL if no error
     * boolean ['valid']: true if valid, false otherwise
     * }
     */
    public function validate( $file, $removeOnValid = false, $removeOnInvalid = true )
    {        
        //first unzip the file
        $unzip = new UnzipClass;
        $destination = $unzip->unzip( $file );

        $projectTxt = 'project.txt';

        //project.txt exists
        if ( !file_exists($destination . DIRECTORY_SEPARATOR . $projectTxt ) ) {
            if( $removeOnInvalid ) {
                $unzip->removeZip( $destination );
                $destination = null;
            }
            return array( 'destination' => $destination, 'version' => null, 'launcher' => null, 'error' => 'No ' . $projectTxt . ' found', 'valid' => false );
        }
        
        //project is valid json
        try
        {
            $string = file_get_contents( $destination . DIRECTORY_SEPARATOR . $projectTxt );
            $this->project = json_decode( $string, true );

            if ( !$this->project ) {
                if( $removeOnInvalid ) {
                    $unzip->removeZip( $destination );
                    $destination = null;
                }
                return array( 'destination' => $destination, 'version' => null, 'launcher' => null, 'error' => $projectTxt . ' json parse error', 'valid' => false );
            }
        }
        catch (Exception $e)
        {
            if( $removeOnInvalid ) {
                $unzip->removeZip( $destination );
                $destination = null;
            }
            return array( 'destination' => $destination, 'version' => null, 'launcher' => null, 'error' => $projectTxt . ' json parse error. [' . $e->getMessage() . ']' , 'valid' => false );
        }

        $validVersions = explode( $_SERVER['SCHEMA_VERSION_SEPARATOR'],  $_SERVER[ 'CAPTIVATE_SCHEMA_VERSION' ] );
        if ( !isset( $this->project['metadata'] ) ||
            !isset( $this->project['metadata']['schemaVersion'] ) ) {
                if( $removeOnInvalid ) {
                    $unzip->removeZip( $destination );
                    $destination = null;
                }
                return array( 'destination' => $destination, 'version' => null, 'launcher' => null, 'error' => $projectTxt . ' has no version metadata', 'valid' => false );
        } else {
            $caseSensitive = $_SERVER[ 'CAPTIVATE_SCHEMA_VERSION_CASE_SENSITIVE' ];
            if( !in_array( trim( $this->project['metadata']['schemaVersion'] ), $validVersions, $caseSensitive ) ) {
                if( $removeOnInvalid ) {
                    $unzip->removeZip( $destination );
                    $destination = null;
                }
                return array( 'destination' => $destination, 'version' => trim( $this->project['metadata']['schemaVersion'] ), 'launcher' => null, 'error' => $projectTxt . ' has wrong schema version', 'valid' => false );    
            }
        }

        if ( !isset( $this->project['metadata']['launchFile'] ) || empty( $this->project['metadata']['launchFile'] ) ) {
            if( $removeOnInvalid ) {
                $unzip->removeZip( $destination );
                $destination = null;
            }
            return array( 'destination' => $destination, 'version' => trim( $this->project['metadata']['schemaVersion'] ), 'launcher' => null, 'error' => $projectTxt . ' has no launcher', 'valid' => false );
        }         

        if( $removeOnValid ) {
            $unzip->removeZip( $destination );
            $destination = null;
        }

        return array( 'destination' => $destination, 'version' => trim( $this->project['metadata']['schemaVersion'] ), 'launcher' => $this->project['metadata']['launchFile'], 'error' => null, 'valid' => true );
    }

}