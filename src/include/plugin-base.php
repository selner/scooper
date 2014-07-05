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
/****         Base Class:  Scooter Site Data Plugin                                                          ****/
/****                                                                                                        ****/
/****************************************************************************************************************/



const C__FEXCLUDE_DATA_YES = 1;
const C__FEXCLUDE_DATA_NO = 0;


abstract class ScooterPluginBaseClass
{
    protected $strDataProviderName = null;
    protected $_fDataIsExcluded_ = C__FEXCLUDE_DATA_NO;
    abstract function getCompanyData($id);





    //
    //  END NEW UNTESTED CODE
    //


    protected  function fetchDataFromAPI($strAPIURL, $fFlatten = false, $nextPageURLColumnKey = null, $nMaxPages = C__RETURNS_SINGLE_RECORD, $nPageNumber = 0, $jsonReturnDataKey = null)
    {
        $arrAPIData = $this->getEmptyDataAPISettings();
        $arrAPIData['result_keys_for_data'] = array('json_object' => $jsonReturnDataKey, 'key' => 0, 'subkey' => null);

        if($nMaxPages == C__RETURNS_SINGLE_RECORD)
        {
            $arrAPIData['result_keys_for_data'] = array('json_object' => $jsonReturnDataKey, 'key' => null, 'subkey' => null);
        }
        else
        {
            $arrAPIData['result_keys_for_next_page'] = array('key' => 1, 'subkey' => $nextPageURLColumnKey);
        }

        $arrAPIData['urls_to_fetch'][] = $strAPIURL;
        $arrAPIData['count_max_pages_to_fetch'] = $nMaxPages;
        $arrAPIData['fetched_data'] = array();
        $arrAPIData['flatten_final_results'] = $fFlatten;

        $this->fetchAPIDataNonRecursive($arrAPIData);
        return $arrAPIData['fetched_data'];

    }

    public function fetchAPIDataNonRecursive(&$arrAPICallSettings)
    {
        if($this->_fDataIsExcluded_ ==  C__FEXCLUDE_DATA_YES)
        {
            throw new ErrorException($this->strDataProviderName . " data has been excluded. Cannot execute this function.");
        }

        while((count($arrAPICallSettings['urls_to_fetch']) > 0) && ($arrAPICallSettings['fetched_total_page_count']< $arrAPICallSettings['count_max_pages_to_fetch']))
        {
            $this->setRecordCountForURL($arrAPICallSettings);
            $this->_fetchAPIDataSingleIteration_($arrAPICallSettings);
        }
    }

    protected function getEmptyDataAPISettings() {
        $ret = array(
            'flatten_final_results' => false,
            'result_keys_for_next_page' => null,
            'multiple_object_result' => true,
            'count_max_pages_to_fetch' => C__MAX_RESULT_PAGES_FETCHED,
            'result_keys_for_data' => null,
            'urls_to_fetch' => null,
            'fetched_total_page_count' => 0,
            'fetched_data' => null,
        );
        $ret['result_keys_for_next_page'] = array('key' => null, 'subkey' => null);
        $ret['result_keys_for_data'] = array('json_object' => null, 'key' => null, 'subkey' => null);
        $ret['fetched_data'] = array();
        $ret['urls_to_fetch'] = array();
        return $ret;
    }

    protected function setRecordCountForURL(&$arrAPICallSettings)
    {
        // do nothing; this is for the child classes if they need it
    }


