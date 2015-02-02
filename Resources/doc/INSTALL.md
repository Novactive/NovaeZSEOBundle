# Novactive eZ SEO Bundle Install


## Requirements

* eZ Publish 5.3+ / eZ Publish Community Project 2014.05+


## Installation steps

### Use Composer

Add the following to your composer.json and run `php composer.phar update novactive/ezseobundle` to refresh dependencies:

```json
#composer.json

"require": {
    "novactive/ezseobundle": "dev-master",
}
```

### Register the bundle

Activate the bundle in `ezpublish\EzPublishKernel.php` file.

```php
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

### Edit configuration


#### FieldType 

You can add your Metas in your configuration

```yml
parameters:
    novaseo.default.metas:
      - { key: 'title', label: 'Title' }
      - { key: 'description', label: 'Description' }
      - { key: 'keyword', label: 'Keywords' }

```

#### Robots.txt

You can manage your Disallow in the configuration

```yml
parameters:
    novaseo.default.disallow:
        - "/plop"
        - "/plop2"
        - "/plop3"

```

#### Google Site Verification

You can manage the Google Verification file in the configuration

```yml
parameters:
    novaseo.default.google_verification: 1234567890
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