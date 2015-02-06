# Novactive eZ SEO Bundle Install

## Usage

### novaseometas FieldType

#### Edit configuration (SiteAccess Aware)

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

This configuration defines 3 metas by default, _title_, _description_ and _keyword_

> You can add what you want to here.

There are 5 levels of fallback which allow you to set the good value for the metas.

Starting from the configuration to the Admin Interface with the Contributor:

- in configuration (Yaml only)
- in the Field Type Definition (Administration Interface, on the Content Type)
- in the Content  (Administration Interface, on the Content)
- Plus: same 3 previous rules but on the Root Content

Wait, you read 5 !

There is an abstract level based on a feature similar to the Object/Url Name Pattern.
 
At each level mentioned above, you can set a "defaut_pattern" value.

This pattern allows you to define a fallback on optional Field, "<title|name>" means it will try to take the title Field, if it finds nothing it will try the name.
Depending where you have set this pattern, the system will fallback to the other level

That's really powerful, and you can do almost what you want to.

If it's not enought, you can also add a sixth level with Twig manipulation!

#### Add the FieldType to your Content Type quickly

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

### Google Site Verification file

You can manage the Google Verification file in the configuration

```yml
novae_zseo:
    system:
        default:
            google_verification: 1234567890
```

> Simpler way, nothing to put on your server, no need to add a new RewriteRules


### Robots.txt file

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

> Simpler way to, nothing to put on your server, no need to add a new RewriteRules

> There is also a security, the Disallow / is automatically set when you're not in "prod" mode.