    protected function _fetchAPIDataSingleIteration_(&$arrAPICallSettings)
    {
        if($arrAPICallSettings == null || $arrAPICallSettings['urls_to_fetch'] == null) return null;

        try
        {
            $strURL = $arrAPICallSettings['urls_to_fetch'][0];
            array_shift($arrAPICallSettings['urls_to_fetch']); // remove the URL we are processing from the list

            $strURL = $this->addKeyToURL($strURL);
            if($GLOBALS['OPTS']['VERBOSE'])  { $GLOBALS['logger']->logLine($this->strDataProviderName . " API Call = ".$strURL, \Scooper\C__DISPLAY_ITEM_DETAIL__); }

            $classAPICall = new \Scooper\ScooperDataAPIWrapper();

            $dataAPI= $classAPICall->getObjectsFromAPICall($strURL, $arrAPICallSettings['result_keys_for_data']['json_object'], \Scooper\C__API_RETURN_TYPE_ARRAY__ , null);
            $retItems = array();

            if($dataAPI == null)
            {
                $GLOBALS['logger']->logLine("API call returned no data.", \Scooper\C__DISPLAY_WARNING__);
                return;
            }

            if(is_object($dataAPI))
            {
                $dataAPI = \Scooper\object_to_array($dataAPI);
            }

            //
            // Let's first get the data set returned with this API call,
            // regardless of whether it's a single or multiple results set
            //
            if($arrAPICallSettings['result_keys_for_data'] != null && strlen($arrAPICallSettings['result_keys_for_data']['key']) > 0)
            {
                $retItems = $dataAPI[$arrAPICallSettings['result_keys_for_data']['key']];
                if(strlen($arrAPICallSettings['result_keys_for_data']['subkey']) > 0)
                {
                    $retItems = $dataAPI[$arrAPICallSettings['result_keys_for_data']['key']][$arrAPICallSettings['result_keys_for_next_page']['subkey']];
                }
            }
            else
            {
                $GLOBALS['logger']->logLine("API caller did not provide the key name of the data to return. Defaulting to returning the full data result.", \Scooper\C__DISPLAY_WARNING__);
                $retItems = $dataAPI;
            }

            //
            // Did the API caller expect multiple results to be returned?
            // If so, let's go process the data to get the other results
            //
            if($arrAPICallSettings['multiple_object_result'] == true && is_array($dataAPI)) // CB  || strcasecmp($dataAPI[1], "Organization") == 0
            {
                if((strlen($arrAPICallSettings['result_keys_for_next_page']['key']) > 0) && $dataAPI[ $arrAPICallSettings['result_keys_for_next_page']['key']] !=null)
                {
                    $keyNextPage = $dataAPI[$arrAPICallSettings['result_keys_for_next_page']['key']];
                    if(strlen($arrAPICallSettings['result_keys_for_next_page']['subkey']) > 0 && $dataAPI[$arrAPICallSettings['result_keys_for_next_page']['key']][$arrAPICallSettings['result_keys_for_next_page']['subkey']] != null)
                    {
                        $keyNextPage = $dataAPI[$arrAPICallSettings['result_keys_for_next_page']['key']][$arrAPICallSettings['result_keys_for_next_page']['subkey']];
                    }

                    if($keyNextPage != null)
                    {
                        $arrAPICallSettings['urls_to_fetch'][] = $keyNextPage;
                    }
                }
            }

            $arrAPICallSettings['fetched_total_page_count']++;
            $arrAPICallSettings['fetched_data'] = array_merge($arrAPICallSettings['fetched_data'], $retItems);

            //
            // If we have no more URLs to fetch OR we've hit the max number of pages to fetch,
            // this is the last call we'll make.  Do any data cleanup, such as flattening, before returning
            // this last time.
            //
            if(count($arrAPICallSettings['urls_to_fetch']) == 0 || $arrAPICallSettings['fetched_total_page_count'] >= $arrAPICallSettings['count_max_pages_to_fetch'])
            {
                if($arrAPICallSettings['flatten'] == true)
                {
                    $GLOBALS['logger']->logLine("Flattening results...", \Scooper\C__DISPLAY_ITEM_DETAIL__);
                    $arrAPICallSettings['fetched_data'] = $this->_flattenData_($arrAPICallSettings['fetched_data']);
                }
            }

        }
        catch ( ErrorException $e )
        {
            $strErr  = 'Error accessing ' . $this->strDataProviderName . ': ' . $e->getMessage();
            $GLOBALS['logger']->logLine ($strErr, \Scooper\C__DISPLAY_ERROR__);
        }
    }

    //
    //  END NEW UNTESTED CODE
    //





    // method declaration
    function addDataToRecord(&$arrRecordToUpdate)
	{
         throw new Exception("addDataToRecord must be defined for any class extending ScooterPluginBaseClass. ");
    }

    private function _getData_($var)
	{
        throw new Exception("_getData_ must be defined for any class extending ScooterPluginBaseClass. ");
    }

    protected function _flattenData_($arrData)
    {
        $arrRet = array();

        foreach(array_keys($arrData) as $dataSectionKey)
        {
            $dataSection = $arrData[$dataSectionKey];
            $dataSection = \Scooper\object_to_array($dataSection);
            if(is_array($dataSection))
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
            else
            {
                $arrRet[$dataSectionKey] = $arrData[$dataSectionKey];
            }

        }
        return $arrRet;
    }



