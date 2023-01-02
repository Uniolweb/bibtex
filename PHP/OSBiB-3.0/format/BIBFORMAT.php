<?php

declare(strict_types=1);
/**
OSBib:
A collection of PHP classes to create and manage bibliographic formatting for OS bibliography software
using the OSBib standard.

Released through http://bibliophile.sourceforge.net under the GPL licence.
Do whatever you like with this -- some credit to the author(s) would be appreciated.

If you make improvements, please consider contacting the administrators at bibliophile.sourceforge.net
so that your improvements can be added to the release package.

Mark Grimshaw 2005
http://bibliophile.sourceforge.net
*/

/** Description of class BIBFORMAT
* Format a bibliographic resource for output.
*
* @author Mark Grimshaw
* @version 1
*/
class BIBFORMAT
{
    protected bool $wikindx = false;
    protected bool $bibtex = false;
    protected bool $preview = false;
    protected bool $dateMonthDay = false;
    protected bool $dateMonthNoDay = false;
    protected bool $pages_plural = false;
    /**
     * @var bool|string
     */
    protected $patternHighlight = '';

    /**
     *  Convert the bibTeX special characters to produce utf8
     *  Default: 'FALSE', we assume that the entries are already clean
     */
    protected bool $cleanEntry = false;
    protected bool $citationFootnote = false;

    /** bool? */
    protected $patterns = false;

    /**
     * @var string|int
     * @todo $this->$type is used in several places to access class properties, use different mechanism to make
     *   type checking possible.
     */
    protected $type = '';
    protected string $titleSubtitleSeparator = '';
    protected string $previousCreator = '';
    protected string $footnoteType = '';
    protected string $dir = '';
    protected string $bibtexParsePath = '';
    protected string $output = '';

    /**
     * @var string
     * @todo is never set to anything except empty string?
     */
    protected string $footnotePages = '';

    /** @var string|object|null  */
    protected $wikindxLanguageClass;
    protected array $style = [];
    protected array $shortMonth = [];
    protected array $longMonth = [];
    protected array $item = [];
    protected array $dateArray = [];
    protected array $footnoteStyle = [];
    protected array $fallback = [];
    protected array $footnoteTypeArray = [];
    protected array $book = [];
    protected array $book_article = [];
    protected array $backup = [];
    protected array $creators = [];

    /**
     * @var array|bool
     * @todo uncertain which type is correct, may be accessed with $this->$type
     */
    protected $editorSwitch = false;

    protected ?StyleMapInterface $styleMap = null;
    protected ?UTF8 $utf8 = null;
    protected ?BIBTEXCONFIG $config = null;
    protected ?PARSESTYLE $parseStyle = null;

    /**
    * $dir is the path to STYLEMAP.php etc.
    */
    public function __construct(
        string $dir = '',
        bool $bibtex = false,
        bool $preview = false,
        bool $wikindx = false,
        bool $cleanEntry = false
    ) {
        include_once(__DIR__ . '/PARSESTYLE.php');
        $this->parseStyle = new PARSESTYLE();

        $this->cleanEntry = $cleanEntry;

        //05/05/2005 G.GARDEY: add a last "/" to $stylePath if not present.
        $this->preview = $preview;
        if (!$this->preview) { // Not javascript preview
            $dir = trim($dir);
            if (!$dir) {
                $this->dir = __DIR__ . '/';
            } else {
                $this->dir = $dir;
                if ($dir[strlen($dir)-1] != '/') {
                    $this->dir .= '/';
                }
            }
            $this->bibtexParsePath  = $this->dir . 'format/bibtexParse';
        } elseif (!$dir) {// preview
            $this->dir = __DIR__ . '/';
        }
        $this->bibtex = $bibtex;
        include_once(__DIR__ . '/../StyleMapInterface.php');
        include_once(__DIR__ . '/../AbstractStyleMap.php');
        if ($this->bibtex) {
            include_once(__DIR__ . '/../STYLEMAPBIBTEX.php');
            $this->styleMap = new STYLEMAPBIBTEX();
        } else {
            include_once(__DIR__ . '/../STYLEMAP.php');
            $this->styleMap = new STYLEMAP();
        }
        include_once(__DIR__ . '/../UTF8.php');
        $this->utf8 = new UTF8();
        /**
         * Highlight preg pattern and CSS class for HTML display
         * @todo $this->patterns not used?
         */
        $this->patterns = false;
        $this->patternHighlight = '';
        /**
        * Output medium:
        * 'html', 'rtf', or 'plain'
        */
        $this->output = 'html';
        $this->previousCreator = '';
        /**
        * Switch editor and author positions in the style definition for a book in which there are only editors
        */
        $this->editorSwitch = $this->dateMonthNoDay = false;
        $this->creators = ['creator1', 'creator2', 'creator3', 'creator4', 'creator5'];
        // Some styles require different templates and formatting of creator names for a citation in a footnote as opposed to a full bibliography.  Setting this to TRUE (set
        // externally in CITEFORMAT) loads a different set of templates and settings for footnotes.  The default FALSE is for full bibliography.
        $this->citationFootnote = false;
        $this->footnotePages = '';
        $this->footnoteType = '';
        $this->wikindx = $wikindx;
        $this->titleSubtitleSeparator = ': ';
    }

    public function getUtf8(): ?UTF8
    {
        return $this->utf8;
    }

    /**
     * @param string $propertyName
     * @return string|array
     */
    public function getDynamicProperty(string $propertyName)
    {
        return $this->$propertyName;
    }

    public function getDynamicPropertyArrayElement(string $propertyName, string $arrayElement): string
    {
        return $this->$propertyName[$arrayElement];
    }

    public function getFootnoteTypeArray(): array
    {
        return $this->footnoteTypeArray;
    }

    /**
     * @return bool
     */
    public function isCitationFootnote(): bool
    {
        return $this->citationFootnote;
    }

    public function setCitationFootnote(bool $citationFootnote): void
    {
        $this->citationFootnote = $citationFootnote;
    }

    /**
     * @return string
     */
    public function getOutput(): string
    {
        return $this->output;
    }

    /**
     * @param string $output
     */
    public function setOutput(string $output): void
    {
        $this->output = $output;
    }

    /**
     * @param string|bool $patternHighlight
     */
    public function setPatternHighlight($patternHighlight): void
    {
        $this->patternHighlight = $patternHighlight;
    }

    /**
     * @return bool|string
     */
    public function getPatternHighlight()
    {
        return $this->patternHighlight;
    }

    /**
     * @param bool $wikindx
     */
    public function setWikindx(bool $wikindx): void
    {
        $this->wikindx = $wikindx;
    }

    /**
     * If cleanEntry is true, convert BibTeX (and LaTeX) special characters to UTF-8
     *
     * @return bool
     */
    public function isCleanEntry(): bool
    {
        return $this->cleanEntry;
    }

    /**
     * If cleanEntry is true, convert BibTeX (and LaTeX) special characters to UTF-8
     *
     * @param bool $cleanEntry
     */
    public function setCleanEntry(bool $cleanEntry): void
    {
        $this->cleanEntry = $cleanEntry;
    }

    /**
     * @return bool
     */
    public function isPatterns(): bool
    {
        return $this->patterns;
    }

    public function getTitleSubtitleSeparator(): string
    {
        return $this->titleSubtitleSeparator;
    }

    public function getStyleMap(): ?StyleMapInterface
    {
        return $this->styleMap;
    }

    public function setStyleEntry(string $key, string $value): void
    {
        $this->style[$key] = $value;
    }

    /**
     * @return int|string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param int|string $type
     */
    public function setType($type): void
    {
        $this->type = $type;
    }

    /**
     * Read the chosen bibliographic style and create arrays based on resource type.
     * !!! The returned array changed!
     *
     * @author Mark Grimshaw
     * @version 4.0
     *
     * @param string $stylePath The path where the styles are.
     * @param string $style The requested bibliographic output style.
     * @return array
     */
    public function loadStyle(string $stylePath, string $style): array
    {
        $namedArray = $this->loadStyleAsNamedArray($stylePath, $style);
        return [
            $namedArray['info'] ?? [],
            $namedArray['citation'] ?? [],
            $namedArray['footnote'] ?? [],
            $namedArray['common'] ?? [],
            $namedArray['types'] ?? [],
        ];
    }

