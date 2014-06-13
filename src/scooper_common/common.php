<?php
/**
 * Copyright 2014 Bryan Selner
 *
 * Licensed under the Apache License, Version 2.0 (the "License"); you may
 * not use this file except in compliance with the License. You may obtain
 * a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS, WITHOUT
 * WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the
 * License for the specific language governing permissions and limitations
 * under the License.
 */

/****************************************************************************************************************/
/****                                                                                                        ****/
/****         Common Includes                                                                                ****/
/****                                                                                                        ****/
/****************************************************************************************************************/
define('__ROOT__', dirname(dirname(__FILE__)));
if ( file_exists ( __ROOT__.'/scooper_common/SimpleScooterCSVFileClass.php' ) )
{
    require_once(__ROOT__.'/scooper_common/SimpleScooterCSVFileClass.php');
}
else
{
    require_once(__ROOT__.'/scooper_common/SimpleScooperCSVClass.php');

}
require_once(__ROOT__.'/scooper_common/debug_functions.php');


ini_set('auto_detect_line_endings', true);

const C__RECORD_CHUNK_SIZE__ = 5;
const C__FSHOWVERBOSE_APICALL__ = 0;


function getDefaultFileName($strFilePrefix, $strBase, $strExt)
{
    $strApp = "";
    if(C__APPNAME__) { $strApp = C__APPNAME__ . "_"; }
    return sprintf($strApp . date("Ymd-Hms")."%s_%s.%s", ($strFilePrefix != null ? "_".$strFilePrefix : ""), ($strBase != null  ? "_".$strBase : ""), $strExt);
}


/****************************************************************************************************************/
/****                                                                                                        ****/
/****         Helper Functions:  Available Options and Command Line Settings                                 ****/
/****                                                                                                        ****/
/****************************************************************************************************************/

function addToErrs(&$strErr, $strNew)
{
    $strErr = (strlen($strErr) > 0 ? "; " : ""). $strNew;

}

function getFullPathFromFileDetails($arrFileDetails, $strPrependToFileBase = "", $strAppendToFileBase = "")
{
    return $arrFileDetails['directory'] . getFileNameFromFileDetails($arrFileDetails, $strPrependToFileBase, $strAppendToFileBase);

}

function getFileNameFromFileDetails($arrFileDetails, $strPrependToFileBase = "", $strAppendToFileBase = "")
{
    return $strPrependToFileBase . $arrFileDetails['file_name_base'] . $strAppendToFileBase . "." . $arrFileDetails['file_extension'];
}

function parseFilePath($strFilePath, $fFileMustExist = false)
{
    $arrReturnFileDetails = array ('full_file_path' => '', 'directory' => '', 'file_name' => '', 'file_name_base' => '', 'file_extension' => '');


    if(strlen($strFilePath) > 0)
    {
        if(is_dir($strFilePath))
        {
            $arrReturnFileDetails['directory'] = $strFilePath;
        }
        else
        {

            // separate into elements by '/'
            $arrFilePathParts = explode("/", $strFilePath);

            if(count($arrFilePathParts) <= 1)
            {
                $arrReturnFileDetails['directory'] = ".";
                $arrReturnFileDetails['file_name'] = $arrFilePathParts[0];
            }
            else
            {
                // pop the last element (the file name + extension) into a string
                $arrReturnFileDetails['file_name'] = array_pop($arrFilePathParts);

                // put the rest of the path parts back together into a path string
                $arrReturnFileDetails['directory']= implode("/", $arrFilePathParts);
            }

            if(strlen($arrReturnFileDetails['directory']) == 0 && strlen($arrReturnFileDetails['file_name']) > 0 && file_exists($arrReturnFileDetails['file_name']))
            {
                $arrReturnFileDetails['directory'] = dirname($arrReturnFileDetails['file_name']);

            }
            if(!file_exists($arrReturnFileDetails['directory']))
            {
                __log__('Specfied path '.$strFilePath.' does not exist.', C__LOGLEVEL_WARN__);
            }
            else
            {
                // since we have a directory and a file name, combine them into the full file path
                $arrReturnFileDetails['full_file_path'] = $arrReturnFileDetails['directory'] . "/" . $arrReturnFileDetails['file_name'];

                if($fFileMustExist == true && !is_file($arrReturnFileDetails['full_file_path']))
                {
                    __log__('Required file '.$arrReturnFileDetails['full_file_path'].' does not exist.', C__LOGLEVEL_WARN__);
                }
                else
                {

                    // separate the file name by '.' to break the extension out
                    $arrFileNameParts = explode(".", $arrReturnFileDetails['file_name']);

                    // pop off the extension
                    $arrReturnFileDetails['file_extension'] = array_pop($arrFileNameParts );

                    // put the rest of the filename back together into a string.
                    $arrReturnFileDetails['file_name_base'] = implode(".", $arrFileNameParts );
                }
            }
        }
    }

    // Make sure the directory part ends with a slash always
    $strDir = $arrReturnFileDetails['directory'];

    if((strlen($strDir) >= 1) && $strDir[strlen($strDir)-1] != "/")
    {
        $arrReturnFileDetails['directory'] = $arrReturnFileDetails['directory'] . "/";
    }

    return $arrReturnFileDetails;

}

