<?php

declare(strict_types=1);
namespace Uniolweb\Bibtex\Bibtex2Html\Utility;

/**
 * Format of a doi:
 * - "DOI names are case insensitive"
 * - "DOI names may incorporate any printable characters from the Universal Character Set (UCS-2), of ISO/IEC 10646, which is the character set defined by Unicode."
 * - "The DOI syntax shall be made up of a DOI prefix and a DOI suffix separated by a forward slash."
 * - "There is no defined limit on the length of the DOI name, or of the DOI prefix or DOI suffix."
 * - "The DOI name is case-insensitive and can incorporate any printable characters from the legal graphic characters of Unicode."
 * - "The DOI prefix shall be composed of a directory indicator followed by a registrant code. These two components shall be separated by a full stop (period)."
 * - "The directory indicator shall be “10”. The directory indicator distinguishes the entire set of character strings (prefix and suffix) as digital object identifiers within the resolution system."
 *
 *  (simplified EBNF):
 *  doi = doi prefix "/" doi suffix
 *    doi prefix = directory indicator "." registrant code
 *      directory indicator = "10"
 *      registrant code = (can contain ".")
 *    doi suffix
 *
 *
 * Derived rules:
 * - doi MUST contain a slash (/)
 * - doi MUST start with 10.
 *
 *
 * Resources:
 * - DOI spec: https://www.doi.org/the-identifier/resources/handbook/
 * - Andrew Gilmartin Blog: https://www.crossref.org/blog/dois-and-matching-regular-expressions/
 *
 * regexes do not "catch" all
 * - /^10.\d{4,9}/[-._;()/:A-Z0-9]+$/i
 * - /^10.1002/[^\s]+$/i
 * - /^10.\d{4}/\d+-\d+X?(\d+)\d+<[\d\w]+:[\d\w]*>\d+.\d+.\w+;\d$/i
 * - /^10.1021/\w\w\d++$/i
 * - /^10.1207/[\w\d]+\&\d+_\d+$/i
 */
class DoiUtility
{
    /**
     * @var array
     * @todo find better solution to normalize the dois, some of the chars are from bibtex, e.g. enclose in {}
     */
    protected const doiMappings = [
        '{10.1007/978--3--642--29208--8{\\_}2}' => '10.1007/978-3-642-29208-8_2',
    ];

    /**
     * Get the normalized name of the DOI, ready for printing as link text. The string "doi: " may be added later
     * and is not returned here as it is usually not part of the link.
     *
     * "When displayed on screen or in print, a DOI name is preceded by a lowercase “doi:” unless the context clearly
     * indicates that a DOI name is implied. The “doi:” label is not part of the DOI name value."
     *
     * https://www.doi.org/the-identifier/resources/handbook/2_numbering
     *
     * @param string $doi
     * @return string
     */
    public static function getDoiLinkText(string $doi): string
    {
        if (!$doi) {
            return '';
        }
        return \htmlspecialchars($doi);
    }

    /**
     * Convert a DOI into a DOI URL.
     * !!! URL may have to be escaped / encoded differently from link text
     *
     * Encoding in URLs
     * "Hexadecimal (%) encoding must be used for characters in a DOI that are not allowed, or have other meanings, in URLs or URNs."
     * https://www.doi.org/the-identifier/resources/handbook/2_numbering
     *
     * @param string $doi
     * @return string
     */
    public static function getDoiUrl(string $doi): string
    {
        if (!$doi) {
            return '';
        }
        return 'https://doi.org/' . urlencode(ltrim($doi, '/'));
    }

    /**
     * Normalize doi
     * - trim
     * - remove some escape characters, e.g. \_
     *
     * We must except the worst: the doi itself may be
     * formatted in all kinds of shapes and forms
     *
     * e.g.
     * 1. "pure" Doi: just append to URL https://doi.org
     * 2. URL, e.g. http://doi.org, https://doi.org, http://dx.doi.org: remove all except path part of URL
     *
     * !!! do not do any escaping here, the escaping depends whether the doi is formatted as URL or as linktext, for
     * example
     *
     * @param string $doi
     * @return string
     * @todo check if valid doi
     */
    public static function normalizeDoi(string $doi): string
    {
        if (!$doi) {
            return '';
        }

        $doi = trim($doi);

        if (preg_match('#^https?://#', $doi)) {
            // is URL, retrieve path only portion
            $doi = parse_url($doi, PHP_URL_PATH);
            if (!$doi) {
                return '';
            }
        }

        // todo: how to remove {} and escaped chars in general?
        // examples:
        // {10.1007/978--3--642--29208--8{\_}2} => 10.1007/978-3-642-29208-8_2

        if (isset(self::doiMappings[$doi])) {
            $doi = self::doiMappings[$doi];
        } else {
            $doi = str_replace('$\\backslash$textunderscore', '', $doi);
            $doi = str_replace(
                [
                '{\\_}',
                '\\_',
                '{\\textunderscore }',
                '{\\textunderscore}',
                ],
                '_',
                $doi
            );
            $doi = str_replace(['{', '}'], '', $doi);
        }

        return ltrim($doi, '/');
    }
}
