<?php
/*
Inspired by an awk BibTeX parser written by Nelson H. F. Beebe over 20 years ago although little of that
remains other than a highly edited braceCount().

Released through http://bibliophile.sourceforge.net under the GPL licence.
Do whatever you like with this -- some credit to the author(s) would be appreciated.

A collection of PHP classes to manipulate bibtex files.

If you make improvements, please consider contacting the administrators at bibliophile.sourceforge.net so that your improvements can be added to the release package.

Mark Grimshaw 2005
http://bibliophile.sourceforge.net

(Amendments to file reading Daniel Pozzi for v1.1)

11/June/2005 - v1.53 Mark Grimshaw:  Stopped expansion of @string when entry is enclosed in {...} or "..."
21/08/2004 v1.4 Guillaume Gardey, Added PHP string parsing and expand macro features.
 Fix bug with comments, strings macro.
    expandMacro = FALSE/TRUE to expand string macros.
    loadStringMacro($bibtex_string) to load a string. (array of lines)
22/08/2004 v1.4 Mark Grimshaw - a few adjustments to Guillaume's code.
28/04/2005 v1.5 Mark Grimshaw - a little debugging for @preamble

02/05/2005 G. Gardey - Add support for @string macro defined by curly brackets:
           @string{M12 = {December}}
                     - Don't expand macro for bibtexCitation and bibtexEntryType
                     - Better support for fields like journal = {Journal of } # JRNL23
03/05/2005 G. Gardey - Fix wrong field value parsing when an entry ends by
                           someField = {value}}

*/

// For a quick command-line test (php -f PARSEENTRIES.php) after installation, uncomment these lines:

/**
// Parse a file
    $parse = NEW PARSEENTRIES();
    $parse->expandMacro = TRUE;
//	$array = array("RMP" =>"Rev., Mod. Phys.");
//	$parse->loadStringMacro($array);
//	$parse->removeDelimit = FALSE;
//	$parse->fieldExtract = FALSE;
    $parse->openBib("bib.bib");
    $parse->extractEntries();
    $parse->closeBib();
    list($preamble, $strings, $entries) = $parse->returnArrays();
    print_r($preamble);
    print "\n";
    print_r($strings);
    print "\n";
    print_r($entries);
    print "\n\n";
*************************/

/************************
// Parse a bibtex PHP string
    $bibtex_data = <<< END

@STRING{three = "THREE"}
@STRING{two = "TWO"}
@string{JRNL23 = {NatLA 23}}


@article{klitzing.1,
    author = "v. Klitzing and Dorda and Pepper",
    title = "New method for high mark@sirfragalot.com accuracy determination of fine structure constant based on quantized hall resistance",
    volume = "45",
    journal = {{Journal of } # JRNL23 # two},
    pages = "494",
               citeulike-article-id = {12222
    }
               ,
               ignoreMe = {blah}, }

@article{klitzing.2,
    author = "Klaus von Klitzing",
    title = "The Quantized Hall Effect",
    volume = "58",
    journal = two,
    pages = "519",
}

END;

    $parse = NEW PARSEENTRIES();
    $parse->expandMacro = TRUE;
//	$parse->removeDelimit = FALSE;
//	$parse->fieldExtract = FALSE;
    $array = array("RMP" =>"Rev., Mod. Phys.");
    $parse->loadStringMacro($array);
    $parse->loadBibtexString($bibtex_data);
    $parse->extractEntries();
    list($preamble, $strings, $entries) = $parse->returnArrays();
    print_r($preamble);
    print "\n";
    print_r($strings);
    print "\n";
    print_r($entries);
    print "\n\n";

*/
class PARSEENTRIES
{
    protected bool $removeDelimit = false;
    protected bool $fieldExtract = false;
    protected bool $expandMacro = false;
    protected bool $parseFile = false;
    protected bool $delimitersExist = false;

    protected int $count = 0;
    protected int $currentLine = 0;

    protected array $userStrings = [];

    /** @var array|bool */
    protected $entries = [];

    /** @var array|bool  */
    protected $strings = [];

    /** @var array|bool  */
    protected $preamble = [];

    protected array $bibtexString = [];

    /** @var bool|resource  */
    protected $fid = false;

    public function __construct(bool $expandMacro = false, bool $fieldExtract = true, bool $removeDelimit = true)
    {
        $this->preamble = $this->strings = $this->entries = [];
        $this->count = 0;
        $this->fieldExtract = $fieldExtract;
        $this->removeDelimit = $removeDelimit;
        $this->expandMacro = $expandMacro;
        $this->parseFile = true;
    }

