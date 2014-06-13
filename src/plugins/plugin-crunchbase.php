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
require_once dirname(__FILE__) . '/../include/plugin-base.php';

define('__ROOT__', dirname(dirname(__FILE__)));
require_once(__ROOT__.'/include/plugin-base.php');

const C__MAX_CRUNCHBASE_PAGE_DOWNLOADS = 2;
const C__RETURNS_SINGLE_RECORD = -1;
$GLOBALS['CB_SUPPORTED_SECONDARY_API_CALLS'] = array("acquisitions", "funding_rounds");

/****************************************************************************************************************/
/****                                                                                                        ****/
/****          Crunchbase Plugin Class                                                                       ****/
/****                                                                                                        ****/
/****************************************************************************************************************/
class CrunchbasePluginClass extends ScooterPluginBaseClass
{

    private $_fDataIsExcluded_ = C__FEXCLUDE_DATA_NO;
    private $strDataProviderName  = 'Crunchbase';


    function __construct($fExcludeThisData)
	{
        if($fExcludeThisData == 1) { $this->_fDataIsExcluded_ = C__FEXCLUDE_DATA_YES; }

        if(strlen($GLOBALS['OPTS']['crunchbase_api_id']) == 0 || $GLOBALS['OPTS']['crunchbase_api_id'] == "")
        {
            __log__("Crunchbase API Key was not set.  Excluding Crunchbase data from the results.", C__LOGLEVEL_ERROR__);
            $this->_fDataIsExcluded_ = C__FEXCLUDE_DATA_YES;
        }

        __debug__printLine("Initializing the ". $this->strDataProviderName ." data plugin (ExcludeData=".$this->_fDataIsExcluded_.").", C__DISPLAY_NORMAL__);
	}






    public function writeCrunchbaseOrganizationToFile($strPermalink, $detailsCSVFile)
    {
        $data = $this->getCrunchbaseOrganization($strPermalink);
        $this->writeCrunchbaseDataToFile($data, $detailsCSVFile);
    }

    public function getCrunchbaseOrganization($strPermalink)
    {

        if(!$strPermalink || strlen($strPermalink) == 0)
        {
            if($GLOBALS['OPTS']['VERBOSE'])  { __debug__printLine("No Crunchbase permanlink value passed.  Cannot lookup other facts.", C__DISPLAY_ITEM_RESULT__);  }
            return null;
        }

        if(!$strPermalink || strlen($strPermalink) == 0)
        {
            if($GLOBALS['OPTS']['VERBOSE'])  { __debug__printLine("No Crunchbase permanlink value passed.  Cannot lookup other facts.", C__DISPLAY_ITEM_RESULT__);  }
            return null;
        }
        $strAPIURL = "http://api.crunchbase.com/v/2/organization/".$strPermalink;

        //
        // Call the Crunchbase Search API
        //
        $data = $this->getDataFromCrunchbaseAPI($strAPIURL, true, C__RETURNS_SINGLE_RECORD, null);
        $data['crunchbase_match_accuracy'] = "Exact match on permalink.";
        if(isRecordFieldNullOrNotSet($data['root_domain'])) { $data['root_domain'] = getPrimaryDomainFromUrl($data['homepage_url']); }
        if(isRecordFieldNullOrNotSet($data['actual_site_url'])) { $data['actual_site_url'] = $data['homepage_url']; }
        return $data;


    }



    public function writeCrunchbaseAPICallResultstoFile($strAPICallURL, $detailsFile, $nMaxPages = C__RETURNS_SINGLE_RECORD)
    {
        if($GLOBALS['OPTS']['crunchbase_api_id'] == null || $GLOBALS['OPTS']['crunchbase_api_id']=="")
        {
            throw new ErrorException("Crunchbase API ID is required to call this function.");
        }

        __debug__printLine("Starting Crunchbase Data export for API call:" .$strAPICallURL, C__DISPLAY_SECTION_START__);

        //
        // Query the API for our data
        //
        __debug__printLine("Getting data...", C__DISPLAY_NORMAL__);
        $arrCrunchAPIData = $this->getDataFromCrunchbaseAPI($strAPICallURL , true, $nMaxPages);

        __debug__printLine("Starting file write ...", C__DISPLAY_NORMAL__);

        $this->writeCrunchbaseDataToFile($arrCrunchAPIData, $detailsFile);

        return $arrCrunchAPIData;

    }


    public function writeCrunchbaseDataToFile($arrCrunchAPIData, $detailsFile)
    {
        if($GLOBALS['OPTS']['crunchbase_api_id'] == null || $GLOBALS['OPTS']['crunchbase_api_id']=="")
        {
            throw new ErrorException("Crunchbase API ID is required to call this function.");
        }

        $classOutputFile = new SimpleScooterCSVFileClass($detailsFile['full_file_path'], "w");

        //
        // Make sure our data is an array so we can write it as expected
        //
        __debug__printLine("Bundling data for writing to file...", C__DISPLAY_NORMAL__);
        if(!is_array_multidimensional($arrCrunchAPIData))
        {
            $outRecord[] = $arrCrunchAPIData;
        }
        else
        {
            $outRecord = $arrCrunchAPIData;
        }

        //
        // Write the CSV out to file
        //
        __debug__printLine("Writing to file: " .$detailsFile['full_file_path'] , C__DISPLAY_NORMAL__);
        $classOutputFile->writeArrayToCSVFile($outRecord);

        __debug__printLine("Export complete." , C__DISPLAY_SUMMARY__);

    }




