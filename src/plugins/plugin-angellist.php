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
define('__ROOT__', dirname(dirname(__FILE__)));
require_once(__ROOT__.'/include/plugin-base.php');

/****************************************************************************************************************/
/****                                                                                                        ****/
/****          AngelList Plugin Class                                                                        ****/
/****                                                                                                        ****/
/****************************************************************************************************************/
class PluginAngelList extends ScooterPluginBaseClass
{

    protected $_fDataIsExcluded_ = C__FEXCLUDE_DATA_NO;
    protected $strDataProviderName  = 'AngelList';


    function __construct($fExcludeThisData)
    {
        if($fExcludeThisData == 1) { $this->_fDataIsExcluded_ = C__FEXCLUDE_DATA_YES; }

        $GLOBALS['logger']->logLine("Initializing the ". $this->strDataProviderName ." data plugin (ExcludeData=".$this->_fDataIsExcluded_.").", \Scooper\C__DISPLAY_NORMAL__);
    }


    function getAllColumns()
    {
        return array(
            'id'=>'<not set>',
            'hidden'=>'<not set>',
            'community_profile'=>'<not set>',
            'name'=>'<not set>',
            'angellist_url'=>'<not set>',
            'logo_url'=>'<not set>',
            'thumb_url'=>'<not set>',
            'quality'=>'<not set>',
            'product_desc'=>'<not set>',
             'high_concept'=>'<not set>',
             'follower_count'=>'<not set>',
             'company_url'=>'<not set>',
             'created_at'=>'<not set>',
             'updated_at'=>'<not set>',
             'crunchbase_url'=>'<not set>',
             'twitter_url'=>'<not set>',
             'blog_url'=>'<not set>',
             'video_url'=>'<not set>',
             'markets'=>'<not set>',
             'locations'=>'<not set>',
             'company_size'=>'<not set>',
             'company_type'=>'<not set>',
             'status'=>'<not set>',
            'screenshots'=>'<not set>',
             'pic'=>'<not set>',
        );

    }

    function getCompanyData($id)
    {

        if(!$id|| strlen($id) == 0)
        {
            if($GLOBALS['OPTS']['VERBOSE'])  { $GLOBALS['logger']->logLine("No " . $this->strDataProviderName . " key value passed.  Cannot lookup other facts.", \Scooper\C__DISPLAY_ITEM_RESULT__);  }
            return null;
        }

        $strAPIURL = "https://api.angel.co/1/startups/".$id;

        //
        // Call the data API
        //
        $data = $this->getDataFromAPI($strAPIURL, true, null);
        return $data;


    }


    // method declaration
    function addDataToRecord(&$arrRecordToUpdate)
    {
         $this->addDataToRecordViaSearch($arrRecordToUpdate, 'company_name', 'name', 'https://api.angel.co/1/search?type=Startup&query=', null, true);
    }





}


