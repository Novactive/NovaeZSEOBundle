# Novactive eZ SEO Bundle

# <i class="fa fa-cogs"></i> Install

## <i class="fa fa-spinner"></i> Installation steps


### Use Composer

Add the lib to your composer.json, run `composer require novactive/ezseobundle` to refresh dependencies.

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

See the file `bundle/Resources/sql/schema.sql`

If on eZ Platform 3x, you need to run:

```bash
php bin/console ezplatform:graphql:generate-schema
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
