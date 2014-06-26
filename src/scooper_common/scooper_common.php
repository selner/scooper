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
 *
 *
 * @author Bryan Selner (dev@recoilvelocity.com)
 * @version 1.5 ($Rev: 196 $)
 * @package PlaceLocalInclude
 * @subpackage scooper_common
 */



/****************************************************************************************************************/
/****                                                                                                        ****/
/****         Main Library Include for all Components                                                        ****/
/****                                                                                                        ****/
/****************************************************************************************************************/
define('__SCROOT__', dirname(__FILE__));

/*
 *
 define('__BASE_DIR__', dirname(dirname(dirname(__FILE__))));
if (file_exists(__BASE_DIR__. '/vendor/autoload.php')) {
    require_once(__BASE_DIR__. '/vendor/autoload.php');
} else {
    trigger_error("Composer required for this library.");
}
*/

require_once(__SCROOT__ . '/include/common.php');
require_once(__SCROOT__ . '/include/ClassScooperSimpleCSVFile.php');
require_once(__SCROOT__ . '/include/helpers_file.php');
require_once(__SCROOT__ . '/include/helpers_logging.php');
require_once(__SCROOT__ . '/include/ClassScooperAPIWrapper.php');
require_once(__SCROOT__ . '/include/ClassScooperConfigFile.php');

