<?php
if (!defined('TYPO3_MODE')) {
	die ('Access denied.');
}

$extkey = 'bibtex';

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
	$extkey,
	'Bibtex',
	'Bibtex'
);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile($extkey, 'Configuration/TypoScript', 'Bibtex Extension (New)');
