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


require_once dirname(__FILE__) .'/SimpleScooterCSVFileClass.php';
require_once dirname(__FILE__) . '/debug_functions.php';

ini_set('auto_detect_line_endings', true);

$GLOBALS['VERBOSE'] = false;
$GLOBALS['OPTS'] = null;
const C__APPNAME__ = "Scooper";
const C__APP_VERSION_MAJOR___ = "0";
const C__APP_VERSION_MINOR___ = ".11";
const C__RECORD_CHUNK_SIZE__ = 5;
const C__FSHOWVERBOSE_APICALL__ = 0;

const C_LOOKUP_MODE_UNKNOWN = -1;
const C_LOOKUP_MODE_SINGLE = 1;
const C_LOOKUP_MODE_FILE = 2;
$GLOBALS['lookup_mode'] = C_LOOKUP_MODE_UNKNOWN;

const C__LOOKUP_DATATYPE_NAME__ = 1;
const C__LOOKUP_DATATYPE_URL__ = 2;
const C__LOOKUP_DATATYPE_BASICFACTS__ = 3;

function getDefaultFileName($strFilePrefix, $strBase, $strExt)
{
    return sprintf(C__APPNAME__."_". date("Ymd-Hms")."%s_%s.%s", ($strFilePrefix != null ? "_".$strFilePrefix : ""), ($strBase != null  ? "_".$strBase : ""), $strExt);
}

/****************************************************************************************************************/
/****                                                                                                        ****/
/****         Logging                                                                                        ****/
/****                                                                                                        ****/
/****************************************************************************************************************/

const C__LOGLEVEL_DEBUG__	= 1;	// Most Verbose
const C__LOGLEVEL_INFO__	= 2;	// ...
const C__LOGLEVEL_WARN__	= 3;	// ...
const C__LOGLEVEL_ERROR__	= 4;	// ...
const C__LOGLEVEL_FATAL__	= 5;	// Least Verbose
const C__LOGLEVEL_OFF__		= 6;	// Nothing at all.

//
// If installed as part of the package, uses Klogger v0.1 version (http://codefury.net/projects/klogger/)
//
if ( file_exists ( dirname(__FILE__) . '/../lib/KLogger/src/KLogger.php') )
{
    define(C_USE_KLOGGER, 1);
    require_once dirname(__FILE__) . '/../lib/KLogger/src/KLogger.php';

}
else
{
    print "Could not find KLogger file: ". dirname(__FILE__) . '/../lib/KLogger/src/KLogger.php'.PHP_EOL;
    define(C_USE_KLOGGER, 0);
}



/****************************************************************************************************************/
/****                                                                                                        ****/
/****         Common Declarations                                                                            ****/
/****                                                                                                        ****/
/****************************************************************************************************************/


const C__API_RETURN_TYPE_OBJECT__ = 33;
const C__API_RETURN_TYPE_ARRAY__ = 44;


$GLOBALS['ALL_KEYS_IN_RIGHT_RESULTS_ORDER'] =  array(
    'company_name' => '<not set>',
    'result_accuracy_warnings' => '<not set>',
    'company_name_linked' => '<not set>',
    'actual_site_url' => '<not set>',
    'crunchbase_match_accuracy' => '<not set>',
    'cb.category_code' => '<not set>',
    'cb.description' => '<not set>',
    'cb.overview' => '<not set>',
    'Specialization' => '<not set>',
    'Where Did Company Get Added to the List From?' => '<not set>',
    'Company Description' => '<not set>',
    'Contact Info' => '<not set>',
    'Notes' => '<not set>',
    'Status' => '<not set>',
    'Last Status Update Date' => '<not set>',
    'Open Roles List (if any)' => '<not set>',
    'cb.crunchbase_url' => '<not set>',
    'cb.offices' => '<not set>',
    'cb.number_of_employees' => '<not set>',
    'cb.total_money_raised' => '<not set>',
    'cb.funding_rounds' => '<not set>',
    'cb.acquisition' => '<not set>',
    'cb.acquisitions' => '<not set>',
    'cb.ipo' => '<not set>',
    'cb.founded_year' => '<not set>',
    'cb.founded_month' => '<not set>',
    'cb.founded_day' => '<not set>',
    'cb.funds' => '<not set>',
    'cb.products' => '<not set>',
    'cb.competitions' => '<not set>',
    'cb.phone_number' => '<not set>',
    'cb.email_address' => '<not set>',
    'cb.screenshots' => '<not set>',
    'cb.partners' => '<not set>',
    'person.first_name' => '<not set>',
    'person.last_name' => '<not set>',
    'cb.homepage_url' => '<not set>',
    'cb.twitter_username' => '<not set>',
    'cb.computed_domain' => '<not set>',
    'cb.image' => '<not set>',
    'cb.blog_url' => '<not set>',
    'cb.blog_feed_url' => '<not set>',
    'effective_domain' => '<not set>',
    'input_source_url' => '<not set>',
    'quantcast.monthly_uniques' => '<not set>',
    'cb.video_embeds' => '<not set>',
    'cb.external_links' => '<not set>',
    'cb.deadpooled_year' => '<not set>',
    'cb.deadpooled_month' => '<not set>',
    'cb.deadpooled_day' => '<not set>',
    'cb.deadpooled_url' => '<not set>',
    'cb.name' => '<not set>',
    'cb.namespace' => '<not set>',
    'cb.permalink' => '<not set>',
    'cb.tag_list' => '<not set>',
    'cb.alias_list' => '<not set>',
    'cb.created_at' => '<not set>',
    'cb.updated_at' => '<not set>',
    'cb.relationships' => '<not set>',
    'cb.investments' => '<not set>',
    'cb.milestones' => '<not set>',
    'cb.providerships' => '<not set>',
    'feid' => '<not set>',
    'fid' => '<not set>',
    'fmrp' => '<not set>',
    'fmrr' => '<not set>',
    'pda' => '<not set>',
    'peid' => '<not set>',
    'pid' => '<not set>',
    'ueid' => '<not set>',
    'ufq' => '<not set>',
    'uid' => '<not set>',
    'uifq' => '<not set>',
    'uipl' => '<not set>',
    'ujid' => '<not set>',
    'umrp' => '<not set>',
    'umrr' => '<not set>',
    'upl' => '<not set>',
    'ut' => '<not set>',
    'uu' => '<not set>',
    'kind' => '<not set>'
);


