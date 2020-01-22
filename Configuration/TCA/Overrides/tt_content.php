<?php

defined('TYPO3_MODE') or die();


$extkey = 'bibtex';
$extensionName = strtolower(\TYPO3\CMS\Core\Utility\GeneralUtility::underscoredToUpperCamelCase($extkey));
$pluginSignature = $extensionName . '_bibtex';

$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist'][$pluginSignature] = 'pi_flexform';
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue($pluginSignature, 'FILE:EXT:' . $extkey . '/Configuration/FlexForms/flexform_btex.xml');
