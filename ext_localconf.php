<?php
declare(strict_types=1);

use TYPO3\CMS\Extbase\Utility\ExtensionUtility;
use Uniolit\Bibtex\Controller\BtexController;

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


// -----------------
// caching framework
// -----------------
$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['bibtex_bibtexcache'] ??= [];

