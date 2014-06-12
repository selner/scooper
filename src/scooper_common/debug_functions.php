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
	

/****************************************************************************************************************/
/****                                                                                                        ****/
/****         Helper Functions:  Debug Functions                                                             ****/
/****                                                                                                        ****/
/****************************************************************************************************************/
define('__ROOT__', dirname(dirname(__FILE__)));
require_once(__ROOT__.'/scooper_common/common.php');

const C__DEBUG_MODE__ = false;


const C__NAPPTOPLEVEL__ = 0;
const C__NAPPFIRSTLEVEL__ = 1;
const C__NAPPSECONDLEVEL__ = 2;
const C__SECTION_BEGIN__ = 1;
const C__SECTION_END__ = 2;
const C__DISPLAY_NORMAL__ = 100;
const C__DISPLAY_SECTION_START__ = 250;
const C__DISPLAY_ITEM_START__ = 200;
const C__DISPLAY_ITEM_DETAIL__ = 300;
const C__DISPLAY_ITEM_RESULT__ = 350;

const C__DISPLAY_MOMENTARY_INTERUPPT__ = 400;
const C__DISPLAY_WARNING__ = 405;
const C__DISPLAY_ERROR__ = 500;
const C__DISPLAY_RESULT__ = 600;
const C__DISPLAY_FUNCTION__= 700;
const C__DISPLAY_SUMMARY__ = 750;


function __debug__var_dump_exit__($var, $desc="__debug__var_dump_exit__")
{

    var_dump($desc, $var);
    throw new ErrorException($desc, 1);

}


function __debug__DumpArrayKeyValues($arrToDump, $intro = "")
{
		print '*-*-*-*-*-*-*-*-*-*-'.PHP_EOL;
		$arrKeys = array_keys($arrToDump);
		if($intro != "") { print $intro.':'.PHP_EOL; }
		foreach ($arrKeys as $fieldKey)
		{
			print $fieldKey.PHP_EOL;
		} 
		print '*-*-*-*-*-*-*-*-*-*-'.PHP_EOL;
}


function __debug__printLine($strToPrint, $varDisplayStyle, $fDebuggingOnly = false)
{
	if($fDebuggingOnly != true || C__DEBUG_MODE__ == true)
	{
		$strLineEnd = '';
		$logLevel = null;
		switch ($varDisplayStyle)
		{
            case  C__DISPLAY_FUNCTION__:
                $strLineBeginning = '<<<<<<<< function "';
                $strLineEnd = '" called >>>>>>> ';
                $logLevel = C__LOGLEVEL_DEBUG__;
                break;

            case C__DISPLAY_WARNING__:
                $strLineBeginning = PHP_EOL.PHP_EOL.'^^^^^^^^^^ "';
                $strLineEnd = '" ^^^^^^^^^^ '.PHP_EOL;
                $logLevel = C__LOGLEVEL_WARN__;
                break;

            case C__DISPLAY_SUMMARY__:

                $strLineBeginning = PHP_EOL."************************************************************************************".PHP_EOL. PHP_EOL;
                $strLineEnd = PHP_EOL.PHP_EOL."************************************************************************************".PHP_EOL;
                $logLevel = C__LOGLEVEL_INFO__;
                break;

            case C__DISPLAY_SECTION_START__:
                $strLineBeginning = PHP_EOL."####################################################################################".PHP_EOL. PHP_EOL;
                $strLineEnd = PHP_EOL.PHP_EOL."####################################################################################".PHP_EOL;
                $logLevel = C__LOGLEVEL_INFO__;
                break;


            case C__DISPLAY_RESULT__:
					$strLineBeginning = '==> ';
					$logLevel = C__LOGLEVEL_INFO__;
					break;

			case C__DISPLAY_ERROR__: 
				$strLineBeginning = '!!!!! ';
				$logLevel = C__LOGLEVEL_ERROR__;
				break;
				
			case C__DISPLAY_ITEM_START__: 
				$strLineBeginning = '---> ';
				$logLevel = C__LOGLEVEL_INFO__;
				break;
				
			case C__DISPLAY_ITEM_DETAIL__: 
				$strLineBeginning = '     ';
				$logLevel = C__LOGLEVEL_INFO__;
				break;
				
			case C__DISPLAY_ITEM_RESULT__: 
				$strLineBeginning = '======> ';
				$logLevel = C__LOGLEVEL_INFO__;
				break;
					
			case C__DISPLAY_MOMENTARY_INTERUPPT__: 
				$strLineBeginning = '......';
				$logLevel = C__LOGLEVEL_WARN__;
				break;
						
			case C__DISPLAY_NORMAL__: 
				$strLineBeginning = '';
				$logLevel = C__LOGLEVEL_INFO__;
				break;
		
			default:
				throw new ErrorException('Invalid type value passed to __debug__printLine.  Value = '.$varDisplayStyle. ".");
				break;
		}


        __log__($strLineBeginning . $strToPrint . $strLineEnd, $logLevel);

	}
}


function __debug__printSectionHeader($headerText, $nSectionLevel, $nType) 
{
	
	$strPaddingBefore = "";
	$strPaddingAfter = "";

	//
	// Set the section header box style and intro/outro padding based on it's level
	// and whether its a section beginning header or an section ending.
	//
	switch ($nSectionLevel) 
	{

		case(C__NAPPTOPLEVEL__):
			if($nType == C__SECTION_BEGIN__) { $strPaddingBefore = PHP_EOL.PHP_EOL; }
			$strSeparatorChars = "#";
			if($nType == C__SECTION_END__) { $strPaddingAfter = PHP_EOL.PHP_EOL; }
			break;

		case(C__NAPPFIRSTLEVEL__):
			if($nType == C__SECTION_BEGIN__) { $strPaddingBefore = ''; }
			$strSeparatorChars = "=";
			if($nType == C__SECTION_END__) { $strPaddingAfter = ''; }
			break;

			case(C__NAPPSECONDLEVEL__):
				if($nType == C__SECTION_BEGIN__)  { $strPaddingBefore = ''; }
				$strSeparatorChars = "-";
				if($nType == C__SECTION_END__) { $strPaddingAfter = ''; }
				break;

		default:
			$strSeparatorChars = ".";
			break;
	}

	//
	// Compute how wide the header box needs to be and then create a string of that length 
	// filled in with just the separator characters.
	// 
	$nHeaderWidth = 80;
	$fmtSeparatorString = "%'".$strSeparatorChars.($nHeaderWidth+3)."s\n";
    $strSectionIntroSeparatorLine = sprintf($fmtSeparatorString, $strSeparatorChars);



    if($nType == C__SECTION_BEGIN__)
    {
           $strSectionType = "  BEGIN:  ".$headerText;
    } else
    {
           $strSectionType = "  END:    ".$headerText;}
	//
	// Output the section header
	//
    if($nType == C__SECTION_BEGIN__ || $nSectionLevel == C__NAPPTOPLEVEL__ )
    {
        echo $strPaddingBefore;
        echo $strSectionIntroSeparatorLine;
        echo ' '.$strSectionType.' '.PHP_EOL;
        echo $strSectionIntroSeparatorLine;
        if($nSectionLevel == C__NAPPTOPLEVEL__ )
        {
            echo $strPaddingAfter;
        }

    }
    else // C__SECTION_END__ $strSectionType = "      Done.  ";}
    {
        echo PHP_EOL . ' '.$strSectionType.' ' .PHP_EOL. $strSectionIntroSeparatorLine . PHP_EOL;
    }
}
?>