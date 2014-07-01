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
/****         Moz Plugin Class                                                                               ****/
/****                                                                                                        ****/
/****************************************************************************************************************/
require_once(__ROOT__.'/include/plugin-base.php');


class MozPluginClass extends ScooterPluginBaseClass
{
    private $_arrMozBulkAPIResults_ = null;
    protected $_fDataIsExcluded_ = C__FEXCLUDE_DATA_NO;
    protected  $strDataProviderName  = 'Moz.com';

    function __construct($fExcludeThisData, $arrAllRecords)
    {
        if($fExcludeThisData == 1) { $this->_fDataIsExcluded_ = C__FEXCLUDE_DATA_YES; }

        $GLOBALS['logger']->logLine("Instantiating a ". $this->strDataProviderName ." data plugin (ExcludeData=".$this->_fDataIsExcluded_.").", \Scooper\C__DISPLAY_ITEM_DETAIL__);

            $this->_batchQueryMozAPI_($arrAllRecords);
    }


    function getCompanyData($id)
    {
        throw new Exception("getCompanyData not implemented for " . get_class($this));

    }

    function getAllColumns()
    {
        return array(
            'ut' => '<not set>',
            'uu' => '<not set>',
            'ufq' => '<not set>',
            'upl' => '<not set>',
            'ueid' => '<not set>',
            'feid' => '<not set>',
            'peid' => '<not set>',
            'ujid' => '<not set>',
            'uifq' => '<not set>',
            'uipl' => '<not set>',
            'uid' => '<not set>',
            'fid' => '<not set>',
            'pid' => '<not set>',
            'umrp' => '<not set>',
            'umrr' => '<not set>',
            'fmrp' => '<not set>',
            'fmrr' => '<not set>',
            'pmrp' => '<not set>',
            'pmrr' => '<not set>',
            'utrp' => '<not set>',
            'utrr' => '<not set>',
            'ftrp' => '<not set>',
            'ftrr' => '<not set>',
            'ptrp' => '<not set>',
            'ptrr' => '<not set>',
            'uemrp' => '<not set>',
            'uemrr' => '<not set>',
            'fejp' => '<not set>',
            'fejr' => '<not set>',
            'pejp' => '<not set>',
            'pejr' => '<not set>',
            'fjp' => '<not set>',
            'fjr' => '<not set>',
            'pjp' => '<not set>',
            'pjr' => '<not set>',
            'us' => '<not set>',
            'fuid' => '<not set>',
            'puid' => '<not set>',
            'fipl' => '<not set>',
            'upa' => '<not set>',
            'pda' => '<not set>',
            'ued' => '<not set>',
            'ulc' => '<not set>',
            'lrid' => '<not set>',
            'lsrc' => '<not set>',
            'ltgt' => '<not set>',
            'lufeid' => '<not set>',
            'lufejp' => '<not set>',
            'lufejr' => '<not set>',
            'lufid' => '<not set>',
            'lufipl' => '<not set>',
            'lufjp' => '<not set>',
            'lufjr' => '<not set>',
            'lufmrp' => '<not set>',
            'lufmrr' => '<not set>',
            'luftrp' => '<not set>',
            'luftrr' => '<not set>',
            'lufuid' => '<not set>',
            'lupda' => '<not set>',
            'lupdar' => '<not set>',
            'lupeid' => '<not set>',
            'lupejp' => '<not set>',
            'lupejr' => '<not set>',
            'lupid' => '<not set>',
            'lupjp' => '<not set>',
            'lupjr' => '<not set>',
            'lupmrp' => '<not set>',
            'lupmrr' => '<not set>',
            'luptrp' => '<not set>',
            'luptrr' => '<not set>',
            'lupuid' => '<not set>',
            'luueid' => '<not set>',
            'luuemrp' => '<not set>',
            'luuemrr' => '<not set>',
            'luufq' => '<not set>',
            'luuid' => '<not set>',
            'luuifq' => '<not set>',
            'luuipl' => '<not set>',
            'luujid' => '<not set>',
            'luumrp' => '<not set>',
            'luumrr' => '<not set>',
            'luupa' => '<not set>',
            'luupar' => '<not set>',
            'luupl' => '<not set>',
            'luurrid' => '<not set>',
            'luus' => '<not set>',
            'luut' => '<not set>',
            'luutrp' => '<not set>',
            'luutrr' => '<not set>',
            'luuu' => '<not set>',
            'pdar' => '<not set>',
            'upar' => '<not set>',
            'ur' => '<not set>',
            'urid' => '<not set>',
            'urrid' => '<not set>',
            'usch' => '<not set>',
        );
    }

