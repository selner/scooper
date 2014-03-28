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
/****          Crunchbase Plugin Class                                                                               ****/
/****                                                                                                        ****/
/****************************************************************************************************************/
class CrunchbasePluginClass extends ScooterPluginBaseClass
{

    private $_fDataIsExcluded_ = C__FEXCLUDE_DATA_NO;
    private $strDataProviderName  = 'Quantcast';


    function __construct($fExcludeThisData)
	{
        if($fExcludeThisData == 1) { $this->_fDataIsExcluded_ = C__FEXCLUDE_DATA_YES; }

        __debug__printLine("Instantiating a ". $this->strDataProviderName ." data plugin (ExcludeData=".$this->_fDataIsExcluded_.").", C__DISPLAY_ITEM_RESULT__);
	}
	
    // Redefine the parent method
    public function addDataToRecord(&$arrRecordToUpdate) 
    {
        if($this->_fDataIsExcluded_ == C__FEXCLUDE_DATA_YES) return;

        $strFunc = "addCrunchbaseFacts(arrRecordToUpdate(size=".count($arrRecordToUpdate)."))";
		__debug__printLine($strFunc, C__DISPLAY_FUNCTION__, true);

		/****************************************************************************************************************/
		/****                                                                                                        ****/
		/****   Get Crunchbase data for the record.                                                                  ****/
		/****                                                                                                        ****/
		/****************************************************************************************************************/
		__debug__printLine("Querying Crunchbase for ".$arrRecordToUpdate['company_name'], C__DISPLAY_ITEM_START__);

		$arrRecordToUpdate = my_merge_add_new_keys($arrRecordToUpdate, array('crunchbase_match_accuracy' => 'N/A'));

        if(isRecordFieldNullOrNotSet($arrRecordToUpdate['company_name']) == true)
        {
            throw new Exception("Error: company_name value was not set on the records correctly.  Cannot search Crunchbase.");
        }

		//
		//  Encode the company name for use in the API call.  Change any space characters to = characters.
		// 
		$company_name_urlenc = urlencode($arrRecordToUpdate['company_name']); 
		$company_name_urlenc = preg_replace('/%20/m', '+', $company_name_urlenc); 
		$url = "http://api.crunchbase.com/v/1/search.js?api_key=7d379mfwxm876tvgw3xhf2fs&entity=company&query=" . $company_name_urlenc;

		//
		// Call the Crunchbase Search API 
		//
        $classAPICall = new APICallWrapperClass();

		$arrCrunchBaseSearchResultsRecords = $classAPICall->getObjectsFromAPICall($url, 'results');

        if($GLOBALS['VERBOSE'])  { __debug__printLine("Crunchbase returned ".count($arrCrunchBaseSearchResultsRecords)." results for ". $arrRecordToUpdate['company_name'].". ", C__DISPLAY_ITEM_RESULT__);  }
		$fCrunchMatchFound = false;
		
		$nMatchCrunchResult = -1;
		$nCurResult = 0;
		if($arrCrunchBaseSearchResultsRecords && count($arrCrunchBaseSearchResultsRecords) > 0)
		{
//            __debug__var_dump_exit__(array('record'=>$arrRecordToUpdate, 'CB results'=>$arrCrunchBaseSearchResultsRecords), 'CB API Call');
			foreach ($arrCrunchBaseSearchResultsRecords as $curCrunchResult)
			{
				$curCrunchResult->computed_domain = getPrimaryDomain($curCrunchResult->homepage_url);
				if(strcasecmp($curCrunchResult->computed_domain, $arrRecordToUpdate['effective_domain']) == 0)
				{
					// Match found
					$nMatchCrunchResult = $nCurResult;
					$arrRecordToUpdate['crunchbase_match_accuracy'] = "Crunchbase matched on domain.";
					break;
					
				}
			}
			if($nMatchCrunchResult == -1 && count($arrCrunchBaseSearchResultsRecords) > 0)
			{
				__debug__printLine("Exact match not found in Crunchbase results, so am using first result.", C__DISPLAY_ERROR__);  
				$nMatchCrunchResult = 0;
				$arrRecordToUpdate['crunchbase_match_accuracy'] = "Crunchbase first search result used; could not find an exact match on domain.";
			}

			$arrRetCrunchResult = json_decode(json_encode($arrCrunchBaseSearchResultsRecords[$nMatchCrunchResult]), true);			$cbEntityType = $arrRetCrunchResult['namespace'];
            $arrPrefixedCrunchResult = $this->addKeyPrefixToCEntityData($arrRetCrunchResult, $cbEntityType);
//            $arrPrefixedCrunchResult = addPrefixToArrayKeys($arrRetCrunchResult, $cbEntityType, ".");
//           __debug__var_dump_exit__(array('$arrPrefixedCrunchResult' => $arrPrefixedCrunchResult, 'record'=>$arrRecordToUpdate, 'company_name_urlenc'=>$company_name_urlenc, 'API_url' => $url), 'CB API Call');

            merge_into_array_and_add_new_keys($arrRecordToUpdate, $arrPrefixedCrunchResult);
		}

		if($nMatchCrunchResult == -1) 
		{		
			$arrRecordToUpdate['crunchbase_match_accuracy'] = "Crunchbase search returned no results.";
			__debug__printLine("Company not found in Crunchbase.", C__DISPLAY_ERROR__);  
		}

        addToAccuracyField($arrRecordToUpdate, $arrRecordToUpdate['crunchbase_match_accuracy']);

        //
        // Now that we have a Crunchbase entity permalink to use, go add the extended entity facts as well
        //
        if($nMatchCrunchResult == -1)
        {
            $this->_addCrunchbaseEntityFacts_($arrRecordToUpdate);
        }

        __debug__printLine('returning from '.$strFunc, C__DISPLAY_FUNCTION__, true);
	}


