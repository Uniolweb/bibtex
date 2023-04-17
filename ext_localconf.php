<?php
declare(strict_types=1);
use TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider;
use TYPO3\CMS\Core\Imaging\IconRegistry;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;
use Uniolit\Bibtex\Controller\BtexController;
use Uniolit\Bibtex\Hooks\PageLayoutView;

defined('TYPO3') or die();


ExtensionUtility::configurePlugin(
    'Bibtex',
    'Bibtex',
    [
        BtexController::class => 'show',

    ]
);

// Page TSconfig
ExtensionManagementUtility::addPageTSConfig("@import 'EXT:bibtex/Configuration/TSconfig/Page/Wizards/NewContentElement.tsconfig'");

// caching framework
$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['bibtex_bibtexcache'] ??= [];

/*
 * ----
 * hooks
 * ----
 */
// Page module hook
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/class.tx_cms_layout.php']['list_type_Info']['bibtex_bibtex']['bibtex'] =
    PageLayoutView::class . '->getExtensionSummary';


