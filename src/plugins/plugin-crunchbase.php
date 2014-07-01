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

const C__MAX_CRUNCHBASE_PAGE_DOWNLOADS = 5;
const C__RETURNS_SINGLE_RECORD = 1;
$GLOBALS['CB_SUPPORTED_SECONDARY_API_CALLS'] = array("acquisitions", "funding_rounds");

CONST C__CB_SINGLERECORD_TYPES_REGEX__ = "/\/person[\?\/]{1,1}|\/organization[\?\/]{1,1}|\/funding-round[\?\/]{1,1}|\/acquisition[\?\/]{1,1}/";

const C__MAX_RESULT_PAGES_FETCHED = 5;


/****************************************************************************************************************/
/****                                                                                                        ****/
/****          Crunchbase Plugin Class                                                                       ****/
/****                                                                                                        ****/
/****************************************************************************************************************/
class CrunchbasePluginClass extends ScooterPluginBaseClass
{

    protected $_fDataIsExcluded_ = C__FEXCLUDE_DATA_NO;
    protected $strDataProviderName  = 'Crunchbase';


    function __construct($fExcludeThisData)
	{
        if($fExcludeThisData == 1) { $this->_fDataIsExcluded_ = C__FEXCLUDE_DATA_YES; }

        if(strlen($GLOBALS['OPTS']['crunchbase_api_id']) == 0 || $GLOBALS['OPTS']['crunchbase_api_id'] == "")
        {
            $GLOBALS['logger']->logLine("Crunchbase API Key was not set.  Excluding Crunchbase data from the results.", \Scooper\C__DISPLAY_ERROR__);
            $this->_fDataIsExcluded_ = C__FEXCLUDE_DATA_YES;
        }

        $GLOBALS['logger']->logLine("Initializing the ". $this->strDataProviderName ." data plugin (ExcludeData=".$this->_fDataIsExcluded_.").", \Scooper\C__DISPLAY_NORMAL__);
	}


    function getAllColumns()
    {
        return array(
            'uuid' => '<not set>',
            'type' => '<not set>',
            'properties' => '<not set>',
            'role_company' => '<not set>',
            'description' => '<not set>',
            'short_description' => '<not set>',
            'permalink' => '<not set>',
            'primary_role' => '<not set>',
            'is_closed' => '<not set>',
            'name' => '<not set>',
            'founded_on_day' => '<not set>',
            'founded_on_month' => '<not set>',
            'founded_on_year' => '<not set>',
            'role_investor' => '<not set>',
            'closed_on_day' => '<not set>',
            'closed_on_month' => '<not set>',
            'closed_on_year' => '<not set>',
            'closed_on' => '<not set>',
            'stock_exchange_id' => '<not set>',
            'created_at' => '<not set>',
            'updated_at' => '<not set>',
            'closed_on_trust_code' => '<not set>',
            'homepage_url' => '<not set>',
            'founded_on' => '<not set>',
            'founded_on_trust_code' => '<not set>',
            'sub_organizations' => '<not set>',
            'current_team' => '<not set>',
            'acquisitions' => '<not set>',
            'competitors' => '<not set>',
            'offices' => '<not set>',
            'headquarters' => '<not set>',
            'funding_rounds' => '<not set>',
            'categories' => '<not set>',
            'investments' => '<not set>',
            'invested_in' => '<not set>',
            'founders' => '<not set>',
            'ipo' => '<not set>',
            'products' => '<not set>',
            'primary_image' => '<not set>',
            'images' => '<not set>',
            'websites' => '<not set>',
            'url' => '<not set>',
            'news' => '<not set>',

        );

    }

    public function exportCompanyDataForCSVInputIDs($strFileIn, $strFileOut)
    {
        $arrIDs = parent::readIDsFromCSVFile($strFileIn, "path");
        $this->exportMultipleOrganizationsToCSV($arrIDs, $strFileOut);
    }


