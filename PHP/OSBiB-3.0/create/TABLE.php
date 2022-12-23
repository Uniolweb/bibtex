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
* HTML TABLE elements
*
* @author Mark Grimshaw
*
* $Header: /cvsroot/bibliophile/OSBib/create/TABLE.php,v 1.1 2005/06/20 22:26:51 sirfragalot Exp $
*/
class TABLE
{
    /**
     * code for starting a table
     * @param string $class
     * @param string|int $border
     * @param string|int $spacing
     * @param string|int $padding
     * @param string $align
     * @param string $width
     * @return string
     */
    public function tableStart(
        string $class = '',
        $border = 0,
        $spacing = 0,
        $padding = 0,
        string $align = 'center',
        string $width='100%'
    ): string {
        $string = <<< END
<table class="$class" border="$border" cellspacing="$spacing" cellpadding="$padding" align="$align" width="$width">
END;
        return $string . "\n";
    }

    /**
     * code for ending a table
     */
    public function tableEnd(): string
    {
        $string = <<< END
</table>
END;
        return $string . "\n";
    }

    /**
     * return properly formatted <tr> start tag
     */
    public function trStart(string $class = '', string $align = 'left', string $vAlign = 'top'): string
    {
        $string = <<< END
<tr class="$class" align="$align" valign="$vAlign">
END;
        return $string . "\n";
    }

    /**
     * return properly formatted <tr> end tag
     */
    public function trEnd(): string
    {
        $string = <<< END
</tr>
END;
        return $string . "\n";
    }

    /**
     * return properly formatted <td> tag
     */
    public function td(string $data, string $class = '', string $align = 'left', string $vAlign = 'top', string $colSpan = '', string $width = ''): string
    {
        $string = <<< END
<td class="$class" align="$align" valign="$vAlign" colspan="$colSpan" width="$width">
$data
</td>
END;
        return $string . "\n";
    }

    /**
     * return start TD tag
     */
    public function tdStart(string $class = '', string $align = 'left', string $vAlign = 'top', string $colSpan = ''): string
    {
        return "<td class=\"$class\" align=\"$align\" valign=\"$vAlign\" colspan=\"$colSpan\">\n";
    }

    /**
     * return td end tag
     */
    public function tdEnd(): string
    {
        return "</td>\n";
    }
}