function getEmptyUserInputRecord()
{
    return array('header_keys'=>null, 'data_type' => null, 'data_rows'=>array());
}
function get_PharseOptionValue($strOptName)
{
    $retvalue = null;
    $strOptGiven = $strOptName."_given";
    if($GLOBALS['OPTS'][$strOptGiven] == true)
    {
        __debug__printLine("'".$strOptName ."'"."=[".$GLOBALS['OPTS'][$strOptName] ."]", C__DISPLAY_ITEM_DETAIL__);
        $retvalue = $GLOBALS['OPTS'][$strOptName];
    }
    else
    {
        $retvalue = null;
    }

    return $retvalue;
}

function setGlobalFileDetails($key, $fRequireFile = false, $fullpath = null)
{
    $ret = null;
    $ret = parseFilePath($fullpath, $fRequireFile);

    __debug__printLine("". $key ." set to [" . var_export($ret, true) . "]", C__DISPLAY_ITEM_DETAIL__);

    $GLOBALS['OPTS'][$key] = $ret;

    return $ret;
}

function set_FileDetails_fromPharseSetting($optUserKeyName, $optDetailsKeyName, $fFileRequired)
{
    $valOpt = get_PharseOptionValue($optUserKeyName);
    return setGlobalFileDetails($optDetailsKeyName, $fFileRequired, $valOpt);
}


function get_FileDetails_fromPharseOption($optUserKeyName, $fFileRequired)
{
    $ret = null;
    $valOpt = get_PharseOptionValue($optUserKeyName);
    if($valOpt) $ret = parseFilePath($valOpt, $fFileRequired);

    return $ret;

}



/****************************************************************************************************************/
/****                                                                                                        ****/
/****         Helper Functions:  Array processing                                                            ****/
/****                                                                                                        ****/
/****************************************************************************************************************/
function addPrefixToArrayKeys( $arr, $strPrefix = "", $strSep = "" )
{

    $arrKeys = array_keys($arr);
    $arrNewKeyValues = $arrKeys;
    $arrNewKeys = array();
    if(strlen($strPrefix) > 0)
    {
        foreach ($arrKeys as $key)
        {
            $arrNewKeys[] = $strPrefix.$strSep.$key;
        }
        $arrNewKeyValues = array_combine($arrNewKeys, $arr);
    }

    return $arrNewKeyValues;
}

function array_to_object($d) {
    if (is_array($d)) {
        /*
        * Return array converted to object
        * Using __FUNCTION__ (Magic constant)
        * for recursive call
        */
        return (object) array_map(__FUNCTION__, $d);
    }
    else {
        // Return object
        return $d;
    }
}
	function object_to_array($d) {
    if (is_object($d)) {
        // Gets the properties of the given object
        // with get_object_vars function
        $d = get_object_vars($d);
    }

    if (is_array($d)) {
        /*
        * Return array converted to object
        * Using __FUNCTION__ (Magic constant)
        * for recursive call
        */
        return array_map(__FUNCTION__, $d);
    }
    else {
        // Return array
        return $d;
    }
}


function merge_into_array_and_add_new_keys( &$arr1, $arr2 )
{

    $arrOrig1 = $arr1;
    $arr1 = my_merge_add_new_keys( $arrOrig1, $arr2 );

}

