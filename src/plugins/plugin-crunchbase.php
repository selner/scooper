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

const C__MAX_CRUNCHBASE_PAGE_DOWNLOADS = 20;

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

    private function _getURLWithKey_($strURL)
    {
        $fFoundQuestion = (substr_count($strURL, "?") > 0);
        return $strURL . ($fFoundQuestion ? "&" : "?") . "user_key=".$GLOBALS['OPTS']['crunchbase_api_id'];
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

        if(isRecordFieldNullOrNotSet($arrRecordToUpdate['permalink']) == false)
        {
            __debug__printLine("Querying Crunchbase for ". $arrRecordToUpdate['permalink'], C__DISPLAY_ITEM_START__);
            // We've got the direct link to the right record in Crunchbase, so we
            // can skip over this next section
            $nMatchCrunchResult = 1;
            $arrRecordToUpdate['crunchbase_match_accuracy'] = "Exact match on permalink.";

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
                                if(strcasecmp($curCrunchResult['computed_domain'], $arrRecordToUpdate['effective_domain']) == 0)
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

        if(($arrRecordToUpdate['permalink'] && strlen($arrRecordToUpdate['permalink']) > 0) &&
            ($arrRecordToUpdate['namespace'] && strlen($arrRecordToUpdate['namespace']) > 0))
        {
            $arrCrunchEntityData = $this->_getCrunchbaseOrganizationFacts_($arrRecordToUpdate['permalink']);

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

    private function __getCBDataForItems__($dataItems)
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


        $arrKeys = array_keys($arrData);
        if(is_numeric($arrKeys[0]))
        {
            $arrData = array_addseq_key($arrData);

        }

        foreach(array_keys($arrData) as $dataSectionKey)
        {
            $dataSection = $arrData[$dataSectionKey];

            if(is_array($dataSection))
            {
                foreach(array_keys($dataSection) as $item)
                {
                    $itemValue = $dataSection[$item];
                    if(is_array($itemValue))
                    {
                        $itemKey = $item;
                        if(is_numeric($item))
                        {
                            $itemKey = "item-" . $item;
                        }
                        $fCallCBDataAPI = false;
                        switch($item)
                        {
                            case "acquisitions":
                            case "funding_rounds":
                                $itemValue = $this->__getCBDataForItems__($dataSection[$item]['items']);
                                break;
                        }



                        if(is_array($itemValue['items']))
                            $itemValue = $itemValue['items'];

                        $itemValue = array_flatten($itemValue, "|", C_ARRFLAT_SUBITEM_SEPARATOR__ | C_ARRFLAT_SUBITEM_LINEBREAK__  );
                    }

                    $arrRet[$dataSectionKey][$item] = $itemValue;
                }
            }
        }
        return $arrRet;
    }

    private function getDataFromCrunchbaseAPI($strAPIURL, $fFlatten = false, $nMaxPages = 2)
    {
        $retItems = array();

        //
        // Call the Crunchbase Search API
        //
        $strKeyedAPIURL = $this->_getURLWithKey_($strAPIURL);
        if($GLOBALS['OPTS']['VERBOSE'])  { __debug__printLine("Crunchbase API Call = ".$strKeyedAPIURL, C__DISPLAY_ITEM_DETAIL__); }

        $classAPICall = new APICallWrapperClass();
        $nPage = 0;

        $dataAPI = $classAPICall->getObjectsFromAPICall($strKeyedAPIURL, 'data', C__API_RETURN_TYPE_ARRAY__, null);
        $retItems = $dataAPI;
        if(is_array($dataAPI) && count($dataAPI[0]) > 1)
        {
            $retItems = array_addseq_key($dataAPI[0]);
            $strNextURL = $dataAPI[1]['next_page_url'];
            while($nPage <= $nMaxPages && strlen($strNextURL) > 0 )
            {
                $nPage = $nPage + 1;
                $strKeyedAPIURL = $this->_getURLWithKey_($strNextURL);
                __debug__printLine("Getting next page of results data from: = ".$strKeyedAPIURL, C__DISPLAY_ITEM_DETAIL__);
                $dataAPINextPage = $classAPICall->getObjectsFromAPICall($strKeyedAPIURL, 'data', C__API_RETURN_TYPE_ARRAY__, null);
                $strNextURL = "";
                if(is_array($dataAPINextPage) && count($dataAPINextPage[0]) > 1)
                {
                    $retSubItems = array_addseq_key($dataAPINextPage[0]);
                    $retItems = array_merge($retItems, $retSubItems);
                    if(count($dataAPINextPage)>1)
                    {
                        $strNextURL = $dataAPINextPage[1]['next_page_url'];
                    }
                }
                else
                {
                    $retItems = array_merge($retItems, $dataAPINextPage);
                }
            }
        }
        if($fFlatten)
        {
            $retItems = $this->_flattenCrunchbaseData_($retItems);
        }
        return $retItems;

    }



    private function _getCrunchbaseOrganizationFacts_($strPermalink)
	{

		if(!$strPermalink || strlen($strPermalink) == 0)
		{
			if($GLOBALS['OPTS']['VERBOSE'])  { __debug__printLine("No Crunchbase permanlink value passed.  Cannot lookup other facts.", C__DISPLAY_ITEM_RESULT__);  }
			return null;
        }

        //
		//  Encode the company name for use in the API call.  Change any space characters to = characters.
		// 

        $strAPIURL = "http://api.crunchbase.com/v/2/organization/".$strPermalink."?user_key=".$GLOBALS['OPTS']['crunchbase_api_id'];
        if($GLOBALS['OPTS']['VERBOSE'])  { __debug__printLine("Crunchbase API Call = ".$strAPIURL, C__DISPLAY_ITEM_DETAIL__); }

        //
        // Call the Crunchbase Search API
        //


        $classAPICall = new APICallWrapperClass();
        $GLOBALS['OPTS']['VERBOSE'] = true;
        $arrCrunchEntityData = $classAPICall->getObjectsFromAPICall($strAPIURL, 'data', C__API_RETURN_TYPE_ARRAY__, null);
        $arrFinalCrunchArray = $this->_flattenCrunchbaseData_($arrCrunchEntityData);

        return $arrFinalCrunchArray;
		
	}



    public function exportCrunchbaseAPICalltoFile($strAPICallURL, $detailsFile)
    {
        $arrCrunchAPIData = array();
        if($GLOBALS['OPTS']['crunchbase_api_id'] == null || $GLOBALS['OPTS']['crunchbase_api_id']=="")
        {
            throw new ErrorException("Crunchbase API ID is required to call this function.");
        }

        $classOutputFile = new SimpleScooterCSVFileClass($detailsFile['full_file_path'], "w");

        __debug__printLine("Starting Crunchbase Data export for API call:" .$strAPICallURL, C__DISPLAY_SECTION_START__);
        __debug__printLine("Getting data...", C__DISPLAY_NORMAL__);
        $arrCrunchAPIData = $this->getDataFromCrunchbaseAPI($strAPICallURL , true, C__MAX_CRUNCHBASE_PAGE_DOWNLOADS );

        __debug__printLine("Bundling data for writing to file...", C__DISPLAY_NORMAL__);
        $outRecord = $arrCrunchAPIData;
//        $outRecord = my_merge_add_new_keys($outRecord, $arrCrunchAPIData );
        if(!is_array($outRecord) && !is_array($outRecord[0])) { $outRecord[] = $outRecord; }
        __debug__printLine("Writing to file: " .$detailsFile['full_file_path'] , C__DISPLAY_NORMAL__);
        $classOutputFile->writeArrayToCSVFile(array_values($outRecord));
        __debug__printLine("Export complete." , C__DISPLAY_SUMMARY__);

        return $arrCrunchAPIData;

    }




    public function getDataFromPermalinks($arrPermalinks, $strFileOutPath)
    {
        if($GLOBALS['OPTS']['crunchbase_api_id'] == null || $GLOBALS['OPTS']['crunchbase_api_id']=="")
        {
            throw new ErrorException("Crunchbase API ID is required to call this function.");
        }

        $strOutVCDetails = parseFilePath($strFileOutPath);
        $classOutputVCData= new SimpleScooterCSVFileClass($strFileOutPath, "w");

        $arrCompanyRecords = Array();

        $retCompanies = Array();
        foreach ($arrPermalinks as $vcRecord)
        {
            $arrCompanyRecords   = getEmptyFullRecordArray();
            $arrCompanyRecords  ['permalink'] = $vcRecord;
            $arrCompanyRecords  ['namespace'] = "company";

            $this->addDataToRecord($arrCompanyRecords, true);
            $arrCompanyRecords['company_name'] = $arrCompanyRecords['name'];
            $arrCompanyRecords['actual_site_url'] = $arrCompanyRecords['homepage_url'];

            $retCompanies[] = $arrCompanyRecords;
            $classOutputVCData->writeArrayToCSVFile($retCompanies);
        }

    }


    function dumpCompanyInfoFromListOfPermalinks($strInputFile, $strOutputFile)
    {

        $strArgErrs = __check_args__();
        if(strlen($strArgErrs) > 0) __log__($strArgErrs, C__LOGLEVEL_WARN__);

        $fileDetails = parseFilePath($strInputFile);

        $classCB = new CrunchbasePluginClass(false);

        $classFileIn= new SimpleScooterCSVFileClass($fileDetails['full_file_path'], "r");
        $arrPermaLinkRows= $classFileIn->readAllRecords(true, array('permalink'));

        $arrLinks = array();
        foreach($arrPermaLinkRows as $vcrecord)
        {
            $arrLinks[] = $vcrecord['permalink'];

        }

        $classCB->getDataFromPermalinks($arrLinks , $strOutputFile);
    }



}


