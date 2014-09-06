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



/****************************************************************************************************************/
/****                                                                                                        ****/
/****         Basic Plugin Class                                                                         ****/
/****                                                                                                        ****/
/****************************************************************************************************************/
class BasicFactsPluginClass extends ScooterPluginBaseClass
{
    private $_data_type = null;
    protected $_fDataIsExcluded_ = C__FEXCLUDE_DATA_NO;



    function __construct($data_type)
    {
        $this->strDataProviderName = "Basic Web Facts";
        $this->setDataType($data_type);
        // if($fVarExclude == 1) { $this->_fDataIsExcluded_ = C__FEXCLUDE_DATA_YES; }
    }

    function getAllColumns()
    {
        return array(
            'company_name'=>'<not set>',
            'result_accuracy_warnings'=>'<not set>',
            'actual_site_url'=>'<not set>',
            'crunchbase_match_accuracy'=>'<not set>',
            'input_source_url'=>'<not set>',
            'root_domain'=>'<not set>',
        );
    }


    function getCompanyData($id)
    {
        throw new Exception("getCompanyData not implemented for " . get_class($this));

    }


    function setDataType($data_type)
    {
        $this->_data_type = $data_type;
    }

    function addDataToMultipleRecords($arrDataLoaded, $strOutputFile = null)
    {


        $arrRecordsToProcess =  array();

        $nRow = 0;
        if(!isset($arrDataLoaded) && !is_array($arrDataLoaded)) return null;

        foreach ($arrDataLoaded as $strCurInputDataRecord)
        {
            $arrRecordsToProcess[] = getEmptyFullRecordArray();
            $valFirstField = '';


            switch ( $this->_data_type)
            {
                case C__LOOKUP_DATATYPE_URL__:
                    $valFirstField  = array_shift($strCurInputDataRecord);
                    $arrRecordsToProcess[$nRow]['input_source_url'] =  $valFirstField;
                    break;

                case C__LOOKUP_DATATYPE_BASICFACTS__:
                case C__LOOKUP_DATATYPE_NAME__:
                default:
                    $valFirstField  = array_shift($strCurInputDataRecord);
                    $arrRecordsToProcess[$nRow]['company_name'] = $valFirstField;

                    $keys = array_keys($strCurInputDataRecord);
                    if(isset($keys[0]) && getDataTypeFromString($keys[0]) == C__LOOKUP_DATATYPE_URL__)
                    {
                        $arrRecordsToProcess[$nRow]['input_source_url'] =  array_shift($strCurInputDataRecord);
                    }
                    break;
            }

            if(!$this->_data_type)
            {
                throw new Exception("Source data type was not set when BasicFactsPluginClass was initialized.");
            }

            if(!strlen($arrRecordsToProcess[$nRow]['company_name']) > 0 && !strlen(($arrRecordsToProcess[$nRow]['input_source_url'])>0))
            {
                exit ("Error processing company lookup.  Invalid source file data entered. Header row did not start with either 'company name' or 'url'. " . PHP_EOL . "Exited." . PHP_EOL);

            }

            $arrRecordsToProcess[$nRow] = \Scooper\my_merge_add_new_keys($arrRecordsToProcess[$nRow],$strCurInputDataRecord );
            $arrRecordsToProcess[$nRow]['result_accuracy_warnings'] = ""; // clear out any previous issues

            $GLOBALS['logger']->logLine("Getting basic facts for ".$valFirstField, \Scooper\C__DISPLAY_ITEM_START__);

            $this->addDataToRecord($arrRecordsToProcess[$nRow]);

            if($strOutputFile != null && $nRow % C__RECORD_CHUNK_SIZE__ == 0)
            {
                $classFileOut = new \Scooper\ScooperSimpleCSV($strOutputFile, "w");
                $classFileOut->writeArrayToCSVFile($arrRecordsToProcess );

            }

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

        if(!isRecordFieldNotSet($arrRecordToUpdate, 'company_name') && isRecordFieldNotSet($arrRecordToUpdate, 'input_source_url'))
        {
            $strSimplifiedCompName = $this->_simplifyCompanyNameForDomainURL_($arrRecordToUpdate['company_name']);
            $arrRecordToUpdate['input_source_url'] = 'http://www.'.$strSimplifiedCompName.'.com';
            addToAccuracyField($arrRecordToUpdate, "Input source URL computed from company name; please verify result is accurate.") ;
        }

        //
        // Go load the basic website facts for the URL value we have on this row
        //
        if(!isRecordFieldNotSet($arrRecordToUpdate, 'actual_site_url') || !isRecordFieldNotSet($arrRecordToUpdate, 'input_source_url'))
        {
            $arrNewBasicSiteFactsRecord = $this->_getData_($arrRecordToUpdate);
            $arrRecordToUpdate = \Scooper\my_merge_add_new_keys($arrRecordToUpdate, $arrNewBasicSiteFactsRecord  );
        }
        else if(isRecordFieldNotSet($arrRecordToUpdate, 'input_source_url') &&  strcasecmp($this->_data_type, C__LOOKUP_DATATYPE_URL__) == 0 )
        {
            throw new Exception("You should not get here for input source files that are URL lists.");
            exit("You should not get here for input source files that are URL lists.");
        }



        //
        // If after loading the basic website data we still don't have a company name, construct one from the actual site domain if we got
        // one of those at least
        //
        if(isRecordFieldNotSet($arrRecordToUpdate, 'company_name') && !isRecordFieldNotSet($arrRecordToUpdate, 'actual_site_url'))
        {
            $arrRecordToUpdate['company_name'] = \Scooper\getPrimaryDomainFromUrl($arrRecordToUpdate['actual_site_url'], false);
            // capitalize the every word in the name we got back
            if(strlen($arrRecordToUpdate['company_name']) > 0) { $arrRecordToUpdate['company_name'] = ucfirst($arrRecordToUpdate['company_name']); }
        }



        return;
    }

    private function _getData_($var)
    {
        $classAPIWrap = new \Scooper\ScooperDataAPIWrapper();

        $curRecord = \Scooper\array_copy($var);

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
        try
        {
            $curl_obj = $classAPIWrap->cURL($strURLToCheck );

            if(!$this->_isRealSite_($curl_obj))
            {
                addToAccuracyField($curRecord, $strErrMsg);
            }
            else
            {
                $curRecord['actual_site_url'] = $curl_obj['actual_site_url'];
                $curRecord['root_domain']  = \Scooper\getPrimaryDomainFromUrl($curRecord['actual_site_url']);
            }

        }
        catch ( ErrorException $e )
        {
            $GLOBALS['logger']->logLine("Error: ". $e->getMessage()."\r\n", \Scooper\C__DISPLAY_ERROR__);
            addToAccuracyField($arrRecordToUpdate, 'ERROR -- PLEASE RETRY');
        }
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
