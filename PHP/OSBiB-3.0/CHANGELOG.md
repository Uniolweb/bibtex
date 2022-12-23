# CHANGELOG

## v4.0 - 23.12.2022 -- Sybille Peters

!!! Minimum supported version is PHP 7.4

Major cleanup for PHP 8.1:

- CGL fix, use php-cs-fixer to convert PHP files to unified format, based on PSR-12, see https://www.php-fig.org/psr/psr-12/
- add .editorconfig, see http://EditorConfig.org
  (this also includes adding the properties in the first place)
- Use PHPDoc style comments for functions, classes, etc., see https://en.wikipedia.org/wiki/PHPDoc
- check with phpstan, see https://phpstan.org/user-guide/getting-started

code changes
- Add "typed properties" for classes, see https://www.php.net/manual/en/migration74.new-features.php
- remove or replaced functions which no longer exist, e.g. split(), set_magic_quotes_runtime(0)

Resources:

- PSR-12: https://www.php-fig.org/psr/psr-12/
- editorconfig: http://EditorConfig.org
- PHP 7.4 features: https://www.php.net/manual/en/migration74.new-features.php
- PHPDoc:  https://en.wikipedia.org/wiki/PHPDoc
- phpstan: https://phpstan.org/user-guide/getting-started


## v3.0 - 21/Nov/2005 -- Mark Grimshaw

Major feature enhancements and minor bug fixes.

FEATURES:

- Added citation formatting.  Developers can now send a body of text with citation markup to the citation formatting engine for either in-text and endnote-style (endnotes or footnotes) citation formatting and appending of bibliographies.  The citation engine requires the installation of the bibliographic engine.
- Bibliographic formatting: further options for edition and day fields.
  It is now possible to further define creator strings in resource style templates to cope with more complex styles such as DIN1505.
- Bibliographic/citation formatting now adds footnote templates to the bibliographic templates for each resource type.  This is for styles such as Chicago which use a different format for footnotes and for the complete bibliography.

BUG FIXES:

- Tidied up detection of multiple punctuation in bibliographies.
- Various other minor fixes.

## v2.1 - 17/July/2005 -- Mark Grimshaw

1.  Added further date formatting in the general bibliographic options.
2.  Multiple spaces in bibliographic style templates are now correctly rendered in html.

## v2.0 - 30/June/2005 - Mark Grimshaw & Christian Boulanger

- A web interface OSBib-Create for creation and editing of XML style files has been added to the package.
- The bibtexParse package is now included.
- Users should note that this OSBib package replaces OSBib-Format and OSBib-Create which are deprecated and no longer supported.
- A preview link is displayed next to each resource type template when editing a style.  (Requires JavaScript and, currently, does not work with Internet Explorer due to query string limits.)

## v1.7 - 17/June/2005 - Mark Grimshaw

-  Date ranges are now supported in bibliographic styles.
-  User-defined strings for each of the 12 months may now be supplied in the bibliographic styles.
NB - an upgrade of the bibtexparse package is also required since handling of month fields has been improved in bibtexparse::PARSEMONTHS

## v1.6 - 08/June/2005

Some debugging of creator list formatting in bibliographic styles.  Multiple punctuation following a
name is now allowed if the punctuation characters are different.

## v1.5 -19/May/2005

- Removed a typo.
- Reorganised export filters in preparation for work on citation formatting.
- Added OpenOffice 'sxw' format for export.
- Added bibliography_xml.html describing the structure of the bibliography section of the XML files.

## v1.4 15/May/2005

- Better support for UTF-8 multibyte strings provided by Andrea Rossato.
- Correction of bibtex solution @inproceedings bug.

## v1.3 - 6/May/2005

- Removed some WIKINDX-specific code for bibtex parsing.
- Fixed a bug with bibtex 'misc' reference types.
(The above two affect those using STYLEMAPBIBTEX.)
- Some error checking code for file paths added by Guillaume Gardey.

## v1.2 - 5/May/2005

- Corrected an error in the incorrect formatting of author names for the bibtex solution.
- Based on modifications suggested by Christian Boulanger, changed path information to make setting of flags easier or redundant and made the storing and loading of XML files more flexible:
	a) Changed BIBFORMAT constructor call to:
	$bibformat = new BIBFORMAT(STRING: $pathToOsbibClasses = FALSE [, BOOLEAN: $useBibtex = FALSE]);
	By default, $pathToOsbibClasses will be the same directory as BIBFORMAT.php is in.
	b) $bibformat->bibtexParsePath by default is now a bibtexParse/ directory in the same directory as BIBFORMAT.php is
	in. This path is where PARSECREATORS, PARSEMONTH and PARSEPAGE classes can be found if you wish to use
	STYLEMAPBIBTEX.
	c) The XML files are downloaded from bibliophile in uppercase format (e.g. APA.xml).  If you wish to store them in
	lowercase (e.g. apa.xml), BIBF0RMAT::loadStyle() now automatically detects this.
Unless you store PARSECREATORS, PARSEMONTH and PARSEPAGE classes elsewhere, there is now no need to set
$bibformat->bibtexParsePath.
-  Added an osbib.html page as a more easily navigable verion of README.

## v1.1 - 29/April/2005

-  Added an (almost) 'out-of-the-box' BibTeX solution.
-  Added the method BIBFORMAT::addAllOtherItems().

## v1.0 - 28/April/2005

Initial release
