#!/usr/bin/env bash

##########################################################################
# runTests.sh script
# - based on Build/Scripts/runTests.sh in TYPO3 core
# - uses docker images, see https://github.com/orgs/TYPO3/packages
##########################################################################

# run from extension directory
# last modified 24.03.2026

# @todo mechanism for setting config.platform.php version without modifying composer.json
#    see   https://github.com/composer/composer/issues/7082
#    composer update --config "platform.php=7.4"
#    does not exist for composer install ...?


# ------
# config
# ------
SUPPORTED_PHP_VERSIONS="8.2|8.3|8.4"
DEFAULT_PHP_VERSION="8.4"
PHP_VERSION="${DEFAULT_PHP_VERSION}"
DEFAULT_PHP_PLATFORM_VERSION="8.4.12"

###
# Set platform PHP version major.minor.patch based on PHP version major.minor
###
case "$PHP_VERSION" in
    "8.1")
        PHP_PLATFORM_VERSION="8.1.20"
        ;;
    "8.2")
        PHP_PLATFORM_VERSION="8.2.29"
        ;;
    "8.3")
        PHP_PLATFORM_VERSION="8.3.23"
        ;;
    "8.4")
        PHP_PLATFORM_VERSION="8.4.10"
        ;;
    *)
        echo "Invalid PHP version"
        exit 1
esac

### define PHP docker version via PHP version
# - currently not used, we always used "latest"
# - but is used in TYPO3 core
###
getPhpImageVersion() {
    case ${1} in
        8.2)
            echo -n "1.13"
            ;;
        8.3)
            echo -n "1.14"
            ;;
        8.4)
            echo -n "1.6"
            ;;
        8.5)
            echo -n "1.0"
            ;;
    esac
}

# Function to write a .env file in Build/testing-docker/local
# This is read by docker compose and vars defined here are
# used in Build/testing-docker/local/docker-compose.yml
setUpDockerComposeDotEnv() {
    # Delete possibly existing local .env file if exists
    [ -e .env ] && rm -f .env
    # Set up a new .env file for docker-compose
    {
        echo "COMPOSE_PROJECT_NAME=local"
        # To prevent access rights of files created by the testing, the docker image later
        # runs with the same user that is currently executing the script. docker-compose can't
        # use $UID directly itself since it is a shell variable and not an env variable, so
        # we have to set it explicitly here.
        echo "HOST_UID=$(id -u)"
        # Your local user
        echo "CORE_ROOT=${CORE_ROOT}"
        echo "DOCKER_COMPOSE_DIR=${DOCKER_COMPOSE_DIR}"
        echo "UNIT_TEST_CONFIG_DIR=${UNIT_TEST_CONFIG_DIR}"
        echo "FUNCTIONAL_TEST_CONFIG_DIR=${FUNCTIONAL_TEST_CONFIG_DIR}"
        echo "HOST_USER=${USER}"
        echo "TEST_FILE=${TEST_FILE}"
        echo "PHP_XDEBUG_ON=${PHP_XDEBUG_ON}"
        echo "PHP_XDEBUG_PORT=${PHP_XDEBUG_PORT}"
        echo "DOCKER_PHP_IMAGE=${DOCKER_PHP_IMAGE}"
        echo "DOCKER_PHP_IMAGE_NAME=${DOCKER_PHP_IMAGE_NAME}"
        echo "EXTRA_TEST_OPTIONS=${EXTRA_TEST_OPTIONS}"
        echo "SCRIPT_VERBOSE=${SCRIPT_VERBOSE}"
        echo "PHPUNIT_RANDOM=${PHPUNIT_RANDOM}"
        echo "CGLCHECK_DRY_RUN=${CGLCHECK_DRY_RUN}"
        echo "DATABASE_DRIVER=${DATABASE_DRIVER}"
        echo "MARIADB_VERSION=${MARIADB_VERSION}"
        echo "MYSQL_VERSION=${MYSQL_VERSION}"
        echo "POSTGRES_VERSION=${POSTGRES_VERSION}"
        echo "PHP_VERSION=${PHP_VERSION}"
        echo "TYPO3_VERSION=${TYPO3_VERSION}"
        echo "DEFAULT_PHP_VERSION"=${DEFAULT_PHP_VERSION}
        echo "DEFAULT_PHP_PLATFORM_VERSION"=${DEFAULT_PHP_PLATFORM_VERSION}
        echo "PHP_PLATFORM_VERSION"=${PHP_PLATFORM_VERSION}
        echo "CHUNKS=${CHUNKS}"
        echo "THISCHUNK=${THISCHUNK}"
        echo "DOCKER_COMPOSE_COMMAND=${DOCKER_COMPOSE_COMMAND}"
        echo "CURDIR=${CURDIR}"
    } > .env
}

