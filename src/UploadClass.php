<?php

namespace ahat\ScormUpload;

require_once 'vendor/autoload.php';

use Socket\Raw\Socket;
use Exception;

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
}