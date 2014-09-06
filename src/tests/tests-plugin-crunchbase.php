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

if (!strlen(__ROOT__) > 0) { define('__ROOT__', dirname(dirname(__FILE__))); }

require_once(__ROOT__.'/tests/tests-base.php');



function testCrunchbaseCommandLineOption($option, $value)
{
    initTests();

    $ret = null;
    setCommandLineValue($option, $value);
    $strLog = $option . " = ". $value;

    runTest($strLog);
}




function runTests_CrunchbasePlugin()
{
    $ret = null;
    testCrunchbaseCommandLineOption('crunchbase_api_url', 'http://api.crunchbase.com/v/2/organization/techcrunch');

    testCrunchbaseCommandLineOption('crunchbase_api_url', 'http://api.crunchbase.com/v/2/person/bryan-selner');

    testCrunchbaseCommandLineOption('crunchbase_api_url', 'http://api.crunchbase.com/v/2/people?order=updated_at%20desc');

    testCrunchbaseCommandLineOption('crunchbase_api_url', 'http://api.crunchbase.com/v/2/acquisition/72a5b1fe11ccb8c1c2ea590bb3ae0e18');

    testCrunchbaseCommandLineOption('crunchbase_api_url', 'http://api.crunchbase.com/v/2/organizations?order=updated_at%20desc');

    testCrunchbase_getOrgDataFromCSVFile('./test_data/CrunchbaseOrganizations_testdata.csv');

    testCrunchbase_APIExport_MultiPage();

    testCrunchbase_getCompanyByPermalink('redfin');

    testCrunchbase_getCompanyByPermalink('facebook');


}




function testCrunchbase_APIExport_MultiPage()
{
    $strLog = "testCrunchbase_APIExport_MultiPage()";
    logTest_Start($strLog);

    initTests();
    $detailsOutFile = getTestOutputFileDetails();

    $pluginCrunchbase = new CrunchbasePluginClass(false);
    $arrData = $pluginCrunchbase->fetchCrunchbaseDataFromAPI("http://api.crunchbase.com/v/2/organizations?order=updated_at%20desc", true);
    $pluginCrunchbase->writeDataToFile($arrData, $detailsOutFile);

    logTest_End($strLog);

}


function testCrunchbase_getCompanyByPermalink($str)
{
    $strLog = "testCrunchbase_getCompanyByPermalink(" . $str . ")";
    logTest_Start($strLog);

    $detailsOutFile = getTestOutputFileDetails();

    $pluginCrunchbase = new CrunchbasePluginClass(false);
    $arrData = $pluginCrunchbase->getCompanyData($str);
    $pluginCrunchbase->writeDataToFile($arrData, $detailsOutFile);

    logTest_End($strLog);



}


function testCrunchbase_getOrgDataFromCSVFile($strFilePath)
{
    $strLog = "testCrunchbase_getOrgDataFromCSVFile(" . $strFilePath . ")";
    logTest_Start($strLog);
    initTests();

    $detailsFileIn = \Scooper\parseFilePath($strFilePath);
    $pluginCrunchbase = new CrunchbasePluginClass(false);

    $arrResults = $pluginCrunchbase->readIDsFromCSVFile($detailsFileIn['full_file_path'], 'path');
    logTest_End($strLog);


}