# Options -a and -d depend on each other. The function
# validates input combinations and sets defaults.
handleDbmsAndDriverOptions() {
    case ${DBMS} in
        mysql|mariadb)
            [ -z "${DATABASE_DRIVER}" ] && DATABASE_DRIVER="mysqli"
            if [ "${DATABASE_DRIVER}" != "mysqli" ] && [ "${DATABASE_DRIVER}" != "pdo_mysql" ]; then
                echo "Invalid option -a ${DATABASE_DRIVER} with -d ${DBMS}" >&2
                echo >&2
                echo "call \".Build/Scripts/runTests.sh -h\" to display help and valid options" >&2
                exit 1
            fi
            ;;
        mssql)
            [ -z ${DATABASE_DRIVER} ] && DATABASE_DRIVER="sqlsrv"
            if [ "${DATABASE_DRIVER}" != "sqlsrv" ] && [ "${DATABASE_DRIVER}" != "pdo_sqlsrv" ]; then
                echo "Invalid option -a ${DATABASE_DRIVER} with -d ${DBMS}" >&2
                echo >&2
                echo "call \".Build/Scripts/runTests.sh -h\" to display help and valid options" >&2
                exit 1
            fi
            ;;
        postgres|sqlite)
            if [ -n "${DATABASE_DRIVER}" ]; then
                echo "Invalid option -a ${DATABASE_DRIVER} with -d ${DBMS}" >&2
                echo >&2
                echo "call \".Build/Scripts/runTests.sh -h\" to display help and valid options" >&2
                exit 1
            fi
            ;;
    esac
}

# encapsulate the docker compose run command. Intention
# 1. Make it possible to make changes in one place
# 2. Create a unique name for the images
#    otherwise we may get problems if running parallel (with older Docker versions)
#    run with --name name_<pid of process>
# 4. Add --rm so container will be torn down and we don't have to run ${DOCKER_COMPOSE_COMMAND} down
#    (which might also bring down all containers)
function docker_run() {
    pid=$$
    name="$1_$pid"
    echo "docker_run: ${DOCKER_COMPOSE_COMMAND} run --rm --name $name $1"
    ${DOCKER_COMPOSE_COMMAND} run --rm --name $name $1
}

function docker_down() {
    echo "docker_down: do nothing"
    #${DOCKER_COMPOSE_COMMAND} down
}

# Load help text into $HELP
read -r -d '' HELP <<EOF
TYPO3 core test runner. Execute acceptance, unit, functional and other test suites in
a docker based test environment. Handles execution of single test files, sending
xdebug information to a local IDE and more.

Recommended docker version is >=20.10 for xdebug break pointing to work reliably, and
a recent ${DOCKER_COMPOSE_COMMAND} (tested >=1.21.2) is needed.

Usage: $0 [options] [file]

No arguments: Run all unit tests with PHP 7.2

