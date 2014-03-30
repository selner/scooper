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

// __main__ ();

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

    //
    // Gather and check that the command line arguments are valid
    //
    __debug__printSectionHeader(C__APPNAME__, C__NAPPTOPLEVEL__, C__SECTION_BEGIN__);
    __debug__printSectionHeader("Getting settings.", C__NAPPFIRSTLEVEL__, C__SECTION_BEGIN__ );

    $strArgErrs = __check_args__();
    if(strlen($strArgErrs) > 0) __log__($strArgErrs, C__LOGLEVEL_WARN__);







    __log__('Input File Details = '.var_export($GLOBALS['input_file_details']), C__LOGLEVEL_INFO__);
    __log__('Input File Details = '.var_export($GLOBALS['output_file_details']), C__LOGLEVEL_INFO__);

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


    $arrInputCSVData = array();

    if($GLOBALS['lookup_mode'] == C_LOOKUP_MODE_SINGLE)
    {

        if($GLOBALS['OPTS']['lookup_url_given'])
        {
            $arrInputCSVData['data_type'] = C__LOOKUP_DATATYPE_URL__;
            $arrInputCSVData['data_rows'][] = array($GLOBALS['OPTS']['lookup_url']);

        }
        else
        {
            $arrInputCSVData['data_type'] = C__LOOKUP_DATATYPE_NAME__;
            $arrInputCSVData['data_rows'][] = array($GLOBALS['OPTS']['lookup_name']);
        }
    }
    else if($GLOBALS['lookup_mode'] == C_LOOKUP_MODE_FILE)
    {
        /****************************************************************************************************************/
        /****                                                                                                        ****/
        /****    Read the Input CSV File into an array                                                               ****/
        /****                                                                                                        ****/
        /****************************************************************************************************************/
        __debug__printSectionHeader("Read Input CSV File", C__NAPPFIRSTLEVEL__, C__SECTION_BEGIN__ );
        $classFileIn = new SimpleScooterCSVFileClass($GLOBALS['input_file_details']['full_file_path'], 'r');

        $classFileIn->readAllRowsFromCSV($arrInputCSVData, true);

        __debug__printLine("Loaded ".count($arrInputCSVData)." records from input CSV file.", C__DISPLAY_NORMAL__);
        __debug__printSectionHeader("Read Input CSV File", C__NAPPFIRSTLEVEL__, C__SECTION_END__ );

    }
    else
    {
        __log__("Unable to determine single or input file mode.  Cannot continue.", C__LOGLEVEL_FATAL__);

    }



    $classFileOut = new SimpleScooterCSVFileClass($GLOBALS['output_file_details']['full_file_path'], 'w+');





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

        $pluginQuantcast->addDataToRecord($arrAllRecordsProcessed[$ncurRecordIndex]);

        $pluginCrunchbase->addDataToRecord($arrAllRecordsProcessed[$ncurRecordIndex]);

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