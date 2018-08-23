#php

## ClamAV

### Apache config file

use mod_env to set

`SetEnv CLAM_UNIX_ADDRESS "unix:///path/to/clamav/clamd.ctl"` (e.g. 'unix:///var/run/clamav/clamd.ctl')

`SetEnv CLAM_TCP_ADDRESS "tcp://xxx.xxx.xxx.xxx:port"` (e.g. tcp://127.0.0.1:3310)

If none is set an Exception will be thrown.

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

For zip files, folder `uploads` must have execute permissions for all so that `root` (who is the user running the docker ClamAV container) is allowed to temporarily unzip and check the contents of the xip file:

    drwxrwxrwx 2 user1 user1 4096 Aug  23 22:27 uploads/