    /**
    * Read the chosen bibliographic style and create arrays based on resource type.
    * !!! The returned array changed!
    *
    * @author Mark Grimshaw
    * @version 4.0
    *
    * @param string $stylePath The path where the styles are.
    * @param string $style The requested bibliographic output style.
    * @return array
    */
    public function loadStyleAsNamedArray(string $stylePath, string $style): array
    {
        //05/05/2005 G.GARDEY: add a last "/" to $stylePath if not present.
        $stylePath = trim($stylePath);
        if ($stylePath[strlen($stylePath)-1] != '/') {
            $stylePath .= '/';
        }
        $uc = $stylePath . strtolower($style) . '/' . strtolower($style) . '.xml';
        $lc = $stylePath . strtolower($style) . '/' . strtoupper($style) . '.xml';
        $styleFile = file_exists($uc) ? $uc : $lc;
        if (!$fh = fopen($styleFile, 'r')) {
            return [
                'info' => [],
                'citation' => [],
                'footnote' => [],
                'common' => [],
                'types' => [],
            ];
        }
        include_once(__DIR__ . '/../PARSEXML.php');
        $parseXML = new PARSEXML();
        list($info, $citation, $footnote, $common, $types) = $parseXML->extractEntries($fh);
        fclose($fh);
        // return array($info, $citation, $footnote, $common, $types);
        return [
            'info' => $info ?? [],
            'citation' => $citation ?? [],
            'footnote' => $footnote ?? [],
            'common' => $common ?? [],
            'types' => $types ?? [],
        ];
    }

    /**
    * Transform the raw data from the XML file into usable arrays
    *
    * @author Mark Grimshaw
    * @version 1
    *
    * @param array $common Array of global formatting data
    * @param array $types Array of style definitions for each resource type
    * @param array $footnote Array of style definitions for footnote creators
    */
    public function getStyle(array $common, array $types, array $footnote = [])
    {
        $this->commonToArray($common);
        $this->footnoteToArray($footnote);
        $this->typesToArray($types);
        /**
        * Load localisations etc.
        */
        $this->loadArrays();
    }

    /**
     * Reformat the array representation of common styling into a more useable format.
     * 'common' styling refers to formatting that is common to all resource types such as creator formatting, title
     * capitalization etc.
     *
     * Creates a flattened array representation for easier use.
     *
     * @author Mark Grimshaw
     * @version 1
     *
     * @param array $common nodal array representation of XML data
     */
    public function commonToArray(array $common): void
    {
        foreach ($common as $array) {
            if (array_key_exists('_NAME', $array) && array_key_exists('_DATA', $array)) {
                $this->style[$array['_NAME']] = $array['_DATA'];
            }
        }
    }

    /**
     * Reformat the array representation of resource types into arrays based on the type.
     *
     * @param array $types nodal array representation of XML data
     */
    public function typesToArray(array $types): void
    {
        foreach ($types as $resourceArray) {
            // The resource type which will be our array name
            $type = $resourceArray['_ATTRIBUTES']['name'];
            $this->rewriteCreatorsToArray($type, $resourceArray);
            $styleDefinition = $resourceArray['_ELEMENTS'];
            foreach ($styleDefinition as $array) {
                if (array_key_exists('_NAME', $array) && array_key_exists('_DATA', $array)
                     && array_key_exists('_ELEMENTS', $array)) {
                    if ($array['_NAME'] == 'fallbackstyle') {
                        $this->fallback[$type] = $array['_DATA'];
                        break;
                    }
                    if ($array['_NAME'] == 'ultimate') {
                        $this->$type['ultimate'] = $array['_DATA'];
                        continue;
                    }
                    if ($array['_NAME'] == 'preliminaryText') {
                        $this->$type['preliminaryText'] = $array['_DATA'];
                        continue;
                    }
                    foreach ($array['_ELEMENTS'] as $elements) {
                        $data = $elements['_DATA'];
                        if ($array['_NAME'] == 'independent') {
                            $split = mb_split('_', $elements['_NAME']);
                            $this->$type[$array['_NAME']][$split[1]] = $data;
                        } else {
                            $this->$type[$array['_NAME']][$elements['_NAME']] = $data;
                        }
                    }
                }
            }
            /**
            * Backup each $this->$type array.  If we need to switch editors, it's faster to restore each
            * $this->$type array from this backup than to reload the style file and parse it.
            */
            if (isset($this->$type)) {
                $this->backup[$type] = $this->$type;
            }
        }
    }

    /**
     * Reformat the array representation of footnote resource styling into a more useable format.
     *
     * Creates a flattened array representation for easier use.
     * @author Mark Grimshaw
     * @version 1
     *
     * @param array $footnote nodal array representation of XML data
     */
    public function footnoteToArray(array $footnote): void
    {
        foreach ($footnote as $array) {
            if (array_key_exists('_NAME', $array) && array_key_exists('_DATA', $array)) {
                if ($array['_NAME'] != 'resource') {
                    $this->footnoteStyle[$array['_NAME']] = $array['_DATA'];
                } elseif (array_key_exists('_ELEMENTS', $array) && !empty($array['_ELEMENTS'])) {
                    $footnoteType = 'footnote_' . $array['_ATTRIBUTES']['name'];
                    foreach ($array['_ELEMENTS'] as $fArray) {
                        if ($fArray['_NAME'] == 'ultimate') {
                            $this->{$footnoteType}['ultimate'] = $fArray['_DATA'];
                            continue;
                        }
                        if ($fArray['_NAME'] == 'preliminaryText') {
                            $this->{$footnoteType}['preliminaryText'] = $fArray['_DATA'];
                            continue;
                        }
                        foreach ($fArray['_ELEMENTS'] as $elements) {
                            if ($fArray['_NAME'] == 'independent') {
                                $split = mb_split('_', $elements['_NAME']);
                                $this->{$footnoteType}[$fArray['_NAME']][$split[1]] = $elements['_DATA'];
                            } else {
                                $this->{$footnoteType}[$fArray['_NAME']][$elements['_NAME']] =
                                $elements['_DATA'];
                            }
                        }
                    }
                }
            }
        }
    }

    /**
    * Add resource-specific rewrite creator fields to $this->$type array
    *
    * @author Mark Grimshaw
    * @version 1
    */
    public function rewriteCreatorsToArray(string $type, array $array): void
    {
        foreach ($this->creators as $creatorField) {
            $name = $creatorField . '_firstString';
            if (array_key_exists($name, $array['_ATTRIBUTES'])) {
                $this->$type[$name] = $array['_ATTRIBUTES'][$name];
            }
            $name = $creatorField . '_firstString_before';
            if (array_key_exists($name, $array['_ATTRIBUTES'])) {
                $this->$type[$name] = $array['_ATTRIBUTES'][$name];
            }
            $name = $creatorField . '_remainderString';
            if (array_key_exists($name, $array['_ATTRIBUTES'])) {
                $this->$type[$name] = $array['_ATTRIBUTES'][$name];
            }
            $name = $creatorField . '_remainderString_before';
            if (array_key_exists($name, $array['_ATTRIBUTES'])) {
                $this->$type[$name] = $array['_ATTRIBUTES'][$name];
            }
            $name = $creatorField . '_remainderString_each';
            if (array_key_exists($name, $array['_ATTRIBUTES'])) {
                $this->$type[$name] = $array['_ATTRIBUTES'][$name];
            }
        }
    }

    /**
     * Restore each $this->type array from $this->backup
     *
     * @author Mark Grimshaw
     * @version	1
     */
    public function restoreTypes(): void
    {
        foreach ($this->backup as $type => $array) {
            $this->$type = $array;
        }
    }

