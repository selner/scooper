<?php

require_once 'pashua_wrapper_functions.php';
// Define what the dialog should be like
// Take a look at Pashua's Readme file for more info on the syntax

class MacSettingsUIClass
{

    function getOptionsFromUser()
    {
        $appConf = $this->_get_pashua_Settngs_UI_();

        # Pass the configuration string to the Pashua module
        $dialog_result = pashua_run($appConf, $encoding = 'utf8', $apppath = dirname(__FILE__));

        $this->_updateOptionsFromPashua_($dialog_result);
    }


    private function _get_pashua_Settngs_UI_()
    {

        $conf = "
    # Set transparency: 0 is transparent, 1 is opaque
    *.transparency=0.95

    # Set window title
    *.title = Site Evaluator

    # Introductory text
    intro_txt.type = text
    intro_txt.default = This app athers site data from Quantcast, Moz.com and Crunchbase for a list of company names or company URLs input as a CSV file.  Fields returned include estimated monthly uniques, company type, description, domain authority and many others.[return][return]     INPUT CSV FORMAT:[return]     Line 1:   File List Type { 'Company Name', 'URL' }[return]     Line 2+: String Values for Names or URLs[return][return]     Example:[return]          \"Company Name\"[return]          \"Apple\"[return]          \"Google\"[return]           ...etc
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
        $conf = $conf . "path_in.default = "  . $GLOBALS['OPTS']['inputfile'].PHP_EOL;
        $conf = $conf . "path_out.default = "  . $GLOBALS['OPTS']['outputfile'].PHP_EOL;

        return $conf;
    }



    private function _updateOptionsFromPashua_($arrPashuaResults)
    {

        if($arrPashuaResults['ok_button'] && $arrPashuaResults['ok_button'] == 1)
        {
            $GLOBALS['OPTS']['exclude_quantcast'] = $arrPashuaResults['check_quant'];
            $GLOBALS['OPTS']['exclude_moz'] = $arrPashuaResults['check_moz'];
            $GLOBALS['OPTS']['exclude_crunchbase'] = $arrPashuaResults['check_cb'];
            $GLOBALS['OPTS']['inputfile'] = $arrPashuaResults['path_in'];
            $GLOBALS['OPTS']['outputfile'] = $arrPashuaResults['path_out'];

            $strErrorMessage = "";
            if(!file_exists($GLOBALS['OPTS']['inputfile']) || !is_file($GLOBALS['OPTS']['inputfile']) )
            {
                $strErrorMessage = $strErrorMessage . "[return]- The file '".$GLOBALS['OPTS']['inputfile']."' is not a valid input CSV file.";
            }

            if(!file_exists(dirname($GLOBALS['OPTS']['outputfile'])) )
            {
                $strLocType = 'folder';
                if(is_file($GLOBALS['OPTS']['outputfile'])) { $strLocType = 'file'; }
                $strErrorMessage = $strErrorMessage . "[return]- The ".$strLocType." '".$GLOBALS['OPTS']['outputfile']."' is not a valid directory or file for output..";
            }

            if(strlen($strErrorMessage) > 0)
            {
                $strErrorMessage = "The following settings were not valid:" . $strErrorMessage."[return][return]Please re-check them.";

                $this->_showErrorDialog_($strErrorMessage );
            }
        }
        else
        {
            exit("User cancelled.");
        }


        return;
    }



    private function _showErrorDialog_($strErrorText)
    {

        $confErrDialog = "
            # Set transparency: 0 is transparent, 1 is opaque
            *.transparency=0.95

            # Set window title
            *.title = Invalid Configuration Settings

            # Introductory text
            intro_txt.type = text
            intro_txt.default = ". $strErrorText."
            intro_txt.height = 276
            intro_txt.width = 310
            intro_txt.x = 340
            intro_txt.y = 44


            # Add a cancel button with default label
            button_cancel.type=cancelbutton
            ok_button.label = Exit App

            # Add a cancel button with default label
            ok_button.type = defaultbutton
            ok_button.label = Edit Settings

            ";

        # Pass the configuration string to the Pashua module
        $dialog_result = pashua_run($confErrDialog, 'utf8', null);

        if($dialog_result['button_cancel'] == 1)
        {
            exit("User clicked \"Exit App\".");
        }

        $this->getOptionsFromUser();
    }
}



?>