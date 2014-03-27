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
/****         Helpers                                                                                        ****/
/****                                                                                                        ****/
/****************************************************************************************************************/
const C__FEXCLUDE_DATA_YES = 1;
const C__FEXCLUDE_DATA_NO = 0;
require_once 'common.php';
require_once 'debug_functions.php';

const C__STR_USER_AGENT__ = "User-Agent:Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_2) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/33.0.1750.152 Safari/537.36";

/****************************************************************************************************************/
/****                                                                                                        ****/
/****         Helper Functions:  Utility Functions                                                           ****/
/****                                                                                                        ****/
/****************************************************************************************************************/

function curlWrap($url)
{

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_MAXREDIRS, 10 );
    curl_setopt($ch, CURLOPT_URL, $url);

    curl_setopt($ch, CURLOPT_VERBOSE, C__FSHOWVERBOSE_APICALL__);

    curl_setopt($ch, CURLOPT_FAILONERROR, true);

    curl_setopt($ch, CURLOPT_USERAGENT, C__STR_USER_AGENT__);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);

    $curl_object = array('input_url' => $inputURL, 'actual_site_url' => '', 'error_number' => 0, 'output' => '');

    $output = curl_exec($ch);
    $curl_object['output'] = $output;
    $curl_object['input_url'] = $url;

    $last_url = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
    $curl_object['actual_site_url'] = $last_url;


    if (curl_errno($ch))
    {
        print 'Error #' . curl_errno($ch) . ': ' . curl_error($ch) . PHP_EOL;
        $curl_object['error_number'] = curl_errno($ch);
        $curl_object['output'] = curl_error($ch);
    } else {
        curl_close($ch);
    }


    return $curl_object;
}

function getObjectsFromAPI( $baseURL, $objName, $fReturnType = C__API_RETURN_TYPE_OBJECT__, $pagenum = 0)
{
//    if($GLOBALS['VERBOSE'] == true) { __debug__printLine('getObjectsFromAPI() call started.  API call= ' . $baseURL . PHP_EOL, C__DISPLAY_ITEM_DETAIL__); }

    $srcdata = curlWrapNew($baseURL, "", "GET", "", $pagenum);
    if($srcdata != null)
    {
        if($objName == "")
        {
            $retData = $srcdata;
        }
        else
        {

            foreach($srcdata->$objName as $value)
                $retData[] = $value;


            //
            // If the data returned has a next_page value, then we have more results available
            // for this query that we need to also go get.  Do that now.
            //
            if($srcdata->next_page)
            {
                if($GLOBALS['VERBOSE'] == true) { __debug__printLine('Multipage results detected. Getting results for ' . $srcdata->next_page . '...' . PHP_EOL, C__DISPLAY_ITEM_DETAIL__); }

                $patternPage = "/.*page=([0-9]{1,})/";
                $patternPagePrefix = "/.*page=/";
                $pattern = "/(\/api\/v2\/).*/";
                $pagenum = preg_replace($patternPagePrefix, "", $srcdata->next_page);
                $retSecondary = getObjectsFromAPI($baseURL, $objName, $pagenum);

                //
                // Merge the primary and secondary result sets into one result
                // before return.  This allows for multiple page result sets from Zendesk API
                //

                foreach($retSecondary as $moreVal)
                    $retData[] = $moreVal;

            }
        }
    }

    switch ($fReturnType)
    {
        case  C__API_RETURN_TYPE_ARRAY__:
            return json_decode(json_encode($retData), true);
            break;

        case  C__API_RETURN_TYPE_ARRAY_FLATTENED__:
            $arrResult = json_decode(json_encode($retData), true);
            return array_flatten_sep('|', $arrResult);
            break;

        case  C__API_RETURN_TYPE_OBJECT__:
        default:
            return $retData;
            break;
    }

    return null;
}



