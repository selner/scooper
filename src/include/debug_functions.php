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
/*
function __debug__DumpVarToLog($var_name, $var_case_details, $var)
{
	$strVarValue = var_export($var);
	$message = 'Variable $'.$var_name.' written to log by '.$var_case_details.' Value=:\n'.$strVarValue.'\n';
	syslog ( LOG_DEBUG , $message );
	
}
*/
require_once 'common.php';
const C__DEBUG_MODE__ = false;

function __debug__FuncStart__($str)
{
	__debug__printLine($str, C__DISPLAY_FUNCTION__, true);
	return $str;
}

function __debug__FuncEnd__($str)
{

    return __debug__FuncStart__("returning from ".$str);
}


function __debug__var_dump_exit__($var, $desc="__debug__var_dump_exit__")
{

    var_dump($desc, $var);
    exit($desc);

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
		$strLineBeginning = '';
		$strLineEnd = '';
		$logLevel = null;
		// Valid $varLogLevels found at http://www.php.net/manual/en/function.syslog.php
		switch ($varDisplayStyle)
		{
				case C__DISPLAY_FUNCTION__: 
					$strLineBeginning = '<<<<<<<< function "';
					$strLineEnd = '" called >>>>>>> ';
					$logLevel = LOG_INFO;
					break;

				case C__DISPLAY_RESULT__: 
					$strLineBeginning = '==> ';
					$logLevel = LOG_INFO;
					break;

			case C__DISPLAY_ERROR__: 
				$strLineBeginning = '!!!!! ';
				$logLevel = LOG_ERR;
				break;
				
			case C__DISPLAY_ITEM_START__: 
				$strLineBeginning = '---> ';
				$logLevel = LOG_INFO;
				break;
				
			case C__DISPLAY_ITEM_DETAIL__: 
				$strLineBeginning = '     ';
				$logLevel = LOG_INFO;
				break;
				
			case C__DISPLAY_ITEM_RESULT__: 
				$strLineBeginning = '======> ';
				$logLevel = LOG_INFO;
				break;
					
			case C__DISPLAY_MOMENTARY_INTERUPPT__: 
				$strLineBeginning = '......';
				$logLevel = LOG_INFO;
				break;
						
			case C__DISPLAY_NORMAL__: 
				$strLineBeginning = '';
				$logLevel = LOG_INFO;
				break;
		
			default:
				print 'Invalid type value passed to __debug__printLine.  Value = '.$varDisplayStyle. ".   Exiting.";
				exit(-1);
				break;	
		}


		print $strLineBeginning . $strToPrint . $strLineEnd . PHP_EOL;
	}
}


function __debug__printSectionHeader($headerText, $nSectionLevel, $nType) 
{
	
	$strPaddingBefore = "";
	$strSectionSeparatorLine = "" . PHP_EOL;
	$strPaddingAfter = "";
	$strSeparatorChars = "'";

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
	$nHeaderWidth = strlen($headerText);
	$nHeaderWidth = 80;
	$fmtSeparatorString = "%'".$strSeparatorChars.($nHeaderWidth+3)."s\n";
   $strSectionSeparatorLine = sprintf($fmtSeparatorString, $strSeparatorChars);
	

	$strSectionType = "BEGIN:  ".$headerText;
//	if($nType == C__SECTION_END__) { $strSectionType = "END:  ";}
	if($nType == C__SECTION_END__) { $strSectionType = "      Done.  ";}
	//
	// Output the section header
	//
	echo $strPaddingBefore;
	echo $strSectionSeparatorLine;
	echo ' '.$strSectionType.' '. PHP_EOL;
	echo $strSectionSeparatorLine;
	echo $strPaddingAfter;
}
?>