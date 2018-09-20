<?php

namespace ahat\ScormUpload;

require_once __DIR__ . '/../vendor/autoload.php';

use Socket\Raw\Socket;
use Exception;

class AntivirusClass
{

    /**
     * Checks a single file for virus.
     * 
     * @param string $file The fullpath of the file to check
     * @param object $socket The socket to use for client communication to clam av
     * 
     * @return array $result {
     *  string ['filename']: the fullpath of the checked file,
     *  string ['reason']: Information on the virus found, NULL if no virus
     *  string ['status']: 'FOUND' if virus found, 'OK' otherwise
     * }
     */
    public function virusCheck( $file, $socket = null )
    {
        if( is_null( $socket ) )
        {
            $socket = $this->createSocket();
        }

        // Create a new instance of the Client
        $quahog = new \Xenolope\Quahog\Client( $socket, 30, PHP_NORMAL_READ );
        
        // Scan a file
        if( isset( $_SERVER[ 'CLAM_BINDMOUNT_VOLUME' ] ) ) {
            $file = $_SERVER[ 'CLAM_BINDMOUNT_VOLUME' ] . DIRECTORY_SEPARATOR . basename( $file );
        }
        $result = $quahog->scanFile( $file );

        return $result;
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
        $results = array();

        if( is_null( $socket ) )
        {
            $socket = $this->createSocket();
        }

        // Create a new instance of the Client
        $quahog = new \Xenolope\Quahog\Client( $socket, 30, PHP_NORMAL_READ );
        
        $quahog->startSession();
        
        foreach ($files as &$file) {
            if( isset( $_SERVER[ 'CLAM_BINDMOUNT_VOLUME' ] ) ) {
                $file = $_SERVER[ 'CLAM_BINDMOUNT_VOLUME' ] . DIRECTORY_SEPARATOR . basename( $file );
            }
    
            $result = $quahog->scanFile( $file );

            $results[] = $result;
        }
        
        $quahog->endSession();

        return $results;
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