Options:
    -s <...>
        Specifies which test suite to run
            - acceptance: main backend acceptance tests
            - buildCss: execute scss to css builder
            - buildJavascript: execute typescript to javascript builder
            - cglGit: test and fix latest committed patch for CGL compliance
            - cglAll: test and fix all core php files
            - checkAnnotations: check php code for allowed annotations
            - checkBom: check UTF-8 files do not contain BOM
            - checkComposer: check composer.json files for version integrity
            - checkCsvFixtures: test integrity of functional test csv fixtures
            - checkExceptionCodes: test core for duplicate exception codes
            - checkExtensionScannerRst: test all .rst files referenced by extension scanner exist
            - checkFilePathLength: test core file paths do not exceed maximum length
            - checkGitSubmodule: test core git has no sub modules defined
            - checkGruntClean: Verify "grunt build" is clean. Warning: Executes git commands! Usually used in CI only.
            - checkPermissions: test some core files for correct executable bits
            - checkRst: test .rst files for integrity
            - composerInstall: "composer install"
            - composerRequire: "composer require typo3/cms-core, needs -t "
            - composerInstallMax: "composer update", with no platform.php config.
            - composerInstallMin: "composer update --prefer-lowest", with platform.php set to PHP version x.x.0.
            - composerValidate: "composer validate"
            - docBlockCheck: Scan php doc blocks for validity
            - fixCsvFixtures: fix broken functional test csv fixtures
            - functional: functional tests
            - install: installation acceptance tests, only with -d mariadb|postgres|sqlite
            - lint: PHP linting
            - lintScss: SCSS linting
            - lintTypescript: TS linting
            - lintHtml: HTML linting
            - listExceptionCodes: list core exception codes in JSON format
            - phpstan: phpstan tests
            - unit (default): PHP unit tests
            - unitDeprecated: deprecated PHP unit tests
            - unitJavascript: JavaScript unit tests
            - unitRandom: PHP unit tests in random order, add -o <number> to use specific seed

    -a <mysqli|pdo_mysql|sqlsrv|pdo_sqlsrv>
        Only with -s functional
        Specifies to use another driver, following combinations are available:
            - mysql
                - mysqli (default)
                - pdo_mysql
            - mariadb
                - mysqli (default)
                - pdo_mysql
            - mssql
                - sqlsrv (default)
                - pdo_sqlsrv

    -d <mariadb|mysql|mssql|postgres|sqlite>
        Only with -s install|functional|acceptance
        Specifies on which DBMS tests are performed
            - mariadb (default): use mariadb
            - mysql: use MySQL server
            - mssql: use mssql microsoft sql server
            - postgres: use postgres
            - sqlite: use sqlite

    -i <10.1|10.2|10.3|10.4|10.5>
        Only with -d mariadb
        Specifies on which version of mariadb tests are performed
            - 10.1
            - 10.2
            - 10.3
            - 10.4 (default)
            - 10.5

    -j <5.5|5.6|5.7|8.0>
        Only with -d mysql
        Specifies on which version of mysql tests are performed
            - 5.5 (default)
            - 5.6
            - 5.7
            - 8.0

    -k <9.6|10|11|12|13>
        Only with -d postgres
        Specifies on which version of postgres tests are performed
            - 9.6
            - 10 (default)
            - 11
            - 12
             -13

    -c <chunk/numberOfChunks>
        Only with -s functional|acceptance
        Hack functional or acceptance tests into #numberOfChunks pieces and run tests of #chunk.
        Example -c 3/13

    -t <12|13|14>
            Only with -s composerInstall|composerInstallMin|composerInstallMax
            Specifies the TYPO3 CORE Version to be used
                - 12: use TYPO3 v12
                - 13: use TYPO3 v13 (default)
                - 14: use TYPO3 v14

    -p <$SUPPORTED_PHP_VERSIONS>
        Specifies the PHP minor version to be used

    -e "<phpunit or codeception options>"
        Only with -s functional|unit|unitDeprecated|unitRandom|acceptance|install
        Additional options to send to phpunit (unit & functional tests) or codeception (acceptance
        tests). For phpunit, options starting with "--" must be added after options starting with "-".
        Example -e "-v --filter canRetrieveValueWithGP" to enable verbose output AND filter tests
        named "canRetrieveValueWithGP"

    -x
        Only with -s functional|unit|unitDeprecated|unitRandom|acceptance|install
        Send information to host instance for test or system under test break points. This is especially
        useful if a local PhpStorm instance is listening on default xdebug port 9003. A different port
        can be selected with -y

    -y <port>
        Send xdebug information to a different port than default 9003 if an IDE like PhpStorm
        is not listening on default port.

    -o <number>
        Only with -s unitRandom
        Set specific random seed to replay a random run in this order again. The phpunit randomizer
        outputs the used seed at the end (in gitlab core testing logs, too). Use that number to
        replay the unit tests in that order.

    -n
        Only with -s cglGit|cglAll
        Activate dry-run in CGL check that does not actively change files and only prints broken ones.

    -u
        Update existing typo3/core-testing-*:latest docker images. Maintenance call to docker pull latest
        versions of the main php images. The images are updated once in a while and only the youngest
        ones are supported by core testing. Use this if weird test errors occur. Also removes obsolete
        image versions of typo3/core-testing-*.

    -v
        Enable verbose script output. Shows variables and docker commands.

    -h
        Show this help.

