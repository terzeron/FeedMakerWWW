FeedMaker management web pages
==============================

The simple web pages written in PHP code.

Requirements
------------
1. jQuery
  1. jquery & jquery-ui

Preparation
-----------

You should set the environment variable 'FEED_MAKER_WWW_ADMIN_DIR'.

`$ export FEED_MAKER_WWW_ADMIN_DIR=$HOME/public_html/feed_maker_web`

Also you should install Apache web server and enable 'mod_php' module in the server configuration (httpd.conf).
You can reference the following example.

```
LoadModule php5_module        /Applications/MAMP/bin/php/php5.6.7/modules/libphp5.so
AddType application/x-httpd-php .php .phtml
DirectoryIndex index.html index.php
DocumentRoot "/home/terzeron/public_html"
<Directory "/home/terzeron/public_html">
    Options All
    AllowOverride All
    Order deny,allow
</Directory>
```

Usage
-----

Try to open [http://yoursite.com/feed_maker_web](http://yoursite.com/feed_maker_web)

