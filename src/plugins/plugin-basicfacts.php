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


function addToAccuracyField(&$arrRecord, $strValueToAdd)
{
    $strNewValue = "";

    if(isRecordFieldNullOrNotSet($arrRecord['result_accuracy']) == true)
    {
        $strNewValue = $strValueToAdd;
    }
    else
    {
        $strNewValue = $arrRecord['result_accuracy'] . " | ". $strValueToAdd;
    }
    $arrRecord['result_accuracy'] = $strNewValue;
}


/****************************************************************************************************************/
/****                                                                                                        ****/
/****         Quantcast Plugin Class                                                                         ****/
/****                                                                                                        ****/
/****************************************************************************************************************/
class BasicFactsPluginClass extends SitePluginBaseClass
{
    private $_data_type = null;

    function __construct($data_type)
    {
        $this->setDataType($data_type);
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
                case 'LOOKUP_BY_BASIC_FACTS':

                    if((count($strCurInputDataRecord) >= 5))
                    {
                        $arrRecordsToProcess[$nRow]['company_name'] = $strCurInputDataRecord[0];
                        $arrRecordsToProcess[$nRow]['actual_site_url'] = $strCurInputDataRecord[4];
                        $arrRecordsToProcess[$nRow]['effective_domain'] = $strCurInputDataRecord[3];
                        $arrRecordsToProcess[$nRow]['result_accuracy'] = $strCurInputDataRecord[2];
                        $arrRecordsToProcess[$nRow]['input_source_url'] = $arrRecordsToProcess[$nRow]['actual_site_url'];
                        __debug__printLine("Found full basic data in row#".$nRow.": ".$arrRecordsToProcess[$nRow]['company_name'].': '.$arrRecordsToProcess[$nRow]['actual_site_url'], C__DISPLAY_ITEM_START__);
                        continue;
                    }
                    else
                    {
                        throw new Exception('Error processing input CSV.  Does not have valid basic facts columns.');
                    }
                    break;
                case 'LOOKUP_BY_NAME':
                    $arrRecordsToProcess[$nRow]['company_name'] = $strCurInputDataRecord[0];
                    $strMessage = $arrRecordsToProcess[$nRow]['company_name'];
                    //
                    // If there is a second column in the input data, let's assume that's a URL column and add it to the input source data URL field
                    //
                    if(count($strCurInputDataRecord) >= 2)
                    {
                           $arrRecordsToProcess[$nRow]['input_source_url'] = $strCurInputDataRecord[0];
                           $strMessage = $strMessage . " (URL: " . $arrRecordsToProcess[$nRow]['input_source_url'];
                    }

                    __debug__printLine("Getting basic data for row#".$nRow.": ".$strMessage, C__DISPLAY_ITEM_START__);

                    break;

                case 'LOOKUP_BY_URL':
                    $arrRecordsToProcess[$nRow]['input_source_url'] = strtolower($strCurInputDataRecord[0]);
                    __debug__printLine("Getting basic data for row#".$nRow.": ".$arrRecordsToProcess[$nRow]['input_source_url'], C__DISPLAY_ITEM_START__);
                    break;

                default:
                    echo "Error processing company lookup.  Invalid source file data entered. Header row did not start with either 'company name' or 'url'. " . PHP_EOL . "Exited." . PHP_EOL;
                    exit(-1);
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
        if(isRecordFieldNullOrNotSet($arrRecordToUpdate['input_source_url']) != true && isRecordFieldNullOrNotSet($arrRecordToUpdate['company_name']) == true)
        {
            if( strcasecmp($this->_data_type, 'LOOKUP_BY_URL') ==0 ) throw new Exception("You should not get here for url source inputs.");
            $arrRecordToUpdate['input_source_url'] = 'http://www.' . simplifyStringForURL($arrRecordToUpdate['company_name']) . '.com';
        }

        //
        // Go load the basic website facts for the URL value we have on this row
        //
        $arrRecordToUpdate = my_merge_add_new_keys($arrRecordToUpdate, $this->_getData_($arrRecordToUpdate));

        //
        // If after loading the basic website data we still don't have a company name, construct one from the actual site domain if we got
        // one of those at least
        //
        if(!isRecordFieldNullOrNotSet($arrRecordToUpdate['company_name']) && isRecordFieldNullOrNotSet($arrRecordToUpdate['actual_site_url']))
        {
            if( strcasecmp($this->_data_type, 'LOOKUP_BY_NAME') == 0 ) { throw new Exception("You should not get here for company name inputs."); }
            $arrRecordToUpdate['company_name'] = getPrimaryDomain($arrRecordToUpdate['actual_site_url'], false);
            // capitalize the every word in the name we got back
            if(strlen($arrRecordToUpdate['company_name']) > 0) { $arrRecordToUpdate['company_name'] = ucfirst($arrRecordToUpdate['company_name']); }
        }



        return;
    }

   private function _getData_($var)
	{

        $curRecord = array_copy($var);

        //
        // Check domain still exists; get the actual full URL that is returned
        //
        $curl_obj = curlWrap($curRecord['input_source_url']);
        if(!$this->_isRealSite_($curl_obj))
        {
            addToAccuracyField($curRecord, "Source URL was not successfully resolved to an actual site.");
        }
        else
        {
            addToAccuracyField($curRecord, "Source URL found.");
        }

        $curRecord['actual_site_url'] = $curl_obj['actual_site_url'];
        $curRecord['effective_domain']  = getPrimaryDomain($curRecord['actual_site_url']);

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





}
?>