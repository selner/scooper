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

/****************************************************************************************************************/
/****                                                                                                        ****/
/****         Common Declarations                                                                            ****/
/****                                                                                                        ****/
/****************************************************************************************************************/

$GLOBALS['VERBOSE'] = false;
$GLOBALS['OPTS'] = null;

const C__APPNAME__ = "Site Evaluator";
const C__APP_VERSION_MAJOR___ = "0";
const C__APP_VERSION_MINOR___ = ".11";
const C__RECORD_CHUNK_SIZE__ = 2;
const C__OUTPUT_HEADERKEY_LISTS_TO_FILE = 0;  // for debug only -- do not use in production mode.
const C__FSHOWVERBOSE_APICALL__ = 0;

const C__NAPPTOPLEVEL__ = 0;
const C__NAPPFIRSTLEVEL__ = 1;
const C__NAPPSECONDLEVEL__ = 2;
const C__SECTION_BEGIN__ = 1;
const C__SECTION_END__ = 2;
const C__DISPLAY_NORMAL__ = 100;
const C__DISPLAY_ITEM_START__ = 200;
const C__DISPLAY_ITEM_DETAIL__ = 300;
const C__DISPLAY_ITEM_RESULT__ = 350;

const C__DISPLAY_MOMENTARY_INTERUPPT__ = 400;
const C__DISPLAY_ERROR__ = 500;
const C__DISPLAY_RESULT__ = 600;
const C__DISPLAY_FUNCTION__= 700;
const C__API_RETURN_TYPE_OBJECT__ = 33;
const C__API_RETURN_TYPE_ARRAY__ = 44;


$GLOBALS['ALL_POSSIBLE_RECORD_KEYS'] =  array(
    'company_name' => 'N/A',
    'input_source_url' => 'N/A',
    'result_accuracy_warnings' => 'N/A',
    'effective_domain' => 'N/A',
    'actual_site_url' => 'N/A',
    'crunchbase_match_accuracy' => 'N/A',
    'company.name' => 'N/A',
    'company.category_code' => 'N/A',
    'company.description' => 'N/A',
    'company.permalink' => 'N/A',
    'company.crunchbase_url' => 'N/A',
    'company.homepage_url' => 'N/A',
    'company.namespace' => 'N/A',
    'company.overview' => 'N/A',
    'company.image' => 'N/A',
    'company.offices' => 'N/A',
    'company.computed_domain' => 'N/A',
    'product.name' => 'N/A',
    'product.permalink' => 'N/A',
    'product.crunchbase_url' => 'N/A',
    'product.homepage_url' => 'N/A',
    'product.namespace' => 'N/A',
    'product.overview' => 'N/A',
    'product.image' => 'N/A',
    'product.computed_domain' => 'N/A',
    'financial-organization.name' => 'N/A',
    'financial-organization.permalink' => 'N/A',
    'financial-organization.crunchbase_url' => 'N/A',
    'financial-organization.homepage_url' => 'N/A',
    'financial-organization.namespace' => 'N/A',
    'financial-organization.overview' => 'N/A',
    'financial-organization.image' => 'N/A',
    'financial-organization.offices' => 'N/A',
    'financial-organization.computed_domain' => 'N/A',
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
