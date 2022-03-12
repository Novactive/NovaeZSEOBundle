# Novactive eZ SEO Bundle

# <i class="fa fa-cogs"></i> Install

## <i class="fa fa-spinner"></i> Installation steps

### Use Composer

Add the lib to your composer.json, run `composer require novactive/ezseobundle` to refresh dependencies.

Then inject the bundle in the `bundles.php` of your application.

```php
    Novactive\Bundle\eZSEOBundle\NovaeZSEOBundle::class => [ 'all'=> true ],
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

You also need to run:

```bash
php bin/console ibexa:graphql:generate-schema
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
