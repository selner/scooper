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
    define('__ROOT__', dirname(dirname(__FILE__)));
    require_once(__ROOT__.'/include/plugin-base.php');


    class MozPluginClass extends ScooterPluginBaseClass
	{
		private $_arrMozBulkAPIResults_ = null;
        private $_fDataIsExcluded_ = C__FEXCLUDE_DATA_NO;
        private $strDataProviderName  = 'Moz.com';

		function __construct($fExcludeThisData, $arrAllRecords)
		{
            if($fExcludeThisData == 1) { $this->_fDataIsExcluded_ = C__FEXCLUDE_DATA_YES; }

            __debug__printLine("Instantiating a ". $this->strDataProviderName ." data plugin (ExcludeData=".$this->_fDataIsExcluded_.").", C__DISPLAY_ITEM_DETAIL__);
			$this->_batchQueryMozAPI_($arrAllRecords);
        }
		
	    public function addDataToRecord(&$arrRecordToUpdate)
	    {
            if($this->_fDataIsExcluded_ == C__FEXCLUDE_DATA_YES) return;

            if($this->_arrMozBulkAPIResults_ == null)
            {
                throw new Exception("MozPlugin was not initialized correctly.");
            }

            __debug__printLine("Looking for a match in Moz data for ".$arrRecordToUpdate['company_name'], C__DISPLAY_ITEM_START__);

				
			$fMatchFound = false;
            if(!isRecordFieldNullOrNotSet($arrRecordToUpdate['root_domain']))
            {
                $recordStringToMatch =  $arrRecordToUpdate['root_domain'];

                foreach($this->_arrMozBulkAPIResults_ as $mozresult)
                {
                    $arrMozRecord = json_decode($mozresult, true);
                    $tempDomain = $arrMozRecord['upl'];
                    if($tempDomain == "" && strlen($arrMozRecord['uu'])>0)
                    {
                        $tempDomain = $arrMozRecord['uu'];
                    }
                    $tempDomain = getPrimaryDomainFromUrl($tempDomain);
//                    if($tempDomain[strlen($tempDomain)-1] = "/") { $tlempDomain = substr($tempDomain, 1, strlen($tempDomain)); }
                    if(strcasecmp($tempDomain, $recordStringToMatch) == 0 )
                    {
                        $arrRecordToUpdate = my_merge_add_new_keys($arrRecordToUpdate, $arrMozRecord);
                        return;
                    }

                }
                if(!$fMatchFound)
                {
                    addToAccuracyField($arrRecordToUpdate, 'No Moz match found for root_domain value.');
                    __debug__printLine("Moz: Match not found for ".$recordStringToMatch, C__DISPLAY_ERROR__);
                }
            }
            else
            {
                addToAccuracyField($arrRecordToUpdate, 'Unable to search Moz data; no root_domain found for company.');
                if(!$fMatchFound) { __debug__printLine("'Unable to search Moz data; no root_domain found for company = ".$arrRecordToUpdate['company_name'], C__DISPLAY_ERROR__);  }
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
					$curDomain = getPrimaryDomainFromUrl($curRecord['input_source_url']);
                }
				$arrDomainsToQuery[] = $curDomain;
			}
			
			// Put it all together and you get your request URL.
			$requestUrl = "http://lsapi.seomoz.com/linkscape/url-metrics/?Cols=".$cols."&AccessID=".$accessID."&Expires=".$expires."&Signature=".$urlSafeSignature;

			if($GLOBALS['OPTS']['VERBOSE'])
			{
				$strDomainList = implode(';  ', $arrDomainsToQuery);
                __debug__printLine("'Moz API call: ".$requestUrl, C__DISPLAY_ITEM_DETAIL__);
                __debug__printLine("Domains:".$strDomainList, C__DISPLAY_ITEM_DETAIL__);

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

                            var_dump($content);
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
