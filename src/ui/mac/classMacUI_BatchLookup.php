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

require_once dirname(__FILE__).'/pashua_wrapper.php';


class classMacUI_BatchLookup extends classMacUI
{

    function getOptionsFromUser()
    {

        $conf = "
        # Set transparency: 0 is transparent, 1 is opaque
        *.transparency=0.95

        # Set window title
        *.title = " . C__APPNAME__." Bulk Search

        # Introductory text
        intro_txt.type = text
        intro_txt.default = This app gathers site data from Quantcast, Moz.com and Crunchbase for a list of company names or company URLs input as a CSV file.  Fields returned include estimated monthly uniques, company type, description, domain authority and many others.[return][return]     INPUT CSV FORMAT:[return]     Line 1:   File List Type { 'Company Name', 'URL' }[return]     Line 2+: String Values for Names or URLs[return][return]     Example:[return]          \"Company Name\"[return]          \"Apple\"[return]          \"Google\"[return]           ...etc
        #intro_txt.height = 276
        intro_txt.width = 500
        #intro_txt.x = 10
        #intro_txt.y = 10

        # InputFile
        path_in.type = openbrowser
        path_in.label = Select the Input CSV File...
        #path_in.x = 350
        #path_in.y = 10
        path_in.width=310
        path_in.tooltip = Browse...

        # OutputFilePath
        path_out.type = openbrowser
        path_out.label = Select the Output Directory...
        path_out.width=310
        path_out.tooltip = Browse...

        #exclude intro
        excludes_intro.type = text
        excludes_intro.default = Exclude the following data sets:

        # Crunchbase
        check_cb.type = checkbox
        check_cb.label = Crunchbase
        check_cb.default = 0

        # Moz
        check_moz.type = checkbox
        check_moz.label = Moz
        check_moz.default = 0

        # Quantcast
        check_quant.type = checkbox
        check_quant.label = Quantcast
        check_quant.default = 0


        # Add a cancel button with default label
        button_cancel.type=cancelbutton

        # Add a cancel button with default label
        ok_button.type = defaultbutton
        ok_button.label = Run App


        ";
            // Set the images' paths relative to this file's path /
            // skip images if they can not be found in this file's path
            $bgimg = dirname(__FILE__).'/.demo.png';
            $icon  = dirname(__FILE__).'/.icon.png';

            if (file_exists($icon)) {
                // Display Pashua's icon
                $conf .= "img.type = image
                          img.x = 530
                          img.y = 255
                          img.path = $icon\n";
            }

            if (file_exists($bgimg)) {
                // Display background image
                $conf .= "bg.type = image
                          bg.x = 30
                          bg.y = 2
                          bg.path = $bgimg";
            }

            $conf = $conf . "check_quant.default = " . $GLOBALS['OPTS']['exclude_quantcast'].PHP_EOL;
            $conf = $conf . "check_moz.default = " . $GLOBALS['OPTS']['exclude_moz'].PHP_EOL;
            $conf = $conf . "check_cb.default = "  . $GLOBALS['OPTS']['exclude_crunchbase'].PHP_EOL;
            $conf = $conf . "path_in.default = "  . $GLOBALS['input_file_details']['full_file_path'].PHP_EOL;
            $conf = $conf . "path_out.default = "  .$GLOBALS['output_file_details']['full_file_path'].PHP_EOL;

        $this->showUI($conf, array($this, 'updateOptionsFromPashua'));

    }


    public static function updateOptionsFromPashua($arrPashuaResults)
    {
        __log__('updateOptionsFromPashua results = { '.var_export($arrPashuaResults).'}', C__LOGLEVEL_DEBUG__);


        if($arrPashuaResults['ok_button'] && $arrPashuaResults['ok_button'] == 1)
        {
            __log__(var_export($arrPashuaResults), C__LOGLEVEL_INFO__);


            $GLOBALS['OPTS']['exclude_quantcast'] = $arrPashuaResults['check_quant'];
            $GLOBALS['OPTS']['exclude_moz'] = $arrPashuaResults['check_moz'];
            $GLOBALS['OPTS']['exclude_crunchbase'] = $arrPashuaResults['check_cb'];
            $GLOBALS['OPTS']['inputfile'] = $arrPashuaResults['path_in'];
            $GLOBALS['OPTS']['outputfile'] = $arrPashuaResults['path_out'];


            $strArgErrs = __check_args__();


            if(strlen($strArgErrs) > 0)
            {
                $strErrorMessage = "The following settings were not valid:[return][return]" . $strArgErrs."[return][return]Please re-check them.";
                __log__($strArgErrs, C__LOGLEVEL_WARN__);
                parent::showErrorDialog($strErrorMessage, array($this, 'getOptionsFromUser'));
            }
        }
        else
        {
            exit("User cancelled.");
        }


        return;
    }



}



?>