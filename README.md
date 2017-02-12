photo-gcs-storage
=======

Easy photo storage in Google Cloud Storage

1. `git clone git@github.com:buzmakova/photo-gcs-storage.git my_project_name/` 
2. install composer
    For example (Install Composer on Linux and Mac OS X):
        `curl -sS https://getcomposer.org/installer | php`
        `sudo mv composer.phar /usr/local/bin/composer`
    Read for more information: https://getcomposer.org/doc/00-intro.md#installation-linux-unix-osx
3. `cd my_project_name/; composer install`
4. Specify parameters:
    * `google_storage_project_id`
        Read for more information: https://cloud.google.com/storage/docs/projects
    * `google_storage_key_file_path`
        Read for more information: 
        https://cloud.google.com/storage/docs/authentication
        https://googlecloudplatform.github.io/google-cloud-php/#/docs/v0.20.2/storage/storageclient
    * `google_storage_default_name` (Default Bucket name in Google Cloud Storage)
        Read for more information:
        https://cloud.google.com/storage/docs/creating-buckets
        https://googlecloudplatform.github.io/google-cloud-php/#/docs/v0.20.2/storage/storageclient
