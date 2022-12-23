<?php

/********************************
OSBib:
A collection of PHP classes to create and manage bibliographic formatting for OS bibliography software
using the OSBib standard.  Originally developed for WIKINDX (http://wikindx.sourceforge.net)

Released through http://bibliophile.sourceforge.net under the GPL licence.
Do whatever you like with this -- some credit to the author(s) would be appreciated.

If you make improvements, please consider contacting the administrators at bibliophile.sourceforge.net
so that your improvements can be added to the release package.

Mark Grimshaw 2005
http://bibliophile.sourceforge.net
 ********************************/

/**
 * Class TESTOSBIB
 * Test suite for OSBIB's BIBFORMAT (bibliographic formatting).
 * This is not part of OSBIB but is here to provide an example of usage and to test data input and output for a non-BibTeX based system.
 * It is intended to be a quick introduction to the main usage of OSBIB.  For more detailed explanation including various parameters that can be
 * set, see WIKINDX's usage of OSBIB in the example BIBSTYLE.php.
 *
 * @author Mark Grimshaw
 * @version 1
 */

/*
* Start the ball rolling
*
* The first parameter to TESTOSBIB is the bibliographic style.  This can be any of the OSBIB supplied styles in ../styles/bibliography.
*/
$useStyle = loadStyle();
include_once(__DIR__ . '/TESTOSBIB.php');
$testosbib = new TESTOSBIB($useStyle);
$testosbib->execute();

// Load styles and print select box.
function loadStyle(): string
{
    include_once(__DIR__ . '/../LOADSTYLE.php');
    $loadStyle = new LOADSTYLE();
    $styles = $loadStyle->loadDir('../styles/bibliography');
    $styleKeys = array_keys($styles);
    print "<h2><font color='red'>OSBIB Bibliographic Formatting (Quick Test)</font></h2>";
    print '<table width="100%" border="0"><tr><td>';
    print '<form method = "POST">';
    print '<select name="style" id="style" size="10">';
    if (array_key_exists('style', $_POST)) {
        $useStyle = $_POST['style'];
    } else {
        $useStyle = $styleKeys[0];
    }
    foreach ($styles as $style => $value) {
        if ($style == $useStyle) {
            print "<option value=\"$style\" selected = \"selected\">$value</option>";
        } else {
            print "<option value=\"$style\">$value</option>";
        }
    }
    print '</select>';
    print '<br /><input type="submit" value="SUBMIT" />';
    print '</form><td>';
    print '<td align="right" valign="top"><a href="http://bibliophile.sourceforge.net">
          <img src="../create/bibliophile.gif" alt="Bibliophile" border="0"></a></td></tr></table>';
    return $useStyle;
}
