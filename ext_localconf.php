<?php
if (!defined('TYPO3_MODE')) {
    die ('Access denied.');
}

(function () {
    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
        'Bibtex',
        'Bibtex',
        [
            'Btex' => 'show',

        ]
    );

    // Page TSconfig
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig("@import 'EXT:bibtex/Configuration/TSconfig/Page/Wizards/NewContentElement.tsconfig'");


    // icons
    $iconRegistry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
        \TYPO3\CMS\Core\Imaging\IconRegistry::class
    );
    $iconRegistry->registerIcon(
        'bibtex-plugin', // Icon-Identifier
        \TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider::class,
        ['source' => 'EXT:bibtex/Resources/Public/Assets/Icons/BibTeX_logo.svg']
    );


    // caching framework

    if (!is_array($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['bibtex_bibtexcache'])) {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['bibtex_bibtexcache'] = [];
    }

})();
