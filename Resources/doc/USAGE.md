# <i class="fa fa-3x fa-rocket"></i><br /> Usage


## Description

The main idea is to avoid you to manage the **&lt;meta&gt;** and **&lt;link&gt;** tag.
If there is a content when the view is rendered then the template will take care of the html tags according to the configuration and to the content.

If there is no content and if the content doesn't have the NovaSEO Field Type, the default **&lt;meta&gt;**, **&lt;title&gt;** and **&lt;link&gt;** will be set.
But you will have to manage yourself. ( we can't do that for you, but we'd simplified the process.

> All the configuration is SiteAccessAware then you can have different one depending on the SiteAccess


## Integration

```twig
# in your pagelayout
{% block seo_metas %}
    {% include "NovaeZSEOBundle::seometas_head.html.twig" %}
{% endblock %}
    
# in a template which extends the pagelayout (not required, useful when you want to do something special)
{% block seo_metas %}
    <title>.....</title>
    <meta name="..." />
    {{ parent() }}
{% endblock %}
```

> As this template handles a lot of &lt;meta&gt; and &lt;link&gt; and the &lt;title&gt; tag you have to be careful in your code to avoid duplicate tags.


## <i class="fa fa-3x fa-wrench"></i><br /> Configuration


### &lt;meta&gt; Configuration

```yml
novae_zseo:
    system:
        default:
            default_metas:
                author: "eZ Community Bundle Nova eZ SEO Bundle"
                copyright: ~
                generator: "eZ Platform"
                MSSmartTagsPreventParsing: "TRUE"
```

> You can add your own <meta> here


### &lt;link&gt; Configuration

```yml
novae_zseo:
    system:
        default:
            default_links:
                Index:
                    href: { location_id: 2 }
                    title: 'Home'
                Top:
                    href: { route : 'yourproject_home' }
                    title: 'Home'
                Search:
                    href: { legacy_uri: '/content/advancedsearch' }
                    title: 'Search'
                'Shortcut icon':
                    href: { asset: '/design/standard/images/favicon.ico' }
                    type: 'image/x-icon'
                Alternate:
                    href: { legacy_uri: '/rss/feed/my_feed' }
                    title: 'RSS'
                    type: 'application/rss+xml'
```

> You can add your own <link> here


### FieldType Configuration

You can add your Metas in your configuration

```yml
novae_zseo:
    system:
        default:
            fieldtype_metas_identifier: "metas"
            fieldtype_metas:
                title:
                    label: 'Title'
                    default_pattern: "<title|name>"
                description:
                    label: 'Description'
                    default_pattern: "<description|short_descrition|title|name>"
                keyword:
                    label: 'Keywords'
                    default_pattern: ~

```

This configuration defines 3 metas, _title_, _description_ and _keyword_

> You can add what you want to here.


## <i class="fa fa-3x fa-file"></i><br /> sitemap.xml

Your sitemap is automatically generated on the fly.

```yml
novae_zseo:
    system:
        default:
            sitemap_excludes:
                locations: [2]
                subtrees: [45,89,343]
                contentTypeIdentifiers: ['footer','something']
```

> It could be a performance issue, we plan to add a better way to do that soon on this extension


## Google Site Verification file

You can manage the Google Verification file in the configuration

```yml
novae_zseo:
    system:
        default:
            google_verification: 1234567890
```

> Simpler way, nothing to put on your server, no need to add a new RewriteRules


## Robots.txt file

You can manage the Robots.txt file

```yml
novae_zseo:
    system:
        default:
            google_verification: 1234567890
            robots_disallow:
                - "/admin"
                - "/specials"
```

> Nothing to put on your server, no need to add a new RewriteRules, and there is also a security, the Disallow / is automatically set when you're not in "prod" mode.



## How the Field Type works

There are 5 levels of fallback which allow you to set the good value for the metas.

- 1: in configuration (Yaml only)
- 2: in the Field Type Definition (Administration Interface, on the Content Type)
- 3: in the Content  (Administration Interface, on the Content)
- 4: Plus: same 3 previous rules but on the Root Content


Wait, you read 5 !

There is a transverse abstract level based on a feature similar to the Object/Url Name Pattern.


### <i class="fa fa-3x fa-magic"></i><br /> Object Pattern like feature

At each level mentioned above, you can set a **default_pattern** value.

This pattern allows you to define a fallback on optional Field, _&lt;title|name&gt;_ means it will try to take the title Field, if it finds nothing it will try the name.
Depending where you have set this pattern, the system will fallback to the other level


### Do what you want

That's really powerful, and you can do almost what you want to.

If it's not enough, you can also add a sixth level with Twig manipulation!

> You can use a image and and object_relation to an image ( needed for facebook image url for example )



## Add the FieldType to your Content Type

A command is provided to simply add the FieldType, you can always do it by the Administration Inferface

```bash
$ php ezpublish/console novae_zseo:addnovaseometasfieldtype -h
Usage:
 novae_zseo:addnovaseometasfieldtype [--identifier="..."] [--identifiers="..."] [--group_identifier="..."]

Options:
 --identifier          a content type identifier
 --identifiers         some content types identifier, separated by a comma
 --group_identifier    a content type group identifier
 ...
 --siteaccess          SiteAccess to use for operations. If not provided, default siteaccess will be used

Help:
 The command novae_zseo:addnovaseometasfieldtype add the FieldType 'novaseometas'.
 You can select the Content Type via the identifier, identifiers, group_identifier option.
     - Identifier will be: %novae_zseo.default.fieldtype_metas_identifier%
     - Name will be: Metas
     - Category will be: SEO
```
