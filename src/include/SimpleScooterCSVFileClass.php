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

        while (($data = fgetcsv($this->_fp_, 0, ',')) !== FALSE)
        {
            if($fHasHeaderRow == true && $nInputRow == 0)
            {
                $arrDataLoaded['header_keys'] = $data;
                switch (strtolower($data[0]))
                {
                    case 'company_name';
                        $arrDataLoaded['data_type'] = C__LOOKUP_DATATYPE_BASICFACTS__;
                        __debug__printLine("CSV file type: company basic facts", C__DISPLAY_NORMAL__);
                        break;

                    case 'company name':
                    case 'company names':
                    case 'names':
                    case 'company':
                        $arrDataLoaded['data_type'] = C__LOOKUP_DATATYPE_NAME__;
                        __debug__printLine("CSV file type: company names", C__DISPLAY_NORMAL__);
                        break;

                    case 'company url':
                    case 'url':
                    case 'urls':
                    case 'input_source_url':
                        $arrDataLoaded['data_type'] = C__LOOKUP_DATATYPE_URL__;
                        __debug__printLine("CSV file type: URLs", C__DISPLAY_NORMAL__);
                        break;

                    default:
                        $arrDataLoaded['data_type'] = 'UNKNOWN';
                        echo "Input CSV file ".$this->_strFilePath_." does not have a header row with a valid column name.  Possible values are 'Company Name' or 'Company URL'.  " . PHP_EOL . "Exited." . PHP_EOL;
                        break;
                }
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

    private $_fp_ = null;
    private $_strFilePath_ = null;
    private $_strAccessMode_ = "";


}

?>