function my_merge_add_new_keys( $arr1, $arr2 )
{
    // check if inputs are really arrays
    if (!is_array($arr1) || !is_array($arr2)) {
    }
    $strFunc = "my_merge_add_new_keys(arr1(size=".count($arr1)."),arr2(size=".count($arr2)."))";
    __debug__printLine($strFunc, C__DISPLAY_FUNCTION__, true);
    $arr1Keys = array_keys($arr1);
    $arr2Keys = array_keys($arr2);
    $arrCombinedKeys = array_merge_recursive($arr1Keys, $arr2Keys);

    $arrNewBlankCombinedRecord = array_fill_keys($arrCombinedKeys, 'unknown');

    $arrMerged =  array_replace( $arrNewBlankCombinedRecord, $arr1 );
    $arrMerged =  array_replace( $arrMerged, $arr2 );

    __debug__printLine('returning from ' . $strFunc, C__DISPLAY_FUNCTION__, true);
    return $arrMerged;
}

function my_merge( $arr1, $arr2 )
{
    // check if inputs are really arrays
    if (!is_array($arr1) || !is_array($arr2)) {
        throw new Exception("Input  is not an Array");
    }
    __debug__printLine("my_merge(arr1(size=".count($arr1).",first=".array_keys($arr1)[0].",arr2(size=".count($arr2).",first=".array_keys($arr2)[0].")", C__DISPLAY_FUNCTION__, true);
    $keys = array_keys( $arr2 );
    foreach( $keys as $key ) {
        if( isset( $arr1[$key] )
            && is_array( $arr1[$key] )
            && is_array( $arr2[$key] )
        ) {
            $arr1[$key] = my_merge( $arr1[$key], $arr2[$key] );
        } else {
            $arr1[$key] = $arr2[$key];
        }
    }
    return $arr1;
}

// Source: http://www.php.net/manual/en/ref.array.php#81081

/**
 * make a recursive copy of an array
 *
 * @param array $aSource
 * @return array    copy of source array
 * @throws Exception if array is not valid
 */
function array_copy ($aSource) {
    // check if input is really an array
    if (!is_array($aSource)) {
        throw new Exception("Input is not an Array");
    }

    // initialize return array
    $aRetAr = array();

    // get array keys
    $aKeys = array_keys($aSource);
    // get array values
    $aVals = array_values($aSource);

    // loop through array and assign keys+values to new return array
    for ($x=0;$x<count($aKeys);$x++) {
        // clone if object
        if (is_object($aVals[$x])) {
            $aRetAr[$aKeys[$x]]=clone $aVals[$x];
            // recursively add array
        } elseif (is_array($aVals[$x])) {
            $aRetAr[$aKeys[$x]]=array_copy ($aVals[$x]);
            // assign just a plain scalar value
        } else {
            $aRetAr[$aKeys[$x]]=$aVals[$x];
        }
    }

    return $aRetAr;
}
function addSeqKey($m)
{
    return(array($m => $m));
}




function array_addseq_key($arr)
{
    $arrStringKeys = array_map(function($n) { return sprintf('key%03d', $n); }, range(1, count($arr)) );
    return array_combine(array_values($arrStringKeys), array_values($arr));
}
/*
 * Flattening a multi-dimensional array into a
 * single-dimensional one. The resulting keys are a
 * string-separated list of the original keys:
 *
 * a[x][y][z] becomes a[implode(sep, array(x,y,z))]
 */

function array_flatten_sep($sep, $array) {
    $result = array();
    $stack = array();
    array_push($stack, array("", $array));

    while (count($stack) > 0)
    {
        list($prefix, $array) = array_pop($stack);

        foreach ($array as $key => $value)
        {
            $new_key = $prefix . strval($key);

            if (is_array($value))
                array_push($stack, array($new_key . $sep, $value));
            else
                $result[$new_key] = $value;
        }
    }

    return $result;
}

/*
 * Flattening a multi-dimensional array into an
 * n-dimensional one. The last n keys of each element are
 * preserved. If this results in ambiguities, results are
 * undefined.
 *
 * a[x_1][x_2]...[x_m]  becomes  a[x_{m-n+1}]...[x_m]
 */
function array_flatten_n($array, $n) {
    $result = array();
    $stack = array();
    array_push($stack, array(array(), $array));

    while (count($stack) > 0) {
        list($prefix, $array) = array_pop($stack);

        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $new_prefix = array_values($prefix);
                array_push($new_prefix, $key);
                if (count($new_prefix) >= $n)
                    array_shift($new_prefix);

                array_push($stack, array($new_prefix, $value));
            } else {
                $array = $result;
                foreach ($prefix as $pkey) {
                    if (!is_array($array[$pkey]))
                        $array[$pkey] = array();
                    $array = $array[$pkey];
                }
                $array[$key] = $value;
            }
        }
    }

    return $result;
}


