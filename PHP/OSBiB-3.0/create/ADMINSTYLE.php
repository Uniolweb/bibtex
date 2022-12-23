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
* ADMINSTYLE class.
*
* Administration of citation bibliographic styles
*
* $Header: /cvsroot/bibliophile/OSBib/create/ADMINSTYLE.php,v 1.10 2005/11/14 06:38:15 sirfragalot Exp $
*****/
class ADMINSTYLE
{
    /**
     * THE OSBIB Version number
     */
    protected const OSBIB_VERSION = '3.0';

    protected bool $footnotePages = false;

    /** @var string function name to use for displaying errors */
    protected string $errorDisplay = '';
    protected array $vars = [];
    protected array $styles = [];
    protected array $creators = [];
    protected array $fallback = [];
    protected ?SESSION $session = null;
    protected ?MESSAGES $messages = null;
    protected ?LOADSTYLE $style = null;
    protected ?SUCCESS $success = null;
    protected ?ERRORS $errors = null;
    protected ?MISC $misc = null;
    protected ?FORM $form = null;
    protected ?STYLEMAP $map = null;
    protected ?UTF8 $utf8 = null;
    protected ?TABLE $table = null;

    public function __construct(array $vars)
    {
        $this->vars = $vars;
        include_once(__DIR__ . '/SESSION.php');
        $this->session = new SESSION();
        include_once(__DIR__ . '/MESSAGES.php');
        $this->messages = new MESSAGES();
        include_once(__DIR__ . '/SUCCESS.php');
        $this->success = new SUCCESS();
        include_once(__DIR__ . '/ERRORS.php');
        $this->errors = new ERRORS();
        include_once(__DIR__ . '/MISC.php');
        $this->misc = new MISC();
        include_once(__DIR__ . '/FORM.php');
        $this->form = new FORM();
        include_once(__DIR__ . '/../LOADSTYLE.php');
        $this->style = new LOADSTYLE();
        $this->styles = $this->style->loadDir(OSBIB_STYLE_DIR);
        $this->creators = ['creator1', 'creator2', 'creator3', 'creator4', 'creator5'];
    }

    /**
     * Run method as passed in parameter $method.
     *
     * old comment: (check we really are admin)
     *
     * @param string $method (can be a function name, such as 'display', 'addInt', 'add', 'editInit' ...
     * @return mixed
     * @todo this function has little use as it is now (unless it is extended) except to obfuscate and confuse,
     *   deprecate it and call the functions directly?
     */
    public function gateKeep(string $method)
    {
        // todo ??? is something missing here???

        // else, run $method
        return $this->$method();
    }

    /**
     * display options for styles
     */
    public function display(string $message = ''): string
    {
        // Clear previous style in session
        $this->session->clearArray('cite');
        $this->session->clearArray('style');
        $pString = $this->misc->h($this->messages->text('heading', 'styles'), false, 3);
        if ($message) {
            $pString .= $this->misc->p($message);
        }
        $pString .= $this->misc->p($this->misc->a(
            'link',
            $this->messages->text('style', 'addLabel'),
            'index.php?action=adminStyleAddInit'
        ));
        if (count($this->styles)) {
            $pString .= $this->misc->p($this->misc->a(
                'link',
                $this->messages->text('style', 'copyLabel'),
                'index.php?action=adminStyleCopyInit'
            ));
            $pString .= $this->misc->p($this->misc->a(
                'link',
                $this->messages->text('style', 'editLabel'),
                'index.php?action=adminStyleEditInit'
            ));
        }
        return $pString;
    }

    /**
     * Add a style - display options.
     */
    public function addInit(bool $error = false): string
    {
        $pString = $this->misc->h($this->messages->text(
            'heading',
            'styles',
            ' (' . $this->messages->text('style', 'addLabel') . ')'
        ), false, 3);
        if ($error) {
            $pString .= $this->misc->p($error, 'error', 'center');
        }
        $pString .= $this->displayStyleForm('add');
        return $pString;
    }

    /**
     * Write style to text file
     */
    public function add(): string
    {
        if ($error = $this->validateInput('add')) {
            $this->badInput($error, 'addInit');
        }
        $this->writeFile();
        $pString = $this->success->text('style', ' ' . $this->messages->text('misc', 'added') . ' ');
        $this->styles = $this->style->loadDir(OSBIB_STYLE_DIR);
        return $this->display($pString);
    }

    /**
     * display styles for editing
     *
     * @todo $error is not used!
     */
    public function editInit(bool $error = false): string
    {
        $pString = $this->misc->h($this->messages->text(
            'heading',
            'styles',
            ' (' . $this->messages->text('style', 'editLabel') . ')'
        ), false, 3);
        $pString .= $this->form->formHeader('adminStyleEditDisplay');
        $styleFile = $this->session->getVar('editStyleFile');
        if ($styleFile) {
            $pString .= $this->form->selectedBoxValue(false, 'editStyleFile', $this->styles, $styleFile, 20);
        } else {
            $pString .= $this->form->selectFBoxValue(false, 'editStyleFile', $this->styles, 20);
        }
        $pString .= $this->misc->br() . $this->form->formSubmit('Edit');
        $pString .= $this->form->formEnd();
        return $pString;
    }

    /**
     * Display a style for editing.
     */
    public function editDisplay(bool $error = false): string
    {
        if (!$error) {
            $this->loadEditSession();
        }
        $pString = $this->misc->h($this->messages->text(
            'heading',
            'styles',
            ' (' . $this->messages->text('style', 'editLabel') . ')'
        ), false, 3);
        if ($error) {
            $pString .= $this->misc->p($error, 'error', 'center');
        }
        $pString .= $this->displayStyleForm('edit');
        return $pString;
    }

    /**
     * Read data from style file and load it into the session
     */
    public function loadEditSession(bool $copy = false): void
    {
        // Clear previous style in session
        $this->session->clearArray('style');
        $this->session->clearArray('cite');
        $this->session->clearArray('footnote');
        include_once(__DIR__ . '/../PARSEXML.php');
        $parseXML = new PARSEXML();
        include_once(__DIR__ . '/../STYLEMAP.php');
        $styleMap = new STYLEMAP();
        $resourceTypes = array_keys($styleMap->getTypes());
        $this->session->setVar('editStyleFile', $this->vars['editStyleFile']);
        $dir = strtolower($this->vars['editStyleFile']);
        $fileName = $this->vars['editStyleFile'] . '.xml';
        if ($fh = fopen(OSBIB_STYLE_DIR . '/' . $dir . '/' . $fileName, 'r')) {
            list($info, $citation, $footnote, $common, $types) = $parseXML->extractEntries($fh);
            if (!$copy) {
                $this->session->setVar('style_shortName', $this->vars['editStyleFile']);
                $this->session->setVar('style_longName', base64_encode($info['description']));
            }
            foreach ($citation as $array) {
                if (array_key_exists('_NAME', $array) && array_key_exists('_DATA', $array)) {
                    $this->session->setVar(
                        'cite_' . $array['_NAME'],
                        base64_encode(htmlspecialchars($array['_DATA']))
                    );
                }
            }
            $this->arrayToTemplate($footnote, true);
            foreach ($resourceTypes as $type) {
                $type = 'footnote_' . $type;
                $sessionKey = $type . 'Template';
                if (!empty($this->$type)) {
                    $this->session->setVar($sessionKey, base64_encode(htmlspecialchars($this->$type)));
                }
                unset($this->$type);
            }
            foreach ($common as $array) {
                if (array_key_exists('_NAME', $array) && array_key_exists('_DATA', $array)) {
                    $this->session->setVar(
                        'style_' . $array['_NAME'],
                        base64_encode(htmlspecialchars($array['_DATA']))
                    );
                }
            }
            $this->arrayToTemplate($types);
            foreach ($resourceTypes as $type) {
                $sessionKey = 'style_' . $type;
                if (!empty($this->$type)) {
                    $this->session->setVar($sessionKey, base64_encode(htmlspecialchars($this->$type)));
                }
                if (array_key_exists($type, $this->fallback)) {
                    $sessionKey .= '_generic';
                    $this->session->setVar($sessionKey, base64_encode($this->fallback[$type]));
                }
            }
        } else {
            $this->badInput($this->errors->text('file', 'read'), $this->errorDisplay);
        }
    }

    /**
     * Transform XML nodal array to resource type template strings for loading into the style editor
     */
    public function arrayToTemplate(array $types, bool $footnote = false): void
    {
        $this->fallback = [];
        foreach ($types as $resourceArray) {
            if ($footnote && ($resourceArray['_NAME'] != 'resource')) {
                $this->session->setVar(
                    'footnote_' . $resourceArray['_NAME'],
                    base64_encode(htmlspecialchars($resourceArray['_DATA']))
                );
                continue;
            }
            $temp = $tempArray = $newArray = $independent = [];
            $empty = $ultimate = $preliminary = false;
            /**
            * The resource type which will be our array name
            */
            if ($footnote) {
                $type = 'footnote_' . $resourceArray['_ATTRIBUTES']['name'];
            } else {
                $type = $resourceArray['_ATTRIBUTES']['name'];
                $this->writeSessionRewriteCreators($type, $resourceArray);
            }
            $styleDefinition = $resourceArray['_ELEMENTS'];
            foreach ($styleDefinition as $array) {
                if (array_key_exists('_NAME', $array) && array_key_exists('_DATA', $array)
                     && array_key_exists('_ELEMENTS', $array)) {
                    if ($array['_NAME'] == 'ultimate') {
                        $temp['ultimate'] = $array['_DATA'];
                        continue;
                    }
                    if ($array['_NAME'] == 'preliminaryText') {
                        $temp['preliminaryText'] = $array['_DATA'];
                        continue;
                    }
                    if (empty($array['_ELEMENTS']) && !$footnote) {
                        $this->fallback[$type] = $array['_DATA'];
                        //						$empty = TRUE;
                    }
                    foreach ($array['_ELEMENTS'] as $elements) {
                        if ($array['_NAME'] == 'independent') {
                            $split = mb_split('_', $elements['_NAME']);
                            $temp[$array['_NAME']][$split[1]]
                            = $elements['_DATA'];
                        } else {
                            $temp[$array['_NAME']][$elements['_NAME']]
                            = $elements['_DATA'];
                        }
                    }
                }
            }
            /**
            * Now parse the temp array into template strings
            */
            foreach ($temp as $key => $value) {
                if (!is_array($value)) {
                    if ($key == 'ultimate') {
                        $ultimate = $value;
                    }
                    if ($key == 'preliminaryText') {
                        $preliminary = $value;
                    }
                    continue;
                }
                if (($key == 'independent')) {
                    $independent = $value;
                    continue;
                }
                $pre = $post = $dependentPre = $dependentPost = $dependentPreAlternative =
                    $dependentPostAlternative = $singular = $plural = $string = false;
                if (array_key_exists('pre', $value)) {
                    $string .= $value['pre'];
                }
                $string .= $key;
                if (array_key_exists('post', $value)) {
                    $string .= $value['post'];
                }
                if (array_key_exists('dependentPre', $value)) {
                    $replace = '%' . $value['dependentPre'] . '%';
                    if (array_key_exists('dependentPreAlternative', $value)) {
                        $replace .= $value['dependentPreAlternative'] . '%';
                    }
                    $string = str_replace('__DEPENDENT_ON_PREVIOUS_FIELD__', $replace, $string);
                }
                if (array_key_exists('dependentPost', $value)) {
                    $replace = '%' . $value['dependentPost'] . '%';
                    if (array_key_exists('dependentPostAlternative', $value)) {
                        $replace .= $value['dependentPostAlternative'] . '%';
                    }
                    $string = str_replace('__DEPENDENT_ON_NEXT_FIELD__', $replace, $string);
                }
                if (array_key_exists('singular', $value) && array_key_exists('plural', $value)) {
                    $replace = '^' . $value['singular'] . '^' . $value['plural'] . '^';
                    $string = str_replace('__SINGULAR_PLURAL__', $replace, $string);
                }
                $tempArray[] = $string;
            }
            if (!empty($independent)) {
                $firstOfPair = false;
                foreach ($tempArray as $index => $value) {
                    if (!$firstOfPair) {
                        if (array_key_exists($index, $independent)) {
                            $newArray[] = $independent[$index] . '|' . $value;
                            //							$newArray[] = $value . '|' . $independent[$index];
                            $firstOfPair = true;
                            continue;
                        }
                    } else {
                        if (array_key_exists($index, $independent)) {
                            $newArray[] = $value . '|' . $independent[$index];
                            $firstOfPair = false;
                            continue;
                        }
                    }
                    $newArray[] = $value;
                }
            } else {
                $newArray = $tempArray;
            }
            $tempString = implode('|', $newArray);
            if ($ultimate && (substr($tempString, -1, 1) != $ultimate)) {
                $tempString .= '|' . $ultimate;
            }
            if ($preliminary) {
                $tempString = $preliminary . '|' . $tempString;
            }
            $this->$type = $tempString;
        }
    }

    /**
     * Add resource-specific rewrite creator fields to session
     */
    public function writeSessionRewriteCreators(string $type, array $array): void
    {
        foreach ($this->creators as $creatorField) {
            $name = $creatorField . '_firstString';
            if (array_key_exists($name, $array['_ATTRIBUTES'])) {
                $sessionKey = 'style_' . $type . '_' . $name;
                $this->session->setVar(
                    $sessionKey,
                    base64_encode(htmlspecialchars($array['_ATTRIBUTES'][$name]))
                );
            }
            $name = $creatorField . '_firstString_before';
            if (array_key_exists($name, $array['_ATTRIBUTES'])) {
                $sessionKey = 'style_' . $type . '_' . $name;
                $this->session->setVar(
                    $sessionKey,
                    base64_encode(htmlspecialchars($array['_ATTRIBUTES'][$name]))
                );
            }
            $name = $creatorField . '_remainderString';
            if (array_key_exists($name, $array['_ATTRIBUTES'])) {
                $sessionKey = 'style_' . $type . '_' . $name;
                $this->session->setVar(
                    $sessionKey,
                    base64_encode(htmlspecialchars($array['_ATTRIBUTES'][$name]))
                );
            }
            $name = $creatorField . '_remainderString_before';
            if (array_key_exists($name, $array['_ATTRIBUTES'])) {
                $sessionKey = 'style_' . $type . '_' . $name;
                $this->session->setVar(
                    $sessionKey,
                    base64_encode(htmlspecialchars($array['_ATTRIBUTES'][$name]))
                );
            }
            $name = $creatorField . '_remainderString_each';
            if (array_key_exists($name, $array['_ATTRIBUTES'])) {
                $sessionKey = 'style_' . $type . '_' . $name;
                $this->session->setVar(
                    $sessionKey,
                    base64_encode(htmlspecialchars($array['_ATTRIBUTES'][$name]))
                );
            }
        }
    }