/****************************************************************************************************************/
/****                                                                                                        ****/
/****         Helper Functions:  Information and Error Logging                                               ****/
/****                                                                                                        ****/
/****************************************************************************************************************/

function __initLogger__($strBaseFileName = null, $strOutputDirPath = null)
{
    $fileLogFullPath = getDefaultFileName(null,$strBaseFileName,"log");

    $GLOBALS['logger'] = null;

    if(C_USE_KLOGGER == 1)
    {

        $log = new KLogger ( $fileLogFullPath , KLogger::DEBUG );

        $GLOBALS['logger'] = $log;

        __log__("Initialized output log:  ".$fileLogFullPath, C__LOGLEVEL_INFO__);

    }
    else
    {
        __debug__printLine("Output log will not be enabled.  KLogger is not installed. ".$fileLogFullPath, C__DISPLAY_NORMAL__);
    }
}


function __log__($strToLog, $LOG_LEVEL)
{
    $arrLevelNames = array( 'DEBUG', 'INFO', 'WARN', 'ERROR', 'FATAL', 'OFF' );

    $strLogLine =  $strToLog;



    if($GLOBALS['logger'] != null)
    {
        switch ($LOG_LEVEL)
        {
            case C__LOGLEVEL_DEBUG__:
                $GLOBALS['logger']->LogDebug($strLogLine);
                break;

            case C__LOGLEVEL_WARN__:
                $GLOBALS['logger']->LogWarn($strLogLine);
                break;

            case C__LOGLEVEL_ERROR__:
                $GLOBALS['logger']->LogError($strLogLine);
                break;

            case C__LOGLEVEL_FATAL__:
                $GLOBALS['logger']->LogFatal($strLogLine);
                break;

            default:
            case C__LOGLEVEL_INFO__:
            $GLOBALS['logger']->LogInfo($strLogLine);
                break;
        }
    }
    print '['.$arrLevelNames[$LOG_LEVEL-1]."] ".$strLogLine .PHP_EOL;
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
    return $arrFileDetails['directory'] . $strPrependToFileBase . $arrFileDetails['file_name_base'] . $strAppendToFileBase . "." . $arrFileDetails['file_extension'];

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
    if((strlen($strDir) > 1) && $strDir[strlen($strDir)-1] != "/")
    {
        $arrReturnFileDetails['directory'] = $arrReturnFileDetails['directory'] . "/";
    }

    return $arrReturnFileDetails;

}

function getEmptyUserInputRecord()
{
    return array('header_keys'=>null, 'data_type' => null, 'data_rows'=>array());
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

function merge_into_array_and_add_new_keys( &$arr1, $arr2 )
{

    $arrOrig1 = $arr1;
    $arr1 = my_merge_add_new_keys( $arrOrig1, $arr2 );

}

function my_merge_add_new_keys( $arr1, $arr2 )
{
    // check if inputs are really arrays
    if (!is_array($arr1) || !is_array($arr2)) {
        throw new Exception("Input is not an Array");
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
                if (count($new_prefix) >= n)
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
    if($str != null && is_string($str)) { return strtolower(trim($str)); }

    return $str;
}

function strScrub($str)
{
    $ret = strTrimAndLower($str);
    if($ret != null)
    {
        $ret  = str_replace(array(".", ",", "–", "/", "-", ":", ";"), " ", $ret);
        $ret  = str_replace("  ", " ", $ret);
        $ret  = str_replace("  ", " ", $ret); // do it twice to catch the multiples
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

?>
