# ScormUpload

This is a library to facilitate the uploading of Scorm / Captivate zip files to Goggle Cloud Storage.

The library checks zip files using the ClamAV software and if the file is clean, it unzips and uploads the contents to the specified bucket.

It also checks that the Scorm / Captivate file is valid i.e.
  * An imsmanifest.xml / project.txt exists
  * imsmanifest.xml is a valid xml file
  * project.txt is a valid json file
  * imsmanifest.xml contains a schemaversion attribute in its metadata element
  * project.txt contains a schemaVersion property in its metadata property
  * the schema version is the one specified in the parameters
  * imsmanifest.xml contains a launcher i.e. a resource with an "href" attribute in its resources
  * project.txt contains a launcher i.e. a non-empty metadata property "launchFile"

## Installation

Download the code to a folder inside your project and run 

```
composer install
```
to install all dependencies

or

```
composer install --no-dev
```

to omit development dependencies such as unit tests.

## Usage
Add

```php
require_once 'vendor/autoload.php'

use ahat\ScormUpload\UploadClass;
```
to your php code to require all necessary packages.

#### Upload package
```php
$upload = new UploadClass;
$result = $upload->uploadZip( 'GCS_bucket_name', 'folder id', 'package zip file' );
```

#### Replace package
```php
$upload = new UploadClass;
$result = $upload->replacePackage( 'GCS_bucket_name', 'folder id', 'old package name', '/path/to/new/package.zip' );
```

#### Remove package
```php
$upload = new UploadClass;
$result = $upload->removePackage( 'GCS_bucket_name', 'folder id', 'package name' );
```

#### Virus check a single file
```php
$upload = new UploadClass;
$result = $upload->virusCheck( 'filename.ext' );
```

#### Virus check mutliple files
```php
$upload = new UploadClass;
$result = $upload->virusCheck( 'filename1.ext', 'filename2.ext', 'filename3.ext' );
```

#### Validate package
```php
$upload = new UploadClass;
$result = $upload->validate( '/path/to/package.zip' );
```

#### Additional utilities
See unit tests `tests/gcsTest.php` and `tests/unzipTest.php` for usage of additional utilities.

### Apache config file

use mod_env to set

  * `SetEnv SCHEMA_VERSION_SEPARATOR "x"`
  * `SetEnv SCORM_SCHEMA_VERSION "xxxxxxxx"`
  * `SetEnv SCORM_SCHEMA_VERSION_CASE_SENSITIVE "xxxxxxxx"`
  * `SetEnv CAPTIVATE_SCHEMA_VERSION "xxxxxxxx"`
  * `SetEnv CAPTIVATE_SCHEMA_VERSION_CASE_SENSITIVE "xxxxxxxx"`

SCHEMA_VERSIONs are arrays of valid schema versions (i.e. strings) separated by the SCHEMA_VERSION_SEPARATOR e.g. '|'

### Environment variables

Per Google Cloud Storage [instructions](https://cloud.google.com/storage/docs/reference/libraries#client-libraries-install-php), once a service account is created and the file containing the corresponding key is downloaded, environment variable GOOGLE_APPLICATION_CREDENTIALS must be exported. Also, export the GOOGLE_CLOUD_STORAGE_PROJECT_ID as an environment variable.
 
#### Linux or MacOS
```
export GOOGLE_APPLICATION_CREDENTIALS="[PATH]"
export GOOGLE_CLOUD_STORAGE_PROJECT_ID="xxxxxxxx"
```
for example
```
export GOOGLE_APPLICATION_CREDENTIALS="/home/user/Downloads/[FILE_NAME].json"
export GOOGLE_CLOUD_STORAGE_PROJECT_ID="840375620846"
```

#### Windows

##### PowerShell
```
$env:GOOGLE_APPLICATION_CREDENTIALS="[PATH]"
$env:GOOGLE_CLOUD_STORAGE_PROJECT_ID="xxxxxxxx"
```
For example:
```
$env:GOOGLE_APPLICATION_CREDENTIALS="C:\Users\username\Downloads\[FILE_NAME].json"
$env:GOOGLE_CLOUD_STORAGE_PROJECT_ID="840375620846"
```
##### command prompt:
```
set GOOGLE_APPLICATION_CREDENTIALS=[PATH]
set GOOGLE_CLOUD_STORAGE_PROJECT_ID=xxxxxxxx
```
**Important Note** 

When setting environment variables in code, e.g. in unit tests, do **not** use the `$_ENV` variable. Values will not be passed in the code. Instead use function `putenv(...)`. For an example see file `tests/bootstrap.php`


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

If you are using ClamAV in a docker container set a bindmount volume to the folder where the files-to-be-checked are saved. E.g. if files are stored in /uploads and the docker mount is /tmp run the docker container with 
```    
sudo docker run -d --name av -v /uploads:/tmp -p 3310:3310 mkodockx/docker-clamav
```
This command will bindmount /uploads of the host with /tmp of the container.

If ClamAV is running in a docker container also set the following variable in the apache config file so that the library can construct the appropriate path for the file that the ClamAV daemon must check:
  * `SetEnv CLAM_BINDMOUNT_VOLUME "/path"` (e.g. /tmp)

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