    public function addDataToRecord(&$arrRecordToUpdate)
    {
        if($this->_fDataIsExcluded_ == C__FEXCLUDE_DATA_YES) return;

        if($this->_arrMozBulkAPIResults_ == null)
        {
            throw new Exception("MozPlugin was not initialized correctly.");
        }

        $GLOBALS['logger']->logLine("Looking for a match in Moz data for ".$arrRecordToUpdate['company_name'], \Scooper\C__DISPLAY_ITEM_START__);

        if(!isRecordFieldNullOrNotSet($arrRecordToUpdate['input_source_url']) && isRecordFieldNullOrNotSet($arrRecordToUpdate['root_domain']))
        {
            $arrRecordToUpdate['root_domain'] = \Scooper\getPrimaryDomainFromUrl($arrRecordToUpdate['input_source_url']);

        }

        $fMatchFound = false;
        if(!isRecordFieldNullOrNotSet($arrRecordToUpdate['root_domain']))
        {
            $recordStringToMatch =  $arrRecordToUpdate['root_domain'];
            $jsonMozRecord = $this->_arrMozBulkAPIResults_[$recordStringToMatch];
            if($jsonMozRecord != null)  // check the results for a value keyed with the domain
            {
                $arrMozRecord = json_decode($jsonMozRecord, true);
                $arrRecordToUpdate = \Scooper\my_merge_add_new_keys($arrRecordToUpdate, $arrMozRecord);
                return;
            }
            else // otherwise, search the results for one with a matching domain value in it
            {
                foreach($this->_arrMozBulkAPIResults_ as $mozresult)
                {
                    $arrMozRecord = json_decode($mozresult, true);
                    $tempDomain = $arrMozRecord['upl'];
                    if($tempDomain == "" && strlen($arrMozRecord['uu'])>0)
                    {
                        $tempDomain = $arrMozRecord['uu'];
                    }
                    $tempDomain = \Scooper\getPrimaryDomainFromUrl($tempDomain);
//                    if($tempDomain[strlen($tempDomain)-1] = "/") { $tlempDomain = substr($tempDomain, 1, strlen($tempDomain)); }
                    if(strcasecmp($tempDomain, $recordStringToMatch) == 0 )
                    {
                        $arrRecordToUpdate = \Scooper\my_merge_add_new_keys($arrRecordToUpdate, $arrMozRecord);
                        return;
                    }
                }

            }
            addToAccuracyField($arrRecordToUpdate, 'No Moz match found for root_domain value.');
            $GLOBALS['logger']->logLine("Moz: Match not found for ".$recordStringToMatch, \Scooper\C__DISPLAY_ERROR__);
        }
        else
        {
            addToAccuracyField($arrRecordToUpdate, 'Unable to search Moz data; no root_domain found for company.');
            if(!$fMatchFound) { $GLOBALS['logger']->logLine("'Unable to search Moz data; no root_domain found for company = ".$arrRecordToUpdate['company_name'], \Scooper\C__DISPLAY_ERROR__);  }
        }

        return;
    }

    private function _getData_($var)
    {
         throw new Exception("_getData_ is not defined for MozPluginClass.");
    }

    private function _batchQueryMozAPI_($arrRecordsToQuery)
    {
        if($this->_fDataIsExcluded_ == C__FEXCLUDE_DATA_YES) return;

        // From https://github.com/seomoz/SEOmozAPISamples/blob/master/php/batching_urls_sample.php

        // you can obtain you access id and secret key here: http://www.seomoz.org/api/keys
        $accessID = $GLOBALS['OPTS']['moz_access_id'];
        $secretKey = $GLOBALS['OPTS']['moz_secret_key'];

        // Set your expires for several minutes into the future.
        // Values excessively far in the future will not be honored by the Mozscape API.
        $expires = time() + 300;

        // A new linefeed is necessary between your AccessID and Expires.
        $stringToSign = $accessID."\n".$expires;

        // Get the "raw" or binary output of the hmac hash.
        $binarySignature = hash_hmac('sha1', $stringToSign, $secretKey, true);

        // We need to base64-encode it and then url-encode that.
        $urlSafeSignature = urlencode(base64_encode($binarySignature));

        // Add up all the bit flags you want returned.
        // Learn more here: http://apiwiki.seomoz.org/categories/api-reference
        // $cols = "68719476736";
        $cols = "68719542269";

        $arrDomainsToQuery = array();

        foreach ($arrRecordsToQuery as $curRecord)
        {
            if($curRecord['root_domain'] && strcasecmp($curRecord['root_domain'], "<not set>") != 0)
            {
                $curDomain = $curRecord['root_domain'];
            }
            else
            {
                $curDomain = \Scooper\getPrimaryDomainFromUrl($curRecord['input_source_url']);
            }
            $arrDomainsToQuery[] = $curDomain;
        }

        // Put it all together and you get your request URL.
        $requestUrl = "http://lsapi.seomoz.com/linkscape/url-metrics?Cols=".$cols."&AccessID=".$accessID."&Expires=".$expires."&Signature=".$urlSafeSignature;

        if($GLOBALS['OPTS']['VERBOSE'])
        {
            $strDomainList = implode(';  ', $arrDomainsToQuery);
            $GLOBALS['logger']->logLine("'Moz API call: ".$requestUrl, \Scooper\C__DISPLAY_ITEM_DETAIL__);
            $GLOBALS['logger']->logLine("Domains:".$strDomainList, \Scooper\C__DISPLAY_ITEM_DETAIL__);

        }

        $counter = 0;

        $arrURLChunks = array_chunk($arrDomainsToQuery, 199, true);
        foreach ($arrURLChunks as $chunk)
        {

            // Put your URLS into an array and json_encode them.
            // $batchedDomains = array('www.seomoz.org', 'www.apple.com', 'www.pizza.com');
            $encodedDomains = json_encode($chunk);

            // We can easily use Curl to send off our request.
            // Note that we send our encoded list of domains through curl's POSTFIELDS.
            $options = array(
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POSTFIELDS     => $encodedDomains
                );

            $ch = curl_init($requestUrl);
            curl_setopt_array($ch, $options);
            $content = curl_exec($ch);
            if (curl_errno($ch)) {
                $strErr = 'Error #' . curl_errno($ch) . ': ' . curl_error($ch);
                $curl_object['error_number'] = curl_errno($ch);
                $curl_object['output'] = curl_error($ch);
                curl_close( $ch );
                throw new ErrorException($strErr,curl_errno($ch),E_RECOVERABLE_ERROR );
            }

            $contents = json_decode($content);
            foreach ($arrDomainsToQuery as $domain)
            {
                // print $contents[$counter].PHP_EOL;
                $this->_arrMozBulkAPIResults_[$domain] = json_encode($contents[$counter]);
                $counter++;
            }
        }
    }
}
