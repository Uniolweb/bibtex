<?php

declare(strict_types=1);

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
 * CITATION STYLE class
 * Format citations.
 *
 * For non-WIKINDX users, you must have set up BIBFORMAT before using this (see documentation for BIBFORMAT()).
 *
 * $Header: /cvsroot/bibliophile/OSBib/format/CITESTYLE.php,v 1.5 2005/11/03 06:34:58 sirfragalot Exp $
 *
 * @todo requires core classes, extract into separate package?
 * @todo Remove the phpstan-ignore-next-line, if this can be resolved (most of them due to missing classes)
 * @deprecated requires core/ classes, which do not exist in this package
 */
class CITESTYLE
{
    /** @todo what data type?  */
    protected $db;

    protected string $setupStyle = '';
    protected string $output = '';
    protected string $tailText = '';

    protected array $rowSingle = [];
    protected array $preText = [];
    protected array $postText = [];
    protected array $matches = [];
    protected array $citeIds = [];
    protected array $items = [];
    protected array $row = [];
    protected array $pageStart = [];
    protected array $pageEnd = [];

    protected ?CITEFORMAT $citeformat = null;
    /** we do not really now the type here, an unknown class SESSION is included  */
    protected ?SESSION $session = null;

    /**
     * $output is 'html' or 'rtf'
     */
    public function __construct($db, string $output, bool $rtfBibExport = false)
    {
        $this->db = $db;
        include_once('core/session/SESSION.php');
        $this->session = new SESSION();
        // Get the bibliographic style.  These session variables are set in WIKINDX's BIBSTYLE.php - other systems will need similar code to load an XML style file.
        if ($rtfBibExport) {
            $this->setupStyle = $this->session->getVar('exportRtf_style');
        } elseif ($output == 'rtf') {
            $this->setupStyle = $this->session->getVar('exportPaper_style');
        } else {
            $this->setupStyle = $this->session->getVar('setup_style');
        }
        // BIBSTYLE.php is used by WIKINDX to set up the system for BIBFORMAT.php which is the bibliographic formatting engine.  Although CITESTYLE initialises
        // CITEFORMAT (the OSBib citation engine), BIBFORMAT is required for the appending of bibliographies to the text containing citations.
        // Your system will need something similar to BIBSTYLE (see documentation for BIBFORMAT()).
        include_once('core/styles/BIBSTYLE.php');
        $bibStyle = new BIBSTYLE($this->db, $output, true, $this->setupStyle);
        include_once('core/styles/CITEFORMAT.php');
        // Pass the bibstyle object to CITEFORMAT() as the first argument.
        // The second argument is the name of the method within the bibstyle object that starts the formatting of a bibliographic item.  WIKINDX uses process().
        // The third argument is the directory for STYLEMAP.php, PARSEXML.php and PARSESTYLE.php.  If FALSE, they're in the same directory as CITEFORMAT.php.
        // The fourth argument is specific to WIKINDX.

        // @todo using unknown class here, and has different constructor from CITEFORMAT in this package
        // @phpstan-ignore-next-line
        $this->citeformat = new CITEFORMAT($bibStyle, 'process', false, 'core/messages/');
        $this->output = $output;
        $this->citeformat->setOutput($output);
        include_once('core/html/MISC.php');
        /**
        * WIKINDX specific:
        * If our style arrays do not exist in session, parse the style file and write to session.  Loading and
        * parsing the XML file takes about 0.1 second (P4 system) and so is a significant slowdown.
        * Try to do this only once every time we use a style.  NB. these are saved in session with 'cite_' and 'style_'
        * prefixes - creating/copying or editing a bibliographic style clears these arrays from the session which will
        * force a reload of the style here.
        *
        * For WIKINDX, XML style files are stored in "styles/bibliography/"
        *
        * Non-WIKINDX users will need to load the XML style file using whatever method they deem appropriate.
        */
        $this->citeformat->setWikindx(true); // default is FALSE
        $citation = unserialize(base64_decode($this->session->getVar('cite_citation')));
        $footnote = unserialize(base64_decode($this->session->getVar('cite_footnote')));
        $this->citeformat->getStyle($citation, $footnote);
        unset($citation, $footnote); // clear memory as these not needed here.
        // END WIKINDX-specific
        // Must be initialised.
        $this->pageStart = $this->pageEnd = $this->preText = $this->postText = $this->citeIds = [];
    }

