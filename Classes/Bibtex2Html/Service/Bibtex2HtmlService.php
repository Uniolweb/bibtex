<?php

declare(strict_types=1);
namespace Uniolit\Bibtex\Bibtex2Html\Service;

/**
 * Credit:
 * Loosely based on Wordpress plugin bib2html:
 * --------------------------------------------
 * Plugin URI: http://sergioandreozzi.com/wordpress/bib2html
 * Description: bib2html enables to add bibtex entries formatted as HTML in wordpress pages and posts. The input data is the bibtex text file and the output is HTML.
 * Version: 0.9.3
 * Author: Sergio Andreozzi
 * Author URI: http://sergioandreozzi.com
 * --------------------------------------------
 * with additional changes by
 * - Sybille Peters, Carl von Ossietzky Universität Oldenburg
 * - Volker Burggräf, Carl von Ossietzky Universität Oldenburg
 * - Philip Rinn for AG TWiSt, 15. Mai 2014
 * - Cristiana Bolchini
 *   - cleaner bibtex presentation
 * - Patrick Mauï¿½
 *   - remote bibliographies managed by citeulike.org or bibsonomy.org
 * - Nemo
 *   - more characters on key
 * - Marco Loregian
 *   - inverting bibtex and html
 */

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Uniolit\Bibtex\Bibtex2Html\Factory\OsbibFactory;
use Uniolit\Bibtex\Configuration\BibtexSettings;

/**
 * Convert bibtext to HTML
 */