Examples:
    # Run all core unit tests using PHP 7.2
    ./Build/Scripts/runTests.sh
    ./Build/Scripts/runTests.sh -s unit

    # Run all core units tests and enable xdebug (have a PhpStorm listening on port 9003!)
    ./Build/Scripts/runTests.sh -x

    # Run unit tests in phpunit verbose mode with xdebug on PHP 7.3 and filter for test canRetrieveValueWithGP
    ./Build/Scripts/runTests.sh -x -p 7.3 -e "-v --filter canRetrieveValueWithGP"

    # Run functional tests in phpunit with a filtered test method name in a specified file
    # example will currently execute two tests, both of which start with the search term
    ./Build/Scripts/runTests.sh -s functional -e "--filter deleteContent" typo3/sysext/core/Tests/Functional/DataHandling/Regular/Modify/ActionTest.php

    # Run unit tests with PHP 7.3 and have xdebug enabled
    ./Build/Scripts/runTests.sh -x -p 7.3

    # Run functional tests on postgres with xdebug, php 7.3 and execute a restricted set of tests
    ./Build/Scripts/runTests.sh -x -p 7.3 -s functional -d postgres typo3/sysext/core/Tests/Functional/Authentication

    # Run functional tests on mariadb 10.5
    ./Build/Scripts/runTests.sh -d mariadb -i 10.5

    # Run install tests on mysql 8.0
    .Build/Scripts/runTests.sh -d mysql -j 8.0

    # Run functional tests on postgres 11
    ./Build/Scripts/runTests.sh -d postgres -k 11

    # Run restricted set of backend acceptance tests
    ./Build/Scripts/runTests.sh -s acceptance typo3/sysext/core/Tests/Acceptance/Backend/Login/BackendLoginCest.php

    # Run installer tests of a new instance on sqlite
    ./Build/Scripts/runTests.sh -s install -d sqlite
EOF

# Test if ${DOCKER_COMPOSE_COMMAND} exists, else exit out with error
if ! type "docker" > /dev/null; then
    echo "This script relies on docker and docker compose. Please install" >&2
    exit 1
fi

# Go to the directory this script is located, so everything else is relative
# to this dir, no matter from where this script is called.
THIS_SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" >/dev/null && pwd)"
cd "$THIS_SCRIPT_DIR" || exit 1

# Go to directory that contains the local docker-compose.yml file
cd ../testing-docker/local || exit 1
DOCKER_COMPOSE_DIR=$(pwd)

# Set core root path by checking whether realpath exists
# CORE_ROOT should usually be path of extension
# here, the path is set 3 levels above Build/Scripts/runTests.sh
if ! command -v realpath &> /dev/null; then
    echo "Consider installing realpath for properly resolving symlinks" >&2
    CORE_ROOT="${PWD}/../../../"
else
    CORE_ROOT=$(realpath "${PWD}/../../../")
fi

# if running runTests.sh within .Build/vendor/uniolweb/unioltest/Build/Script/runTests.sh
#CORE_ROOT="${PWD}/../../../../../../../"
#UNIOLTEST_ROOT="${THIS_SCRIPT_DIR}/"
UNIOLTEST_ROOT="${CORE_ROOT}"
UNIT_TEST_CONFIG_DIR="${UNIOLTEST_ROOT}/Build/phpunit"
FUNCTIONAL_TEST_CONFIG_DIR="${UNIOLTEST_ROOT}/Build/phpunit"



# Option defaults
TEST_SUITE="unit"
DBMS="mariadb"
PHP_XDEBUG_ON=0
PHP_XDEBUG_PORT=9003
EXTRA_TEST_OPTIONS=""
SCRIPT_VERBOSE=0
PHPUNIT_RANDOM=""
CGLCHECK_DRY_RUN=""
DATABASE_DRIVER=""
MARIADB_VERSION="10.4"
MYSQL_VERSION="5.5"
POSTGRES_VERSION="10"
CHUNKS=0
THISCHUNK=0
DOCKER_COMPOSE_COMMAND="docker-compose"
which docker-compose 2>/dev/null >/dev/null
if [ $? -ne 0 ];then
     DOCKER_COMPOSE_COMMAND="docker compose"
