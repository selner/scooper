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

define('__ROOT__', dirname(dirname(__FILE__)));
require_once(__ROOT__ . '/main/scooper.php');

$classcb = new CrunchbasePluginClass(false);

//$classcb->dumpCompanyInfoFromListOfPermalinks("/Users/bryan/Desktop/ApptentiveCompeteURLs.csv", "/Users/bryan/OneDrive/OneDrive-JobSearch/portfolio_companies_needing_data_output.csv" );

__main__();


?>