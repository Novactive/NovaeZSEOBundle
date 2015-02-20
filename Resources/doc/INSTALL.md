# <i class="fa fa-3x fa-cogs"></i><br /> Install


## Requirements

* eZ Publish 5.4+ / eZ Publish Community Project 2014.07+
* PHP 5.4+


## <i class="fa fa-3x fa-spinner"></i><br /> Installation steps


### Use Composer

Add the following to your composer.json and run `php composer.phar update novactive/ezseobundle` to refresh dependencies:

```json
# composer.json

"require": {
    "novactive/ezseobundle": "dev-master",
}
```


### Register the bundle

Activate the bundle in `ezpublish\EzPublishKernel.php` file.

```php
// ezpublish\EzPublishKernel.php

public function registerBundles()
{
   ...
   $bundles = array(
       new FrameworkBundle(),
       ...
       new Novactive\Bundle\eZSEOBundle\NovaeZSEOBundle(),
   );
   ...
}
```


### Add routes

Make sure you add this route to your routing:

```yml
# ezpublish/config/routing.yml

_novaseoRoutes:
    resource: "@NovaeZSEOBundle/Controller/"
    type:     annotation
    prefix:   /
```


### Install the Legacy extension

```bash
php ezpublish/console ezpublish:legacybundles:install_extensions
cd ezpublish_legacy
php bin/php/ezpgenerateautoloads.php -e
```


### Create the table

```mysql
CREATE TABLE `novaseo_meta` (
  `objectattribute_id` bigint(20) unsigned NOT NULL,
  `meta_name` varchar(255) NOT NULL,
  `meta_content` varchar(255) NOT NULL,
  `objectattribute_version` int(10) unsigned NOT NULL,
  PRIMARY KEY (`objectattribute_id`,`objectattribute_version`,`meta_name`),
  KEY `novaseo_idx_content` (`objectattribute_id`,`objectattribute_version`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
```


### Remove the Robots.txt (native RewriteRules )

Add a `#` at the beginning of the line

#### Nginx

```
#rewrite "^/robots\.txt" "/robots.txt" break;
```

#### Apache

```
#RewriteRule ^/robots\.txt - [L]
```