    /**
     * Start the whole process off by finding [cite]...[/cite] tags in input text.
     * WIKINDX uses [cite]34[/cite] or [cite]34:23[/cite] or [cite]34:23-24[/cite] where '34' is the resource's unique
     * ID, '23' is a single page for the citation and '23-24' is a page range for the citation.  If your system uses
     * something else, you will need to make changes here and in $this->parseCiteTag(). PreText and postText can also
     * be encoded: e.g. (see Grimshaw 2003; Boulanger 2004 for example)
     * [cite]23:34-35|see ` for example[/cite]
     */
    public function start(string $text, string $citeLink): string
    {
        // Turn on hyperlinking for html output of the citation references within the text.
        // The unique resource ID in the database is appended to this string.  The default in $this->citeformat is FALSE meaning no hyperlinking.
        if ($citeLink) {
            $this->citeformat->setHyperlinkBase('index.php?action=resourceView&amp;id=');
        }
        // Capture any text after last [cite]...[/cite] tag
        $explode = explode(']etic/[', strrev($text), 2);
        $this->tailText = strrev($explode[0]);
        $text = strrev(']etic/[' . $explode[1]);
        preg_match_all("/(.*)\s*\[cite\](.*)\[\/cite\]/Uis", $text, $match);
        foreach ($match[1] as $value) {
            if ($this->output == 'html') { // WIKINDX metadata stored in db with <br />
                $this->matches[1][] = $value;
            } else {
                $this->matches[1][] = rtrim(str_replace('<br />', '', $value));
            }
        }
        $this->citeformat->setCount(0);
        foreach ($match[2] as $index => $value) {
            $citeFormatCount = $this->citeformat->getCount();
            $citeFormatCount++;
            $this->citeformat->setCount($citeFormatCount);
            if ($id = $this->parseCiteTag($index, str_replace('<br />', '', $value))) {
                $this->citeIds[] = $id;
            }
        }
        // If empty($this->citeIds), there are no citations to scan for (or user has entered invalid IDs) so return $text unchanged.
        if (empty($this->citeIds)) {
            return $text;
        }
        // Get appended bibliographies.  $bibliography is a multiple array of raw bibliographic data from the database suitable for passing to BIBFORMAT.php.
        $bibliography = $this->bibliographyProcess();
        /*
        * $matches[1]is an array of $1 above
        * $matches[2] is an array of $2 (the citation references)
        * e.g.
        * [1] => Array ( [0] => First [1] => [2] => [3] => [4] => blah blah see ) [2] => Array ( [0] => 1 [1] => 2 [2] => 3 [3] => 4 [4] => 2 )
        * might represent:
        * First [cite]1[/cite] [cite]2[/cite] [cite]3[/cite]
        * [cite]1[/cite] blah blah see[cite]2[/cite]
        *
        * Note that having both [1][0] and [2][0] populated means that the citation reference [2][0] _follows_ the text in [1][0].
        * Any unpopulated elements of matches[1] indicates multiple citations at that point.  e.g., in the example above,
        * there are multiple citations (references 1, 2, 3 and 4) following the text 'First' and preceeding the text 'blah blah see'.
        *
        * N.B. the preg_match_all() above does not capture any text after the final citation so this must be handled manually and appended to any final output -
        * this is $this->tailText above.
        */
        /** @todo $this->row is never used? */
        $this->row = [];
        $this->citeformat->setCount(0);
        $citeIndex = 0;
        while (!empty($this->matches[1])) {
            $this->citeformat->resetItem(); // must be reset each time.
            $id = $this->citeIds[$citeIndex];
            ++$citeIndex;
            $citeFormatCount = $this->citeformat->getCount();
            $citeFormatCount++;
            $this->citeformat->setCount($citeFormatCount);
            $text = array_shift($this->matches[1]);
            $this->citeformat->setItemId($id);
            $this->createPrePostText(array_shift($this->preText), array_shift($this->postText));
            // For each element of $bibliography, process title, creator names etc.
            if (array_key_exists($id, $bibliography)) {
                $this->process($bibliography[$id], $id);
            }
            // $this->rowSingle is set in $this->process().  'type' is the type of resource (book, journal article etc.).  In WIKINDX, this is part of the row returned by SQL:  you may
            // need to set this manually if this is not the case for your system.  'type' is used in CITEFORMAT::prependAppend() to add any special strings to the citation within
            // the text (e.g. the XML style file might state that 'Personal communication: ' needs to be appended to any in-text citations for resources of type 'email'.
            // CITEFORMAT::prependAppend() will map 'type' against the $types array in STYLEMAP as used in BIBFORMAT.
            // @todo process this in CITEFORMAT

            /*
            $citeFormatCount = $this->citeformat->getCount();
            $citeFormatItems = $this->citeformat->getItems();
            $citeFormatItems[$citeFormatCount]['type'] = $this->rowSingle['type'];
            $citeFormatItems[$citeFormatCount]['text'] = $text;
            */
            $this->citeformat->setItemType($this->rowSingle['type']);
            $this->citeformat->setItemText($text);
        }
        $pString = $this->citeformat->process() . $this->tailText;
        // bibTeX ordinals such as 5$^{th}$
        $pString = preg_replace_callback("/(\d+)\\$\^\{(.*)\}\\$/", [$this, 'ordinals'], $pString);
        // WIKINDX-specific:  Line spacing of main paper body
        if ($this->output == 'rtf') {
            if ($this->session->getVar('exportPaper_spacePaper') == 'oneHalfSpace') {
                $pString = "\\pard\\plain \\sl360\\slmult1\n$pString";
            } elseif ($this->session->getVar('exportPaper_spacePaper') == 'doubleSpace') {
                $pString = "\\pard\\plain \\sl480\\slmult1\n$pString";
            }
        }
        // Endnote-style citations so add the endnotes bibliography
        if ($this->citeformat->getStyleArray()['citationStyle']) {
            // Turn off hyperlinking for the appended bibliography
            $this->citeformat->setHyperlinkBase('');
            $pString = $this->citeformat->printEndnoteBibliography($pString);
            if ($this->citeformat->getStyleArray()['endnoteStyle'] != 2) { // Not footnotes.
                return $pString;
            }
        }
        // In-text citations and footnotes - output the appended bibliography
        $bib = $this->printBibliography($bibliography);
        if ($this->output == 'rtf') {
            // WIKINDX-specific:  Indentation of appended bibliography
            if ($this->session->getVar('exportPaper_indentBib') == 'indentAll') {
                $bib = "\\li720\n$bib";
            } elseif ($this->session->getVar('exportPaper_indentBib') == 'indentFL') {
                $bib = "\\fi720\n$bib";
            } elseif ($this->session->getVar('exportPaper_indentBib') == 'indentNotFL') {
                $bib = "\\li720\\fi-720\n$bib";
            } else {
                $bib = "\\li1\\fi1\n$bib";
            }
            // WIKINDX-specific:  Line spacing of appended bibliography
            if ($this->session->getVar('exportPaper_spaceBib') == 'oneHalfSpace') {
                $bib = "\\pard\\plain \\sl360\\slmult1\n$bib";
            } elseif ($this->session->getVar('exportPaper_spaceBib') == 'doubleSpace') {
                $bib = "\\pard\\plain \\sl480\\slmult1\n$bib";
            } else {
                $bib = "\\pard\\plain $bib";
            }
            $bib = "\par\n\n$bib";
        }
        return $pString . $bib;
    }

