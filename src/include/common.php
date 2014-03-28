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

if ( file_exists ( dirname(__FILE__) . '/../config_debug.php') )
{
    require_once dirname(__FILE__) . '/../config_debug.php';
}
else
{
    require_once dirname(__FILE__) . '/../config.php';
}
require_once dirname(__FILE__) .'/SimpleScooterCSVFileClass.php';
require_once dirname(__FILE__) . '/debug_functions.php';

$GLOBALS['VERBOSE'] = false;
$GLOBALS['OPTS'] = null;
const C__APPNAME__ = "Scooper";
const C__APP_VERSION_MAJOR___ = "0";
const C__APP_VERSION_MINOR___ = ".11";
const C__RECORD_CHUNK_SIZE__ = 2;
const C__FSHOWVERBOSE_APICALL__ = 0;

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
    require_once dirname(__FILE__) . '/../lib/KLogger/src/KLogger.php';
    define(C_USE_KLOGGER, 1);
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
    'company_name' => 'N/A',
    'input_source_url' => 'N/A',
    'result_accuracy_warnings' => 'N/A',
    'effective_domain' => 'N/A',
    'actual_site_url' => 'N/A',
	 'Quantcast.Monthly Uniques' => 'N/A',
    'crunchbase_match_accuracy' => 'N/A',
    'cb.name' => 'N/A',
    'cb.namespace' => 'N/A',
    'cb.description' => 'N/A',
    'cb.overview' => 'N/A',
    'cb.category_code' => 'N/A',
    'cb.permalink' => 'N/A',
    'cb.crunchbase_url' => 'N/A',
    'cb.homepage_url' => 'N/A',
    'cb.image' => 'N/A',
    'cb.offices' => 'N/A',
    'person.first_name' => 'N/A',
    'person.last_name' => 'N/A',
    'feid' => 'N/A',
    'fid' => 'N/A',
    'fmrp' => 'N/A',
    'fmrr' => 'N/A',
    'pda' => 'N/A',
    'peid' => 'N/A',
    'pid' => 'N/A',
    'ueid' => 'N/A',
    'ufq' => 'N/A',
    'uid' => 'N/A',
    'uifq' => 'N/A',
    'uipl' => 'N/A',
    'ujid' => 'N/A',
    'umrp' => 'N/A',
    'umrr' => 'N/A',
    'upl' => 'N/A',
    'ut' => 'N/A',
    'uu' => 'N/A',
);


/****************************************************************************************************************/
/****                                                                                                        ****/
/****         Helper Functions:  Information and Error Logging                                               ****/
/****                                                                                                        ****/
/****************************************************************************************************************/

function __initLogger__($strBaseFileName = null, $strOutputDirPath = null)
{
    if($strBaseFileName == null) { $strBaseFileName = "_".C__APPNAME__."_".date("Ymd_Hm"); }
    if($strOutputDirPath == null) {            $strOutputDirPath = "."; }
    $fileLogFullPath = $strOutputDirPath."/".$strBaseFileName."__.log";

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


function __check_args__()
{

    # specify some options
    $options = array(
        'suppressUI' => array(
            'description'   => 'Show user interface.',
            'default'       => 0,
            'type'          => Pharse::PHARSE_INTEGER,
            'required'      => false,
            'short'      => 'q',
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
        'inputfile' => array(
            'description'   => 'Full file path of the CSV file to use as the input data.',
            'default'       => '',
            'type'          => Pharse::PHARSE_STRING,
            'required'      => true,
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
    );

    # You may specify a program banner thusly:
    $banner = "Import a list of company names or URLs from a CSV and export Moz, Crunchbase and Quantcast data about each one.";
    Pharse::setBanner($banner);

    # After you've configured Pharse, run it like so:
    $GLOBALS['OPTS'] = Pharse::options($options);

    return $GLOBALS['OPTS'];
}


?>
