<?php

/****************************************************************************************************************/
/****                                                                                                        ****/
/****         Helper Functions:  Row Record Utilities                                                        ****/
/****                                                                                                        ****/
/****************************************************************************************************************/



function getEmptyFullRecordArray()
{
    return $GLOBALS['ALL_POSSIBLE_RECORD_KEYS'];
}

function isRecordFieldNullOrNotSet($val, $fEmptyStringEqualsInvalid = false)
{
    $retValid = false;


    if(!$val) $retValid = true;
    if(is_string($val) && (strcasecmp($val, "N/A") == 0 || ($fEmptyStringEqualsInvalid == true && strlen($val) == 0)))
    {
        $retValid = true;
    }

//    __debug__var_dump_exit__(array('acc_val' => $val, 'is_string' => is_string($val), 'N/A match' => strcasecmp($val, "N/A"), 'empty_is_invalid' => $fEmptyStringEqualsInvalid, 'ret' => $retValid));

    return $retValid;
}






/****************************************************************************************************************/
/****                                                                                                        ****/
/****         Helper Functions:  Array processing                                                            ****/
/****                                                                                                        ****/
/****************************************************************************************************************/

function addPrefixToArrayKeys( $arr, $strPrefix = "", $strSep = "" )
{
	$strFunc = __debug__FuncStart__("addPrefixToArrayKeys(arr,".$strPrefix.",".$strSep.")");

	$arrKeys = array_keys($arr);
	$arrNewKeyValues = $arrKeys;
	$arrNewKeys = array();
	if(strlen($strPrefix) > 0) 
	{
		foreach ($arrKeys as $key) 
		{
			$arrNewKeys[] = $strPrefix.$strSep.$key;
		}	
		$arrNewKeyValues = array_combine($arrNewKeys, $arr);
	}

	__debug__FuncEnd__($strFunc);
	
	return $arrNewKeyValues;
}

function merge_into_array_and_add_new_keys( &$arr1, $arr2 )
{
	
	$arrOrig1 = $arr1;
	$arr1 = my_merge_add_new_keys( $arrOrig1, $arr2 );

}

function my_merge_add_new_keys( $arr1, $arr2 )
{
    // check if inputs are really arrays
    if (!is_array($arr1) || !is_array($arr2)) {
          throw new Exception("Input  is not an Array");
    }
	$strFunc = "my_merge_add_new_keys(arr1(size=".count($arr1)."),arr2(size=".count($arr2)."))";
	__debug__printLine($strFunc, C__DISPLAY_FUNCTION__, true);
	$arr1Keys = array_keys($arr1);
	$arr2Keys = array_keys($arr2);
	$arrCombinedKeys = array_merge_recursive($arr1Keys, $arr2Keys);
	
	// echo 'keys1 = '. count($arr1Keys).'; keys2 = '. count($arr2Keys).'; arrCombinedKeys = '. count($arrCombinedKeys).PHP_EOL;
	
	$arrNewBlankCombinedRecord = array_fill_keys($arrCombinedKeys, 'unknown');

	$arrMerged =  array_replace( $arrNewBlankCombinedRecord, $arr1 );
	$arrMerged =  array_replace( $arrMerged, $arr2 );

	__debug__printLine('returning from ' + $strFunc, C__DISPLAY_FUNCTION__, true);	return $arrMerged;
}

function my_merge( $arr1, $arr2 )
{
    // check if inputs are really arrays
    if (!is_array($arr1) || !is_array($arr2)) {
          throw new Exception("Input  is not an Array");
    }
	__debug__printLine("my_merge(arr1(size=".count($arr1).",first=".array_keys($arr1)[0].",arr2(size=".count($arr2).",first=".array_keys($arr2)[0].")", C__DISPLAY_FUNCTION__, true);
    $keys = array_keys( $arr2 );
    foreach( $keys as $key ) {
        if( isset( $arr1[$key] ) 
            && is_array( $arr1[$key] ) 
            && is_array( $arr2[$key] ) 
        ) {
            $arr1[$key] = my_merge( $arr1[$key], $arr2[$key] );
        } else {
            $arr1[$key] = $arr2[$key];
        }
    }
    return $arr1;
}

// Source: http://www.php.net/manual/en/ref.array.php#81081

    /**
     * make a recursive copy of an array 
     *
     * @param array $aSource
     * @return array    copy of source array
     */
    function array_copy ($aSource) {
        // check if input is really an array
        if (!is_array($aSource)) {
            throw new Exception("Input is not an Array");
        }
        
        // initialize return array
        $aRetAr = array();
        
        // get array keys
        $aKeys = array_keys($aSource);
        // get array values
        $aVals = array_values($aSource);
        
        // loop through array and assign keys+values to new return array
        for ($x=0;$x<count($aKeys);$x++) {
            // clone if object
            if (is_object($aVals[$x])) {
                $aRetAr[$aKeys[$x]]=clone $aVals[$x];
            // recursively add array
            } elseif (is_array($aVals[$x])) {
                $aRetAr[$aKeys[$x]]=array_copy ($aVals[$x]);
            // assign just a plain scalar value
            } else {
                $aRetAr[$aKeys[$x]]=$aVals[$x];
            }
        }
        
        return $aRetAr;
    }