    /**
     * Gather bibliography of citations.  The order is important:
     * 1. for in-text citations as it controls disambiguation where a letter is added after the year.
     * 2. for endnote-style citations, if your bibliographic style says that a resource following another by the same
     *    creator(s) should have the creator(s) replaced by
     *    something like '_______', order is again important.
     * 3. for endnote-style citations having the same id no. for the same resource where the id no. in the text follows
     *   the bibliography order.
     */
    public function bibliographyProcess(): array
    {
        $rows = [];
        $ids = [];

        include_once('core/sql/STATEMENTS.php');
        // @phpstan-ignore-next-line
        $stmt = new STATEMENTS($this->db);
        // The database resource IDs are the values of $this->citeformat->ids.
        foreach (array_unique($this->citeIds) as $id) {
            $ids[] = $this->db->tidyInput($id);
        }
        // Get the requested order - three orders available
        $join = 'LEFT JOIN ' . $this->db->formatTable('WKX_creator') . ' ON ' .
        $this->db->formatfield('WKX_creator.id') . '=' . $this->db->formatField('creator1') . ' ';
        $ascDesc = ' ASC'; // default ascending
        $style = $this->citeformat->getStyleArray();
        // 1st order
        if ($style['order1desc']) { // descending (default 0 = ascending)
            $ascDesc = ' DESC';
        }
        if ($style['order1'] == 1) { // publication year
            $order1 = ' CASE WHEN (' . $this->db->formatField('type') . '=' .
                        $this->db->tidyInput('book') .
                        ' OR ' . $this->db->formatField('type') . '=' .
                        $this->db->tidyInput('book_article') .
                        ') AND ' . $this->db->formatField('year2') . ' IS NOT NULL ' .
                        ' THEN ' . $this->db->tidyInputClause('year2') .
                    ' ELSE ' .
                        $this->db->tidyInputClause('year1') .
                    ' END' . $ascDesc;
        } elseif ($style['order1'] == 2) { // title
            $order1 = $this->db->tidyInputClause('title') . $ascDesc;
        } else { // default: by creator
            $order1 = $this->db->tidyInputClause('surname') . $ascDesc .
                ', ' . $this->db->tidyInputClause('firstname') . $ascDesc;
        }
        // 2nd order
        $ascDesc = ' ASC'; // default ascending
        if ($this->citeformat->getStyleArray()['order2desc']) { // descending (default 0 = ascending)
            $ascDesc = ' DESC';
        }
        if ($style['order2'] == 1) { // publication year
            $order2 = ' CASE WHEN (' . $this->db->formatField('type') . '=' .
                        $this->db->tidyInput('book') .
                        ' OR ' . $this->db->formatField('type') . '=' .
                        $this->db->tidyInput('book_article') .
                        ') AND ' . $this->db->formatField('year2') . ' IS NOT NULL ' .
                        ' THEN ' . $this->db->tidyInputClause('year2') .
                    ' ELSE ' .
                        $this->db->tidyInputClause('year1') .
                    ' END' . $ascDesc;
        } elseif ($style['order2'] == 2) { // title
            $order2 = $this->db->tidyInputClause('title') . $ascDesc;
        } else { // default: by creator
            $order2 = $this->db->tidyInputClause('surname') . $ascDesc .
                ', ' . $this->db->tidyInputClause('firstname') . $ascDesc;
        }
        // 3rd order
        $ascDesc = ' ASC'; // default ascending
        if ($style['order3desc']) { // descending (default 0 = ascending)
            $ascDesc = ' DESC';
        }
        if ($style['order3'] == 1) { // publication year
            $order3 = ' CASE WHEN (' . $this->db->formatField('type') . '=' .
                        $this->db->tidyInput('book') .
                        ' OR ' . $this->db->formatField('type') . '=' .
                        $this->db->tidyInput('book_article') .
                        ') AND ' . $this->db->formatField('year2') . ' IS NOT NULL ' .
                        ' THEN ' . $this->db->tidyInputClause('year2') .
                    ' ELSE ' .
                        $this->db->tidyInputClause('year1') .
                    ' END' . $ascDesc;
        } elseif ($style['order3'] == 2) { // title
            $order3 = $this->db->tidyInputClause('title') . $ascDesc;
        } else { // default: by creator
            $order3 = $this->db->tidyInputClause('surname') . $ascDesc .
                ', ' . $this->db->tidyInputClause('firstname') . $ascDesc;
        }
        $condition = ' WHERE ' . $this->db->formatField('WKX_resource.id') . '=';
        $condition .= implode(' OR ' . $this->db->formatField('WKX_resource.id') . '=', $ids);
        $resultset = $this->db->select(
            ['WKX_resource'],
            // @phpstan-ignore-next-line class STATEMENT is unknown here
            $stmt->listFields('creator'),
            // @phpstan-ignore-next-line class STATEMENT is unknown here
            $stmt->listJoin() . $join . $condition .
            ' ORDER BY ' . $order1 . ', ' . $order2 . ', ' . $order3
        );
        while ($row = $this->db->fetchRow($resultset)) {
            // collect multiple array for passing to $this->citeformat->processEndnoteBibliography.  Must be keyed by unique resource identifier.
            $rows[$row['resourceId']] = $row;
            // Set the placeholder to deal with ambiguous in-text citations.  Must be keyed by unique resource identifier.
            $this->citeformat->getBibliographyIds()[$row['resourceId']] = false;
        }
        $this->citeformat->processEndnoteBibliography($rows, $this->citeIds);
        return $rows;
    }

