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
    require_once dirname(__FILE__).'/plugin-base.php';


    class MozPluginClass extends SitePluginBaseClass
	{
		private $_arrMozBulkAPIResults_ = null; 
		
		function __construct($fExcludeThisData, $arrAllRecords)
		{
			parent::__construct($fExcludeThisData);
			$this->_batchQueryMozAPI_($arrAllRecords);
		}
		
	    // Redefine the parent method
	    public function addDataToRecord(&$arrRecordToUpdate) 
	    {
			$this->_returnIfExcluded();

			if($this->_arrMozBulkAPIResults_ == null)
			{
		        throw new Exception("MozPlugin was not initialized correctly.  You must use the MozPluginClass($arrAllRecords) constructor.");
			}
				
			$fMatchFound = false;
			$recordStringToMatch =  $arrRecordToUpdate['effective_domain'].'/';
			
			foreach($this->_arrMozBulkAPIResults_ as $mozresult)
			{
				$arrMozRecord = json_decode($mozresult, true);
				if(strcasecmp($arrMozRecord['upl'], $recordStringToMatch) == 0 )
				{
					$arrRecordToUpdate = array_merge($arrRecordToUpdate, $arrMozRecord);
					return;
				}
				
			}
		
			if(!$fMatchFound) { __debug__printLine("Match not found.", C__DISPLAY_ERROR__);  }
			
			return;
	    }

	   	private function _getData_($var) 
		{
	         throw new Exception("_getData_ is not defined for MozPluginClass.");
		}

		private function _batchQueryMozAPI_($arrRecordsToQuery)
		{
			$this->_returnIfExcluded();
			
			// From https://github.com/seomoz/SEOmozAPISamples/blob/master/php/batching_urls_sample.php
				
			// you can obtain you access id and secret key here: http://www.seomoz.org/api/keys
			$accessID = "member-0c068bd3a1";
			$secretKey = "c09f545d7ff6c27b43c3e1b01bc6ba11";

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
				if($curRecord['effective_domain'] && strcasecmp($curRecord['effective_domain'], "N/A") != 0)
				{
					$curDomain = $curRecord['effective_domain'];
				}
				else 
				{
					$curDomain = getPrimaryDomain($curRecord['input_source_url']);			}
				$arrDomainsToQuery[] = $curDomain;
			}
			
			// Put it all together and you get your request URL.
			$requestUrl = "http://lsapi.seomoz.com/linkscape/url-metrics/?Cols=".$cols."&AccessID=".$accessID."&Expires=".$expires."&Signature=".$urlSafeSignature;

			if($GLOBALS['VERBOSE'])
			{
				$strDomainList = implode(';  ', $arrDomainsToQuery);
				
				print 'MOZ API call: '.$requestUrl.PHP_EOL;
				print 'Domain List:'.PHP_EOL;
				print $strDomainList.PHP_EOL;
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
				curl_close( $ch );

				$contents = json_decode($content);
						
				$mozResults = array();
				foreach ($arrDomainsToQuery as $domain) 
				{
					// print $contents[$counter].PHP_EOL;
					$this->_arrMozBulkAPIResults_[$domain] = json_encode($contents[$counter]);
					$counter++;
				}
			}
		}
	}
?>