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
 *                \ingroup    timesheet othermodule1 othermodule2
 *                \brief      This file is an example of a php page
 *                                        Put here some comments
 */
// hide left menu
//$_POST['dol_hide_leftmenu'] = 1;
// Change this following line to use the correct relative path (../, ../../, etc)
include 'core/lib/includeMain.lib.php';
require_once 'core/lib/timesheet.lib.php';
require_once 'class/AttendanceEvent.class.php';
require_once 'class/TimesheetTask.class.php';
require_once 'core/lib/generic.lib.php';
$admin = $user->admin || $user->rights->timesheet->attendance->admin;
if (!$user->rights->timesheet->attendance->user && !$admin) {
    $accessforbidden = accessforbidden("You don't have the attendance/chrono user or admin right");
}
$token = GETPOST('token', 'alpha');
$action = GETPOST('action', 'alpha');
$project = GETPOST('project', 'int');
$task = GETPOST('taskid', 'int');
$customer = GETPOST('customer', 'int');
$json = GETPOST('json');//], 'alpha');
$today = time();
// Load traductions files requiredby by page
//$langs->load("companies");
$langs->load("main");
$langs->load("projects");
$langs->load('timesheet@timesheet');
$userid = $user->id;
$postUserId = GETPOST('userid', 'int');

// if the user can enter ts for other the user id is diferent
if ( getConf('TIMESHEET_ADD_FOR_OTHER') == 1) {
    if (!empty($postUserId)) {
        $newuserid = $postUserId;
    }else{
        $newuserid =$user->id;
    }
    
    $SubordiateIds = getSubordinates($db, $user->id, 2, array(), ALL, $entity = '1');
    //$SubordiateIds[] = $user->id;
    if (in_array($newuserid, $SubordiateIds) || $admin) {
        $SubordiateIds[] = $userid;
        $userid = $newuserid;
    } elseif ($action == 'getOtherTs') {
        setEventMessage($langs->transnoentitiesnoconv("NotAllowed"), 'errors');
        unset($action);
    }
    
}
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
        ob_clean();
        header("Content-type: text/json;charset = utf-8");
        echo $json;
        ob_end_flush();
        exit();
    case 'stop':
        $json = $timesheet_attendance->ajaxStop($user, $json);
       // ob_clean();
        header("Content-type: text/json;charset = utf-8");
        ob_clean();
        echo $json;
        ob_end_flush();
        exit();
    case 'heartbeat':
        $json = $timesheet_attendance->ajaxheartbeat($user, $json);
       // ob_clean();
        header("Content-type: text/json;charset = utf-8");
        ob_clean();
        echo $json;
        ob_end_flush();
        exit();
    default:
        break;
}
if (!empty($token)) {
    unset($_SESSION['timesheet'][$token]);
}
$token = getToken();
$_SESSION['timesheet'][$token] = array();
/***************************************************
* VIEW
*
* Put here all code to build page
****************************************************/
$morejs = array("/timesheet/core/js/stopWatch.js?".getConf('TIMESHEET_VERSION'), 
    "/timesheet/core/js/timesheet.js?".getConf('TIMESHEET_VERSION'));
$morecss = array("/timesheet/core/css/stopWatch.css");
llxHeader('', $langs->trans('Attendance'), '', '', '', "", $morejs, $morecss);

//calculate the week days
// clock
$timesheet_attendance->fetch('', $user);
$timesheet_attendance->token = $token;
if (getConf('TIMESHEET_ADD_FOR_OTHER') == 1 
    && (  $SubordiateIds > 0 ||  $admin)) {
        print $timesheet_attendance->getHTMLGetOtherUserTs($SubordiateIds, $userid, $admin);
}
$timesheet_attendance->printHTMLClock();
$headers = explode('||', getConf('TIMESHEET_HEADERS'));
// remove tta note as it is useless there
$key = array_search('Note', $headers);
if ($key !== false){
    unset($headers[$key]);
}
// remove total
$key = array_search('Total', $headers);
if ($key !== false){
    unset($headers[$key]);
}
$ajax = false;

//headers
$html = "<table id='chronoTable' class = 'noborder' width = '100%'>";
$html .= "<tr>";
foreach ($headers as $key => $value) {
    $html .= "\t<th ";
    if (count($headers) == 1) {
           $html .= 'colspan = "2" ';
    }
    $html .= "> <a onclick=\"sortTable('chronoTable', 'col{$value}', 'asc');\">".$langs->trans($value)."</a></th>\n";
}
$html .= "<th>".$langs->trans("Action")."</th></tr>";
// show the filter
$html .= '<tr class = "timesheet_line" id = "searchline">';
$html .= '<td><a>'.$langs->trans("Search").'</a></td>';

if (getConf('TIMESHEET_WHITELIST') == 1) {
   $html .= '<div class = "tabs" data-role = "controlgroup" data-type = "horizontal"  >';
   $html .= '  <div '.((getConf('TIMESHEET_WHITELIST_MODE') == 2)?'id = "defaultOpen"':'')
        .' class = "inline-block tabsElem" onclick = "showFavoris(event,\'All\')">'
        .'<a  href = "javascript:void(0);"  class = "tabunactive tab inline-block" data-role = "button">'
        .$langs->trans('All').'</a></div>';
   $html .= '  <div '.((getConf('TIMESHEET_WHITELIST_MODE') == 0)?'id = "defaultOpen"':'')
        .' class = "inline-block tabsElem" onclick = "showFavoris(event,\'whitelist\')">'
        .'<a  href = "javascript:void(0);" class = "tabunactive tab inline-block" data-role = "button">'
        .$langs->trans('blackWhiteList').'</a></div>';
   $html .= '  <div '.((getConf('TIMESHEET_WHITELIST_MODE') == 1)?'id = "defaultOpen"':'')
        .' class = "inline-block tabsElem"  onclick = "showFavoris(event,\'blacklist\')">'
        .'<a href = "javascript:void(0);" class = "tabunactive tab inline-block" data-role = "button">'
        .$langs->trans('Others').'</a></div>';
   $html .= '</div>';       
}
$html .= '<td span = "0"><input type = "texte" name = "taskSearch" onkeyup = "searchTask(this)"></td></tr>';
$html .= '<input type = "hidden" id="csrf-token" name = "token" value = "'.$token."\"/>\n";
$htmltmp = $timesheet_attendance->printHTMLTaskList($headers, $userid);
$pattern  = "/(progressTask\[[^\]]+\]\[[^\]]+\])/i";
$replacement = '$1" onchange="updateProgress(event);';
$html .=  preg_replace($pattern, $replacement, $htmltmp);
$html .= "</table>";
//Javascript
//$Form .= ' <script type = "text/javascript" src = "core/js/timesheet.js"></script>'."\n";
$html .= '<script type = "text/javascript">'."\n\t";
$html .= "let stopwatch = new Stopwatch(document.getElementById('stopwatch'),".$userid.", '".$token."');stopwatch.load();";
$html .= "updateAllProgress();\n";
$html .= "var _eldo = document.getElementById('defaultOpen'); if(_eldo) _eldo.click();\n";
$html .= "\n\t".'</script>'."\n";
// $Form .= '</div>';//TimesheetPage
print $html;
//add attachement
// End of page
llxFooter();
$db->close();
