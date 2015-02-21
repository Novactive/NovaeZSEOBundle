#!/bin/bash

#################################################################
# Novactive eZ Bundle setup script for continuous integration
#
# @package   NovaBundle
# @author    Guillaume Maïssa <g.maissa@novactive.com>
# @copyright 2015 Novactive
# @license   Proprietary
#################################################################

#################################################################
# This script helps you setup your CI environment to run tests
#################################################################

echo "> Install bundle dependencies"
composer require novactive/phpcs-novastandards:~1.3
composer require phpmd/phpmd:~2.1
composer require sebastian/phpcpd:~2.0
composer dump-autoload

echo "> Enable bundle"
sed -i.bak 's#new EzPublishLegacyBundle(),#new EzPublishLegacyBundle(),\n            new Novactive\Bundle\eZSEOBundle\NovaeZSEOBundle(),#g' ${TRAVIS_BUILD_DIR}/ezpublish/EzPublishKernel.php

echo "> Add bundle route"
echo '
_novaseoRoutes:
    resource: "@NovaeZSEOBundle/Controller/"
        type:     annotation
            prefix:   /
' >> ${TRAVIS_BUILD_DIR}/ezpublish/config/routing.yml

echo "> Install bundle legacy extension"
php ezpublish/console ezpublish:legacybundles:install_extensions
cd ${TRAVIS_BUILD_DIR}/ezpublish_legacy
php bin/php/ezpgenerateautoloads.php -e
cd ${TRAVIS_BUILD_DIR}

echo "> Create bundle table"
mysql -u root behattestdb < ${TRAVIS_BUILD_DIR}/${NOVABUNDLE_PATH}/Resources/sql/shema.sql

echo "> Update apache config"
sudo sed -i 's|RewriteRule \^\/robots|#RewriteRule \^\/robots|' /etc/apache2/sites-enabled/behat
sudo service apache2 restart
