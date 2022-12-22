<?php

declare(strict_types=1);
namespace Uniolit\Bibtex\Bibtex2Html\Factory;

use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class OsbibFactory
{
    /** @var mixed|string  */
    protected $osbibPath = 'bibtex:PHP/bib2html/OSBiB/';

    /** @var string  */
    protected $absoluteOsbibPath = '';

    public function __construct(string $osbibPath = '')
    {
        if (!$osbibPath) {
            $osbibPath = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('bibtex', 'osbibPath');
        }
        if ($osbibPath) {
            $this->osbibPath = $osbibPath;
        }
        $this->absoluteOsbibPath = $this->getAbsolutePathForExtensionPath(...explode(':', $this->osbibPath));
    }

    /**
     * Autoload classes for non-Composer mode
     *
     * @todo move to OsbibFactory
     * @todo check if composer mode
     */
    public function autoloadClasses(): void
    {
        if (!class_exists('PARSEENTRIES')
        ) {
            include_once($this->absoluteOsbibPath . '/format/bibtexParse/PARSEENTRIES.php');
        }
        if (!class_exists('BIBFORMAT')
        ) {
            include_once($this->absoluteOsbibPath . '/format/BIBFORMAT.php');
        }
    }

    public function instantiateParseEntries()
    {
        if (class_exists('PARSEENTRIES')) {
            return new \PARSEENTRIES();
        }
    }

    public function instantiateBibFormat()
    {
        if (class_exists('BIBFORMAT')) {
            return new \BIBFORMAT($this->absoluteOsbibPath, true);
        }
    }

    public function getAbsolutePathForExtensionPath(string $extkey, string $path): string
    {
        return ExtensionManagementUtility::extPath($extkey, $path);
    }
}
