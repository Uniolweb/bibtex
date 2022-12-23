<?php
/**********************************************************************************
WIKINDX: Bibliographic Management system.
Copyright (C)

This program is free software; you can redistribute it and/or modify it under the terms
of the GNU General Public License as published by the Free Software Foundation; either
version 2 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with this program;
if not, write to the
Free Software Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA

The WIKINDX Team 2005
sirfragalot@users.sourceforge.net
**********************************************************************************/

/**
 * BIBLIOGRAPHY STYLE class
 * Format a resource for a bibliographic style.
 *
 * $Header: /cvsroot/bibliophile/OSBib/format/BIBSTYLE.php,v 1.2 2005/10/16 03:49:34 sirfragalot Exp $
 *
 * @todo requires core classes, extract into separate package?
 * @deprecated requires core classes, should be extracted into separate package
 */
class BIBSTYLE
{
    /**
     * @var mixed|null
     * @todo what type is $db?
     */
    protected $db;

    protected bool $shortOutput = false;
    protected bool $pages = false;

    protected string $output = '';
    protected string $setupStyle = '';

    protected array $row = [];

    protected ?BIBFORMAT $bibformat = null;
    protected ?MISC $misc = null;
    protected ?SESSION $session = null;

    /**
     * @param $db
     * @param string $output
     * @param bool $export
     * @param string $style
     *
     * @todo what type is $db???
     */
    public function __construct($db, string $output, bool $export = false, string $style = '')
    {
        $this->db = $db;
        // Is our database based on BibTeX and using the same field names?
        // @todo we just use the SESSION.php class in this package, no idea if this is correct ...
        //include_once('core/session/SESSION.php');
        include_once(__DIR__ . '/create/SESSION.php');
        $this->session = new SESSION();
        // @todo we just use the BIBFORMAT.php class in this package, no idea if this is correct ...
        //include_once('core/styles/BIBFORMAT.php');
        include_once(__DIR__ . '/BIBFORMAT.php');
        $this->bibformat = new BIBFORMAT();
        $this->output = $output;
        $this->bibformat->setOutput($output);
        // WIKINDX-specific
        $this->bibformat->setWikindx(true);
        /**
        * CSS class for highlighting search terms
        */
        $this->bibformat->setPatternHighlight('highlight');

        // @todo this class does not exist here!
        // @todo we just use the BIBFORMAT.php class in this package, no idea if this is correct ...
        //include_once('core/html/MISC.php');
        include_once(__DIR__ . '/create/MISC.php');
        $this->misc = new MISC();
        // get the bibliographic style
        if ($style) {
            $this->setupStyle = $style;
        } else {
            if ($export) {
                if ($output == 'rtf') {
                    $this->setupStyle = $this->session->getVar('exportPaper_style');
                }
            } else {
                if ($output == 'rtf') {
                    $this->setupStyle = $this->session->getVar('exportRtf_style');
                }
            }
            if (!isset($this->setupStyle)) {
                $this->setupStyle = $this->session->getVar('setup_style');
            }
        }
        /**
        * If our style arrays do not exist in session, parse the style file and write to session.  Loading and
        * parsing the XML file takes about 0.1 second (P4 system) and so is a significant slowdown.
        * Try to do this only once every time we use a style.  NB. these are saved in session with 'cite_' and 'style_'
        * prefixes - creating/copying or editing a bibliographic style clears these arrays from the session which will
        * force a reload of the style here.
        */
        $styleInfo = $this->session->getVar('style_name');

        /**
         * @todo get rid of unserialize
         * "Warning: Do not pass untrusted user input to unserialize() regardless of the options value of
         *   allowed_classes. Unserialization can result in code being loaded and executed due to object instantiation
         *   and autoloading, and a malicious user may be able to exploit this. Use a safe, standard data interchange
         *   format such as JSON (via json_decode() and json_encode()) if you need to pass serialized data to the user."
         *  https://www.php.net/manual/en/function.unserialize.php
         */
        $styleCommon = unserialize(base64_decode($this->session->getVar('style_common')));
        //print "$styleInfo:  "; print_r($styleCommon); print "<P>";
        $styleTypes = unserialize(base64_decode($this->session->getVar('style_types')));
        // File not yet parsed or user's choice of style has changed so need to
        // load, parse and store to session
        if ((!$styleInfo || !$styleCommon || !$styleTypes)
            || ($styleInfo != $this->setupStyle)) {
            list($info, $citation, $styleCommon, $styleTypes) =
                $this->bibformat->loadStyle('styles/bibliography/', $this->setupStyle);
            $this->session->setVar('style_name', $info['name']);
            $this->session->setVar('cite_citation', base64_encode(serialize($citation)));
            $this->session->setVar('style_common', base64_encode(serialize($styleCommon)));
            $this->session->setVar('style_types', base64_encode(serialize($styleTypes)));
            $this->session->delVar('style_edited');
        }
        unset($this->session, $info, $citation);
        $this->bibformat->getStyle($styleCommon, $styleTypes);
        unset($styleCommon, $styleTypes);
    }

