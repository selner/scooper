<?php

/****************************************************************************************************************/
/****                                                                                                        ****/
/****         Helper Functions:  Row Record Utilities                                                        ****/
/****                                                                                                        ****/
/****************************************************************************************************************/

define('__ROOT__', dirname(dirname(__FILE__)));
require_once(__ROOT__.'/include/options.php');

$GLOBALS['ALL_KEYS_IN_RIGHT_RESULTS_ORDER'] =  array(
    'company_name' => '<not set>',
    'result_accuracy_warnings' => '<not set>',
    'company_name_linked' => '<not set>',
    'actual_site_url' => '<not set>',
    'crunchbase_match_accuracy' => '<not set>',
    );

//$GLOBALS['ALL_KEYS_IN_RIGHT_RESULTS_ORDER'] =  array(
//    'company_name' => '<not set>',
//    'result_accuracy_warnings' => '<not set>',
//    'company_name_linked' => '<not set>',
//    'actual_site_url' => '<not set>',
//    'crunchbase_match_accuracy' => '<not set>',
//    'category_code' => '<not set>',
//    'description' => '<not set>',
//    'overview' => '<not set>',
//    'Specialization' => '<not set>',
//    'Where Did Company Get Added to the List From?' => '<not set>',
//    'Company Description' => '<not set>',
//    'Contact Info' => '<not set>',
//    'Notes' => '<not set>',
//    'Status' => '<not set>',
//    'Last Status Update Date' => '<not set>',
//    'Open Roles List (if any)' => '<not set>',
//    'crunchbase_url' => '<not set>',
//    'offices' => '<not set>',
//    'number_of_employees' => '<not set>',
//    'total_money_raised' => '<not set>',
//    'funding_rounds' => '<not set>',
//    'acquisition' => '<not set>',
//    'acquisitions' => '<not set>',
//    'ipo' => '<not set>',
//    'founded_year' => '<not set>',
//    'founded_month' => '<not set>',
//    'founded_day' => '<not set>',
//    'funds' => '<not set>',
//    'products' => '<not set>',
//    'competitions' => '<not set>',
//    'phone_number' => '<not set>',
//    'email_address' => '<not set>',
//    'screenshots' => '<not set>',
//    'partners' => '<not set>',
//    'person.first_name' => '<not set>',
//    'person.last_name' => '<not set>',
//    'homepage_url' => '<not set>',
//    'twitter_username' => '<not set>',
//    'computed_domain' => '<not set>',
//    'image' => '<not set>',
//    'blog_url' => '<not set>',
//    'blog_feed_url' => '<not set>',
//    'effective_domain' => '<not set>',
//    'input_source_url' => '<not set>',
//    'quantcast.monthly_uniques' => '<not set>',
//    'video_embeds' => '<not set>',
//    'external_links' => '<not set>',
//    'deadpooled_year' => '<not set>',
//    'deadpooled_month' => '<not set>',
//    'deadpooled_day' => '<not set>',
//    'deadpooled_url' => '<not set>',
//    'name' => '<not set>',
//    'namespace' => '<not set>',
//    'permalink' => '<not set>',
//    'tag_list' => '<not set>',
//    'alias_list' => '<not set>',
//    'created_at' => '<not set>',
//    'updated_at' => '<not set>',
//    'relationships' => '<not set>',
//    'investments' => '<not set>',
//    'milestones' => '<not set>',
//    'providerships' => '<not set>',
//    'feid' => '<not set>',
//    'fid' => '<not set>',
//    'fmrp' => '<not set>',
//    'fmrr' => '<not set>',
//    'pda' => '<not set>',
//    'peid' => '<not set>',
//    'pid' => '<not set>',
//    'ueid' => '<not set>',
//    'ufq' => '<not set>',
//    'uid' => '<not set>',
//    'uifq' => '<not set>',
//    'uipl' => '<not set>',
//    'ujid' => '<not set>',
//    'umrp' => '<not set>',
//    'umrr' => '<not set>',
//    'upl' => '<not set>',
//    'ut' => '<not set>',
//    'uu' => '<not set>',
//    'kind' => '<not set>'
//);


function getEmptyFullRecordArray()
{
    return $GLOBALS['ALL_KEYS_IN_RIGHT_RESULTS_ORDER'];
}

function isRecordFieldNullOrNotSet($val, $fEmptyStringIsValid = false, $fZeroIsValid = false)
{
    // true = not valid (e.g. "<not set>", "n/a", "", 0, null, etc.)
    // false = valid data
    if(!$val) return true;
    if(($fZeroIsValid == true) && ($val == 0)) { return true; }

    if(is_string($val) && (strcasecmp($val, "<not set>") == 0 || (strlen($val) == 0 && $fEmptyStringIsValid != true)))
    {
        return true;
    }

//    __debug__var_dump_exit__(array('acc_val' => $val, 'is_string' => is_string($val), '<not set> match' => strcasecmp($val, "<not set>"), 'empty_is_invalid' => $fEmptyStringEqualsInvalid, 'ret' => $retValid));

    return false;
}




function addToAccuracyField(&$arrRecord, $strValueToAdd)
{

    if(isRecordFieldNullOrNotSet($arrRecord['result_accuracy_warnings']) == true)
    {
        $arrRecord['result_accuracy_warnings'] = $strValueToAdd;
    }
    else
    {
        $arrRecord['result_accuracy_warnings'] = $arrRecord['result_accuracy_warnings'] . " | ". $strValueToAdd;
    }

}




?>
