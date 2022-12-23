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
        // try to load ParseEntries or PARSEENTRIES
        if (!class_exists('ParseEntries')
            && !class_exists('PARSEENTRIES')
        ) {
            $path = $this->absoluteOsbibPath . '/format/bibtexParse/';
            if (file_exists($path . 'ParseEntries.php')) {
                include_once($path . 'ParseEntries.php');
            } elseif (file_exists($path . 'PARSEENTRIES.php')) {
                include_once($path . 'PARSEENTRIES.php');
            } else {
                throw new \RuntimeException('class ParseEntries or PARSEENTRIES does not exist in ' . $path);
            }
        }

        if (!class_exists('BibFormat')
            && (!class_exists('BIBFORMAT'))
        ) {
            $path = $this->absoluteOsbibPath . '/format/';
            if (file_exists($path . 'BibFormat.php')) {
                include_once($path . 'BibFormat.php');
            } elseif (file_exists($path . 'BIBFORMAT.php')) {
                include_once($path . 'BIBFORMAT.php');
            } else {
                throw new \RuntimeException('class BibFormat or BIBFORMAT does not exist in ' . $path);
            }
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
        if (class_exists('PARSEENTRIES')) {
            return new \PARSEENTRIES($expandMacro, $fieldExtract, $removeDelimit);
        }
        throw new \RuntimeException('class ParseEntries or PARSEENTRIES does not exist');
    }

    public function instantiateBibFormat()
    {
        if (class_exists('BibFormat')) {
            return new \BibFormat($this->absoluteOsbibPath, true);
        }
        if (class_exists('BIBFORMAT')) {
            return new \BIBFORMAT($this->absoluteOsbibPath, true);
        }
        throw new \RuntimeException('class BibFormat or BIBFORMAT does not exist');
    }

    public function getAbsolutePathForExtensionPath(string $extkey, string $path): string
    {
        return ExtensionManagementUtility::extPath($extkey, $path);
    }
}
