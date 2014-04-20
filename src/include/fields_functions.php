<?php

/****************************************************************************************************************/
/****                                                                                                        ****/
/****         Helper Functions:  Row Record Utilities                                                        ****/
/****                                                                                                        ****/
/****************************************************************************************************************/

require_once dirname(__FILE__) . '/../config.php';


$GLOBALS['ALL_KEYS_IN_RIGHT_RESULTS_ORDER'] =  array(
    'company_name' => '<not set>',
    'result_accuracy_warnings' => '<not set>',
    'company_name_linked' => '<not set>',
    'actual_site_url' => '<not set>',
    'crunchbase_match_accuracy' => '<not set>',
    'cb.category_code' => '<not set>',
    'cb.description' => '<not set>',
    'cb.overview' => '<not set>',
    'Specialization' => '<not set>',
    'Where Did Company Get Added to the List From?' => '<not set>',
    'Company Description' => '<not set>',
    'Contact Info' => '<not set>',
    'Notes' => '<not set>',
    'Status' => '<not set>',
    'Last Status Update Date' => '<not set>',
    'Open Roles List (if any)' => '<not set>',
    'cb.crunchbase_url' => '<not set>',
    'cb.offices' => '<not set>',
    'cb.number_of_employees' => '<not set>',
    'cb.total_money_raised' => '<not set>',
    'cb.funding_rounds' => '<not set>',
    'cb.acquisition' => '<not set>',
    'cb.acquisitions' => '<not set>',
    'cb.ipo' => '<not set>',
    'cb.founded_year' => '<not set>',
    'cb.founded_month' => '<not set>',
    'cb.founded_day' => '<not set>',
    'cb.funds' => '<not set>',
    'cb.products' => '<not set>',
    'cb.competitions' => '<not set>',
    'cb.phone_number' => '<not set>',
    'cb.email_address' => '<not set>',
    'cb.screenshots' => '<not set>',
    'cb.partners' => '<not set>',
    'person.first_name' => '<not set>',
    'person.last_name' => '<not set>',
    'cb.homepage_url' => '<not set>',
    'cb.twitter_username' => '<not set>',
    'cb.computed_domain' => '<not set>',
    'cb.image' => '<not set>',
    'cb.blog_url' => '<not set>',
    'cb.blog_feed_url' => '<not set>',
    'effective_domain' => '<not set>',
    'input_source_url' => '<not set>',
    'quantcast.monthly_uniques' => '<not set>',
    'cb.video_embeds' => '<not set>',
    'cb.external_links' => '<not set>',
    'cb.deadpooled_year' => '<not set>',
    'cb.deadpooled_month' => '<not set>',
    'cb.deadpooled_day' => '<not set>',
    'cb.deadpooled_url' => '<not set>',
    'cb.name' => '<not set>',
    'cb.namespace' => '<not set>',
    'cb.permalink' => '<not set>',
    'cb.tag_list' => '<not set>',
    'cb.alias_list' => '<not set>',
    'cb.created_at' => '<not set>',
    'cb.updated_at' => '<not set>',
    'cb.relationships' => '<not set>',
    'cb.investments' => '<not set>',
    'cb.milestones' => '<not set>',
    'cb.providerships' => '<not set>',
    'feid' => '<not set>',
    'fid' => '<not set>',
    'fmrp' => '<not set>',
    'fmrr' => '<not set>',
    'pda' => '<not set>',
    'peid' => '<not set>',
    'pid' => '<not set>',
    'ueid' => '<not set>',
    'ufq' => '<not set>',
    'uid' => '<not set>',
    'uifq' => '<not set>',
    'uipl' => '<not set>',
    'ujid' => '<not set>',
    'umrp' => '<not set>',
    'umrr' => '<not set>',
    'upl' => '<not set>',
    'ut' => '<not set>',
    'uu' => '<not set>',
    'kind' => '<not set>'
);


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