    public function getCompanyData($strPermalink)
    {

        if(!$strPermalink || strlen($strPermalink) == 0)
        {
            if($GLOBALS['OPTS']['VERBOSE'])  { $GLOBALS['logger']->logLine("No Crunchbase permanlink value passed.  Cannot lookup other facts.", \Scooper\C__DISPLAY_ITEM_RESULT__);  }
            return null;
        }

        if(!$strPermalink || strlen($strPermalink) == 0)
        {
            if($GLOBALS['OPTS']['VERBOSE'])  { $GLOBALS['logger']->logLine("No Crunchbase permanlink value passed.  Cannot lookup other facts.", \Scooper\C__DISPLAY_ITEM_RESULT__);  }
            return null;
        }
        $strAPIURL = "http://api.crunchbase.com/v/2/organization/".$strPermalink;

        //
        // Call the Crunchbase Search API
        //
        $data = $this->fetchDataFromAPI($strAPIURL, true, C__RETURNS_SINGLE_RECORD, null);
        $data['crunchbase_match_accuracy'] = "Exact match on permalink.";
        if(isRecordFieldNullOrNotSet($data['root_domain'])) { $data['root_domain'] = \Scooper\getPrimaryDomainFromUrl($data['homepage_url']); }
        if(isRecordFieldNullOrNotSet($data['actual_site_url'])) { $data['actual_site_url'] = $data['homepage_url']; }
        return $data;
    }





    // Redefine the parent method
    public function addDataToRecord(&$arrRecordToUpdate, $fExpandArrays = true)
    {

        if($this->_fDataIsExcluded_ == C__FEXCLUDE_DATA_YES) return;

		/****************************************************************************************************************/
		/****                                                                                                        ****/
		/****   Get Crunchbase data for the record.                                                                  ****/
		/****                                                                                                        ****/
		/****************************************************************************************************************/

        try
        {
            $arrRecordToUpdate = \Scooper\my_merge_add_new_keys($arrRecordToUpdate, array('crunchbase_match_accuracy' => '<not set>'));

            //
            // Call the Crunchbase Search API
            //
            $classAPICall = new \Scooper\ScooperDataAPIWrapper();
            $nMatchCrunchResult = -1;
            $nCurResult = 0;

            $arrPermalinksToLookup = array('permalink' => array('index' => null, 'permalink' => null));

            if(isRecordFieldNullOrNotSet($arrRecordToUpdate['permalink']) == false)
            {
                $GLOBALS['logger']->logLine("Querying Crunchbase for ". $arrRecordToUpdate['permalink'], \Scooper\C__DISPLAY_ITEM_START__);
                // We've got the direct link to the right record in Crunchbase, so we
                // can skip over this next section
                $nMatchCrunchResult = 1;
            }
            else
            {
                $GLOBALS['logger']->logLine("Querying Crunchbase for ". $arrRecordToUpdate['company_name'], \Scooper\C__DISPLAY_ITEM_START__);
                if(isRecordFieldNullOrNotSet($arrRecordToUpdate['company_name']) == true)
                {
                    $arrRecordToUpdate['crunchbase_match_accuracy'] = "Could not search Crunchbase: no company name.";
                    // throw new Exception("Error: company_name value was not set on the records correctly.  Cannot search Crunchbase.");
                }
                else
                {

                    //
                    //  Encode the company name for use in the API call.  Change any space characters to = characters.
                    //
                    $company_name_urlenc = urlencode($arrRecordToUpdate['company_name']);
                    $company_name_urlenc = preg_replace('/%20/m', '+', $company_name_urlenc);
                    $url = "http://api.crunchbase.com/v/1/search.js?api_key=".$GLOBALS['OPTS']['crunchbase_v1_api_id']."&entity=company&query=" . $company_name_urlenc;


                        if($GLOBALS['OPTS']['VERBOSE'])  { $GLOBALS['logger']->logLine("Crunchbase API call=".$url, \Scooper\C__DISPLAY_ITEM_DETAIL__);  }
                       $arrCrunchBaseSearchResultsRecords = $classAPICall->getObjectsFromAPICall($url, 'results', \Scooper\C__API_RETURN_TYPE_ARRAY__, null);

                        if($GLOBALS['OPTS']['VERBOSE'])  { $GLOBALS['logger']->logLine("Crunchbase returned ".count($arrCrunchBaseSearchResultsRecords)." results for ". $arrRecordToUpdate['company_name'].". ", \Scooper\C__DISPLAY_ITEM_DETAIL__);  }

                        if($arrCrunchBaseSearchResultsRecords && count($arrCrunchBaseSearchResultsRecords) > 0)
                        {
                            foreach ($arrCrunchBaseSearchResultsRecords as $curCrunchResult)
                            {
                                if($curCrunchResult['homepage_url'] && strlen($curCrunchResult['homepage_url']) > 0)
                                {
                                    $curCrunchResult['computed_domain'] = \Scooper\getPrimaryDomainFromUrl($curCrunchResult['homepage_url']);
                                    if(strcasecmp($curCrunchResult['computed_domain'], $arrRecordToUpdate['root_domain']) == 0)
                                    {
                                        // Match found
                                        $nMatchCrunchResult = $nCurResult;
                                        $arrRecordToUpdate['crunchbase_match_accuracy'] = "Crunchbase matched on domain.";
                                        $arrRecordToUpdate = \Scooper\my_merge_add_new_keys($arrRecordToUpdate, $curCrunchResult);
                                        break;

                                    }
                                }
                            }
                            if($nMatchCrunchResult == -1 && count($arrCrunchBaseSearchResultsRecords) > 0)
                            {
                                $GLOBALS['logger']->logLine("Exact match not found in Crunchbase results, so am using first result.", \Scooper\C__DISPLAY_ERROR__);
                                $nMatchCrunchResult = 0;
                                $arrRecordToUpdate['crunchbase_match_accuracy'] = "Crunchbase first search result used; could not find an exact match on domain.";
                            }
                        }

                    }
            }
            //
            // If we didn't get a match, note it in the record
            //
            if($nMatchCrunchResult == -1)
            {
                $arrRecordToUpdate['crunchbase_match_accuracy'] = "Crunchbase search returned no results.";
                $GLOBALS['logger']->logLine("Company not found in Crunchbase.", \Scooper\C__DISPLAY_ERROR__);
            }
            else
            {
                //
                // Otherwise, go get the full entity facts for that record
                //
                $this->_addCrunchbaseEntityFacts_($arrRecordToUpdate);
            }

           addToAccuracyField($arrRecordToUpdate, $arrRecordToUpdate['crunchbase_match_accuracy']);

           if($fExpandArrays == true)
           {
               $this->_expandArrays_($arrRecordToUpdate);
           }
        }
        catch ( ErrorException $e )
        {
         $strErr  = 'Error accessing ' . $this->strDataProviderName . ': ' . $e->getMessage();
         $GLOBALS['logger']->logLine ($strErr, \Scooper\C__DISPLAY_ERROR__);
         addToAccuracyField($arrRecordToUpdate, $strErr );
        }
    }




