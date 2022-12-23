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

/*****
 * Initialize a few default variables before we truly enter the system.
 *
 * $Header: /cvsroot/bibliophile/OSBib/create/INIT.php,v 1.1 2005/06/20 22:26:51 sirfragalot Exp $
 */
class INIT
{
    public function __construct()
    {
        // Set to error_reporting(0) before release!!!!!!!!!
        // For debugging, set to E_ALL
        error_reporting(E_ALL);
        // error_reporting(0);
        // buffer printing to browser
        ob_start();
        // make sure that Session output is XHTML conform ('&amp;' instead of '&')
        ini_set('arg_separator.output', '&amp;');

        // Check we have PHP 7.4 and above.
        if (($PHP_VERSION = phpversion()) < '7.4') {
            die("OSBib requires PHP 7.4 or greater.  Your PHP version is $PHP_VERSION. Please upgrade.");
        }
    }

    /**
     * Make sure we get HTTP VARS in whatever format they come in
     */
    public function getVars(): array
    {
        if (!empty($_POST)) {
            $vars = $_POST;
        } elseif (!empty($_GET)) {
            $vars = $_GET;
        } else {
            return [];
        }
        $vars = array_map(['INIT', 'magicSlashes'], $vars);
        return $vars;
    }

    /**
     * start the SESSION
     */
    public function startSession(): void
    {
        // start session
        session_start();
    }

    /**
     * Add slashes to all incoming GET/POST data.  We now know what we're dealing with and can code accordingly.
     *
     * @param array|string $element
     * @return array|string
     */
    public function magicSlashes($element)
    {
        if (is_array($element)) {
            return array_map(['INIT', 'magicSlashes'], $element);
        }

        return addslashes($element);
    }
}
