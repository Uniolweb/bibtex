<?php
if (!defined('TYPO3_MODE')) {
	die ('Access denied.');
}

Tx_Extbase_Utility_Extension::registerPlugin(
	$_EXTKEY,
	'Bibtex',
	'Bibtex'
);

$extensionName = strtolower(t3lib_div::underscoredToUpperCamelCase($_EXTKEY));

$pluginSignature = $extensionName . '_bibtex';
$TCA['tt_content']['types']['list']['subtypes_addlist'][$pluginSignature] = 'pi_flexform';
t3lib_extMgm::addPiFlexFormValue($pluginSignature, 'FILE:EXT:' . $_EXTKEY . '/Configuration/FlexForms/flexform_btex.xml');

t3lib_extMgm::addStaticFile($_EXTKEY, 'Configuration/TypoScript', 'BibtexExtension');


?>