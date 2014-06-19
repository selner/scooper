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


define('__SCROOT__', dirname(dirname(__FILE__)));
require_once(__SCROOT__ . '/scooper_common.php');


class ClassScooperSimpleCSVFile {

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

    private function __getFileType__()
    {
        $extensionType = null;

        $strExt = strtolower($this->detailsFile['file_extension']);
        switch ($strExt )
        {
            case 'xlsx':			//	Excel (OfficeOpenXML) Spreadsheet
            case 'xlsm':			//	Excel (OfficeOpenXML) Macro Spreadsheet (macros will be discarded)
            case 'xltx':			//	Excel (OfficeOpenXML) Template
            case 'xltm':			//	Excel (OfficeOpenXML) Macro Template (macros will be discarded)
            $extensionType = 'Excel2007';
            break;
            case 'xls':				//	Excel (BIFF) Spreadsheet
            case 'xlt':				//	Excel (BIFF) Template
            $extensionType = 'Excel5';
            break;
            case 'ods':				//	Open/Libre Offic Calc
            case 'ots':				//	Open/Libre Offic Calc Template
            $extensionType = 'OOCalc';
            break;
            case 'slk':
            $extensionType = 'SYLK';
            break;
            case 'xml':				//	Excel 2003 SpreadSheetML
            $extensionType = 'Excel2003XML';
            break;
            case 'gnumeric':
            $extensionType = 'Gnumeric';
            break;
            case 'htm':
            case 'html':
            $extensionType = 'HTML';
            break;
            case 'csv':
                $extensionType = 'CSV';
            break;
            default:
            break;
          }

        return $extensionType;
    }


function __construct($fileFullPath, $strAccessMode)
    {
        if(!$fileFullPath || strlen($fileFullPath) == 0 )
        {
            throw new Exception("File path including the file name is required to instantiate a SimpleScooperCSVClass. ");
        }

        $this->detailsFile = parseFilePath($fileFullPath, false);

        $this->_openFile_($strAccessMode);

    }

    function __destruct()
    {
        $this->_closeFile_();
    }

    private function _closeFile_()
    {
        if($this->_fp_ && get_resource_type($this->_fp_) === 'file')
        {
            fclose($this->_fp_) or die("can't close file ".$this->detailsFile['full_file_path']);
        }
    }

    private function _openFile_($strAccessMode)
    {
        $this->_strAccessMode_ = $strAccessMode;

        $fp = fopen($this->detailsFile['full_file_path'], $strAccessMode);
        if($fp)
            $this->_fp_ = $fp;
        else
            throw new ErrorException("Unable to open file '". $filepath . "' with access mode of '".$strAccessMode."'.".PHP_EOL .error_get_last()['message']) ;
    }

    private function _resetFile()
    {
        $this->_closeFile_();
        $this->_openFile_($this->_strAccessMode_);
    }


    function readAllRecords($fHasHeaderRow, $arrKeysToUse = null, $sheetName = null)
    {
        __debug__printLine("Reading all records for file type =".$this->__getFileType__()."; File=".$this->detailsFile['full_file_path'], C__DISPLAY_ITEM_DETAIL__);

        $ret = null;

        switch($this->__getFileType__())
        {
            case 'CSV':
                $ret = $this->__readAllRecords_CSV__($fHasHeaderRow, $arrKeysToUse);
                break;

//            case 'Excel2007':
 //             $ret =   $this->__readAllRecords_Excel__($fHasHeaderRow, $arrKeysToUse, $sheetName);
 //               break;
            default:
                __debug__printLine("Unsupported file type.  Extension=".$this->detailsFile['file_extension']."; File=".$this->detailsFile['full_file_path'], C__DISPLAY_ERROR__);
                break;
        }

        return $ret;

    }

