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

require_once dirname(__FILE__) . '/config.php';
require_once dirname(__FILE__) . '/lib/pharse.php';
require_once dirname(__FILE__) . '/include/SimpleScooterCSVFileClass.php';
require_once dirname(__FILE__) . '/include/common.php';
require_once dirname(__FILE__) . '/include/fields_functions.php';
require_once dirname(__FILE__) . '/config_pashua_settings.php';
require_once dirname(__FILE__) . '/plugins/plugin-base.php';
require_once dirname(__FILE__) . '/plugins/plugin-basicfacts.php';
require_once dirname(__FILE__) . '/plugins/plugin-crunchbase.php';
require_once dirname(__FILE__) . '/plugins/plugin-moz.php';
require_once dirname(__FILE__) . '/plugins/plugin-quantcast.php';

/****************************************************************************************************************/
/****                                                                                                        ****/
/****         Global Constants			                                                                     ****/
/****                                                                                                        ****/
/****************************************************************************************************************/


__main__ ();

/****************************************************************************************************************/
/****                                                                                                        ****/
/****         Main Application Processing Function                                                           ****/
/****                                                                                                        ****/
/****************************************************************************************************************/

function __main__ ()
{
	ini_set('auto_detect_line_endings', true);
    date_default_timezone_set('America/Los_Angeles');

    __initLogger__();

    /****************************************************************************************************************/
    /****                                                                                                        ****/
    /****                                                                                                        ****/
    /****                                                                                                        ****/
    /****    Configure the app settings for this run.                                                            ****/
    /****                                                                                                        ****/
    /****                                                                                                        ****/
    /****                                                                                                        ****/
    /****************************************************************************************************************/




	/****************************************************************************************************************/
	/****                                                                                                        ****/
	/****    Initialize the app and setup the options based on the command line variables                        ****/
	/****                                                                                                        ****/
	/****************************************************************************************************************/

	// 
	// Gather and check that the command line arguments are valid
	//
	$GLOBALS['OPTS'] = __check_args__();


	if($GLOBALS['OPTS']['verbose_given']) {  $GLOBALS['VERBOSE'] = true; } else { $GLOBALS['VERBOSE'] = false; }
	if($GLOBALS['OPTS']['verbose_api_calls_given']) {  define(C__FSHOWVERBOSE_APICALL__, true); } else { define(C__FSHOWVERBOSE_APICALL__, false); }

	if($GLOBALS['VERBOSE'] == true) { echo 'Options set: '; var_dump($GLOBALS['OPTS']); }
	$arrExclusions = array();
    if($GLOBALS['OPTS']['exclude_quantcast_given'] ) {  $GLOBALS['OPTS']['exclude_quantcast'] = 1;  $arrExclusions[] = 'Quantcast'; } else { $GLOBALS['OPTS']['exclude_quantcast'] = 0; }
    if($GLOBALS['OPTS']['exclude_crunchbase_given'] ) {  $GLOBALS['OPTS']['exclude_crunchbase'] = 1;  $arrExclusions[] = 'Crunchbase'; }else { $GLOBALS['OPTS']['exclude_crunchbase'] = 0; }

    if(!$GLOBALS['OPTS']['moz_access_id_given'] )
    {
        $GLOBALS['OPTS']['moz_access_id'] = C_MOZ_API_ACCESS_ID;
        __debug__printLine("No Moz.com access ID given by the the user.  Defaulting to config value: (".C_MOZ_API_ACCESS_ID.")." , C__DISPLAY_ERROR__);
    }
    if(!$GLOBALS['OPTS']['moz_secret_key_given'] )
    {
        $GLOBALS['OPTS']['moz_secret_key'] = C_MOZ_API_ACCESS_ID;
        __debug__printLine("No Moz.com secret key given by the the user.  Defaulting to config value: (".C_MOZ_API_ACCESS_SECRETKEY.")." , C__DISPLAY_ERROR__);
    }

if($GLOBALS['OPTS']['exclude_moz_given'] || (strlen($GLOBALS['OPTS']['moz_access_id']) == 0 && $GLOBALS['OPTS']['moz_secret_key'] == 0)  )
    {
        if(!$GLOBALS['OPTS']['exclude_moz_given']) { __debug__printLine("Excluding Moz.com data: missing Moz API access ID and secret key.", C__DISPLAY_ERROR__); }
        $GLOBALS['OPTS']['exclude_moz'] = 1;
        $arrExclusions[] = 'Moz';
    }
    else
    {
        $GLOBALS['OPTS']['exclude_moz'] = 0;
    }


	__debug__printSectionHeader(C__APPNAME__, C__NAPPTOPLEVEL__, C__SECTION_BEGIN__);

    __debug__printSectionHeader("Getting settings.", C__NAPPFIRSTLEVEL__, C__SECTION_BEGIN__ );

    /****************************************************************************************************************/
    /****                                                                                                        ****/
    /****    Build a default Output FilePath as our starting value based on the current inputfile path           ****/
    /****                                                                                                        ****/
    /****************************************************************************************************************/

    //
    //  Build the new output file name
    //

    $fileInFullPath = $GLOBALS['OPTS']['inputfile'];

    // separate into elements by '/'
    $arrInputFilePathParts = explode("/", $fileInFullPath);

    // pop the last element (the file name + extension) into a string
    $strInputFileName = array_pop($arrInputFilePathParts);

    // put the rest of the path parts back together into a path string
    $baseInputFilePath = implode("/", $arrInputFilePathParts);

    // separate the file name by '.' to break the extension out
    $arrInputFileNameParts = explode(".", $strInputFileName);

    // pop off the extension
    $strInputExtension = array_pop($arrInputFileNameParts);

    // put the rest of the filename back together into a string.
    $strInputBase = implode(".", $arrInputFileNameParts);

    //
    // Default Output Name Format is <DATE>_output_<INPUTBASE>.csv
    //
    $baseDefaultOutputFileName = date("Ymd-Hm")."_output_".$strInputBase.".csv";



    //
    // Make sure we've got a good input directory path to use
    //

    //
    // handle the case where we only got the file name with no path; default to the current directory
    //
    if(strlen($baseInputFilePath) <= 1)
    {
           $baseInputFilePath = "./";
    }

    //
    //  Combine the output path & filename into the output file path
    //

    //
    //  Update the user options with the new values
    //
    $fileOutFullPath = $baseInputFilePath."/".$baseDefaultOutputFileName;

    if($GLOBALS['OPTS']['outputfile_given'] && file_exists($GLOBALS['OPTS']['outputfile'])) // it's a valid folder. but not a file
    {
       if(is_file($GLOBALS['OPTS']['outputfile']))
       {
           $fileOutFullPath = $GLOBALS['OPTS']['outputfile'];
       }
       else
       {
           $fileOutFullPath = $GLOBALS['OPTS']['outputfile']."/".$baseDefaultOutputFileName;
       }
    }
    $GLOBALS['OPTS']['outputfile'] = $fileOutFullPath;



    /****************************************************************************************************************/
    /****                                                                                                        ****/
    /****    Check if we need to display the settings UI to the user.  Show it, if so.                           ****/
    /****                                                                                                        ****/
    /****************************************************************************************************************/
    if(!$GLOBALS['OPTS']['suppressUI_given'] || !$GLOBALS['OPTS']['inputfile'] || strlen($GLOBALS['OPTS']['inputfile']) <=0 || !$GLOBALS['OPTS']['outputfile'] || strlen($GLOBALS['OPTS']['outputfile']) <= 0)
    {
        $classMacSettingsUI = new MacSettingsUIClass();
        $classMacSettingsUI->getOptionsFromUser();
    }

    if(count($arrExclusions) > 0) { __debug__printLine("Excluding data from: ".implode(',', $arrExclusions), C__DISPLAY_NORMAL__); }

    __debug__printSectionHeader("Getting settings.", C__NAPPFIRSTLEVEL__, C__SECTION_END__ );




    /****************************************************************************************************************/
    /****                                                                                                        ****/
    /****                                                                                                        ****/
    /****                                                                                                        ****/
    /****    App Setup is done.   Let's start processing the user's data.                                        ****/
    /****                                                                                                        ****/
    /****                                                                                                        ****/
    /****                                                                                                        ****/
    /****************************************************************************************************************/




	/****************************************************************************************************************/
	/****                                                                                                        ****/
	/****    Read the Input CSV File into an array                                                               ****/
	/****                                                                                                        ****/
	/****************************************************************************************************************/
	__debug__printSectionHeader("Read Input CSV File", C__NAPPFIRSTLEVEL__, C__SECTION_BEGIN__ );
    $classFileIn = new SimpleScooterCSVFileClass($fileInFullPath, 'r');

    $classFileIn->readAllRowsFromCSV($arrInputCSVData, true);

	__debug__printLine("Loaded ".count($arrInputCSVData)." records from input CSV file.", C__DISPLAY_NORMAL__);
	__debug__printSectionHeader("Read Input CSV File", C__NAPPFIRSTLEVEL__, C__SECTION_END__ );

    $classFileOut = new SimpleScooterCSVFileClass($fileOutFullPath, 'w+');





    /****************************************************************************************************************/
	/****                                                                                                        ****/
	/****    Get the basic facts for the loaded CSV input data                                                   ****/
	/****                                                                                                        ****/
	/****************************************************************************************************************/
	__debug__printSectionHeader("Getting basic facts", C__NAPPFIRSTLEVEL__, C__SECTION_BEGIN__ );

    $pluginBasicFacts = new BasicFactsPluginClass( $arrInputCSVData['data_type']);
    $arrAllRecordsProcessed = $pluginBasicFacts->addDataToMultipleRecords($arrInputCSVData['data_rows']);
	__debug__printSectionHeader("Getting basic facts", C__NAPPFIRSTLEVEL__, C__SECTION_END__ );


	/****************************************************************************************************************/
	/****                                                                                                        ****/
	/****   Initialize the data plugin classes                                                                   ****/
	/****                                                                                                        ****/
	/****************************************************************************************************************/

    $pluginQuantcast = new QuantcastPluginClass($GLOBALS['OPTS']['exclude_quantcast']);
	$pluginMoz = new MozPluginClass($GLOBALS['OPTS']['exclude_moz'], $arrAllRecordsProcessed);
	$pluginCrunchbase = new CrunchbasePluginClass($GLOBALS['OPTS']['exclude_crunchbase']);


	/****************************************************************************************************************/
	/****                                                                                                        ****/
	/****   Process the list of company / URL records to get the additional data for each one.                   ****/
	/****                                                                                                        ****/
	/****************************************************************************************************************/
	__debug__printSectionHeader("Collecting Data from Plugins", C__NAPPFIRSTLEVEL__, C__SECTION_BEGIN__ );


    $arrRecordCopyForKeys = null;

	$finalResults = array();
	print PHP_EOL;
	$ncurRecordIndex = 0;
	while($ncurRecordIndex < count($arrAllRecordsProcessed))
	{


        $pluginCrunchbase->addDataToRecord($arrAllRecordsProcessed[$ncurRecordIndex]);
		$pluginQuantcast->addDataToRecord($arrAllRecordsProcessed[$ncurRecordIndex]);
        $pluginMoz->addDataToRecord($arrAllRecordsProcessed[$ncurRecordIndex]);

        // Every X records, Update the output data file with what we've gotten so far.
        if($ncurRecordIndex % C__RECORD_CHUNK_SIZE__ == 0)  {         $classFileOut->writeArrayToCSVFile($arrAllRecordsProcessed );           }
        $ncurRecordIndex++;

		__debug__printLine("Added ".$arrAllRecordsProcessed[$ncurRecordIndex]['company_name']. " to final results list.", C__DISPLAY_ITEM_RESULT__);
	}

	__debug__printSectionHeader("Collecting Data from Plugins", C__NAPPFIRSTLEVEL__, C__SECTION_END__ );
    __debug__printLine("Total records processed: ".count($arrAllRecordsProcessed).".", C__DISPLAY_NORMAL__);

		


	/****************************************************************************************************************/
	/****                                                                                                        ****/
	/****   Output the results to a new CSV file.                                                                ****/
	/****                                                                                                        ****/
	/****************************************************************************************************************/
    $classFileOut->writeArrayToCSVFile($arrAllRecordsProcessed );



	__debug__printSectionHeader(C__APPNAME__, C__NAPPTOPLEVEL__, C__SECTION_END__ ); 
}


/****************************************************************************************************************/
/****                                                                                                        ****/
/****   Helper:  File Output                                                                                 ****/
/****                                                                                                        ****/
/****************************************************************************************************************/



?>