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
### Global options
    
    * [-j][--json] Output as a raw json
    * [-h][--help](bool) Display this help message
    * [-q][--quiet](bool) Do not output any message
    * [-V][--version](bool) Display this application version
    * [--ansi](bool) Force ANSI output
    * [--no-ansi](bool) Disable ANSI output
    * [-n][--no-interaction](bool) Do not ask any interactive question
    * [-v|vv|vvv][--verbose](bool) Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug

### Deploy
1. ### deploy [--laravel] [\<dir>]
    Deploy your app.
    
    Arguments:
    
    * `<dir>[optional](string){$PWD}` Path to your project, default your current working directory
    
    Options:
    * `[--laravel](bool){false}` Specify your app as a laravel project 
    


### Token
1.  #### auth [-u][--update_token]

    Will ask you to input your auth token

    Options:

    * `[-u][--update_token](bool){false}` will allow to override your current auth token
    
### Apps
1.  #### apps:list

    Will output you all your apps associated to your token
    

2.  #### apps:describe <app_id>

    Will output all app info

    Arguments:

    * `<app_id>(string)` The ID of the app
    

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
    
### App backups

1.  #### app_backups:create <app_id>

    Back up files in app
    
    Arguments:
        
    * `<app_id>(string)` The ID of the app

2.  #### app_backups:list [-o][--organization_id]
   
    Return list of all your app backups
    
    Options:
    
    * `[-o][--organization_id](string)` Comma-separated list of requested organization_ids. If omitted defaults to user's default organization

3. #### app_backups:describe <app_backup_id> 

    Return an app backup
    
    Arguments:
    
    * `<app_backup_id>(string)` The ID of the app backup
    
    
4. #### app_backups:download <app_backup_id> <dir>
    
   Download an app backup 
    
    Arguments:
        
    * `<app_backup_id>(string)` The ID of the app backup
    * `<dir>(string){$PWD}` Path to directory, where should be stored downloaded file. Default value current working directory
    
5. #### app_backups:delete <app_backup_id> <dir>

    Delete an app backup
    
    Arguments:
        
    * `<app_backup_id>(string)` The ID of the app backup
    
6.  #### app_backups:list [-o][--organization_id]

    Return list of all your app backups
    
    Options:
    
    * `[-o][--organization_id](string)` Comma-separated list of requested organization_ids. If omitted defaults to user's default organization
    
### App runs

1. ### app_runs:new <app_id> <exec>

    Run command on app'
    
    Arguments:
    * `<app_id>(string)` The ID of the app
    * `<exec>(string)` Command that will be ran
    
2. ###app_runs:describe

    Run command on app
    
    Arguments:
    * `<app_run_id>(string)` ID of runned command

### Files

1. #### files:list [-l][--limit] [--human-readable] [-r][--recursive] <app_id> <file_id>

    Return files from the root of an app (if not define <file_id>)
    
    Arguments:
    
    * `<app_id>(string)` The ID of the app
    * `file_id(string){/}` The ID of the file. The ID is also the file path relative to its app root
    
    Options:
    
    * `[-l][--limit](int){1000}` The number of results to return in each response to a list operation. The default value is 1000 (the maximum allowed). Using a lower value may help if an operation times out
    * `[--human-readable](bool){false}` Format size values from raw bytes to human readable format
    * `[-r][--recursive](bool){false}` Command is performed on all files or objects under the specified path
    
2. #### files:upload <file> <app_id> <remote_path>

    Upload file to selected app
    
    Arguments:
    
    * `<file>(string)` Path to file, that should be uploaded
    * `<app_id>(string)` The ID of the app
    * `<remote_path>(string)` Path on app, where uploaded file should be saved
    
3. #### files:download <app_id> <file_id> <dir>

    Download files from selected app in a zip archive.
    
    Arguments:
    
    * `<app_id>(string)` The ID of the app
    * `<file_id>(string)` The ID of the file. The ID is also the file path relative to its app root.
    * `<dir>(string){$PWD}` Path to directory, where should be stored downloaded file. Default value current working directory
    
4. #### files:update <app_id> [<remote_path>] [<local_file>] [-r][--recur] [--command] [--source]
    
   This will update the file at specified file ID (file path including file name, relative to app root)
   
   Arguments:
       
   * `<app_id(string)>` The ID of the app
   * `<remote_path>(string)[optional]{}` File path on app, that should be updated
   * `<local_file>(string)[optional]{}` Path to a local file, which content will sent to remote. If not specified, will make your <remote_path> appache writable

5. #### files:delete <app_id> <remote_path>

    Delete file/directory on selected app
    
    Arguments:
    
    * `<app_id>(string)` The ID of the app
    * `<remote_path>(string)` Remote path on app, what file/directory you need to delete

### Files sub commands: 

1. #### files:update:unarchive <app_id> <remote_path>
    Extract your archived file, on your app
    
    Arguments:
    
    * `<app_id>(string)` The ID of the app
    * `<remote_path>(string)` File path on app, that should be unarchived

### Users

1. #### files:update:unarchive [--organization_id][-o] [--email][-e]

    Get all users from your account
    
    Options:
    
    * `[--organization_id][-o](string)` Comma-separated list of requested organization_ids. If omitted defaults to user's default organization
    * `[--email][-e](string)` Format size values from raw bytes to human readable format
    
### Phar updates

1. `self-update` Update your phar build to the latest release (will work only if you use phar build)


Composer scripts
------------

1. `composer build` Create phar build
