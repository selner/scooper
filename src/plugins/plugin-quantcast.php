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

require_once(__ROOT__.'/include/plugin-base.php');

/****************************************************************************************************************/
/****                                                                                                        ****/
/****         Quantcast Plugin Class                                                                         ****/
/****                                                                                                        ****/
/****************************************************************************************************************/
class QuantcastPluginClass extends ScooterPluginBaseClass
{
    protected $_fDataIsExcluded_ = C__FEXCLUDE_DATA_NO;
    protected  $strDataProviderName  = 'Quantcast';

    function __construct($fExcludeThisData)
    {
        if($fExcludeThisData == 1) { $this->_fDataIsExcluded_ = C__FEXCLUDE_DATA_YES; }
        $GLOBALS['logger']->logLine("Instantiating a ". $this->strDataProviderName ." data plugin (ExcludeData=".$this->_fDataIsExcluded_.").", \Scooper\C__DISPLAY_ITEM_DETAIL__);
    }

    function getAllColumns()
    {
        return array('monthly_uniques' => '<not set>');
    }

    // Redefine the parent method
    function addDataToRecord(&$arrRecordToUpdate) 
    {
        if($this->_fDataIsExcluded_ == C__FEXCLUDE_DATA_YES) return;
        if($arrRecordToUpdate['root_domain'] && strlen($arrRecordToUpdate['root_domain']) > 0 && strcasecmp($arrRecordToUpdate['root_domain'], "<not set>") != 0)
        {
            $arrQuant = $this->_getData_($arrRecordToUpdate['root_domain']);
            $arrRecordToUpdate = \Scooper\my_merge_add_new_keys( $arrRecordToUpdate, $arrQuant );
        }
    }

    function getCompanyData($id)
    {
        throw new Exception("getCompanyData not implemented for " . get_class($this));

    }


    private function _getData_($var)
	{
        if($this->_fDataIsExcluded_ == C__FEXCLUDE_DATA_YES) return null;

        $classAPIWrap = new \Scooper\ScooperDataAPIWrapper();
        $domain = $var;
        $url = 'https://www.quantcast.com/'.$domain;
        $GLOBALS['logger']->logLine("Querying Quantcast data for ".$domain, \Scooper\C__DISPLAY_ITEM_START__);
    	$curl_obj = $classAPIWrap->cURL($url);
		  $uniqs = $this->_getUniqsFromHTML_($curl_obj);

        $arrReturn = array("monthly_uniques" => $uniqs);
		// $arrReturn = addPrefixToArrayKeys($arrNew, "quantcast", ".");
		return $arrReturn;
    }



	private function _getUniqsFromHTML_($curl_obj)
	{

			$regexMatch = '/\sclass="reach" id="[a-zA-Z0-9-:.]{1,}">\s*([N\/A0-9Mk.,]+)/mi';

			preg_match_all($regexMatch, $curl_obj['output'], $arrMatches);
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
                    $retValue = $parentValue;
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