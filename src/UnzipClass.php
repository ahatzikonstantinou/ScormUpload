<?php

namespace ahat\ScormUpload;

require_once __DIR__ . '/../vendor/autoload.php';

use Exception;
use ZipArchive;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class UnzipClass
{
    /**
     * Remove unzipped files and temporary folder
     * 
     * @param string $folder The folder that contains the unzipped files
     * 
     */
    public function removeZip( $dir )
    {
        // echo "Removing $dir\n";
        $it = new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS);
        $files = new RecursiveIteratorIterator($it, RecursiveIteratorIterator::CHILD_FIRST);
        foreach($files as $file) {
            if ($file->isDir()) {
                rmdir($file->getRealPath());
            } else {
                unlink($file->getRealPath());
            }
        }
        rmdir($dir);
    }

 
    /**
     * Extracts the contents of a zip file to a destination folder
     * 
     * @param string $file
     * @param string $destination If NULL compose based on $_SERVER['TMP_UNZIP_DIR']
     * 
     * @throws object Exception 'File does not exist'
     * @throws object Exception 'Wrong file type' if extensions not 'zip'
     * @throws object Exception 'Unable to open zip file'
     * @throws object Exception 'Unable to write zip contents to destination'
     * 
     * @return string The destination folder unzipped.
     */
    public function unzip($file, $destination = null ) 
    {
        if (!file_exists($file)) {
            throw new Exception('File '. $file . ' does not exist');
        }

        if (!$this->checkFileExtension($file, 'zip')) {
            throw new Exception('Wrong file type');
        }

        $zip = new ZipArchive;
        $res = $zip->open($file);
        if (!$res) {
            throw new Exception('Unable to open zip file');
        }

        if ( is_null( $destination ) ) {
            $destination = UnzipClass::getTempUnzipDir() . DIRECTORY_SEPARATOR . pathinfo( $file, PATHINFO_FILENAME );
        }

        if (!$zip->extractTo($destination)) {
            throw new Exception('Unable to write zip contents to destination');
        }
        $zip->close();

        return $destination;
    }


    /**
     * Check the file extension
     * 
     * @param string $filename
     * @param string $ext
     * 
     * @return boolean
     */
    private function checkFileExtension($filename, $ext) 
    {
        return ( $ext === pathinfo($filename, PATHINFO_EXTENSION) );
    }

    /**
     * Calculate and return the dir used for temporary unzip scorm packages
     * 
     * @return string The dir
     */
    public static function getTempUnzipDir()
    {
        $destinationDir = '.';
        if (isset($_SERVER['TMP_UNZIP_DIR']) && !empty($_SERVER['TMP_UNZIP_DIR'])) {
            $destinationDir = $_SERVER['TMP_UNZIP_DIR'];
        }
        return $destinationDir ;
    }

}