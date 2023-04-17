<?php
defined('TYPO3') or die();

$extkey = 'bibtex';
$extensionName = 'Bibtex';
$pluginSignature = $extkey . '_bibtex';

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
    // extension_key or ExtensionName
    $extkey,
    // plugin name
    'Bibtex',
    // label
    'Bibtex',
    // icon
    'bibtex-plugin'
);

// add flexform
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist'][$pluginSignature] = 'pi_flexform';
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue($pluginSignature, 'FILE:EXT:' . $extkey . '/Configuration/FlexForms/flexform_btex.xml');

// do not show "Record storage page" configuration for plugin in form
// https://stackoverflow.com/questions/39386018/typo3-hide-plugin-mode-and-record-storage-page-in-a-plugin/39387488
// pages: storage pid
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist'][$pluginSignature]
    = 'recursive,select_key,pages';
