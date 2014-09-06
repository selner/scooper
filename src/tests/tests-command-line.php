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
require_once(__ROOT__.'/main/scooper.php');





function runTests_CommandLine()
{
    initTests();

    $ret = null;

    $ret = testCrunchbaseCommandLineOption('lookup_name', 'microsoft.com');
    $ret = testCrunchbaseCommandLineOption('lookup_url', 'Microsoft');
    $ret = testCrunchbaseCommandLineOption('lookup_name', 'redfin.com');
}




function set_PharseOptionValue($strOptName, $value)
{
    $strOptGiven = $strOptName."_given";
    $GLOBALS['OPTS'][$strOptGiven] = true;
    $GLOBALS['OPTS'][$strOptName] = $value;
    if(isset($GLOBALS['logger'])) $GLOBALS['logger']->logLine("'".$strOptName ."'"." set to [".$GLOBALS['OPTS'][$strOptName] ."].", \Scooper\C__DISPLAY_ITEM_DETAIL__);

    return (isset($GLOBALS['OPTS'][$strOptName]));

}