function strTrimAndLower($str)
{
    return strScrub($str, LOWERCASE | REMOVE_EXTRA_WHITESPACE );
}

/*
0x20 : 00100000
0x10 : 00010000
0x08 : 00001000
0x04 : 00000100
0x02 : 00000010
0x01 : 00000001
*/

define('REMOVE_PUNCT', 0x001);
define('LOWERCASE', 0x002);
define('HTML_DECODE', 0x004);
define('URL_ENCODE', 0x008);
define('REPLACE_SPACES_WITH_HYPHENS', 0x010);
define('REMOVE_EXTRA_WHITESPACE', 0x020);
define('REMOVE_ALL_SPACES', 0x040);
define('SIMPLE_TEXT_CLEANUP', HTML_DECODE | REMOVE_EXTRA_WHITESPACE );
define('ADVANCED_TEXT_CLEANUP', HTML_DECODE | REMOVE_EXTRA_WHITESPACE | REMOVE_PUNCT | REMOVE_EXTRA_WHITESPACE | HTML_DECODE );
define('FOR_LOOKUP_VALUE_MATCHING', REMOVE_PUNCT | LOWERCASE | HTML_DECODE | LOWERCASE | REMOVE_EXTRA_WHITESPACE | REMOVE_ALL_SPACES );
define('DEFAULT_SCRUB', REMOVE_PUNCT | HTML_DECODE | LOWERCASE | REMOVE_EXTRA_WHITESPACE );

//And so on, 0x8, 0x10, 0x20, 0x40, 0x80, 0x100, 0x200, 0x400, 0x800 etc..


function strScrub($str, $flags = null)
{
    if($flags == null)  $flags = REMOVE_EXTRA_WHITESPACE;
    $ret = $str;


    if ($flags & HTML_DECODE)
    {
        $ret = html_entity_decode($ret);
    }

    if ($flags & REMOVE_PUNCT)  // has to come after HTML_DECODE
    {
        $ret = strip_punctuation($ret);
    }

    if ($flags & REMOVE_ALL_SPACES)
    {
        $ret = trim($ret);
        if($ret != null)
        {
            $ret  = str_replace(" ", "", $ret);
        }
    }

    if ($flags & REMOVE_EXTRA_WHITESPACE)
    {
        $ret = trim($ret);
        if($ret != null)
        {
            $ret  = str_replace(array("   ", "  ", "    "), " ", $ret);
            $ret  = str_replace(array("   ", "  ", "    "), " ", $ret);
        }
        $ret = trim($ret);
    }


    if ($flags & REPLACE_SPACES_WITH_HYPHENS) // has to come after REMOVE_EXTRA_WHITESPACE
    {
        $ret  = str_replace(" ", "-", $ret); // do it twice to catch the multiples
    }


    if ($flags & LOWERCASE)
    {
        $ret = strtolower($ret);
    }

    if ($flags & URL_ENCODE)
    {
        $ret  = urlencode($ret);
    }

    return $ret;
}



function intceil($number)
{
    if(is_string($number)) $number = floatval($number);

    $ret = ( is_numeric($number) ) ? ceil($number) : false;
    if ($ret != false) $ret = intval($ret);

    return $ret;
}

function readarray($from_array, $addr = array()) {
//    var_dump('readarray start:' .var_export($from_array, true));
    global $output;
        foreach ($from_array as $key => $value)
        {
            if (is_Array($value) && count($value) > 0) {
                $addr[] = $key;
                $output[] = readarray($value, $addr);
            } else {
                $output[] = implode('||', $addr) . $value;
            }
        }
    return $output;
}

const C_ARRFLAT_SUBITEM_NONE__ = 0;
const C_ARRFLAT_SUBITEM_SEPARATOR__ = 1;
const C_ARRFLAT_SUBITEM_LINEBREAK__ = 2;

function substr_count_array( $haystack, $needle ) {
    $count = 0;
    foreach ($needle as $substring) {
        $count += substr_count( $haystack, $substring);
    }
    return $count;
}

function is_array_multidimensional($a)
{
    if(!is_array($a)) return false;
    foreach($a as $v) if(is_array($v)) return TRUE;
    return FALSE;
}

