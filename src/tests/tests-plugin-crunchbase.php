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
require_once(__ROOT__.'/tests/tests-base.php');








function runTests_CrunchbasePlugin()
{
    $ret = null;

   $ret = testCrunchbase_APIExport_MultiPage();

    $ret = testCrunchbase_getCompanyByPermalink('redfin');

    $ret = testCrunchbase_getCompanyByPermalink('facebook');


}


function testCrunchbase_APIExport_MultiPage()
{
    $detailsOutFile = getTestOutputFileDetails();

    $pluginCrunchbase = new CrunchbasePluginClass(false);
    return $pluginCrunchbase->writeCrunchbaseAPICallResultstoFile("http://api.crunchbase.com/v/2/organizations?order=updated_at%20desc", $detailsOutFile, 3 );
}


function testCrunchbase_getCompanyByPermalink($str)
{
    $detailsOutFile = getTestOutputFileDetails();


    $pluginCrunchbase = new CrunchbasePluginClass(false);
//    $arrRecords = array('company_name' => $str);
//    $pluginCrunchbase->addDataToRecord($arrRecords, true);
    $arrRecords = $pluginCrunchbase->writeCrunchbaseOrganizationToFile($str, $detailsOutFile);

    return $arrRecords;
}

