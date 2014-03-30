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
/****         Basic Plugin Class                                                                         ****/
/****                                                                                                        ****/
/****************************************************************************************************************/
class BasicFactsPluginClass extends ScooterPluginBaseClass
{
    private $_data_type = null;
    private $_fDataIsExcluded_ = C__FEXCLUDE_DATA_NO;



    function __construct($data_type)
    {
        $this->strDataProviderName = "Basic Web Facts";
        $this->setDataType($data_type);
        // if($fVarExclude == 1) { $this->_fDataIsExcluded_ = C__FEXCLUDE_DATA_YES; }
    }

    function setDataType($data_type)
    {
        $this->_data_type = $data_type;
    }

    function addDataToMultipleRecords($arrDataLoaded)
    {
        if(!$this->_data_type)
        {
            throw new Exception("Source data type was not set when BasicFactsPluginClass was initialized.");
        }

        $arrRecordsToProcess =  array();

        $nRow = 0;
        foreach ($arrDataLoaded as $strCurInputDataRecord)
        {
            $arrRecordsToProcess[] = getEmptyFullRecordArray();
            switch ( $this->_data_type)
            {
                case C__LOOKUP_DATATYPE_BASICFACTS__:

                    if((count($strCurInputDataRecord) >= 5))
                    {
                        $arrRecordsToProcess[$nRow]['company_name'] = $strCurInputDataRecord[0];
                        $arrRecordsToProcess[$nRow]['actual_site_url'] = $strCurInputDataRecord[4];
                        $arrRecordsToProcess[$nRow]['effective_domain'] = $strCurInputDataRecord[3];
                        $arrRecordsToProcess[$nRow]['result_accuracy_warnings'] = $strCurInputDataRecord[2];
                        $arrRecordsToProcess[$nRow]['input_source_url'] = $arrRecordsToProcess[$nRow]['actual_site_url'];
                        __debug__printLine("Found full basic data in row#".$nRow.": ".$arrRecordsToProcess[$nRow]['company_name'].': '.$arrRecordsToProcess[$nRow]['actual_site_url'], C__DISPLAY_ITEM_START__);
                        continue;
                    }
                    else
                    {
                        throw new Exception('Error processing input CSV.  Does not have valid basic facts columns.');
                    }
                    break;


                case C__LOOKUP_DATATYPE_NAME__:
                    $arrRecordsToProcess[$nRow]['company_name'] = $strCurInputDataRecord[0];
                    $strMessage = $arrRecordsToProcess[$nRow]['company_name'];
                    //
                    // If there is a second column in the input data, let's assume that's a URL column and add it to the input source data URL field
                    //
                    if(count($strCurInputDataRecord) >= 2 and !isRecordFieldNullOrNotSet($strCurInputDataRecord[1])) // it's not "N/A" or empty
                    {
                        assert(strlen($strCurInputDataRecord[1]) > 0);
                        $arrRecordsToProcess[$nRow]['input_source_url'] = $strCurInputDataRecord[1];
                    }
                    else
                    {
                        //
                        // The input URL was missing or invalid.  Set it to N/A so we are sure later it was invalid
                        //
                        $arrRecordsToProcess[$nRow]['input_source_url'] = 'N/A';
                    }

                    __debug__printLine("Getting basic data for row#".$nRow.": ".$strMessage, C__DISPLAY_ITEM_START__);

                    break;

                case C__LOOKUP_DATATYPE_URL__:
                    $arrRecordsToProcess[$nRow]['input_source_url'] = strtolower($strCurInputDataRecord[0]);
                    __debug__printLine("Getting basic data for row#".$nRow.": ".$arrRecordsToProcess[$nRow]['input_source_url'], C__DISPLAY_ITEM_START__);
                    break;

                default:
                    exit ("Error processing company lookup.  Invalid source file data entered. Header row did not start with either 'company name' or 'url'. " . PHP_EOL . "Exited." . PHP_EOL);
                    break;
            }

            $this->addDataToRecord($arrRecordsToProcess[$nRow]);

            $nRow++;
        }

        return $arrRecordsToProcess;


    }

