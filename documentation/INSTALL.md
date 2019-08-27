# <i class="fa fa-3x fa-cogs"></i><br /> Install


## Requirements

* eZ Platform 2.x
* PHP PHP 7.1


## <i class="fa fa-3x fa-spinner"></i><br /> Installation steps


### Use Composer

Add the following to your composer.json and run `php composer.phar update novactive/ezseobundle` to refresh dependencies:

```json
# composer.json

"require": {
    "novactive/ezseobundle": "^4.0",
}
```


### Register the bundle

Activate the bundle in `app\AppKernel.php` file.

```php
// app\AppKernel.php

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
# app/config/routing.yml

_novaezseo_routes:
    resource: '@NovaeZSEOBundle/Resources/config/routing/main.yaml'

```

### Create the table

See the file `bundle/Resources/sql/shema.sql`


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