    /**
     * Perform pre-processing on the raw SQL array
     *
     * @author Mark Grimshaw
     * @version 1
     *
     * @param string $type The resource type
     * @param array $row Associate array of raw SQL data
     * @return array $row Processed row of raw SQL data
     */
    public function preProcess(string $type, array $row): array
    {
        /**
        * Ensure that $this->item is empty for each resource!!!!!!!!!!
        */
        $this->item = [];
        // Map this system's resource type to OSBib's resource type
        $this->type = array_search($type, $this->styleMap->getTypes());
        if ($this->bibtex && array_key_exists('author', $row)) {
            $row['creator1'] = $row['author'];
            unset($row['author']);
        }
        if ($this->bibtex && array_key_exists('editor', $row)) {
            $row['creator2'] = $row['editor'];
            unset($row['editor']);
        }
        /**
        * Set any author/editor re-ordering for book and book_article type.
        */
        if (!$this->preview && (($type == 'book') || ($type == 'book_article')) &&
            ($row['creator2'] ?? false) && !($row['creator1'] ?? false) && $this->style['editorSwitch'] &&
            array_key_exists('author', $this->$type)) {
            $row['creator1'] = $row['creator2'];
            $row['creator2'] = false;
            include_once($this->dir . 'PARSESTYLE.php');
            $editorArray = $this->parseStyle->parseStringToArray(
                $type,
                $this->style['editorSwitchIfYes'],
                $this->styleMap
            );
            if (!empty($editorArray) && array_key_exists('editor', $editorArray)) {
                $this->$type['author'] = $editorArray['editor'];
                unset($this->$type['editor']);
                $this->editorSwitch = true;
            }
        }
        if (($this->style['dateMonthNoDay'] ?? false) && array_key_exists('date', $this->styleMap->getDynamicProperty($type)) &&
            array_key_exists('dateMonthNoDayString', $this->style) && $this->style['dateMonthNoDayString']) {
            $this->dateArray = $this->parseStyle->parseStringToArray(
                $type,
                $this->style['dateMonthNoDayString'],
                $this->styleMap,
                true
            );
            $this->dateMonthNoDay = true;
        }
        /**
        * If $row comes in in BibTeX format, process and add items to $this->item
        */
        if ($this->bibtex) {
            if (!$this->type) {
                list($type, $row) = $this->preProcessBibtex($row, $type);
            } else {
                list($type, $row) = $this->preProcessBibtex($row, $this->type);
            }
        }
        /**
        * Ensure that for theses types, the first letter of type and label are capitalized (e.g. 'Master's Thesis').
        */
        if ($type == 'thesis') {
            if (($key = array_search('type', $this->styleMap->getDynamicProperty($type))) !== false) {
                if (isset($row[$key])) {
                    $row[$key] = ucfirst($row[$key]);
                }
            }
            if (($key = array_search('label', $this->styleMap->getDynamicProperty($type))) !== false) {
                if (isset($row[$key])) {
                    $row[$key] = ucfirst($row[$key]);
                }
            }
        }
        /**
        * Set to catch-all generic style.  For all keys except named database fields, creator1 and year1,
        * we only print if the value in $this->styleMap matches the value in
        * $this->styleMap->generic for each key.
        */
        if ($this->citationFootnote) { // using footnote template
            $footnoteType = 'footnote_' . $type;
            if (isset($this->$footnoteType)) { // footnote template for this resource exists
                $this->footnoteType = $footnoteType;
                $this->footnoteTypeArray[$type] = $footnoteType;
            } else {
                $footnoteType = 'footnote_' . $this->fallback[$type];
                if (isset($this->$footnoteType)) { // fallback footnote template exists
                    $this->footnoteType = $footnoteType;
                    $this->footnoteTypeArray[$type] = $footnoteType;
                } elseif (!isset($this->$type)) { // use fallback bibliography template
                    $fallback = $this->fallback[$type];
                    $this->footnoteTypeArray[$type] = $fallback;
                    $type = $fallback;
                }
                // else, we're using the bibliography template for this resource type
                else {
                    $this->footnoteTypeArray[$type] = $type;
                }
            }
        } else {
            if (!isset($this->$type)) {
                $fallback = $this->fallback[$type];
                $type = $fallback;
            }
        }
        $this->type = $type;
        /**
        * Add BibTeX entry to $this->item
        */
        if ($this->bibtex) {
            foreach ($row as $field => $value) {
                if (array_key_exists($field, $this->styleMap->getDynamicProperty($type)) &&
                    !array_key_exists($this->styleMap->getDynamicPropertyArrayElement($type, $field), $this->item)) {
                    $this->addItem($row[$field], $field);
                }
            }
        }
        return $row;
    }

    /**
     * Preprocess BibTeX-type entries
     * @author Mark Grimshaw
     * @version 1
     *
     * @param array $row assoc. array of elements for one bibtex entry
     * @param string $type resource type
     * @return array resource assoc. array of elements for one bibtex entry
     */
    public function preProcessBibtex(array &$row, string $type): array
    {
        $temp = [];

        //05/05/2005 G.GARDEY: change bibtexParse name.
        /**
        * This set of includes is for the OSBib public release and should be uncommented for that and
        * the WIKINDX-specific includes below commented out!
        */
        include_once($this->bibtexParsePath . '/PARSECREATORS.php');
        $parseCreator = new PARSECREATORS();
        include_once($this->bibtexParsePath . '/PARSEMONTH.php');
        $parseDate = new PARSEMONTH();
        include_once($this->bibtexParsePath . '/PARSEPAGE.php');
        $parsePages = new PARSEPAGE();

        // Added by Christophe Ambroise: convert the bibtex entry to utf8 (for storage or printing)
        if ($this->cleanEntry) {
            $row = $this->convertEntry($row);
        }
        /**
        * Bibtex-specific types not defined in STYLEMAPBIBTEX
        */
        if (!$this->type) {
            if ($type == 'mastersthesis') {
                $type = 'thesis';
                $row['type'] = "Master's Dissertation";
            }
            if ($type == 'phdthesis') {
                $type = 'thesis';
                $row['type'] = 'PhD Thesis';
            } elseif ($type == 'booklet') {
                $type = 'miscellaneous';
            } elseif ($type == 'conference') {
                $type = 'proceedings_article';
            } elseif ($type == 'incollection') {
                $type = 'book_article';
            } elseif ($type == 'manual') {
                $type = 'report';
            }
        }
        /**
        * 'article' could be journal, newspaper or magazine article
        */
        elseif ($type == 'journal_article') {
            // @todo 'date' is not in STYLEMAPBIBTEX::journal_article
            if (array_key_exists('month', $row) && array_key_exists('date', $this->styleMap->getDynamicProperty('journal_article'))) {
                list($startMonth, $startDay, $endMonth, $endDay) = $parseDate->init($row['month']);
                if ($startDay) {
                    $type = 'newspaper_article';
                } elseif ($startMonth) {
                    $type = 'magazine_article';
                }
                $this->formatDate($startDay, $startMonth, $endDay, $endMonth);
            }
        }
        /**
        * Is this a web article?
        */
        elseif (($type == 'miscellaneous') && array_key_exists('howpublished', $row)) {
            if (preg_match("#^\\\url{(.*://.*)}#", $row['howpublished'], $match)) {
                $row['URL'] = $match[1];
                $type = 'web_article';
            }
        }
        $this->type = $type;
        if (array_key_exists('creator1', $row) && $row['creator1'] &&
            array_key_exists('creator1', $this->styleMap->getDynamicProperty($type))) {
            $creators = $parseCreator->parse($row['creator1']);
            foreach ($creators as $cArray) {
                $temp[] = [
                        'surname'   => trim($cArray[2]),
                        'firstname' => trim($cArray[0]),
                        'initials'  => trim($cArray[1]),
                        'prefix'    => trim($cArray[3]),
                    ];
            }
            $this->formatNames($temp, 'creator1');
            unset($temp);
        }
        if (array_key_exists('creator2', $row) && $row['creator2'] &&
            array_key_exists('creator2', $this->styleMap->getDynamicProperty($type))) {
            $creators = $parseCreator->parse($row['creator2']);
            foreach ($creators as $cArray) {
                $temp[] = [
                        'surname'   => trim($cArray[2]),
                        'firstname' => trim($cArray[0]),
                        'initials'  => trim($cArray[1]),
                        'prefix'    => trim($cArray[3]),
                    ];
            }
            $this->formatNames($temp ?? [], 'creator2');
        }
        if (array_key_exists('pages', $row) && array_key_exists('pages', $this->styleMap->getDynamicProperty($type))) {
            list($start, $end) = $parsePages->init($row['pages']);
            $this->formatPages($start, $end);
        }
        if (isset($row['title'])) {
            $this->formatTitle($row['title'], '{', '}');
        }
        return [$type, $row];
    }

