<?php
/**
 * Created by PhpStorm.
 * User: bryan
 * Date: 6/13/14
 * Time: 12:21 AM
 */
define('__ROOT__', dirname(dirname(__FILE__)));
require_once(__ROOT__.'/include/options.php');


function initTests()
{
    $testOutFile = getTestOutputFileDetails();
    $testIniFile = getTestIniFile();

    __startApp__();

    if(!isset($GLOBALS['tests_started']))
    {
        $GLOBALS['tests_started'] = true;
        __check_args__();
    }
    else
    {
        resetPharseOptionsToDefaults();
        __check_args__();
    }


    if(!isset($GLOBALS['config_file_details']))
    {
        setCommandLineValue('use_config_ini', $testIniFile['full_file_path'], 'config_file_details', true);
    }
    if(!isset($GLOBALS['output_file_details']))
    {
        setCommandLineValue('outputfile', $testOutFile['full_file_path'], 'output_file_details', true);
    }
    setCommandLineValue('verbose', true);

    __check_args__();

}
function logTest_Start($strLog) { logTest($strLog, false); }
function logTest_End($strLog) { logTest($strLog, true); }

function logTest($strLog = "<unknown>", $fLogEnd = false)
{
    $GLOBALS['logger']->logLine("Test " . ($fLogEnd == false ? "started:  " : "completed: ") . $strLog, \Scooper\C__DISPLAY_SUMMARY__);
}

function runTest($strToLog = "<unknown>")
{
    logTest_Start($strToLog);
    __doRun__();

    logTest_End($strToLog);


}

function getTestOutputFileDetails()
{
    if(isset($GLOBALS['output_file_details'])) { return $GLOBALS['output_file_details'];  }

    $strDir = sys_get_temp_dir();
    return \Scooper\parseFilePath($strDir.\Scooper\getDefaultFileName('Scooper_Test', '', 'csv'));

}

function getTestIniFile()
{
    return \Scooper\parseFilePath("./test_data/tests_config.ini");
}

function resetPharseOptionsToDefaults()
{
    foreach($GLOBALS['OPTS'] as $option)
    {
        $option['value'] = $option['default'];
        $GLOBALS['OPTS'][$option['name']] = $option;
    }
}

function setCommandLineValue($strOption, $strValue, $setAsGlobalFileDetailsName = null, $fFileNameRequired = false)
{
    //
    // set the command line value
    //
    set_PharseOptionValue($strOption, $strValue);

    //
    // If it's a $GLOBALS file detail, also set that value
    //
    if($setAsGlobalFileDetailsName != null && strlen($setAsGlobalFileDetailsName) > 0)
    {
        $GLOBALS[$setAsGlobalFileDetailsName] = \Scooper\get_FileDetails_fromPharseOption($strOption, $fFileNameRequired);
    }
}