    /**
     * Edit groups
     */
    public function edit()
    {
        if ($error = $this->validateInput('edit')) {
            $this->badInput($error, 'editDisplay');
        }
        $dirName = OSBIB_STYLE_DIR . '/' . strtolower(trim($this->vars['styleShortName']));
        $fileName = $dirName . '/' . strtoupper(trim($this->vars['styleShortName'])) . '.xml';
        $this->writeFile($fileName);
        $pString = $this->success->text('style', ' ' . $this->messages->text('misc', 'edited') . ' ');
        return $this->display($pString);
    }

    /**
     * display groups for copying and making a new style
     */
    public function copyInit($error = false)
    {
        $pString = $this->misc->h($this->messages->text(
            'heading',
            'styles',
            ' (' . $this->messages->text('style', 'copyLabel') . ')'
        ), false, 3);
        $pString .= $this->form->formHeader('adminStyleCopyDisplay');
        $pString .= $this->form->selectFBoxValue(false, 'editStyleFile', $this->styles, 20);
        $pString .= $this->misc->br() . $this->form->formSubmit('Edit');
        $pString .= $this->form->formEnd();
        return $pString;
    }

    /**
     * Display a style for copying.
     */
    public function copyDisplay($error = false)
    {
        if (!$error) {
            $this->loadEditSession(true);
        }
        $pString = $this->misc->h($this->messages->text(
            'heading',
            'styles',
            ' (' . $this->messages->text('style', 'copyLabel') . ')'
        ), false, 3);
        if ($error) {
            $pString .= $this->misc->p($error, 'error', 'center');
        }
        $pString .= $this->displayStyleForm('copy');
        return $pString;
    }