    function readIDsFromCSVFile($strInputFile, $columnKeyName)
    {

        $fileDetails = \Scooper\parseFilePath($strInputFile);
        $classFileIn = new \Scooper\ScooperSimpleCSV($fileDetails['full_file_path'], "r");
        $arrCSVLinkRecords = $classFileIn->readAllRecords(true, null);

        $arrIDs = array_column($arrCSVLinkRecords, $columnKeyName);

        // $this->exportMultipleOrganizationsToCSV($arrIDs, $strOutputFile);
        return $arrIDs;
    }

    public function addDataToRecordViaSearch(&$arrRecordToUpdate, $idColumnKey, $domainMatchKey, $strSearchURLBase,  $jsonResultsKeyName = null, $fExpandArrays = true)
    {

        if($this->_fDataIsExcluded_ == C__FEXCLUDE_DATA_YES) return;

        /****************************************************************************************************************/
        /****                                                                                                        ****/
        /****   Get  data for the record.                                                                  ****/
        /****                                                                                                        ****/
        /****************************************************************************************************************/

        try
        {
            $strMatchType = "";

            //
            // Call the  Search API
            //
            $classAPICall = new \Scooper\ScooperDataAPIWrapper();
            $nMatchResult = -1;
            $nCurResult = 0;

            $arrIDsToLookup = array($idColumnKey => array('index' => null, $idColumnKey => null));

            if(isRecordFieldNullOrNotSet($arrRecordToUpdate[$idColumnKey]) == false)
            {
                $GLOBALS['logger']->logLine("Querying " . $this->strDataProviderName . " for ". $arrRecordToUpdate[$idColumnKey], \Scooper\C__DISPLAY_ITEM_START__);
                // We've got the direct link to the right record, so we can skip over this next section
                $nMatchResult = 1;
                $strMatchType =  "Searching " . $this->strDataProviderName . " by " . $idColumnKey . " of " . $arrRecordToUpdate[$idColumnKey] . ".";
            }
            else
            {
                $GLOBALS['logger']->logLine("Querying " . $this->strDataProviderName . " for ". $arrRecordToUpdate['company_name'], \Scooper\C__DISPLAY_ITEM_START__);
                if(isRecordFieldNullOrNotSet($arrRecordToUpdate['company_name']) == true)
                {
                    $strMatchType =  "Could not search " . $this->strDataProviderName . ": no company name.";
                    // throw new Exception("Error: " . $strMatchType);
                }
                else
                {

                    //
                    //  Encode the company name for use in the API call.  Change any space characters to = characters.
                    //
                    $company_name_urlenc = urlencode($arrRecordToUpdate['company_name']);
                    $company_name_urlenc = preg_replace('/%20/m', '+', $company_name_urlenc);
                    $url = $strSearchURLBase . $company_name_urlenc;


                    if($GLOBALS['OPTS']['VERBOSE'])  { $GLOBALS['logger']->logLine($this->strDataProviderName . "API call=".$url, \Scooper\C__DISPLAY_ITEM_DETAIL__);  }
                    $arrSearchResultsRecords = $classAPICall->getObjectsFromAPICall($url, $jsonResultsKeyName, \Scooper\C__API_RETURN_TYPE_ARRAY__, null);

                    if($GLOBALS['OPTS']['VERBOSE'])  { $GLOBALS['logger']->logLine($this->strDataProviderName . "returned ".count($arrSearchResultsRecords )." results for ". $arrRecordToUpdate['company_name'].". ", \Scooper\C__DISPLAY_ITEM_DETAIL__);  }

                    if($arrSearchResultsRecords != null && count($arrSearchResultsRecords ) > 0)
                    {
                        foreach ($arrSearchResultsRecords  as $curResult)
                        {
                            if($curResult[$domainMatchKey] && strlen($curResult[$domainMatchKey]) > 0)
                            {
                                $curResult['computed_domain'] = \Scooper\getPrimaryDomainFromUrl($curResult[$domainMatchKey]);
                                if((strcasecmp($curResult['computed_domain'], $arrRecordToUpdate['root_domain']) == 0) ||
                                    (strcasecmp($curResult[$nameColumnKey], $arrRecordToUpdate['company_name']) == 0))
                                {
                                    // Match found
                                    $nMatchResult = $nCurResult;
                                    $strMatchType =  $this->strDataProviderName . " matched on name or domain exactly.";
                                    $arrRecordToUpdate = \Scooper\my_merge_add_new_keys($arrRecordToUpdate, $curResult);
                                    break;
                                }
                                else

                                {

                                }
                            }
                        }
                        if($nMatchResult == -1 && count($arrSearchResultsRecords) > 0)
                        {
                            $strMatchType =   $this->strDataProviderName . " first search result used; could not find an exact match on domain.";
                            $GLOBALS['logger']->logLine($strMatchType, \Scooper\C__DISPLAY_WARNING__);
                            $nMatchResult = 0;
                        }
                    }

                }
            }
            //
            // If we didn't get a match, note it in the record
            //
            if($nMatchResult == -1)
            {
                $GLOBALS['logger']->logLine("Company not found in " . $this->strDataProviderName . ".", \Scooper\C__DISPLAY_ERROR__);
            }
            else
            {
                //
                // Otherwise, go get the full entity facts for that record
                //
                $arrCompanyData = $this->getCompanyData($arrRecordToUpdate[$idColumnKey]);
                if($arrCompanyData != null)
                {
                    $arrRecordToUpdate = \Scooper\my_merge_add_new_keys($arrRecordToUpdate, $arrCompanyData);
                }

            }

            addToAccuracyField($arrRecordToUpdate, $strMatchType );

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


    protected function getDataFromAPI($strAPIURL, $fFlatten = false, $nextPageURLColumnKey = null, $nMaxPages = C__RETURNS_SINGLE_RECORD, $nPageNumber = 0, $jsonReturnDataKey = null)
    {
        try
        {
            if($GLOBALS['OPTS']['VERBOSE'])  { $GLOBALS['logger']->logLine($this->strDataProviderName . " API Call = ".$strAPIURL, \Scooper\C__DISPLAY_ITEM_DETAIL__); }

            $classAPICall = new \Scooper\ScooperDataAPIWrapper();

            $dataAPI= $classAPICall->getObjectsFromAPICall($strAPIURL, $jsonReturnDataKey, \Scooper\C__API_RETURN_TYPE_ARRAY__ , null);
            if(is_object($dataAPI))
            {
                $dataAPI = \Scooper\object_to_array($dataAPI);
            }
            $retItems = array();

            $fRootCall = ($nPageNumber == 0);

            if($nMaxPages == C__RETURNS_SINGLE_RECORD)
            {
                if($dataAPI != null && is_array($dataAPI[0]))
                {
                    $retItems = $dataAPI[0];  // add the first bucket of data into the return set.
                }
                else if($dataAPI != null)
                {
                    $retItems = $dataAPI;  // add the first bucket of data into the return set.
                }
                else
                {
                    $GLOBALS['logger']->logLine('$dataAPI is null, but shouldn\'t have been.', \Scooper\C__DISPLAY_ERROR__);
                }
            }
            else // multiple item API call
            {
                if(is_array($dataAPI) && !is_array($dataAPI[0]) && !is_object($dataAPI[1]))
                {
                    $retItems = $dataAPI; // add the first bucket of data into the return set.
                }
                else
                {
                    $retItems = $dataAPI[0];
                    $dataAPINextPage = $dataAPI[1];
                    if(is_object($dataAPINextPage))
                    {
                        $dataAPINextPage = \Scooper\object_to_array($dataAPINextPage);
                    }

                    if($nextPageURLColumnKey != null && $dataAPINextPage[$nextPageURLColumnKey] !=null)
                    {
                        if($dataAPINextPage != null && $nPageNumber < $nMaxPages &&
                            $dataAPINextPage[$nextPageURLColumnKey] !=null) // we're in paging mode
                        {
                            $recursItems = $this->getDataFromAPI($dataAPINextPage[$nextPageURLColumnKey], $fFlatten, $nextPageURLColumnKey, $nMaxPages, $nPageNumber+1, $jsonReturnDataKey);

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
                    $GLOBALS['logger']->logLine("Total results: " . count($retItems), \Scooper\C__DISPLAY_ITEM_DETAIL__);


                if($fFlatten)
                {
                    $GLOBALS['logger']->logLine("Flattening results...", \Scooper\C__DISPLAY_ITEM_DETAIL__);
                    $retItems = $this->_flattenData_($retItems);
                }
            }

            return $retItems;
        }
        catch ( ErrorException $e )
        {
            $strErr  = 'Error accessing ' . $this->strDataProviderName . ': ' . $e->getMessage();
            $GLOBALS['logger']->logLine ($strErr, \Scooper\C__DISPLAY_ERROR__);
            addToAccuracyField($arrRecordToUpdate, $strErr );
        }


    }



    public function writeAPIResultsToFile($strAPICallURL, $detailsFile, $strNextPageURLKey = null, $nMaxPages = C__RETURNS_SINGLE_RECORD, $jsonKeyToReturn = null)
    {
        if($this->_fDataIsExcluded_ == C__FEXCLUDE_DATA_YES)
        {
            throw new ErrorException($this->strDataProviderName . " data has been excluded. Cannot execute this function.");
        }

        $GLOBALS['logger']->logLine("Starting " . $this->strDataProviderName . " Data export for API call:" .$strAPICallURL, \Scooper\C__DISPLAY_SECTION_START__);

        //
        // Query the API for our data
        //
        $GLOBALS['logger']->logLine("Getting data...", \Scooper\C__DISPLAY_NORMAL__);
        $strKeyedURL = $this->addKeyToURL($strAPICallURL);
        $arrDataAPI = $this->getDataFromAPI($strKeyedURL , true, $strNextPageURLKey, $nMaxPages, 0, $jsonKeyToReturn);

        $GLOBALS['logger']->logLine("Starting file write ...", \Scooper\C__DISPLAY_NORMAL__);

        $this->writeDataToFile($arrDataAPI, $detailsFile);

        return $arrDataAPI;

    }


    public function writeDataToFile($arrData, $detailsFile)
    {
        if($this->_fDataIsExcluded_ ==  C__FEXCLUDE_DATA_YES)
        {
            throw new ErrorException($this->strDataProviderName . " data has been excluded. Cannot execute this function.");
        }

        $classOutputFile = new \Scooper\ScooperSimpleCSV($detailsFile['full_file_path'], "w");

        //
        // Make sure our data is an array so we can write it as expected
        //
        $GLOBALS['logger']->logLine("Bundling data for writing to file...", \Scooper\C__DISPLAY_NORMAL__);
        if(!\Scooper\is_array_multidimensional($arrData))
        {
            $outRecord[] = $arrData;
        }
        else
        {
            $outRecord = $arrData;
        }

        //
        // Write the CSV out to file
        //
        $GLOBALS['logger']->logLine("Writing to file: " .$detailsFile['full_file_path'] , \Scooper\C__DISPLAY_NORMAL__);
        $classOutputFile->writeArrayToCSVFile($outRecord);

        $GLOBALS['logger']->logLine("Export complete." , \Scooper\C__DISPLAY_SUMMARY__);

    }




    public function exportMultipleOrganizationsToCSV($arrIDs, $strOutputFile)
    {
        $arrOrgData = Array();
        foreach ($arrIDs as $strID)
        {
            $arrOrgData[] = $this->getCompanyData($strID);
        }

        $classOutputVCData = new \Scooper\ScooperSimpleCSV($strOutputFile, "w");
        $classOutputVCData->writeArrayToCSVFile($arrOrgData);

    }

    protected function getKeyURLString()
    {
        return "";  // used by child classes
    }

    protected function addKeyToURL($strURL)
    {
        $retURL = $strURL;
        $keyString =  $this->getKeyURLString();
        if(strlen($keyString) > 0)
        {
            $fFoundQuestion = (substr_count($strURL, "?") > 0);
            $retURL = $strURL . ($fFoundQuestion ? "&" : "?") . $keyString;
        }

        return $retURL;
    }

    function _expandArrays_(&$arrToExpand)
    {

        $values = $arrToExpand;
        $keys = array_keys($arrToExpand);
        $nIndex = 0;
        foreach($values as $val)
        {
            if(is_array($val))
            {
                $arrNewValue = array();
                if(count($val) > 1)
                    $sep = " | ";
                else

                    $sep = "";

                foreach($val as $subItem)
                {
                    if(is_array($subItem))
                    {
                        $arrNewValue[] = implode(" ", \Scooper\array_flatten_sep(".", $subItem));
                    }
                    else
                    {
                        $arrNewValue[] = $subItem;
                    }
                }
                $strNewValue = implode($sep, $arrNewValue);
                $arrToExpand[$keys[$nIndex]] =$strNewValue;

            }

            $nIndex++;
        }
    }


}

function getDataTypeFromString($strType)
{
    $ret = 'UNKNOWN';

    switch (strtolower($strType))
    {
        case 'company_name';
            $ret  = C__LOOKUP_DATATYPE_BASICFACTS__;
            break;

        case 'company name':
        case 'company names':
        case 'names':
        case 'company':
            $ret  = C__LOOKUP_DATATYPE_NAME__;
            break;

        case 'company url':
        case 'url':
        case 'urls':
        case 'input_source_url':
            $ret = C__LOOKUP_DATATYPE_URL__;

            break;
    }

    return $ret;

}
