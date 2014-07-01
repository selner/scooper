<?php

/****************************************************************************************************************/
/****                                                                                                        ****/
/****         Helper Functions:  Row Record Utilities                                                        ****/
/****                                                                                                        ****/
/****************************************************************************************************************/

require_once(__ROOT__.'/include/options.php');

$GLOBALS['ALL_KEYS_IN_RIGHT_RESULTS_ORDER'] =  array(
    'company_name' => '<not set>',
    'result_accuracy_warnings' => '<not set>',
    'actual_site_url' => '<not set>',
    'crunchbase_match_accuracy' => '<not set>',
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





