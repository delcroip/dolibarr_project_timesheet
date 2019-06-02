<?php
/*
 * Copyright (C) 2016 delcroip <patrick@pmpd.eu>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY;without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
include '../lib/includeMain.lib.php';

//global $langs;
$langs->load('timesheet@timesheet');
//define('$conf->global->TIMESHEET_DAY_MAX_DURATION', '12');
header('Content-Type: text/javascript');
echo 'var day_max_hours ='.null2zero($conf->global->TIMESHEET_DAY_MAX_DURATION).";\n";
echo 'var day_hours ='.null2zero($conf->global->TIMESHEET_DAY_DURATION).";\n";
echo 'var time_type = "'.$conf->global->TIMESHEET_TIME_TYPE."\";\n";
echo 'var hide_zero ='.null2zero($conf->global->TIMESHEET_HIDE_ZEROS).";\n";
echo 'var err_msg_max_hours_exceded = "'.rtrim($langs->transnoentitiesnoconv('errMsgMaxHoursExceded'))."\";\n";//FIXTRAD
echo 'var wng_msg_hours_exceded = "'.rtrim($langs->transnoentitiesnoconv('wngMsgHoursExceded'))."\";\n";//FIXTRAD

/** function to avoid null returned for an int
 *
 * @param int $value int to check
 * @return int int value or 0 if int is null
 */
function null2zero($value = '')
{
    return (empty($value))?0:$value;
}