    /**
     * Map the $item array against the style array ($this->$type) for this resource type and produce a string ready to be
     * formatted for bold, italics etc.
     *
     * @author Mark Grimshaw
     * @version 1
     *
     * @param string $template If called from CITEFORMAT, this is the array of template elements.
     * @return string ready for printing to the output medium.
     *
     * @todo function too complex, split up?
     */
    public function map(string $template = ''): string
    {
        $itemArray = [];

        /**
        * Output medium:
        * 'html', 'rtf', or 'plain'
        */
        include_once(__DIR__ . '/EXPORTFILTER.php');
        $this->export = new EXPORTFILTER($this, $this->output);
        // Don't think $template is used anymore
        if ($template) {
            $this->citation = $template;
            $this->type = 'citation';
        }
        $type = $pluralType = $this->type;
        if ($this->footnoteType) {
            $type = $this->footnoteType;
            $this->footnoteType = false;
        }
        $ultimate = $preliminary = '';
        $index = 0;
        $previousFieldExists = $nextFieldExists = true;
        if (array_key_exists('independent', $this->$type)) {
            $independent = $this->$type['independent'];
        }
        /**
        * For dependency on next field, we must grab array keys of $this->$type, shift the first element then, in the loop,
        * check each element exists in $item.  If it doesn't, $nextFieldExists is set to FALSE
        */
        $checkPost = array_keys($this->$type);
        array_shift($checkPost);
        $lastFieldKey = false;
        // Add or replace pages field if this process is called from CTIEFORMAT for footnotes where $this->footnotePages are the formatted citation pages.
        if ($this->footnotePages) {
            $this->item['pages'] = $this->footnotePages;
        }
        foreach ($this->$type as $key => $value) {
            if ($key == 'ultimate') {
                $ultimate = $value;
                continue;
            }
            if ($key == 'preliminaryText') {
                $preliminary = $value;
                continue;
            }
            if (!array_key_exists($key, $this->item) || !$this->item[$key]) {
                $keyNotExists[] = $index;
                $index++;
                array_shift($checkPost);
                $previousFieldExists = false;
                continue;
            }
            $checkPostShift = array_shift($checkPost);
            if (!array_key_exists($checkPostShift, $this->item) || !$this->item[$checkPostShift]) {
                $nextFieldExists = false;
            }
            $pre = array_key_exists('pre', $value) ? $value['pre'] : '';
            $post = array_key_exists('post', $value) ? $value['post'] : '';
            /**
            * Deal with __DEPENDENT_ON_PREVIOUS_FIELD__ for characters dependent on previous field's existence and
            * __DEPENDENT_ON_NEXT_FIELD__ for characters dependent on the next field's existence
            */
            if ($previousFieldExists && array_key_exists('dependentPre', $value)) {
                $pre = preg_replace(
                    '/__DEPENDENT_ON_PREVIOUS_FIELD__/',
                    $value['dependentPre'],
                    $pre
                );
            } elseif (array_key_exists('dependentPreAlternative', $value)) {
                $pre = preg_replace(
                    '/__DEPENDENT_ON_PREVIOUS_FIELD__/',
                    $value['dependentPreAlternative'],
                    $pre
                );
            } else {
                $pre = preg_replace('/__DEPENDENT_ON_PREVIOUS_FIELD__/', '', $pre);
            }
            if ($nextFieldExists && array_key_exists('dependentPost', $value)) {
                $post = str_replace(
                    '__DEPENDENT_ON_NEXT_FIELD__',
                    $value['dependentPost'],
                    $post
                );
            } elseif (array_key_exists('dependentPostAlternative', $value)) {
                $post = preg_replace(
                    '/__DEPENDENT_ON_NEXT_FIELD__/',
                    $value['dependentPostAlternative'],
                    $post
                );
            } else {
                $post = preg_replace('/__DEPENDENT_ON_NEXT_FIELD__/', '', $post);
            }

            /**
            * Deal with __SINGULAR_PLURAL__ for creator lists and pages
            */
            if ($styleKey = array_search($key, $this->styleMap->getDynamicProperty($pluralType))) {
                $pluralKey = $styleKey . '_plural';
            } else {
                // For use with generic footnote templates which uses generic 'creator' field
                $pluralKey = 'creator_plural';
            }
            if (isset($this->$pluralKey) && $this->$pluralKey) { // plural alternative for this key
                $pre = array_key_exists('plural', $value) ?
                        preg_replace('/__SINGULAR_PLURAL__/', $value['plural'], $pre) : $pre;
                $post = array_key_exists('plural', $value) ?
                    preg_replace('/__SINGULAR_PLURAL__/', $value['plural'], $post) : $post;
            } elseif (isset($this->$pluralKey)) { // singular alternative for this key
                $pre = array_key_exists('singular', $value) ?
                        preg_replace('/__SINGULAR_PLURAL__/', $value['singular'], $pre) : $pre;
                $post = array_key_exists('singular', $value) ?
                    preg_replace('/__SINGULAR_PLURAL__/', $value['singular'], $post) : $post;
            }
            // Deal with en dash characters in pages
            if ($key == 'pages') {
                $this->item[$key] = $this->export->format($this->item[$key]);
            }
            /**
            * Strip backticks used in template
            */
            $pre = str_replace('`', '', $pre);
            $post = str_replace('`', '', $post);
            /**
            * Make sure we don't have multiple punctuation characters after a field
            */            $lastPre = substr($post, -1);
            $firstItem = substr($this->item[$key], 0, 1);
            if ($firstItem === $lastPre) {
                $this->item[$key] = substr($this->item[$key], 1);
            }
            // Match last character of this field with $post
            if ($post && preg_match('/[.,;:?!]$/', $this->item[$key]) &&
                preg_match("/^(\[.*?[\]]+)*([.,;:?!])|^([.,;:?!])/", $post, $capture, PREG_OFFSET_CAPTURE)) {
                // There is punctuation in post either immediately following BBCode formatting or at the start of the string.
                // The offset for the punctuation character in $post is given at $capture[2][1]
                $post = substr_replace($post, '', $capture[2][1], 1);
            }
            // Match $itemArray[$lastFieldKey] with $pre
            if (($lastFieldKey !== false) && $pre && preg_match('/^[.,;:?!]/', $pre) &&
                preg_match(
                    "/([.,;:?!])(\[.*?[\]]+)*$|([.,;:?!])$/",
                    $itemArray[$lastFieldKey],
                    $capture,
                    PREG_OFFSET_CAPTURE
                )) {
                // There is punctuation in post either immediately following BBCode formatting or at the start of the string.
                $pre = substr_replace($pre, '', 0, 1);
            }
            if ($this->item[$key]) {
                $itemArray[$index] = $pre . $this->item[$key] . $post;
                $lastFieldKey = $index;
            }
            $previousFieldExists = $nextFieldExists = true;
            $index++;
        }
        /**
        * Check for independent characters.  These (should) come in pairs.
        */        if (isset($independent)) {
            $independentKeys = array_keys($independent);
            while ($independent) {
                $preAlternative = $postAlternative = false;
                $startFound = $endFound = false;
                $pre = array_shift($independent);
                $post = array_shift($independent);
                if (preg_match('/%(.*)%(.*)%|%(.*)%/U', $pre, $dependent)) {
                    if (count($dependent) == 4) {
                        $pre = $dependent[3];
                    } else {
                        $pre = $dependent[1];
                        $preAlternative = $dependent[2];
                    }
                }
                if (preg_match('/%(.*)%(.*)%|%(.*)%/U', $post, $dependent)) {
                    if (count($dependent) == 4) {
                        $post = $dependent[3];
                    } else {
                        $post = $dependent[1];
                        $postAlternative = $dependent[2];
                    }
                }
                /**
                * Strip backticks used in template
                */
                if (!$preAlternative) {
                    $preAlternative = '';
                }
                if (!$postAlternative) {
                    $postAlternative = '';
                }
                $preAlternative = str_replace('`', '', $preAlternative);
                $postAlternative = str_replace('`', '', $postAlternative);
                $firstKey = array_shift($independentKeys);
                $secondKey = array_shift($independentKeys);
                for ($index = $firstKey; $index <= $secondKey; $index++) {
                    if (array_key_exists($index, $itemArray)) {
                        $startFound = $index;
                        break;
                    }
                }
                for ($index = $secondKey; $index >= $firstKey; $index--) {
                    if (array_key_exists($index, $itemArray)) {
                        $endFound = $index;
                        break;
                    }
                }
                if (($startFound !== false) && ($endFound !== false)) { // intervening fields found
                    $itemArray[$startFound] = $pre . $itemArray[$startFound];
                    $itemArray[$endFound] = $itemArray[$endFound] . $post;
                } else { // intervening fields not found - do we have an alternative?
                    if (array_key_exists($firstKey - 1, $itemArray) && $preAlternative) {
                        $itemArray[$firstKey - 1] .= $preAlternative;
                    }
                    if (array_key_exists($secondKey + 1, $itemArray) && $postAlternative) {
                        $itemArray[$secondKey + 1] = $postAlternative . $itemArray[$secondKey + 1];
                    }
                }
            }
        }
        $pString = implode('', $itemArray);
        /**
        * if last character is punctuation (which it may be with missing fields etc.), and $ultimate is also
        * punctuation, set $ultimate to empty string.
        */        if (isset($ultimate) && $ultimate) {
            $pString = trim($pString);
            /**
            * Don't do ';' in case last element is URL with &gt; ...!
            */
            if (preg_match('/^[.,:?!]/', $ultimate) &&
                preg_match("/([.,:?!])(\[.*?[\]]+)*$|([.,:?!])$/", $pString)) {
                $ultimate = '';
            }
        }
        // If $this->editorSwitch or $this->dateMonthNoDay, we have altered $this->$bibformat->$type so need to reload styles
        if (!$this->preview && ($this->editorSwitch || $this->dateMonthNoDay)) {
            $this->restoreTypes();
            $this->editorSwitch = $this->dateMonthNoDay = false;
        }
        return $this->export->format($preliminary . trim($pString) . $ultimate);
    }

