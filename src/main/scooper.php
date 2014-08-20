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
use \Scooper\ScooperSimpleCSV;

require_once(dirname(dirname(__FILE__)).'/include/options.php');

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

    __check_args__();


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


    $GLOBALS['logger']->logLine('Input File Details = '.var_export($GLOBALS['input_file_details'], true), \Scooper\C__DISPLAY_NORMAL__);
    $GLOBALS['logger']->logLine('Output File Details = '.var_export($GLOBALS['output_file_details'], true), \Scooper\C__DISPLAY_NORMAL__);



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
            __runCrunchbaseAPICall__($GLOBALS['OPTS']['crunchbase_api_url']);
        }
        else
        {
            __runCompanyLookups__();
        }

    $GLOBALS['logger']->logSectionHeader(C__APPNAME__, \Scooper\C__NAPPTOPLEVEL__, \Scooper\C__SECTION_END__ );
}

function __runCrunchbaseAPICall__($strURL)
{
    $pluginCrunchbase = new CrunchbasePluginClass($GLOBALS['OPTS']['exclude_crunchbase']);
    $arrData = $pluginCrunchbase->fetchCrunchbaseDataFromAPI($strURL);
    $pluginCrunchbase->writeDataToFile($arrData, $GLOBALS['output_file_details']);

}



