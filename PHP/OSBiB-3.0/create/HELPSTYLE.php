<?php

declare(strict_types=1);
/********************************
OSBib:
A collection of PHP classes to create and manage bibliographic formatting for OS bibliography software
using the OSBib standard.

Released through http://bibliophile.sourceforge.net under the GPL licence.
Do whatever you like with this -- some credit to the author(s) would be appreciated.

If you make improvements, please consider contacting the administrators at bibliophile.sourceforge.net
so that your improvements can be added to the release package.

Adapted from WIKINDX: http://wikindx.sourceforge.net

Mark Grimshaw 2005
http://bibliophile.sourceforge.net
********************************/

/**
 * Help file
 *
 * @author Mark Grimshaw
 *
 * $Header: /cvsroot/bibliophile/OSBib/create/HELPSTYLE.php,v 1.2 2005/06/20 22:47:32 sirfragalot Exp $
*/
class HELPSTYLE
{
    protected const TEXT4 = "You can create a new style based on an existing one by copying the existing style. To remove a style from the list available to your users, simply remove that style's directory from the styles directory ";

    /** 'Short Name' and 'Long Name' should match the name given in MESSAGES.php style array.*/
    protected const TEXT5 = "Each style has a set of options that define the heading style of titles, how to display numbers and dates etc. and then a separate style definition for each resource type that OSBib handles. The 'Short Name' is used by OSBib as both the folder and file name and for this reason should not only be a unique name within " . OSBIB_STYLE_DIR . ", but should also have no spaces or any other characters that may cause confusion with your operating system (i.e. alphanumeric characters only). The 'Long Name' is the description of the style that is displayed to OSBib users.";

    /** 'generic style' should be whatever you set for $style['generic'] in MESSAGES.php. */
    protected const TEXT6 = "The three 'generic style' definitions are required and are used to display any resource type for which there does not yet exist a style definition. This allows you to build up your style definitions bit by bit.  Furthermore, some bibliographic styles provide no formatting guidelines for particular types of resource in which case the generic styles will provide some formatting for those resources according to the general guidelines for that bibliographic style. Each resource for which there is no style definition will fall back to the chosen generic style. The generic styles try their best but if formatting is strange for a particular resource type then you should explicitly define a style definition for that type. ";

    // Don't translate HTML tags!
    protected const TEXT7 = 'Each style definition has a range of available fields listed to the right of each input box. These fields are <strong>case-sensitive</strong> and need not all be used. However, with some of the more esoteric styles, the more database fields that have been populated for each resource in the OSBib-compatible bibliographic database, the more likely it is that the formatting will be correct.';
    protected const TEXT9 = 'The formatting of the names, edition and page numbers and the capitalization of the title depends on the global settings provided for your bibliographic style.';
    protected const TEXT8 = 'If the value entered for the edition of a resource contains non-numeric characters, then, despite having set the global setting for the edition format to ordinal (3rd. etc.), no conversion will take place.';
    // 'Editor switch' should be whatever you set for $style['editorSwitchHead'] in MESSAGES.php.
    // 'Yes' should be whatever you set for $style['yes'] in MESSAGES.php.

    protected const TEXT10 = "The 'Editor switch' requires special attention. Some bibliographic styles require that for books and book chapters, where there exists an editor but no author, that the position occupied by the author is taken by the editor. If you select 'Yes' here, you should then supply a replacement editor field. Please note that if the switch occurs, the editor(s) formatting will be inherited from the global settings you supplied for the author. See the examples below.";
    protected const TEXT11 = 'Tip: In most cases, you will find it easiest to attach punctuation and spacing at the end of the preceding field rather than at the start of the following field. This is especially the case with finite punctuation such as full stops.';

