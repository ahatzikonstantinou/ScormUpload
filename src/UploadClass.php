<?php

namespace ahat\ScormUpload;

require_once 'vendor/autoload.php';

use Socket\Raw\Socket;
use Exception;
use ZipArchive;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class UploadClass
{
    private $socket;

    public function __construct( $socket = null )
    {
        $this->socket = $socket;

        if( is_null( $socket ) )
        {
            $this->socket = $this->createSocket();
        }
    }

    /**
     * Checks a single file for virus
     * 
     * @param string $file The fullpath of the file to check
     * 
     * @return associative array $result {
     *  string ['filename']: the fullpath of the checked file,
     *  string ['reason']: Information on the virus found, NULL if no virus
     *  string ['status']: 'FOUND' if virus found, 'OK' otherwise
     * }
     */
    public function virusCheck( $file )
    {
        // Create a new instance of the Client
        $quahog = new \Xenolope\Quahog\Client( $this->socket, 30, PHP_NORMAL_READ );
        
        // Scan a file
        $result = $quahog->scanFile( $file );

        return $result;
    }

    /**
     * Checks multiple files for virus
     * 
     * @param array of strings $files Array of the fullpath of the files to check
     * 
     * @return array of associative array $result {
     *  string ['filename']: the fullpath of the checked file,
     *  string ['reason']: Information on the virus found, NULL if no virus
     *  string ['status']: 'FOUND' if virus found, 'OK' otherwise
     * }
     */
    public function virusMultiCheck( $files )
    {
        $results = array();

        // Create a new instance of the Client
        $quahog = new \Xenolope\Quahog\Client( $this->socket, 30, PHP_NORMAL_READ );
        
        $quahog->startSession();
        
        foreach ($files as &$file) {
            $result = $quahog->scanFile( $file );

            $results[] = $result;
        }
        
        $quahog->endSession();

        return $results;
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
        //first unzip the file
        $destination = UploadClass::getTempUnzipDir() . DIRECTORY_SEPARATOR . basename( $file );
        // print '$file: ' . $file . ', $destination: '.$destination;
        $this->unzip( $file, $destination );


        //imsmanifest exists
        if(!file_exists($destination.'/imsmanifest.xml')){
            $this->removeZip( $destination );
            return array( 'error' => 'No imsmanifest.xml found', 'valid' => false );
        }
        
        //imsmanifest is valid xml
        try
        {
            $this->imsManifest = simplexml_load_file($destination.'/imsmanifest.xml');
            if(!$this->imsManifest){
                $this->removeZip( $destination );
                return array( 'error' => 'imsmanifest.xml XML parse error', 'valid' => false );
            }
        }
        catch (Exception $e)
        {
            $this->removeZip( $destination );
            return array( 'error' => 'imsmanifest.xml XML parse error', 'valid' => false );
        }


        if ( !isset( $this->imsManifest->metadata ) ||
            !isset( $this->imsManifest->metadata->schemaversion ) ||
            empty( $this->imsManifest->metadata->schemaversion ) ) {
                $this->removeZip( $destination );
                return array( 'error' => 'imsmanifest.xml has no version', 'valid' => false );
        }

        if ( !isset($this->imsManifest->resources ) ) {
            $this->removeZip( $destination );
            return array( 'error' => 'imsmanifest.xml has no resources', 'valid' => false );
        }
        
        if ( !isset( $this->imsManifest->resources->resource ) ||
             !isset( $this->imsManifest->resources->resource->attributes()->href )
        ) {
            $this->removeZip( $destination );
            return array( 'error' => 'imsmanifest.xml has no launcher', 'valid' => false );
        }         

        $this->removeZip( $destination );
        
        return array( 'error' => null, 'valid' => true );
    }

    /**
     * Remove unzipped files and temporary folder
     * 
     * @param string $folder The folder that contains the unzipped files
     * 
     */
    public function removeZip( $dir )
    {
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
     * Unzips a zip file, and uploads the contents to Google Storage Cloud
     * 
     * @param string $bucketName the name of your Google Cloud bucket.
     * @param string $file the full path to the file to upload.
     * 
     */
    public function uploadZip( $bucketName, $file )
    {

    }

    /**
     * Extracts the contents of a zip file to a destination folder
     * 
     * @param string $file
     * @param string $destination
     * 
     * @throws object Exception 'File does not exist'
     * @throws object Exception 'Wrong file type' if extensions not 'zip'
     * @throws object Exception 'Unable to open zip file'
     * @throws object Exception 'Unable to write zip contents to destination'
     * 
     * @return boolean
     */
    public function unzip($file, $destination) 
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
        if (!$zip->extractTo($destination)) {
            throw new Exception('Unable to write zip contents to destination');
        }
        $zip->close();

        return true;
    }

    /**
     * Creates a Socket according to $_SERVER variables CLAM_UNIX_ADDRESS or CLAM_TCP_ADDRESS
     * 
     * @throws SOCKET_EHOSTUNREACH Exception if no route to ClamAV daemon is available
     * 
     * @return Socket to be used in the construction of the ClamAV client
     */
    private function createSocket()
    {
        $address = null;        

        if (isset($_SERVER['CLAM_UNIX_ADDRESS']) && !empty($_SERVER['CLAM_UNIX_ADDRESS'])) {
            // fwrite(STDOUT, 'CLAM_UNIX_ADDRESS: ' . $_SERVER['CLAM_UNIX_ADDRESS'] . "\n"); // debug
            $address = $_SERVER['CLAM_UNIX_ADDRESS'];
        } elseif (isset($_SERVER['CLAM_TCP_ADDRESS']) && !empty($_SERVER['CLAM_TCP_ADDRESS'])) {
            // fwrite(STDOUT, 'CLAM_TCP_ADDRESS: ' . $_SERVER['CLAM_TCP_ADDRESS'] . "\n"); // debug
            $address = $_SERVER['CLAM_TCP_ADDRESS'];
        } else {
            throw new Exception( 'Either $_SERVER[\'CLAM_UNIX_ADDRESS\'] or $_SERVER[\'CLAM_TCP_ADDRESS\'] must be specified' );
        }

        if( !is_null( $address ) )
        {
            return (new \Socket\Raw\Factory())->createClient( $address );
        }        
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
        $destinationDir = '/tmp';
        if (isset($_SERVER['TMP_UNZIP_DIR']) && !empty($_SERVER['TMP_UNZIP_DIR'])) {
            $destinationDir = $_SERVER['TMP_UNZIP_DIR'];
        }
        return $destinationDir ;
    }

}