    public function getBibFormat(): BIBFORMAT
    {
        return $this->bibformat;
    }

    /**
     * Accept a SQL result row of raw bibliographic data and process it.
     * We build up the $bibformat->item array with formatted parts from the raw $row
     * @return string|array|null
     */
    public function process(array $row, bool $shortOutput = false)
    {
        $this->row = $row;
        $this->shortOutput = $shortOutput;
        $type = $row['type']; // WIKINDX type
        unset($row);
        // For WIKINDX, if type == book or book article and there exists both 'year1' and 'year2' in $row (entered as
        // publication year and reprint year respectively), then switch these around as 'year1' is
        // entered in the style template as 'originalPublicationYear' and 'year2' should be 'publicationYear'.
        if (($type == 'book') || ($type == 'book_article')) {
            $year2 = stripslashes($this->row['year2']);
            if ($year2 && !$this->row['year1']) {
                $this->row['year1'] = $year2;
                unset($this->row['year2']);
            } elseif ($year2 && $this->row['year1']) {
                $this->row['year2'] = stripslashes($this->row['year1']);
                $this->row['year1'] = $year2;
            }
        }
        $this->row = $this->bibformat->preProcess($type, $this->row);
        // Return $type is the OSBib resource type ($this->book, $this->web_article etc.) as used in STYLEMAP
        $type = $this->bibformat->getType();
        $this->preProcess($type);
        // We now have an array for this item where the keys match the key names of $this->styleMap->$type
        // where $type is book, journal_article, thesis etc. and are now ready to map this against the defined
        // bibliographic style for each resource ($this->book, $this->book_article etc.).
        // This bibliographic style array not only provides the formatting and punctuation for each field but also
        // provides the order. If a field name does not exist in this style array, we print nothing.
        $pString = $this->bibformat->map();
        // bibTeX ordinals such as 5$^{th}$
        $pString = preg_replace_callback("/(\d+)\\$\^\{(.*)\}\\$/", [$this, 'ordinals'], $pString);
        // remove extraneous {...}
        return preg_replace('/{(.*)}/U', '$1', $pString);
    }

    /**
     * Perform some pre-processing
     */
    public function preProcess(string $type): void
    {
        // Various types of creator
        for ($index = 1; $index <= 5; $index++) {
            if ($this->shortOutput && ($index > 1)) {
                break;
            }
            if (!$this->row['creator' . $index] ||
                !array_key_exists('creator' . $index, $this->bibformat->getStyleMap()->getDynamicProperty($type))) {
                continue;
            }
            if (array_key_exists('creator' . $index, $this->bibformat->getStyleMap()->getDynamicProperty($type))) {
                $this->grabNames('creator' . $index);
            }
        }
        // The title of the resource
        $this->createTitle();
        if (!$this->shortOutput) {
            // edition
            if ($editionKey = array_search('edition', $this->bibformat->getStyleMap()->getDynamicProperty($type))) {
                $this->createEdition($editionKey);
            }
            // pageStart and pageEnd
            $this->pages = false; // indicates not yet created pages for articles
            if (array_key_exists('pages', $this->bibformat->getStyleMap()->$type)) {
                $this->createPages();
            }
            // Date
            if (array_key_exists('date', $this->bibformat->getStyleMap()->$type)) {
                $this->createDate();
            }
            // runningTime for film/broadcast
            if (array_key_exists('runningTime', $this->bibformat->getStyleMap()->$type)) {
                $this->createRunningTime();
            }
            // web_article URL
            if (array_key_exists('URL', $this->bibformat->getStyleMap()->$type) &&
                ($itemElement = $this->createUrl())) {
                $this->bibformat->addItem($itemElement, 'URL');
            }
            // proceedings_article can have publisher as well as organiser/location. Publisher is in 'miscField1'
            if (($type == 'proceedings_article') && $this->row['miscField1']) {
                $recordset = $this->db->select(
                    ['WKX_publisher'],
                    ['publisherName', 'publisherLocation'],
                    ' WHERE ' . $this->db->formatField('id') . '=' .
                    $this->db->tidyInput($this->row['miscField1'])
                );
                $pubRow = $this->db->fetchRow($recordset);
                if ($pubRow['publisherName']) {
                    $this->bibformat->addItem($pubRow['publisherName'], 'publisher');
                }
                if ($pubRow['publisherLocation']) {
                    $this->bibformat->addItem($pubRow['publisherLocation'], 'location');
                }
            }
            // the rest...  All other database resource fields that do not require special formatting/conversion.
            $this->bibformat->addAllOtherItems($this->row);
        }
        // Add the publication year for short output.
        elseif (array_key_exists('year1', $this->bibformat->getStyleMap()->$type) && $this->row['year1']) {
            $this->bibformat->addItem($this->row['year1'], 'year1');
        }
    }

