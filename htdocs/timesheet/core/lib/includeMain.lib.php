<?php
/*
 * Copyright (C) 2015           Patrick DELCROIX     <patrick@pmpd.eu>
 *
 * This program is free software;you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation;either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY;without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */
//global $db;

// Define status


$res = 0;
error_reporting(E_ALL);
$currentTimesheetPath = dirname(__FILE__);
if (! $res && file_exists($currentTimesheetPath."/dev.inc.php")) {
    include $currentTimesheetPath.'/dev.inc.php';
}

$root = dirname($_SERVER["DOCUMENT_ROOT"]);
$filePath = $root . "/html/main.inc.php";
if (! $res && file_exists($filePath)) {
    $res = @include $filePath;
} else {
    $maxDepth = 6;
    for ($i = 3; $i <= $maxDepth && !$res; $i++) {
        $dirPath = $currentTimesheetPath . str_repeat("/..", $i) . "/";
        $filePath = $dirPath . "main.inc.php";
        if (! $res && file_exists($filePath)) {
            $res = @include $filePath;
            break;
            //$_SERVER["CONTEXT_DOCUMENT_ROOT"] = realpath($dirPath);
        }
    }
}
if (! $res) die("Include of main fails") ;

if ($user->admin && version_compare("4.3.10", $conf->global->TIMESHEET_VERSION) > 0){
    setEventMessage("Version of timesheet updated, please deactivate then reactivate the module", 'warnings');
}
// return from functions
define("BADPARAM",-2);
define("OK",1);
define("NOK",-1);
define("NOTCREATED",-3);
define("NOTUPDATED",-4);
define("CHILDNOTCREATED",-5);