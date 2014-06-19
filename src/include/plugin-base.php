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
define('__ROOT__', dirname(dirname(__FILE__)));
require_once(__ROOT__ . '/scooper_common/scooper_common.php');




const C__FEXCLUDE_DATA_YES = 1;
const C__FEXCLUDE_DATA_NO = 0;


abstract class ScooterPluginBaseClass
{
    protected $strDataProviderName = null;
    protected $_fDataIsExcluded_ = C__FEXCLUDE_DATA_NO;
    abstract function getCompanyData($id);


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
            $dataSection = object_to_array($dataSection);
            if(is_array($dataSection))
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
            else
            {
                $arrRet[$dataSectionKey] = $arrData[$dataSectionKey];
            }

        }
        return $arrRet;
    }



    function readIDsFromCSVFile($strInputFile, $columnKeyName, $strOutputFile)
    {

        $fileDetails = parseFilePath($strInputFile);
        $classFileIn = new ClassScooperSimpleCSVFile($fileDetails['full_file_path'], "r");
        $arrCSVLinkRecords = $classFileIn->readAllRecords(true, array($columnKeyName));

        $arrIDs = array_column($arrCSVLinkRecords, $columnKeyName);

        $this->exportMultipleOrganizationsToCSV($arrIDs, $strOutputFile);
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

            //
            // Call the  Search API
            //
            $classAPICall = new ClassScooperAPIWrapper();
            $nMatchResult = -1;
            $nCurResult = 0;

            $arrIDsToLookup = array($idColumnKey => array('index' => null, $idColumnKey => null));

            if(isRecordFieldNullOrNotSet($arrRecordToUpdate[$idColumnKey]) == false)
            {
                __debug__printLine("Querying " . $this->strDataProviderName . " for ". $arrRecordToUpdate[$idColumnKey], C__DISPLAY_ITEM_START__);
                // We've got the direct link to the right record, so we can skip over this next section
                $nMatchResult = 1;
                $strMatchType =  "Could not search " . $this->strDataProviderName . ": no company name.";
            }
            else
            {
                __debug__printLine("Querying " . $this->strDataProviderName . " for ". $arrRecordToUpdate['company_name'], C__DISPLAY_ITEM_START__);
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


                    if($GLOBALS['OPTS']['VERBOSE'])  { __debug__printLine($this->strDataProviderName . "API call=".$url, C__DISPLAY_ITEM_DETAIL__);  }
                    $arrSearchResultsRecords = $classAPICall->getObjectsFromAPICall($url, $jsonResultsKeyName, C__API_RETURN_TYPE_ARRAY__, null);

                    if($GLOBALS['OPTS']['VERBOSE'])  { __debug__printLine($this->strDataProviderName . "returned ".count($arrSearchResultsRecords )." results for ". $arrRecordToUpdate['company_name'].". ", C__DISPLAY_ITEM_DETAIL__);  }

                    if($arrSearchResultsRecords != null && count($arrSearchResultsRecords ) > 0)
                    {
                        foreach ($arrSearchResultsRecords  as $curResult)
                        {
                            if($curResult[$domainMatchKey] && strlen($curResult[$domainMatchKey]) > 0)
                            {
                                $curResult['computed_domain'] = getPrimaryDomainFromUrl($curResult[$domainMatchKey]);
                                if((strcasecmp($curResult['computed_domain'], $arrRecordToUpdate['root_domain']) == 0) ||
                                    (strcasecmp($curResult[$nameColumnKey], $arrRecordToUpdate['company_name']) == 0))
                                {
                                    // Match found
                                    $nMatchResult = $nCurResult;
                                    $strMatchType =  $this->strDataProviderName . " matched on name or domain exactly.";
                                    $arrRecordToUpdate = my_merge_add_new_keys($arrRecordToUpdate, $curResult);
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
                            __debug__printLine($strMatchType, C__DISPLAY_WARNING__);
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
                __debug__printLine("Company not found in " . $this->strDataProviderName . ".", C__DISPLAY_ERROR__);
            }
            else
            {
                //
                // Otherwise, go get the full entity facts for that record
                //
                $arrCompanyData = $this->getCompanyData($arrRecordToUpdate[$idColumnKey]);
                $arrRecordToUpdate = my_merge_add_new_keys($arrRecordToUpdate, $arrCompanyData);

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
            __debug__printLine ($strErr, C__DISPLAY_ERROR__);
            addToAccuracyField($arrRecordToUpdate, $strErr );
        }
    }


    protected function getDataFromAPI($strAPIURL, $fFlatten = false, $nextPageURLColumnKey = null, $nMaxPages = C__RETURNS_SINGLE_RECORD, $nPageNumber = 0, $jsonReturnDataKey = null)
    {
        try
        {
            if($GLOBALS['OPTS']['VERBOSE'])  { __debug__printLine($this->strDataProviderName . " API Call = ".$strAPIURL, C__DISPLAY_ITEM_DETAIL__); }

            $classAPICall = new ClassScooperAPIWrapper();

            $dataAPI= $classAPICall->getObjectsFromAPICall($strAPIURL, $jsonReturnDataKey, C__API_RETURN_TYPE_OBJECT__, null);
            $dataAPI = object_to_array($dataAPI);
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
                    __debug__printLine('$dataAPI is null, but shouldn\'t have been.', C__DISPLAY_ERROR__);
                }
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
                    if($nextPageURLColumnKey != null && $dataAPINextPage[$nextPageURLColumnKey] !=null)
                    {
                        if($dataAPINextPage != null && $nPageNumber < $nMaxPages &&
                            $dataAPINextPage[$nextPageURLColumnKey] !=null) // we're in paging mode
                        {
                            $recursItems = $this->getDataFromAPI($dataAPINextPage[$nextPageURLColumnKey], $fFlatten, $nMaxPages, $nPageNumber+1);

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
                    $retItems = $this->_flattenData_($retItems);
                }
            }

            return $retItems;
        }
        catch ( ErrorException $e )
        {
            $strErr  = 'Error accessing ' . $this->strDataProviderName . ': ' . $e->getMessage();
            __debug__printLine ($strErr, C__DISPLAY_ERROR__);
            addToAccuracyField($arrRecordToUpdate, $strErr );
        }


    }



    public function writeAPIResultsToFile($strAPICallURL, $detailsFile, $nMaxPages = C__RETURNS_SINGLE_RECORD)
    {
        if($this->_fDataIsExcluded_ == C__FEXCLUDE_DATA_YES)
        {
            throw new ErrorException($this->strDataProviderName . " data has been excluded. Cannot execute this function.");
        }

        __debug__printLine("Starting " . $this->strDataProviderName . " Data export for API call:" .$strAPICallURL, C__DISPLAY_SECTION_START__);

        //
        // Query the API for our data
        //
        __debug__printLine("Getting data...", C__DISPLAY_NORMAL__);
        $arrDataAPI = $this->getDataFromAPI($strAPICallURL , true, $nMaxPages);

        __debug__printLine("Starting file write ...", C__DISPLAY_NORMAL__);

        $this->writeDataToFile($arrDataAPI, $detailsFile);

        return $arrDataAPI;

    }


    public function writeDataToFile($arrData, $detailsFile)
    {
        if($this->_fDataIsExcluded_ ==  C__FEXCLUDE_DATA_YES)
        {
            throw new ErrorException($this->strDataProviderName . " data has been excluded. Cannot execute this function.");
        }

        $classOutputFile = new ClassScooperSimpleCSVFile($detailsFile['full_file_path'], "w");

        //
        // Make sure our data is an array so we can write it as expected
        //
        __debug__printLine("Bundling data for writing to file...", C__DISPLAY_NORMAL__);
        if(!is_array_multidimensional($arrData))
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
        __debug__printLine("Writing to file: " .$detailsFile['full_file_path'] , C__DISPLAY_NORMAL__);
        $classOutputFile->writeArrayToCSVFile($outRecord);

        __debug__printLine("Export complete." , C__DISPLAY_SUMMARY__);

    }




    public function exportMultipleOrganizationsToCSV($arrIDs, $strOutputFile)
    {
        $arrOrgData = Array();
        foreach ($arrIDs as $strID)
        {
            $arrOrgData[] = $this->getCompanyData($strID);
        }

        $classOutputVCData = new ClassScooperSimpleCSVFile($strOutputFile, "w");
        $classOutputVCData->writeArrayToCSVFile($arrOrgData);

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
                        $arrNewValue[] = implode(" ", array_flatten_sep(".", $subItem));
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