    /**
     * Process bibliography array into string for output -- used for in-text citations and appended bibliographies for
     * footnotes
     */
    public function printBibliography(array $bibliography): string
    {
        $style = $this->citeformat->getStyleArray();
        foreach ($bibliography as $row) {
            // Do not add if cited resource type shouldn't be in the appended bibliography
            if (array_key_exists($row['type'] . '_notInBibliography', $style)) {
                continue;
            }
            // If we're disambiguating citations by adding a letter after the year, we need to insert the yearLetter into $row before formatting the bibliography.
            if ($style['ambiguous'] &&
                array_key_exists($row['resourceId'], $this->citeformat->getYearsDisambiguated())) {
                // For WIKINDX, if type == book or book article and there exists both 'year1' and 'year2' in $row (entered as
                // publication year and reprint year respectively), need to make sure we have the later publication year
                $yearField = 'year1';
                if (($row['type'] == 'book') || ($row['type'] == 'book_article')) {
                    $year2 = stripslashes($row['year2']);
                    if ($year2 && !$row['year1']) {
                        $yearField = 'year2';
                    } elseif ($year2 && $row['year1']) {
                        $yearField = 'year2';
                    }
                }
                $row[$yearField] = $this->citeformat->getYearsDisambiguated()[$row['resourceId']];
            }
            $this->citeformat->processIntextBibliography($row);
        }
        return $this->citeformat->collateIntextBibliography();
    }