    private function _addCrunchbaseEntityFacts_(&$arrRecordToUpdate)
    {
        if($this->_fDataIsExcluded_ == C__FEXCLUDE_DATA_YES) return;

        $GLOBALS['logger']->logLine("Getting Crunchbase ".$arrRecordToUpdate['namespace'] ." entity-specific facts for ".(isRecordFieldNullOrNotSet($arrRecordToUpdate['name'])? $arrRecordToUpdate['permalink'] : $arrRecordToUpdate['name']) , \Scooper\C__DISPLAY_ITEM_DETAIL__);

        if(!isRecordFieldNullOrNotSet($arrRecordToUpdate['permalink']))
        {

            $arrCrunchEntityData = $this->getCompanyData($arrRecordToUpdate['permalink']);
            if(is_array($arrCrunchEntityData))
            {
                $arrRecordToUpdate = \Scooper\my_merge_add_new_keys($arrRecordToUpdate, $arrCrunchEntityData);
            }
        }
        else
        {
            $strErr = "Could not lookup entity-specific facts for ".$arrRecordToUpdate['name']. ".  Invalid permalink or namespace value was given.";
            $GLOBALS['logger']->logLine($strErr , \Scooper\C__DISPLAY_ERROR__);
            addToAccuracyField($arrRecordToUpdate, $strErr);
        }

    }





    private function _addRelationshipsToResult_(&$arrData, $relationships)
    {
        $arrData = \Scooper\object_to_array($arrData);
        $arrRelationships = \Scooper\object_to_array($relationships);
        if(is_array($arrRelationships) && count($arrRelationships) > 0)
        {
            $retArrAdditions = array();
            foreach(array_keys($arrRelationships) as $relation)
            {
                $relationItems = \Scooper\object_to_array($arrRelationships[$relation]);
                $retArrAdditions[$relation] = $relationItems['items'];
            }
            $arrData = \Scooper\my_merge_add_new_keys($arrData, $this->_flattenCrunchbaseData_($retArrAdditions));
        }



    }


    public function fetchCrunchbaseDataFromAPI($strAPIURL, $fFlatten = false)
    {
        $arrObjectMatches = array();
        $retItems = null;
        $fSingleMatch = preg_match_all(C__CB_SINGLERECORD_TYPES_REGEX__, $strAPIURL, $arrObjectMatches);

        if($fSingleMatch == true && count($arrObjectMatches) > 0)
        {
            $data = $this->fetchDataFromAPI($strAPIURL, $fFlatten, null,  C__RETURNS_SINGLE_RECORD, null, 'data');
            $retItems = $this->_getSingleItemFromCBData_($data, $fFlatten);
            $this->_addRelationshipsToResult_($retItems, $data[1]);

        }
        else
        {
            $retItems = $this->fetchDataFromAPI($strAPIURL, $fFlatten, 'next_page_url',  C__MAX_CRUNCHBASE_PAGE_DOWNLOADS, null, 'data');
        }
        return $retItems;
    }

