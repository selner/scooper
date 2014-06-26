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
require_once(__ROOT__.'/main/scooper.php');





function runTests_CommandLine()
{
    initTests();

    $ret = null;

    $ret = testCommand_Lookup('lookup_name', 'microsoft.com');
    $ret = testCommand_Lookup('lookup_url', 'Micrsoft');
    $ret = testCommand_Lookup('lookup_name', 'microsoft.com');
    $ret = testCommand_Lookup('inputfile', $fileDetails);

    $ret = testCommand_Lookup('crunchbase_api_url', 'http://api.crunchbase.com/v/2/organization/techcrunch');

    $ret = testCommand_Lookup('crunchbase_api_url', 'http://www.crunchbase.com/person/bryan-selner');

    $ret = testCommand_Lookup('crunchbase_api_url', 'http://api.crunchbase.com/v/2/people?order=updated_at%20desc');


}


function testCommand_Lookup($strOption, $strValue)
{

    $detailsOutFile = getTestOutputFileDetails();

    testSetCommandLineOption($strOption, $strValue);
    testSetCommandLineOption('outputfile', $detailsOutFile['full_file_path']);
    initTests();

    __doRun__();

}