    protected const SYNTAX_HEADING = 'self::SYNTAX';
    protected const SYNTAX1 = 'The style definition syntax uses a number of rules and special characters:';
    protected const SYNTAX2 = "The character '|' separates fields from one another.";
    protected const SYNTAX3 = 'If a field does not exist or is blank in the database, none of the definition for that field is printed.';
    protected const SYNTAX4 = 'Field names are case-sensitive';
    // follows on from above in the same sentence...
    protected const SYNTAX5 = 'and need not all be used.';
    protected const SYNTAX6 = 'Within a field, you can add any punctuation characters or phrases you like before and after the field name.';
    protected const SYNTAX7 = "Any word that you wish to be printed and that is the same (even a partial word) as a field name should be enclosed in backticks '`'.";
    // Do not translate |^p.^pp.^pages|, 'pages', 'pp.' and 'p.'
    protected const SYNTAX8 = "For creator lists (editors, revisers, directors etc.) and pages, alternative singular and plural text can be specified with '^' (e.g. |^p.^pp.^pages| would print the field 'pages' preceded by 'pp.' if there were multiple pages or 'p.' if not).";
    protected const SYNTAX9 = 'BBCode [u]..[/u], [i]..[/i] and [b]..[/b] can be used to specify underline, italics and bold.';
    // Do not translate HTML tags!
    protected const SYNTAX10 = "The character '%' enclosing any text or punctuation <em>before</em> the field name states that that text or those characters will only be printed if the <em>preceeding</em> field exists or is not blank in the database. The character '%' enclosing any text or punctuation <em>after</em> the field name states that that text or those characters will only be printed if the <em>following</em> field exists or is not blank in the database. It is optional to have a second pair in which case the construct should be read 'if target field exists, then print this, else, if target field does not exist, print that'.  For example, '%: %' will print ': ' if the target field exists else nothing if it doesn't while '%: %. %' will print ': ' if the target field exists else '. ' if it does not.";
    // Do not translate HTML tags!
    /* The following is an alternative version for self::SYNTAX11:
     * "You may enclose groups of fields in characters, such as paired parentheses, stipulating that these characters are only to be printed if at least one of the enclosed fields exists.  To do this, place the characters into their own fields (delimited by '|' but without a fieldname) surrounding the target fields.  If such special fields exist in isolation (i.e.  are not paired to enclose other fields), unintended results may occur.
    */
    protected const SYNTAX11 = 'Characters in fields that do not include a field name should be paired with another set and together enclose a group of fields. If these special fields are not paired unintended results may occur. These are intended to be used for enclosing groups of fields in brackets where <em>at least</em> one of the enclosed fields exists or is not blank in the database.';
    // Don't translate <code>|%,\"%\". %|xxxxx|xxxxx|%: %; %|</code> or other HTML tags
    protected const SYNTAX12 = 'The above two rules can combine to aid in defining particularly complex bibliographic styles (see examples below). The pair <br /><code>|%,"%". %|xxxxx|xxxxx|%: %; %|</code><br /> states that if at least one of the intervening fields exists, then the comma and colon will be printed; if an intervening field does not exist, then the full stop will be printed <em>only</em> if the <em>preceeding</em> field exists (else nothing will be printed) and the semicolon will be printed <em>only</em> if the <em>following</em> field exists (else nothing will be printed).';
    protected const SYNTAX13 = "If the final set of characters in the style definition is '|.' for example, the '.' is taken as the ultimate punctuation printed at the very end.";
    protected const EXAMPLE_HEADING = 'self::EXAMPLES';
    // Do not translate HTML tags!
    protected const EXAMPLE2 = '<em>might produce:</em>';
    protected const EXAMPLE4 = '<em>and, if there were no publisher location or edition entered for that resource and only one page number given, it would produce:</em>';
    protected const EXAMPLE9 = '<em>and, if there were no publisher location or publication year entered for that resource, it would produce:</em>';
    // don't translate 'editor ^ed.^eds.^ '
    protected const EXAMPLE13 = "<em>and, if there were no author entered for that resource and the replacement editor field were 'editor ^ed.^eds.^ ', it would produce:</em>";
    protected const EXAMPLE15 = 'Consider the following (IEEE-type) generic style definition and what it does with a resource type lacking certain fields:';
    // don't translate HTML tags
    protected const EXAMPLE18 = '<em>and, when applied to a resource type with editor and edition fields:</em>';
    protected const EXAMPLE20 = 'Clearly there is a problem here, notably at the end of the resource title. The solution is to use rule no. 10 above:';
    protected const EXAMPLE23 = '<em>and:</em>';
    protected const EXAMPLE25 = 'Bibliographic styles requiring this complexity are few and far between.';
    // TRANSLATORS end here
    ////////////////////////////////////////////////////////////////////////////////////
    // Do not translate these:
    protected const EXAMPLE1 = 'author. |publicationYear. |title. |In [i]book[/i], |edited by editor (^ed^eds^). |publisherLocation%:% |publisherName. |edition ed%,%.% |(Originally published originalPublicationYear) |^p.^pp.^pages|.';