    /**
     * Format creator name lists (authors, editors, etc.)
     *
     * @author Mark Grimshaw
     * @version 1
     *
     * @param array $creators Multi-associative array of creator names e.g. this array might be of
     * the primary authors:
     * <pre>
     * array([0] => array(['surname'] => 'Grimshaw', ['firstname'] => Mark, ['initials'] => 'N', ['prefix'] => ),
     *    [1] => array(['surname'] => 'Witt', ['firstname'] => Jan, ['initials'] => , ['prefix'] => 'de'))
     * </pre>
     * @param string $nameType 'creator1', 'creator2' etc.
     * @param bool $shortFootnote.  If TRUE, this is being used for just the primary creator names in a footnote style citation using Ibid, Idem, op cit. etc.
     *   return is optional if $nameType == 'citation': formatted string of all creator names in the input array.
     *
     * @todo optional return, always return something?
     * @todo split up function, is too complex
     */
    public function formatNames(array $creators, string $nameType, bool $shortFootnote = false)
    {
        /** @var array $style */
        $style = $this->citationFootnote ? $this->footnoteStyle : $this->style;
        /**
         * @var bool $first
         * @todo $first is always true. Possible bug?
         */
        $first = true;
        $cArray = [];
        $creatorIds = [];

        /**
        * Citation creators
        */
        if ($nameType === 'citation') {
            $limit = 'creatorListLimit';
            $moreThan = 'creatorListMore';
            $abbreviation = 'creatorListAbbreviation';
            $initialsStyle = 'creatorInitials';
            $firstNameInitial = 'creatorFirstName';
            $delimitTwo = 'twoCreatorsSep';
            $delimitFirstBetween = 'creatorSepFirstBetween';
            $delimitNextBetween = 'creatorSepNextBetween';
            $delimitLast = 'creatorSepNextLast';
            $uppercase = 'creatorUppercase';
            $italics = 'creatorListAbbreviationItalic';
            if ($first) {
                $nameStyle = 'creatorStyle';
            } else {
                $nameStyle = 'creatorOtherStyle';
            }
        }
        /**
        * Primary creator
        */
        elseif ($nameType === 'creator1') {
            $limit = 'primaryCreatorListLimit';
            $moreThan = 'primaryCreatorListMore';
            $abbreviation = 'primaryCreatorListAbbreviation';
            $initialsStyle = 'primaryCreatorInitials';
            $firstNameInitial = 'primaryCreatorFirstName';
            $delimitTwo = 'primaryTwoCreatorsSep';
            $delimitFirstBetween = 'primaryCreatorSepFirstBetween';
            $delimitNextBetween = 'primaryCreatorSepNextBetween';
            $delimitLast = 'primaryCreatorSepNextLast';
            $uppercase = 'primaryCreatorUppercase';
            $italics = 'primaryCreatorListAbbreviationItalic';
            if ($first) {
                $nameStyle = 'primaryCreatorFirstStyle';
            } else {
                $nameStyle = 'primaryCreatorOtherStyle';
            }
        } else {
            $limit = 'otherCreatorListLimit';
            $moreThan = 'otherCreatorListMore';
            $abbreviation = 'otherCreatorListAbbreviation';
            $initialsStyle = 'otherCreatorInitials';
            $firstNameInitial = 'otherCreatorFirstName';
            $delimitTwo = 'otherTwoCreatorsSep';
            $delimitFirstBetween = 'otherCreatorSepFirstBetween';
            $delimitNextBetween = 'otherCreatorSepNextBetween';
            $delimitLast = 'otherCreatorSepNextLast';
            $uppercase = 'otherCreatorUppercase';
            $italics = 'otherCreatorListAbbreviationItalic';

            if ($first) {
                $nameStyle = 'otherCreatorFirstStyle';
            } else {
                $nameStyle = 'otherCreatorOtherStyle';
            }
        }
        $type = $this->type;
        /**
        * Set default plural behaviour for creator lists
        */
        // For use with generic footnote templates which uses generic 'creator' field
        if ($this->citationFootnote && ($nameType == 'creator1') &&
        ($this->styleMap->{$type}[$nameType] != 'creator')) {
            $pluralKey = 'creator_plural';
        } else {
            $pluralKey = $nameType . '_plural';
        }
        $this->$pluralKey = false;
        $firstInList = true;
        $rewriteCreatorBeforeDone = $rewriteCreatorFinal = false;
        foreach ($creators as $creator) {
            if (array_key_exists('id', $creator)) {
                $creatorIds[] = $creator['id'];
            }
            $firstName = trim($this->checkInitials(
                $creator,
                $style[$initialsStyle],
                $style[$firstNameInitial]
            ));
            $prefix = $creator['prefix'] ? trim(stripslashes($creator['prefix'])) . ' ' : '';
            if ($style[$nameStyle] == 0) { // Joe Bloggs
                $nameString = $firstName . ' ' .
                        $prefix .
                        stripslashes($creator['surname']);
            } elseif ($style[$nameStyle] == 1) { // Bloggs, Joe
                $prefixDelimit = $firstName ? ', ' : '';
                $nameString =
                    stripslashes($creator['prefix']) . ' ' .
                    stripslashes($creator['surname']) . $prefixDelimit .
                    $firstName;
            } elseif ($style[$nameStyle] == 2) { // Bloggs Joe
                $nameString =
                        stripslashes($creator['prefix']) . ' ' .
                        stripslashes($creator['surname']) . ' ' .
                        $firstName;
            } else { // Last name only
                $nameString =
                        stripslashes($creator['prefix']) . ' ' .
                        stripslashes($creator['surname']);
            }
            if (isset($style[$uppercase])) {
                $nameString = $this->utf8->utf8_strtoupper($nameString);
            }
            $nameString = trim($nameString);
            if ($firstInList) {
                $rewriteCreatorField = $nameType . '_firstString';
                $rewriteCreatorFieldBefore = $nameType . '_firstString_before';
            } else {
                $rewriteCreatorField = $nameType . '_remainderString';
                $rewriteCreatorFieldBefore = $nameType . '_remainderString_before';
                $rewriteCreatorFieldEach = $nameType . '_remainderString_each';
            }
            if (array_key_exists($rewriteCreatorField, $this->$type ?? [])) {
                if ($firstInList) {
                    if (array_key_exists($rewriteCreatorFieldBefore, $this->$type)) {
                        $nameString = $this->$type[$rewriteCreatorField] . $nameString;
                    } else {
                        $nameString .= $this->$type[$rewriteCreatorField];
                    }
                    $firstInList = false;
                } elseif (array_key_exists($rewriteCreatorFieldEach, $this->$type)) {
                    if (array_key_exists($rewriteCreatorFieldBefore, $this->$type)) {
                        $nameString = $this->$type[$rewriteCreatorField] . $nameString;
                    } else {
                        $nameString .= $this->$type[$rewriteCreatorField];
                    }
                } else {
                    if (!$rewriteCreatorBeforeDone && array_key_exists($rewriteCreatorFieldBefore, $this->$type)) {
                        $nameString = $this->$type[$rewriteCreatorField] . $nameString;
                        $rewriteCreatorBeforeDone = true;
                    } elseif (!$rewriteCreatorBeforeDone &&
                        !array_key_exists($rewriteCreatorFieldEach, $this->$type)) {
                        $rewriteCreatorFinal = $this->$type[$rewriteCreatorField];
                    }
                }
            }
            $cArray[] = $nameString;
            $first = false;
        }
        /**
        * Keep only some elements in array if we've exceeded $moreThan
        */
        $etAl = false;
        if ($style[$limit] && (count($cArray) > $style[$moreThan])) {
            array_splice($cArray, $style[$limit]);
            if (isset($style[$italics])) {
                $etAl = '[i]' . $style[$abbreviation] . '[/i]';
            } else {
                $etAl = $style[$abbreviation];
            }
        }
        /**
        * add delimiters
        */
        if (count($cArray) > 1) {
            if (count($cArray) == 2) {
                $cArray[0] .= $style[$delimitTwo];
            } else {
                for ($index = 0; $index < (count($cArray) - 2); $index++) {
                    if (!$index) {
                        $cArray[$index] .= $style[$delimitFirstBetween];
                    } else {
                        $cArray[$index] .= $style[$delimitNextBetween];
                    }
                }
                $cArray[count($cArray) - 2] .= $style[$delimitLast];
            }
        }
        /**
        * If sizeof of $cArray > 1 or $etAl != FALSE, set this $nameType_plural to TRUE
        */
        if ((count($cArray) > 1) || $etAl) {
            $this->$pluralKey = true;
        }
        /**
        * Finally flatten array
        */
        if ($etAl) {
            $pString = implode('', $cArray) . $etAl;
        } else {
            $pString = implode('', $cArray);
        }
        if ($rewriteCreatorFinal) {
            $pString .= $rewriteCreatorFinal;
        }
        /**
        * Check for repeating primary creator list in subsequent bibliographic item.
        */
        if ($nameType == 'creator1') {
            $tempString = $pString;
            if (($style['primaryCreatorRepeat'] == 2) && ($this->previousCreator == $pString)) {
                $pString = $style['primaryCreatorRepeatString'];
            } elseif (($style['primaryCreatorRepeat'] == 1) && ($this->previousCreator == $pString)) {
                $pString = '';
            } // don't print creator list
            $this->previousCreator = $tempString;
        }
        if ($shortFootnote) {
            return [$pString, $creatorIds];
        }
        // For use with generic footnote templates, we must also place 'creator1' string (if not called 'creator') into the 'creator' slot
        if (($nameType == 'creator1') && ($this->styleMap->getDynamicPropertyArrayElement($type, $nameType) != 'creator')) {
            $this->item['creator'] = $pString;
        }
        $this->item[$this->styleMap->getDynamicPropertyArrayElement($type, $nameType)] = $pString;
    }