function curlWrapNew($full_url, $json, $action, $onbehalf = null, $pagenum = null, $fileUpload = null)
{
    $onbehalf =	$GLOBALS['UserToPost'];

    /*	if($GLOBALS['VERBOSE'])
        {
            $strLog = 'API CALL: '.$action.' '. $full_url.'   '. 'JSON: '.$json.'   '.'PAGENUM: '.$pagenum.'   '.'ONBEHALFOF: '.$onbehalf.'   '. 'FILE: '.$fileUpload.' '.PHP_EOL;
            __debug__printLine($strLog, C__DISPLAY_ITEM_DETAIL__);
        }
    */
    if($pagenum > 0)
    {
        $full_url .= "?page=" . $pagenum;
    }
    if($onbehalf != null) $header = array('Content-type: application/json', 'X-On-Behalf-Of: ' . $onbehalf);
    else $header = array('Content-type: application/json');

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_VERBOSE,  C__FSHOWVERBOSE_APICALL__);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_MAXREDIRS, 10 );
    curl_setopt($ch, CURLOPT_URL, $full_url);
    curl_setopt($ch, CURLOPT_USERPWD, $GLOBALS['Auth']);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
    curl_setopt($ch, CURLOPT_FAILONERROR, false);
    curl_setopt($ch, CURLOPT_USERAGENT, C__STR_USER_AGENT__);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);

    switch($action)
    {
        case "POST":

            if($fileUpload != null)
            {
                if($onbehalf != null) $header = array('Content-type: application/binary', 'X-On-Behalf-Of: ' . $onbehalf);
                else $header = array('Content-type: application/binary');
                $fileh = fopen($fileUpload, 'r');
                $size = filesize($fileUpload);
                $fildata = fread($fileh,$size);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $fildata);
                curl_setopt($ch, CURLOPT_INFILE, $fileh);
                curl_setopt($ch, CURLOPT_INFILESIZE, $size);
            }
            else
            {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
            }
            break;
        case "GET":
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
            break;
        case "PUT":
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
            curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
            break;
        case "DELETE":
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
            break;
        default:
            break;
    }
    curl_setopt($ch, CURLOPT_HTTPHEADER, $header);



    $output = curl_exec($ch);
    if (curl_errno($ch)) {
        print 'Error #' . curl_errno($ch) . ': ' . curl_error($ch) . PHP_EOL;
        return null ;
    } else {
        curl_close($ch);
    }
    $decoded = json_decode($output);
    return $decoded;
}

/****************************************************************************************************************/
/****                                                                                                        ****/
/****         Helper Functions:  HTTP, HTML, and API Call Utility Functions                                  ****/
/****                                                                                                        ****/
/****************************************************************************************************************/




////////////////////////////////////////////////////////////
//
// Modified
// Original Source for getPrimaryDomain:  http://php.net/parse_url
// Author: webmaster at bigbirdmedia dot com
// Modified to support returing the domain name minus the top level domain
////////////////////////////////////////////////////////////
function getPrimaryDomain($url, $fIncludeTLD = true)
{
    $tld = parse_url($url,PHP_URL_HOST);
    $tldArray = explode(".",$tld);

    // COUNTS THE POSITION IN THE ARRAY TO IDENTIFY THE TOP LEVEL DOMAIN (TLD)
    $l1 = '0';

    foreach($tldArray as $s) {
        // CHECKS THE POSITION IN THE ARRAY TO SEE IF IT MATCHES ANY OF THE KNOWN TOP LEVEL DOMAINS (YOU CAN ADD TO THIS LIST)
        if($s == 'com' || $s == 'net' || $s == 'info' || $s == 'biz' || $s == 'us' || $s == 'co' || $s == 'org' || $s == 'me') {

            // CALCULATES THE SECOND LEVEL DOMAIN POSITION IN THE ARRAY ONCE THE POSITION OF THE TOP LEVEL DOMAIN IS IDENTIFIED
            $l2 = $l1 - 1;
        }
        else {
            // INCREMENTS THE COUNTER FOR THE TOP LEVEL DOMAIN POSITION IF NO MATCH IS FOUND
            $l1++;
        }
    }

    // RETURN THE SECOND LEVEL DOMAIN AND THE TOP LEVEL DOMAIN IN THE FORMAT LIKE "SOMEDOMAIN.COM"
    $strReturnDomain = $tldArray[$l2];
    if($fIncludeTLD == true) { $strReturnDomain = $strReturnDomain . '.' . $tldArray[$l1]; }
    return $strReturnDomain;

}
////////////////////////////////////////////////////////////



function simplifyStringForURL($text)
{
    $retValue = strtolower($text);
    $retValue = preg_replace('/\.com/', "", $retValue);
    $retValue = preg_replace('/[^[:alpha:]]/', '', $retValue);

    return $retValue;
}






/*
class SuperSimpleCSVClass
{
    public $keys = array();
    public $records = array();

    function __construct($arrRecords, $arrKeys = null)
    {
        if(is_array($arrKeys))
        {
            $this->$keys = $arrKeys;
        }
        else
        {
            $this->$keys = array_keys($arrRecords[0]);
        }
        $this->records = $arrRecords;
    }
}
*/
/***
From:  http://www.php.net/manual/en/function.fopen.php

A list of possible modes for fopen() using mode
mode	Description
'r'	 Open for reading only; place the file pointer at the beginning of the file.
'r+'	 Open for reading and writing; place the file pointer at the beginning of the file.
'w'	 Open for writing only; place the file pointer at the beginning of the file and truncate the file to zero length. If the file does not exist, attempt to create it.
'w+'	 Open for reading and writing; place the file pointer at the beginning of the file and truncate the file to zero length. If the file does not exist, attempt to create it.
'a'	 Open for writing only; place the file pointer at the end of the file. If the file does not exist, attempt to create it.
'a+'	 Open for reading and writing; place the file pointer at the end of the file. If the file does not exist, attempt to create it.
'x'	 Create and open for writing only; place the file pointer at the beginning of the file. If the file already exists, the fopen() call will fail by returning FALSE and generating an error of level E_WARNING. If the file does not exist, attempt to create it. This is equivalent to specifying O_EXCL|O_CREAT flags for the underlying open(2) system call.
'x+'	 Create and open for reading and writing; otherwise it has the same behavior as 'x'.
'c'	 Open the file for writing only. If the file does not exist, it is created. If it exists, it is neither truncated (as opposed to 'w'), nor the call to this function fails (as is the case with 'x'). The file pointer is positioned on the beginning of the file. This may be useful if it's desired to get an advisory lock (see flock()) before attempting to modify the file, as using 'w' could truncate the file before the lock was obtained (if truncation is desired, ftruncate() can be used after the lock is requested).
'c+'	 Open the file for reading and writing; otherwise it has the same behavior as 'c'.
****/