    protected const EXAMPLE3 = 'de Maus, Mickey. 2004. An amusing diversion. In <em>A History of Cartoons</em>, Donald D. A. F. F. Y. Duck, and Bugs Bunny (eds). London: Animatron Publishing. 10th ed, (Originally published 2000) pp.20-9.';

    protected const EXAMPLE5 = 'de Maus, Mickey. 2004. An amusing diversion. In <em>A History of Cartoons</em>, Donald D. A. F. F. Y. Duck, and Bugs Bunny (eds). Animatron Publishing. (Originally published 2000) p.20.';

    protected const EXAMPLE7 = 'author. |[i]title[/i]. |(|publisherLocation%: %|publisherName%, %|publicationYear.|) |ISBN|.';
    protected const EXAMPLE8 = 'de Maus, Mickey. <em>A big book</em> (London: Animatron Publishing, 1999.) 1234-09876.';
    protected const EXAMPLE10 = 'de Maus, Mickey. <em>A big book</em>. (Animatron Publishing.) 1234-09876.';

    protected const EXAMPLE11 = 'author. |publicationYear. |[i]title[/i]. |Edited by editor. |edition ed. |publisherLocation%:%.% |publisherName. |Original `edition`, originalPublicationYear|.';
    protected const EXAMPLE12 = 'Duck, Donald D. A. F. F. Y. 2004. <em>How to Make it Big in Cartoons</em>. Edited by M. de Maus and Goofy. 3rd ed. Selebi Phikwe: Botswana Books. Original edition, 2003.';
    protected const EXAMPLE14 = 'de Maus, Mickey and Goofy eds. 2004. <em>How to Make it Big in Cartoons</em>. 3rd ed. Selebi Phikwe: Botswana Books. Original edition, 2003.';

    protected const EXAMPLE16 = 'creator, |"title,"| in [i]collection[/i], |editor, ^Ed.^Eds.^, |edition ed|. publisherLocation: |publisherName, |publicationYear, |pp. pages|.';
    protected const EXAMPLE17 = "ed Software, \"Mousin' Around,\". Gaborone: Computer Games 'r' Us, 1876.";
    protected const EXAMPLE19 = 'Donald D. A. F. F. Y. de Duck, "How to Make it Big in Cartoons,"Mickey de Maus and Goofy, Eds., 3rd ed. Selebi Phikwe: Botswana Books, 2003.';
    protected const EXAMPLE21 = 'creator, |"title|%," %." %|in [i]collection[/i]|%, %editor, ^Ed.^Eds.^|%, %edition ed|%. %|publisherLocation: |publisherName, |publicationYear, |pp. pages|.';
    protected const EXAMPLE22 = "ed Software, \"Mousin' Around.\" Gaborone: Computer Games 'r' Us, 1876.";
    protected const EXAMPLE24 = 'Donald D. A. F. F. Y. de Duck, "How to Make it Big in Cartoons," Mickey de Maus and Goofy, Eds., 3rd ed. Selebi Phikwe: Botswana Books, 2003.';

    protected string $pString = '';
    protected ?MISC $misc = null;
    protected ?MESSAGES $messages = null;
    protected ?UTF8 $utf8 = null;

    public function __construct()
    {
        include_once('MISC.php');
        $this->misc = new MISC();
        include_once('MESSAGES.php');
        $this->messages = new MESSAGES();
        include_once('../UTF8.php');
        $this->utf8 = new UTF8();
    }

