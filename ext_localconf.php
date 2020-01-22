<?php
if (!defined('TYPO3_MODE')) {
    die ('Access denied.');
}

$extkey = "bibtex";
$vendorname = 'Uniolit';
$extname = "$vendorname.$extkey";
$extensionName = strtolower(\TYPO3\CMS\Core\Utility\GeneralUtility::underscoredToUpperCamelCase($extkey));


\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
    $extname,
    'Bibtex',
    [
        'Btex' => 'show',

    ],
    // non-cacheable actions
    [
//        'Btex' => 'show'
    ]
);

// caching framework

if (!is_array($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['bibtex_bibtexcache'])) {
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['bibtex_bibtexcache'] = [];
}