    /**
    * Parse the cite tag by extracting resource ID and any page numbers. Check ID is valid
    * PreText and postText can also be encoded: e.g. (see Grimshaw 2003; Boulanger 2004 for example)
    * [cite]23:34-35|see ` for example[/cite].  For multiple citations, only the first encountered preText and postText will be used to enclose the citations.
    */
    public function parseCiteTag(int $matchIndex, string $tag): string
    {
        // When a user cut's 'n' pastes in HTML design mode, superfluous HTML tags (usually <style lang=xx></span>) are inserted.  Remove anything that looks like HTML
        $tag = preg_replace('/<.*?>/si', '', $tag);
        $rawCitation = explode('|', $tag);
        $idPart = explode(':', $rawCitation[0]);
        $id = $idPart[0];
        $resultset = $this->db->select(
            ['WKX_resource'],
            ['id'],
            ' WHERE ' . $this->db->formatField('id') . '=' . $this->db->tidyInput($id)
        );
        if (!$this->db->numRows($resultset)) {
            // For an invalid citation ID, deal with any text that precedes it by either prepending to the next cite tag capture or prepending to $this->tailText.
            if (array_key_exists($matchIndex + 1, $this->matches[1])) {
                $this->matches[1][$matchIndex + 1] =
                $this->matches[1][$matchIndex] . $this->matches[1][$matchIndex + 1];
            } else {
                $this->tailText = $this->matches[1][$matchIndex] . $this->tailText;
            }
            // Ensure we don't pass this invalid ID in the citation engine.
            unset($this->matches[1][$matchIndex]);
            return '';
        }
        if (array_key_exists('1', $idPart)) {
            $pages = explode('-', $idPart[1]);
            $pageStart = $pages[0];
            $pageEnd = array_key_exists('1', $pages) ? $pages[1] : false;
        } else {
            $pageStart = $pageEnd = false;
        }
        $this->citeformat->formatPages($pageStart, $pageEnd);
        if (array_key_exists('1', $rawCitation)) {
            $text = explode('`', $rawCitation[1]);
            $this->preText[] = $text[0];
            $this->postText[] = array_key_exists('1', $text) ? $text[1] : false;
        } else {
            // @todo do we really want to add array elements containing false here or do not add anything?
            $this->preText[] = $this->postText[] = false;
        }
        return $id;
    }

