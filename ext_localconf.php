<?php
if (!defined('TYPO3_MODE')) {
	die ('Access denied.');
}

$extkey = "btex";
$vendorname = 'Uniol';
$extname = "$vendorname . $extkey";
$extensionName = strtolower(\TYPO3\CMS\Core\Utility\GeneralUtility::underscoredToUpperCamelCase($extkey));


\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
    $extname,
	'Bibtex',
	array(
		'Btex' => 'show',
		
	),
	// non-cacheable actions
	array(
		'Btex' => 'show',
		
	)
);


?>