<?php

defined('TYPO3_MODE') or die();


$extkey = 'bibtex';
$extensionName = strtolower(\TYPO3\CMS\Core\Utility\GeneralUtility::underscoredToUpperCamelCase($extkey));
$pluginSignature = $extensionName . '_bibtex';


// add flexform
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist'][$pluginSignature] = 'pi_flexform';
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue($pluginSignature, 'FILE:EXT:' . $extkey . '/Configuration/FlexForms/flexform_btex.xml');

// do not show "Record storage page" configuration for plugin in form
// https://stackoverflow.com/questions/39386018/typo3-hide-plugin-mode-and-record-storage-page-in-a-plugin/39387488
// pages: storage pid
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist'][$pluginSignature]
    = 'recursive,select_key,pages';