    /**
     * Accept a SQL result row of raw bibliographic data and process it.
     * We build up the $citeformat->item array with formatted parts from the raw $row
     */
    public function process(array $row, $id): void
    {
        // For WIKINDX, if type == book or book article and there exists both 'year1' and 'year2' in $row (entered as
        // publication year and reprint year respectively), then switch these around as 'year1' is
        // entered in the style template as 'originalPublicationYear' and 'year2' should be 'publicationYear'.
        if (($row['type'] == 'book') || ($row['type'] == 'book_article')) {
            $year2 = stripslashes($row['year2']);
            if ($year2 && !$row['year1']) {
                $row['year1'] = $year2;
                unset($row['year2']);
            } elseif ($year2 && $row['year1']) {
                $row['year2'] = stripslashes($row['year1']);
                $row['year1'] = $year2;
            }
        }
        $this->rowSingle = $row;
        unset($row);
        // Get creator names for resource
        $this->grabNames($id);
        // The title of the resource
        $this->createTitle();
        // Publication year of resource.  If no publication year, we create a dummy key entry so that CITEFORMAT can provide a replacement string if required by the style.
        if (!array_key_exists('year1', $this->rowSingle)) {
            $this->rowSingle['year1'] = false;
        }
        $this->citeformat->formatYear(stripslashes($this->rowSingle['year1']));
    }

    /**
     * callback for ordinals above
     */
    public function ordinals(array $matches): string
    {
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
        $pString = stripslashes($this->rowSingle['noSort']) . ' ' . stripslashes($this->rowSingle['title']);
        if ($this->rowSingle['subtitle']) {
            $pString .= ': ' . stripslashes($this->rowSingle['subtitle']);
        }
        // anything enclosed in {...} is to be left as is
        $this->citeformat->formatTitle($pString, '{', '}');
    }

    /**
     * Create preText and postText
     */
    public function createPrePostText(string $preText, string $postText): void
    {
        if (!$preText && !$postText) { // empty field
            return;
        }
        $this->citeformat->formatPrePostText($preText, $postText);
    }

    /**
     * get names from database for creator, editor, translator etc.
     */
    public function grabNames(string $citationId): bool
    {
        $rowTemp = [];
        $conditions = [];

        $nameIds = mb_split(',', $this->rowSingle['creator1']);
        foreach ($nameIds as $nameId) {
            $conditions[] = $this->db->formatField('id') . '=' . $this->db->tidyInput($nameId);
        }
        $recordset = $this->db->select(
            ['WKX_creator'],
            ['surname', 'firstname',
            'initials', 'prefix', 'id'],
            ' WHERE ' . implode(' OR ', $conditions)
        );
        $numNames = $this->db->numRows($recordset);
        // Reorder $row so that creator order is correct and not that returned by SQL
        while ($row = $this->db->loopRecordSet($recordset)) {
            $rowSql[$row['id']] = $row;
        }
        if (!isset($rowSql)) {
            return false;
        }
        foreach ($nameIds as $id) {
            $rowTemp[] = $rowSql[$id];
        }
        $this->citeformat->formatNames($rowTemp, $citationId);
        return true;
    }
}