	public function getArbitraryAPICallData($strAPICallURL, $fileOutFullPath)
	{
		$arrCrunchAPIData = array();
        $classAPICall = new APICallWrapperClass();

        $arrCrunchAPIData[] = $classAPICall->getObjectsFromAPICall($strAPICallURL, '', C__API_RETURN_TYPE_ARRAY__);
    	$classOutputFile = new SimpleScooterCSVFileClass($fileOutFullPath, "w");
        $classOutputFile->writeArrayToCSVFile($arrCrunchAPIData);
	}



	private function _addCrunchbaseEntityFacts_(&$arrRecordToUpdate)
	{
		$strFunc = "addCrunchbaseEntityFacts(arrRecordToUpdate(size=".count($arrRecordToUpdate)."))";
		__debug__printLine($strFunc, C__DISPLAY_FUNCTION__, true);

		if($GLOBALS['OPTS']['exclude_crunchbase'] != true)
		{
			if($arrRecordToUpdate['permalink'] && strlen($arrRecordToUpdate['permalink']) > 0)
			{
				if($arrRecordToUpdate['namespace'] && strlen($arrRecordToUpdate['namespace']) > 0)
				{
					$cbEntityType = 	$arrRecordToUpdate['namespace'];
                    $arrCrunchEntityData = $this->_getCrunchbaseEntityFacts_($cbEntityType, $arrRecordToUpdate['permalink']);
                    if(is_array($arrCrunchEntityData))
                    {
                        $arrPrefixedResult = addPrefixToArrayKeys($arrCrunchEntityData, $cbEntityType, ".");
                        $arrRecordToUpdate = my_merge_add_new_keys($arrRecordToUpdate, $arrPrefixedResult);
                    }
                }
            }
		}
			
		__debug__printLine('returning from '.$strFunc, C__DISPLAY_FUNCTION__, true);

	}
	

	private function _getCrunchbaseEntityFacts_($entity_type, $strPermanlink)
	{

		if(!$strPermanlink || strlen($strPermanlink) == 0)
		{
			if($GLOBALS['VERBOSE'])  { __debug__printLine("No Crunchbase permanlink value passed.  Cannot lookup other facts.", C__DISPLAY_ITEM_RESULT__);  }
			return null;
		}
		
		
		//
		//  Encode the company name for use in the API call.  Change any space characters to = characters.
		// 
		$strAPIURL = "http://api.crunchbase.com/v/1/".$entity_type."/".$strPermanlink.".js?api_key=7d379mfwxm876tvgw3xhf2fs";
		if($GLOBALS['VERBOSE'])  { __debug__printLine("Crunchbase API Call = ".$strAPIURL, C__DISPLAY_ITEM_DETAIL__); }

		//
		// Call the Crunchbase Search API 
		// 

        $classAPICall = new APICallWrapperClass();

        $classAPICall ->getObjectsFromAPICall($strAPICallURL, '', C__API_RETURN_TYPE_ARRAY__);

		$arrCrunchEntityData = getObjectsFromAPI($strAPIURL, '');
		if($arrCrunchEntityData && is_array($arrCrunchEntityData))
		{
            $this->addKeyPrefixToCEntityData($arrCrunchEntityData, $entity_type);
//           merge_into_array_and_add_new_keys($arrRecordToUpdate, $arrPrefixedCrunchResult);
//			addPrefixToArrayKeys($arrCrunchEntityData, "Crunchbase", ".");
		} 
		return $arrCrunchEntityData;
		
	}

    private function addKeyPrefixToCEntityData($arrEntityData, $entityType)
    {
        $arrKeys = array_keys($arrEntityData);
        $arrNewKeyValues = $arrKeys;
        $arrNewKeys = array();
        foreach ($arrKeys as $key)
        {
            if($this->arrCBCommonEntityFieldPrefixes[$key])
            {
                $key = 'cb.'.$key;
            }
            else
            {
                $key = $entityType.'.'.$key;
            }
            $arrNewKeys[] = $key;
        }
        return array_combine($arrNewKeys, $arrEntityData);

    }

    private $arrCBCommonEntityFieldPrefixes = array(
        'category_code' => 'N/A',
        'field_name' => 'N/A',
        'crunchbase_url' => 'N/A',
        'description' => 'N/A',
        'homepage_url' => 'N/A',
        'image' => 'N/A',
        'name' => 'N/A',
        'namespace' => 'N/A',
        'offices' => 'N/A',
        'overview' => 'N/A',
        'permalink' => 'N/A',
        'computed_domain' => 'N/A'
    );




}

?>
