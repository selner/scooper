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

require_once dirname(__FILE__) . '/../config.php';
require_once dirname(__FILE__) .'/SimpleScooterCSVFileClass.php';
require_once dirname(__FILE__) . '/debug_functions.php';

$GLOBALS['VERBOSE'] = false;
$GLOBALS['OPTS'] = null;
const C__APPNAME__ = "Scooper";
const C__APP_VERSION_MAJOR___ = "0";
const C__APP_VERSION_MINOR___ = ".11";
const C__RECORD_CHUNK_SIZE__ = 1;
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


$GLOBALS['ALL_POSSIBLE_RECORD_KEYS'] =  array(
    'company_name' => '<not set>',
    'input_source_url' => '<not set>',
    'result_accuracy_warnings' => '<not set>',
    'effective_domain' => '<not set>',
    'actual_site_url' => '<not set>',
	'quantcast.monthly_uniques' => '<not set>',
    'crunchbase_match_accuracy' => '<not set>',
    'cb.name' => '<not set>',
    'cb.namespace' => '<not set>',
    'cb.description' => '<not set>',
    'cb.overview' => '<not set>',
    'cb.category_code' => '<not set>',
    'cb.permalink' => '<not set>',
    'cb.crunchbase_url' => '<not set>',
    'cb.homepage_url' => '<not set>',
    'cb.computed_domain' => '<not set>',
    'cb.image' => '<not set>',
    'cb.offices' => '<not set>',
    'cb.blog_url' => '<not set>',
    'cb.blog_feed_url' => '<not set>',
    'cb.twitter_username' => '<not set>',
    'cb.phone_number' => '<not set>',
    'cb.email_address' => '<not set>',
    'cb.number_of_employees' => '<not set>',
    'cb.founded_year' => '<not set>',
    'cb.founded_month' => '<not set>',
    'cb.founded_day' => '<not set>',
    'cb.tag_list' => '<not set>',
    'cb.alias_list' => '<not set>',
    'cb.created_at' => '<not set>',
    'cb.updated_at' => '<not set>',
    'cb.relationships' => '<not set>',
    'cb.investments' => '<not set>',
    'cb.milestones' => '<not set>',
    'cb.providerships' => '<not set>',
    'cb.funds' => '<not set>',
    'cb.video_embeds' => '<not set>',
    'cb.external_links' => '<not set>',
    'cb.deadpooled_year' => '<not set>',
    'cb.deadpooled_month' => '<not set>',
    'cb.deadpooled_day' => '<not set>',
    'cb.deadpooled_url' => '<not set>',
    'cb.products' => '<not set>',
    'cb.competitions' => '<not set>',
    'cb.total_money_raised' => '<not set>',
    'cb.funding_rounds' => '<not set>',
    'cb.acquisition' => '<not set>',
    'cb.acquisitions' => '<not set>',
    'cb.ipo' => '<not set>',
    'cb.screenshots' => '<not set>',
    'cb.partners' => '<not set>',
    'person.first_name' => '<not set>',
    'person.last_name' => '<not set>',
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

function __check_args__()
{
    $strErrOptions = "";
    $fHadFatalError = false;

    if(!$GLOBALS['OPTS']) {  __get_args__(); }


    /****************************************************************************************************************/
    /****                                                                                                        ****/
    /****    Initialize the app and setup the options based on the command line variables                        ****/
    /****                                                                                                        ****/
    /****************************************************************************************************************/

    if($GLOBALS['OPTS']['verbose_given']) {  $GLOBALS['VERBOSE'] = true; } else { $GLOBALS['VERBOSE'] = false; }
    if($GLOBALS['OPTS']['verbose_api_calls_given']) {  define(C__FSHOWVERBOSE_APICALL__, true); } else { define(C__FSHOWVERBOSE_APICALL__, false); }


    if($GLOBALS['VERBOSE'] == true) { __log__ ('Options set: '.var_export($GLOBALS['OPTS'], true), C__LOGLEVEL_INFO__); }


    /****************************************************************************************************************/
    /****                                                                                                        ****/
    /****    Get the input and output file settings                                                              ****/
    /****                                                                                                        ****/
    /****************************************************************************************************************/
    $GLOBALS['input_file_details'] = null;
    $GLOBALS['output_file_details'] = null;


    $GLOBALS['input_file_details'] = parseFilePath($GLOBALS['OPTS']['inputfile'], $GLOBALS['OPTS']['inputfile_given']);
    $GLOBALS['output_file_details'] = parseFilePath($GLOBALS['OPTS']['outputfile'], false);
    $strDefaultOutFileName = getDefaultFileName("_output_",$GLOBALS['input_file_details']['file_name_base'],"csv");


    if(strlen($GLOBALS['output_file_details']['full_file_path']) == 0)
    {
        if(strlen($GLOBALS['output_file_details']['directory']) > 0 )
        {
            $GLOBALS['output_file_details'] = parseFilePath($GLOBALS['output_file_details']['directory'] . "/" . $strDefaultOutFileName , false);
        }
        else if(strlen($GLOBALS['output_file_details']['directory'] == 0) && strlen($GLOBALS['input_file_details']['directory']) > 0)
        {
            $GLOBALS['output_file_details'] = parseFilePath($GLOBALS['input_file_details']['directory']."/".$strDefaultOutFileName, false);
        }
        else
        {
            $GLOBALS['output_file_details'] = parseFilePath("./".$strDefaultOutFileName, false);
        }
    }

    if(strlen($GLOBALS['output_file_details']['full_file_path']) == 0)
    {
        addToErrs($strErrOptions,  "Output file path '". $GLOBALS['output_file_details']['full_file_path'] ."' could not be found.");
        $fHadFatalError = true;
    }


    /****************************************************************************************************************/
    /****                                                                                                        ****/
    /****    Determine whether we're doing a single lookup or processing an input file                           ****/
    /****                                                                                                        ****/
    /****************************************************************************************************************/

    if($GLOBALS['OPTS']['lookup_name_given'] || $GLOBALS['OPTS']['lookup_url_given'])
    {
        $GLOBALS['lookup_mode'] = C_LOOKUP_MODE_SINGLE;

        if($GLOBALS['OPTS']['lookup_url_given'] && strlen($GLOBALS['OPTS']['lookup_url']) == 0 )
        {
            addToErrs($strErrOptions, "Company website URL required with --lookup_url/-lu .");
            $fHadFatalError = true;
        }
        else if($GLOBALS['OPTS']['lookup_name_given'] && strlen($GLOBALS['OPTS']['lookup_name']) == 0 )
        {
            addToErrs($strErrOptions, "Company name required with --lookup_name/-ln .");
            $fHadFatalError = true;
        }

        if(strlen($GLOBALS['output_file_details']['full_file_path']) == 0)
        {
            addToErrs($strErrOptions, 'Output file path required (--outputfile / -o) when using single lookup mode.');
            $fHadFatalError = true;
        }
    }
    else
    {
        $GLOBALS['lookup_mode'] = C_LOOKUP_MODE_FILE ;
    }


    if($GLOBALS['lookup_mode'] == C_LOOKUP_MODE_FILE  && strlen($GLOBALS['input_file_details']['full_file_path']) == 0)
    {
        addToErrs($strErrOptions, 'You must specify a valid input CSV file.');

    }



    /****************************************************************************************************************/
    /****                                                                                                        ****/
    /****    get the settings for the plugins                                                                    ****/
    /****                                                                                                        ****/
    /****************************************************************************************************************/


    if($GLOBALS['OPTS']['exclude_quantcast_given'] ) {  $GLOBALS['OPTS']['exclude_quantcast'] = 1;  } else { $GLOBALS['OPTS']['exclude_quantcast'] = 0; }
    if(!$GLOBALS['OPTS']['moz_access_id_given'] )
    {
        $GLOBALS['OPTS']['moz_access_id'] = C__MOZ_API_ACCESS_ID__;
        __debug__printLine("No Moz.com access ID given by the the user.  Defaulting to config value: (".C__MOZ_API_ACCESS_ID__.")." , C__DISPLAY_ERROR__);
    }
    if(!$GLOBALS['OPTS']['moz_secret_key_given'] )
    {
        $GLOBALS['OPTS']['moz_secret_key'] = C__MOZ_API_ACCESS_ID__;
        __debug__printLine("No Moz.com secret key given by the the user.  Defaulting to config value: (".C__MOZ_API_ACCESS_SECRETKEY__.")." , C__DISPLAY_ERROR__);
    }

    if(!$GLOBALS['OPTS']['exclude_moz_given'] && (strlen($GLOBALS['OPTS']['moz_access_id']) == 0 && $GLOBALS['OPTS']['moz_secret_key'] == 0)  )
    {
        if(!$GLOBALS['OPTS']['exclude_moz_given']) { __debug__printLine("Moz API access ID and secret key were not both set.  Excluding Moz.com data. ", C__DISPLAY_ERROR__); }
        $GLOBALS['OPTS']['exclude_moz'] = 1;
    }
    else
    {
        $GLOBALS['OPTS']['exclude_moz'] = 0;
    }

    if($GLOBALS['OPTS']['exclude_crunchbase_given'] )
    {
        $GLOBALS['OPTS']['exclude_crunchbase'] = 1;
    }
    else
    {
        $GLOBALS['OPTS']['exclude_crunchbase'] = 0;
        if(!$GLOBALS['OPTS']['crunchbase_api_id_given']  || (strlen($GLOBALS['OPTS']['crunchbase_api_id']) == 0)  )
        {
            $GLOBALS['OPTS']['crunchbase_api_id'] = C__CRUNCHBASE_API_KEY__;
            if(strlen(C__CRUNCHBASE_API_KEY__) > 0)
                __debug__printLine("No Crunchbase API Key given by the the user.  Defaulting to config value: (".C__CRUNCHBASE_API_KEY__.")." , C__DISPLAY_ERROR__);
            else
            {
                $GLOBALS['OPTS']['exclude_crunchbase'] = 1;
                __debug__printLine("No Crunchbase API Key given by the the user. Excluding Crunchbase." , C__DISPLAY_ERROR__);
            }
        }

    }






    if($fHadFatalError == true)
    {
        __log__($strErrOptions, C__LOGLEVEL_FATAL__);

       exit(PHP_EOL."Unable to run with the settings specified: ".PHP_EOL.var_export($GLOBALS['OPTS'], true).PHP_EOL."Run --help option to view the required settings.".PHP_EOL);
    }

    return $strErrOptions;

}

function __get_args__()
{

    # specify some options
    $options = array(
        'lookup_name' => array(
            'description'   => 'The name of the company to lookup. (Requires --outputfile.)',
            'default'       => 0,
            'type'          => Pharse::PHARSE_STRING,
            'required'      => false,
            'short'      => 'ln',
        ),
        'lookup_url' => array(
            'description'   => 'The website URL for the company to lookup. (Requires --outputfile.)',
            'default'       => 0,
            'type'          => Pharse::PHARSE_STRING,
            'required'      => false,
            'short'      => 'lu',
        ),
        'inputfile' => array(
            'description'   => 'Full file path of the CSV file to use as the input data.',
            'default'       => '',
            'type'          => Pharse::PHARSE_STRING,
            'required'      => false,
            'short'      => 'i'
        ),
        'outputfile' => array(
            'description'   => '(optional) Output path or full file path and name for writing the results.',
            'default'       => '',
            'type'          => Pharse::PHARSE_STRING,
            'required'      => false,
            'short'      => 'o'
        ),
        'exclude_moz' => array(
            'description'   => 'Include moz.com data in the final result set.',
            'default'       => 0,
            'type'          => Pharse::PHARSE_INTEGER,
            'required'      => false,
            'short'      => 'em',
        ),
        'exclude_quantcast' => array(
            'description'   => 'Include quantcast.com uniq visitors data in the final result set.',
            'default'       => 0,
            'type'          => Pharse::PHARSE_INTEGER,
            'required'      => false,
            'short'      => 'eq',
        ),
        'exclude_crunchbase' => array(
            'description'   => 'Include TechCrunch\'s Crunchbase data in the final result set.',
            'default'       => 0,
            'type'          => Pharse::PHARSE_INTEGER,
            'required'      => false,
            'short'      => 'ec',
        ),

        'moz_access_id' => array(
            'description'   => 'Your Moz.com API access ID value.  If you do not have one, Moz data will be excluded.  Learn more about Moz.com access IDs at http://moz.com/products/api.',
            'default'       => 0,
            'type'          => Pharse::PHARSE_STRING,
            'required'      => false,
            'short'      => 'mozid',
        ),
        'moz_secret_key' => array(
            'description'   => 'Your Moz.com API secret key value.  If you do not have one, Moz data will be excluded.  Learn more about Moz.com access IDs at http://moz.com/products/api.',
            'default'       => 0,
            'type'          => Pharse::PHARSE_STRING,
            'required'      => false,
            'short'      => 'mozkey',
        ),

        'crunchbase_api_id' => array(
            'description'   => 'Your Crunchbase API key value.  If you do not have one, Crunchbase data will be excluded.  Learn more about Moz.com access IDs at http://developer.crunchbase.com.',
            'default'       => 0,
            'type'          => Pharse::PHARSE_STRING,
            'required'      => false,
            'short'      => 'cbid',
        ),
        'verbose' => array(
            'description'   => 'Show debug statements and other information.',
            'default'       => 0,
            'type'          => Pharse::PHARSE_INTEGER,
            'required'      => false,
            'short'      => 'v',
        ),
        'verbose_api_calls' => array(
            'description'   => 'Show API calls in verbose mode.',
            'default'       => 0,
            'type'          => Pharse::PHARSE_INTEGER,
            'required'      => false,
            'short'      => 'va',
        ),


    );

    # You may specify a program banner thusly:
    $banner = "Find and export basic website, Moz.com, Crunchbase and Quantcast data for any company name or URL.";
    Pharse::setBanner($banner);

    # After you've configured Pharse, run it like so:
    $GLOBALS['OPTS'] = Pharse::options($options);


    return $GLOBALS['OPTS'];
}

function parseFilePath($strFilePath, $fFileMustExist = false)
{
    $arrReturnFileDetails= array ('full_file_path' => '', 'directory' => '', 'file_name' => '', 'file_name_base' => '', 'file_extension' => '');


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
                $arrReturnFileDetails['directory'] = ".w";
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

//     __log__('parsed path ('. $strFilePath.') as'. ($fFileMustExist ? " " : " not ") . 'required into $arrReturnFileDetails { '.var_export($arrReturnFileDetails)." }".PHP_EOL, C__LOGLEVEL_DEBUG__);
    return $arrReturnFileDetails;

}

function getEmptyUserInputRecord()
{
    return array('header_keys'=>null, 'data_type' => null, 'data_rows'=>array());
}


?>
