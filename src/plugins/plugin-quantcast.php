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

require_once dirname(__FILE__).'/plugin-base.php';

/****************************************************************************************************************/
/****                                                                                                        ****/
/****         Quantcast Plugin Class                                                                         ****/
/****                                                                                                        ****/
/****************************************************************************************************************/
class QuantcastPluginClass extends SitePluginBaseClass
{
    // Redefine the parent method
    function addDataToRecord(&$arrRecordToUpdate) 
    {
		$strFunc = __debug__FuncStart__("addQuantcastFacts(arrRecordToUpdate(size=".count($arrRecordToUpdate).",first=".array_keys($arrRecordToUpdate)[0].")");

		if(!$GLOBALS['OPTS']['exclude_quantcast'])
		{
			if($arrRecordToUpdate['effective_domain'] && strlen($arrRecordToUpdate['effective_domain']) > 0 && strcasecmp($arrRecordToUpdate['effective_domain'], "N/A") != 0)
			{
				$arrQuant = $this->_getData_($arrRecordToUpdate['effective_domain']);
				$arrRecordToUpdate = my_merge_add_new_keys( $arrRecordToUpdate, $arrQuant );
			}
		}
		__debug__FuncEnd__($strFunc);
    }

   private function _getData_($var) 
	{
		$domain = $var;
		$strFunc = __debug__FuncStart__("getQuantcastDataByDomain(".$domain.")");
		$url = 'https://www.quantcast.com/'.$domain;
		$curl_obj = curlWrap($url);
		  $uniqs = $this->_getUniqsFromHTML_($curl_obj);
			
		$arrNew = array("Monthly Uniques" => $uniqs);
		$arrReturn = addPrefixToArrayKeys($arrNew, "Quantcast", ".");
		__debug__FuncEnd__($strFunc);
		return $arrReturn;
    }



	private function _getUniqsFromHTML_($curl_obj)
	{

			$regexMatch = '/\sclass="reach" id="[a-zA-Z0-9-:.]{1,}">\s*([N\/A0-9Mk.,]+)/mi';

			$ret =	preg_match_all($regexMatch, $curl_obj['output'], $arrMatches);
			if(count($arrMatches) > 0)
			{
				$retValue = null;
				$nMatch = count($arrMatches[1])-1;
			
				$matchValue = $arrMatches[1][$nMatch]; 
				$value = $this->_getExpandedNumber_($matchValue);
				$retValue = $value;

				if($nMatch > 0)
				{
					$parentMatch = $arrMatches[1][$nMatch-1]; 
					$parentValue = $this->_getExpandedNumber_($parentMatch);
				}
			
			
				return $retValue;					
			}
			else
			{
				print '     Monthly uniques: Unable to get count'.PHP_EOL;
				return 'ERROR';
			}

		return null;
	}

	private function _getExpandedNumber_($value)
	{
		$retValue = $value;
	
		preg_match('/^([0-9]{1,})./', $value, $predot); 
		if(count($predot) > 1)
		{
			preg_match('/[0-9]{1,}.([0-9]{1})/',$value, $postdot); 
			preg_match('/M$/i', $value, $wasMil); 
			preg_match('/K$/i', $value, $wasThou); 

			$fullValue = $predot[1].','.$postdot[1].'00';
			if(count($wasMil) > 0) { $fullValue = $fullValue.',000'; }
			$retValue = $fullValue;
		
		}
		return $retValue;
	}

}
?>