    protected function setRecordCountForURL(&$arrAPICallSettings)
    {
        $fSingleMatch = preg_match_all(C__CB_SINGLERECORD_TYPES_REGEX__, $arrAPICallSettings['urls_to_fetch'][0], $arrObjectMatches);

        if($fSingleMatch == true && count($arrObjectMatches) > 0)
        {
            $arrAPICallSettings['multiple_object_result'] = false;
        }
        else
        {
            $arrAPICallSettings['multiple_object_result'] = true;

        }
    }

//    public function writeAPIResultsToFile($strAPICallURL, $detailsFile, $nMaxPages = C__RETURNS_SINGLE_RECORD)
//    {
//        $arrAPIResults = $this->getEmptyDataAPISettings();
//        $arrAPIResults['urls_to_fetch'] = array($strAPICallURL);
//        $arrAPIResults['flatten_final_results'] = true;
//        $arrAPIResults['result_keys_for_next_page'] = 'next_page_url';
//        $arrAPIResults['result_keys_for_data'] = 'data';
//        $arrAPIResults['max_result_pages'] = $nMaxPages;
//
//        $this->getAPIData($arrAPIResults);
//
//        $GLOBALS['logger']->logLine("Starting file write ...", \Scooper\C__DISPLAY_NORMAL__);
//
//        $this->writeDataToFile($arrAPIResults['fetched_data'], $detailsFile);
//    }

    protected function getKeyURLString()
    {
        return "user_key=".$GLOBALS['OPTS']['crunchbase_api_id'];
    }

    private function _getSingleItemFromCBData_($dataAPI, $fFlatten = false)
    {

        try
        {
            $retItems = array();

            if($dataAPI != null)
            {
                array_shift($dataAPI);
                array_shift($dataAPI);
                $retItems = $dataAPI[0];  // add the first bucket of data into the return set.
                $this->_addRelationshipsToResult_($retItems, $dataAPI[1]);
            }
            else
            {
                $GLOBALS['logger']->logLine('$dataAPI is null, but shouldn\'t have been.', \Scooper\C__DISPLAY_ERROR__);
            }
            if($fFlatten)
            {
                $GLOBALS['logger']->logLine("Flattening results...", \Scooper\C__DISPLAY_ITEM_DETAIL__);
                $retItems = $this->_flattenCrunchbaseData_($retItems);
            }

            return $retItems;
        }
        catch ( ErrorException $e )
        {
            print ("Error: ". $e->getMessage()."\r\n" );
        }


    }

//    private function getDataFromCrunchbaseAPI($strAPIURL, $fFlatten = false, $nMaxPages = C__RETURNS_SINGLE_RECORD, $nPageNumber = 0)
//    {
//
//        try
//        {
//        //
//        // Add the user key to the API call
//        //
//        $strKeyedAPIURL = $this->addKeyToURL($strAPIURL);
//        if($GLOBALS['OPTS']['VERBOSE'])  { $GLOBALS['logger']->logLine("Crunchbase API Call = ".$strKeyedAPIURL, \Scooper\C__DISPLAY_ITEM_DETAIL__); }
//
//        $classAPICall = new \Scooper\ScooperDataAPIWrapper();
//
//        $dataAPI= $classAPICall->getObjectsFromAPICall($strKeyedAPIURL, 'data', \Scooper\C__API_RETURN_TYPE_OBJECT__, null);
//        $retItems = array();
//
//        $fRootCall = ($nPageNumber == 0);
//
//        if($nMaxPages == C__RETURNS_SINGLE_RECORD || preg_match(C__CB_SINGLERECORD_TYPES_REGEX__, $dataAPI[1]) == 0)
//        {
//            $retItems = $this->_getSingleItemFromCBData_($dataAPI, $fFlatten);
//        }
//        else // multiple item API call
//        {
//            if(is_array($dataAPI) && !is_array($dataAPI[1]) && !is_object($dataAPI[1]))
//            {
//                $retItems = $dataAPI; // add the first bucket of data into the return set.
//            }
//            else
//            {
//                $retItems = $dataAPI[0];
//                $dataAPINextPage = \Scooper\object_to_array($dataAPI[1]);
//                if($dataAPINextPage['next_page_url'] !=null)
//                {
//                    if($dataAPINextPage != null && $nPageNumber < $nMaxPages &&
//                         $dataAPINextPage['next_page_url'] !=null) // we're in paging mode
//                    {
//                        $recursItems = $this->getDataFromCrunchbaseAPI($dataAPINextPage['next_page_url'], $fFlatten, $nMaxPages, $nPageNumber+1);
//
//                        if(is_array($recursItems))
//                        {
//                            $retItems = array_merge($retItems, $recursItems);
//                        }
//                    }
//
//                }
//                else // relationships mode, so add those
//                {
//                    $retItems = array_merge($retItems, $dataAPI[1]);
//                }
//            }
//        }
//        if($fRootCall )
//        {
//            if($nMaxPages != C__RETURNS_SINGLE_RECORD)
//                $GLOBALS['logger']->logLine("Total results: " . count($retItems), \Scooper\C__DISPLAY_ITEM_DETAIL__);
//
//
//            if($fFlatten)
//            {
//                $GLOBALS['logger']->logLine("Flattening results...", \Scooper\C__DISPLAY_ITEM_DETAIL__);
//                $retItems = $this->_flattenCrunchbaseData_($retItems);
//            }
//        }
//
//        return $retItems;
//        }
//        catch ( ErrorException $e )
//        {
//            print ("Error: ". $e->getMessage()."\r\n" );
//            addToAccuracyField($arrRecordToUpdate, 'ERROR ACCESSING CRUNCHBASE -- PLEASE RETRY');
//            $retItems['crunchbase_match_accuracy'] = 'ERROR';
//        }
//
//
//    }


