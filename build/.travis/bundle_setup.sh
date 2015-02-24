#!/bin/bash

###########################################################################################
# Novactive eZ Bundle setup script for continuous integration
#
# @package   Novactive\Bundle\eZSEOBundle
# @author    Novactive <novaseobundle@novactive.com>
# @copyright 2015 Novactive
# @license   https://github.com/Novactive/NovaeZSEOBundle/blob/master/LICENSE MIT Licence
###########################################################################################

#################################################################
# This script helps you setup your CI environment to run tests
#################################################################

echo "> Install bundle dependencies"
composer require novactive/phpcs-novastandards:~1.3 phpmd/phpmd:~2.1 sebastian/phpcpd:~2.0 phpunit/phpunit:~4.4
sudo sed -i "s|return array(|return array('Novactive\\Bundle\\eZSEOBundle\\' => array($vendorDir . '/novactive/seobundle'),|" ${TRAVIS_BUILD_DIR}/vendor/composer/autoload_psr4.php


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
php ezpublish/console clear:cache

echo "> Create bundle table"
mysql -u root behattestdb < ${TRAVIS_BUILD_DIR}/${NOVABUNDLE_PATH}/Resources/sql/shema.sql

echo "> Update apache config"
sudo sed -i 's|RewriteRule \^\/robots|#RewriteRule \^\/robots|' /etc/apache2/sites-enabled/behat
sudo service apache2 restart
