<?php

declare(strict_types=1);
namespace Uniolit\Bibtex\Bibtex2Html\Factory;

use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Uniolit\Bibtex\Bibtex2Html\Service\Bibtex2HtmlService;

class OsbibFactory
{
    /** @var string  */
    protected $osbibPath = '';

    /** @var string  */
    protected $absoluteOsbibPath = '';

    public function __construct(string $osbibPath = '')
    {
        if (!$osbibPath) {
            $osbibPath = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('bibtex', 'osbibPath')
                ?: Bibtex2HtmlService::DEFAULT_OSBIBPATH;
        }
        $this->osbibPath = $osbibPath;
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
        if (!class_exists('ParseEntries')
        ) {
            include_once($this->absoluteOsbibPath . '/format/bibtexParse/ParseEntries.php');
        }
        if (!class_exists('BibFormat')
        ) {
            include_once($this->absoluteOsbibPath . '/format/BibFormat.php');
        }
    }

    public function instantiateParseEntries()
    {
        $expandMacro = true;
        $fieldExtract = true;
        $removeDelimit = true;

        if (class_exists('ParseEntries')) {
            return new \ParseEntries($expandMacro, $fieldExtract, $removeDelimit);
        }
    }

    public function instantiateBibFormat()
    {
        if (class_exists('BibFormat')) {
            return new \BibFormat($this->absoluteOsbibPath, true);
        }
    }

    public function getAbsolutePathForExtensionPath(string $extkey, string $path): string
    {
        return ExtensionManagementUtility::extPath($extkey, $path);
    }
}
