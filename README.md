# [Lamp-io](https://www.lamp.io/) Command-line-interface

Installation
------------
##### As a Global Composer Install
```sh
$ composer global require lamp-io/lio 
```
#### Download as a PHAR
<a href = "">lio.phar</a>

#### As local composer package
```sh
composer require lamp-il/lio
```
Usage
------------
```sh
lio command [options] [arguments]
```

Commands
------------
1.  Usage: auth \[-u]\[--update_token]

    Will ask you to input your auth token

    Options:

    *   \[-u]\[--update_token] (bool) will allow to override your current auth token
2.  Usage: apps:list

    Will output you all your apps associated to your token

3.  Usage: apps:describe <app_id>

    Will output all app info

    Arguments:

    *   <app_id> The ID of the app  

4.  Usage: apps:new <organization_id> \[-d]\[--description] \[--httpd_conf] \[--max_replicas] \[-m]\[--memory] \[--min_replicas] \[--php_ini] \[-r]\[--replicas] \[--vcpu]

    Will allow you to create an app

    Arguments:

    *   <organization_id>\[optional](string) The ID(uuid) of the organization this app belongs to

    Options:

    *   [-d]\[--description](string){Default} A description of your app
    *   \[--httpd_conf](string){default appache config} Path to your httpd.conf
    *   \[--max_replicas](int){1} The maximum number of auto-scaled replicas
    *   [-m]\[--memory](string){128Mi} The amount of memory available (example: 1Gi)
    *   \[--min_replicas](string){1} The minimum number of auto-scaled replicas
    *   [--php_ini]{default php.ini} Path to your php.ini
    *   [-r]\[--replicas](int){1} The number current number replicas available. 0 stops app.
    *   \[--vcpu](float){0.25} The number of virtual cpu cores available (maximum: 4, minimum: 0.25)
    
5. 
 