    /**
     * display the citation templating form
     */
    public function displayCiteForm(string $type)
    {
        include_once(__DIR__ . '/TABLE.php');
        $this->table = new TABLE();
        include_once(__DIR__ . '/../STYLEMAP.php');
        $this->map = new STYLEMAP();
        $pString = $this->misc->h($this->messages->text('cite', 'citationFormat') . ' (' .
            $this->messages->text('cite', 'citationFormatInText') . ')');
        // 1st., creator style
        $pString .= $this->table->tableStart('styleTable', 1, false, 5);
        $pString .= $this->table->trStart();
        $exampleName = ['Joe Bloggs', 'Bloggs, Joe', 'Bloggs Joe',
            $this->messages->text('cite', 'lastName')];
        $exampleInitials = ['T. U. ', 'T.U.', 'T U ', 'TU'];
        $example = [$this->messages->text('style', 'creatorFirstNameFull'),
            $this->messages->text('style', 'creatorFirstNameInitials')];
        $firstStyle = base64_decode($this->session->getVar('cite_creatorStyle'));
        $otherStyle = base64_decode($this->session->getVar('cite_creatorOtherStyle'));
        $initials = base64_decode($this->session->getVar('cite_creatorInitials'));
        $firstName = base64_decode($this->session->getVar('cite_creatorFirstName'));
        $useInitials = base64_decode($this->session->getVar('cite_useInitials'));
        $td = $this->misc->b($this->messages->text('cite', 'creatorStyle')) . $this->misc->br() .
            $this->form->selectedBoxValue(
                $this->messages->text('style', 'creatorFirstStyle'),
                'cite_creatorStyle',
                $exampleName,
                $firstStyle,
                4
            );
        $td .= $this->misc->br() . '&nbsp;' . $this->misc->br();
        $td .= $this->form->selectedBoxValue(
            $this->messages->text('style', 'creatorOthers'),
            'cite_creatorOtherStyle',
            $exampleName,
            $otherStyle,
            4
        );
        $td .= $this->misc->br() . '&nbsp;' . $this->misc->br();
        $td .= $this->messages->text('cite', 'useInitials') . ' ' . $this->form->checkbox(
            false,
            'cite_useInitials',
            $useInitials
        );
        $td .= $this->misc->br() . '&nbsp;' . $this->misc->br();
        $td .= $this->form->selectedBoxValue(
            $this->messages->text('style', 'creatorInitials'),
            'cite_creatorInitials',
            $exampleInitials,
            $initials,
            4
        );
        $td .= $this->misc->br() . '&nbsp;' . $this->misc->br();
        $td .= $this->form->selectedBoxValue(
            $this->messages->text('style', 'creatorFirstName'),
            'cite_creatorFirstName',
            $example,
            $firstName,
            2
        );
        $uppercase = base64_decode($this->session->getVar('cite_creatorUppercase')) ?
            true : false;
        $td .= $this->misc->P($this->form->checkbox(
            $this->messages->text('style', 'uppercaseCreator'),
            'cite_creatorUppercase',
            $uppercase
        ));
        $pString .= $this->table->td($td);
        // Delimiters
        $twoCreatorsSep = stripslashes(base64_decode($this->session->getVar('cite_twoCreatorsSep')));
        $betweenFirst = stripslashes(base64_decode($this->session->getVar('cite_creatorSepFirstBetween')));
        $betweenNext = stripslashes(base64_decode($this->session->getVar('cite_creatorSepNextBetween')));
        $last = stripslashes(base64_decode($this->session->getVar('cite_creatorSepNextLast')));
        $td = $this->misc->b($this->messages->text('cite', 'creatorSep')) .
            $this->misc->p($this->messages->text('style', 'ifOnlyTwoCreators') . '&nbsp;' .
            $this->form->textInput(false, 'cite_twoCreatorsSep', $twoCreatorsSep, 7, 255)) .
            $this->messages->text('style', 'sepCreatorsFirst') . '&nbsp;' .
            $this->form->textInput(
                false,
                'cite_creatorSepFirstBetween',
                $betweenFirst,
                7,
                255
            ) . $this->misc->br() .
            $this->misc->p($this->messages->text('style', 'sepCreatorsNext') . $this->misc->br() .
            $this->messages->text('style', 'creatorSepBetween') . '&nbsp;' .
            $this->form->textInput(false, 'cite_creatorSepNextBetween', $betweenNext, 7, 255) .
            $this->messages->text('style', 'creatorSepLast') . '&nbsp;' .
            $this->form->textInput(false, 'cite_creatorSepNextLast', $last, 7, 255));
        $td .= $this->misc->br() . '&nbsp;' . $this->misc->br();
        // List abbreviation
        $example = [$this->messages->text('style', 'creatorListFull'),
            $this->messages->text('style', 'creatorListLimit')];
        $list = base64_decode($this->session->getVar('cite_creatorList'));
        $listMore = stripslashes(base64_decode($this->session->getVar('cite_creatorListMore')));
        $listLimit = stripslashes(base64_decode($this->session->getVar('cite_creatorListLimit')));
        $listAbbreviation = stripslashes(base64_decode($this->session->getVar('cite_creatorListAbbreviation')));
        $italic = base64_decode($this->session->getVar('cite_creatorListAbbreviationItalic')) ?
            true : false;
        $td .= $this->misc->b($this->messages->text('cite', 'creatorList')) .
            $this->misc->p($this->form->selectedBoxValue(
                false,
                'cite_creatorList',
                $example,
                $list,
                2
            ) . $this->misc->br() .
            $this->messages->text('style', 'creatorListIf') . ' ' .
            $this->form->textInput(false, 'cite_creatorListMore', $listMore, 3) .
            $this->messages->text('style', 'creatorListOrMore') . ' ' .
            $this->form->textInput(false, 'cite_creatorListLimit', $listLimit, 3) . $this->misc->br() .
            $this->messages->text('style', 'creatorListAbbreviation') . ' ' .
            $this->form->textInput(false, 'cite_creatorListAbbreviation', $listAbbreviation, 15) . ' ' .
            $this->form->checkbox(false, 'cite_creatorListAbbreviationItalic', $italic) . ' ' .
            $this->messages->text('style', 'italics'));
        $list = base64_decode($this->session->getVar('cite_creatorListSubsequent'));
        $listMore = stripslashes(base64_decode($this->session->getVar('cite_creatorListSubsequentMore')));
        $listLimit = stripslashes(base64_decode($this->session->getVar('cite_creatorListSubsequentLimit')));
        $listAbbreviation = stripslashes(base64_decode(
            $this->session->getVar('cite_creatorListSubsequentAbbreviation')
        ));
        $italic = base64_decode($this->session->getVar('cite_creatorListSubsequentAbbreviationItalic')) ?
            true : false;
        $td .= $this->misc->br() . '&nbsp;' . $this->misc->br();
        $td .= $this->misc->b($this->messages->text('cite', 'creatorListSubsequent')) .
            $this->misc->p($this->form->selectedBoxValue(
                false,
                'cite_creatorListSubsequent',
                $example,
                $list,
                2
            ) . $this->misc->br() .
            $this->messages->text('style', 'creatorListIf') . ' ' .
            $this->form->textInput(false, 'cite_creatorListSubsequentMore', $listMore, 3) .
            $this->messages->text('style', 'creatorListOrMore') . ' ' .
            $this->form->textInput(false, 'cite_creatorListSubsequentLimit', $listLimit, 3) . $this->misc->br() .
            $this->messages->text('style', 'creatorListAbbreviation') . ' ' .
            $this->form->textInput(false, 'cite_creatorListSubsequentAbbreviation', $listAbbreviation, 15) . ' ' .
            $this->form->checkbox(false, 'cite_creatorListSubsequentAbbreviationItalic', $italic) . ' ' .
            $this->messages->text('style', 'italics'));
        $pString .= $this->table->td($td, false, false, 'top');
        $pString .= $this->table->trEnd();
        $pString .= $this->table->tableEnd();
        $pString .= $this->table->tdEnd() . $this->table->trEnd() . $this->table->trStart() . $this->table->tdStart();
        $pString .= $this->misc->br() . '&nbsp;' . $this->misc->br();
        // Miscellaneous citation formatting
        $pString .= $this->table->tableStart('styleTable', 1, false, 5);
        $pString .= $this->table->trStart();

        $firstChars = stripslashes(base64_decode($this->session->getVar('cite_firstChars')));
        $template = stripslashes(base64_decode($this->session->getVar('cite_template')));
        $lastChars = stripslashes(base64_decode($this->session->getVar('cite_lastChars')));
        $td = $this->messages->text('cite', 'enclosingCharacters') . $this->misc->br() .
            $this->form->textInput(false, 'cite_firstChars', $firstChars, 3, 255) . ' ... ' .
            $this->form->textInput(false, 'cite_lastChars', $lastChars, 3, 255);
        $td .= $this->misc->br() . '&nbsp;' . $this->misc->br();

        $availableFields = implode(', ', $this->map->getCitation());
        $td .= $this->messages->text('cite', 'template') . ' ' .
            $this->form->textInput(false, 'cite_template', $template, 40, 255) .
            ' ' . $this->misc->span('*', 'required') .
            $this->misc->p($this->misc->i($this->messages->text('style', 'availableFields')) .
            $this->misc->br() . $availableFields, 'small');

        $replaceYear = stripslashes(base64_decode($this->session->getVar('cite_replaceYear')));
        $td .= $this->misc->p($this->form->textInput(
            $this->messages->text('cite', 'replaceYear'),
            'cite_replaceYear',
            $replaceYear,
            10,
            255
        ));

        $td .= $this->messages->text('cite', 'followCreatorTemplate');
        $template = stripslashes(base64_decode($this->session->getVar('cite_followCreatorTemplate')));
        $td .= $this->misc->p($this->messages->text('cite', 'template') . ' ' .
            $this->form->textInput(false, 'cite_followCreatorTemplate', $template, 40, 255)) .
            $this->misc->p($this->misc->i($this->messages->text('style', 'availableFields')) .
            $this->misc->br() . $availableFields, 'small');

        $pageSplit = base64_decode($this->session->getVar('cite_followCreatorPageSplit')) ?
            true : false;
        $td .= $this->misc->P($this->messages->text('cite', 'followCreatorPageSplit') . '&nbsp;&nbsp;' .
            $this->form->checkbox(false, 'cite_followCreatorPageSplit', $pageSplit));

        $consecutiveSep = stripslashes(base64_decode($this->session->getVar('cite_consecutiveCitationSep')));
        $td .= $this->misc->p($this->messages->text('cite', 'consecutiveCitationSep') . ' ' .
            $this->form->textInput(false, 'cite_consecutiveCitationSep', $consecutiveSep, 7));

        // Consecutive citations by same author(s)
        $consecutiveSep = stripslashes(base64_decode($this->session->getVar('cite_consecutiveCreatorSep')));
        $template = stripslashes(base64_decode($this->session->getVar('cite_consecutiveCreatorTemplate')));
        $availableFields = implode(', ', $this->map->getCitation());
        $td .= $this->misc->p($this->messages->text('cite', 'consecutiveCreator'));
        $td .= $this->messages->text('cite', 'template') . ' ' .
            $this->form->textInput(false, 'cite_consecutiveCreatorTemplate', $template, 40, 255) .
            $this->misc->p($this->misc->i($this->messages->text('style', 'availableFields')) .
            $this->misc->br() . $availableFields, 'small');
        $td .= $this->messages->text('cite', 'consecutiveCreatorSep') . ' ' .
            $this->form->textInput(false, 'cite_consecutiveCreatorSep', $consecutiveSep, 7);

        // Subsequent citations by same author(s)
        $template = stripslashes(base64_decode($this->session->getVar('cite_subsequentCreatorTemplate')));
        $td .= $this->misc->p($this->messages->text('cite', 'subsequentCreator'));
        $td .= $this->messages->text('cite', 'template') . ' ' .
            $this->form->textInput(false, 'cite_subsequentCreatorTemplate', $template, 40, 255) .
            $this->misc->p($this->misc->i($this->messages->text('style', 'availableFields')) .
            $this->misc->br() . $availableFields, 'small');

        $pString .= $this->table->td($td, false, false, 'top');

        $example = ['132-9', '132-39', '132-139'];
        $input = base64_decode($this->session->getVar('cite_pageFormat'));
        $td = $this->form->selectedBoxValue(
            $this->messages->text('style', 'pageFormat'),
            'cite_pageFormat',
            $example,
            $input,
            3
        );
        $td .= $this->misc->br() . '&nbsp;' . $this->misc->br();
        $example = ['1998', "'98", '98'];
        $year = base64_decode($this->session->getVar('cite_yearFormat'));
        $td .= $this->form->selectedBoxValue(
            $this->messages->text('cite', 'yearFormat'),
            'cite_yearFormat',
            $example,
            $year,
            3
        );
        $td .= $this->misc->br() . '&nbsp;' . $this->misc->br();
        $example = [$this->messages->text('style', 'titleAsEntered'),
            'Wikindx bibliographic management system'];
        $titleCapitalization = base64_decode($this->session->getVar('cite_titleCapitalization'));
        $td .= $this->misc->p($this->messages->text('style', 'titleCapitalization') . $this->misc->br() .
            $this->form->selectedBoxValue(false, 'cite_titleCapitalization', $example, $titleCapitalization, 2));

        // Ambiguous citations
        $ambiguous = base64_decode($this->session->getVar('cite_ambiguous'));
        $example = [$this->messages->text('cite', 'ambiguousUnchanged'),
            $this->messages->text('cite', 'ambiguousYear'), $this->messages->text('cite', 'ambiguousTitle')];
        $template = stripslashes(base64_decode($this->session->getVar('cite_ambiguousTemplate')));
        $td .= $this->misc->p($this->form->selectedBoxValue(
            $this->misc->b($this->messages->text('cite', 'ambiguous')),
            'cite_ambiguous',
            $example,
            $ambiguous,
            3
        ));
        $availableFields = implode(', ', $this->map->getCitation());
        $td .= $this->messages->text('cite', 'template') . ' ' .
            $this->form->textInput(false, 'cite_ambiguousTemplate', $template, 40, 255) .
            $this->misc->p($this->misc->i($this->messages->text('style', 'availableFields')) .
            $this->misc->br() . $availableFields, 'small');

        $pString .= $this->table->td($td, false, false, 'top');
        $pString .= $this->table->trEnd();
        $pString .= $this->table->tableEnd();
        $pString .= $this->misc->br() . '&nbsp;' . $this->misc->br();
        $pString .= $this->table->tdEnd() . $this->table->trEnd() . $this->table->trStart() . $this->table->tdStart();
        // Endnote style citations
        $pString .= $this->misc->h($this->messages->text('cite', 'citationFormat') . ' (' .
            $this->messages->text('cite', 'citationFormatEndnote') . ')');
        $pString .= $this->table->tableStart('styleTable', 1, false, 5);
        $pString .= $this->table->trStart();
        $td = $this->misc->p($this->misc->b($this->messages->text('cite', 'endnoteFormat1')));
        $firstChars = stripslashes(base64_decode($this->session->getVar('cite_firstCharsEndnoteInText')));
        $lastChars = stripslashes(base64_decode($this->session->getVar('cite_lastCharsEndnoteInText')));
        $td .= $this->messages->text('cite', 'enclosingCharacters') . $this->misc->br() .
            $this->form->textInput(false, 'cite_firstCharsEndnoteInText', $firstChars, 3, 255) . ' ... ' .
            $this->form->textInput(false, 'cite_lastCharsEndnoteInText', $lastChars, 3, 255);
        $td .= $this->misc->br() . '&nbsp;' . $this->misc->br();

        $template = stripslashes(base64_decode($this->session->getVar('cite_templateEndnoteInText')));
        $availableFields = implode(', ', $this->map->getCitationEndnoteInText());
        $td .= $this->messages->text('cite', 'template') . ' ' .
            $this->form->textInput(false, 'cite_templateEndnoteInText', $template, 40, 255) .
            ' ' . $this->misc->span('*', 'required') .
            $this->misc->p($this->misc->i($this->messages->text('style', 'availableFields')) .
            $this->misc->br() . $availableFields, 'small');

        $citeFormat = [$this->messages->text('cite', 'normal'),
            $this->messages->text('cite', 'superscript'), $this->messages->text('cite', 'subscript')];
        $input = base64_decode($this->session->getVar('cite_formatEndnoteInText'));
        $td .= $this->misc->p($this->form->selectedBoxValue(false, 'cite_formatEndnoteInText', $citeFormat, $input, 3));

        $consecutiveSep = stripslashes(base64_decode(
            $this->session->getVar('cite_consecutiveCitationEndnoteInTextSep')
        ));
        $td .= $this->misc->p($this->messages->text('cite', 'consecutiveCitationSep') . ' ' .
            $this->form->textInput(false, 'cite_consecutiveCitationEndnoteInTextSep', $consecutiveSep, 7));

        $endnoteStyleArray = [$this->messages->text('cite', 'endnoteStyle1'),
            $this->messages->text('cite', 'endnoteStyle2'), $this->messages->text('cite', 'endnoteStyle3')];
        $endnoteStyle = base64_decode($this->session->getVar('cite_endnoteStyle'));
        $td .= $this->misc->p($this->form->selectedBoxValue(
            $this->messages->text('cite', 'endnoteStyle'),
            'cite_endnoteStyle',
            $endnoteStyleArray,
            $endnoteStyle,
            3
        ));

        $pString .= $this->table->td($td);

        $td = $this->misc->p($this->misc->b($this->messages->text('cite', 'endnoteFormat2')));
        $td .= $this->misc->p($this->messages->text('cite', 'endnoteFieldFormat'), 'small');
        $template = stripslashes(base64_decode($this->session->getVar('cite_templateEndnote')));
        $availableFields = implode(', ', $this->map->getCitationEndnote());
        $td .= $this->messages->text('cite', 'template') . ' ' .
            $this->form->textInput(false, 'cite_templateEndnote', $template, 40, 255) . ' ' .
            $this->misc->span('*', 'required') .
            $this->misc->p($this->misc->i($this->messages->text('style', 'availableFields')) .
            $this->misc->br() . $availableFields, 'small');

        $availableFields = implode(', ', $this->map->getCitationEndnote());
        $ibid = stripslashes(base64_decode($this->session->getVar('cite_ibid')));
        $td .= $this->form->textInput($this->messages->text('cite', 'ibid'), 'cite_ibid', $ibid, 40, 255);
        $td .= $this->misc->br() . '&nbsp;' . $this->misc->br();
        $idem = stripslashes(base64_decode($this->session->getVar('cite_idem')));
        $td .= $this->form->textInput($this->messages->text('cite', 'idem'), 'cite_idem', $idem, 40, 255);
        $td .= $this->misc->br() . '&nbsp;' . $this->misc->br();
        $opCit = stripslashes(base64_decode($this->session->getVar('cite_opCit')));
        $td .= $this->form->textInput($this->messages->text('cite', 'opCit'), 'cite_opCit', $opCit, 40, 255) .
            $this->misc->p($this->misc->i($this->messages->text('style', 'availableFields')) .
            $this->misc->br() . $availableFields, 'small');

        $firstChars = stripslashes(base64_decode($this->session->getVar('cite_firstCharsEndnoteID')));
        $lastChars = stripslashes(base64_decode($this->session->getVar('cite_lastCharsEndnoteID')));
        $td .= $this->misc->p($this->messages->text('cite', 'endnoteIDEnclose') . $this->misc->br() .
            $this->form->textInput(false, 'cite_firstCharsEndnoteID', $firstChars, 3, 255) . ' ... ' .
            $this->form->textInput(false, 'cite_lastCharsEndnoteID', $lastChars, 3, 255));
        $pString .= $this->table->td($td);
        $pString .= $this->table->trEnd();
        $pString .= $this->table->tableEnd();
        $pString .= $this->misc->br() . '&nbsp;' . $this->misc->br();

        // Creator formatting for footnotes
        $pString .= $this->misc->h($this->messages->text('cite', 'citationFormatFootnote'));
        $pString .= $this->creatorFormatting('footnote', true);

        // bibliography order
        $pString .= $this->table->tdEnd() . $this->table->trEnd() . $this->table->trStart() . $this->table->tdStart();
        $pString .= $this->misc->h($this->messages->text('cite', 'orderBib1'));
        $pString .= $this->table->tableStart('styleTable', 1, false, 5);
        $pString .= $this->table->trStart();
        $heading = $this->misc->p($this->messages->text('cite', 'orderBib2'));
        $sameIdOrderBib = base64_decode($this->session->getVar('cite_sameIdOrderBib')) ? true : false;
        $heading .= $this->misc->P($this->messages->text('cite', 'orderBib3') . '&nbsp;&nbsp;' .
            $this->form->checkbox(false, 'cite_sameIdOrderBib', $sameIdOrderBib));
        $order1 = base64_decode($this->session->getVar('cite_order1'));
        $order2 = base64_decode($this->session->getVar('cite_order2'));
        $order3 = base64_decode($this->session->getVar('cite_order3'));
        $radio = !base64_decode($this->session->getVar('cite_order1desc')) ?
            $this->messages->text('powerSearch', 'ascending') . '&nbsp;&nbsp;' .
            $this->form->radioButton(false, 'cite_order1desc', 0, true) . $this->misc->br() .
            $this->messages->text('powerSearch', 'descending') . '&nbsp;&nbsp;' .
            $this->form->radioButton(false, 'cite_order1desc', 1) :
            $this->messages->text('powerSearch', 'ascending') . '&nbsp;&nbsp;' .
            $this->form->radioButton(false, 'cite_order1desc', 0) . $this->misc->br() .
            $this->messages->text('powerSearch', 'descending') . '&nbsp;&nbsp;' .
            $this->form->radioButton(false, 'cite_order1desc', 1, true);
        $orderArray = [$this->messages->text('list', 'creator'),
            $this->messages->text('list', 'year'), $this->messages->text('list', 'title')];
        $pString .= $this->table->td($heading . $this->form->selectedBoxValue(
            $this->messages->text('powerSearch', 'order1'),
            'cite_order1',
            $orderArray,
            $order1,
            3
        ) . $this->misc->p($radio));
        $radio = !base64_decode($this->session->getVar('cite_order2desc')) ?
            $this->messages->text('powerSearch', 'ascending') . '&nbsp;&nbsp;' .
            $this->form->radioButton(false, 'cite_order2desc', 0, true) . $this->misc->br() .
            $this->messages->text('powerSearch', 'descending') . '&nbsp;&nbsp;' .
            $this->form->radioButton(false, 'cite_order2desc', 1) :
            $this->messages->text('powerSearch', 'ascending') . '&nbsp;&nbsp;' .
            $this->form->radioButton(false, 'cite_order2desc', 0) . $this->misc->br() .
            $this->messages->text('powerSearch', 'descending') . '&nbsp;&nbsp;' .
            $this->form->radioButton(false, 'cite_order2desc', 1, true);
        $pString .= $this->table->td($this->form->selectedBoxValue(
            $this->messages->text('powerSearch', 'order2'),
            'cite_order2',
            $orderArray,
            $order2,
            3
        ) . $this->misc->p($radio), false, false, 'bottom');
        $radio = !base64_decode($this->session->getVar('cite_order3desc')) ?
            $this->messages->text('powerSearch', 'ascending') . '&nbsp;&nbsp;' .
            $this->form->radioButton(false, 'cite_order3desc', 0, true) . $this->misc->br() .
            $this->messages->text('powerSearch', 'descending') . '&nbsp;&nbsp;' .
            $this->form->radioButton(false, 'cite_order3desc', 1) :
            $this->messages->text('powerSearch', 'ascending') . '&nbsp;&nbsp;' .
            $this->form->radioButton(false, 'cite_order3desc', 0) . $this->misc->br() .
            $this->messages->text('powerSearch', 'descending') . '&nbsp;&nbsp;' .
            $this->form->radioButton(false, 'cite_order3desc', 1, true);
        $pString .= $this->table->td($this->form->selectedBoxValue(
            $this->messages->text('powerSearch', 'order3'),
            'cite_order3',
            $orderArray,
            $order3,
            3
        ) . $this->misc->p($radio), false, false, 'bottom');
        $pString .= $this->table->trEnd();
        $pString .= $this->table->tableEnd();
        $pString .= $this->misc->br() . '&nbsp;' . $this->misc->br();
        $pString .= $this->table->tdEnd() . $this->table->trEnd() . $this->table->trStart() . $this->table->tdStart();
        return $pString;
    }

