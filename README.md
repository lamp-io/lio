# Command line interface, [Lamp-io](https://www.lamp.io/) platform

Installation
------------
##### As a Global Composer Install
```sh
$ composer global require lamp-io/lio 
```

##### As local composer package
```sh
composer require lamp-il/lio
```

##### Download as a PHAR
[lio.phar]()

Usage
------------
```sh
lio command [options] [arguments]
```

Commands
------------
### Token
1.  #### auth [-u][--update_token]

    Will ask you to input your auth token

    Options:

    * `[-u][--update_token](bool){false}` will allow to override your current auth token
    
### Apps
1.  #### apps:list

    Will output you all your apps associated to your token
    
    Options:
    
    * `[-j][--json](bool){false}` Output as a raw json

2.  #### apps:describe <app_id>

    Will output all app info

    Arguments:

    * `<app_id>(string)` The ID of the app
    
    Options:
        
    * `[-j][--json](bool){false}` Output as a raw json

3. #### apps:new <organization_id> [-d][--description] [--httpd_conf] [--max_replicas] [-m][--memory] [--min_replicas] [--php_ini] [-r][--replicas] [--vcpu] [--github_webhook_secret] [--webhook_run_command]

    Will allow you to create an app

    Arguments:

    * `<organization_id>[optional](string)` The ID(uuid) of the organization this app belongs to

    Options:

    * `[-d][--description](string){Default}` A description of your app
    * `[--httpd_conf](string){default appache config}` Path to your httpd.conf
    * `[--max_replicas](int){1}` The maximum number of auto-scaled replicas
    * `[-m][--memory](string){128Mi}` The amount of memory available (example: 1Gi)
    * `[--min_replicas](string){1}` The minimum number of auto-scaled replicas
    * `[--php_ini]{default php.ini}` Path to your php.ini
    * `[-r][--replicas](int){1}` The number current number replicas available. 0 stops app.
    * `[--vcpu](float){0.25} `The number of virtual cpu cores available (maximum: 4, minimum: 0.25)
    * `[--github_webhook_secret](string){''}` Github web-hook secret token
    * `[--webhook_run_command](string){''}` Github web-hook command
    
4. #### apps:update <app_id> <organization_id>  [-d][--description] [--httpd_conf] [--max_replicas] [-m][--memory] [--min_replicas] [--php_ini] [-r][--replicas] [--vcpu]
    
    Will allow you to update selected app
    
    Arguments:
    
    * `<app_id>(string)` The ID of the app
    * `<organization_id>[optional](string)` The ID(uuid) of the organization this app belongs to
    
    Options:
    
    * `[-d][--description](string){Default}` A description of your app
    * `[--httpd_conf](string){default appache config}` Path to your httpd.conf
    * `[--max_replicas](int){1}` The maximum number of auto-scaled replicas
    * `[-m][--memory](string){128Mi}` The amount of memory available (example: 1Gi)
    * `[--min_replicas](string){1}` The minimum number of auto-scaled replicas
    * `[--php_ini]{default php.ini}` Path to your php.ini
    * `[-r][--replicas](int){1}` The number current number replicas available. 0 stops app.
    * `[--vcpu](float){0.25} `The number of virtual cpu cores available (maximum: 4, minimum: 0.25)
    * `[--github_webhook_secret](string){''}` Github web-hook secret token
    * `[--webhook_run_command](string){''}` Github web-hook command
    
5. #### apps:delete <app_id>

    Delete an app
    
    Arguments:
    
    * `<app_id>(string)` The ID of the app  
    
### Files

1. #### files:list [-l][--limit] [--human-readable] [-r][--recursive] <app_id> <file_id>

    Return files from the root of an app (if not define <file_id>)
    
    Arguments:
    
    * `<app_id>(string)` The ID of the app
    * `file_id(string){/}` The ID of the file. The ID is also the file path relative to its app root
    
    Options:
    
    * `[-j][--json](bool){false}` Output as a raw json
    * `[-l][--limit](int){1000}` The number of results to return in each response to a list operation. The default value is 1000 (the maximum allowed). Using a lower value may help if an operation times out
    * `[--human-readable](bool){false}` Format size values from raw bytes to human readable format
    * `[-r][--recursive](bool){false}` Command is performed on all files or objects under the specified path
    
2. #### files:upload <file> <app_id> <remote_path>

    Upload file to selected app
    
    Arguments:
    
    * `<file>(string)` Path to file, that should be uploaded
    * `<app_id>(string)` The ID of the app
    * `<remote_path>(string)` Path on app, where uploaded file should be saved
    
3. #### files:download <app_id> <file_id> [--gzip]

    Download files from selected app in a zip archive.
    
    Arguments:
    
    * `<app_id>(string)` The ID of the app
    * `<file_id>(string){/}` The ID of the file. The ID is also the file path relative to its app root. Default value its a root of your app
    
    Options:
    
    * `[--gzip](bool){false}` Allow to download archive in a gzip archive

4. #### files:update <app_id> <remote_path> <local_file> [-r][--recur] [--command] [--source]
    
   This will update the file at specified file ID (file path including file name, relative to app root)
   
   Arguments:
       
   * `app_id(string)` The ID of the app
   * `<remote_path>(string)` File path on app, that should be updated
   * `<local_file>(string)` Path to a local file, which content will sent to remote 
   
   Options:
   
   * `[-r][--recur](bool){false}` Recur into directories
   * `[--command](string)` Command that will be executed in your app (Allowed: 'fetch', 'move', 'unarchive')
   * `[--source](string)` A URL to that will be retrieved if "command" is "fetch"
   
5. #### files:delete <app_id> <remote_path>

    Delete file on selected app
    
    Arguments:
    
    * `<app_id>(string)` The ID of the app
    * `<remote_path>(string)` Path on app, where uploaded file should be saved

### Phar updates

1. `self-update` Update your phar build to the latest release (will work only if you use phar build)


Composer scripts
------------

1. `composer build` Create phar build