<?php
/**
 * 2017-11-08 VB: Abfrage auf GET-Parameter "style" ergï¿½nzt
 */
/*
Plugin Name: bib2html
Plugin URI: http://sergioandreozzi.com/wordpress/bib2html
Description: bib2html enables to add bibtex entries formatted as HTML in wordpress pages and posts. The input data is the bibtex text file and the output is HTML. 
Version: 0.9.3
Author: Sergio Andreozzi
Author URI: http://sergioandreozzi.com
*/


/*  Copyright 2006-2007  Sergio Andreozzi  (email : sergio <DOT> andreozzi <AT> gmail <DOT> com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/


/*
This plug-in has been improved thanks to the suggestons and contributions of
- Cristiana Bolchini
-- cleaner bibtex presentation
- Patrick Mauï¿½
-- remote bibliographies managed by citeulike.org or bibsonomy.org
- Nemo
-- more characters on key
- Marco Loregian
-- inverting bibtex and html
*/


function bib2htmlProcess(
    $data,
    $filterType,
    $filter,
    $sort = false,
    $lang = '',
    $sortFixed = false,
    $template = '',
    $style = 'uniol'
) {
    $OSBiBPath = dirname(__FILE__) . '/OSBiB/';
    // $OSBiBPath = '/var/www/php51.uni-oldenburg.de/htdocs/www/bib2html/OSBiB/';
    include_once($OSBiBPath . 'format/bibtexParse/PARSEENTRIES.php');
    include_once($OSBiBPath . 'format/BIBFORMAT.php');
    include_once(dirname(__FILE__) . '/class.TemplatePower.inc.php');

    echo '<!-- Style bib2htmlProcess: ' . $style . $lang . ' -->';

    // parse the content of bib string and generate associative array with valid entries
    $parse = NEW PARSEENTRIES();
    $parse->expandMacro = true;
    $parse->fieldExtract = true;
    $parse->removeDelimit = true;
    $parse->loadBibtexString($data);
    $parse->extractEntries();
    list($preamble, $strings, $entries) = $parse->returnArrays();

    /* Format the entries array  for html output */
    $bibformat = NEW BIBFORMAT($OSBiBPath, true); // TRUE implies that the input data is in bibtex format
    $bibformat->cleanEntry = true; // convert BibTeX (and LaTeX) special characters to UTF-8
    list($info, $citation, $styleCommon, $styleTypes) = $bibformat->loadStyle($OSBiBPath . "styles/bibliography/",
        $style . $lang);
    $bibformat->getStyle($styleCommon, $styleTypes);

    if ($template == '') {
        $template = dirname(__FILE__) . '/bibentry-html.tpl';
    }
    $template_str = file_get_contents($template);
    if ($template_str === false) {
        die(print('<br /> Couldn\'t open or read [ ' . $template . ' ]!' . '<br />' . PHP_EOL));
    }
    $tpl = new TemplatePower($template_str, 1);
    $tpl->prepare();

    //// Added by C.v.O Uni Oldenburg
    if ($sort) {
        foreach ($entries as $key => $entry) {
            $sortiere[$sort][$key] = $entry[$sort];
        }
        array_multisort($sortiere[$sort], ($sort == 'year' ? SORT_DESC : SORT_ASC), $entries);
    }
    //// End added by C.v.O Uni Oldenburg

    foreach ($entries as $entry) {
        // Get the resource type ('book', 'article', 'inbook' etc.)
        $resourceType = $entry['bibtexEntryType'];

        //  adds all the resource elements automatically to the BIBFORMAT::item array
        $bibformat->preProcess($resourceType, $entry);

        // apply filters
        $pos = strpos($filter, $resourceType);
        $bibkey = $entry['bibtexCitation'];

        if (((strcmp($filterType, "allow") === 0) && ($pos === false)) or
            ((strcmp($filterType, "deny") === 0) && ($pos !== false)) or
            ((strcmp($filterType, "key") === 0) && (strcmp($filter, $bibkey) != 0))) {
            continue;
        }

        // get the formatted resource string ready for printing to the web browser
        // the str_replace is used to remove the { } parentheses possibly present in title
        // to enforce uppercase, TODO: check if it can be done only on title
        $mapped_entry = str_replace(array('{', '}'), '', $bibformat->map());
        $tpl->newBlock("bibtex_entry");
        $tpl->assign("year", $entry['year']);
        $tpl->assign("type", $entry['bibtexEntryType']);
        $tpl->assign("pdf", toDownload($entry, $lang));
        $tpl->assign("key", strtr($bibkey, ":", "-"));
        $tpl->assign("entry", $mapped_entry);
        $tpl->assign("bibtex", formatBibtex($entry['bibtexEntry']));
        // These are rather AG TWiSt specific formats but probably of more general interest
        $tpl->assign("twist_title", toTwistTitle($mapped_entry, $resourceType, $entry['doi']));
        $tpl->assign("twist_entry", toTWiStEntry($mapped_entry, $resourceType));
        $tpl->assign("twist_pdf", toTWiStPDF($entry));
    }



    //// Added by C.v.O Uni Oldenburg
    ///
    /*
    $sortbuttons = '';
    if (!$sortFixed) {
        $ref = $_REQUEST['ref'] ? 'ref=' . $_REQUEST['ref'] : '';
        $sortpars = $lang == '_en' ? array(
            'bibtexEntryType' => 'Type of publication',
            'author' => 'Author',
            'year' => 'Year',
            '0' => '(none)'
        ) : array('bibtexEntryType' => 'Art der Publikation', 'author' => 'Autor', 'year' => 'Jahr', '0' => '(ohne)');
        $sortbuttons .= $lang == '_en' ? '<div class="sortierung"><span>Sort by: </span>' : '<div class="sortierung"><span>Sortierung: </span>';
        foreach ($sortpars as $sortpar => $linktext) {
            $parstring = $ref . ($sortpar ? ($ref ? '&amp;' : '') . 'sort=' . $sortpar : '');
            $sortbuttons .= ' <a ' . (($sort === $sortpar) || ($sortpar == '0' && !$sort) ? 'class="aktiv" ' : '') . 'href="?' . $parstring . '">' . $linktext . '</a> ';
        }
        $sortbuttons .= '<div>&nbsp;</div></div>';
    }
    return $sortbuttons . $tpl->getOutputContent();
    */
    //// End added by C.v.O Uni Oldenburg

    return $tpl->getOutputContent();
}

