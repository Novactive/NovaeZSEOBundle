# <i class="fa fa-3x fa-cogs"></i><br /> Install


## Requirements

* eZ Platform 3.x
* PHP PHP 7.4


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

Activate the bundle in `config\bundles.php` file.

```php
// config\bundles.php

<?php

return [
    Symfony\Bundle\FrameworkBundle\FrameworkBundle::class => ['all' => true],
    ...
    Novactive\Bundle\eZSEOBundle\NovaeZSEOBundle::class => ['all' => true],
];
```


### Add routes

Make sure you add this route to your routing:

```yml
# config/routes.yaml

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
