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

function getDataTypeFromString($strType)
{
    $ret = 'UNKNOWN';

    switch (strtolower($strType))
    {
        case 'company_name';
            $ret  = C__LOOKUP_DATATYPE_BASICFACTS__;
            break;

        case 'company name':
        case 'company names':
        case 'names':
        case 'company':
            $ret  = C__LOOKUP_DATATYPE_NAME__;
            break;

        case 'company url':
        case 'url':
        case 'urls':
        case 'input_source_url':
            $ret = C__LOOKUP_DATATYPE_URL__;

            break;
    }

    return $ret;

}

class SimpleScooterCSVFileClass {

    /***
    From:  http://www.php.net/manual/en/function/fopen.php

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


    function __construct($fileFullPath, $strAccessMode)
    {
        if(!$fileFullPath || strlen($fileFullPath) == 0 )
        {
            throw new Exception("File path including the file name is required to instantiate a SimpleScooterCSVFileClass. ");
        }


        $this->_openFile_($fileFullPath, $strAccessMode);

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

    private function _openFile_($filepath, $strAccessMode)
    {
        $this->_strFilePath_ = $filepath;
        $this->_strAccessMode_ = $strAccessMode;

        $fp = fopen($this->_strFilePath_,$strAccessMode);
        if($fp)
            $this->_fp_ = $fp;
        else
            throw new ErrorException("Unable to open file '". $filepath . "' with access mode of '".$strAccessMode."'.".PHP_EOL .error_get_last()['message']) ;
    }

    private function _resetFile()
    {
        $this->_closeFile_();
        $this->_openFile_($this->_strFilePath_, $this->_strAccessMode_);
    }

    function readAllRowsFromCSV(&$arrCSVRecords, $fHasHeaderRow = false)
    {
        __debug__printLine("File: ".$this->_strFilePath_, C__DISPLAY_NORMAL__);


        $arrDataLoaded = getEmptyUserInputRecord();
        $nInputRow = 0;

        while (($data = fgetcsv($this->_fp_, 0, ',', '"')) !== FALSE)
        {
            if($fHasHeaderRow == true && $nInputRow == 0)
            {
                $arrDataLoaded['header_keys'] = $data;
                $arrDataLoaded['data_type'] = getDataTypeFromString($data[0]);
            }
            else
            {
                if(strlen($data[0])> 0)  // skip rows with blank values in the first field.
                {
                    $arrDataLoaded['data_rows'][] = array_combine($arrDataLoaded['header_keys'], $data);
                }

            }
            $nInputRow++;
        }

        $arrCSVRecords = $arrDataLoaded;
        return $arrCSVRecords;
    }


    function readAllRecords($fHasHeaderRow, $arrKeysToUse = null)
    {
        __debug__printLine("Reading CSV records from: ".$this->_strFilePath_, C__DISPLAY_ITEM_DETAIL__);

        $arrDataLoaded = array();
        $nInputRow = 0;
        $keys = (!$arrKeysToUse ? $arrKeysToUse : null);


        while (($data = fgetcsv($this->_fp_, 0, ',', '"')) !== FALSE)
        {
            if(!$keys && !$arrKeysToUse && !$fHasHeaderRow)
            {
                __debug__printLine("No idea where we should get the keys for the array: ".$this->_strFilePath_, C__DISPLAY_NORMAL__);
            }
            else if(!$keys && $fHasHeaderRow == true && $nInputRow == 0)
            {
                $keys = array_values($data);
//                __debug__printLine("Using keys from the first row:  ". var_export($keys, true), C__DISPLAY_NORMAL__);
            }
            else
            {
                if(strlen($data[0])> 0)  // skip rows with blank values in the first field.
                {
                    $arrDataLoaded[] = array_combine($keys, $data);
                }

            }
            $nInputRow++;
        }

        if($keys)
        {
            $retArr = array();
            $retArr = my_merge_add_new_keys($retArr , $arrDataLoaded);
        }
        else
        {
            $retArr = $arrDataLoaded;
        }

        return $retArr;
    }

    function writeArrayToCSVFile($records, $keys=null)
    {

        if($this->_strAccessMode_[0] == 'w' || $this->_strAccessMode_[0] == 'w')
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


    function getSortedDeDupedCSVArray($arrCSVRows, $arrFieldsToUseInKey)
    {

        if(!$arrFieldsToUseInKey || !is_array($arrFieldsToUseInKey))
        {
            __debug__printLine("Field keys to use for deduping were not set.  Skipping dedupe", C__DISPLAY_MOMENTARY_INTERUPPT__);
            return $arrCSVRows;
        }
//        print 'input array rows = ' . count($arrCSVRows).PHP_EOL;
        $arrKeyedCSV = array();
        $inputKeys = array_keys($arrCSVRows);

        foreach($arrCSVRows as $rec)
        {
            $strThisKey = "";
            foreach($arrFieldsToUseInKey as $fieldName)
            {
                $strThisKey .= $rec[$fieldName] . "-";
            }
            if($arrKeyedCSV[$strThisKey])
            {
                $arrKeyedCSV[$strThisKey] = array_merge($rec, $arrKeyedCSV[$strThisKey] );
            }
            else
            {

                // add it to the array with new key's  records we're returning
                $arrKeyedCSV[$strThisKey] = $rec;

                // add it to the normal array of records we're returning
                $retArray[] = $rec;
            }
        }

        return $retArray;

    }


    function readMultipleCSVsAndCombine($arrFullPaths, $keysToUse = null)
    {
        __debug__printLine("Loading and combining CSV records from " . count($arrFullPaths)." files.", C__DISPLAY_ITEM_START__);

        $arrRecordsCombined = null;
        foreach($arrFullPaths as $curFilePath)
        {
            __debug__printLine("Loading ". $curFilePath." for combining into CSV records...", C__DISPLAY_ITEM_DETAIL__);

            if(is_file($curFilePath))
            {
                $classCurrentInput = new SimpleScooterCSVFileClass($curFilePath, 'r');

                $arrCSVInput = $classCurrentInput->readAllRecords(true, $keysToUse);

                if(count($arrCSVInput) > 0)
                {
                    if(!$arrRecordsCombined)
                    {
                        $arrRecordsCombined = array_copy($arrCSVInput);
                    }
                    else
                    {
                        $arrRecordsCombined = array_merge($arrRecordsCombined, $arrCSVInput);

                    }
                    __debug__printLine("Added  ". count($arrCSVInput) . " records from " . $curFilePath . ". Total record counts is now ". count($arrRecordsCombined) .".", C__DISPLAY_ITEM_DETAIL__);

                }
                else
                {
                    __debug__printLine("Warning: No rows were loaded from " . $curFilePath, C__DISPLAY_ERROR__);

                }

            }
        }

        __debug__printLine("Loaded " . count($arrRecordsCombined). " records from " . count($arrFullPaths)." files.", C__DISPLAY_ITEM_RESULT__);

        return $arrRecordsCombined;

    }

    function combineMultipleCSVs($arrFullFilePaths, $keysToUseForOutputCSV = null, $arrKeysToUseForDedupe = null)
    {

        $arrRecordsCombinedOutput = $this->readMultipleCSVsAndCombine($arrFullFilePaths, $keysToUse);

        print 'Total records before de-dupe= '. count($arrRecordsCombinedOutput).'...'.PHP_EOL;

        // sort the list and get to only the uniq records we haven't seen before
        $arrUniq = $this->getSortedDeDupedCSVArray($arrRecordsCombinedOutput, $arrKeysToUseForDedupe );

        print 'Total final records = '. count($arrUniq).'...'.PHP_EOL;

        // write the uniq values out to the results file
        $this->writeArrayToCSVFile($arrUniq, $keysToUseForOutputCSV );

        //
        // And, finally, return the uniqure records
        //
        return $arrUniq;

    }


    private $_fp_ = null;
    private $_strFilePath_ = null;
    private $_strAccessMode_ = "";


}

?>