/**
 * @param $entry Current entry
 * @param $lang language string, e.g. 'en', 'de' (default is 'en')
 * @return string
 */
function toDownload($entry, $lang)
{
    if (array_key_exists('url', $entry)) {
        if ($lang === 'de' || $lang === '') {
            $text = 'Dokument aufrufen';
        } else {
            $text = 'Go to document';
        }
        $string = " | <a href='" . str_replace('\\', '',
                $entry['url']) . "' title='" . $text . "'><img src='https://uol.de/www/bib2html/bib2html/external.png' width='10' height='10' alt='" . $text . "' /> " . $text . "</a>";
        return $string;
    }
    return '';
}


//// Added by Philip Rinn for AG TWiSt, 15. Mai 2014

// Construct a string with the title linked to the url field if existent
function toTWiStTitle($entry, $type, $doi)
{
    // Extract the title from the whole entry sting
    if ($type == 'book') {
        $title = preg_replace("/.*<em>(.*)<\/em>.*/", '\1', $entry);
    } else {
        $title = preg_replace("/.*&quot;(.*),&quot;.*/", '\1', $entry);
    }
    if ($doi) {
        //check if doi starts with 'http(s):' and don't do anything if so
        if (!preg_match("/^https?:/i", $doi)) {
            // Remove eventually existing 'doi:'
            $doi = trim(preg_replace("/^doi:/i", '', $doi));
            // Prefix with resolver address
            $doi = 'http://dx.doi.org/' . $doi;
        }
        $title = "<a href='" . $doi . "'>" . $title . "</a>";
    }
    return $title;
}

// Remove the title and add a linebreak instead
function toTWiStEntry($entry, $type)
{
    if ($type == 'book') {
        $rest = preg_replace("/, <em>.*<\/em>,/", "<br>", $entry);
    } else {
        $rest = preg_replace("/, &quot;.*&quot;/", "<br>", $entry);
    }
    return $rest;
}

// If the field 'url' exists construct a link
function toTWiStPDF($entry)
{
    if (array_key_exists('url', $entry)) {
        $string = "[<a href='" . str_replace('\\', '', $entry['url']) . "'>PDF</a>]";
        return $string;
    }
    return '';
}

//// End added by Philip Rinn


function getSslPage($url)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_REFERER, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $result = curl_exec($ch);
    curl_close($ch);
    return $result;
}


function bib2html($myContent, $sort = false, $lang = '', $sortFixed = false, $template = '', $style = 'uniol')
{

    // search for all [bibtex filename] tags and extract the filename
    preg_match_all("/\[\s*bibtex\s+file=(.+)(\s+(allow|deny|key)=(.+))*]/U", $myContent, $bibItemsSets, PREG_SET_ORDER);

    if ($bibItemsSets) {

        foreach ($bibItemsSets as $bibItems) {
            $bibFile = $bibItems[1];


            if (preg_match("/^https/", $bibFile)) {
                $bib = getSslPage($bibFile);
            } else {
                $bib = file_get_contents($bibFile);
            }

            if ($bib) {
                if (!empty($bib)) {
                    // if bibtex file identified and opened, then convert to html
                    $htmlbib = bib2htmlProcess($bib, $bibItems[3], $bibItems[4], $sort, $lang, $sortFixed, $template,
                        $style);
                    $myContent = str_replace($bibItems[0], $htmlbib, $myContent);
                } else {
                    $myContent = str_replace($bibItems[0], $bibItems[1] . ' bibtex file empty', $myContent);
                }
            } else {
                $myContent = str_replace($bibItems[0], $bibItems[1] . ' bibtex file not found', $myContent);
            }
        }
    }

    return $myContent;
}

// this function formats a bibtex code in order to be readable
// when appearing in the modal window
function formatBibtex($entry)
{
    $order = array("},");
    $replace = "}, <br />\n &nbsp;";

    $entry = preg_replace('/\s\s+/', ' ', trim($entry));
    $new_entry = str_replace($order, $replace, $entry);
    $new_entry = str_replace(", author", ", <br />\n &nbsp;&nbsp;author", $new_entry);
    $new_entry = str_replace(", Author", ", <br />\n &nbsp;&nbsp;author", $new_entry);
    $new_entry = str_replace(", AUTHOR", ", <br />\n &nbsp;&nbsp;author", $new_entry);
    $new_entry = preg_replace('/\},?\s*\}$/', "}\n}", $new_entry);
    return $new_entry;
}


