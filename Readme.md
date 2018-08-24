# ScormUpload

This is a library to facilitate the uploading of Scorm zip files to Goggle Cloud Storage.

The library checks zip files using the ClamAV software and if the file is clean, it unzips and uploads the contents to the specified bucket.

It also checks that the Scorm file is valid i.e.
  * An imsmanifest.xml exists
  * imsmanifest.xml is a valid xml file
  * imsmanifest.xml contains a schemaversion attribute in its metadata element
  * imsmanifest.xml contains a launcher i.e. a resource with an href attribute in its resources
 

## ClamAV

### Apache config file

use mod_env to set

  * `SetEnv CLAM_UNIX_ADDRESS "unix:///path/to/clamav/clamd.ctl"` (e.g. 'unix:///var/run/clamav/clamd.ctl')  

  * `SetEnv CLAM_TCP_ADDRESS "tcp://xxx.xxx.xxx.xxx:port"` (e.g. tcp://127.0.0.1:3310)

If none of the above is set an Exception will be thrown.

### ClamAV docker container (optional)

An alternative to local installation, ClamAV may also be installed as a docker container image. The image provided by https://hub.docker.com/r/mkodockx/docker-clamav/ builds with a current virus database and
runs freshclam in the background constantly updating the virus signature database. clamd itself
is listening on exposed port 3310.

If you are using ClamAV in a docker container set a bindmount volume to the folder where the files-to-be-checked are saved. E.g. if files are stored in /tmp/uploads/ run the docker container with 
    
    sudo docker run -d --name av -v /uploads:/tmp -p 3310:3310 mkodockx/docker-clamav

This command will bindmount /uploads of the host with /tmp of the container. To check file `/uploads/infected_file.zip` use the following php code:

```php
$upload = new UploadClass;
$upload->virusCheck( '/tmp/infected_file.zip' );

```
### Folder Permissions
For zip files, folder `uploads` must have proper execute permissions so that `root` (who is the user running the docker ClamAV container) is allowed to temporarily unzip and check the contents of the xip file:

  * Option 1: add execute permission for all

    ```
    drwxrwxrwx 2 user1 user1 4096 Aug  23 22:27 uploads/
    ```  

  * Option 2 (recommended): create a new group, add root and other necessary users to the new group, give write persmissions for the group

    ```
    drwxrwxr-x 2 user1 uploadgroup 4096 Aug  23 22:27 uploads/
    ```

## Unzip

PHP must be compiled with zip support and have the zip extension installed (see http://php.net/manual/en/zip.installation.php)

  * For ubuntu `sudo apt-get install php7.0-zip`
  * For docker `docker-php-ext-install zip`


Uses class ZipArchive

### Apache config file

use mod_env to set

  * `SetEnv TMP_UNZIP_DIR "/path/to/temporary/folder/for/unzipping/"` (default /tmp/)  
