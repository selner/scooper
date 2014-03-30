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
    private $strDataProviderName  = 'Crunchbase';


    function __construct($fExcludeThisData)
	{
        if($fExcludeThisData == 1) { $this->_fDataIsExcluded_ = C__FEXCLUDE_DATA_YES; }

        if(strlen(C__CRUNCHBASE_API_KEY__) == 0 || C__CRUNCHBASE_API_KEY__ == "")
        {
            __log__("Crunchbase API Key was not set.  Excluding Crunchbase data from the results.", C__LOGLEVEL_ERROR__);
            $this->_fDataIsExcluded_ = C__FEXCLUDE_DATA_YES;
        }

        __debug__printLine("Instantiating a ". $this->strDataProviderName ." data plugin (ExcludeData=".$this->_fDataIsExcluded_.").", C__DISPLAY_ITEM_RESULT__);
	}
	
    // Redefine the parent method
    public function addDataToRecord(&$arrRecordToUpdate) 
    {
        if($this->_fDataIsExcluded_ == C__FEXCLUDE_DATA_YES) return;

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
		$url = "http://api.crunchbase.com/v/1/search.js?api_key=".C__CRUNCHBASE_API_KEY__."&entity=company&query=" . $company_name_urlenc;

		//
		// Call the Crunchbase Search API 
		//
        $classAPICall = new APICallWrapperClass();

		$arrCrunchBaseSearchResultsRecords = $classAPICall->getObjectsFromAPICall($url, 'results', C__API_RETURN_TYPE_ARRAY__, array($this, 'updateCBDataWithCommonPrefixes'));
        if($GLOBALS['VERBOSE'])  { __debug__printLine("Crunchbase returned ".count($arrCrunchBaseSearchResultsRecords)." results for ". $arrRecordToUpdate['company_name'].". ", C__DISPLAY_ITEM_RESULT__);  }

        $nMatchCrunchResult = -1;
		$nCurResult = 0;
		if($arrCrunchBaseSearchResultsRecords && count($arrCrunchBaseSearchResultsRecords) > 0)
		{
			foreach ($arrCrunchBaseSearchResultsRecords as $curCrunchResult)
			{
                if($curCrunchResult['cb.homepage_url'] && strlen($curCrunchResult['cb.homepage_url']) > 0)
                {
                    $curCrunchResult['cb.computed_domain'] = getPrimaryDomain($curCrunchResult['cb.homepage_url']);
                    if(strcasecmp($curCrunchResult['cb.computed_domain'], $arrRecordToUpdate['effective_domain']) == 0)
                    {
                        // Match found
                        $nMatchCrunchResult = $nCurResult;
                        $arrRecordToUpdate['crunchbase_match_accuracy'] = "Crunchbase matched on domain.";
                        merge_into_array_and_add_new_keys($arrRecordToUpdate, $curCrunchResult);
                        break;

                    }
                }
            }
			if($nMatchCrunchResult == -1 && count($arrCrunchBaseSearchResultsRecords) > 0)
			{
				__debug__printLine("Exact match not found in Crunchbase results, so am using first result.", C__DISPLAY_ERROR__);  
				$nMatchCrunchResult = 0;
				$arrRecordToUpdate['crunchbase_match_accuracy'] = "Crunchbase first search result used; could not find an exact match on domain.";
			}
		}

        //
        // If we didn't get a match, note it in the record
        //
		if($nMatchCrunchResult == -1) 
		{		
			$arrRecordToUpdate['crunchbase_match_accuracy'] = "Crunchbase search returned no results.";
			__debug__printLine("Company not found in Crunchbase.", C__DISPLAY_ERROR__);  
		}
        else
        {
            //
            // Otherwise, go get the full entity facts for that record
            //
            $this->_addCrunchbaseEntityFacts_($arrRecordToUpdate);
        }

        addToAccuracyField($arrRecordToUpdate, $arrRecordToUpdate['crunchbase_match_accuracy']);

        $this->_expandArrays_($arrRecordToUpdate);

        if($GLOBALS['VERBOSE'] == true) { __log__ ('Updated Record: '.var_export($arrRecordToUpdate), C__LOGLEVEL_DEBUG__); }

	}




    private function _addCrunchbaseEntityFacts_(&$arrRecordToUpdate)
    {
        if($this->_fDataIsExcluded_ == C__FEXCLUDE_DATA_YES) return;

        __debug__printLine("Getting Crunchbase ".$arrRecordToUpdate['cb.namespace'] ." entity-specific facts for ".$arrRecordToUpdate['cb.name'] , C__DISPLAY_ITEM_RESULT__);

        if(($arrRecordToUpdate['cb.permalink'] && strlen($arrRecordToUpdate['cb.permalink']) > 0) &&
            ($arrRecordToUpdate['cb.namespace'] && strlen($arrRecordToUpdate['cb.namespace']) > 0))
        {
            $arrCrunchEntityData = $this->_getCrunchbaseEntityFacts_($arrRecordToUpdate['cb.namespace'], $arrRecordToUpdate['cb.permalink']);
            if(is_array($arrCrunchEntityData))
            {
                $arrRecordToUpdate = my_merge_add_new_keys($arrRecordToUpdate, $arrCrunchEntityData);
            }
        }
        else
        {
            $strErr = "Could not lookup entity-specific facts for ".$arrRecordToUpdate['cb.name']. ".  Invalid cb.permalink or cb.namespace value was given.";
            __debug__printLine($strErr , C__DISPLAY_ERROR__);
            addToAccuracyField($arrRecordToUpdate, $strErr);
        }

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

        $arrCrunchEntityData = $classAPICall->getObjectsFromAPICall($strAPIURL, '', C__API_RETURN_TYPE_ARRAY__, array($this, 'updateCBDataWithCommonPrefixes'));

    	return $arrCrunchEntityData;
		
	}

    static public function updateCBDataWithCommonPrefixes(&$arrRecord)
    {
        if(is_array($arrRecord))
        {

            print '--- -HERE2---- '.PHP_EOL;



            $entityType = $arrRecord['namespace'];
            if(!$entityType || strlen($entityType) == 0) { $entityType = $arrRecord['cb.namespace']; };

             $arrCBCommonEntityFieldPrefixes = array(
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
                'computed_domain' => 'N/A',
                'blog_url' => 'N/A',
                'blog_feed_url' => 'N/A',
                'twitter_username' => 'N/A',
                'phone_number' => 'N/A',
                'email_address' => 'N/A',
                'number_of_employees' => 'N/A',
                'founded_year' => 'N/A',
                'founded_month' => 'N/A',
                'founded_day' => 'N/A',
                'tag_list' => 'N/A',
                'alias_list' => 'N/A',
                'created_at' => 'N/A',
                'updated_at' => 'N/A',
                'relationships' => 'N/A',
                'investments' => 'N/A',
                'milestones' => 'N/A',
                'providerships' => 'N/A',
                'funds' => 'N/A',
                'video_embeds' => 'N/A',
                'external_links' => 'N/A',
                'deadpooled_year' => 'N/A',
                'deadpooled_month' => 'N/A',
                'deadpooled_day' => 'N/A',
                'deadpooled_url' => 'N/A',
                'products' => 'N/A',
                 'competitions' => 'N/A',
                 'total_money_raised' => 'N/A',
                 'funding_rounds' => 'N/A',
                 'acquisition' => 'N/A',
                 'acquisitions' => 'N/A',
                 'ipo' => 'N/A',
                 'screenshots' => 'N/A',
                 'partners' => 'N/A'                                          );


            $arrKeys = array_keys($arrRecord);

            $arrNewKeys = array();
            foreach ($arrKeys as $key)
            {
                if($arrCBCommonEntityFieldPrefixes[$key])
                {
                    $key = 'cb.'.$key;
                }
                else if(strlen($entityType) > 0)
                {
                    $key = $entityType .'.'.$key;
                }
                $arrNewKeys[] = $key;
            }

            $arrRecord = array_copy(array_combine($arrNewKeys, $arrRecord));
        }

    }



    public function getArbitraryAPICallData($strAPICallURL, $fileOutFullPath)
    {
        $arrCrunchAPIData = array();
        $classAPICall = new APICallWrapperClass();

        $arrCrunchAPIData[] = $classAPICall->getObjectsFromAPICall($strAPICallURL, '', C__API_RETURN_TYPE_ARRAY__);
        $classOutputFile = new SimpleScooterCSVFileClass($fileOutFullPath, "w");
        $classOutputFile->writeArrayToCSVFile($arrCrunchAPIData);
    }



}

?>
