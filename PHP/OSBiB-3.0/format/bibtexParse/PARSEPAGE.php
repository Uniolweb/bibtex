<?php
/*
Released through http://bibliophile.sourceforge.net under the GPL licence.
Do whatever you like with this -- some credit to the author(s) would be appreciated.

A collection of PHP classes to manipulate bibtex files.

If you make improvements, please consider contacting the administrators at bibliophile.sourceforge.net
so that your improvements can be added to the release package.

Mark Grimshaw 2005
http://bibliophile.sourceforge.net*/
/**
 * BibTeX PAGES import class
 */
class PARSEPAGE
{
    protected const NO_VALID_RESULT = [false, false];

    /** @var array<int,bool|string>>  */
    protected array $return = [];

    /**
     * Create page arrays from bibtex input.
     * 'pages' field can be:
     * "77--99"
     * "3 - 5"
     * "ix -- 101"
     * "73+"
     * 73, 89,103"
     * Currently, PARSEPAGE will take 1/, 2/ and 3/ above as page_start and page_end and, in the other cases, will accept
     * the first valid number it finds from the left as page_start setting page_end to NULL
     *
     * @param string $item the pages, e.g. "493"
     * @return array<int,int|bool> Array consisting of [$start, $end], array elements are either int or false
     *
     * @todo this never returns end as a value, possible bug?
     * @todo return a class for better type checking and more clarity what values are expected
     */
    public function init(string $item): array
    {
        $item = trim($item);
        if ($this->type1($item)) {
            return self::NO_VALID_RESULT;
        }
        // else, return first number we can find
        if (preg_match("/(\d+|[ivx]+)/i", $item, $array)) {
            $start = $array[1] ?? '';
            if ($start) {
                $start = trim($start);
            }
            return [$start, false];
        }
        // No valid page numbers found
        return self::NO_VALID_RESULT;
    }

    /**
     * "77--99" or '-'type?
     */
    public function type1(string $item): bool
    {
        /** @var string|bool $start */
        $start =  false;
        /** @var string|bool $end */
        $end = false;
        $array = preg_split('/--|-/', $item);
        if (count($array) > 1) {
            if (is_numeric(trim($array[0]))) {
                $start = trim($array[0]);
            } else {
                $start = strtolower(trim($array[0]));
            }
            if (is_numeric(trim($array[1]))) {
                $end = trim($array[1]);
            } else {
                $end = strtolower(trim($array[1]));
            }
            if ($end && !$start) {
                $this->return = [$end, $start];
            } else {
                $this->return = [$start, $end];
            }
            return true;
        }
        return false;
    }
}