    /**
     * Handle initials.
     * @see formatNames()
     *
     * @author	Mark Grimshaw
     * @version	1
     *
     * @param array $creator Associative array of creator name e.g.
     * <pre>
     * array(['surname'] => 'Grimshaw', ['firstname'] => Mark, ['initials'] => 'M N G', ['prefix'] => ))
     * </pre>
     * Initials must be space-delimited.
     *
     * @param $initialsStyle
     * @param $firstNameInitial
     * @return string Formatted string of initials.
     */
    public function checkInitials(array &$creator, $initialsStyle, $firstNameInitial): string
    {
        /**
        * Format firstname
        */
        if ($creator['firstname'] && !$firstNameInitial) { // Full name
            $firstName = stripslashes($creator['firstname']);
        } elseif ($creator['firstname']) { // Initial only of first name.  'firstname' field may actually have several 'firstnames'
            $fn = mb_split(' ', stripslashes($creator['firstname']));
            $firstTime = true;
            foreach ($fn as $name) {
                if ($firstTime) {
                    $firstNameInitialMake = $this->utf8->utf8_strtoupper($this->utf8->utf8_substr(trim($name), 0, 1));
                    $firstTime = false;
                } else {
                    $initials[] = $this->utf8->utf8_strtoupper($this->utf8->utf8_substr(trim($name), 0, 1));
                }
            }
            if (isset($initials)) {
                if ($creator['initials']) {
                    $creator['initials'] = implode(' ', $initials) . ' ' . $creator['initials'];
                } else {
                    $creator['initials'] = implode(' ', $initials);
                }
            }
        }
        /**
        * Initials are stored as space-delimited characters.
        * If no initials, return just the firstname or its initial in the correct format.
        */
        if (!$creator['initials']) {
            if (isset($firstName)) { // full first name only
                return $firstName;
            }
            if (isset($firstNameInitialMake) && $initialsStyle > 1) { // First name initial with no '.'
                return $firstNameInitialMake;
            }
            if (isset($firstNameInitialMake)) { // First name initial with  '.'
                return $firstNameInitialMake . '.';
            }
            return ''; // nothing here
        }
        $initialsArray = explode(' ', $creator['initials']);
        /**
        * If firstname is initial only, prepend to array
        */
        if (isset($firstNameInitialMake)) {
            array_unshift($initialsArray, $firstNameInitialMake);
        }
        if ($initialsStyle == 0) { // 'T. U. '
            $initials = implode('. ', $initialsArray) . '.';
        } elseif ($initialsStyle == 1) { // 'T.U.'
            $initials = implode('.', $initialsArray) . '.';
        } elseif ($initialsStyle == 2) { // 'T U '
            $initials = implode(' ', $initialsArray);
        } else { // 'TU '
            $initials = implode('', $initialsArray);
        }
        /**
        * If we have a full first name, prepend it to $initials.
        */
        if (isset($firstName)) {
            return $firstName . ' ' . $initials;
        }
        return $initials;
    }

    /**
     * Add an item to $this->item array
     *
     * @author Mark Grimshaw
     * @version	1
     *
     * @param string|bool $item The item to be added.
     * @param string $fieldName The database fieldName of the item to be added
     *
     * @todo use only string for $item
    */
    public function addItem($item, string $fieldName): bool
    {
        $type = $this->type;
        if ($item === false) {
            return true;
        }
        $item = (string)$item;
        $item = stripslashes($item);
        /**
        * This item may already exist (e.g. edition field for WIKINDX)
        */
        if (isset($this->item) && array_key_exists($this->styleMap->getDynamicPropertyArrayElement($type, $fieldName), $this->item)) {
            return false;
        }
        $this->item[$this->styleMap->getDynamicPropertyArrayElement($type, $fieldName)] = $item;
        return true;
    }

    /**
     * Add all remaining items to $this->item array
     *
     * @author Mark Grimshaw
     * @version 1
     *
     * @param array $row The items to be added.
     */
    public function addAllOtherItems(array $row): void
    {
        $type = $this->type;
        foreach ($row as $field => $value) {
            if (array_key_exists($field, $this->styleMap->getDynamicProperty($type)) &&
                !array_key_exists($this->styleMap->getDynamicPropertyArrayElement($type, $field), $this->item)) {
                $item = stripslashes($row[$field]);
                $this->addItem($item, $field);
            }
        }
    }

