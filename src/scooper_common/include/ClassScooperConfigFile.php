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

class ClassScooperConfigFile
{
    private $config = null;


    private $detailsIniFile = null;
    private $detailsOutputFile = null;
    private $arrInputFiles = null;
    private $arrEmailAddresses = array();

    function __construct($strINIFileFullPath)
    {
        if(strlen($strINIFileFullPath) > 0)
        {
            $this->detailsIniFile = parseFilePath($strINIFileFullPath, true);
            $iniParser = new IniParser($this->detailsIniFile['full_file_path']);
            $this->config = $iniParser->parse();
        }
        $this->_setConfig_();
    }

    function printAllSettings()
    {
        $retStr = "CONFIG = " . PHP_EOL;
        $retStr = var_export(object_to_array($this->config), true) . PHP_EOL;
        $retStr = $retStr . 'INI file details = ' . var_export($this->detailsIniFile, true) . PHP_EOL;
        $retStr = $retStr . 'Input file details = ' . var_export($this->arrInputFiles, true) . PHP_EOL;
        $retStr = $retStr . 'Output file details = ' . var_export($this->detailsOutputFile, true) . PHP_EOL;
        $retStr = $retStr . 'Email addresses = ' . var_export($this->arrEmailAddresses, true) . PHP_EOL;

        $retStr = $retStr . PHP_EOL. '$GLOBAL[OPTS] = ' . PHP_EOL;
        $retStr = $retStr . var_export($GLOBALS['OPTS'], true) . PHP_EOL;

        return $retStr;

    }

    function keys($strKey) { if($this->config && $this->config->keys) { return $this->config->keys[$strKey]; } else return null; }

        function getINIFileDetails()
    {
        return $this->detailsIniFile;
    }


    function getOutputFileDetails()
    {
        return $this->detailsOutputFile;
    }


    function getInputFilesDetails($strTypeKey = null)
    {
        if($strTypeKey == null)
            return $this->arrInputFiles;
        else
            return $this->__getInputFilesByValue__('file_use_type', $strTypeKey );
    }


    function createOutputSubFolder($strSubDirName)
    {
        // Append the file name base to the directory as a new subdirectory for output
        $fullNewDirectory = $this->detailsOutputFile['directory'] . $strSubDirName;
        __debug__printLine("Attempting to create output subdirectory: " . $fullNewDirectory , C__DISPLAY_ITEM_START__);
        if(is_dir($fullNewDirectory))
        {

        }
        else
        {
            if (!mkdir($fullNewDirectory, 0777, true))
            {
                throw new ErrorException('Failed to create the output folder: '.$fullNewDirectory);
            }
        }
        __debug__printLine("Created folder for results output: " . $fullNewDirectory , C__DISPLAY_SUMMARY__);

        // return the new directory details
        return parseFilePath($fullNewDirectory, false);
    }


    private function _setConfig_()
    {
        if($this->config->output)
        {
            if($this->config->output->folder)
            {
                $this->detailsOutputFile = parseFilePath($this->config->output->folder, false);
            }

            if($this->config->output->file)
            {
                $this->detailsOutputFile = parseFilePath($this->detailsOutputFile['directory'] . $this->config->output->file);
            }

        }

        if($this->config->emails )
        {
            foreach($this->config->emails as $emailItem)
            {
                if($emailItem != null && $emailItem != "")
                {
                    $tempEmail = $this->__getEmptyEmailRecord__();
                    $tempEmail['name'] = $emailItem['name'];
                    $tempEmail['address'] = $emailItem['address'];
                    $tempEmail['type'] = $emailItem['type'];
                    $this->arrEmailAddresses[] = $tempEmail;
                }
            }
        }

        $pathInput = "";
        if($this->config->input && $this->config->input->folder)
        {
            $pathInput = parseFilePath($this->config->input->folder);
        }

        if($this->config->inputfiles)
        {
            foreach($this->config->inputfiles as $iniInputFile)
            {
                $tempFileDetails = parseFilePath($pathInput['directory'].$iniInputFile['name'], true);

                __debug__printLine("Processing input file '" . $pathInput['directory'].$iniInputFile['name'] . "' with type of '". $iniInputFile['type'] . "'...", C__DISPLAY_NORMAL__);
                $this->__addInputFile__($tempFileDetails, $iniInputFile['type'], $iniInputFile['sheet']);

            }
        }

    }

    private function __addInputFile__($fileDetails, $file_use, $excel_sheet_name)
    {
        $this->arrInputFiles[] = array('details'=> $fileDetails, 'file_use_type' => $file_use, 'worksheet_name'=>$excel_sheet_name);
    }



    private function __getInputFilesByValue__($valKey, $val)
    {
        $ret = null;
        foreach($this->arrInputFiles as $fileItem)
        {
            if(strcasecmp($fileItem[$valKey], $val) == 0)
            {
                $ret[] = $fileItem;
            }
        }

        return $ret;
    }

    private function __getEmptyEmailRecord__()
    {
        return array('type'=> null, 'name'=>null, 'address' => null);
    }


}