    /**
     * callback for ordinals above
     */
    public function ordinals(array $matches): string
    {
        if (!isset($matches[1]) || !isset($matches[2])) {
            return '';
        }

        if ($this->output == 'html') {
            return $matches[1] . '<sup>' . $matches[2] . '</sup>';
        }
        if ($this->output == 'rtf') {
            return $matches[1] . "{{\up5 " . $matches[2] . '}}';
        }
        return $matches[1] . $matches[2];
    }

    /**
     * Create the resource title
     */
    public function createTitle(): void
    {
        $pString = stripslashes($this->row['noSort']) . ' ' .
            stripslashes($this->row['title']);
        if ($this->row['subtitle']) {
            $pString .= $this->bibformat->getTitleSubtitleSeparator() . stripslashes($this->row['subtitle']);
        }
        // anything enclosed in {...} is to be left as is
        $this->bibformat->formatTitle($pString, '{', '}');
    }

    /**
     * Create the URL
     * @todo Accessing MISC::a will fail miserably, if a is not a static function !!!
     *   this class includes a class MISC which is not in the package so we do not know
     *   what type MISC is
     */
    public function createUrl(): string
    {
        if (!$this->row['url']) {
            return '';
        }
        $url = ($this->output == 'html') ? htmlspecialchars(stripslashes($this->row['url'])) :
            stripslashes($this->row['url']);
        unset($this->row['url']);
        if ($this->output == 'html') {
            return $this->misc->a('rLink', $url, $url, '_blank');
        }

        return $url;
    }
// Create date
    public function createDate()
    {
        $startDay = isset($this->row['miscField2']) ? stripslashes($this->row['miscField2']) : false;
        $startMonth = isset($this->row['miscField3']) ? stripslashes($this->row['miscField3']) : false;
        unset($this->row['miscField2']);
        unset($this->row['miscField3']);
        $endDay = isset($this->row['miscField5']) ? stripslashes($this->row['miscField5']) : false;
        $endMonth = isset($this->row['miscField6']) ? stripslashes($this->row['miscField6']) : false;
        unset($this->row['miscField5']);
        unset($this->row['miscField6']);
        $startDay = ($startDay == 0) ? false : $startDay;
        $startMonth = ($startMonth == 0) ? false : $startMonth;
        if (!$startMonth) {
            return;
        }
        $endDay = ($endDay == 0) ? false : $endDay;
        $endMonth = ($endMonth == 0) ? false : $endMonth;
        $this->bibformat->formatDate($startDay, $startMonth, $endDay, $endMonth);
    }
// Create runningTime for film/broadcast
    public function createRunningTime()
    {
        $minutes = stripslashes($this->row['miscField1']);
        $hours = stripslashes($this->row['miscField4']);
        if (!$hours && !$minutes) {
            return;
        }
        if (!$hours) {
            $hours = 0;
        }
        $this->bibformat->formatRunningTime($minutes, $hours);
    }
// Create the edition number
    public function createEdition($editionKey)
    {
        if (!$this->row[$editionKey]) {
            return false;
        }
        $edition = stripslashes($this->row[$editionKey]);
        $this->bibformat->formatEdition($edition);
    }

    /**
     * Create page start and page end
     */
    public function createPages()
    {
        // empty field or page format already done
        if (!$this->row['pageStart'] || $this->pages) {
            $this->pages = true;
            return;
        }
        $this->pages = true;
        $start = trim(stripslashes($this->row['pageStart']));
        $end = $this->row['pageEnd'] ? trim(stripslashes($this->row['pageEnd'])) : false;
        $this->bibformat->formatPages($start, $end);
    }

    /**
     * get names from database for creator, editor, translator etc.
     */
    public function grabNames(string $nameType): bool
    {
        /** @var array $conditions */
        $conditions = [];

        $nameIds = mb_split(',', $this->row[$nameType]);
        foreach ($nameIds as $nameId) {
            $conditions[] = $this->db->formatField('id') . '=' . $this->db->tidyInput($nameId);
        }
        $recordset = $this->db->select(
            ['WKX_creator'],
            ['surname', 'firstname',
            'initials', 'prefix', 'id'],
            ' WHERE ' . implode(' OR ', $conditions)
        );
        /**
         * @todo $numNames is not used here
         */
        $numNames = $this->db->numRows($recordset);

        // Reorder $row so that creator order is correct and not that returned by SQL
        $ids = explode(',', $this->row[$nameType]);
        while ($row = $this->db->loopRecordSet($recordset)) {
            $rowSql[$row['id']] = $row;
        }
        if (!isset($rowSql)) {
            return false;
        }
        foreach ($ids as $id) {
            $rowTemp[] = $rowSql[$id];
        }
        $this->bibformat->formatNames($rowTemp, $nameType);
        return true;
    }

    /**
     * bad Input function
     */
    public function badInput($error): void
    {
        //include_once('core/html/CLOSE.php');
        include_once(__DIR__ . '/create/CLOSE.php');
        new CLOSE($this->db, $error);
    }
}
