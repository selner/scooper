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

        if(isRecordFieldNullOrNotSet($arrRecordToUpdate['cb.permalink']) == false)
        {
            __debug__printLine("Querying Crunchbase for ". $arrRecordToUpdate['cb.permalink'], C__DISPLAY_ITEM_START__);
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
                throw new Exception("Error: company_name value was not set on the records correctly.  Cannot search Crunchbase.");
            }

            //
            //  Encode the company name for use in the API call.  Change any space characters to = characters.
            //
            $company_name_urlenc = urlencode($arrRecordToUpdate['company_name']);
            $company_name_urlenc = preg_replace('/%20/m', '+', $company_name_urlenc);
            $url = "http://api.crunchbase.com/v/1/search.js?api_key=".$GLOBALS['OPTS']['crunchbase_v1_api_id']."&entity=company&query=" . $company_name_urlenc;

           try
           {
                if($GLOBALS['VERBOSE'])  { __debug__printLine("Crunchbase API call=".$url, C__DISPLAY_ITEM_DETAIL__);  }
                $arrCrunchBaseSearchResultsRecords = $classAPICall->getObjectsFromAPICall($url, 'results', C__API_RETURN_TYPE_ARRAY__, array($this, 'updateCBDataWithCommonPrefixes'));

                if($GLOBALS['VERBOSE'])  { __debug__printLine("Crunchbase returned ".count($arrCrunchBaseSearchResultsRecords)." results for ". $arrRecordToUpdate['company_name'].". ", C__DISPLAY_ITEM_DETAIL__);  }

                if($arrCrunchBaseSearchResultsRecords && count($arrCrunchBaseSearchResultsRecords) > 0)
                {
                    foreach ($arrCrunchBaseSearchResultsRecords as $curCrunchResult)
                    {
                        if($curCrunchResult['cb.homepage_url'] && strlen($curCrunchResult['cb.homepage_url']) > 0)
                        {
                            $curCrunchResult['cb.computed_domain'] = getPrimaryDomain($curCrunchResult['cb.homepage_url']);
                            if(strcasecmp($curCrunchResult['cb.computed_domain'], $arrRecordToUpdate['effective_domain']) == 0)
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

        __debug__printLine("Getting Crunchbase ".$arrRecordToUpdate['cb.namespace'] ." entity-specific facts for ".(isRecordFieldNullOrNotSet($arrRecordToUpdate['cb.name'])? $arrRecordToUpdate['cb.permalink'] : $arrRecordToUpdate['cb.name']) , C__DISPLAY_ITEM_DETAIL__);

        if(($arrRecordToUpdate['cb.permalink'] && strlen($arrRecordToUpdate['cb.permalink']) > 0) &&
            ($arrRecordToUpdate['cb.namespace'] && strlen($arrRecordToUpdate['cb.namespace']) > 0))
        {
            $arrCrunchEntityData = $this->_getCrunchbaseEntityFacts_($arrRecordToUpdate['cb.namespace'], $arrRecordToUpdate['cb.permalink']);

            if(is_array($arrCrunchEntityData))
            {
                $arrRecordToUpdate = my_merge_add_new_keys($arrRecordToUpdate, $arrCrunchEntityData);
            }
        }
        else
        {
            $strErr = "Could not lookup entity-specific facts for ".$arrRecordToUpdate['cb.name']. ".  Invalid cb.permalink or cb.namespace value was given.";
            __debug__printLine($strErr , C__DISPLAY_ERROR__);
            addToAccuracyField($arrRecordToUpdate, $strErr);
        }

    }



    private function _getCrunchbaseEntityFacts_($entity_type, $strPermanlink)
	{

		if(!$strPermanlink || strlen($strPermanlink) == 0)
		{
			if($GLOBALS['VERBOSE'])  { __debug__printLine("No Crunchbase permanlink value passed.  Cannot lookup other facts.", C__DISPLAY_ITEM_RESULT__);  }
			return null;
        }

        //
		//  Encode the company name for use in the API call.  Change any space characters to = characters.
		// 

        $strAPIURL = "http://api.crunchbase.com/v/2/organization/".$strPermanlink."?user_key=".$GLOBALS['OPTS']['crunchbase_api_id'];
        if($GLOBALS['VERBOSE'])  { __debug__printLine("Crunchbase API Call = ".$strAPIURL, C__DISPLAY_ITEM_DETAIL__); }

//        $getJsonContents = new getJsonContents($strAPIURL);
//        $getJsonContents -> run();

		//
		// Call the Crunchbase Search API 
		// 

        $classAPICall = new APICallWrapperClass();

        $arrCrunchEntityData = $classAPICall->getObjectsFromAPICall($strAPIURL, '', C__API_RETURN_TYPE_ARRAY__, array($this, 'updateCBDataWithCommonPrefixes'));

        //
        // There are a couple fields that tend to problematic
        // due to their encoding and length.  For now, just blank those
        // columns out from the record and mark them accordingly.
        //
        $arrCrunchEntityData['cb.relationships'] = '<filtered>';

        return $arrCrunchEntityData;
		
	}

    static public function updateCBDataWithCommonPrefixes(&$arrRecord)
    {
        if(is_array($arrRecord))
        {


            $entityType = $arrRecord['namespace'];
            if(!$entityType || strlen($entityType) == 0) { $entityType = $arrRecord['cb.namespace']; };

             $arrCBCommonEntityFieldPrefixes = array(
                'category_code' => '<not set>',
                'field_name' => '<not set>',
                'crunchbase_url' => '<not set>',
                'description' => '<not set>',
                'homepage_url' => '<not set>',
                'image' => '<not set>',
                'name' => '<not set>',
                'namespace' => '<not set>',
                'offices' => '<not set>',
                'overview' => '<not set>',
                'permalink' => '<not set>',
                'computed_domain' => '<not set>',
                'blog_url' => '<not set>',
                'blog_feed_url' => '<not set>',
                'twitter_username' => '<not set>',
                'phone_number' => '<not set>',
                'email_address' => '<not set>',
                'number_of_employees' => '<not set>',
                'founded_year' => '<not set>',
                'founded_month' => '<not set>',
                'founded_day' => '<not set>',
                'tag_list' => '<not set>',
                'alias_list' => '<not set>',
                'created_at' => '<not set>',
                'updated_at' => '<not set>',
                'relationships' => '<not set>',
                'investments' => '<not set>',
                'milestones' => '<not set>',
                'providerships' => '<not set>',
                'funds' => '<not set>',
                'video_embeds' => '<not set>',
                'external_links' => '<not set>',
                'deadpooled_year' => '<not set>',
                'deadpooled_month' => '<not set>',
                'deadpooled_day' => '<not set>',
                'deadpooled_url' => '<not set>',
                'products' => '<not set>',
                 'competitions' => '<not set>',
                 'total_money_raised' => '<not set>',
                 'funding_rounds' => '<not set>',
                 'acquisition' => '<not set>',
                 'acquisitions' => '<not set>',
                 'ipo' => '<not set>',
                 'screenshots' => '<not set>',
                 'partners' => '<not set>'                                          );


            $arrKeys = array_keys($arrRecord);

            $arrNewKeys = array();
            foreach ($arrKeys as $key)
            {
                if($arrCBCommonEntityFieldPrefixes[$key])
                {
                    $key = 'cb.'.$key;
                }
                else if(strlen($entityType) > 0)
                {
                    $key = $entityType .'.'.$key;
                }
                $arrNewKeys[] = $key;
            }

            $arrRecord = array_copy(array_combine($arrNewKeys, $arrRecord));
        }

    }



    public function getArbitraryAPICallData($strAPICallURL, $fileOutFullPath)
    {
        $arrCrunchAPIData = array();
        $classAPICall = new APICallWrapperClass();
        if($GLOBALS['OPTS']['crunchbase_api_id'] == null || $GLOBALS['OPTS']['crunchbase_api_id']=="")
        {
            throw new ErrorException("Crunchbase API ID is required to call this function.");
        }



        $apiURL = $strAPICallURL."api_key=".$GLOBALS['OPTS']['crunchbase_api_id'];
        __log__("Calling Crunchbase API ".$apiURL, C__LOGLEVEL_INFO__);
        $arrCrunchAPIData = $classAPICall->getObjectsFromAPICall($apiURL, null, C__API_RETURN_TYPE_ARRAY__);
        $classOutputFile = new SimpleScooterCSVFileClass($fileOutFullPath, "w");
        $classOutputFile->writeArrayToCSVFile($arrCrunchAPIData);

        return $arrCrunchAPIData;

    }


    public function outputFundingRounds($arrVCSlugs, $strFileOutFolder)
    {
        if($GLOBALS['OPTS']['crunchbase_api_id'] == null || $GLOBALS['OPTS']['crunchbase_api_id']=="")
        {
            throw new ErrorException("Crunchbase API ID is required to call this function.");
        }

        $strOutVCDetails = parseFilePath($strFileOutFolder);

        $strOutVCFacts= $strOutVCDetails['directory'] . "financial_organization_facts.csv";
        $strOutVCInvestments = $strOutVCDetails['directory'] . "financial_organization_investments.csv";
        $classOutputVCData= new SimpleScooterCSVFileClass($strOutVCFacts, "w");
        $classOutputInvestments= new SimpleScooterCSVFileClass($strOutVCInvestments, "w");

        $retVCFullRecords = Array();

        $retArrayInvestments = Array();
        foreach ($arrVCSlugs as $vcRecord)
        {
            $arrVCRecord = getEmptyFullRecordArray();
            $arrVCRecord['cb.permalink'] = $vcRecord;
            $arrVCRecord['cb.namespace'] = "financial-organization";

            $this->addDataToRecord($arrVCRecord, false);
            $arrVCRecord['company_name'] = $arrVCRecord['cb.name'];
            $arrVCRecord['actual_site_url'] = $arrVCRecord['cb.homepage_url'];

            $arrVCFundingData = $arrVCRecord['cb.investments'];
            if(!isRecordFieldNullOrNotSet($arrVCFundingData) && $arrVCFundingData != null && count($arrVCFundingData) > 0)
            {
                __log__($vcRecord . " has " . count($arrVCFundingData) . " funding rounds for companies.", C__LOGLEVEL_INFO__);
                // Get list of portfolio companies for each VC
                foreach($arrVCFundingData as $record)
                {
                    $investment = $record['funding_round'];
                    $itemData = array('VC'=>$vcRecord, 'portfolio_company' => $investment['company']['name'], 'company_permalink' => $investment['company']['permalink']);
                    $retArrayInvestments[] = my_merge_add_new_keys($itemData, $investment);
                }

                __log__("Writing investments for " . $vcRecord . "to file... ", C__LOGLEVEL_INFO__);
                $classOutputInvestments->writeArrayToCSVFile($retArrayInvestments);
           }

            $arrVCRecord['cb.investments'] = '[see other file]';
            $arrVCRecord['cb.video_embeds'] = '[see other file]';
            $arrVCRecord['cb.relationships'] = '[see other file]';


            $this->_expandArrays_($arrVCRecord);
            $retVCFullRecords[] = $arrVCRecord;
        $classOutputVCData->writeArrayToCSVFile($retVCFullRecords);


        }

    }

    function writeFinancialOrganizations($arrOrgPermalinks = null, $strOutputFolder)
    {
        if($GLOBALS['OPTS']['crunchbase_api_id'] == null || $GLOBALS['OPTS']['crunchbase_api_id']=="")
        {
            throw new ErrorException("Crunchbase API ID is required to call this function.");
        }

        $outFileDetails = parseFilePath($strOutputFolder);

        $strOutOrgList = $outFile['directory'] . "financial_organizations.csv";
        // If we didn't get a list of financial org CB permalinks to use,
        // we assume the user wants them all, so let's go get the list
        //
        if($arrOrgPermalinks == null || count($arrOrgPermalinks) == 0)
        {
            __log__("Fetching full list of financial organizations...", C__LOGLEVEL_INFO__);

            $strURL = "http://api.crunchbase.com/v/2/financial-organizations?";
            $arrVCs = $this->getArbitraryAPICallData($strURL, $strOutOrgList  );
            foreach($arrVCs as $vcrecord)
            {
                $arrOrgPermalinks[] = $vcrecord['permalink'];

            }
        }

        __log__("Fetching full data for " . count($arrOrgPermalinks) ." financial organizations...", C__LOGLEVEL_INFO__);


        //
        // Now go get each companies investment list
        //
        $strOutInvestments = $outFile['directory'] . "financial_organizations_investments.csv";
        $this->outputFundingRounds($arrOrgPermalinks, $strOutputFolder);

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
            $arrCompanyRecords  ['cb.permalink'] = $vcRecord;
            $arrCompanyRecords  ['cb.namespace'] = "company";

            $this->addDataToRecord($arrCompanyRecords, true);
            $arrCompanyRecords['company_name'] = $arrCompanyRecords['cb.name'];
            $arrCompanyRecords['actual_site_url'] = $arrCompanyRecords['cb.homepage_url'];

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

?>
