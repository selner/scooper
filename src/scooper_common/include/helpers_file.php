<?php

/****************************************************************************************************************/
/****                                                                                                        ****/
/****         Helper Functions:  Files                                                                       ****/
/****                                                                                                        ****/
/****************************************************************************************************************/
define('__SCROOT__', dirname(dirname(__FILE__)));
require_once(__SCROOT__ . '/scooper_common.php');


const C__DEBUG_MODE__ = false;


function getFullPathFromFileDetails($arrFileDetails, $strPrependToFileBase = "", $strAppendToFileBase = "")
{
    return $arrFileDetails['directory'] . getFileNameFromFileDetails($arrFileDetails, $strPrependToFileBase, $strAppendToFileBase);

}

function getFileNameFromFileDetails($arrFileDetails, $strPrependToFileBase = "", $strAppendToFileBase = "")
{
    return $strPrependToFileBase . $arrFileDetails['file_name_base'] . $strAppendToFileBase . "." . $arrFileDetails['file_extension'];
}

function parseFilePath($strFilePath, $fFileMustExist = false)
{
    $arrReturnFileDetails = array ('full_file_path' => '', 'directory' => '', 'file_name' => '', 'file_name_base' => '', 'file_extension' => '');


    if(strlen($strFilePath) > 0)
    {
        if(is_dir($strFilePath))
        {
            $arrReturnFileDetails['directory'] = $strFilePath;
        }
        else
        {

            // separate into elements by '/'
            $arrFilePathParts = explode("/", $strFilePath);

            if(count($arrFilePathParts) <= 1)
            {
                $arrReturnFileDetails['directory'] = ".";
                $arrReturnFileDetails['file_name'] = $arrFilePathParts[0];
            }
            else
            {
                // pop the last element (the file name + extension) into a string
                $arrReturnFileDetails['file_name'] = array_pop($arrFilePathParts);

                // put the rest of the path parts back together into a path string
                $arrReturnFileDetails['directory']= implode("/", $arrFilePathParts);
            }

            if(strlen($arrReturnFileDetails['directory']) == 0 && strlen($arrReturnFileDetails['file_name']) > 0 && file_exists($arrReturnFileDetails['file_name']))
            {
                $arrReturnFileDetails['directory'] = dirname($arrReturnFileDetails['file_name']);

            }

            if(!is_dir($arrReturnFileDetails['directory']))
            {
                __log__('Specfied path '.$strFilePath.' does not exist.', C__LOGLEVEL_WARN__);
            }
            else
            {
                // since we have a directory and a file name, combine them into the full file path
                $arrReturnFileDetails['full_file_path'] = $arrReturnFileDetails['directory'] . "/" . $arrReturnFileDetails['file_name'];

                // separate the file name by '.' to break the extension out
                $arrFileNameParts = explode(".", $arrReturnFileDetails['file_name']);

                // pop off the extension
                $arrReturnFileDetails['file_extension'] = array_pop($arrFileNameParts );

                // put the rest of the filename back together into a string.
                $arrReturnFileDetails['file_name_base'] = implode(".", $arrFileNameParts );


                if($fFileMustExist == true && !is_file($arrReturnFileDetails['full_file_path']))
                {
                    __log__('Required file '.$arrReturnFileDetails['full_file_path'].' does not exist.', C__LOGLEVEL_WARN__);
                }
            }
        }
    }

    // Make sure the directory part ends with a slash always
    $strDir = $arrReturnFileDetails['directory'];

    if((strlen($strDir) >= 1) && $strDir[strlen($strDir)-1] != "/")
    {
        $arrReturnFileDetails['directory'] = $arrReturnFileDetails['directory'] . "/";
    }

    return $arrReturnFileDetails;

}