    /**
     * Open bib file
     */
    public function openBib(string $file): void
    {
        if (!is_file($file)) {
            die;
        }
        $this->fid = fopen($file, 'r');
        // 22/08/2004 Mark Grimshaw - commented out as set in constructor.
        // 25/08/2004 G. Gardey needed in order to be able to alternate file parsing or PHP string parsing
        $this->parseFile = true;
    }

    /**
     * Load a bibtex string to parse it
     * @param string|mixed $bibtex_string
     */
    public function loadBibtexString($bibtex_string): void
    {
        if (is_string($bibtex_string)) {
            $this->bibtexString = explode("\n", $bibtex_string);
        } else {
            $this->bibtexString = $bibtex_string;
        }
        $this->parseFile = false;
        $this->currentLine = 0;
    }

    /**
     * set strings macro
     */
    public function loadStringMacro(array $macro_array): void
    {
        $this->userStrings = $macro_array;
    }

    /**
     * Close bib file
     */
    public function closeBib()
    {
        fclose($this->fid);
    }

    /**
     * Get a line from bib file
     * @return string|bool
     */
    public function getLine()
    {
        // 21/08/2004 G.Gardey
        // remove comments from parsing
        if ($this->parseFile) {
            if (!feof($this->fid)) {
                do {
                    $line = trim(fgets($this->fid));
                    $isComment = (strlen($line) > 0) ? $line[0] == '%' : false;
                } while (!feof($this->fid) && $isComment);
                return $line;
            }
            return false;
        }

        do {
            $line = trim($this->bibtexString[$this->currentLine]);
            $isComment = (strlen($line)>0) ? $line[0] == '%' : false;
            $this->currentLine++;
        } while ($this->currentLine <count($this->bibtexString) && $isComment);
        $val = ($this->currentLine < count($this->bibtexString)) ? $line : false;
        return $val;
    }

    /**
     * Count entry delimiters
     */
    public function braceCount($line, $delimitStart)
    {
        if ($delimitStart == '{') {
            $delimitEnd = '}';
        } else {
            $delimitStart = '(';
            $delimitEnd = ')';
        }
        $count = substr_count($line, $delimitStart);
        $count -= substr_count($line, $delimitEnd);
        return $count;
    }

    /**
     * Extract value part of @string field enclosed by double-quotes.
     */
    public function extractStringValue(string $string): string
    {
        // 2/05/2005 G. Gardey Add support for @string macro
        // defined by curly bracket : @string{M12 = {December}}
        $oldvalue = $this->expandMacro;
        $this->expandMacro = false;
        // $string contains a end delimiter
        // remove it
        $string = trim(substr($string, 0, strlen($string)-1));
        // remove delimiters
        $string = $this->removeDelimiters($string);
        // restore expandMacro
        $this->expandMacro = $oldvalue;
        return $string;
    }

    /**
     * Extract a field
     */
    public function fieldSplit(string $seg): array
    {
        // handle fields like another-field = {}
        $array = preg_split("/,\s*([-_.:,a-zA-Z0-9]+)\s*={1}\s*/U", $seg, PREG_SPLIT_DELIM_CAPTURE);
        if (!array_key_exists(1, $array)) {
            return [$array[0], false];
        }
        return [$array[0], $array[1]];
    }

    /**
     * Extract and format fields
     * @param string $oldString
     */
    public function reduceFields(string $oldString): void
    {
        // Sybille Peters: check for invalid input
        if (!$oldString) {
            return;
        }

        $values = [];

        // 03/05/2005 G. Gardey. Do not remove all occurences, juste one
        //              * correctly parse an entry ended by: somefield = {aValue}}
        $lg = strlen($oldString);
        if ($oldString[$lg-1] == '}' || $oldString[$lg-1] == ')' || $oldString[$lg-1] == ',') {
            $oldString = substr($oldString, 0, $lg-1);
        }
        $split = preg_split('/=/', $oldString, 2);
        $string = $split[1];
        while ($string) {
            list($entry, $string) = $this->fieldSplit($string);
            $values[] = $entry;
        }
        foreach ($values as $value) {
            $pos = strpos($oldString, $value);
            $oldString = substr_replace($oldString, '', $pos, strlen($value));
        }
        $rev = strrev(trim($oldString));
        if (($rev[0] ?? false) != ',') {
            $oldString .= ',';
        }
        $keys = preg_split('/=,/', $oldString);
        // 22/08/2004 - Mark Grimshaw
        // I have absolutely no idea why this array_pop is required but it is.  Seems to always be an empty key at the end after the split
        // which causes problems if not removed.
        array_pop($keys);
        foreach ($keys as $key) {
            $value = trim(array_shift($values));
            $rev = strrev($value);
            // remove any dangling ',' left on final field of entry
            if (($rev[0] ?? false) == ',') {
                $value = rtrim($value, ',');
            }
            if (!$value) {
                continue;
            }
            // 21/08/2004 G.Gardey -> expand macro
            // Don't remove delimiters now
            // needs to know if the value is a string macro
            // $this->entries[$this->count][strtolower(trim($key))] = trim($this->removeDelimiters(trim($value)));
            $key = strtolower(trim($key));
            $value = trim($value);
            $this->entries[$this->count][$key] = $value;
        }
    }

