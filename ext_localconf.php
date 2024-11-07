<?php
declare(strict_types=1);

use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;
use Uniolit\Bibtex\Controller\BtexController;
use Uniolit\Bibtex\Hooks\PageLayoutView;

defined('TYPO3') or die();

// -------
// plugins
// -------

ExtensionUtility::configurePlugin(
    'Bibtex',
    'Bibtex',
    [
        BtexController::class => 'show',

    ]
);

// -------------
// Page TSconfig
// -------------

// todo: this can be removed when support for v11 is dropped
// BEGIN: remove when v11 support ist dropped
$versionInformation = GeneralUtility::makeInstance(Typo3Version::class);
// Only include page.tsconfig if TYPO3 version is below 12 so that it is not imported twice.
if ($versionInformation->getMajorVersion() < 12) {
    ExtensionManagementUtility::addPageTSConfig(
        '@import "EXT:bibtex/Configuration/page.tsconfig"'
    );
}
// END: remove when v11 support ist dropped


// -----------------
// caching framework
// -----------------
$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['bibtex_bibtexcache'] ??= [];

require 'phar://' . \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('bibtex') . 'Resources/Private/libraries.phar/vendor/autoload.php';