fi

# Option parsing
# Reset in case getopts has been used previously in the shell
OPTIND=1
# Array for invalid options
INVALID_OPTIONS=();
# Simple option parsing based on getopts (! not getopt)
while getopts ":a:s:c:d:i:j:k:p:t:e:xy:o:nhuv" OPT; do
    case ${OPT} in
        s)
            TEST_SUITE=${OPTARG}
            ;;
        a)
            DATABASE_DRIVER=${OPTARG}
            ;;
        c)
            if ! [[ ${OPTARG} =~ ^([0-9]+\/[0-9]+)$ ]]; then
                INVALID_OPTIONS+=("${OPTARG}")
            else
                # Split "2/13" - run chunk 2 of 13 chunks
                THISCHUNK=$(echo "${OPTARG}" | cut -d '/' -f1)
                CHUNKS=$(echo "${OPTARG}" | cut -d '/' -f2)
            fi
            ;;
        d)
            DBMS=${OPTARG}
            ;;
        i)
            MARIADB_VERSION=${OPTARG}
            if ! [[ ${MARIADB_VERSION} =~ ^(10.1|10.2|10.3|10.4|10.5)$ ]]; then
                INVALID_OPTIONS+=("${OPTARG}")
            fi
            ;;
        j)
            MYSQL_VERSION=${OPTARG}
            if ! [[ ${MYSQL_VERSION} =~ ^(5.5|5.6|5.7|8.0)$ ]]; then
                INVALID_OPTIONS+=("${OPTARG}")
            fi
            ;;
        k)
            POSTGRES_VERSION=${OPTARG}
            if ! [[ ${POSTGRES_VERSION} =~ ^(9.6|10|11|12|13)$ ]]; then
                INVALID_OPTIONS+=("${OPTARG}")
            fi
            ;;
         t)
            TYPO3_VERSION=${OPTARG}
            if ! [[ ${TYPO3_VERSION} =~ ^(12|13|14)$ ]]; then
                INVALID_OPTIONS+=("-t ${OPTARG}")
            fi
            ;;
        p)
            PHP_VERSION=${OPTARG}
            if ! [[ ${PHP_VERSION} =~ ^(${SUPPORTED_PHP_VERSIONS})$ ]]; then
                INVALID_OPTIONS+=("${OPTARG}")
            fi
            ;;
        e)
            EXTRA_TEST_OPTIONS=${OPTARG}
            ;;
        x)
            PHP_XDEBUG_ON=1
            ;;
        y)
            PHP_XDEBUG_PORT=${OPTARG}
            ;;
        o)
            PHPUNIT_RANDOM="--random-order-seed=${OPTARG}"
            ;;
        n)
            CGLCHECK_DRY_RUN="-n"
            ;;
        h)
            echo "${HELP}"
            exit 0
            ;;
        u)
            TEST_SUITE=update
            ;;
        v)
            SCRIPT_VERBOSE=1
            ;;
        \?)
            INVALID_OPTIONS+=("${OPTARG}")
            ;;
        :)
            INVALID_OPTIONS+=("${OPTARG}")
            ;;
    esac
done

