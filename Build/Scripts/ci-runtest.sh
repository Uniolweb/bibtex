#!/bin/bash

# 7.4 and composer latest

# abort on error
set -e

echo "create link to auth.json"
rm -f auth.json
ln -s /var/www/site-uol11/auth.json auth.json

echo "composer install"
Build/Scripts/runTests.sh -s composerInstallMax

echo "cgl"
Build/Scripts/runTests.sh -s cgl -n

echo "composer validate"
Build/Scripts/runTests.sh -s composerValidate

echo "lint"
Build/Scripts/runTests.sh -s lint

echo "phpstan"
Build/Scripts/runTests.sh -s phpstan -e "-c ../phpstan.neon" -v

echo "Unit tests"
Build/Scripts/runTests.sh -s unit -v

#echo "functional tests"
#Build/Scripts/runTests.sh -d mariadb -s functional

echo "done"