    /**
     * Format a title.  Anything enclosed in $delimitLeft...$delimitRight is to be left unchanged
     *
     * @author Mark Grimshaw
     * @version	1
     *
     * @param $pString Raw title string.
     * @param $delimitLeft
     * @param $delimitRight
     */
    public function formatTitle(string $pString, string $delimitLeft = '', string $delimitRight = ''): void
    {
        if (!$delimitLeft) {
            $delimitLeft = '{';
        }
        if (!$delimitRight) {
            $delimitRight = '}';
        }
        $delimitLeft = preg_quote($delimitLeft, '/');
        $delimitRight = preg_quote($delimitRight, '/');
        $match = '/' . $delimitLeft . '/';
        $type = $this->type;
        if (!array_key_exists('title', $this->styleMap->getDynamicProperty($type))) {
            $this->item[$this->styleMap->getDynamicPropertyArrayElement($type, 'title')] = '';
        }
        /**
        * '0' == 'Osbib Bibliographic Formatting'
        * '1' == 'Osbib bibliographic formatting'
        */
        if ($this->style['titleCapitalization']) {
            // Something here (preg_split probably) interferes with UTF-8 encoding (data is stored in
            // the database as UTF-8 as long as web browser charset == UTF-8).
            // So first decode then encode back to UTF-8 at end.
            // There is a 'u' UTF-8 parameter for preg_xxx but it doesn't work.
            $pString = $this->utf8->decodeUtf8($pString);
            $newString = '';
            while (preg_match($match, $pString)) {
                $array = preg_split(
                    "/(.*)$delimitLeft(.*)$delimitRight(.*)/U",
                    $pString,
                    2,
                    PREG_SPLIT_DELIM_CAPTURE
                );
                /**
                * in case user has input {..} incorrectly
                */
                if (count($array) == 1) {
                    break;
                }
                $newString .= $this->utf8->utf8_strtolower($this->utf8->encodeUtf8($array[1])) . $array[2];
                $pString = $array[4];
            }
            $newString .= $this->utf8->utf8_strtolower($this->utf8->encodeUtf8($pString));
        }
        $pString = isset($newString) ? $newString : $pString;
        $title = $this->utf8->utf8_ucfirst(trim($pString));
        $this->item[$this->styleMap->getDynamicPropertyArrayElement($type, 'title')] = $title;
    }

    /**
     * Format pages.
     *
     * !!! This is a fault tolerant functions which also handles invalid input. In case something is not formatted
     * as expected, the original string is used.
     * Since $start and $end might also be false, we do not do strict type checking here (yet)
     *
     * $this->style['pageFormat']:
     * 0 == 132-9
     * 1 == 132-39
     * 2 == 132-139
     *
     * @author	Mark Grimshaw
     * @version	1
     *
     * @param bool|int|string $start Page start.
     * @param bool|int|string $end Page end.
     * @param bool $citation If called from CITEFORMAT, this is the array of citation stylings.
     *
     * @todo Use a class for $start / $end
     * @see PARSEPAGE::init()
    */
    public function formatPages($start, $end = false, bool $citation = false): void
    {
        $type = $this->type;
        $style = $citation ? $citation : $this->style;
        /**
        * Set default plural behaviour for pages
        */
        $this->pages_plural = false;

        /**
        * If no page end, use just $start;
        */
        if (!$end) {
            $this->item[$this->styleMap->getDynamicPropertyArrayElement($type, 'pages')] = $start;
            return;
        }
        /**
        * Pages may be in roman numeral format etc.  Return unchanged
        */
        if (!is_numeric($start)) {
            $this->item[$this->styleMap->getDynamicPropertyArrayElement($type, 'pages')] = $start . 'WIKINDX_NDASH' . $end;
            return;
        }
        /**
        * We have multiple pages...
        */
        $this->pages_plural = true;
        /**
        * They've done something wrong so give them back exactly what they entered
        */
        if (($end <= $start) || (strlen($end) < strlen($start))) {
            $this->item[$this->styleMap->getDynamicPropertyArrayElement($type, 'pages')] = $start . 'WIKINDX_NDASH' . $end;
            return;
        }
        if ($style['pageFormat'] == 2) {
            $this->item[$this->styleMap->getDynamicPropertyArrayElement($type, 'pages')] = $start . 'WIKINDX_NDASH' . $end;
            return;
        }

        /**
        * We assume page numbers are not into the 10,000 range - if so, return the complete pages
        */
        if (strlen($start) <= 4) {
            $startArray = preg_split('//', $start);
            array_shift($startArray); // always an empty element at start?
            array_pop($startArray); // always an empty array element at end?
            if ($style['pageFormat'] == 0) {
                array_pop($startArray);
                $endPage = substr($end, -1);
                $index = -2;
            } else {
                array_pop($startArray);
                array_pop($startArray);
                $endPage = substr($end, -2);
                $index = -3;
            }
            while (!empty($startArray)) {
                $startPop = array_pop($startArray);
                $endSub = substr($end, $index--, 1);
                if ($endSub == $startPop) {
                    $this->item[$this->styleMap->getDynamicPropertyArrayElement($type, 'pages')]
                        = $start . '-' . $endPage;
                    return;
                }
                if ($endSub > $startPop) {
                    $endPage = $endSub . $endPage;
                }
            }
        } else {
            $this->item[$this->styleMap->getDynamicPropertyArrayElement($type, 'pages')] = $start . 'WIKINDX_NDASH' . $end;
            return;
        }

        /**
        * We should never reach here - in case we do, give back complete range so that something at least is printed
        */
        $this->item[$this->styleMap->getDynamicPropertyArrayElement($type, 'pages')] = $start . 'WIKINDX_NDASH' . $end;
    }

    /**
     * Format runningTime for film/broadcast
     *
     * @author Mark Grimshaw
     * @version 1
     *
     * @param int|string|mixed $minutes
     * @param int|string|mixed $hours
     *
     * @todo be more strict with types
     */
    public function formatRunningTime($minutes, $hours): void
    {
        $runningTime = '';

        $type = $this->type;
        if ($this->style['runningTimeFormat'] == 0) { // 3'45"
            if (isset($minutes) && $minutes) {
                if ($minutes < 10) {
                    $minutes = '0' . $minutes;
                }
                $runningTime = $hours . "'" . $minutes . '"';
            } else {
                $runningTime = $hours . "'00\"";
            }
        } elseif ($this->style['runningTimeFormat'] == 1) { // 3:45
            if (isset($minutes) && $minutes) {
                if ($minutes < 10) {
                    $minutes = '0' . $minutes;
                }
                $runningTime = $hours . ':' . $minutes;
            } else {
                $runningTime = $hours . ':00';
            }
        } elseif ($this->style['runningTimeFormat'] == 1) { // 3,45
            if (isset($minutes) && $minutes) {
                if ($minutes < 10) {
                    $minutes = '0' . $minutes;
                }
                $runningTime = $hours . ',' . $minutes;
            } else {
                $runningTime = $hours . ',00';
            }
        } elseif ($this->style['runningTimeFormat'] == 3) { // 3 hours, 45 minutes
            $hours = ($hours == 1) ? $hours . ' hour' : $hours . ' hours';
            if (isset($minutes) && $minutes) {
                $minutes = ($minutes == 1) ? $minutes . ' minute' : $minutes . ' minutes';
                $runningTime = $hours . ', ' . $minutes;
            } else {
                $runningTime = $hours;
            }
        } elseif ($this->style['runningTimeFormat'] == 4) { // 3 hours and 45 minutes
            $hours = ($hours == 1) ? $hours . ' hour' : $hours . ' hours';
            if (isset($minutes) && $minutes) {
                $minutes = ($minutes == 1) ? $minutes . ' minute' : $minutes . ' minutes';
                $runningTime = $hours . ' and ' . $minutes;
            } else {
                $runningTime = $hours;
            }
        }
        $this->item[$this->styleMap->getDynamicPropertyArrayElement($type, 'runningTime')] = $runningTime;
    }

