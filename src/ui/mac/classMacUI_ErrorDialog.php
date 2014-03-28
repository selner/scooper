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

class classMacUI_ErrorDialog extends classMacUI
{

    private $_callback_ = null;

    function show($strErrorText, $callback)
    {

        $this->_callback_ = $callback;

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
            ok_button.type = defaultbutton
            ok_button.label = Edit Settings

            ";

        $this->showUI($confErrDialog, array($this, 'dialogClosed'));
    }

    public static function dialogClosed($dataResults)
    {
        __log__('dialogClosed results = { '.var_export($dataResults).'}', C__LOGLEVEL_DEBUG__);


        // BUGBUG: NEEDS TO CALLBACK TO THE ORIGINAL SETTINGS

        $prevCallback = $this->_callback_;
        //
        // if we have a handler for the results, call it now
        //
        if ($prevCallback && is_callable($prevCallback))
        {
            call_user_func($prevCallback, $dataResults);
        }
    }



} 