    /**
     * display the style form for both adding and editing
     */
    public function displayStyleForm(string $type)
    {
        include_once(__DIR__ . '/TABLE.php');
        include_once(__DIR__ . '/../STYLEMAP.php');
        $this->map = new STYLEMAP();
        $types = array_keys($this->map->getTypes());
        if ($type == 'add') {
            $pString = $this->form->formHeader('adminStyleAdd');
        } elseif ($type == 'edit') {
            $pString = $this->form->formHeader('adminStyleEdit');
        } else { // copy
            $pString = $this->form->formHeader('adminStyleAdd');
        }
        $pString .= $this->table->tableStart();
        $pString .= $this->table->trStart();
        $input = stripslashes($this->session->getVar('style_shortName'));
        if ($type == 'add') {
            $pString .= $this->table->td($this->form->textInput(
                $this->messages->text('style', 'shortName'),
                'styleShortName',
                $input,
                20,
                255
            ) . ' ' . $this->misc->span('*', 'required') .
                $this->misc->br() . $this->messages->text('hint', 'styleShortName'));
        } elseif ($type == 'edit') {
            $pString .= $this->form->hidden('editStyleFile', $this->vars['editStyleFile']) .
                $this->form->hidden('styleShortName', $input) .
                $this->table->td(
                    $this->misc->b($this->vars['editStyleFile'] . ':&nbsp;&nbsp;'),
                    false,
                    false,
                    'top'
                );
        } else { // copy
            $pString .= $this->table->td($this->form->textInput(
                $this->messages->text('style', 'shortName'),
                'styleShortName',
                $input,
                20,
                255
            ) . ' ' . $this->misc->span('*', 'required') .
                $this->misc->br() . $this->messages->text('hint', 'styleShortName'));
        }
        $input = stripslashes(base64_decode($this->session->getVar('style_longName')));
        $pString .= $this->table->td($this->form->textInput(
            $this->messages->text('style', 'longName'),
            'styleLongName',
            $input,
            50,
            255
        ) . ' ' . $this->misc->span('*', 'required'));
        $input = base64_decode($this->session->getVar('cite_citationStyle'));
        $example = [$this->messages->text('cite', 'citationFormatInText'),
            $this->messages->text('cite', 'citationFormatEndnote')];
        $pString .= $this->table->td($this->form->selectedBoxValue(
            $this->messages->text('cite', 'citationFormat'),
            'cite_citationStyle',
            $example,
            $input,
            2
        ) . ' ' . $this->misc->span('*', 'required'));

        $pString .= $this->table->trEnd();
        $pString .= $this->table->tableEnd();
        $pString .= $this->table->tdEnd() . $this->table->trEnd() . $this->table->trStart() . $this->table->tdStart();
        $pString .= $this->misc->p($this->misc->hr());
        $pString .= $this->displayCiteForm('copy');
        $pString .= $this->misc->p($this->misc->hr() . $this->misc->hr());
        $pString .= $this->misc->h($this->messages->text('style', 'bibFormat'));

        // Creator formatting for bibliography
        $pString .= $this->creatorFormatting('style');
        // Editor replacements
        $pString .= $this->table->tableStart('styleTable', 1, false, 5);
        $pString .= $this->table->trStart();
        $switch = base64_decode($this->session->getVar('style_editorSwitch'));
        $editorSwitchIfYes = stripslashes(base64_decode($this->session->getVar('style_editorSwitchIfYes')));
        $example = [$this->messages->text('style', 'no'), $this->messages->text('style', 'yes')];
        $pString .= $this->table->td($this->misc->b($this->messages->text('style', 'editorSwitchHead')) . $this->misc->br() .
            $this->form->selectedBoxValue(
                $this->messages->text('style', 'editorSwitch'),
                'style_editorSwitch',
                $example,
                $switch,
                2
            ));
        $pString .= $this->table->td(
            $this->form->textInput(
                $this->messages->text('style', 'editorSwitchIfYes'),
                'style_editorSwitchIfYes',
                $editorSwitchIfYes,
                30,
                255
            ),
            false,
            false,
            'bottom'
        );
        $pString .= $this->table->trEnd();
        $pString .= $this->table->tableEnd();
        $pString .= $this->table->tdEnd() . $this->table->trEnd() . $this->table->trStart() . $this->table->tdStart();
        $pString .= $this->misc->br() . '&nbsp;' . $this->misc->br();
        // Title capitalization, edition, day and month, runningTime and page formats
        $pString .= $this->table->tableStart('styleTable', 1, false, 5);
        $pString .= $this->table->trStart();
        $example = [$this->messages->text('style', 'titleAsEntered'),
            'Wikindx bibliographic management system'];
        $input = base64_decode($this->session->getVar('style_titleCapitalization'));
        $td = $this->misc->p($this->misc->b($this->messages->text('style', 'titleCapitalization')) . $this->misc->br() .
            $this->form->selectedBoxValue(false, 'style_titleCapitalization', $example, $input, 2));
        $pString .= $this->table->td($td);
        $example = ['3', '3.', '3rd'];
        $input = base64_decode($this->session->getVar('style_editionFormat'));
        $pString .= $this->table->td($this->misc->b($this->messages->text('style', 'editionFormat')) . $this->misc->br() .
            $this->form->selectedBoxValue(false, 'style_editionFormat', $example, $input, 3));
        $example = ['132-9', '132-39', '132-139'];
        $input = base64_decode($this->session->getVar('style_pageFormat'));
        $pString .= $this->table->td($this->misc->b($this->messages->text('style', 'pageFormat')) . $this->misc->br() .
            $this->form->selectedBoxValue(false, 'style_pageFormat', $example, $input, 3));
        $pString .= $this->table->trEnd();
        $pString .= $this->table->tableEnd();
        $pString .= $this->table->tdEnd() . $this->table->trEnd() . $this->table->trStart() . $this->table->tdStart();
        $pString .= $this->misc->br() . '&nbsp;' . $this->misc->br();
        $pString .= $this->table->tableStart('styleTable', 1, false, 5);
        $pString .= $this->table->trStart();
        $example = ['10', '10.', '10th'];
        $input = base64_decode($this->session->getVar('style_dayFormat'));
        $leadingZero = base64_decode($this->session->getVar('style_dayLeadingZero')) ?
            true : false;
        $pString .= $this->table->td($this->misc->b($this->messages->text('style', 'dayFormat')) . $this->misc->br() .
            $this->form->selectedBoxValue(false, 'style_dayFormat', $example, $input, 3) .
            $this->misc->P($this->form->checkbox(
                $this->messages->text('style', 'dayLeadingZero'),
                'style_dayLeadingZero',
                $leadingZero
            )));
        $example = ['Feb', 'February', $this->messages->text('style', 'userMonthSelect')];
        $input = base64_decode($this->session->getVar('style_monthFormat'));
        $pString .= $this->table->td($this->misc->b($this->messages->text('style', 'monthFormat')) . $this->misc->br() .
            $this->form->selectedBoxValue(false, 'style_monthFormat', $example, $input, 3));
        $example = ['Day Month', 'Month Day'];
        $input = base64_decode($this->session->getVar('style_dateFormat'));
        $pString .= $this->table->td($this->misc->b($this->messages->text('style', 'dateFormat')) . $this->misc->br() .
            $this->form->selectedBoxValue(false, 'style_dateFormat', $example, $input, 2));

        $input = base64_decode($this->session->getVar('style_dateMonthNoDay'));
        $inputString = stripslashes(base64_decode($this->session->getVar('style_dateMonthNoDayString')));
        $example = [$this->messages->text('style', 'dateMonthNoDay1'),
            $this->messages->text('style', 'dateMonthNoDay2')];
        $pString .= $this->table->td($this->form->selectedBoxValue(
            $this->messages->text('style', 'dateMonthNoDay'),
            'style_dateMonthNoDay',
            $example,
            $input,
            2
        ) . $this->misc->br() .
            $this->form->textInput(false, 'style_dateMonthNoDayString', $inputString, 30, 255) . $this->misc->br() .
            $this->misc->span($this->messages->text('style', 'dateMonthNoDayHint'), 'hint'));

        $pString .= $this->table->trEnd();
        $pString .= $this->table->trStart();
        $monthString = '';
        for ($i = 1; $i <= 12; $i++) {
            $input = stripslashes(base64_decode($this->session->getVar("style_userMonth_$i")));
            if ($i == 7) {
                $monthString .= $this->misc->br() . "$i:&nbsp;&nbsp;" .
                $this->form->textInput(false, "style_userMonth_$i", $input, 15, 255);
            } else {
                $monthString .= "$i:&nbsp;&nbsp;" .
                $this->form->textInput(false, "style_userMonth_$i", $input, 15, 255);
            }
        }
        $pString .= $this->table->td($this->messages->text('style', 'userMonths') . $this->misc->br() .
            $monthString, false, false, false, 5);
        $pString .= $this->table->trEnd();
        $pString .= $this->table->tableEnd();
        $pString .= $this->table->tdEnd() . $this->table->trEnd() . $this->table->trStart() . $this->table->tdStart();
        $pString .= $this->misc->br() . '&nbsp;' . $this->misc->br();

        // Date range formatting
        $pString .= $this->misc->b($this->messages->text('style', 'dateRange')) . $this->misc->br();
        $pString .= $this->table->tableStart('styleTable', 1, false, 5);
        $pString .= $this->table->trStart();
        $input = stripslashes(base64_decode($this->session->getVar('style_dateRangeDelimit1')));
        $pString .= $this->table->td($this->form->textInput(
            $this->messages->text('style', 'dateRangeDelimit1'),
            'style_dateRangeDelimit1',
            $input,
            6,
            255
        ));
        $input = base64_decode($this->session->getVar('style_dateRangeDelimit2'));
        $pString .= $this->table->td($this->form->textInput(
            $this->messages->text('style', 'dateRangeDelimit2'),
            'style_dateRangeDelimit2',
            $input,
            6,
            255
        ));
        $pString .= $this->table->trEnd();
        $pString .= $this->table->trStart();
        $input = base64_decode($this->session->getVar('style_dateRangeSameMonth'));
        $example = [$this->messages->text('style', 'dateRangeSameMonth1'),
            $this->messages->text('style', 'dateRangeSameMonth2')];
        $pString .= $this->table->td($this->form->selectedBoxValue(
            $this->messages->text('style', 'dateRangeSameMonth'),
            'style_dateRangeSameMonth',
            $example,
            $input,
            2
        ), false, false, false, 2);
        $pString .= $this->table->trEnd();
        $pString .= $this->table->tableEnd();
        $pString .= $this->table->tdEnd() . $this->table->trEnd() . $this->table->trStart() . $this->table->tdStart();
        $pString .= $this->misc->br() . '&nbsp;' . $this->misc->br();

        $pString .= $this->table->tableStart('styleTable', 1, false, 5);
        $pString .= $this->table->trStart();
        $example = ["3'45\"", '3:45', '3,45', '3 hours, 45 minutes', '3 hours and 45 minutes'];
        $input = base64_decode($this->session->getVar('style_runningTimeFormat'));
        $pString .= $this->table->td($this->misc->b($this->messages->text('style', 'runningTimeFormat')) . $this->misc->br() .
            $this->form->selectedBoxValue(false, 'style_runningTimeFormat', $example, $input, 5));
        $pString .= $this->table->trEnd();
        $pString .= $this->table->tableEnd();
        $pString .= $this->table->tdEnd() . $this->table->trEnd() . $this->table->trStart() . $this->table->tdStart();
        $pString .= $this->misc->br() . $this->misc->hr() . $this->misc->br();

        // print some basic advice
        $pString .= $this->misc->p(
            $this->messages->text('style', 'templateHelp1') .
            $this->misc->br() . $this->messages->text('style', 'templateHelp2') .
            $this->misc->br() . $this->messages->text('style', 'templateHelp3') .
            $this->misc->br() . $this->messages->text('style', 'templateHelp4') .
            $this->misc->br() . $this->messages->text('style', 'templateHelp5'),
            'small'
        );

        $generic = ['genericBook' => $this->messages->text('resourceType', 'genericBook'),
            'genericArticle' => $this->messages->text('resourceType', 'genericArticle'),
            'genericMisc' => $this->messages->text('resourceType', 'genericMisc')];
        $availableFieldsCitation = implode(', ', $this->map->getCitation());
        // Resource types
        foreach ($types as $key) {
            if (($key == 'genericBook') || ($key == 'genericArticle') || ($key == 'genericMisc')) {
                $required = $this->misc->span('*', 'required');
                $fallback = false;
                $citationString = false;
            } else {
                $required = false;
                $formElementName = 'style_' . $key . '_generic';
                $input = $this->session->issetVar($formElementName) ?
                    base64_decode($this->session->getVar($formElementName)) : 'genericMisc';
                $fallback = $this->form->selectedBoxValue(
                    $this->messages->text('style', 'fallback'),
                    $formElementName,
                    $generic,
                    $input,
                    3
                );
                // Replacement citation template for in-text citation for this type
                $citationStringName = 'cite_' . $key . 'Template';
                $citationNotInBibliography = 'cite_' . $key . '_notInBibliography';
                $input = stripslashes(base64_decode($this->session->getVar($citationStringName)));
                $notAdd = base64_decode($this->session->getVar($citationNotInBibliography)) ? true : false;
                $checkBox = '&nbsp;&nbsp;' . $this->messages->text('cite', 'notInBibliography') .
                '&nbsp;' . $this->form->checkbox(false, $citationNotInBibliography, $notAdd);
                $citationString = $this->misc->p($this->form->textInput(
                    $this->messages->text('cite', 'typeReplace'),
                    $citationStringName,
                    $input,
                    60,
                    255
                ) . $checkBox . $this->misc->br() .
                    $this->misc->i($this->messages->text('style', 'availableFields')) .
                    $this->misc->br() . $availableFieldsCitation, 'small');
            }
            // Footnote template
            $footnoteTemplateName = 'footnote_' . $key . 'Template';
            $input = stripslashes(base64_decode($this->session->getVar($footnoteTemplateName)));
            $footnoteTemplate = $this->misc->p($this->form->textareaInput(
                $this->messages->text('cite', 'footnoteTemplate'),
                $footnoteTemplateName,
                $input,
                80,
                3
            ));
            $rewriteCreatorString = $this->rewriteCreators($key, $this->map->$key);
            $pString .= $this->misc->br() . $this->misc->hr() . $this->misc->br();
            $pString .= $this->table->tableStart();
            $pString .= $this->table->trStart();
            $keyName = 'style_' . $key;
            $preview = $this->misc->a(
                'link linkCiteHidden',
                'preview',
                "javascript:openPopUpStylePreview('index.php?action=previewStyle',
				'100', '750', '$keyName')"
            );
            $input = stripslashes(base64_decode($this->session->getVar($keyName)));
            $heading = $this->misc->b($this->messages->text('resourceType', $key)) . $this->misc->br() .
                $this->messages->text('style', 'bibTemplate') . $required;
            $pString .= $this->table->td($this->form->textareaInput(
                $heading,
                $keyName,
                $input,
                80,
                3
            ) . $preview . $footnoteTemplate .
                $rewriteCreatorString . $citationString);
            // List available fields for this type
            $availableFields = implode(', ', array_values($this->map->$key));
            // If 'pages' not in field list, add for field footnotes
            if (array_search('pages', $this->map->$key) === false) {
                $availableFields .= ', ' . $this->messages->text('style', 'footnotePageField');
            }
            $pString .= $this->table->td($this->misc->p($this->misc->i($this->messages->text('style', 'availableFields')) .
                $this->misc->br() . $availableFields . $this->misc->br() .
                $this->messages->text('hint', 'caseSensitive'), 'small') . $this->misc->p($fallback));
            $pString .= $this->table->trEnd();
            $pString .= $this->table->tableEnd();
            $pString .= $this->table->tdEnd() . $this->table->trEnd() . $this->table->trStart() . $this->table->tdStart();
        }
        if (($type == 'add') || ($type == 'copy')) {
            $pString .= $this->misc->p($this->form->formSubmit('Add'));
        } else {
            $pString .= $this->misc->p($this->form->formSubmit('Edit'));
        }
        $pString .= $this->form->formEnd();
        return $pString;
    }

    /**
     * display creator formatting options for bibliographies and footnotes
     */
    public function creatorFormatting($prefix, $footnote = false)
    {
        // Display general options for creator limits, formats etc.
        // 1st., creator style
        $pString = $this->table->tableStart($prefix . 'Table', 1, false, 5);
        $pString .= $this->table->trStart();
        $exampleName = ['Joe Bloggs', 'Bloggs, Joe', 'Bloggs Joe',
            $this->messages->text('cite', 'lastName')];
        $exampleInitials = ['T. U. ', 'T.U.', 'T U ', 'TU'];
        $example = [$this->messages->text('style', 'creatorFirstNameFull'),
            $this->messages->text('style', 'creatorFirstNameInitials')];
        $firstStyle = base64_decode($this->session->getVar($prefix . '_primaryCreatorFirstStyle'));
        $otherStyle = base64_decode($this->session->getVar($prefix . '_primaryCreatorOtherStyle'));
        $initials = base64_decode($this->session->getVar($prefix . '_primaryCreatorInitials'));
        $firstName = base64_decode($this->session->getVar($prefix . '_primaryCreatorFirstName'));
        $td = $this->misc->b($this->messages->text('style', 'primaryCreatorStyle')) . $this->misc->br() .
            $this->form->selectedBoxValue(
                $this->messages->text('style', 'creatorFirstStyle'),
                $prefix . '_primaryCreatorFirstStyle',
                $exampleName,
                $firstStyle,
                4
            );
        $td .= $this->misc->br() . '&nbsp;' . $this->misc->br();
        $td .= $this->form->selectedBoxValue(
            $this->messages->text('style', 'creatorOthers'),
            $prefix . '_primaryCreatorOtherStyle',
            $exampleName,
            $otherStyle,
            4
        );
        $td .= $this->misc->br() . '&nbsp;' . $this->misc->br();
        $td .= $this->form->selectedBoxValue(
            $this->messages->text('style', 'creatorInitials'),
            $prefix . '_primaryCreatorInitials',
            $exampleInitials,
            $initials,
            4
        );
        $td .= $this->misc->br() . '&nbsp;' . $this->misc->br();
        $td .= $this->form->selectedBoxValue(
            $this->messages->text('style', 'creatorFirstName'),
            $prefix . '_primaryCreatorFirstName',
            $example,
            $firstName,
            2
        );
        $uppercase = base64_decode($this->session->getVar($prefix . '_primaryCreatorUppercase')) ?
            true : false;
        $td .= $this->misc->P($this->form->checkbox(
            $this->messages->text('style', 'uppercaseCreator'),
            $prefix . '_primaryCreatorUppercase',
            $uppercase
        ));
        $repeat = base64_decode($this->session->getVar($prefix . '_primaryCreatorRepeat'));
        $exampleRepeat = [$this->messages->text('style', 'repeatCreators1'),
            $this->messages->text('style', 'repeatCreators2'),
            $this->messages->text('style', 'repeatCreators3')];
        $td .= $this->form->selectedBoxValue(
            $this->messages->text('style', 'repeatCreators'),
            $prefix . '_primaryCreatorRepeat',
            $exampleRepeat,
            $repeat,
            3
        ) . $this->misc->br();
        $repeatString = stripslashes(base64_decode(
            $this->session->getVar($prefix . '_primaryCreatorRepeatString')
        ));
        $td .= $this->form->textInput(false, $prefix . '_primaryCreatorRepeatString', $repeatString, 15, 255);
        $pString .= $this->table->td($td);
        //		if(!$footnote)
        //		{
        // Other creators (editors, translators etc.)
        $firstStyle = base64_decode($this->session->getVar($prefix . '_otherCreatorFirstStyle'));
        $otherStyle = base64_decode($this->session->getVar($prefix . '_otherCreatorOtherStyle'));
        $initials = base64_decode($this->session->getVar($prefix . '_otherCreatorInitials'));
        $firstName = base64_decode($this->session->getVar($prefix . '_otherCreatorFirstName'));
        $td = $this->misc->b($this->messages->text('style', 'otherCreatorStyle')) . $this->misc->br() .
            $this->form->selectedBoxValue(
                $this->messages->text('style', 'creatorFirstStyle'),
                $prefix . '_otherCreatorFirstStyle',
                $exampleName,
                $firstStyle,
                4
            );
        $td .= $this->misc->br() . '&nbsp;' . $this->misc->br();
        $td .= $this->form->selectedBoxValue(
            $this->messages->text('style', 'creatorOthers'),
            $prefix . '_otherCreatorOtherStyle',
            $exampleName,
            $otherStyle,
            4
        );
        $td .= $this->misc->br() . '&nbsp;' . $this->misc->br();
        $td .= $this->form->selectedBoxValue(
            $this->messages->text('style', 'creatorInitials'),
            $prefix . '_otherCreatorInitials',
            $exampleInitials,
            $initials,
            4
        );
        $td .= $this->misc->br() . '&nbsp;' . $this->misc->br();
        $td .= $this->form->selectedBoxValue(
            $this->messages->text('style', 'creatorFirstName'),
            $prefix . '_otherCreatorFirstName',
            $example,
            $firstName,
            2
        );
        $uppercase = base64_decode($this->session->getVar($prefix . '_otherCreatorUppercase')) ?
            true : false;
        $td .= $this->misc->P($this->form->checkbox(
            $this->messages->text('style', 'uppercaseCreator'),
            $prefix . '_otherCreatorUppercase',
            $uppercase
        ));
        $pString .= $this->table->td($td);
        //		}
        $pString .= $this->table->trEnd();
        $pString .= $this->table->tableEnd();
        $pString .= $this->misc->br() . '&nbsp;' . $this->misc->br();
        $pString .= $this->table->tdEnd() . $this->table->trEnd() . $this->table->trStart() . $this->table->tdStart();

        // 2nd., creator delimiters
        $pString .= $this->table->tableStart($prefix . 'Table', 1, false, 5);
        $pString .= $this->table->trStart();
        $twoCreatorsSep = stripslashes(base64_decode($this->session->getVar(
            $prefix . '_primaryTwoCreatorsSep'
        )));
        $betweenFirst = stripslashes(base64_decode($this->session->getVar(
            $prefix . '_primaryCreatorSepFirstBetween'
        )));
        $betweenNext = stripslashes(base64_decode($this->session->getVar(
            $prefix . '_primaryCreatorSepNextBetween'
        )));
        $last = stripslashes(base64_decode($this->session->getVar($prefix . '_primaryCreatorSepNextLast')));
        $pString .= $this->table->td(
            $this->misc->b($this->messages->text('style', 'primaryCreatorSep')) .
            $this->misc->p($this->messages->text('style', 'ifOnlyTwoCreators') . '&nbsp;' .
            $this->form->textInput(false, $prefix . '_primaryTwoCreatorsSep', $twoCreatorsSep, 7, 255)) .
            $this->messages->text('style', 'sepCreatorsFirst') . '&nbsp;' .
            $this->form->textInput(false, $prefix . '_primaryCreatorSepFirstBetween', $betweenFirst, 7, 255) .
            $this->misc->br() . $this->misc->p($this->messages->text('style', 'sepCreatorsNext') . $this->misc->br() .
            $this->messages->text('style', 'creatorSepBetween') . '&nbsp;' .
            $this->form->textInput(false, $prefix . '_primaryCreatorSepNextBetween', $betweenNext, 7, 255) .
            $this->messages->text('style', 'creatorSepLast') . '&nbsp;' .
            $this->form->textInput(false, $prefix . '_primaryCreatorSepNextLast', $last, 7, 255)),
            false,
            false,
            'bottom'
        );
        $twoCreatorsSep = stripslashes(base64_decode($this->session->getVar($prefix . '_otherTwoCreatorsSep')));
        $betweenFirst = stripslashes(base64_decode($this->session->getVar(
            $prefix . '_otherCreatorSepFirstBetween'
        )));
        $betweenNext = stripslashes(base64_decode($this->session->getVar(
            $prefix . '_otherCreatorSepNextBetween'
        )));
        $last = stripslashes(base64_decode($this->session->getVar($prefix . '_otherCreatorSepNextLast')));
        $pString .= $this->table->td(
            $this->misc->b($this->messages->text('style', 'otherCreatorSep')) .
            $this->misc->p($this->messages->text('style', 'ifOnlyTwoCreators') . '&nbsp;' .
            $this->form->textInput(false, $prefix . '_otherTwoCreatorsSep', $twoCreatorsSep, 7, 255)) .
            $this->messages->text('style', 'sepCreatorsFirst') . '&nbsp;' .
            $this->form->textInput(false, $prefix . '_otherCreatorSepFirstBetween', $betweenFirst, 7, 255) .
            $this->misc->p($this->messages->text('style', 'sepCreatorsNext') . $this->misc->br() .
            $this->messages->text('style', 'creatorSepBetween') . '&nbsp;' .
            $this->form->textInput(false, $prefix . '_otherCreatorSepNextBetween', $betweenNext, 7, 255) .
            $this->messages->text('style', 'creatorSepLast') . '&nbsp;' .
            $this->form->textInput(false, $prefix . '_otherCreatorSepNextLast', $last, 7, 255)),
            false,
            false,
            'bottom'
        );
        $pString .= $this->table->trEnd();
        $pString .= $this->table->tableEnd();
        $pString .= $this->table->tdEnd() . $this->table->trEnd() . $this->table->trStart() . $this->table->tdStart();
        $pString .= $this->misc->br() . '&nbsp;' . $this->misc->br();

        // 3rd., creator list limits
        $pString .= $this->table->tableStart($prefix . 'Table', 1, false, 5);
        $pString .= $this->table->trStart();
        $example = [$this->messages->text('style', 'creatorListFull'),
            $this->messages->text('style', 'creatorListLimit')];
        $list = base64_decode($this->session->getVar($prefix . '_primaryCreatorList'));
        $listMore = stripslashes(base64_decode($this->session->getVar($prefix . '_primaryCreatorListMore')));
        $listLimit = stripslashes(base64_decode($this->session->getVar($prefix . '_primaryCreatorListLimit')));
        $listAbbreviation = stripslashes(base64_decode($this->session->getVar(
            $prefix . '_primaryCreatorListAbbreviation'
        )));
        $italic = base64_decode($this->session->getVar($prefix . '_primaryCreatorListAbbreviationItalic')) ?
            true : false;
        $pString .= $this->table->td($this->misc->b($this->messages->text('style', 'primaryCreatorList')) . $this->misc->br() .
            $this->form->selectedBoxValue(
                false,
                $prefix . '_primaryCreatorList',
                $example,
                $list,
                2
            ) . $this->misc->br() .
            $this->messages->text('style', 'creatorListIf') . ' ' .
            $this->form->textInput(false, $prefix . '_primaryCreatorListMore', $listMore, 3) .
            $this->messages->text('style', 'creatorListOrMore') . ' ' .
            $this->form->textInput(false, $prefix . '_primaryCreatorListLimit', $listLimit, 3) . $this->misc->br() .
            $this->messages->text('style', 'creatorListAbbreviation') . ' ' .
            $this->form->textInput(false, $prefix . '_primaryCreatorListAbbreviation', $listAbbreviation, 15) . ' ' .
            $this->form->checkbox(false, $prefix . '_primaryCreatorListAbbreviationItalic', $italic) . ' ' .
            $this->messages->text('style', 'italics'));
        $list = base64_decode($this->session->getVar($prefix . '_otherCreatorList'));
        $listMore = stripslashes(base64_decode($this->session->getVar($prefix . '_otherCreatorListMore')));
        $listLimit = stripslashes(base64_decode($this->session->getVar($prefix . '_otherCreatorListLimit')));
        $listAbbreviation = stripslashes(base64_decode($this->session->getVar(
            $prefix . '_otherCreatorListAbbreviation'
        )));
        $italic = base64_decode($this->session->getVar($prefix . '_otherCreatorListAbbreviationItalic')) ?
            true : false;
        $pString .= $this->table->td($this->misc->b($this->messages->text('style', 'otherCreatorList')) . $this->misc->br() .
            $this->form->selectedBoxValue(
                false,
                $prefix . '_otherCreatorList',
                $example,
                $list,
                2
            ) . $this->misc->br() .
            $this->messages->text('style', 'creatorListIf') . ' ' .
            $this->form->textInput(false, $prefix . '_otherCreatorListMore', $listMore, 3) .
            $this->messages->text('style', 'creatorListOrMore') . ' ' .
            $this->form->textInput(false, $prefix . '_otherCreatorListLimit', $listLimit, 3) . $this->misc->br() .
            $this->messages->text('style', 'creatorListAbbreviation') . ' ' .
            $this->form->textInput(false, $prefix . '_otherCreatorListAbbreviation', $listAbbreviation, 15) . ' ' .
            $this->form->checkbox(false, $prefix . '_otherCreatorListAbbreviationItalic', $italic) . ' ' .
            $this->messages->text('style', 'italics'));
        $pString .= $this->table->trEnd();
        $pString .= $this->table->tableEnd();
        $pString .= $this->table->tdEnd() . $this->table->trEnd() . $this->table->trStart() . $this->table->tdStart();
        $pString .= $this->misc->br() . '&nbsp;' . $this->misc->br();
        return $pString;
    }

    /**
     * Re-write creator(s) portion of templates to handle styles such as DIN 1505.
     * @return bool|string
     */
    public function rewriteCreators(string $key, array $availableFields)
    {
        $heading = $this->misc->p($this->misc->b($this->messages->text('style', 'rewriteCreator1')), 'small');
        foreach ($this->creators as $creatorField) {
            if (!array_key_exists($creatorField, $availableFields)) {
                continue;
            }
            $fields[$creatorField] = $availableFields[$creatorField];
        }
        if (!isset($fields)) {
            return false;
        }
        $pString = false;
        foreach ($fields as $creatorField => $value) {
            $basicField = 'style_' . $key . '_' . $creatorField;
            $field = $this->table->td($this->misc->p($this->misc->i($value), 'small'), false, false, 'middle');
            $formString = $basicField . '_firstString';
            $string = stripslashes(base64_decode($this->session->getVar($formString)));
            $formCheckbox = $basicField . '_firstString_before';
            $checkbox = base64_decode($this->session->getVar($formCheckbox)) ? true : false;
            $firstCheckbox = $this->misc->br() . $this->messages->text('style', 'rewriteCreator4') .
                '&nbsp;' . $this->form->checkbox(false, $formCheckbox, $checkbox);
            $first = $this->table->td($this->misc->p($this->form->textInput(
                $this->messages->text('style', 'rewriteCreator2'),
                $formString,
                $string,
                20,
                255
            ) . $firstCheckbox, 'small'), false, false, 'bottom');
            $formString = $basicField . '_remainderString';
            $string = stripslashes(base64_decode($this->session->getVar($formString)));
            $formCheckbox = $basicField . '_remainderString_before';
            $checkbox = base64_decode($this->session->getVar($formCheckbox)) ? true : false;
            $remainderCheckbox = $this->misc->br() . $this->messages->text('style', 'rewriteCreator4') .
                '&nbsp;' . $this->form->checkbox(false, $formCheckbox, $checkbox);
            $formCheckbox = $basicField . '_remainderString_each';
            $checkbox = base64_decode($this->session->getVar($formCheckbox)) ? true : false;
            $remainderCheckbox .= ',&nbsp;&nbsp;&nbsp;' . $this->messages->text('style', 'rewriteCreator5') .
                '&nbsp;' . $this->form->checkbox(false, $formCheckbox, $checkbox);
            $remainder = $this->table->td($this->misc->p($this->form->textInput(
                $this->messages->text('style', 'rewriteCreator3'),
                $formString,
                $string,
                20,
                255
            ) . $remainderCheckbox, 'small'), false, false, 'bottom');
            $pString .= $this->table->trStart() . $field . $first . $remainder . $this->table->trEnd();
        }
        return $heading . $this->table->tableStart('styleTable', 1, false, 5) . $pString . $this->table->tableEnd();
    }

    /**
     * parse input into array
     */
    public function parseStringToArray(string $type, string $subjectToParse, ?STYLEMAP $map = null): array
    {
        if (!$subjectToParse) {
            return [];
        }
        if (!$map) {
            return [];
        }
        $final = [];
        $this->map = $map;
        $search = implode('|', $this->map->$type);
        // footnotes can have pages field
        if ($this->footnotePages && !array_key_exists('pages', $this->map->$type)) {
            $search .= '|' . 'pages';
        }
        $subjectArray = mb_split("\|", $subjectToParse);
        //$sizeSubject = count($subjectArray);
        // Loop each field string
        $index = 0;
        $subjectIndex = 0;
        foreach ($subjectArray as $subject) {
            ++$subjectIndex;
            $dependentPre = $dependentPost = $dependentPreAlternative =
                $dependentPostAlternative = $singular = $plural = false;
            // First grab fieldNames from the input string.
            preg_match("/(.*)(?<!`|[a-zA-Z])($search)(?!`|[a-zA-Z])(.*)/", $subject, $array);
            if (empty($array)) {
                if (!$index) {
                    $possiblePreliminaryText = $subject;
                    continue;
                }
                // 2022-12-23#SP $independent is not defined here, commented out
                /*
                if (isset($independent) && ($subjectIndex == $sizeSubject) &&
                    array_key_exists('independent_' . $index, $independent)) {
                    $ultimate = $subject;
                } else {
                    if (isset($independent) && (count($independent) % 2)) {
                        $independent['independent_' . ($index - 1)] = $subject;
                    } else {
                        $independent['independent_' . $index] = $subject;
                    }
                }
                */
                $independent['independent_' . $index] = $subject;
                continue;
            }
            // At this stage, [2] is the fieldName, [1] is what comes before and [3] is what comes after.
            $pre = $array[1];
            $fieldName = $array[2];
            $post = $array[3];
            // Anything in $pre enclosed in '%' characters is only to be printed if the resource has something in the
            // previous field -- replace with unique string for later preg_replace().
            if (preg_match('/%(.*)%(.*)%|%(.*)%/U', $pre, $dependent)) {
                // if sizeof == 4, we have simply %*% with the significant character in [3].
                // if sizeof == 3, we have %*%*% with dependent in [1] and alternative in [2].
                $pre = str_replace($dependent[0], '__DEPENDENT_ON_PREVIOUS_FIELD__', $pre);
                if (count($dependent) == 4) {
                    $dependentPre = $dependent[3];
                    $dependentPreAlternative = '';
                } else {
                    $dependentPre = $dependent[1];
                    $dependentPreAlternative = $dependent[2];
                }
            }
            // Anything in $post enclosed in '%' characters is only to be printed if the resource has something in the
            // next field -- replace with unique string for later preg_replace().
            if (preg_match('/%(.*)%(.*)%|%(.*)%/U', $post, $dependent)) {
                $post = str_replace($dependent[0], '__DEPENDENT_ON_NEXT_FIELD__', $post);
                if (count($dependent) == 4) {
                    $dependentPost = $dependent[3];
                    $dependentPostAlternative = '';
                } else {
                    $dependentPost = $dependent[1];
                    $dependentPostAlternative = $dependent[2];
                }
            }
            // find singular/plural alternatives in $pre and $post and replace with unique string for later preg_replace().
            if (preg_match("/\^(.*)\^(.*)\^/U", $pre, $matchCarat)) {
                $pre = str_replace($matchCarat[0], '__SINGULAR_PLURAL__', $pre);
                $singular = $matchCarat[1];
                $plural = $matchCarat[2];
            } elseif (preg_match("/\^(.*)\^(.*)\^/U", $post, $matchCarat)) {
                $post = str_replace($matchCarat[0], '__SINGULAR_PLURAL__', $post);
                $singular = $matchCarat[1];
                $plural = $matchCarat[2];
            }
            // Now dump into $final[$fieldName] stripping any backticks
            if ($dependentPre) {
                $final[$fieldName]['dependentPre'] = $dependentPre;
            } else {
                $final[$fieldName]['dependentPre'] = '';
            }
            if ($dependentPost) {
                $final[$fieldName]['dependentPost'] = $dependentPost;
            } else {
                $final[$fieldName]['dependentPost'] = '';
            }
            if ($dependentPreAlternative) {
                $final[$fieldName]['dependentPreAlternative'] = $dependentPreAlternative;
            } else {
                $final[$fieldName]['dependentPreAlternative'] = '';
            }
            if ($dependentPostAlternative) {
                $final[$fieldName]['dependentPostAlternative'] = $dependentPostAlternative;
            } else {
                $final[$fieldName]['dependentPostAlternative'] = '';
            }
            if ($singular) {
                $final[$fieldName]['singular'] = $singular;
            } else {
                $final[$fieldName]['singular'] = '';
            }
            if ($plural) {
                $final[$fieldName]['plural'] = $plural;
            } else {
                $final[$fieldName]['plural'] = '';
            }
            $final[$fieldName]['pre'] = str_replace('`', '', $pre);
            $final[$fieldName]['post'] = str_replace('`', '', $post);
            $index++;
        }
        if (isset($possiblePreliminaryText)) {
            if (isset($independent)) {
                $independent = ['independent_0' => $possiblePreliminaryText] + $independent;
            } else {
                $final['preliminaryText'] = $possiblePreliminaryText;
            }
        }
        if ($final) { // presumably no field names...
            $this->badInput($this->errors->text('inputError', 'invalid'), $this->errorDisplay);
        }
        if (isset($independent)) {
            $size = count($independent);
            // If $size == 3 and exists 'independent_0', this is preliminaryText
            // If $size == 3 and exists 'independent_' . $index, this is ultimate
            // If $size % 2 == 0 and exists 'independent_0' and 'independent_' . $index, these are preliminaryText and ultimate
            if (($size == 3) && array_key_exists('independent_0', $independent)) {
                $final['preliminaryText'] = array_shift($independent);
            } elseif (($size == 3) && array_key_exists('independent_' . $index, $independent)) {
                $final['ultimate'] = array_pop($independent);
            } elseif (!($size % 2) && array_key_exists('independent_0', $independent)
            && array_key_exists('independent_' . $index, $independent)) {
                $final['preliminaryText'] = array_shift($independent);
                $final['ultimate'] = array_pop($independent);
            }
            $size = count($independent);
            // last element of odd number is actually ultimate punctuation or first element is preliminary if exists 'independent_0'
            if ($size % 2) {
                if (array_key_exists('independent_0', $independent)) {
                    $final['preliminaryText'] = array_shift($independent);
                } else {
                    $final['ultimate'] = array_pop($independent);
                }
            }
            if ($size == 1) {
                if (array_key_exists('independent_0', $independent)) {
                    $final['preliminaryText'] = array_shift($independent);
                }
                if (array_key_exists('independent_' . $index, $independent)) {
                    $final['ultimate'] = array_shift($independent);
                }
            }
            // $ultimate is not never defined
            /*
            if (isset($ultimate) && !array_key_exists('ultimate', $final)) {
                $final['ultimate'] = $ultimate;
            }
            */
            // $preliminaryText is never defined
            /*
            if (isset($preliminaryText) && !array_key_exists('preliminaryText', $final)) {
                $final['preliminaryText'] = $preliminaryText;
            }
            */
            if (!empty($independent)) {
                $final['independent'] = $independent;
            }
        }
        return $final;
    }

    /**
     * write the styles to file.
     * If !$fileName, this is called from add() and we create folder/filename immediately before writing to file.
     * If $fileName, this comes from edit()
     *
     * @todo $fileName is optional parameter, should be required parameter
     */
    public function writeFile(string $fileName = '')
    {
        if ($fileName) {
            $this->errorDisplay = 'editInit';
        } else {
            $this->errorDisplay = 'addInit';
        }
        include_once(__DIR__ . '/TABLE.php');
        include_once(__DIR__ . '/../STYLEMAP.php');
        $this->map = new STYLEMAP();
        include_once(__DIR__ . '/../UTF8.php');
        $this->utf8 = new UTF8();
        $types = array_keys($this->map->getTypes());
        // Start XML
        $fileString = '<?xml version="1.0" encoding="utf-8"?>';
        $fileString .= '<style xml:lang="en">';
        // Main style information
        $fileString .= '<info>';
        $fileString .= '<name>' . trim(stripslashes($this->vars['styleShortName'])) . '</name>';
        $fileString .= '<description>' . htmlspecialchars(trim(stripslashes($this->vars['styleLongName'])))
             . '</description>';
        // Temporary place holder
        $fileString .= '<language>English</language>';
        $fileString .= '<osbibVersion>' . self::OSBIB_VERSION . '</osbibVersion>';
        $fileString .= '</info>';
        // Start citation definition
        $fileString .= '<citation>';
        $inputArray = [
            'cite_creatorStyle', 'cite_creatorOtherStyle', 'cite_creatorInitials',
            'cite_creatorFirstName', 'cite_twoCreatorsSep', 'cite_creatorSepFirstBetween',
            'cite_creatorListSubsequentAbbreviation', 'cite_creatorSepNextBetween',
            'cite_creatorSepNextLast', 'cite_creatorList', 'cite_creatorListMore',
            'cite_creatorListLimit', 'cite_creatorListAbbreviation', 'cite_creatorUppercase',
            'cite_creatorListSubsequentAbbreviationItalic', 'cite_creatorListAbbreviationItalic',
            'cite_creatorListSubsequent', 'cite_creatorListSubsequentMore',
            'cite_creatorListSubsequentLimit', 'cite_consecutiveCreatorTemplate', 'cite_consecutiveCreatorSep',
            'cite_template', 'cite_useInitials', 'cite_consecutiveCitationSep', 'cite_yearFormat',
            'cite_pageFormat', 'cite_titleCapitalization', 'cite_ibid', 'cite_idem',
            'cite_opCit', 'cite_followCreatorTemplate',
            'cite_firstChars', 'cite_lastChars', 'cite_citationStyle', 'cite_templateEndnoteInText',
            'cite_templateEndnote', 'cite_consecutiveCitationEndnoteInTextSep', 'cite_firstCharsEndnoteInText',
            'cite_lastCharsEndnoteInText', 'cite_formatEndnoteInText', 'cite_endnoteStyle',
            'cite_ambiguous', 'cite_ambiguousTemplate', 'cite_order1', 'cite_order2', 'cite_order3',
            'cite_order1desc', 'cite_order2desc', 'cite_order3desc', 'cite_sameIdOrderBib',
            'cite_firstCharsEndnoteID', 'cite_lastCharsEndnoteID',
            'cite_followCreatorPageSplit', 'cite_subsequentCreatorTemplate', 'cite_replaceYear',
        ];
        foreach ($inputArray as $input) {
            if (isset($this->vars[$input])) {
                $split = mb_split('_', $input, 2);
                $elementName = $split[1];
                $fileString .= "<$elementName>" .
                    htmlspecialchars(stripslashes($this->vars[$input])) . "</$elementName>";
            }
        }
        // Resource types replacing citation templates
        foreach ($types as $key) {
            $citationStringName = 'cite_' . $key . 'Template';
            if (array_key_exists($citationStringName, $this->vars) &&
            ($string = $this->vars[$citationStringName])) {
                $fileString .= '<' . $key . 'Template>' . htmlspecialchars(stripslashes($string)) .
                '</' . $key . 'Template>';
            }
            $field = 'cite_' . $key . '_notInBibliography';
            $element = $key . '_notInBibliography';
            if (isset($this->vars[$field])) {
                $fileString .= "<$element>" . $this->vars[$field] . "</$element>";
            }
        }
        $fileString .= '</citation>';
        // Footnote creator formatting
        $fileString .= '<footnote>';
        $inputArray = [
        // foot note creator formatting
            'footnote_primaryCreatorFirstStyle', 'footnote_primaryCreatorOtherStyle',
            'footnote_primaryCreatorList', 'footnote_primaryCreatorFirstName',
            'footnote_primaryCreatorListAbbreviationItalic', 'footnote_primaryCreatorInitials',
            'footnote_primaryCreatorListMore', 'footnote_primaryCreatorListLimit',
            'footnote_primaryCreatorListAbbreviation', 'footnote_primaryCreatorUppercase',
            'footnote_primaryCreatorRepeatString', 'footnote_primaryCreatorRepeat',
            'footnote_primaryCreatorSepFirstBetween',  'footnote_primaryTwoCreatorsSep',
            'footnote_primaryCreatorSepNextBetween', 'footnote_primaryCreatorSepNextLast',
            'footnote_otherCreatorFirstStyle', 'footnote_otherCreatorListAbbreviationItalic',
            'footnote_otherCreatorOtherStyle', 'footnote_otherCreatorInitials',
            'footnote_otherCreatorFirstName', 'footnote_otherCreatorList',
            'footnote_otherCreatorUppercase', 'footnote_otherCreatorListMore',
            'footnote_otherCreatorListLimit', 'footnote_otherCreatorListAbbreviation',
            'footnote_otherCreatorSepFirstBetween', 'footnote_otherCreatorSepNextBetween',
            'footnote_otherCreatorSepNextLast', 'footnote_otherTwoCreatorsSep',
        ];
        foreach ($inputArray as $input) {
            if (isset($this->vars[$input])) {
                $split = mb_split('_', $input, 2);
                $elementName = $split[1];
                $fileString .= "<$elementName>" .
                    htmlspecialchars(stripslashes($this->vars[$input])) . "</$elementName>";
            }
        }
        $this->footnotePages = true;
        // Footnote templates for each resource type
        foreach ($types as $key) {
            $type = 'footnote_' . $key . 'Template';
            $name = 'footnote_' . $key;
            $input = trim(stripslashes($this->vars[$type]));
            // remove newlines etc.
            $input = preg_replace("/\r|\n|\015|\012/", '', $input);
            $fileString .= "<resource name=\"$key\">";
            $fileString .= $this->arrayToXML($this->parseStringToArray($key, $input), $name);
            $fileString .= '</resource>';
        }
        $fileString .= '</footnote>';
        $this->footnotePages = false;
        // Start bibliography
        $fileString .= '<bibliography>';
        // Common section defining how authors, titles etc. are formatted
        $fileString .= '<common>';
        $inputArray = [
        // style
            'style_titleCapitalization', 'style_monthFormat', 'style_editionFormat', 'style_dateFormat',

            'style_primaryCreatorFirstStyle', 'style_primaryCreatorOtherStyle', 'style_primaryCreatorInitials',
            'style_primaryCreatorFirstName', 'style_otherCreatorFirstStyle',
            'style_otherCreatorOtherStyle', 'style_otherCreatorInitials',
            'style_otherCreatorFirstName', 'style_primaryCreatorList', 'style_otherCreatorList',
            'style_primaryCreatorListAbbreviationItalic', 'style_otherCreatorListAbbreviationItalic',
            'style_primaryCreatorListMore', 'style_primaryCreatorListLimit',
            'style_primaryCreatorListAbbreviation', 'style_otherCreatorListMore',
            'style_primaryCreatorRepeatString', 'style_primaryCreatorRepeat',
            'style_otherCreatorListLimit', 'style_otherCreatorListAbbreviation',
            'style_primaryCreatorUppercase',
            'style_otherCreatorUppercase', 'style_primaryCreatorSepFirstBetween',
            'style_primaryCreatorSepNextBetween', 'style_primaryCreatorSepNextLast',
            'style_otherCreatorSepFirstBetween', 'style_otherCreatorSepNextBetween',
            'style_otherCreatorSepNextLast', 'style_primaryTwoCreatorsSep', 'style_otherTwoCreatorsSep',
            'style_userMonth_1', 'style_userMonth_2', 'style_userMonth_3', 'style_userMonth_4',
            'style_userMonth_5', 'style_userMonth_6', 'style_userMonth_7', 'style_userMonth_8',
            'style_userMonth_9', 'style_userMonth_10', 'style_userMonth_11', 'style_userMonth_12',
            'style_dateRangeDelimit1', 'style_dateRangeDelimit2', 'style_dateRangeSameMonth',
            'style_dateMonthNoDay', 'style_dateMonthNoDayString', 'style_dayLeadingZero', 'style_dayFormat',
            'style_runningTimeFormat', 'style_editorSwitch', 'style_editorSwitchIfYes',
            'style_pageFormat',
        ];
        foreach ($inputArray as $input) {
            if (isset($this->vars[$input])) {
                $split = mb_split('_', $input, 2);
                $elementName = $split[1];
                $fileString .= "<$elementName>" .
                    htmlspecialchars(stripslashes($this->vars[$input])) . "</$elementName>";
            }
        }
        $fileString .= '</common>';
        // Resource types
        foreach ($types as $key) {
            $type = 'style_' . $key;
            $input = trim(stripslashes($this->vars[$type]));
            // remove newlines etc.
            $input = preg_replace("/\r|\n|\015|\012/", '', $input);
            // Rewrite creator strings
            $attributes = $this->creatorXMLAttributes($type);
            $fileString .= "<resource name=\"$key\" $attributes>";
            $fileString .= $this->arrayToXML($this->parseStringToArray($key, $input), $type);
            if (($key != 'genericBook') && ($key != 'genericArticle') && ($key != 'genericMisc')) {
                $name = $type . '_generic';
                if (!isset($this->vars[$name])) {
                    $name = 'genericMisc';
                } else {
                    $name = $this->vars[$name];
                }
                $fileString .= "<fallbackstyle>$name</fallbackstyle>";
            }
            $fileString .= '</resource>';
        }
        $fileString .= '</bibliography>';
        $fileString .= '</style>';
        if (!$fileName) { // called from add()
            // Create folder with lowercase styleShortName
            $dirName = OSBIB_STYLE_DIR . '/' . strtolower(trim($this->vars['styleShortName']));
            if (!mkdir($dirName)) {
                $this->badInput($error = $this->errors->text('file', 'folder'), $this->errorDisplay);
            }
            $fileName = $dirName . '/' . strtoupper(trim($this->vars['styleShortName'])) . '.xml';
        }
        if (!$fp = fopen("$fileName", 'w')) {
            $this->badInput($this->errors->text('file', 'write', ": $fileName"), $this->errorDisplay);
        }
        if (!fwrite($fp, $this->utf8->encodeUtf8($fileString))) {
            $this->badInput($this->errors->text('file', 'write', ": $fileName"), $this->errorDisplay);
        }
        fclose($fp);
        // Remove sessionvars
        $this->session->clearArray('cite');
        $this->session->clearArray('style');
    }

    /**
     * create attribute strings for XML <resource> element for creators
     */
    public function creatorXMLAttributes(string $type): string
    {
        $attributes = '';
        foreach ($this->creators as $creatorField) {
            $basic = $type . '_' . $creatorField;
            $field = $basic . '_firstString';
            $name = $creatorField . '_firstString';
            if (array_key_exists($field, $this->vars) && trim($this->vars[$field])) {
                $attributes .= "$name=\"" . htmlspecialchars(stripslashes($this->vars[$field])) . '" ';
            }
            $field = $basic . '_firstString_before';
            $name = $creatorField . '_firstString_before';
            if (isset($this->vars[$field])) {
                $attributes .= "$name=\"" . htmlspecialchars(stripslashes($this->vars[$field])) . '" ';
            }
            $field = $basic . '_remainderString';
            $name = $creatorField . '_remainderString';
            if (array_key_exists($field, $this->vars) && trim($this->vars[$field])) {
                $attributes .= "$name=\"" . htmlspecialchars(stripslashes($this->vars[$field])) . '" ';
            }
            $field = $basic . '_remainderString_before';
            $name = $creatorField . '_remainderString_before';
            if (isset($this->vars[$field])) {
                $attributes .= "$name=\"" . htmlspecialchars(stripslashes($this->vars[$field])) . '" ';
            }
            $field = $basic . '_remainderString_each';
            $name = $creatorField . '_remainderString_each';
            if (isset($this->vars[$field])) {
                $attributes .= "$name=\"" . htmlspecialchars(stripslashes($this->vars[$field])) . '" ';
            }
        }
        return $attributes;
    }

    /**
     * Parse array to XML
     *
     * @todo $type not used
     */
    public function arrayToXML(array $array, string $type): string
    {
        $fileString = '';
        foreach ($array as $key => $value) {
            $fileString .= "<$key>";
            if (is_array($value)) {
                $fileString .= $this->arrayToXML($value, $type);
            } else {
                $fileString .= htmlspecialchars($value);
            }
            $fileString .= "</$key>";
        }
        return $fileString;
    }

    /**
     * validate input
     * @return string|bool
     */
    public function validateInput(string $type)
    {
        $error = false;
        if (($type == 'add') || ($type == 'edit')) {
            $array = ['style_titleCapitalization', 'style_primaryCreatorFirstStyle',
                'style_primaryCreatorOtherStyle', 'style_primaryCreatorInitials',
                'style_primaryCreatorFirstName', 'style_otherCreatorFirstStyle', 'style_dateFormat',
                'style_otherCreatorOtherStyle', 'style_otherCreatorInitials', 'style_pageFormat',
                'style_otherCreatorFirstName', 'style_primaryCreatorList', 'style_dayFormat',
                'style_otherCreatorList', 'style_monthFormat', 'style_editionFormat',
                'style_runningTimeFormat', 'style_editorSwitch', 'style_primaryCreatorRepeat',
                'style_dateRangeSameMonth', 'style_dateMonthNoDay',
                'cite_creatorStyle', 'cite_creatorOtherStyle', 'cite_creatorInitials', 'cite_creatorFirstName',
                'cite_twoCreatorsSep', 'cite_creatorSepFirstBetween', 'cite_creatorListSubsequentAbbreviation',
                'cite_creatorSepNextBetween', 'cite_creatorSepNextLast',
                'cite_creatorList', 'cite_creatorListMore', 'cite_creatorListLimit', 'cite_creatorListAbbreviation',
                'cite_creatorListSubsequent', 'cite_creatorListSubsequentMore', 'cite_creatorListSubsequentLimit',
                'cite_template', 'cite_templateEndnoteInText', 'cite_templateEndnote',
                'cite_consecutiveCitationSep', 'cite_yearFormat', 'cite_pageFormat',
                'cite_titleCapitalization', 'cite_citationStyle', 'cite_formatEndnoteInText', 'cite_ambiguous',
                'footnote_primaryCreatorFirstStyle',
                'footnote_primaryCreatorOtherStyle', 'footnote_primaryCreatorInitials',
                'footnote_primaryCreatorFirstName',
                'footnote_primaryCreatorList',  'footnote_primaryCreatorRepeat',
                // Probably not required but code left here in case (see creatorsFormatting())
                'footnote_otherCreatorFirstStyle', 'footnote_otherCreatorFirstName',
                'footnote_otherCreatorOtherStyle', 'footnote_otherCreatorInitials', 'footnote_otherCreatorList',
            ];
            $this->writeSession($array);
            if (!trim($this->vars['styleShortName'])) {
                $error = $this->errors->text('inputError', 'missing');
            } else {
                $this->session->setVar('style_shortName', trim($this->vars['styleShortName']));
            }
            if (preg_match("/\s/", trim($this->vars['styleShortName']))) {
                $error = $this->errors->text('inputError', 'invalid');
            } elseif (!trim($this->vars['styleLongName'])) {
                $error = $this->errors->text('inputError', 'missing');
            } elseif (!trim($this->vars['style_genericBook'])) {
                $error = $this->errors->text('inputError', 'missing');
            } elseif (!trim($this->vars['style_genericArticle'])) {
                $error = $this->errors->text('inputError', 'missing');
            } elseif (!trim($this->vars['style_genericMisc'])) {
                $error = $this->errors->text('inputError', 'missing');
            }
            foreach ($array as $input) {
                if (!isset($this->vars[$input])) {
                    return $this->errors->text('inputError', 'missing');
                }
            }
            if ($this->vars['cite_citationStyle'] == 1) { // endnotes
                // Must also have a bibliography template for the resource if a footnote template is defined
                if ($this->vars['cite_endnoteStyle'] == 2) { // footnotes
                    $types = array_keys($this->map->getTypes());
                    foreach ($types as $key) {
                        $type = 'footnote_' . $key . 'Template';
                        $name = 'footnote_' . $key;
                        $input = trim(stripslashes($this->vars[$type]));
                        if ($input && !$this->vars['style_' . $key]) {
                            return $this->errors->text('inputError', 'missing');
                        }
                    }
                    if (($this->vars['footnote_primaryCreatorList'] == 1) &&
                        (!trim($this->vars['footnote_primaryCreatorListLimit']) ||
                        (!$this->vars['footnote_primaryCreatorListMore']))) {
                        $error = $this->errors->text('inputError', 'missing');
                    } elseif (($this->vars['footnote_primaryCreatorList'] == 1) &&
                    (!is_numeric($this->vars['footnote_primaryCreatorListLimit']) ||
                    !is_numeric($this->vars['footnote_primaryCreatorListMore']))) {
                        $error = $this->errors->text('inputError', 'nan');
                    } elseif (($this->vars['footnote_otherCreatorList'] == 1) &&
                    (!trim($this->vars['footnote_otherCreatorListLimit']) ||
                    (!$this->vars['footnote_otherCreatorListMore']))) {
                        $error = $this->errors->text('inputError', 'missing');
                    } elseif (($this->vars['footnote_otherCreatorList'] == 1) &&
                    (!is_numeric($this->vars['footnote_otherCreatorListLimit']) ||
                    !is_numeric($this->vars['footnote_otherCreatorListMore']))) {
                        $error = $this->errors->text('inputError', 'nan');
                    } elseif (($this->vars['footnote_otherCreatorList'] == 1) &&
                    (!is_numeric($this->vars['footnote_otherCreatorListLimit']) ||
                    !is_numeric($this->vars['footnote_otherCreatorListMore']))) {
                        $error = $this->errors->text('inputError', 'nan');
                    } elseif (($this->vars['footnote_primaryCreatorRepeat'] == 2) &&
                    !trim($this->vars['footnote_primaryCreatorRepeatString'])) {
                        $error = $this->errors->text('inputError', 'missing');
                    }
                }
                if (!trim($this->vars['cite_templateEndnoteInText'])) {
                    $error = $this->errors->text('inputError', 'missing');
                } elseif (!trim($this->vars['cite_templateEndnote'])) {
                    $error = $this->errors->text('inputError', 'missing');
                }
            } elseif (!trim($this->vars['cite_template'])) {
                $error = $this->errors->text('inputError', 'missing');
            }
            // If xxx_creatorList set to 1 (limit), we must have style_xxxCreatorListMore and xxx_CreatorListLimit. The
            // latter two must be numeric.
            if (($this->vars['style_primaryCreatorList'] == 1) &&
                (!trim($this->vars['style_primaryCreatorListLimit']) ||
                (!$this->vars['style_primaryCreatorListMore']))) {
                $error = $this->errors->text('inputError', 'missing');
            } elseif (($this->vars['style_primaryCreatorList'] == 1) &&
            (!is_numeric($this->vars['style_primaryCreatorListLimit']) ||
            !is_numeric($this->vars['style_primaryCreatorListMore']))) {
                $error = $this->errors->text('inputError', 'nan');
            } elseif (($this->vars['style_otherCreatorList'] == 1) &&
            (!trim($this->vars['style_otherCreatorListLimit']) ||
            (!$this->vars['style_otherCreatorListMore']))) {
                $error = $this->errors->text('inputError', 'missing');
            } elseif (($this->vars['cite_creatorList'] == 1) &&
            (!trim($this->vars['cite_creatorListLimit']) ||
            (!$this->vars['cite_creatorListMore']))) {
                $error = $this->errors->text('inputError', 'missing');
            } elseif (($this->vars['cite_creatorList'] == 1) &&
            (!is_numeric($this->vars['cite_creatorListLimit']) ||
            !is_numeric($this->vars['cite_creatorListMore']))) {
                $error = $this->errors->text('inputError', 'nan');
            } elseif (($this->vars['cite_creatorListSubsequent'] == 1) &&
            (!trim($this->vars['cite_creatorListSubsequentLimit']) ||
            (!$this->vars['cite_creatorListSubsequentMore']))) {
                $error = $this->errors->text('inputError', 'missing');
            } elseif (($this->vars['cite_creatorListSubsequent'] == 1) &&
            (!is_numeric($this->vars['cite_creatorListSubsequentLimit']) ||
            !is_numeric($this->vars['cite_creatorListSubsequentMore']))) {
                $error = $this->errors->text('inputError', 'nan');
            } elseif (($this->vars['style_editorSwitch'] == 1) &&
            !trim($this->vars['style_editorSwitchIfYes'])) {
                $error = $this->errors->text('inputError', 'missing');
            } elseif (($this->vars['style_primaryCreatorRepeat'] == 2) &&
            !trim($this->vars['style_primaryCreatorRepeatString'])) {
                $error = $this->errors->text('inputError', 'missing');
            } elseif ($this->vars['style_monthFormat'] == 2) {
                for ($i = 1; $i <= 12; $i++) {
                    if (!trim($this->vars["style_userMonth_$i"])) {
                        $error = $this->errors->text('inputError', 'missing');
                    }
                }
            }
            // If style_dateMonthNoDay, style_dateMonthNoDayString must have at least 'date' in it
            elseif ($this->vars['style_dateMonthNoDay']) {
                if (strstr($this->vars['style_dateMonthNoDayString'], 'date') === false) {
                    $error = $this->errors->text('inputError', 'invalid');
                }
            }
            if (($this->vars['cite_ambiguous'] == 2) &&
                !trim($this->vars['cite_ambiguousTemplate'])) {
                $error = $this->errors->text('inputError', 'missing');
            }
        }
        if ($type == 'add') {
            if (preg_match("/\s/", trim($this->vars['styleShortName']))) {
                $error = $this->errors->text('inputError', 'invalid');
            } elseif (array_key_exists(strtoupper(trim($this->vars['styleShortName'])), $this->styles)) {
                $error = $this->errors->text('inputError', 'styleExists');
            }
        } elseif ($type == 'editDisplay') {
            if (!array_key_exists('editStyleFile', $this->vars)) {
                $error = $this->errors->text('inputError', 'missing');
            }
        }
        if ($error) {
            return $error;
        }
        // FALSE means validated input
        return false;
    }

    public function writeSession(array $array): void
    {
        include_once(__DIR__ . '/TABLE.php');
        include_once(__DIR__ . '/../STYLEMAP.php');
        $this->map = new STYLEMAP();
        $types = array_keys($this->map->getTypes());
        if (trim($this->vars['styleLongName'])) {
            $this->session->setVar(
                'style_longName',
                base64_encode(trim(htmlspecialchars($this->vars['styleLongName'])))
            );
        }
        // other resource types
        foreach ($types as $key) {
            // Footnote templates
            $array[] = 'footnote_' . $key . 'Template';
            $type = 'style_' . $key;
            if (trim($this->vars[$type])) {
                $this->session->setVar($type, base64_encode(trim(htmlspecialchars($this->vars[$type]))));
            }
            // Rewrite creator strings
            foreach ($this->creators as $creatorField) {
                $basic = $type . '_' . $creatorField;
                $field = $basic . '_firstString';
                if (array_key_exists($field, $this->vars) && trim($this->vars[$field])) {
                    $this->session->setVar($field, base64_encode(htmlspecialchars($this->vars[$field])));
                }
                $field = $basic . '_firstString_before';
                if (isset($this->vars[$field])) {
                    $this->session->setVar($field, base64_encode($this->vars[$field]));
                }
                $field = $basic . '_remainderString';
                if (array_key_exists($field, $this->vars) && trim($this->vars[$field])) {
                    $this->session->setVar($field, base64_encode(htmlspecialchars($this->vars[$field])));
                }
                $field = $basic . '_remainderString_before';
                if (isset($this->vars[$field])) {
                    $this->session->setVar($field, base64_encode($this->vars[$field]));
                }
                $field = $basic . '_remainderString_each';
                if (isset($this->vars[$field])) {
                    $this->session->setVar($field, base64_encode($this->vars[$field]));
                }
            }
            $field = 'cite_' . $key . '_notInBibliography';
            if (isset($this->vars[$field])) {
                $this->session->setVar($field, base64_encode(trim($this->vars[$field])));
            }
            $citationStringName = 'cite_' . $key . 'Template';
            if (array_key_exists($citationStringName, $this->vars) &&
            ($input = $this->vars[$citationStringName])) {
                $this->session->setVar($citationStringName, base64_encode(htmlspecialchars($input)));
            }
            // Fallback styles
            if (($key != 'genericBook') && ($key != 'genericArticle') && ($key != 'genericMisc')) {
                $name = $type . '_generic';
                $this->session->setVar($name, base64_encode(trim($this->vars[$name])));
            }
        }
        // Other values. $array parameter is required, other optional input is added to the array
        $array[] = 'style_primaryCreatorSepBetween';
        $array[] = 'style_primaryCreatorSepLast';
        $array[] = 'style_otherCreatorSepBetween';
        $array[] = 'style_otherCreatorSepLast';
        $array[] = 'style_primaryCreatorListMore';
        $array[] = 'style_primaryCreatorListLimit';
        $array[] = 'style_primaryCreatorListAbbreviation';
        $array[] = 'style_otherCreatorListMore';
        $array[] = 'style_otherCreatorListLimit';
        $array[] = 'style_otherCreatorListAbbreviation';
        $array[] = 'style_editorSwitchIfYes';
        $array[] = 'style_primaryCreatorUppercase';
        $array[] = 'style_otherCreatorUppercase';
        $array[] = 'style_primaryTwoCreatorsSep';
        $array[] = 'style_primaryCreatorSepFirstBetween';
        $array[] = 'style_primaryCreatorSepNextBetween';
        $array[] = 'style_primaryCreatorSepNextLast';
        $array[] = 'style_otherTwoCreatorsSep';
        $array[] = 'style_otherCreatorSepFirstBetween';
        $array[] = 'style_otherCreatorSepNextBetween';
        $array[] = 'style_otherCreatorSepNextLast';
        $array[] = 'style_primaryCreatorRepeatString';
        $array[] = 'style_primaryCreatorListAbbreviationItalic';
        $array[] = 'style_otherCreatorListAbbreviationItalic';
        $array[] = 'style_dateMonthNoDayString';
        $array[] = 'style_userMonth_1';
        $array[] = 'style_userMonth_2';
        $array[] = 'style_userMonth_3';
        $array[] = 'style_userMonth_4';
        $array[] = 'style_userMonth_5';
        $array[] = 'style_userMonth_6';
        $array[] = 'style_userMonth_7';
        $array[] = 'style_userMonth_8';
        $array[] = 'style_userMonth_9';
        $array[] = 'style_userMonth_10';
        $array[] = 'style_userMonth_11';
        $array[] = 'style_userMonth_12';
        $array[] = 'style_dateRangeDelimit1';
        $array[] = 'style_dateRangeDelimit2';
        $array[] = 'style_dayLeadingZero';
        $array[] = 'cite_useInitials';
        $array[] = 'cite_creatorUppercase';
        $array[] = 'cite_creatorListAbbreviationItalic';
        $array[] = 'cite_creatorListSubsequentAbbreviationItalic';
        $array[] = 'cite_ambiguousTemplate';
        $array[] = 'cite_ibid';
        $array[] = 'cite_idem';
        $array[] = 'cite_opCit';
        $array[] = 'cite_followCreatorTemplate';
        $array[] = 'cite_consecutiveCreatorTemplate';
        $array[] = 'cite_consecutiveCreatorSep';
        $array[] = 'cite_firstChars';
        $array[] = 'cite_lastChars';
        $array[] = 'cite_consecutiveCitationEndnoteInTextSep';
        $array[] = 'cite_firstCharsEndnoteInText';
        $array[] = 'cite_lastCharsEndnoteInText';
        $array[] = 'cite_endnoteStyle';
        $array[] = 'cite_order1';
        $array[] = 'cite_order2';
        $array[] = 'cite_order3';
        $array[] = 'cite_order1desc';
        $array[] = 'cite_order2desc';
        $array[] = 'cite_order3desc';
        $array[] = 'cite_sameIdOrderBib';
        $array[] = 'cite_firstCharsEndnoteID';
        $array[] = 'cite_lastCharsEndnoteID';
        $array[] = 'cite_followCreatorPageSplit';
        $array[] = 'cite_subsequentCreatorTemplate';
        $array[] = 'cite_replaceYear';
        $array[] = 'footnote_primaryCreatorSepBetween';
        $array[] = 'footnote_primaryCreatorSepLast';
        $array[] = 'footnote_primaryCreatorListMore';
        $array[] = 'footnote_primaryCreatorListLimit';
        $array[] = 'footnote_primaryCreatorListAbbreviation';
        $array[] = 'footnote_primaryCreatorUppercase';
        $array[] = 'footnote_primaryTwoCreatorsSep';
        $array[] = 'footnote_primaryCreatorSepFirstBetween';
        $array[] = 'footnote_primaryCreatorSepNextBetween';
        $array[] = 'footnote_primaryCreatorSepNextLast';
        $array[] = 'footnote_primaryCreatorRepeatString';
        $array[] = 'footnote_primaryCreatorListAbbreviationItalic';
        // Probably not required but code left here in case (see creatorsFormatting())
        $array[] = 'footnote_otherCreatorListAbbreviationItalic';
        $array[] = 'footnote_otherTwoCreatorsSep';
        $array[] = 'footnote_otherCreatorSepFirstBetween';
        $array[] = 'footnote_otherCreatorSepNextBetween';
        $array[] = 'footnote_otherCreatorSepNextLast';
        $array[] = 'footnote_otherCreatorUppercase';
        $array[] = 'footnote_otherCreatorListMore';
        $array[] = 'footnote_otherCreatorListLimit';
        $array[] = 'footnote_otherCreatorListAbbreviation';
        $array[] = 'footnote_otherCreatorSepBetween';
        $array[] = 'footnote_otherCreatorSepLast';
        foreach ($array as $input) {
            if (isset($this->vars[$input])) {
                $this->session->setVar($input, base64_encode(htmlspecialchars($this->vars[$input])));
            } else {
                $this->session->delVar($input);
            }
        }
    }

    public function badInput(string $error, string $method): void
    {
        include_once(__DIR__ . '/CLOSE.php');
        new CLOSE($this->$method($error));
    }
}
