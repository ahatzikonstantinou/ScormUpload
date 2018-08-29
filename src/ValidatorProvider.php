<?php

namespace ahat\ScormUpload;

require_once 'vendor/autoload.php';

use ahat\ScormUpload\ScormValidatorClass;
use ahat\ScormUpload\CaptivateValidatorClass;
use ahat\ScormUpload\UnzipClass;
use Exception;

class ValidatorProvider
{
    /**
     * Returns either a ScormValidator or a CaptivateValidator depending on whether the zip contains
     * imsmanifest.xml or project.txt in ots root.
     * 
     * @param string $file The fullpath of the zip file to validate
     * 
     * @return object Either a Scorm or Captivate validator
     * 
     * @throws Exception if neither imsmanifest nor project.txt is found in the root folder of zip $file
     */
    public static function getValidator( $file )
    {
        $validator = null;
        $unzip = new UnzipClass;
        $destination = $unzip->unzip( $file );

        if( file_exists( $destination.'/imsmanifest.xml' ) ){
            $validator = new ScormValidatorClass;
        } elseif ( file_exists($destination . DIRECTORY_SEPARATOR . 'project.txt' ) ) {
            $validator = new CaptivateValidatorClass;
        } else {
            $unzip->removeZip( $destination );
            throw new Exception( $file . ' does not contain imsmanifest.xml or project.txt in the root folder. No validator is applicable' );
        }

        return $validator;
    }
}