    function addDataToRecord(&$arrRecordToUpdate)
    {

        //
        // If we don't yet have an URL for the company but we do have a company name,
        // let's guess at the URL and use that
        //
        if(!isRecordFieldNullOrNotSet($arrRecordToUpdate['company_name']) && isRecordFieldNullOrNotSet($arrRecordToUpdate['input_source_url']))
        {
            $strSimplifiedCompName = $this->_simplifyCompanyNameForDomainURL_($arrRecordToUpdate['company_name']);
            $arrRecordToUpdate['input_source_url'] = 'http://www.'.$strSimplifiedCompName.'.com';
            addToAccuracyField($arrRecordToUpdate, "Input source URL computed from company name; please verify result is accurate.") ;
        }

        //
        // Go load the basic website facts for the URL value we have on this row
        //
        if(!isRecordFieldNullOrNotSet($arrRecordToUpdate['actual_site_url']) || !isRecordFieldNullOrNotSet($arrRecordToUpdate['input_source_url']))
        {
            $arrNewBasicSiteFactsRecord = $this->_getData_($arrRecordToUpdate);
            $arrRecordToUpdate = my_merge_add_new_keys($arrRecordToUpdate, $arrNewBasicSiteFactsRecord  );
        }
        else if(isRecordFieldNullOrNotSet($arrRecordToUpdate['input_source_url']) &&  strcasecmp($this->_data_type, C__LOOKUP_DATATYPE_URL__) == 0 )
        {
            throw new Exception("You should not get here for input source files that are URL lists.");
            exit("You should not get here for input source files that are URL lists.");
        }



        //
        // If after loading the basic website data we still don't have a company name, construct one from the actual site domain if we got
        // one of those at least
        //
        if(isRecordFieldNullOrNotSet($arrRecordToUpdate['company_name']) && !isRecordFieldNullOrNotSet($arrRecordToUpdate['actual_site_url']))
        {
            $arrRecordToUpdate['company_name'] = getPrimaryDomain($arrRecordToUpdate['actual_site_url'], false);
            // capitalize the every word in the name we got back
            if(strlen($arrRecordToUpdate['company_name']) > 0) { $arrRecordToUpdate['company_name'] = ucfirst($arrRecordToUpdate['company_name']); }
        }



        return;
    }

    private function _getData_($var)
    {
        $classAPIWrap = new APICallWrapperClass();

        $curRecord = array_copy($var);

        //
        // Check domain still exists; get the actual full URL that is returned
        //
        $strURLToCheck = $curRecord['input_source_url'];
        $strErrMsg = "Could not find website for input_source_url.";
        if(!isRecordFieldNullOrNotSet($curRecord['actual_site_url']))
        {
            $strURLToCheck = $curRecord['actual_site_url'];
            $strErrMsg = "Could not find website for actual_site_url.";
        }

        $curl_obj = $classAPIWrap->cURL($strURLToCheck );

        if(!$this->_isRealSite_($curl_obj))
        {
            addToAccuracyField($curRecord, $strErrMsg);
        }
        else
        {
            $curRecord['actual_site_url'] = $curl_obj['actual_site_url'];
            $curRecord['effective_domain']  = getPrimaryDomain($curRecord['actual_site_url']);
        }

        $arrRet = my_merge_add_new_keys( $var, $curRecord );

        return $curRecord;
    }

    private function _isRealSite_($curl_object)
    {

        if($curl_object['error_number'] != 0)
        {
            return false;
        }

        $regexMatch = "/dir.yahoo.com/";
        $ret =	preg_match($regexMatch, $curl_object['input_url'], $arrMatches);
        if($ret > 0)
        {
            return false;
        }

        // check if we got our ERROR tag
        $regexMatch = "/^---ERROR---/";
        $ret =	preg_match($regexMatch, $curl_object['output'], $arrMatches);
        if($ret > 0)
        {
            return false;
        }

        // check if we got the CenturyLink search helper page
        $regexMatch = "/<title>Website\sSuggestions<\/title>/i";
        $ret =	preg_match($regexMatch, $curl_object['output'], $arrMatches);
        if($ret > 0)
        {
            return false;
        }

        // check if we got a domain squatter result
        // i.e. 	<title>Chicagoflame.com</title>
        // check if we got the CenturyLink search helper page
        // check if we got our ERROR tag
        $regexMatch = "/<title>Website\sSuggestions<\/title>/i";
        $ret =	preg_match($regexMatch, $curl_object['output'], $arrMatches);
        if($ret > 0)
        {
            return false;
        }

        return true;
    }


    private function _simplifyCompanyNameForDomainURL_($strCompName)
    {

        $retValue = strtolower($strCompName);
        $retValue = preg_replace('/\.com/', "", $retValue);
        $retValue = preg_replace( "/[^a-z0-9-]/i", "", $retValue );

        return $retValue;
    }





}
?>