    /**
    * Format date
    *
    * @author Mark Grimshaw
    * @version 2
    *
    * @param int|bool $startDay
    * @param int|bool $startMonth
    * @param int|bool $endDay
    * @param int|bool $endMonth
    */
    public function formatDate($startDay, $startMonth, $endDay, $endMonth)
    {
        $type = $this->type;
        $oldStartDay = $startDay;
        $oldEndDay = $endDay;
        if ($this->dateMonthNoDay && !$startDay && !$endDay) {
            $this->$type[$this->styleMap->getDynamicPropertyArrayElement($type, 'date')] =
            $this->dateArray[$this->styleMap->getDynamicPropertyArrayElement($type, 'date')];
        }
        if ($startDay !== false) {
            if ($this->style['dayFormat'] == 1) { // e.g. 10.
                $startDay .= '.';
            } elseif ($this->style['dayFormat'] == 2) { // e.g. 10th
                $startDay = $this->cardinalToOrdinal($startDay, 'dayMonth');
            }
            if (array_key_exists('dayLeadingZero', $this->style) && $oldStartDay < 10) {
                $startDay = '0' . $startDay;
            }
        }
        if ($endDay !== false) {
            if ($this->style['dayFormat'] == 1) { // e.g. 10.
                $endDay .= '.';
            } elseif ($this->style['dayFormat'] == 2) { // e.g. 10th
                $endDay = $this->cardinalToOrdinal($endDay, 'dayMonth');
            }
            if (array_key_exists('dayLeadingZero', $this->style) && $oldEndDay < 10) {
                $endDay = '0' . $endDay;
            }
        }
        if ($this->style['monthFormat'] == 1) { // Full month name
            $monthArray = $this->longMonth;
        } elseif ($this->style['monthFormat'] == 2) { // User-defined
            for ($i = 1; $i <= 12; $i++) {
                $monthArray[$i] = $this->style["userMonth_$i"];
            }
        } else { // Short month name
            $monthArray = $this->shortMonth;
        }
        if ($startMonth !== false) {
            $startMonth = $monthArray[$startMonth];
        }
        if ($endMonth !== false) {
            $endMonth = $monthArray[$endMonth];
        }
        if (!$endMonth) {
            if ($this->style['dateFormat']) { // Order == Month Day
                $startDay = ($startDay === false) ? '' : ' ' . $startDay;
                $date = $startMonth . $startDay;
            } else { // Order == Day Month
                $startDay = ($startDay === false) ? '' : $startDay . ' ';
                $date = $startDay . $startMonth;
            }
        } else { // date range
            if (!$startDay) {
                $delimit = $this->style['dateRangeDelimit2'];
            } else {
                $delimit = $this->style['dateRangeDelimit1'];
            }
            if (($endMonth !== false) && ($startMonth == $endMonth) && ($this->style['dateRangeSameMonth'] == 1)) {
                $endMonth = false;
                if (!$endDay) {
                    $delimit = false;
                }
            }
            if ($this->style['dateFormat']) { // Order == Month Day
                $startDay = ($startDay === false) ? '' : ' ' . $startDay;
                $startDate = $startMonth . $startDay;
                if ($endMonth) {
                    $endDate = $endMonth . $endDay = ($endDay === false) ? '' : ' ' . $endDay;
                } else {
                    $endDate = $endDay;
                }
            } else { // Order == Day Month
                if ($endMonth) {
                    $startDate = $startDay . ' ' . $startMonth;
                    $endDate = $endDay = ($endDay === false) ? '' : $endDay . ' ';
                    $endDate .= $endMonth;
                } else {
                    $startDate = $startDay;
                    $endDate = ($endDay === false) ? ' ' : $endDay . ' ';
                    $endDate .= $startMonth;
                }
            }
            $date = $startDate . $delimit . $endDate;
        }
        $this->item[$this->styleMap->getDynamicPropertyArrayElement($type, 'date')] = $date;
    }

    /**
     * Format edition
     *
     * @author Mark Grimshaw
     * @version 1
     *
     * @param int|mixed $edition
     */
    public function formatEdition($edition): void
    {
        $type = $this->type;
        if (!is_numeric($edition)) {
            $edition = $edition;
        } elseif ($this->style['editionFormat'] == 1) { // 10.
            $edition .= '.';
        } elseif ($this->style['editionFormat'] == 2) { // 10th
            $edition = $this->cardinalToOrdinal($edition, 'edition');
        }
        $this->item[$this->styleMap->getDynamicPropertyArrayElement(
            $type,
            array_search(
                'edition',
                $this->styleMap->getDynamicProperty($type)
            )
        )] = $edition;
    }

    /**
     * Create ordinal number from cardinal
     *
     * @author Mark Grimshaw
     * @version	1
     *
     * @param string $cardinal
     * @param bool|mixed $field
     * @return string ordinal
     */
    public function cardinalToOrdinal(string $cardinal, $field = false): string
    {
        // WIKINDX-specific
        if ($this->wikindx && method_exists($this->wikindxLanguageClass, 'cardinalToOrdinal')) {
            return $this->wikindxLanguageClass->cardinalToOrdinal($cardinal, $field);
        }
        $modulo = $cardinal % 100;
        if (($modulo == 11) || ($modulo == 12) || ($modulo == 13)) {
            return $cardinal . 'th';
        }
        $modulo = $cardinal % 10;
        if (($modulo >= 4) || !$modulo) {
            return $cardinal . 'th';
        }
        if ($modulo == 1) {
            return $cardinal . 'st';
        }
        if ($modulo == 2) {
            return $cardinal . 'nd';
        }
        if ($modulo == 3) {
            return $cardinal . 'rd';
        }
        return '';
    }

    /**
     * Localisations etc.
     * @author Mark Grimshaw
     * @version 1
     */
    public function loadArrays(): void
    {
        // WIKINDX-specific
        if ($this->wikindx) {
            $languageDir = $this->style['localisation'];
            if (!is_dir("languages/$languageDir")) {
                $languageDir = 'en';
            }
            include_once("languages/$languageDir/CONSTANTS.php");
            $class = 'CONSTANTS_' . $languageDir;
            $this->wikindxLanguageClass = new $class();
            if (isset($this->wikindxLanguageClass->titleSubtitleSeparator)
                && method_exists($this->wikindxLanguageClass, 'monthToLongName')
                && method_exists($this->wikindxLanguageClass, 'monthToShortName')) {
                $this->titleSubtitleSeparator = $this->wikindxLanguageClass->titleSubtitleSeparator;
                $this->longMonth = $this->wikindxLanguageClass->monthToLongName();
                $this->shortMonth = $this->wikindxLanguageClass->monthToShortName();
                return;
            }
        }
        // Defaults
        $this->longMonth = [
                1  => 'January',
                2  => 'February',
                3  => 'March',
                4  => 'April',
                5  => 'May',
                6  => 'June',
                7  => 'July',
                8  => 'August',
                9  => 'September',
                10 => 'October',
                11 => 'November',
                12 => 'December',
            ];
        $this->shortMonth = [
                1  => 'Jan',
                2  => 'Feb',
                3  => 'Mar',
                4  => 'Apr',
                5  => 'May',
                6  => 'Jun',
                7  => 'Jul',
                8  => 'Aug',
                9  => 'Sep',
                10 => 'Oct',
                11 => 'Nov',
                12 => 'Dec',
            ];
        $this->titleSubtitleSeparator = ': ';
    }

    /**
     * convertEntry - convert any laTeX code and convert to UTF-8 ready for storing in the database (betex only)
     *
     * @author Mark Grimshaw, modified by Christophe Ambroise 26/10/2003
     * @param array $entry - a bibtex entry
     * @return array $entry converted to utf8
     */
    public function convertEntry(array $entry): array
    {
        $replaceBibtex = [];
        $matchBibtex = [];

        include_once(__DIR__ . '/BIBTEXCONFIG.php');
        $this->config = new BIBTEXCONFIG();
        $this->config->bibtex();
        // Construction of the transformation filter
        foreach ($this->config->getBibtexSpCh() as $key => $value) {
            $replaceBibtex[] = chr($key);
            $matchBibtex[] = '/' . preg_quote("$value", '/') . '/';
        }
        foreach ($this->config->getBibtexSpChOld() as $key => $value) {
            $replaceBibtex[] = chr($key);
            $matchBibtex[] = '/' . preg_quote("$value", '/') . '/';
        }
        foreach ($this->config->getBibtexSpChOld2() as $key => $value) {
            $replaceBibtex[] = chr($key);
            $matchBibtex[] = '/' . preg_quote("$value", '/') . '/';
        }
        foreach ($this->config->getBibtexSpChLatex() as $key => $value) {
            $replaceBibtex[] =  chr($key);
            $matchBibtex[] = '/' . preg_quote("$value", '/') . '/';
        }
        // Processing of the entry
        foreach ($entry as $key => $value) {
            // The transformation filter  has returned  latin1 code
            // We thus need to work with latin1.
            $value= $this->utf8->smartUtf8_decode($value);
            $entry[$key] = utf8_encode(preg_replace($matchBibtex, $replaceBibtex, $value));
        }
        return $entry;
    }
}
