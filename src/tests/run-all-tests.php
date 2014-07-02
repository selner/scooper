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
require_once(__ROOT__.'/tests/tests-command-line.php');
require_once(__ROOT__.'/tests/tests-plugin-crunchbase.php');

runAllTests();


function runAllTests()
{

    initTests();

    testCrunchbase_getCompanyByPermalink('redfin');

    $GLOBALS['logger']->logLine("Starting runTests_CrunchbasePlugin...", \Scooper\C__DISPLAY_SECTION_START__);

    runTests_CrunchbasePlugin();

    $GLOBALS['logger']->logLine("Starting runTests_CommandLine...", \Scooper\C__DISPLAY_SECTION_START__);
    runTests_CommandLine();

}


