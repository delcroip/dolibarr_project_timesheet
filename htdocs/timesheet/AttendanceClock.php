<?php
/* Copyright (C) 2007-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2018 delcroip <patrick@pmpd.eu>
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
/**
 *        \file       dev/skeletons/skeleton_page.php
 *                \ingroup    mymodule othermodule1 othermodule2
 *                \brief      This file is an example of a php page
 *                                        Put here some comments
 */
// hide left menu
//$_POST['dol_hide_leftmenu'] = 1;
// Change this following line to use the correct relative path (../, ../../, etc)
include 'core/lib/includeMain.lib.php';
require_once 'core/lib/timesheet.lib.php';
require_once 'class/TimesheetAttendanceEvent.class.php';
require_once 'class/TimesheetTask.class.php';
if(!$user->rights->timesheet->attendance->user) {
    $accessforbidden = accessforbidden("You don't have the attendance/chrono user right");
}
$tms = GETPOST('tms', 'alpha');
$action = GETPOST('action', 'alpha');
$project = GETPOST('project', 'int');
$task = GETPOST('taskid', 'int');
$customer = GETPOST('customer', 'int');
$json = $_POST['json'];//], 'alpha');
$today = time();
// Load traductions files requiredby by page
//$langs->load("companies");
$langs->load("main");
$langs->load("projects");
$langs->load('timesheet@timesheet');
$userid = $user->id;
$timesheet_attendance = new Attendanceevent($db, $userid);
/*******************************************************************
* ACTIONS
*
* Put here all code to do according to value of "action" parameter
********************************************************************/
$status = '';
$update = false;
switch($action) {
    case 'start':
        $json = $timesheet_attendance->ajaxStart($user, $json, $customer, $project, $task);
       // ob_clean();
        header("Content-type: text/json;charset = utf-8");
        echo $json;
        ob_end_flush();
        exit();
    case 'stop':
        $json = $timesheet_attendance->ajaxStop($user, $json);
       // ob_clean();
        header("Content-type: text/json;charset = utf-8");
        echo $json;
        ob_end_flush();
        exit();
    case 'heartbeat':
        $json = $timesheet_attendance->ajaxheartbeat($user, $json);
       // ob_clean();
        header("Content-type: text/json;charset = utf-8");
        echo $json;
        ob_end_flush();
        exit();
    default:
        break;
}
if(!empty($tms)) {
    unset($_SESSION['timesheet_attendance'][$tms]);
}
//$timesheet_attendance->fetchAll($today);//FIXME: fetcht the list project/task
/***************************************************
* VIEW
*
* Put here all code to build page
****************************************************/
$morejs = array("/timesheet/core/js/stopWatch.js?".$conf->global->TIMESHEET_VERSION, "/timesheet/core/js/timesheet.js?".$conf->global->TIMESHEET_VERSION);
$morecss = array("/timesheet/core/css/stopWatch.css");
llxHeader('', $langs->trans('Attendance'), '', '', '', "", $morejs, $morecss);
//calculate the week days
// clock
$timesheet_attendance->fetch('', $user);
$timesheet_attendance->printHTMLClock();
//tmstp = time();
//fetch ts for others
if(isset($conf->global->TIMESHEET_ADD_FOR_OTHER) && $conf->global->TIMESHEET_ADD_FOR_OTHER == 1 && (count($SubordiateIds)>1 || $user->admin)) {
    //print $timesheet_attendance->getHTMLGetOtherUserTs($SubordiateIds, $userid, $user->admin);
}
$headers = explode('||', $conf->global->TIMESHEET_HEADERS);
// remove tta note as it is useless there
$key = array_search('Note', $headers);
if($key !== false){
    unset($headers[$key]);
}
// remove total
$key = array_search('Total', $headers);
if($key !== false){
    unset($headers[$key]);
}
$ajax = false;
 // $timesheet_attendance->fetchStarted();//FIXMED
//headers
$html .= "<table id='chronoTable' class = 'noborder' width = '100%'>";
$html .= "<tr>";
foreach($headers as $key => $value) {
    $html .= "\t<th ";
    if(count($headers) == 1) {
           $html .= 'colspan = "2" ';
    }
    $html .= "> <a onclick=\"sortTable('chronoTable','col{$value}','asc');\">".$langs->trans($value)."</a></th>\n";
}
$html .= "<th>".$langs->trans("Action")."</th></tr>";
// show the filter
$html .= '<tr class = "timesheet_line" id = "searchline">';
$html .= '<td><a>'.$langs->trans("Search").'</a></td>';
$html .= '<td span = "0"><input type = "texte" name = "taskSearch" onkeyup = "searchTask(this)"></td></tr>';
$html .= $timesheet_attendance->printHTMLTaskList($headers, $user->id);
$html .= "</table>";
//Javascript
//$Form .= ' <script type = "text/javascript" src = "core/js/timesheet.js"></script>'."\n";
$html .= '<script type = "text/javascript">'."\n\t";
$html .= "let stopwatch = new Stopwatch(document.getElementById('stopwatch'));stopwatch.load();";
$html .= "\n\t".'</script>'."\n";
// $Form .= '</div>';//TimesheetPage
print $html;
//add attachement
// End of page
llxFooter();
$db->close();
