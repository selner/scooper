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
define('__ROOT__', dirname(dirname(__FILE__)));
require_once(__ROOT__.'/include/options.php');

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
    __startApp__();
    __doRun__();

}
function __doRun__()
{


        /****************************************************************************************************************/
        /****                                                                                                        ****/
        /****                                                                                                        ****/
        /****                                                                                                        ****/
        /****    Configure the app settings for this run.                                                            ****/
        /****                                                                                                        ****/
        /****                                                                                                        ****/
        /****                                                                                                        ****/
        /****************************************************************************************************************/

        __debug__printSectionHeader("Getting settings.", C__NAPPFIRSTLEVEL__, C__SECTION_BEGIN__ );




        __log__('Input File Details = '.var_export($GLOBALS['input_file_details'], true), LOG_INFO);
        __log__('Output File Details = '.var_export($GLOBALS['output_file_details'], true), LOG_INFO);

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



        if($GLOBALS['OPTS']['crunchbase_api_url_given'])
        {
            __runCrunchbaseAPICall__();
        }
        else
        {
            __runCompanyLookups__();
        }

    __debug__printSectionHeader(C__APPNAME__, C__NAPPTOPLEVEL__, C__SECTION_END__ );
}

function __runCrunchbaseAPICall__()
{
    $pluginCrunchbase = new CrunchbasePluginClass($GLOBALS['OPTS']['exclude_crunchbase']);
    $strURL = $GLOBALS['OPTS']['crunchbase_api_url'];

    $pluginCrunchbase->writeCrunchbaseAPICallResultstoFile($strURL, $GLOBALS['output_file_details']);

}

function __runCompanyLookups__()
{

     try
     {
         $arrInputCSVData = array();
        if($GLOBALS['OPTS']['lookup_url_given'])
        {
            $arrInputCSVData['data_type'] = C__LOOKUP_DATATYPE_URL__;
            $arrInputCSVData['data_rows'][] = array($GLOBALS['OPTS']['lookup_url']);
            $GLOBALS['lookup_mode'] = C_LOOKUP_MODE_SINGLE;
        }
        else if($GLOBALS['OPTS']['lookup_name_given'])
        {
            $arrInputCSVData['data_type'] = C__LOOKUP_DATATYPE_NAME__;
            $arrInputCSVData['data_rows'][] = array($GLOBALS['OPTS']['lookup_name']);
            $GLOBALS['lookup_mode'] = C_LOOKUP_MODE_SINGLE;
        }
        else if($GLOBALS['OPTS']['inputfile_given'])
        {
            $GLOBALS['lookup_mode'] = C_LOOKUP_MODE_FILE;

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
            __log__("Unable to determine single or input file mode.  Cannot continue.", LOG_CRIT);

        }



        /****************************************************************************************************************/
        /****                                                                                                        ****/
        /****    Get the basic facts for the loaded CSV input data                                                   ****/
        /****                                                                                                        ****/
        /****************************************************************************************************************/
        __debug__printSectionHeader("Getting basic facts", C__NAPPFIRSTLEVEL__, C__SECTION_BEGIN__ );

        $pluginBasicFacts = new BasicFactsPluginClass( $arrInputCSVData['data_type'], $GLOBALS['output_file_details']['full_file_path']);
         $arrAllPluginColumnsForRecords = $pluginBasicFacts->getAllColumns();
         $arrAllRecordsProcessed = $pluginBasicFacts->addDataToMultipleRecords($arrInputCSVData['data_rows'], $GLOBALS['output_file_details']['full_file_path']);
        __debug__printSectionHeader("Getting basic facts", C__NAPPFIRSTLEVEL__, C__SECTION_END__ );


        /****************************************************************************************************************/
        /****                                                                                                        ****/
        /****   Initialize the data plugin classes                                                                   ****/
        /****                                                                                                        ****/
        /****************************************************************************************************************/
        $classFileOut = new SimpleScooterCSVFileClass($GLOBALS['output_file_details']['full_file_path'], 'w+');

        $pluginQuantcast = new QuantcastPluginClass($GLOBALS['OPTS']['exclude_quantcast']);
        $arrAllPluginColumnsForRecords = my_merge_add_new_keys($arrAllPluginColumnsForRecords, $pluginQuantcast->getAllColumns());
        $pluginCrunchbase = new CrunchbasePluginClass($GLOBALS['OPTS']['exclude_crunchbase']);
         $arrAllPluginColumnsForRecords  = my_merge_add_new_keys($arrAllPluginColumnsForRecords, $pluginCrunchbase->getAllColumns());

         $pluginMoz = new MozPluginClass($GLOBALS['OPTS']['exclude_moz'], $arrAllRecordsProcessed);
         $arrAllPluginColumnsForRecords = my_merge_add_new_keys($arrAllPluginColumnsForRecords, $pluginMoz->getAllColumns());

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
            $arrRecordToUpdate = my_merge_add_new_keys($arrAllPluginColumnsForRecords, $arrAllRecordsProcessed[$ncurRecordIndex]);

            $arrAllRecordsProcessed[$ncurRecordIndex] = my_merge_add_new_keys($arrAllPluginColumnsForRecords, $arrAllRecordsProcessed[$ncurRecordIndex]);

            $company = $arrAllRecordsProcessed[$ncurRecordIndex]['company_name'];
            $pluginQuantcast->addDataToRecord($arrAllRecordsProcessed[$ncurRecordIndex]);

            $pluginCrunchbase->addDataToRecord($arrAllRecordsProcessed[$ncurRecordIndex]);

            $pluginMoz->addDataToRecord($arrAllRecordsProcessed[$ncurRecordIndex]);


            // Every X records, Update the output data file with what we've gotten so far.
            if($ncurRecordIndex % C__RECORD_CHUNK_SIZE__ == 0)  {         $classFileOut->writeArrayToCSVFile($arrAllRecordsProcessed );           }
            $ncurRecordIndex++;

            __debug__printLine("Added ".$company . " to final results list.".PHP_EOL, C__DISPLAY_ITEM_RESULT__);
        }

        __debug__printSectionHeader("Collecting Data from Plugins", C__NAPPFIRSTLEVEL__, C__SECTION_END__ );
        __debug__printLine("Total records processed: ".count($arrAllRecordsProcessed).".", C__DISPLAY_NORMAL__);




        /****************************************************************************************************************/
        /****                                                                                                        ****/
        /****   Output the results to a new CSV file.                                                                ****/
        /****                                                                                                        ****/
        /****************************************************************************************************************/
        $classFileOut->writeArrayToCSVFile($arrAllRecordsProcessed );



    }
    catch ( ErrorException $e )
    {

          exit("Error: ".PHP_EOL. $e->getMessage().PHP_EOL."Exiting.\r\n" );
    }

}



