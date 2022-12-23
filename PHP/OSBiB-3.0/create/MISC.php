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
 * Miscellaneous HTML elements
 *
 * @author Mark Grimshaw
 *
 * $Header: /cvsroot/bibliophile/OSBib/create/MISC.php,v 1.1 2005/06/20 22:26:51 sirfragalot Exp $
 */
class MISC
{
    /**
     * Return <hr> HTML element as string
     *
     * @param string $class
     * @return string
     */
    public function hr(string $class = ''): string
    {
        $string = <<< END
<hr class="$class" />
END;
        return $string . "\n";
    }

    /**
     * Return <P> HTML element as string
     */
    public function p(string $data = '', string $class = '', string $align = 'left'): string
    {
        $string = <<< END
<p class="$class" align="$align">$data</p>
END;
        return $string . "\n";
    }

    /**
     * <BR>
     */
    public function br(): string
    {
        $string = <<< END
<br />
END;
        return $string . "\n";
    }

    /**
     * <UL>
     */
    public function ul(string $data, string $class = ''): string
    {
        $string = <<< END
<ul class="$class">$data</ul>
END;
        return $string . "\n";
    }

    /**
     * <OL>
     */
    public function ol(string $data, string $class = '', string $type = '1'): string
    {
        $string = <<< END
<ul class="$class" type="$type">$data</ul>
END;
        return $string . "\n";
    }

    /**
     * Return <li> HTML element as string
     */
    public function li(string $data, string $class = ''): string
    {
        $string = <<< END
<li class="$class">$data</li>
END;
        return $string . "\n";
    }

    /**
     * Return <strong> HTML element as string
     */
    public function b(string $data, string $class = ''): string
    {
        return <<< END
<strong class="$class">$data</strong>
END;
    }

    /**
     * <EM>
     */
    public function i(string $data, string $class = ''): string
    {
        return <<< END
<em class="$class">$data</em>
END;
    }
// <U>
    public function u(string $data, string $class = ''): string
    {
        return <<< END
<u class="$class">$data</u>
END;
    }
// <SPAN>
    public function span(string $data, string $class = ''): string
    {
        return <<< END
<span class="$class">$data</span>
END;
    }

    /**
     * Return <hx> (e.g. h1) HTML element as string
     */
    public function h(string $data, string $class = '', int $level = 4): string
    {
        $tag = 'h' . $level;
        $string = <<< END
<$tag class="$class">$data</$tag>
END;
        return $string . "\n";
    }

    /**
     * Return <img> HTML element as string
     */
    public function img(string $src, string $width, string $height, string $alt = ''): string
    {
        $string = <<< END
<img src="$src" border="0" width="$width" height="$height" alt="$alt" />
END;
        return $string . "\n";
    }

    /**
     * Return <a> HTML element as string
     */
    public function a(string $class, string $label, string $link, string $target = '_self'): string
    {
        // NB - no blank line before END;
        return <<< END
<a class="$class" href="$link" target="$target">$label</a>
END;
    }

    /**
     * Return <a name="..."> HTML element as string
     */
    public function aName(string $name): string
    {
        $string = <<< END
<a name="$name"></a>
END;
        return $string . "\n";
    }

    /**
     * Return HTML element <script src="..."> as string
     */
    public function jsExternal(string $src): string
    {
        $string = <<< END
<script src="$src" type="text/javascript"></script>
END;
        return $string . "\n";
    }
}