function array_flatten($arr, $strDelim = '|', $flagsSubItems=C_ARRFLAT_SUBITEM_NONE__)
{
    $fSkipLevel = false;
    $keys = array_keys($arr);
    $values= array_values($arr);
    $output = array();
    foreach ($keys as $key => $item)
    {
       $newVal = $values[$key];
        if(is_array($newVal))
        {
            if(is_array_multidimensional($newVal))
            {
               $outputVal = array_flatten($newVal, $strDelim, $flagsSubItems );
            }
            else
            {
               $outputVal = implode($strDelim, $newVal);
           }
        }
        else
       {
            $outputVal = $newVal;
       }
        $fIncludeLineBreaks = (substr_count($outputVal, "|") > 1 && ($flagsSubItems & C_ARRFLAT_SUBITEM_LINEBREAK__));
        $fIncludeSeparators = (substr_count($outputVal, "|") > 1 && ($flagsSubItems & C_ARRFLAT_SUBITEM_SEPARATOR__));
        $output[$key] = ($fIncludeLineBreaks ? "\n" : "") . ($fIncludeSeparators ? "(" : "") . $outputVal . ($fIncludeSeparators ? ")" : "");
    }
    $ret = implode($strDelim, $output);

    return $ret;
}

/**
 * Strip punctuation from text.
 * http://nadeausoftware.com/articles/2007/9/php_tip_how_strip_punctuation_characters_web_page
 */
function strip_punctuation( $text )
{
    $urlbrackets    = '\[\]\(\)';
    $urlspacebefore = ':;\'_\*%@&?!' . $urlbrackets;
    $urlspaceafter  = '\.,:;\'\-_\*@&\/\\\\\?!#' . $urlbrackets;
    $urlall         = '\.,:;\'\-_\*%@&\/\\\\\?!#' . $urlbrackets;

    $specialquotes  = '\'"\*<>';

    $fullstop       = '\x{002E}\x{FE52}\x{FF0E}';
    $comma          = '\x{002C}\x{FE50}\x{FF0C}';
    $arabsep        = '\x{066B}\x{066C}';
    $numseparators  = $fullstop . $comma . $arabsep;

    $numbersign     = '\x{0023}\x{FE5F}\x{FF03}';
    $percent        = '\x{066A}\x{0025}\x{066A}\x{FE6A}\x{FF05}\x{2030}\x{2031}';
    $prime          = '\x{2032}\x{2033}\x{2034}\x{2057}';
    $nummodifiers   = $numbersign . $percent . $prime;

    return preg_replace(
        array(
            // Remove separator, control, formatting, surrogate,
            // open/close quotes.
            '/[\p{Z}\p{Cc}\p{Cf}\p{Cs}\p{Pi}\p{Pf}]/u',
            // Remove other punctuation except special cases
            '/\p{Po}(?<![' . $specialquotes .
            $numseparators . $urlall . $nummodifiers . '])/u',
            // Remove non-URL open/close brackets, except URL brackets.
            '/[\p{Ps}\p{Pe}](?<![' . $urlbrackets . '])/u',
            // Remove special quotes, dashes, connectors, number
            // separators, and URL characters followed by a space
            '/[' . $specialquotes . $numseparators . $urlspaceafter .
            '\p{Pd}\p{Pc}]+((?= )|$)/u',
            // Remove special quotes, connectors, and URL characters
            // preceded by a space
            '/((?<= )|^)[' . $specialquotes . $urlspacebefore . '\p{Pc}]+/u',
            // Remove dashes preceded by a space, but not followed by a number
            '/((?<= )|^)\p{Pd}+(?![\p{N}\p{Sc}])/u',
            // Remove consecutive spaces
            '/ +/',
        ),
        ' ',
        $text );
}

/**
 * copied from <a href="http://php.net/manual/en/function.system.php">http://php.net/manual/en/function.system.php</a>
 * returns an array of stdout, stderr, and return value from the systemcall
 */
function my_exec($cmd, $input='') {
    $proc=proc_open($cmd, array(0=>array('pipe', 'r'), 1=>array('pipe', 'w'), 2=>array('pipe', 'w')), $pipes);
    fwrite($pipes[0], $input);fclose($pipes[0]);
    $stdout=stream_get_contents($pipes[1]);fclose($pipes[1]);
    $stderr=stream_get_contents($pipes[2]);fclose($pipes[2]);
    $rtn=proc_close($proc);
    return array(
        'stdout'=>$stdout,
        'stderr'=>$stderr,
        'return'=>$rtn
    );
}






