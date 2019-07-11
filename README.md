<h1><a href="https://www.lamp.io/" target="_blank">
    Lamp-io Command-line-interface
</a></h1>

Installation
------------
#### As a Global Composer Install
```sh
$ composer global require lamp-io/lio 
```
### Download as a PHAR
<a href = "">lio.phar</a>

Commands
------------
<ol>
<li> 
Usage: auth [-u][--update_token]
<p>Will ask you to input your auth token</p>
<p>Options:</p><ul>
<li>
[-u][--update_token] (bool) will allow to override your current auth token
</li>
</ul>
</li>
<li>
Usage: apps:list
<p>Will output you all your apps associated to your token</p>
</li>
<li>
Usage: apps:describe &lt;app_id&gt; 
<p>Will output all app info</p>
<p>Arguments:</p>
<ul>
<li>
&lt;app_id&gt; The ID of the app 
</li>
</ul><br>
<li>
Usage: apps:new &lt;organization_id&gt; [-d][--description] [--httpd_conf] [--max_replicas] [-m][--memory] [--min_replicas] [--php_ini] [-r][--replicas] [--vcpu]
<p>Will allow you to create an app</p>
<p>Arguments:</p>
<ul>
<li>
&lt;organization_id&gt[optional](string) The ID(uuid) of the organization this app belongs to 
</li>
</ul>
<p>Options:</p>
<ul>
<li>
[-d][--description](string){Default} A description of your app
</li>
<li>
[--httpd_conf](string){default appache config} Path to your httpd.conf
</li>
<li>
[--max_replicas](int){1} The maximum number of auto-scaled replicas
</li>
<li>
[-m][--memory](string){128Mi} The amount of memory available (example: 1Gi)
</li>
<li>
[--min_replicas](string){1} The minimum number of auto-scaled replicas
</li>
<li>
[--php_ini]{default php.ini} Path to your php.ini
</li>
<li>
[-r][--replicas](int){1} The number current number replicas available. 0 stops app.
</li>
<li>
[--vcpu](float){0.25}  The number of virtual cpu cores available (maximum: 4, minimum: 0.25)
</li>
</ul>
</li>
</ol>

