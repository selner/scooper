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


class classMacUI_SingleLookup extends classMacUI
{
    function getUserInput()
    {
        $appConf = $this->_get_pashua_lookup_UI_();

        # Pass the configuration string to the Pashua module
        $dialog_result = pashua_run($appConf, $encoding = 'utf8', $apppath = dirname(__FILE__));

        return $this->_updateOptionsFromPashua_($dialog_result);
    }


    private function _get_pashua_lookup_UI_()
    {

        $conf = "
    # Set transparency: 0 is transparent, 1 is opaque
    *.transparency=0.95

    # Set window title
    *.title = " . C__APPNAME__." Company Lookup

    # Introductory text
    intro_txt.type = text
    intro_txt.default = THIS SCREEN IS TBD.
    #intro_txt.height = 276
    intro_txt.width = 500
    #intro_txt.x = 10
    #intro_txt.y = 10

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
        $conf = $conf . "path_out.default = "  . $GLOBALS['OPTS']['outputfile'].PHP_EOL;

        return $conf;
    }



    public static function _updateOptionsFromPashua_($arrPashuaResults)
    {

        if($arrPashuaResults['ok_button'] && $arrPashuaResults['ok_button'] == 1)
        {
            $GLOBALS['OPTS']['exclude_quantcast'] = $arrPashuaResults['check_quant'];
            $GLOBALS['OPTS']['exclude_moz'] = $arrPashuaResults['check_moz'];
            $GLOBALS['OPTS']['exclude_crunchbase'] = $arrPashuaResults['check_cb'];
            $GLOBALS['OPTS']['outputfile'] = $arrPashuaResults['path_out'];


            if(!file_exists(dirname($GLOBALS['OPTS']['outputfile'])) )
            {
                $strLocType = 'folder';
                if(is_file($GLOBALS['OPTS']['outputfile'])) { $strLocType = 'file'; }
                $strErrorMessage = $strErrorMessage . "[return]- The ".$strLocType." '".$GLOBALS['OPTS']['outputfile']."' is not a valid directory or file for output..";
            }

            if(strlen($strErrorMessage) > 0)
            {
                $strErrorMessage = "The following settings were not valid:" . $strErrorMessage."[return][return]Please re-check them.";

                return parent::_showErrorDialog_($strErrorMessage );
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