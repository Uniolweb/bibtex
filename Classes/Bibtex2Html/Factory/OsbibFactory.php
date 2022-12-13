<?php

declare(strict_types=1);
namespace Uniolit\Bibtex\Bibtex2Html\Factory;

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

class OsbibFactory
{
    public function instantiateParseEntries()
    {
        if (class_exists('PARSEENTRIES')) {
            return new \PARSEENTRIES();
        }
    }

    public function instantiateBibFormat()
    {
        if (class_exists('BIBFORMAT')) {
            $OSBiBPath = ExtensionManagementUtility::extPath('bibtex', 'Resources/Private/Php/bib2html/OSBiB/');
            // TRUE implies that the input data is in bibtex format
            return new \BIBFORMAT($OSBiBPath, true);
        }
    }
}
