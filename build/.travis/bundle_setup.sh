#!/bin/bash

#################################################################
# Novactive eZ Bundle setup script
#
# @package   NovaBundle
# @author    Guillaume Maïssa <g.maissa@novactive.com>
# @copyright 2015 Novactive
# @license   Proprietary
#################################################################

#################################################################
# This script helps you setup your CI environment to run tests
#################################################################

# Install composer dependencies for the bundle
composer require-dev novactive/phpcs-novastandards 

# Enable bundle
sed -i.bak 's#new EzPublishLegacyBundle(),#new EzPublishLegacyBundle(),\n            new Novactive\Bundle\eZSEOBundle\NovaeZSEOBundle(),#g' ${TRAVIS_BUILD_DIR}/ezpublish/EzPublishKernel.php

# Enable custom route
echo '
_novaseoRoutes:
    resource: "@NovaeZSEOBundle/Controller/"
        type:     annotation
            prefix:   /
' >> ${TRAVIS_BUILD_DIR}/ezpublish/config/routing.yml

# Install the legacy extension
php ezpublish/console ezpublish:legacybundles:install_extensions
cd ezpublish_legacy
php bin/php/ezpgenerateautoloads.php -e
cd ..

# Create bundle table
mysql -u root behattestdb < ${TRAVIS_BUILD_DIR}/${NOVABUNDLE_PATH}/Resources/sql/shema.sql

# Remove robots.txt rewrite rule
sed -i.bak 's|RewriteRule \^\/robots|#RewriteRule \^\/robots|' /etc/apache2/sites-available/behat
