<?php
/********************************
OSBib:
A collection of PHP classes to create and manage bibliographic formatting for OS bibliography software
using the OSBib standard.

Released through http://bibliophile.sourceforge.net under the GPL licence.
Do whatever you like with this -- some credit to the author(s) would be appreciated.

If you make improvements, please consider contacting the administrators at bibliophile.sourceforge.net
so that your improvements can be added to the release package.

Mark Grimshaw 2005
http://bibliophile.sourceforge.net
********************************/

class PARSESTYLE
{
    protected ?STYLEMAP $styleMap = null;

    /**
     * parse input into array
     * @todo function very long and complex, unclear what it does, split up and write tests and comments!
     */
    public function parseStringToArray(string $type, string $subjectString, ?STYLEMAP $styleMap = null, bool $date = false): array
    {
        $ultimate = '';
        $lastSubject = '';
        $independent = [];

        if (!$subjectString) {
            return [];
        }
        if ($styleMap) {
            $this->styleMap = $styleMap;
        }
        $search = implode('|', $this->styleMap->$type);
        if ($date) {
            $search .= '|' . 'date';
        }
        $subjectArray = mb_split("\|", $subjectString);
        $sizeSubject = count($subjectArray);
        // Loop each field string
        $index = 0;
        $subjectIndex = 0;
        foreach ($subjectArray as $subject) {
            $lastSubject = $subject;
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
                if ($independent && ($subjectIndex == $sizeSubject) &&
                    array_key_exists('independent_' . $index, $independent)) {
                    $ultimate = $subject;
                } else {
                    if ($independent && (count($independent) % 2)) {
                        $independent['independent_' . ($index - 1)] = $subject;
                    } else {
                        $independent['independent_' . $index] = $subject;
                    }
                }
                continue;
            }
            // At this stage, [2] is the fieldName, [1] is what comes before and [3] is what comes after.
            $pre = $array[1];
            $fieldName = $array[2];
            if ($date && ($fieldName == 'date')) {
                $fieldName = $this->styleMap->{$type}['date'];
            }
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
            if ($independent) {
                $independent = ['independent_0' => $possiblePreliminaryText] + $independent;
            } else {
                $final['preliminaryText'] = $possiblePreliminaryText;
            }
        }
        if (!isset($final)) { // presumably no field names... so assume $subject is standalone text and return
            $final['preliminaryText'] = $lastSubject ?: $subjectString;
            return $final;
        }
        if ($independent) {
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
            if (($ultimate ?? false) && !array_key_exists('ultimate', $final)) {
                $final['ultimate'] = $ultimate;
            }
            // todo $preliminaryText is never initialized!
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
}