    /**
     * Help page
     */
    public function display()
    {
        $this->pString = $this->misc->h($this->messages->text('heading', 'helpStyles'), false, 3);
        $this->pString .= $this->misc->aName('top');
        $this->pString .= $this->misc->p(self::TEXT4);
        $this->pString .= $this->misc->p(self::TEXT5);
        $this->pString .= $this->misc->p(self::TEXT10);
        $this->pString .= $this->misc->p(self::TEXT6);
        $this->pString .= $this->misc->p(self::TEXT7);
        $this->pString .= $this->misc->p($this->misc->hr());
        $this->pString .= $this->misc->h(self::SYNTAX_HEADING);
        $this->pString .= $this->misc->p(self::SYNTAX1);
        $this->pString .= $this->misc->ol(
            $this->misc->li(self::SYNTAX2) .
            $this->misc->li(self::SYNTAX3) .
            $this->misc->li($this->misc->b(self::SYNTAX4) . ' ' . self::SYNTAX5) .
            $this->misc->li(self::SYNTAX6) .
            $this->misc->li(self::SYNTAX7) .
            $this->misc->li(self::SYNTAX8) .
            $this->misc->li(self::SYNTAX9) .
            $this->misc->li(self::SYNTAX10) .
            $this->misc->li(self::SYNTAX11) .
            $this->misc->li(self::SYNTAX12) .
            $this->misc->li(self::SYNTAX13)
        );
        $this->pString .= $this->misc->p(self::TEXT11);
        $this->pString .= $this->misc->p($this->misc->hr());
        $this->pString .= $this->misc->h(self::EXAMPLE_HEADING);
        $this->pString .= $this->misc->p('<code>' . self::EXAMPLE1 . '</code>' . $this->misc->BR() .
            self::EXAMPLE2 . '</code>' . $this->misc->BR() . '<code>' . self::EXAMPLE3 . '</code>');
        $this->pString .= $this->misc->p(self::EXAMPLE4 . $this->misc->BR() . '<code>' . self::EXAMPLE5 . '</code>');
        $this->pString .= $this->misc->hr();
        $this->pString .= $this->misc->p('<code>' . self::EXAMPLE7 . '</code>' . $this->misc->BR() .
            self::EXAMPLE2 . '</code>' . $this->misc->BR() . '<code>' . self::EXAMPLE8 . '</code>');
        $this->pString .= $this->misc->p(self::EXAMPLE9 . $this->misc->BR() . '<code>' . self::EXAMPLE10 . '</code>');
        $this->pString .= $this->misc->hr();
        $this->pString .= $this->misc->p('<code>' . self::EXAMPLE11 . '</code>' . $this->misc->BR() .
            self::EXAMPLE2 . '</code>' . $this->misc->BR() . '<code>' . self::EXAMPLE12 . '</code>');
        $this->pString .= $this->misc->p(self::EXAMPLE13 . $this->misc->BR() . '<code>' . self::EXAMPLE14 . '</code>');
        $this->pString .= $this->misc->hr();
        $this->pString .= $this->misc->p(self::EXAMPLE15 . $this->misc->BR() . '<code>' . self::EXAMPLE16 . '</code>' . $this->misc->BR() .
            self::EXAMPLE2 . $this->misc->BR() . '<code>' . self::EXAMPLE17 . '</code>' . $this->misc->br() .
            self::EXAMPLE18 . $this->misc->br() . '<code>' . self::EXAMPLE19 . '</code>');
        $this->pString .= $this->misc->p(self::EXAMPLE20 . $this->misc->BR() . '<code>' . self::EXAMPLE21 . '</code>' . $this->misc->BR() .
            self::EXAMPLE2 . $this->misc->BR() . '<code>' . self::EXAMPLE22 . '</code>' . $this->misc->br() .
            self::EXAMPLE23 . $this->misc->br() . '<code>' . self::EXAMPLE24 . '</code>');
        $this->pString .= $this->misc->p(self::EXAMPLE25);
        $this->pString .= $this->misc->hr();
        $this->pString .= $this->misc->p(self::TEXT8);
        $this->pString .= $this->misc->p(self::TEXT9);
        $this->pString .= $this->misc->p($this->misc->a(
            'link',
            $this->utf8->decodeUtf8($this->messages->text('misc', 'top')),
            '#top'
        ), 'small', 'right');
        return $this->pString;
    }
}
