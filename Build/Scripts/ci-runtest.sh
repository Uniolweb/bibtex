#!/bin/bash

PHP_VERSION=8.1
PHP_VERSION_PLATFORM="8.1.13"

# abort on error
set -e


# cleanup
# --------

echo "cleanup"
echo "cleanup: remove platform"
composer config --unset platform.php
composer config --unset platform
rm -f composer.lock

# composer validate
# (before add platform php to composer.json!)
# -------------------
echo "composer validate"
#Build/Scripts/runTests.sh -s composerValidate -p $PHP_VERSION
composer validate


# setup
# -----

echo "create link to auth.json"
rm -f auth.json
ln -s /var/www/site-uol11/auth.json auth.json

echo "composer validate"
composer validate

echo "add Platform $PHP_VERSION_PLATFORM"
composer config platform.php "$PHP_VERSION_PLATFORM"

echo "composer install"
Build/Scripts/runTests.sh -s composerInstall -p $PHP_VERSION

# check
# -----

echo "cgl"
Build/Scripts/runTests.sh -s cgl -n -p $PHP_VERSION

echo "lint"
Build/Scripts/runTests.sh -s lint -p $PHP_VERSION

echo "phpstan"
Build/Scripts/runTests.sh -s phpstan -p $PHP_VERSION

echo "Unit tests"
Build/Scripts/runTests.sh -s unit -p $PHP_VERSION

#echo "functional tests"
#Build/Scripts/runTests.sh -d mariadb -s functional

# cleanup
# --------

echo "cleanup: remove platform"
composer config --unset platform.php
composer config --unset platform

echo "done"