# Exit on invalid options
if [ ${#INVALID_OPTIONS[@]} -ne 0 ]; then
    echo "Invalid option(s):" >&2
    for I in "${INVALID_OPTIONS[@]}"; do
        echo "-"${I} >&2
    done
    echo >&2
    echo "call \".Build/Scripts/runTests.sh -h\" to display help and valid options"
    exit 1
fi


# Docker PHP images
# @see https://github.com/orgs/TYPO3/packages
# z.B. DOCKER_PHP_IMAGE="php82"
DOCKER_PHP_IMAGE=$(echo "php${PHP_VERSION}" | sed -e 's/\.//')
# z.B. DOCKER_PHP_IMAGE_NAME = ghcr.io/typo3/core-testing-php82:1.13
#DOCKER_PHP_IMAGE_NAME=ghcr.io/typo3/core-testing-${DOCKER_PHP_IMAGE}:$(getPhpImageVersion $PHP_VERSION)
DOCKER_PHP_IMAGE_NAME=ghcr.io/typo3/core-testing-${DOCKER_PHP_IMAGE}:latest
echo "DOCKER_PHP_IMAGE_NAME: $DOCKER_PHP_IMAGE_NAME"

# Set $1 to first mass argument, this is the optional test file or test directory to execute
shift $((OPTIND - 1))
TEST_FILE=${1}

if [ ${SCRIPT_VERBOSE} -eq 1 ]; then
    set -x
fi

# Suite execution
case ${TEST_SUITE} in
    acceptance)
        handleDbmsAndDriverOptions
        setUpDockerComposeDotEnv
        if [ "${CHUNKS}" -gt 1 ]; then
            docker_run acceptance_split
        fi
        case ${DBMS} in
            mysql)
                echo "Using driver: ${DATABASE_DRIVER}"
                docker_run prepare_acceptance_backend_mysql
                docker_run acceptance_backend_mysql
                SUITE_EXIT_CODE=$?
                ;;
            mariadb)
                echo "Using driver: ${DATABASE_DRIVER}"
                docker_run prepare_acceptance_backend_mariadb
                docker_run acceptance_backend_mariadb
                SUITE_EXIT_CODE=$?
                ;;
            postgres)
                docker_run prepare_acceptance_backend_postgres
                docker_run acceptance_backend_postgres
                SUITE_EXIT_CODE=$?
                ;;
            *)
                echo "Acceptance tests don't run with DBMS ${DBMS}" >&2
                echo >&2
                echo "call \".Build/Scripts/runTests.sh -h\" to display help and valid options" >&2
                exit 1
        esac
        docker_down
        ;;
    buildCss)
        setUpDockerComposeDotEnv
        docker_run build_css
        SUITE_EXIT_CODE=$?
        docker_down
        ;;
    buildJavascript)
        setUpDockerComposeDotEnv
        docker_run build_javascript
        SUITE_EXIT_CODE=$?
        docker_down
        ;;
    cglGit)
        setUpDockerComposeDotEnv
        docker_run cgl_git
        SUITE_EXIT_CODE=$?
        docker_down
        ;;
    cglAll)
        # Active dry-run for cglAll needs not "-n" but specific options
        if [[ ! -z ${CGLCHECK_DRY_RUN} ]]; then
            CGLCHECK_DRY_RUN="--dry-run --diff"
        fi
        setUpDockerComposeDotEnv
        docker_run cgl_all
        SUITE_EXIT_CODE=$?
        docker_down
        ;;
    checkAnnotations)
        setUpDockerComposeDotEnv
        docker_run check_annotations
        SUITE_EXIT_CODE=$?
        docker_down
        ;;
    checkBom)
        setUpDockerComposeDotEnv
        docker_run check_bom
        SUITE_EXIT_CODE=$?
        docker_down
        ;;
    checkComposer)
        setUpDockerComposeDotEnv
        docker_run check_composer
        SUITE_EXIT_CODE=$?
        docker_down
        ;;
    checkCsvFixtures)
        setUpDockerComposeDotEnv
        docker_run check_csv_fixtures
        SUITE_EXIT_CODE=$?
        docker_down
        ;;
    checkExceptionCodes)
        setUpDockerComposeDotEnv
        docker_run check_exception_codes
        SUITE_EXIT_CODE=$?
        docker_down
        ;;
    checkExtensionScannerRst)
        setUpDockerComposeDotEnv
        docker_run check_extension_scanner_rst
        SUITE_EXIT_CODE=$?
        docker_down
        ;;
    checkFilePathLength)
        setUpDockerComposeDotEnv
        docker_run check_file_path_length
        SUITE_EXIT_CODE=$?
        docker_down
        ;;
    checkGitSubmodule)
        setUpDockerComposeDotEnv
        docker_run check_git_submodule
        SUITE_EXIT_CODE=$?
        docker_down
        ;;
    checkGruntClean)
        setUpDockerComposeDotEnv
        docker_run check_grunt_clean
        SUITE_EXIT_CODE=$?
        docker_down
        ;;
    checkPermissions)
        setUpDockerComposeDotEnv
        docker_run check_permissions
        SUITE_EXIT_CODE=$?
        docker_down
        ;;
    checkRst)
        setUpDockerComposeDotEnv
        docker_run check_rst
        SUITE_EXIT_CODE=$?
        docker_down
        ;;
    composerInstall)
        setUpDockerComposeDotEnv
        docker_run composer_install
        SUITE_EXIT_CODE=$?
        docker_down
        ;;

    composerRequire)
            setUpDockerComposeDotEnv
            docker_run composer_require
            SUITE_EXIT_CODE=$?
            docker_down
            ;;

    composerInstallMax)
        setUpDockerComposeDotEnv
        docker_run composer_install_max
        SUITE_EXIT_CODE=$?
        docker_down
        ;;
    composerInstallMin)
        setUpDockerComposeDotEnv
        docker_run composer_install_min
        SUITE_EXIT_CODE=$?
        docker_down
        ;;
    composerValidate)
        setUpDockerComposeDotEnv
        docker_run composer_validate
        SUITE_EXIT_CODE=$?
        docker_down
        ;;
    docBlockCheck)
        setUpDockerComposeDotEnv
        docker_run doc_block_check
        SUITE_EXIT_CODE=$?
        docker_down
        ;;
    fixCsvFixtures)
        setUpDockerComposeDotEnv
        docker_run fix_csv_fixtures
        SUITE_EXIT_CODE=$?
        docker_down
        ;;
    functional)
        handleDbmsAndDriverOptions
        setUpDockerComposeDotEnv
        if [ "${CHUNKS}" -gt 1 ]; then
            docker_run functional_split
        fi
        case ${DBMS} in
            mariadb)
                echo "Using driver: ${DATABASE_DRIVER}"
                docker_run prepare_functional_mariadb
                docker_run functional_mariadb
                SUITE_EXIT_CODE=$?
                ;;
            mysql)
                echo "Using driver: ${DATABASE_DRIVER}"
                docker_run prepare_functional_mysql
                docker_run functional_mysql
                SUITE_EXIT_CODE=$?
                ;;
            mssql)
                echo "Using driver: ${DATABASE_DRIVER}"
                docker_run prepare_functional_mssql2019latest
                docker_run functional_mssql2019latest
                SUITE_EXIT_CODE=$?
                ;;
            postgres)
                docker_run prepare_functional_postgres
                docker_run functional_postgres
                SUITE_EXIT_CODE=$?
                ;;
            sqlite)
                # sqlite has a tmpfs as typo3temp/var/tests/functional-sqlite-dbs/
                # Since docker is executed as root (yay!), the path to this dir is owned by
                # root if docker creates it. Thank you, docker. We create the path beforehand
                # to avoid permission issues on host filesystem after execution.
                mkdir -p "${CORE_ROOT}/typo3temp/var/tests/functional-sqlite-dbs/"
                docker_run prepare_functional_sqlite
                docker_run functional_sqlite
                SUITE_EXIT_CODE=$?
                ;;
            *)
                echo "Functional tests don't run with DBMS ${DBMS}" >&2
                echo >&2
                echo "call \".Build/Scripts/runTests.sh -h\" to display help and valid options" >&2
                exit 1
        esac
        docker_down
        ;;
    install)
        handleDbmsAndDriverOptions
        setUpDockerComposeDotEnv
        case ${DBMS} in
            mysql)
                echo "Using driver: ${DATABASE_DRIVER}"
                docker_run prepare_acceptance_install_mysql
                docker_run acceptance_install_mysql
                SUITE_EXIT_CODE=$?
                ;;
            mariadb)
                echo "Using driver: ${DATABASE_DRIVER}"
                docker_run prepare_acceptance_install_mariadb
                docker_run acceptance_install_mariadb
                SUITE_EXIT_CODE=$?
                ;;
            postgres)
                docker_run prepare_acceptance_install_postgres
                docker_run acceptance_install_postgres
                SUITE_EXIT_CODE=$?
                ;;
            sqlite)
                docker_run prepare_acceptance_install_sqlite
                docker_run acceptance_install_sqlite
                SUITE_EXIT_CODE=$?
                ;;
            *)
                echo "Install tests don't run with DBMS ${DBMS}" >&2
                echo >&2
                echo "call \".Build/Scripts/runTests.sh -h\" to display help and valid options" >&2
                exit 1
        esac
        docker_down
        ;;
    lint)
        setUpDockerComposeDotEnv
        docker_run lint_php
        SUITE_EXIT_CODE=$?
        docker_down
        ;;
    lintScss)
        setUpDockerComposeDotEnv
        docker_run lint_scss
        SUITE_EXIT_CODE=$?
        docker_down
        ;;
    lintTypescript)
        setUpDockerComposeDotEnv
        docker_run lint_typescript
        SUITE_EXIT_CODE=$?
        docker_down
        ;;
    lintHtml)
        setUpDockerComposeDotEnv
        docker_run lint_html
        SUITE_EXIT_CODE=$?
        docker_down
        ;;
    listExceptionCodes)
        setUpDockerComposeDotEnv
        docker_run list_exception_codes
        SUITE_EXIT_CODE=$?
        docker_down
        ;;
    phpstan)
        setUpDockerComposeDotEnv
        docker_run phpstan
        SUITE_EXIT_CODE=$?
        docker_down
        ;;
    unit)
        setUpDockerComposeDotEnv
        docker_run unit
        SUITE_EXIT_CODE=$?
        docker_down
        ;;
    unitDeprecated)
        setUpDockerComposeDotEnv
        docker_run unitDeprecated
        SUITE_EXIT_CODE=$?
        docker_down
        ;;
    unitJavascript)
        setUpDockerComposeDotEnv
        docker_run unitJavascript
        SUITE_EXIT_CODE=$?
        docker_down
        ;;
    unitRandom)
        setUpDockerComposeDotEnv
        docker_run unitRandom
        SUITE_EXIT_CODE=$?
        docker_down
        ;;
    update)
        # pull typo3/core-testing-*:latest versions of those ones that exist locally
        docker images typo3/core-testing-*:latest --format "{{.Repository}}:latest" | xargs -I {} docker pull {}
        # remove "dangling" typo3/core-testing-* images (those tagged as <none>)
        docker images typo3/core-testing-* --filter "dangling=true" --format "{{.ID}}" | xargs -I {} docker rmi {}
        ;;
    *)
        echo "Invalid -s option argument ${TEST_SUITE}" >&2
        echo >&2
        echo "${HELP}" >&2
        exit 1