    /**
     * Start splitting a bibtex entry into component fields.
     * Store the entry type and citation.
     */
    public function fullSplit(string $entry): void
    {
        // Sybille Peters: check for invalid input
        if (!$entry) {
            return;
        }

        $matches = preg_split("/@(.*)\s*[{(](.*),/U", $entry, 2, PREG_SPLIT_DELIM_CAPTURE);
        if (!($matches[1] ?? false)) {
            return;
        }
        $this->entries[$this->count]['bibtexEntryType'] = strtolower($matches[1]);

        if ($matches[2] ?? false) {
            // sometimes a bibtex file will have no citation key
            if (preg_match('/=/', $matches[2])) { // this is a field
                $matches = preg_split("/@(.*)\s*[{(](.*)/U", $entry, 2, PREG_SPLIT_DELIM_CAPTURE);
            }
            //print_r($matches); print "<P>";
            $this->entries[$this->count]['bibtexCitation'] = $matches[2];
        }
        $this->entries[$this->count]['bibtexEntry'] = $entry;
        if ($matches[3] ?? false) {
            $this->reduceFields($matches[3]);
        }
    }

    /**
     * Grab a complete bibtex entry
     * @param bool|string $line
     * @return bool|string
     */
    public function getEntry($line)
    {
        $entry = '';
        $count = 0;
        $lastLine = false;
        if (preg_match("/@(.*)\s*([{(])/", preg_quote($line, '/'), $matches)) {
            do {
                $count += $this->braceCount($line, $matches[2]);
                $entry .= ' ' . $line;
                if (($line = $this->getLine()) === false) {
                    break;
                }
                $lastLine = $line;
            } while ($count);
        } else {
            $line .= $this->getLine();
            $this->getEntry($line);
        }
        if (!array_key_exists(1, $matches)) {
            return $lastLine;
        }
        if (preg_match('/string/i', $matches[1])) {
            $this->strings[] = $entry;
        } elseif (preg_match('/preamble/i', $matches[1])) {
            $this->preamble[] = $entry;
        } else {
            if ($this->fieldExtract) {
                $this->fullSplit($entry);
            } else {
                $this->entries[$this->count] = $entry;
            }
            $this->count++;
        }
        return $lastLine;
    }

    /**
     * 02/05/2005 G.Gardey	only remove delimiters from a string
     */
    public function removeDelimiters(string $string): string
    {
        // MG 10/06/2005 - Make a note of whether delimiters exist - required in removeDelimitersAndExpand() otherwise, expansion happens everywhere including
        // inside {...} and "..."
        $this->delimitersExist = false;
        if ($string  && ($string[0] == '"')) {
            $string = substr($string, 1);
            $string = substr($string, 0, -1);
        } elseif ($string && ($string[0] == '{')) {
            if ($string[strlen($string)-1] == '}') {
                $string = substr($string, 1);
                $string = substr($string, 0, -1);
            }
        }
        return $string;
    }

    /**
     * Remove enclosures around entry field values.  Additionally, expand macros if flag set.
     */
    public function removeDelimitersAndExpand(string $string, $preamble = false): string
    {
        // 02/05/2005 G. Gardey
        $string = $this->removeDelimiters($string);
        $delimitersExist = $this->delimitersExist;
        // expand the macro if defined
        // 23/08/2004 Mark - changed isset() to !empty() since $this->strings isset in constructor.
        if ($string && $this->expandMacro) {
            if (!empty($this->strings) && !$preamble) {
                // macro are case insensitive
                foreach ($this->strings as $key => $value) {
                    // 09/March/2005 - Mark Grimshaw - sometimes $key is empty - not sure why
                    //  if(!$key || !$value || !$string)
                    //  continue;
                    if (!$delimitersExist) {
                        $string = mb_eregi_replace($key, $value, $string);
                    }
                    // 22/08/2004 Mark Grimshaw - make sure a '#' surrounded by any number of spaces is replaced by just one space.
                    // 30/04/2005 Mark Grimshaw - ensure entries such as journal = {{Journal of } # JRNL23} are properly parsed
                    // 02/05/2005 G. Gardey - another solution for the previous line
                    $items = mb_split('#', $string);
                    $string = '';
                    foreach ($items as $val) {
                        $string .= $this->removeDelimiters(trim($val)) . ' ';
                    }

                    $string = preg_replace("/\s+/", ' ', $string);
                }
            }
            if (!empty($this->userStrings)) {
                // 24/08/2004 G.Gardey replace user defined strings macro
                foreach ($this->userStrings as $key => $value) {
                    $string = mb_eregi_replace($key, $value, $string);
                    $string = preg_replace("/\s*#\s*/", ' ', $string);
                }
            }
        }
        return $string;
    }