class Bibtex2HtmlService implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var RequestFactory|null
     */
    protected ?RequestFactory $requestFactory = null;

    /**
     * @var OsbibFactory|null
     */
    protected ?OsbibFactory $osbibFactory = null;

    protected string $osbibPath = '';

    protected string $bib2HtmlPath = '';

    public function __construct(RequestFactory $requestFactory = null, OsbibFactory $osbibFactory = null)
    {
        $this->osbibPath = ExtensionManagementUtility::extPath('bibtex', 'Resources/Private/Php/bib2html/OSBiB/');
        $this->bib2HtmlPath = ExtensionManagementUtility::extPath('bibtex', 'Resources/Private/Php/bib2html/');
        $this->requestFactory = $requestFactory ?: GeneralUtility::makeInstance(RequestFactory::class);
        $this->osbibFactory = $osbibFactory ?: GeneralUtility::makeInstance(OsbibFactory::class);
    }

    /**
     * Autoload classes for non-Composer mode
     *
     *
     * @todo check if composer mode
     */
    public function autoloadClasses(): void
    {
        // OSBib

        if (!class_exists('PARSEENTRIES')
            && !class_exists('\Hasantayyar\Osbib\Format\BibtextParse\PARSEENTRIES')
        ) {
            include_once($this->osbibPath . 'format/bibtexParse/PARSEENTRIES.php');
        }
        if (!class_exists('BIBFORMAT')
            && !class_exists('\Hasantayyar\Osbib\Format\BIBFORMAT')
        ) {
            include_once($this->osbibPath . 'format/BIBFORMAT.php');
        }
    }

    /**
     * Prepare bibtex entries
     *
     * @param BibtexSettings $bibtexSettings
     * @param int $languageId
     * @return array
     * @todo Make this more modular and separate
     * - converting bibtex content into normalized bibtex entries
     * - converting this into HTML
     */
    public function bibtex2Html(BibtexSettings $bibtexSettings, int $languageId = 0): array
    {
        $this->autoloadClasses();

        // @todo: use configurable language mapping, e.g. via TypoScript
        $languageKey = $languageId === 0 ? '' : '_en';

        $entries = $this->bib2html($bibtexSettings, $languageKey);

        /*
        if ($convertStyle) {
            $content = $this->convertStyle($content);
        }
        */
        return $entries;
    }

    protected function bib2html(BibtexSettings $bibtexSettings, string $lang=''): array
    {
        $bibFile = $bibtexSettings->getUrl();
        $sort = $bibtexSettings->getSort();
        $sortFixed = $bibtexSettings->isSortFixed();
        $filterType = $bibtexSettings->getFilterType();
        $filterItems = [];
        if ($filterType === 'allow') {
            $filterItems = $bibtexSettings->getAllow();
        } elseif ($filterType === 'deny') {
            $filterItems = $bibtexSettings->getDeny();
        }

        if (!$bibFile) {
            return [];
        }
        $content = $this->fetchContent($bibFile);
        if (!$content) {
            return [];
        }
        // if bibtex file identified and opened, then convert to html
        $newEntries = $this->bib2htmlPreProcess(
            $content,
            $filterType,
            implode(',', $filterItems),
            $sort,
            $lang,
            $sortFixed,
            $bibtexSettings->getTemplate()
        );

        return $newEntries;
    }

    protected function fetchContent(string $url): string
    {
        try {
            $response = $this->requestFactory->request($url);
            return $response->getBody()->getContents();
        } catch (\Exception $e) {
            $message = 'Bibtex file does not exist or is empty:' . $url;
            // @extensionScannerIgnoreLine
            $this->logger->error($message);
            return '';
        }
    }

    /**
     * @param string $content
     * @return string
     *
     * @todo Replace this, change directly in the template file
     */
    protected function convertStyle(string $content): string
    {
        $content = str_replace('<ul>', '<ul class="geweitet">', $content);
        $content = str_replace('class="toggle"', 'class="bibtextoggle"', $content);
        return $content;
    }

    /**
     * Preprocess elements, do not convert to HTML but return as array to be passed
     * to Fluid
     *
     * @param string $data
     * @param string $filterType
     * @param string $filter
     * @param string $sort
     * @param string $lang
     * @param bool $sortFixed
     * @param string $template
     * @param string $style
     * @return array
     */
    protected function bib2htmlPreProcess(
        string $data,
        string $filterType = '',
        string $filter = '',
        string $sort = '',
        string $lang = '',
        bool $sortFixed = false,
        string $template = '',
        string $style = 'uniol'
    ): array {
        // parse the content of bib string and generate associative array with valid entries
        $parse = $this->osbibFactory->instantiateParseEntries();
        $parse->expandMacro = true;
        $parse->fieldExtract = true;
        $parse->removeDelimit = true;
        $parse->loadBibtexString($data);
        $parse->extractEntries();

        /** @var array<int,array<string,string>> $entries */
        list($preamble, $strings, $entries) = $parse->returnArrays();

        // Format the entries array  for html output

        $bibformat = $this->osbibFactory->instantiateBibFormat();
        $bibformat->cleanEntry = true; // convert BibTeX (and LaTeX) special characters to UTF-8
        list($info, $citation, $styleCommon, $styleTypes) = $bibformat->loadStyle(
            $this->osbibPath . 'styles/bibliography/',
            $style . $lang
        );
        $bibformat->getStyle($styleCommon, $styleTypes);

        //// Added by C.v.O Uni Oldenburg
        if ($sort) {
            $sortiere = [
                $sort => []
            ];
            foreach ($entries as $key => $entry) {
                $sortiere[$sort][$key] = $entry[$sort] ?? '';
            }
            array_multisort($sortiere[$sort], ($sort == 'year' ? SORT_DESC : SORT_ASC), $entries);
        }
        //// End added by C.v.O Uni Oldenburg
        /** @var array<int,array<string,string>> $newEntries */
        $newEntries = [];

        /**
         * @var array<string,string> $entry
         * @todo use class
         */
        foreach ($entries as $entry) {
            // Get the resource type ('book', 'article', 'inbook' etc.)
            $resourceType = $entry['bibtexEntryType'];

            //  adds all the resource elements automatically to the BIBFORMAT::item array
            $bibformat->preProcess($resourceType, $entry);

            // apply filters
            $pos = strpos($filter, $resourceType);
            $bibkey = $entry['bibtexCitation'] ?? '';

            if (((strcmp($filterType, 'allow') === 0) && ($pos === false)) or
                ((strcmp($filterType, 'deny') === 0) && ($pos !== false)) or
                ((strcmp($filterType, 'key') === 0) && (strcmp($filter, $bibkey) != 0))) {
                continue;
            }

            // get the formatted resource string ready for printing to the web browser
            // the str_replace is used to remove the { } parentheses possibly present in title
            // to enforce uppercase, TODO: check if it can be done only on title
            $mapped_entry = $bibformat->map();
            $mapped_entry = str_replace(['{', '}'], '', $mapped_entry);

            /**
             * SP 2021-07-08 Fix trailing comma in title
             * e.g.  "Climate Policies after Paris: Pledge, Trade and Recyle,"
             * should be:  "Climate Policies after Paris: Pledge, Trade and Recyle"
             */
            $mapped_entry = str_replace([',&quot' ], '&quot', $mapped_entry);
            $newEntries[] = [
                'year' => $entry['year'],
                'type' =>  $entry['bibtexEntryType'],
                'pdf' => $this->toDownload($entry, $lang),
                'key' => \strtr($bibkey, ':', '-'),
                'entry' => $mapped_entry,
                'bibtex' => $this->formatBibtex((string)($entry['bibtexEntry'] ?? '')),
                // These are rather AG TWiSt specific formats but probably of more general interest
                'twist_title' => $this->toTwistTitle($mapped_entry, $resourceType, $entry['doi'] ?? ''),
                'twist_entry' => $this->toTWiStEntry($mapped_entry, $resourceType),
                'twist_pdf' => $this->toTWiStPDF($entry)
            ];
        }
        return $newEntries;
    }

    /**
     * @param $entry Current entry
     * @param $lang language string, e.g. 'en', 'de' (default is 'en')
     * @return string
     */
    protected function toDownload($entry, $lang): string
    {
        if (array_key_exists('url', $entry)) {
            if ($lang === 'de' || $lang === '') {
                $text = 'Dokument aufrufen';
            } else {
                $text = 'Go to document';
            }
            /**
             * @var string $string
             * @todo make image path configurable
             */
            $string = " | <a href='" . str_replace(
                '\\',
                '',
                $entry['url']
            ) . "' title='" . $text . "'><img src='/typo3conf/ext/bibtex/Resources/Public/Assets/Icons/bibtex_external.png' width='10' height='10' alt='" . $text . "' /> " . $text . '</a>';
            return $string;
        }
        return '';
    }

    /**
     * Construct a string with the title linked to the url field if existent
     *
     * @param string $entry
     * @param string $type
     * @param string $doi
     * @return string
     *
     * Credit: Added by Philip Rinn for AG TWiSt, 15. Mai 2014
     */
    protected function toTWiStTitle(string $entry, string $type, string $doi): string
    {
        // Extract the title from the whole entry sting
        if ($type == 'book') {
            $title = preg_replace("/.*<em>(.*)<\/em>.*/", '\1', $entry);
        } else {
            $title = preg_replace('/.*&quot;(.*),&quot;.*/', '\1', $entry);
        }
        if ($doi) {
            //check if doi starts with 'http(s):' and don't do anything if so
            if (!preg_match('/^https?:/i', $doi)) {
                // Remove eventually existing 'doi:'
                $doi = trim(preg_replace('/^doi:/i', '', $doi));
                // Prefix with resolver address
                $doi = 'http://dx.doi.org/' . $doi;
            }
            $title = "<a href='" . $doi . "'>" . $title . '</a>';
        }
        return $title;
    }

    /**
     * Remove the title and add a linebreak instead
     * @param $entry
     * @param string $type
     * @return array|string|string[]|void|null
     *
     * Credit: Added by Philip Rinn for AG TWiSt, 15. Mai 2014
     *
     * @todo add type hinting
     */
    protected function toTWiStEntry($entry, string $type)
    {
        if ($type == 'book') {
            $rest = preg_replace("/, <em>.*<\/em>,/", '<br>', $entry);
        } else {
            $rest = preg_replace('/, &quot;.*&quot;/', '<br>', $entry);
        }
        return $rest;
    }

    /**
     * If the field 'url' exists construct a link
     *
     * @param array $entry
     * @return string
     *
     * Credit: Added by Philip Rinn for AG TWiSt, 15. Mai 2014
     */
    public function toTWiStPDF(array $entry): string
    {
        if (array_key_exists('url', $entry)) {
            $string = "[<a href='" . str_replace('\\', '', $entry['url']) . "'>PDF</a>]";
            return $string;
        }
        return '';
    }

    /**
     * this function formats a bibtex code in order to be readable when appearing in the modal window
     * @param $entry
     * @return string
     */
    protected function formatBibtex(string $entry): string
    {
        $order = ['},'];
        $replace = "}, <br />\n &nbsp;";

        $entry = preg_replace('/\s\s+/', ' ', trim($entry));
        $new_entry = str_replace($order, $replace, $entry);
        $new_entry = str_replace(', author', ", <br />\n &nbsp;&nbsp;author", $new_entry);
        $new_entry = str_replace(', Author', ", <br />\n &nbsp;&nbsp;author", $new_entry);
        $new_entry = str_replace(', AUTHOR', ", <br />\n &nbsp;&nbsp;author", $new_entry);
        $new_entry = preg_replace('/\},?\s*\}$/', "}\n}", $new_entry);
        return $new_entry;
    }
}
