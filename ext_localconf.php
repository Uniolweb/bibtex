<?php
if (!defined('TYPO3_MODE')) {
	die ('Access denied.');
}

Tx_Extbase_Utility_Extension::configurePlugin(
	$_EXTKEY,
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