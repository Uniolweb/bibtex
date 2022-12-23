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
* Session functions
*
* @author Mark Grimshaw
*
* $Header: /cvsroot/bibliophile/OSBib/create/SESSION.php,v 1.1 2005/06/20 22:26:51 sirfragalot Exp $
*/
class SESSION
{
    protected array $sessionVars = [];

    public function __construct()
    {
        if (isset($_SESSION)) {
            $this->sessionVars = &$_SESSION;
        }
    }

    /**
     * Set a session variable
     */
    public function setVar(?string $key, ?string $value): bool
    {
        if (!isset($key) || !isset($value)) {
            return false;
        }
        $this->sessionVars[$key] = $value;
        if (!isset($this->sessionVars[$key])) {
            return false;
        }
        return true;
    }

    /**
     * Get a session variable
     */
    public function getVar(string $key): string
    {
        if (isset($this->sessionVars[$key])) {
            return $this->sessionVars[$key];
        }
        return '';
    }

    /**
     * Delete a session variable
     */
    public function delVar(string $key): void
    {
        if (isset($this->sessionVars[$key])) {
            unset($this->sessionVars[$key]);
        }
    }

    /**
     * Is a session variable set?
     */
    public function issetVar(string $key): bool
    {
        if (isset($this->sessionVars[$key])) {
            return true;
        }
        return false;
    }

    /**
     * Destroy the whole session
     */
    public function destroy(): void
    {
        $this->sessionVars = [];
    }

    /**
     * Return an associative array of all session variables starting with $prefix_.
     * key in returned array is minus the prefix to aid in matching database table fields.
     */
    public function getArray(string $prefix): array
    {
        $prefix .= '_';
        foreach ($this->sessionVars as $key => $value) {
            if (preg_match("/^$prefix(.*)/", $key, $matches)) {
                $array[$matches[1]] = $value;
            }
        }
        if (isset($array)) {
            return $array;
        }
        return [];
    }

    /**
     * Write to session variables named with $prefix_ the given associative array
     */
    public function writeArray(array $row, string $prefix = ''): bool
    {
        foreach ($row as $key => $value) {
            if (!$value) {
                $value = false;
            }
            if ($prefix) {
                if (!$this->setVar($prefix . '_' . $key, $value)) {
                    return false;
                }
            } else {
                if (!$this->setVar($key, $value)) {
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * Clear session variables named with $prefix
     */
    public function clearArray(string $prefix): void
    {
        $prefix .= '_';
        foreach ($this->sessionVars as $key => $value) {
            if (preg_match("/^$prefix/", $key)) {
                $this->delVar($key);
            }
        }
    }
}
