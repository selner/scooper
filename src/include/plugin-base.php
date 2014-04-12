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
/****         Base Class:  Scooter Site Data Plugin                                                          ****/
/****                                                                                                        ****/
/****************************************************************************************************************/
require_once dirname(__FILE__) . '/../scooper_common/common.php';
require_once dirname(__FILE__) . '/../scooper_common/SimpleScooterCSVFileClass.php';
require_once dirname(__FILE__) . '/../scooper_common/APICallWrapperClass.php';



const C__FEXCLUDE_DATA_YES = 1;
const C__FEXCLUDE_DATA_NO = 0;


class ScooterPluginBaseClass
{

    // method declaration
    function addDataToRecord(&$arrRecordToUpdate) 
	{
         throw new Exception("addDataToRecord must be defined for any class extending ScooterPluginBaseClass. ");
    }

   private function _getData_($var) 
	{
        throw new Exception("_getData_ must be defined for any class extending ScooterPluginBaseClass. ");
    }

    function _expandArrays_(&$arrToExpand)
    {

        $values = $arrToExpand;
        $keys = array_keys($arrToExpand);
        $nIndex = 0;
        foreach($values as $val)
        {
            if(is_array($val))
            {
                $arrNewValue = array();
                if(count($val) > 1)
                    $sep = " | ";
                else

                    $sep = "";

                foreach($val as $subItem)
                {
                    if(is_array($subItem))
                    {
                        $arrNewValue[] = implode(" ", array_flatten_sep(".", $subItem));
                    }
                    else
                    {
                        $arrNewValue[] = $subItem;
                    }
                }
                $strNewValue = implode($sep, $arrNewValue);
                $arrToExpand[$keys[$nIndex]] =$strNewValue;

            }

            $nIndex++;
        }
    }


    private $_strPluginDataProviderName_ = "UNKNOWN";
}



?>