function __runCompanyLookups__()
{

    $detailsOut = $GLOBALS['output_file_details'];

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
            /****************************************************************************************************************/
            /****                                                                                                        ****/
            /****    Read the Input CSV File into an array                                                               ****/
            /****                                                                                                        ****/
            /****************************************************************************************************************/

            $GLOBALS['lookup_mode'] = C_LOOKUP_MODE_FILE;
            $GLOBALS['logger']->logSectionHeader("Read Input CSV File", \Scooper\C__NAPPFIRSTLEVEL__, \Scooper\C__SECTION_BEGIN__ );
            $classFileIn = new \Scooper\ScooperSimpleCSV($GLOBALS['input_file_details']['full_file_path'], 'r');

            $arrInputCSVData = $classFileIn->readAllRecords(true);

            $GLOBALS['logger']->logLine("Loaded ".count($arrInputCSVData)." records from input CSV file.", \Scooper\C__DISPLAY_NORMAL__);
            $GLOBALS['logger']->logSectionHeader("Read Input CSV File", \Scooper\C__NAPPFIRSTLEVEL__, \Scooper\C__SECTION_END__ );

            if(isset($arrInputCSVData['header_keys']) && count($arrInputCSVData['header_keys']) >= 1 )
            {
                $strHeaders = join("|", $arrInputCSVData['header_keys']);

                $nCount = \Scooper\substr_count_array($strHeaders, array("name", "company"));

                if($nCount > 0)
                {
                    $arrInputCSVData['data_type'] = C__LOOKUP_DATATYPE_NAME__;
                }
                else
                {
                    $nCount = \Scooper\substr_count_array($strHeaders, array("input_source_url", "company_url", "website", "homepage", "root_domain"));
                    if($nCount > 0)
                    {
                        $arrInputCSVData['data_type'] = C__LOOKUP_DATATYPE_URL__;
                    }
                }
            }

        }
        else
        {
            $GLOBALS['logger']->logLine("Unable to determine single or input file mode.  Cannot continue.", \Scooper\C__DISPLAY_ERROR__);

        }



        /****************************************************************************************************************/
        /****                                                                                                        ****/
        /****    Get the basic facts for the loaded input data                                                       ****/
        /****                                                                                                        ****/
        /****************************************************************************************************************/
        $GLOBALS['logger']->logSectionHeader("Getting basic facts", \Scooper\C__NAPPFIRSTLEVEL__, \Scooper\C__SECTION_BEGIN__ );

        $pluginBasicFacts = new BasicFactsPluginClass($arrInputCSVData['data_type']);
        $arrAllPluginColumnsForRecords = $pluginBasicFacts->getAllColumns();
        $arrAllRecordsProcessed = $pluginBasicFacts->addDataToMultipleRecords($arrInputCSVData['data_rows'], $detailsOut['full_file_path']);
        $GLOBALS['logger']->logSectionHeader("Getting basic facts", \Scooper\C__NAPPFIRSTLEVEL__, \Scooper\C__SECTION_END__ );


        /****************************************************************************************************************/
        /****                                                                                                        ****/
        /****   Initialize the data plugin classes                                                                   ****/
        /****                                                                                                        ****/
        /****************************************************************************************************************/
        $classFileOut = new ScooperSimpleCSV($detailsOut['full_file_path'], 'w+');

        $pluginQuantcast = new QuantcastPluginClass($GLOBALS['OPTS']['exclude_quantcast']);
        $arrAllPluginColumnsForRecords = \Scooper\my_merge_add_new_keys($arrAllPluginColumnsForRecords, $pluginQuantcast->getAllColumns());

        $pluginAngel = new PluginAngelList($GLOBALS['OPTS']['exclude_angellist']);
        $arrAllPluginColumnsForRecords = \Scooper\my_merge_add_new_keys($arrAllPluginColumnsForRecords, $pluginAngel->getAllColumns());

        $pluginCrunchbase = new CrunchbasePluginClass($GLOBALS['OPTS']['exclude_crunchbase']);
        $arrAllPluginColumnsForRecords  = \Scooper\my_merge_add_new_keys($arrAllPluginColumnsForRecords, $pluginCrunchbase->getAllColumns());

        $pluginMoz = new MozPluginClass($GLOBALS['OPTS']['exclude_moz'], $arrAllRecordsProcessed);
        $arrAllPluginColumnsForRecords = \Scooper\my_merge_add_new_keys($arrAllPluginColumnsForRecords, $pluginMoz->getAllColumns());

        /****************************************************************************************************************/
        /****                                                                                                        ****/
        /****   Process the list of company / URL records to get the additional data for each one.                   ****/
        /****                                                                                                        ****/
        /****************************************************************************************************************/
        $GLOBALS['logger']->logSectionHeader("Collecting Data from Plugins", \Scooper\C__NAPPFIRSTLEVEL__, \Scooper\C__SECTION_BEGIN__ );


        $arrRecordCopyForKeys = null;

        $finalResults = array();
        print PHP_EOL;
        $ncurRecordIndex = 0;
        while($ncurRecordIndex < count($arrAllRecordsProcessed))
        {
            $arrRecordToUpdate = \Scooper\my_merge_add_new_keys($arrAllPluginColumnsForRecords, $arrAllRecordsProcessed[$ncurRecordIndex]);

            $arrAllRecordsProcessed[$ncurRecordIndex] = \Scooper\my_merge_add_new_keys($arrAllPluginColumnsForRecords, $arrAllRecordsProcessed[$ncurRecordIndex]);

            $company = $arrAllRecordsProcessed[$ncurRecordIndex]['company_name'];
            $pluginQuantcast->addDataToRecord($arrAllRecordsProcessed[$ncurRecordIndex]);

            $pluginCrunchbase->addDataToRecord($arrAllRecordsProcessed[$ncurRecordIndex]);

            $pluginAngel->addDataToRecord($arrAllRecordsProcessed[$ncurRecordIndex]);

            $pluginMoz->addDataToRecord($arrAllRecordsProcessed[$ncurRecordIndex]);


            // Every X records, Update the output data file with what we've gotten so far.
            if($ncurRecordIndex % C__RECORD_CHUNK_SIZE__ == 0)  {         $classFileOut->writeArrayToCSVFile($arrAllRecordsProcessed );           }
            $ncurRecordIndex++;

            $GLOBALS['logger']->logLine("Added ".$company . " to final results list.".PHP_EOL, \Scooper\C__DISPLAY_ITEM_RESULT__);
        }

        $GLOBALS['logger']->logSectionHeader("Collecting Data from Plugins", \Scooper\C__NAPPFIRSTLEVEL__, \Scooper\C__SECTION_END__ );
        $GLOBALS['logger']->logLine("Total records processed: ".count($arrAllRecordsProcessed).".", \Scooper\C__DISPLAY_NORMAL__);




        /****************************************************************************************************************/
        /****                                                                                                        ****/
        /****   Output the results to a new CSV file.                                                                ****/
        /****                                                                                                        ****/
        /****************************************************************************************************************/

        $GLOBALS['logger']->logLine("Writing results to ".$detailsOut['full_file_path'], \Scooper\C__DISPLAY_NORMAL__);
         $classFileOut->writeArrayToCSVFile( $arrAllRecordsProcessed );



    }
    catch ( ErrorException $e )
    {

          exit("Error: ".PHP_EOL. $e->getMessage().PHP_EOL."Exiting.\r\n" );
    }

}