    private function __getCrunchbaseAPIDataForSubItems__($dataItems)
    {
        if($dataItems == null) return null;

        $ret = null;
        foreach($dataItems as $record)
        {
            $dataExp = array();
            foreach(array_keys($record) as $recordKey)
            {
                if($recordKey == 'path')
                {
                    $strAPIURL = "http://api.crunchbase.com/v/2/".$record[$recordKey];
                    $dataFacts = $this->fetchCrunchbaseDataFromAPI($strAPIURL, true);
                    foreach(array_keys($dataFacts) as $factKey)
                    {
                        $dataExp[$factKey] = $dataFacts[$factKey];
                    }
                }
                else
                {
                    $dataExp[$recordKey] = $record[$recordKey];
                }
            }
            $ret[] = $dataExp;
        }
        return $ret;
    }

    private function _flattenCrunchbaseData_($arrData)
    {
        $arrRet = array();

        foreach(array_keys($arrData) as $dataSectionKey)
        {
            $dataSection = $arrData[$dataSectionKey];
            $dataSection = \Scooper\object_to_array($dataSection);
            if(is_array($dataSection))
            {
                if(in_array(strtolower($dataSectionKey), $GLOBALS['CB_SUPPORTED_SECONDARY_API_CALLS']) == true)
                {
                    $subItems = $this->__getCrunchbaseAPIDataForSubItems__($arrData[$dataSectionKey]);
                    $itemValue = \Scooper\array_flatten($subItems, "|", \Scooper\C_ARRFLAT_SUBITEM_LINEBREAK__ | \Scooper\C_ARRFLAT_SUBITEM_LINEBREAK__  );
                    $arrRet[$dataSectionKey] = $itemValue;
                }
                else
                {
                    if(\Scooper\is_array_multidimensional($dataSection))
                    {
                        $itemValue = \Scooper\array_flatten($dataSection, "|", \Scooper\C_ARRFLAT_SUBITEM_LINEBREAK__ | \Scooper\C_ARRFLAT_SUBITEM_LINEBREAK__  );
                    }
                    else
                    {
                        $itemValue = $dataSection;
                    }
                    $arrRet[$dataSectionKey] = $itemValue;
                }
            }
            else
            {
                $arrRet[$dataSectionKey] = $arrData[$dataSectionKey];
            }

        }
        return $arrRet;
    }

    protected function getDataFromAPI($strAPIURL, $fFlatten = false, $nextPageURLColumnKey = null, $nMaxPages = C__RETURNS_SINGLE_RECORD, $nPageNumber = 0, $jsonReturnDataKey = null)
    {
        $strKeyedURL = $this->addKeyToURL($strAPIURL);

        return parent::getDataFromAPI($strKeyedURL, $fFlatten, $nextPageURLColumnKey, $nMaxPages, $nPageNumber, $jsonReturnDataKey);
    }

}