class FileBaseClass
{

    function __construct($fileFullPath, $strAccessNeeded)
	{
		if(!$fileFullPath || strlen($fileFullPath) == 0 ) 
		{ 
	        throw new Exception("File path including the file name is required to instatiate a FileBaseClass. ");
		}


        $this->_openFile_($fileFullPath, $strAccessNeeded);

	}

    function __destruct()
    {
        $this->_closeFile_();
    }

    private function _closeFile_()
    {
        if($this->_fp_ && get_resource_type($this->_fp_) === 'file')
        {
             fclose($this->_fp_) or die("can't close file ".$this->_strFilePath_);
        }
    }

    private function _openFile_($filepath, $accessLevel)
    {
        $this->_strFilePath_ = $filepath;
        $this->_strAccessLevel_ = $accessLevel;
        $this->_fp_ = fopen($this->_strFilePath_,$accessLevel) or die("can't open file ".$this->_strFilePath_."");
    }

    private function _resetFile()
    {
        $this->_closeFile_();
        $this->_openFile_($this->_strFilePath_, $this->_strAccessLevel_);
    }

   function readAllRowsFromCSV(&$arrCSVRecords, $fHasHeaderRow = false) 
	{
		__debug__printLine("File: ".$this->_strFilePath_, C__DISPLAY_NORMAL__);


		$arrDataLoaded = array('header_keys'=>null, 'data_type' => null, 'data_rows'=>array());
    $nInputRow = 0;

    while (($data = fgetcsv($this->_fp_, 0, ',')) !== FALSE)
	{
        if($fHasHeaderRow == true && $nInputRow == 0)
        {
            $arrDataLoaded['header_keys'] = $data;
            switch (strtolower($data[0]))
            {
                case 'company_name';
                    $arrDataLoaded['data_type'] = 'LOOKUP_BY_BASIC_FACTS';
                    __debug__printLine("CSV file type: company basic facts list", C__DISPLAY_NORMAL__);
                    break;

                case 'company name';
                case 'company names';
                case 'names';
                    $arrDataLoaded['data_type'] = 'LOOKUP_BY_NAME';
                    __debug__printLine("CSV file type: company name list", C__DISPLAY_NORMAL__);
                    break;

                case 'company url';
                case 'url';
                case 'urls';
                case 'input_source_url';
                    $arrDataLoaded['data_type'] = 'LOOKUP_BY_URL';
                    __debug__printLine("CSV file type: URL list", C__DISPLAY_NORMAL__);
                    break;

                default:
                    $arrDataLoaded['data_type'] = 'UNKNOWN';
                    echo "Input CSV file ".$this->_strFilePath_." does not have a header row with a valid column name.  Possible values are 'Company Name' or 'Company URL'.  " . PHP_EOL . "Exited." . PHP_EOL;
                    break;
            }
        }
        else
        {
            $arrDataLoaded['data_rows'][] = $data;
        }
        $nInputRow++;
    }

    $arrCSVRecords = $arrDataLoaded;
    return $arrCSVRecords;
}

   private function _getData_($var) 
	{
        throw new Exception("_getData_ must be defined for any class extending SitePluginBaseClass. ");
    }


	 function _returnIfExcluded()
	{
		if($this->_fDataIsExcluded_ == C__FEXCLUDE_DATA_YES) { return; }
	}


	function writeArrayToCSVFile($records, $keys=null)
	{

        if($this->_strAccessLevel_[0] == 'w' || $this->_strAccessLevel_[0] == 'w')
        {
           $this->_resetFile();
        }

	    // check if inputs are really arrays
	    if(!is_array($records) && !is_array($records[0])) {
	          throw new Exception("$records variable passed was not a 2-D array.");
	    }

        if(!$keys)
        {
            $keys = array_keys($records[0]);
        }

        if (is_array($keys))
        {
		   fputcsv($this->_fp_, $keys, ',', '"');
		}
        else
        {
            throw new Exception("$keys variable passed was not a valid array.");
        }


		foreach ($records as $record)
		{
			fputcsv($this->_fp_, $record);
            // throw new Exception("writeArrayToCSVFile. ");
		}
	}

	private $_fp_ = null;
	private $_strFilePath_ = null;
	private $_strAccessLevel_ = "";

}



?>