esac

case ${DBMS} in
    mariadb)
        DBMS_OUTPUT="DBMS: ${DBMS}  version ${MARIADB_VERSION}  driver ${DATABASE_DRIVER}"
        ;;
    mysql)
        DBMS_OUTPUT="DBMS: ${DBMS}  version ${MYSQL_VERSION}  driver ${DATABASE_DRIVER}"
        ;;
    mssql)
        DBMS_OUTPUT="DBMS: ${DBMS}  driver ${DATABASE_DRIVER}"
        ;;
    postgres)
        DBMS_OUTPUT="DBMS: ${DBMS}  version ${POSTGRES_VERSION}"
        ;;
    sqlite)
        DBMS_OUTPUT="DBMS: ${DBMS}"
        ;;
    *)
        DBMS_OUTPUT="DBMS not recognized: $DBMS"
        exit 1
esac

# Print summary
if [ ${SCRIPT_VERBOSE} -eq 1 ]; then
    # Turn off verbose mode for the script summary
    set +x
fi
echo "" >&2
echo "###########################################################################" >&2
if [[ ${TEST_SUITE} =~ ^(functional|install|acceptance)$ ]]; then
    echo "Result of ${TEST_SUITE}" >&2
    echo "PHP: ${PHP_VERSION}" >&2
    echo "${DBMS_OUTPUT}" >&2
else
    echo "Result of ${TEST_SUITE}" >&2
    echo "PHP: ${PHP_VERSION}" >&2
fi

if [[ ${SUITE_EXIT_CODE} -eq 0 ]]; then
    echo "SUCCESS" >&2
else
    echo "FAILURE" >&2
fi
echo "###########################################################################" >&2
echo "" >&2

# Exit with code of test suite - This script return non-zero if the executed test failed.
exit $SUITE_EXIT_CODE
