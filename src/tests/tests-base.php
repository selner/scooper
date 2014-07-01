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
    __startApp__();

}


function getTestOutputFileDetails()
{
    $class = new Scooper\ScooperFileInfo();

    $strDir = sys_get_temp_dir();
    return $class ->parseFilePath($strDir.\Scooper\getDefaultFileName('testCrunchbase_', '', '.csv'));

}


function testSetCommandLineOption($strKey, $strValue = null)
{
    $GLOBALS['OPTS'][$strKey."_given"] = ($strValue != null);
    $GLOBALS['OPTS'][$strKey] = $strValue;
}