    /**
     * This method starts the whole process
     */
    public function extractEntries(): void
    {
        $lastLine = false;
        if ($this->parseFile) {
            while (!feof($this->fid)) {
                $line = $lastLine ? $lastLine : $this->getLine();
                if (!preg_match('/^@/i', $line)) {
                    continue;
                }
                if (($lastLine = $this->getEntry($line)) !== false) {
                    continue;
                }
            }
        } else {
            $lastCurrentLine = $this->currentLine;
            $numSame = 0;
            $lastLine = '';
            while ($this->currentLine < count($this->bibtexString)) {
                $line = $lastLine ? $lastLine : $this->getLine();

                // BEGIN: check to guard against endless loop
                // this is a workaround to guard against endless loop which may occur for example with entries
                // with parenthesis in citation key, e.g. @book blah (blah) { ...
                // This is actually an error in the bibtex file, but it was not correct that it resulted in an
                // endless loop, we now throw an exception.
                // Sybille Peters
                // @todo: fix this
                if ($lastCurrentLine === $this->currentLine) {
                    $numSame++;
                    if ($numSame > 500) {
                        throw new \RuntimeException('Error parsing bibtex file, may be unexpected charaters such as ( in citation key, e.g. @book title (something) { ....');
                    }
                }
                $lastCurrentLine = $this->currentLine;
                // END: check to guard against endless loop

                if (!preg_match('/^@/i', $line)) {
                    continue;
                }
                if (($lastLine = $this->getEntry($line)) !== false) {
                    // todo: makes no sense ???
                    continue;
                }
            }
        }
    }

    /**
     * Return arrays of entries etc. to the calling process.
     */
    public function returnArrays(): array
    {
        foreach ($this->preamble as $value) {
            preg_match('/.*[{(](.*)/', $value, $matches);
            $preamble = substr($matches[1], 0, -1);
            $preambles['bibtexPreamble'] = trim($this->removeDelimitersAndExpand(trim($preamble), true));
        }
        if (isset($preambles)) {
            $this->preamble = $preambles;
        }
        if ($this->fieldExtract) {
            foreach ($this->strings as $value) {
                // changed 21/08/2004 G. Gardey
                // 23/08/2004 Mark G. account for comments on same line as @string - count delimiters in string value
                $value = trim($value);
                $matches = preg_split("/@string\s*([{(])/i", $value, 2, PREG_SPLIT_DELIM_CAPTURE);
                $delimit = $matches[1];
                $matches = preg_split('/=/', $matches[2], 2, PREG_SPLIT_DELIM_CAPTURE);
                $strings[trim($matches[0])] = trim($this->extractStringValue($matches[1]));
            }
        }
        if (isset($strings)) {
            $this->strings = $strings;
        }

        // changed 21/08/2004 G. Gardey
        // 22/08/2004 Mark Grimshaw - stopped useless looping.
        // removeDelimit and expandMacro have NO effect if !$this->fieldExtract
        if ($this->removeDelimit || $this->expandMacro && $this->fieldExtract) {
            for ($i = 0; $i < count($this->entries); $i++) {
                foreach ($this->entries[$i] as $key => $value) {
                    // 02/05/2005 G. Gardey don't expand macro for bibtexCitation
                    // and bibtexEntryType
                    if ($key != 'bibtexCitation' && $key != 'bibtexEntryType') {
                        $this->entries[$i][$key] = trim($this->removeDelimitersAndExpand($this->entries[$i][$key]));
                    }
                }
            }
        }
        if (empty($this->preamble)) {
            $this->preamble = [];
        }
        if (empty($this->strings)) {
            $this->strings = [];
        }
        if (empty($this->entries)) {
            $this->entries = [];
        }
        return [$this->preamble, $this->strings, $this->entries];
    }
}
