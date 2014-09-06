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
if (!defined('__ROOT__')) { define('__ROOT__', dirname(dirname(__FILE__))); }
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

        if(!isset($GLOBALS['OPTS']['crunchbase_api_id']) || strlen($GLOBALS['OPTS']['crunchbase_api_id']) == 0)
        {
            $GLOBALS['logger']->logLine("Crunchbase API Key was not set.  Excluding Crunchbase data from the results.", \Scooper\C__DISPLAY_ITEM_DETAIL__);
            $this->_fDataIsExcluded_ = C__FEXCLUDE_DATA_YES;
        }

        $GLOBALS['logger']->logLine("Initializing the ". $this->strDataProviderName ." data plugin (ExcludeData=".$this->_fDataIsExcluded_.").", \Scooper\C__DISPLAY_ITEM_DETAIL__);
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
            if(isOptionEqualValue('VERBOSE'))  { $GLOBALS['logger']->logLine("No Crunchbase permanlink value passed.  Cannot lookup other facts.", \Scooper\C__DISPLAY_ITEM_RESULT__);  }
            return null;
        }

        $strAPIURL = "http://api.crunchbase.com/v/2/organization/".$strPermalink;

        //
        // Call the Crunchbase API
        //
        $data = $this->fetchCrunchbaseDataFromAPI($strAPIURL, true, 'data');
        $data['crunchbase_match_accuracy'] = "Exact match on permalink.";
        if(isRecordFieldNotSet($data, 'root_domain')) { $data['root_domain'] = \Scooper\getPrimaryDomainFromUrl($data['homepage_url']); }
        if(isRecordFieldNotSet($data, 'actual_site_url')) { $data['actual_site_url'] = $data['homepage_url']; }
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

            $strQueryVal = null;
            $strPermaLink  = null;
            if(isRecordFieldValidlySet($arrRecordToUpdate['permalink']))
            {
                $nMatchCrunchResult = 1;
            }
            elseif(isRecordFieldValidlySet($arrRecordToUpdate['company_name'])) // use Company Name for CB API query value
            {
                $strQueryVal = "query=" . $arrRecordToUpdate['company_name'];
            }
            elseif(isRecordFieldValidlySet($arrRecordToUpdate['root_domain'])) // use root_domain for CB API query value
            {
                $strQueryVal = "domain=" . $arrRecordToUpdate['root_domain'];
            }
            elseif(isRecordFieldValidlySet($arrRecordToUpdate['computed_domain'])) // use actual_site_url for CB API query value
            {
                $strQueryVal = "domain=" . $arrRecordToUpdate['computed_domain'];
            }
            elseif(isRecordFieldValidlySet($arrRecordToUpdate['actual_site_url'])) // use input_source_url for CB API query value
            {
                $strDomainVal = \Scooper\getPrimaryDomainFromUrl($arrRecordToUpdate['actual_site_url']);
                $strQueryVal = "domain=" . $strDomainVal;
            }
            elseif(isRecordFieldValidlySet($arrRecordToUpdate['input_source_url'])) // use input_source_url for CB API query value
            {
                $strDomainVal = \Scooper\getPrimaryDomainFromUrl($arrRecordToUpdate['input_source_url']);
                $strQueryVal = "domain=" . $strDomainVal;
            }
            else
            {
                $GLOBALS['logger']->logLine("Could not find value to use to query Crunchbase for row.", \Scooper\C__DISPLAY_WARNING__);
                return;
            }

            if(strlen($strQueryVal) > 0)
            {
                $GLOBALS['logger']->logLine("Querying Crunchbase for '". $strQueryVal ."'...", \Scooper\C__DISPLAY_ITEM_START__);

                //
                //  Encode the company name for use in the API call.  Change any space characters to = characters.
                //
                $arrQueryVal = explode("=", $strQueryVal);
                $query_val_urlenc = urlencode($arrQueryVal[1]);
                $query_val_urlenc = preg_replace('/%20/m', '+', $query_val_urlenc);
                $strAPIURL = "http://api.crunchbase.com/v/2/organizations?organization_types=company&".$arrQueryVal[0]."=".$query_val_urlenc;

                //
                // Call the Crunchbase Search API
                //
                $jsonDataTypes = array('json_object' => 'data', 'key' => 'items', 'subkey' => null);
                $retData = $this->fetchCrunchbaseDataFromAPI($strAPIURL, true, $jsonDataTypes);

                $arrMinLev = array("lev_score" => 1000000, "record"=>null);
                if(isset($retData) && count($retData) > 0)
                {
                    for($nIdx = 0; $nIdx < count($retData); $nIdx++)
                    {
                        $lev = levenshtein($retData[$nIdx]['name'], $arrRecordToUpdate['company_name']);
                        if($lev < $arrMinLev['lev_score'])
                        {
                            $arrMinLev['lev_score'] = $lev;
                            $arrMinLev['record'] = $retData[$nIdx];
                        }

//                        if($curCrunchResult['homepage_url'] && strlen($curCrunchResult['homepage_url']) > 0)
//                        {
//                            $curCrunchResult['computed_domain'] = \Scooper\getPrimaryDomainFromUrl($curCrunchResult['homepage_url']);
//                            if(strcasecmp($curCrunchResult['computed_domain'], $arrRecordToUpdate['root_domain']) == 0)
//                            {
//                                // Match found
//                                $nMatchCrunchResult = $nCurResult;
//                                $arrRecordToUpdate['crunchbase_match_accuracy'] = "Crunchbase matched on domain.";
//                                $arrRecordToUpdate = \Scooper\my_merge_add_new_keys($arrRecordToUpdate, $curCrunchResult);
//                                break;
//
//                            }
//                        }
                        if(isset($arrMinLev['record']))
                        {
                            $arrRecordToUpdate['crunchbase_url'] = 'http://www.crunchbase.com/' . $arrMinLev['record']['path'];
                            $arrPathParts = explode("/", $arrMinLev['record']['path']);
                            $arrRecordToUpdate['type'] = $arrPathParts[0];
                            $arrRecordToUpdate['permalink'] = $arrPathParts[1];
                            $arrRecordToUpdate['company_name'] = $arrMinLev['record']['name'];
                            $nMatchCrunchResult = 1;
                        }

                    }
                    if($nMatchCrunchResult == -1 && count($data) > 0)
                    {
                        $GLOBALS['logger']->logLine("Exact match not found in Crunchbase results, so am using first result.", \Scooper\C__DISPLAY_ERROR__);
                        $nMatchCrunchResult = 0;
                        $arrRecordToUpdate['crunchbase_match_accuracy'] = "Crunchbase first search result used; could not find an exact match on domain.";
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

        $GLOBALS['logger']->logLine("Getting Crunchbase entity-specific facts for ".(isRecordFieldNotSet($arrRecordToUpdate, 'name')? $arrRecordToUpdate['permalink'] : $arrRecordToUpdate['name']) , \Scooper\C__DISPLAY_ITEM_DETAIL__);

        if(!isRecordFieldNotSet($arrRecordToUpdate, 'permalink'))
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


    public function fetchCrunchbaseDataFromAPI($strAPIURL, $fFlatten = false, $jsonResultsKey = 'data')
    {
        $arrObjectMatches = array();
        $retItems = null;
        $fSingleMatch = preg_match_all(C__CB_SINGLERECORD_TYPES_REGEX__, $strAPIURL, $arrObjectMatches);

        if($fSingleMatch == true && count($arrObjectMatches) > 0)
        {
            $data = $this->fetchDataFromAPI($strAPIURL, $fFlatten, null,  C__RETURNS_SINGLE_RECORD, null, 'data');
            $retItems = $this->_getSingleItemFromCBData_($data, $fFlatten);
            if(isset($data['relationships']))
                $this->_addRelationshipsToResult_($retItems, $data['relationships']);

        }
        else
        {
            if($jsonResultsKey == null) { $jsonResultsKey = array('json_object' => 'data', 'key' => 'items', 'subkey' => null); }

            $retItems = $this->fetchDataFromAPI($strAPIURL, $fFlatten, 'next_page_url',  C__MAX_CRUNCHBASE_PAGE_DOWNLOADS, null, $jsonResultsKey);
        }
        return $retItems;
    }

    protected function setRecordCountForURL(&$arrAPICallSettings)
    {
        $fSingleMatch = true;
        $arrObjectMatches = array();
        if(isset($arrAPICallSettings['urls_to_fetch']) && isset($arrAPICallSettings['urls_to_fetch'][0]))
        {
            $fSingleMatch = preg_match_all(C__CB_SINGLERECORD_TYPES_REGEX__, $arrAPICallSettings['urls_to_fetch'][0], $arrObjectMatches);
        }

            if($fSingleMatch == true && count($arrObjectMatches) > 0)
            {
                $arrAPICallSettings['multiple_object_result'] = false;
            }
            else
            {
                $arrAPICallSettings['multiple_object_result'] = true;

            }
    }

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
                $retItems = $dataAPI['properties'];  // add the first bucket of data into the return set.
                $this->_addRelationshipsToResult_($retItems, $dataAPI['relationships']);
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