    public function exportMultipleOrganizationsToCSV($arrPermalinks, $strOutputFile)
    {
        $arrOrgData = Array();
        foreach ($arrPermalinks as $strPermalink)
        {
            $arrOrgData[] = $this->getCrunchbaseOrganization($strPermalink);
        }

        $classOutputVCData = new SimpleScooterCSVFileClass($strOutputFile, "w");
        $classOutputVCData->writeArrayToCSVFile($arrOrgData);

    }


    function readPermalinksFromCSVFile($strInputFile, $strOutputFile)
    {

        $fileDetails = parseFilePath($strInputFile);
        $classFileIn = new SimpleScooterCSVFileClass($fileDetails['full_file_path'], "r");
        $arrCSVLinkRecords = $classFileIn->readAllRecords(true, array('permalink'));

        $arrPermalinks = array_column($arrCSVLinkRecords, 'permalink');

        $this->exportMultipleOrganizationsToCSV($arrPermalinks, $strOutputFile);
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

		$arrRecordToUpdate = my_merge_add_new_keys($arrRecordToUpdate, array('crunchbase_match_accuracy' => '<not set>'));

        //
        // Call the Crunchbase Search API
        //
        $classAPICall = new APICallWrapperClass();
        $nMatchCrunchResult = -1;
        $nCurResult = 0;

        $arrPermalinksToLookup = array('permalink' => array('index' => null, 'permalink' => null));

        if(isRecordFieldNullOrNotSet($arrRecordToUpdate['permalink']) == false)
        {
            __debug__printLine("Querying Crunchbase for ". $arrRecordToUpdate['permalink'], C__DISPLAY_ITEM_START__);
            // We've got the direct link to the right record in Crunchbase, so we
            // can skip over this next section
            $nMatchCrunchResult = 1;
        }
        else
        {
            __debug__printLine("Querying Crunchbase for ". $arrRecordToUpdate['company_name'], C__DISPLAY_ITEM_START__);
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

               try
               {
                    if($GLOBALS['OPTS']['VERBOSE'])  { __debug__printLine("Crunchbase API call=".$url, C__DISPLAY_ITEM_DETAIL__);  }
                   $arrCrunchBaseSearchResultsRecords = $classAPICall->getObjectsFromAPICall($url, 'results', C__API_RETURN_TYPE_ARRAY__, null);

                    if($GLOBALS['OPTS']['VERBOSE'])  { __debug__printLine("Crunchbase returned ".count($arrCrunchBaseSearchResultsRecords)." results for ". $arrRecordToUpdate['company_name'].". ", C__DISPLAY_ITEM_DETAIL__);  }

                    if($arrCrunchBaseSearchResultsRecords && count($arrCrunchBaseSearchResultsRecords) > 0)
                    {
                        foreach ($arrCrunchBaseSearchResultsRecords as $curCrunchResult)
                        {
                            if($curCrunchResult['homepage_url'] && strlen($curCrunchResult['homepage_url']) > 0)
                            {
                                $curCrunchResult['computed_domain'] = getPrimaryDomainFromUrl($curCrunchResult['homepage_url']);
                                if(strcasecmp($curCrunchResult['computed_domain'], $arrRecordToUpdate['root_domain']) == 0)
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


               }
               catch ( ErrorException $e )
               {
                   print ("Error: ". $e->getMessage()."\r\n" );
                   addToAccuracyField($arrRecordToUpdate, 'ERROR ACCESSING CRUNCHBASE -- PLEASE RETRY');
                   $arrRecordToUpdate['crunchbase_match_accuracy'] = 'ERROR';
               }

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

       if($fExpandArrays == true)
       {
           $this->_expandArrays_($arrRecordToUpdate);
       }

    }




    private function _addCrunchbaseEntityFacts_(&$arrRecordToUpdate)
    {
        if($this->_fDataIsExcluded_ == C__FEXCLUDE_DATA_YES) return;

        __debug__printLine("Getting Crunchbase ".$arrRecordToUpdate['namespace'] ." entity-specific facts for ".(isRecordFieldNullOrNotSet($arrRecordToUpdate['name'])? $arrRecordToUpdate['permalink'] : $arrRecordToUpdate['name']) , C__DISPLAY_ITEM_DETAIL__);

        if($arrRecordToUpdate['permalink'] && strlen($arrRecordToUpdate['permalink']) > 0)
        {

            $arrCrunchEntityData = $this->getCrunchbaseOrganization($arrRecordToUpdate['permalink']);
            if(is_array($arrCrunchEntityData))
            {
                $arrRecordToUpdate = my_merge_add_new_keys($arrRecordToUpdate, $arrCrunchEntityData);
            }
        }
        else
        {
            $strErr = "Could not lookup entity-specific facts for ".$arrRecordToUpdate['name']. ".  Invalid permalink or namespace value was given.";
            __debug__printLine($strErr , C__DISPLAY_ERROR__);
            addToAccuracyField($arrRecordToUpdate, $strErr);
        }

    }



    private function _getURLWithKey_($strURL)
    {
        $fFoundQuestion = (substr_count($strURL, "?") > 0);
        return $strURL . ($fFoundQuestion ? "&" : "?") . "user_key=".$GLOBALS['OPTS']['crunchbase_api_id'];
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
                    $dataFacts = $this->getDataFromCrunchbaseAPI($strAPIURL, true);
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
            $dataSection = object_to_array($dataSection);
            if(is_array($dataSection))
            {
                if(in_array(strtolower($dataSectionKey), $GLOBALS['CB_SUPPORTED_SECONDARY_API_CALLS']) == true)
                {
                    $subItems = $this->__getCrunchbaseAPIDataForSubItems__($arrData[$dataSectionKey]);
                    $itemValue = array_flatten($subItems, "|", C_ARRFLAT_SUBITEM_SEPARATOR__ | C_ARRFLAT_SUBITEM_LINEBREAK__  );
                    $arrRet[$dataSectionKey] = $itemValue;
                }
                else
                {
                    if(is_array_multidimensional($dataSection))
                    {
                        $itemValue = array_flatten($dataSection, "|", C_ARRFLAT_SUBITEM_SEPARATOR__ | C_ARRFLAT_SUBITEM_LINEBREAK__  );
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

    private function _addRelationshipsToResult_(&$arrData, $relationships)
    {
        $arrData = object_to_array($arrData);
        $arrRelationships = object_to_array($relationships);
        if(is_array($arrRelationships) && count($arrRelationships) > 0)
        {
            $retArrAdditions = array();
            foreach(array_keys($arrRelationships) as $relation)
            {
                $relationItems = object_to_array($arrRelationships[$relation]);
                $retArrAdditions[$relation] = $relationItems['items'];
            }
            $arrData = my_merge_add_new_keys($arrData, $this->_flattenCrunchbaseData_($retArrAdditions));
        }



    }


    private function getDataFromCrunchbaseAPI($strAPIURL, $fFlatten = false, $nMaxPages = C__RETURNS_SINGLE_RECORD, $nPageNumber = 0)
    {
        $retItems = array();

        //
        // Add the user key to the API call
        //
        $strKeyedAPIURL = $this->_getURLWithKey_($strAPIURL);
        if($GLOBALS['OPTS']['VERBOSE'])  { __debug__printLine("Crunchbase API Call = ".$strKeyedAPIURL, C__DISPLAY_ITEM_DETAIL__); }

        $classAPICall = new APICallWrapperClass();

        $dataAPI= $classAPICall->getObjectsFromAPICall($strKeyedAPIURL, 'data', C__API_RETURN_TYPE_OBJECT__, null);
        $retItems = array();

        $fRootCall = ($nPageNumber == 0);

        if($nMaxPages == C__RETURNS_SINGLE_RECORD)
        {
            array_shift($dataAPI);
            array_shift($dataAPI);
            $retItems = $dataAPI[0];  // add the first bucket of data into the return set.
            $this->_addRelationshipsToResult_($retItems, $dataAPI[1]);
        }
        else // mulitple item API call
        {
            if(is_array($dataAPI) && !is_array($dataAPI[1]) && !is_object($dataAPI[1]))
            {
                $retItems = $dataAPI; // add the first bucket of data into the return set.
            }
            else
            {
                $retItems = $dataAPI[0];
                $dataAPINextPage = object_to_array($dataAPI[1]);
                if($dataAPINextPage['next_page_url'] !=null)
                {
                    if($dataAPINextPage != null && $nPageNumber < $nMaxPages &&
                         $dataAPINextPage['next_page_url'] !=null) // we're in paging mode
                    {
                        $recursItems = $this->getDataFromCrunchbaseAPI($dataAPINextPage['next_page_url'], $fFlatten, $nMaxPages, $nPageNumber+1);

                        if(is_array($recursItems))
                        {
                            $retItems = array_merge($retItems, $recursItems);
                        }
                    }

                }
                else // relationships mode, so add those
                {
                    $retItems = array_merge($retItems, $dataAPI[1]);
                }
            }
        }
        if($fRootCall )
        {
            if($nMaxPages != C__RETURNS_SINGLE_RECORD)
                __debug__printLine("Total results: " . count($retItems), C__DISPLAY_ITEM_DETAIL__);


            if($fFlatten)
            {
                __debug__printLine("Flattening results...", C__DISPLAY_ITEM_DETAIL__);
                $retItems = $this->_flattenCrunchbaseData_($retItems);
            }
        }

        return $retItems;

    }




}


