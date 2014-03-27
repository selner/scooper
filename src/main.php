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

require_once './lib/pharse.php';
require_once './include/helpers.php';
require_once './include/debug_functions.php';
require_once './include/fields_functions.php';
require_once 'config_pashua_settings.php';
require_once './plugins/plugin-base.php';
require_once './plugins/plugin-basicfacts.php';
require_once './plugins/plugin-crunchbase.php';
require_once './plugins/plugin-moz.php';
require_once './plugins/plugin-quantcast.php';

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
    if($GLOBALS['OPTS']['exclude_moz_given'] ) {  $GLOBALS['OPTS']['exclude_moz'] = 1;  $arrExclusions[] = 'Moz'; }else { $GLOBALS['OPTS']['exclude_moz'] = 0; }
    if($GLOBALS['OPTS']['exclude_quantcast_given'] ) {  $GLOBALS['OPTS']['exclude_quantcast'] = 1;  $arrExclusions[] = 'Quantcast'; } else { $GLOBALS['OPTS']['exclude_quantcast'] = 0; }
    if($GLOBALS['OPTS']['exclude_crunchbase_given'] ) {  $GLOBALS['OPTS']['exclude_crunchbase'] = 1;  $arrExclusions[] = 'Crunchbase'; }else { $GLOBALS['OPTS']['exclude_crunchbase'] = 0; }


	__debug__printSectionHeader(C__APPNAME__, C__NAPPTOPLEVEL__, C__SECTION_BEGIN__);

    __debug__printSectionHeader("Getting settings.", C__NAPPFIRSTLEVEL__, C__SECTION_BEGIN__ );
	$fileInFullPath = $GLOBALS['OPTS']['inputfile'];
    // use the folder passed in and use the input file name to generate a new output file name
    //
    $arrInputFilePathParts = explode("/", $fileInFullPath); // separate into elements by '/'
    $strInputFileName = array_pop($arrInputFilePathParts);  // pop the last element (the file name + extension) into a string
    $baseInputFilePath = implode("/", $arrInputFilePathParts); // put the rest of the path parts back together into a path string
    $arrInputFileNameParts = explode(".", $strInputFileName); // separate the file name by '.' to break the extension out
    $strInputExtension = array_pop($arrInputFileNameParts);  // pop off the extension
    $strInputBase = implode(".", $arrInputFileNameParts);    // put the rest of the filename back together into a string.

    $baseDefaultOutputFileName = $strInputBase."_output_".date("Ymd-Hm").".csv";
    $fileOutFullPath = $baseInputFilePath."/".$baseDefaultOutputFileName;

    if(file_exists($GLOBALS['OPTS']['outputfile'])) // it's a valid folder. but not a file
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

    if(!$GLOBALS['OPTS']['suppressUI_given'] || !$GLOBALS['OPTS']['inputfile'] || strlen($GLOBALS['OPTS']['inputfile']) <=0 || !$GLOBALS['OPTS']['outputfile'] || strlen($GLOBALS['OPTS']['outputfile']) <= 0)
    {
        $classMacSettingsUI = new MacSettingsUIClass();
        $ret = $classMacSettingsUI->getOptionsFromUser();
    }

    if(count($arrExclusions) > 0) { __debug__printLine("Excluding data from: ".implode(',', $arrExclusions), C__DISPLAY_NORMAL__); }

    __debug__printSectionHeader("Getting settings.", C__NAPPFIRSTLEVEL__, C__SECTION_END__ );


	/****************************************************************************************************************/
	/****                                                                                                        ****/
	/****    Read the Input CSV File into an array                                                               ****/
	/****                                                                                                        ****/
	/****************************************************************************************************************/
	__debug__printSectionHeader("Read Input CSV File", C__NAPPFIRSTLEVEL__, C__SECTION_BEGIN__ );
    $classFileIn = new FileBaseClass($fileInFullPath, 'r');

    $classFileIn->readAllRowsFromCSV($arrInputCSVData, true);

	__debug__printLine("Loaded ".count($arrInputCSVData)." records from input CSV file.", C__DISPLAY_NORMAL__);
	__debug__printSectionHeader("Read Input File", C__NAPPFIRSTLEVEL__, C__SECTION_END__ );

    $classFileOut = new FileBaseClass($fileOutFullPath, 'w+');

	
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
	
	/////////////////////////
	// BUGBUG:  These need to stay in the order Quant -> Moz -> CB for now.  If CB moves earlier, than
	//          CB records != company entity will have incorrectly shifted data.
	$pluginMoz = new MozPluginClass($GLOBALS['OPTS']['exclude_moz'], $arrAllRecordsProcessed);
	$pluginCrunchbase = new CrunchbasePluginClass($GLOBALS['OPTS']['exclude_crunchbase']);
	//          
	/////////////////////////

	/****************************************************************************************************************/
	/****                                                                                                        ****/
	/****   Process the list of company / URL records to get the additional data for each one.                   ****/
	/****                                                                                                        ****/
	/****************************************************************************************************************/
	__debug__printSectionHeader("Data Collection from CrunchBase, Quantcast, Moz", C__NAPPFIRSTLEVEL__, C__SECTION_BEGIN__ );


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

		__debug__printLine("Added ".$curRecord['company_name']. " to final results list.", C__DISPLAY_ITEM_RESULT__);
	}

	__debug__printLine("Collected data for ".count($arrAllRecordsProcessed)." records", C__DISPLAY_NORMAL__);
	__debug__printSectionHeader("Data Collection from CrunchBase, Quantcast, Moz", C__NAPPFIRSTLEVEL__, C__SECTION_END__ ); 

		
	__debug__printSectionHeader("Data Collection from Moz", C__NAPPFIRSTLEVEL__, C__SECTION_END__ );


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




/****************************************************************************************************************/
/****                                                                                                        ****/
/****         Helper Functions:  Validating Sites, making API Calls                                          ****/
/****                                                                                                        ****/
/****************************************************************************************************************/


?>
