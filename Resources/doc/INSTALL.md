# <i class="fa fa-3x fa-cogs"></i><br /> Install


## Requirements

* eZPlatform 1.6+ / eZStudio 1.6+
* PHP 5.6+


## <i class="fa fa-3x fa-spinner"></i><br /> Installation steps


### Use Composer

Add the following to your composer.json and run `php composer.phar update novactive/ezseobundle` to refresh dependencies:

```json
# composer.json

"require": {
    "novactive/ezseobundle": "dev-develop-6.6.x",
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

_novaezseoRoutes:
    resource: "@NovaeZSEOBundle/Controller/"
    type:     annotation
    prefix:   /
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