    private function __readAllRecords_CSV__($fHasHeaderRow, $arrKeysToUse = null)
    {
        __debug__printLine("Reading CSV records from: ".$this->detailsFile['full_file_path'], C__DISPLAY_ITEM_DETAIL__);

        $arrDataLoaded = array();
        $nInputRow = 0;

        $arrDataLoaded['data_type'] = "NOT SET";
        while (($data = fgetcsv($this->_fp_, 0, ',', '"')) !== FALSE)
        {
            if($fHasHeaderRow == true && $nInputRow == 0)
            {
                if(count($arrKeysToUse) <= 0)
                {
                    $arrDataLoaded['header_keys'] = $data;
                }
                else
                {
                    $arrDataLoaded['header_keys'] = $arrKeysToUse;
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

        return $arrDataLoaded['data_rows'];
    }

    function writeArrayToHTMLFile($records, $keys=null, $arrKeysToUseToDedupe = null, $strCSSToInclude = null)
    {
        if($this->_strAccessMode_[0] == 'w' || $this->_strAccessMode_[0] == 'w')
        {
            $this->_resetFile();
        }

        $htmlOut = $this->getHTMLTableForCSV($records, $keys, $strCSSToInclude);

        if(!fputs($this->_fp_, $htmlOut))
        {
            throw new Exception("Unable to write file.");
        }
    }

    function writeArrayToCSVFile($records, $keys=null, $arrKeysToUseToDedupe = null)
    {

        if($this->_strAccessMode_[0] == 'w' || $this->_strAccessMode_[0] == 'w')
        {
            $this->_resetFile();
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

        if(count($records) > 0)
        {

        $arrRecordsToOutput = $this->getSortedDeDupedCSVArray($records, $arrKeysToUseToDedupe);

            foreach ($arrRecordsToOutput as $record)
            {
                if(!fputcsv($this->_fp_, $record))
                {
                    $err = error_get_last();

                    throw new Exception("Error: writeArrayToCSVFile failed because ".$err['message'] ." for file ".$err['file']. " writing " . count(@$records) . " records with keys=" . var_export($keys, true));
                }
            }
        }
    }


    function getHTMLTableForCSV($arrCSVRows, $arrFieldsToUseInKey, $strCSSToInclude = null)
    {

        $strHTMLReturn = "";

        if($strCSSToInclude != null)
        {
            $strHTMLReturn = PHP_EOL . PHP_EOL . "<style>" . $strCSSToInclude . "</style>". PHP_EOL . PHP_EOL;
        }

        $strHTMLReturn .= "<table class='CSVTable'>";

        $strHTMLReturn .= "<tr>";
        foreach($arrFieldsToUseInKey as $fieldName)
        {
            $strHTMLReturn .= "<td>";
            $strHTMLReturn .= $fieldName;
            $strHTMLReturn .= "</td>";
        }
        $strHTMLReturn .= "</tr>";


        foreach($arrCSVRows as $rec)
        {
            $strHTMLReturn .= "<tr>";
            foreach($arrFieldsToUseInKey as $fieldName)
            {
                $strHTMLReturn .= "<td>";
                $linkCount = substr_count($rec[$fieldName], "http");
                switch ($linkCount)
                {
                    case 0:
                         $strHTMLReturn .= $rec[$fieldName];
                        break;

                    case 1:
                        $strLink = substr($rec[$fieldName], 0, 50);
                        $strHTMLReturn .= "<a href='" . $rec[$fieldName] . "'>" . $strLink . "</a>";
                        break;

                    default:
                        $strHTMLReturn .= linkify($rec[$fieldName]);
                        break;
                }
                $strHTMLReturn .= "</td>";
            }
            $strHTMLReturn .= "</tr>";
        }
        $strHTMLReturn .= "</table>";


//        $strHTMLReturn = preg_replace("/^(.*)/", "<br/><br/>$1", $strHTMLReturn );

        return $strHTMLReturn;

    }


    function getSortedDeDupedCSVArray($arrCSVRows, $arrFieldsToUseInKey)
    {
        $retArray = null;

        if(!$arrFieldsToUseInKey || !is_array($arrFieldsToUseInKey))
        {
//            __debug__printLine("Not deduping output data; primary keys to use were not set.", C__DISPLAY_MOMENTARY_INTERUPPT__);
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


    function readMultipleCSVsAndCombine($arrFullPaths, $keysToUse = null, $arrKeysToUseForDedupe = null)
    {
        __debug__printLine("readMultipleCSVsAndCombine . " . $strOutFilePath, C__DISPLAY_ITEM_DETAIL__);

        __debug__printLine("Loading and combining CSV records from " . count($arrFullPaths)." files.", C__DISPLAY_ITEM_START__);

        $arrRecordsCombined = null;
        foreach($arrFullPaths as $curFilePath)
        {
            __debug__printLine("Loading ". $curFilePath." for combining into CSV records...", C__DISPLAY_ITEM_DETAIL__);

            if(is_file($curFilePath))
            {
                $classCurrentInput = new ClassScooperSimpleCSVFile($curFilePath, 'r');

                $arrCSVInput = $classCurrentInput->__readAllRecords_CSV__(true, $keysToUse);
                __debug__printLine("readAllRecords returned " . count($arrCSVInput) . " for ".$curFilePath, C__DISPLAY_ITEM_DETAIL__);

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

        __debug__printLine("Total records before de-dupe= ". count($arrRecordsCombined) . "...", C__DISPLAY_ITEM_DETAIL__);

        // sort the list and get to only the uniq records we haven't seen before
        $arrUniq = $this->getSortedDeDupedCSVArray($arrRecordsCombined, $arrKeysToUseForDedupe );


        __debug__printLine("Loaded " . count($arrUniq). " unique records from " . count($arrFullPaths)." files.", C__DISPLAY_ITEM_RESULT__);

        return $arrUniq;

    }

    function combineMultipleCSVs($arrFullFilePaths, $keysToUseForOutputCSV = null, $arrKeysToUseForDedupe = null)
    {

        switch($this->__getFileType__())
        {
            case 'CSV':
                $this->__combineMultipleCSVsIntoCSV__($arrFullFilePaths, $keysToUseForOutputCSV, $arrKeysToUseForDedupe);
                break;

            case 'Excel2007':
                if($keysToUseForOutputCSV != null || $arrKeysToUseForDedupe != null)
                {
                    __debug__printLine("Data is not deduped when writing to Excel. Keys also cannot be set. File=".$this->detailsFile['full_file_path'], C__DISPLAY_WARNING__);
                }
                $this->combineMultipleCSVsToExcel($arrFullFilePaths);
                break;
            default:
                __debug__printLine("Unsupported file type.  Extension=".$this->detailsFile['file_extension']."; File=".$this->detailsFile['full_file_path'], C__DISPLAY_ITEM_DETAIL__);
                break;
        }
    }

    private function __combineMultipleCSVsIntoCSV__($arrFullFilePaths, $keysToUseForOutputCSV = null, $arrKeysToUseForDedupe = null)
    {

            $arrRecordsCombinedOutput = $this->readMultipleCSVsAndCombine($arrFullFilePaths, $keysToUseForOutputCSV);

        // sort the list and get to only the uniq records we haven't seen before
        $arrUniq = $this->getSortedDeDupedCSVArray($arrRecordsCombinedOutput, $arrKeysToUseForDedupe );

        __debug__printLine("Total of " . count($arrUniq) ." unique records out of " . count($arrRecordsCombinedOutput)." records will be written to  ".$this->detailsFile['full_file_path'].".", C__DISPLAY_ITEM_DETAIL__);

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

function getEmptyUserInputRecord()
{
    return array('header_keys'=>null, 'data_type' => null, 'data_rows'=>array());
}

?>
