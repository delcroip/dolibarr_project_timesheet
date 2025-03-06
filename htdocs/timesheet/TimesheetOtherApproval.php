<?php
/* Copyright (C) 2017 delcroip <patrick@pmpd.eu>
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
define('TIMESHEET_MAX_TTA_APPROVAL', 100);
define('TIMESHEET_GROUP_OTHER_AP', "week");
include 'core/lib/includeMain.lib.php';
require_once 'core/lib/timesheet.lib.php';
require_once 'core/lib/generic.lib.php';
require_once 'class/TimesheetTask.class.php';
$admin = $user->admin || $user->rights->timesheet->approval->admin;
if (!$user->rights->timesheet->approval->other && !$admin) {
    $accessforbidden = accessforbidden("You don't have the approval projet or admin right");
}
/*******************************************************************
* ACTIONS
*
* Put here all code to do according to value of "action" parameter
********************************************************************/
$userId = is_object($user)?$user->id:$user;
// find the Role //FIX ME SHOW ONLY if he has right
$role = GETPOST('role', 'alpha');
$role_key = '';
if (!$role) {
    $role_key = array_search('1', array_slice($apflows, 2))+1;// search other than team
    if ($role_key == false) {
        header("location:TimesheetTeamApproval.php");
    } else {
        $role_key++;
        $role = $roles[$role_key];
    }
} else {
    $role_key = array_search($role, $roles);
}
// end find the role
// get other param
$action = GETPOST('action', 'alpha');
$view = GETPOST('view', 'alpha');
if ($view != ''){
    $action = $view;
}
$offset = GETPOST('offset', 'int');
if (!is_numeric($offset))$offset = 0;
$optioncss = GETPOST('optioncss', 'alpha');
$print = ($optioncss == 'print')?true:false;

$current = GETPOST('target', 'int');
$token = GETPOST('token', 'alpha');
if ($current == null)$current = '0';
//handle submission
if ($action == 'submit') {
    if (isset($_SESSION['timesheet'][$token])) {
        // $_SESSION['timesheet'][$token]['tsUser']
        $tsApproved = 0;
        $tsRejected = 0;
        $ret = 0;
        $errors = 0;
        $count = 0;
        //$task_timesheet->db = $db;
        if (!empty($_POST['approval']) || !empty($_POST['notesTask'])) {
            $task_timesheet = new TimesheetTask($db);
            $approvals = GETPOST('approval', 'array');
            $notes = GETPOST('notesTask', 'array');
            $update = false;
            foreach ($_SESSION['timesheet'][$token] as $id => $role_row) {
                $count++;
                $task_timesheet->fetch($id);
                if ($notes[$id]!=$task_timesheet->note) {
                    $task_timesheet->note = $notes[$id];
                    $update = true;
                }
                switch(uniordHex($approvals[$id])) {
                    case '2705'://Approved':
                        $ret = $task_timesheet->approved($user, array_search($role_row, $roles));
                        if ($ret<0)$errors++;
                        else $tsApproved++;
                        break;
                    case '274C'://'Rejected':
                        $ret = $task_timesheet->challenged($user, array_search($role_row, $roles));
                        if ($ret<0)$errors++;
                        else $tsRejected++;
                        break;
                    case '2753': // ? submitted
                        if ($update)$task_timesheet->update($user);
                    default:
                        break;
                }
            }
            if (($tsRejected+$tsApproved)>0) {
               $current--;
            }
            if ($tsApproved)
                setEventMessage($langs->transnoentitiesnoconv("NumberOfTimesheetApproved").' '.$tsApproved);
            if ($tsRejected)
                setEventMessage($langs->transnoentitiesnoconv("NumberOfTimesheetRejected").' '.$tsRejected);
            if ($errors)
                setEventMessage($langs->transnoentitiesnoconv("NumberOfErrors").' '.$errors, 'errors');
            if ($errors == 0 && $tsApproved == 0 && $tsRejected == 0) {
                setEventMessage($langs->transnoentitiesnoconv("NothingChanged"), 'warning');
            }
        }
    } else {
        setEventMessage($langs->transnoentitiesnoconv("NothingChanged"), 'warning');// shoudn't happend
    }
}
/***************************************************
* PREP VIEW
*
* Put here all code to build page
****************************************************/
$subId = ($admin)?'all':
    getSubordinates($db, $userId, 1, array($userId), $role_key);//FIx ME for other role
$tasks = implode(', ', array_keys(getTasks($db, $userId)));
if ($tasks == "")$tasks = 0;
$selectList = getSelectAps($subId, $tasks, $role_key);
if ($current >= count($selectList))$current = 0;
// number of TS to show
$level = intval(TIMESHEET_MAX_TTA_APPROVAL);
//define the offset
$offset = 0;
/*
if (is_array($selectList)&& count($selectList)) {
        if ($current >= count($selectList))$current = 0;
        $offset = 0;
        for($i = 0;$i<$current;$i++)
{
            $offset += $selectList[$i]['count'];
        }
        $level = $selectList[$i]['count'];
}*/
// get the TTA to show
$objectArray = getTStobeApproved($current, $selectList);
$token = getToken();
if (is_array($objectArray)) {
    // SAVE THE ARRAY IN THE SESSION FOR CHECK UPON SUBMIT
    foreach ($objectArray as $object) {
        $_SESSION['timesheet'][$token][$object->appId] = $role;
    }
}
/***************************************************
* VIEW
*
* Put here all code to build page
****************************************************/
$head = ($print)?'<style type = "text/css" >@page { size: A4 landscape;marks:none;margin: 1cm ;}</style>':'';
$morejs = array();
$morejs = array("/timesheet/core/js/timesheet.js?".getConf('TIMESHEET_VERSION'));
llxHeader($head, $langs->trans('Timesheet'), '', '', '', '', $morejs);
//calculate the week days
showTimesheetApTabs($role_key);
echo '<div id = "'.$role.'" class = "tabBar">';
if (!$print) echo getHTMLNavigation($role, $optioncss, $selectList, $token, $current);
// form header
echo '<form action="?action=submit" method = "POST" name = "OtherAp" id = "OtherAp">';
echo '<input type = "hidden" id="csrf-token" name = "token" value = "'.$token.'"/>';
echo '<input type = "hidden" name = "role" value = "'.$role.'"/>';
echo '<input type = "hidden" name = "target" value = "'.($current+1)."\"/>\n";
// table hearder
echo "\n<table id = \"ApTable\" class = \"noborder\" width = \"100%\">\n";
//rows
getHTMLRows($objectArray);
// table footer
echo "\n</table>";
echo '<div class = "tabsAction">';
echo '<input type = "submit" class = "butAction" name = "Send" value = "'
    .$langs->trans('Submit').'/'.$langs->trans('Next')."\" />\n";
//form footer
echo '</div>';
echo "\n</form>";
echo '</div>';
llxFooter();
/***************************************************
* FUNCTIONS
*
* Put here all code of funcitons
****************************************************/
/*
 * function to print the timesheet navigation header
 *
 *  @param    string               $role                  the role of the user
 *  @param     string             $optioncss             optioncss for the print mode
 *  @param     array              $selectList        list of pages
 *  @param     int                      $current                current page
 *
 *  @return     string                                         HTML
 */
function getHTMLNavigation($role, $optioncss, $selectList,$token, $current = 0)
{
    global $langs, $db;
    $htmlSelect = '<select name = "target">';
    foreach ($selectList as $key => $element) {
        $htmlSelect .= ' <option value = "'.$key.'" '.(($current == $key)?'selected':'').'>'
            .$element['label'].'</option>';
    }
    $htmlSelect .= '</select>';
    $form = new Form($db);
    $Nav = '<table class = "noborder" width = "50%">'."\n\t".'<tr>'."\n\t\t".'<th>'."\n\t\t\t";
    if ($current!=0) {
        $Nav .= '<a href="?view=goto&token='.$token.'&target='.($current-1);
        $Nav .= '&role='.($role);
        if ($optioncss != '')$Nav .= '&amp;optioncss='.$optioncss;
        $Nav .= '">  &lt;&lt;'.$langs->trans("Previous").' </a>'."\n\t\t";
    }
    $Nav .= "</th>\n\t\t<th>\n\t\t\t";
    $Nav .= '<form name = "goTo" action="?view=goto&role='.$role.'" method = "POST" >'."\n\t\t\t";
    $Nav .= $langs->trans("GoTo").': '.$htmlSelect."\n\t\t\t";
    $Nav .= '<input type = "hidden" id="csrf-token" name = "token" value = "'.$token.'"/>';
    $Nav .= '<input type = "submit" value = "Go" /></form>'."\n\t\t</th>\n\t\t<th>\n\t\t\t";
   

    if ($current<count($selectList)) {
        $Nav .= '<a href="?view=goto&token='.$token.'&target='.($current+1);
        $Nav .= '&role='.($role);
        if ($optioncss != '') $Nav .= '&amp;optioncss='.$optioncss;
        $Nav .= '">'.$langs->trans("Next").' &gt;&gt;</a>';
    }
    $Nav .= "\n\t\t</th>\n\t</tr>\n </table>\n";
    return $Nav;
}
/* Funciton to fect timesheet to be approuved.
    *  @param    int               $current            current item of the select
    *  @param    int               $selectList        list of the item showed in the navigation select
    *  @return   array(task_timesheet)                     result
    */
function getTStobeApproved($current, $selectList)
{
    global $db;
    if ((!is_array($selectList) ||  !array_key_exists($current,$selectList) ||!is_array($selectList[$current]['idList'])))return array();
    $listTTA = array();
    foreach ($selectList[$current]['idList'] as $idTTA) {
        $TTA = new TimesheetTask($db);
        $TTA->fetch($idTTA);
        $listTTA[] = $TTA;
    }
    return $listTTA;
}
 /*
 * function to get the Approval elible for this user
 *
  *  @param    object            $db             database objet
 *  @param    array(int)/int        $userids        array of manager id
  *  @return  array(int => String)                                array(ID => userName)
 */
function getSelectAps($subId, $tasks, $role_key)
{
    if ((!is_array($subId) || !count($subId)) && $subId!='all')return array();
    global $db, $langs, $conf, $roles;
    $sql = "SELECT COUNT(ts.rowid) as nb, ";
    switch(getConf('TIMESHEET_TIME_SPAN')) {
        case 'month':
            $sql .= " CONCAT(DATE_FORMAT(ts.date_start, '%m/%Y'), '-', pjt.ref) as id, ";
            if ($db->type!='pgsql') {
                $sql .= " CONCAT(pjt.title, ' (', MONTH(date_start), '/', YEAR(date_start), ')#') as label, ";
            } else {
                $sql .= " CONCAT(pjt.title, ' (', date_part('month', date_start), '/', "
                    ."date_part('year', date_start), ')#') as label, ";
            }
            break;
        case 'week':
        case 'splitedWeek':
        default:
            $sql .= " CONCAT(DATE_FORMAT(ts.date_start, '%v/%Y'), '-', pjt.ref) as id, ";
            if ($db->type!='pgsql') {
                $sql .= " CONCAT(pjt.title, ' (".$langs->trans("Week")
                    ."', WEEK(ts.date_start, 1), '/', YEAR(ts.date_start), ')#') as label, ";
            } else {
                $sql .= " CONCAT(pjt.title, ' (".$langs->trans("Week")
                    ."', date_part('week', ts.date_start), '/', date_part('year', ts.date_start), ')#') as label, ";
            }
           break;
    }
    if ($db->type!='pgsql') {
        $sql .= " GROUP_CONCAT(ts.rowid  SEPARATOR ', ') as idlist";
    } else {
        $sql .= " STRING_AGG(to_char(ts.rowid, '9999999999999999'), ', ') as idlist";
    }
    $sql .= ' FROM '.MAIN_DB_PREFIX.'project_task_time_approval as ts';
    $sql .= ' JOIN '.MAIN_DB_PREFIX.'projet_task as tsk on ts.fk_projet_task = tsk.rowid ';
    $sql .= ' JOIN '.MAIN_DB_PREFIX.'projet as pjt on tsk.fk_projet = pjt.rowid ';
    $sql .= ' WHERE ts.status in ('.SUBMITTED.', '.UNDERAPPROVAL.', '.CHALLENGED.')';
    $sql .= ' AND recipient='.$role_key;
    if ($subId!='all') {
        $sql .= ' AND ts.fk_userid in ('.implode(', ', $subId).')';
        if ($role_key == PROJECT) {
            $sql .= ' AND tsk.rowid in ('.$tasks.') ';
        }
    }
    $sql .= ' group by ts.date_start, pjt.ref, pjt.title ORDER BY id DESC, pjt.title, ts.date_start ';
    dol_syslog(__METHOD__, LOG_DEBUG);
    $list = array();
    $resql = $db->query($sql);
    if ($resql) {
        $i = 0;
        $j = 0;
        $num = $db->num_rows($resql);
        while($i<$num)
        {
            $obj = $db->fetch_object($resql);
            if ($obj) {
                $j = 1;
                $nb = $obj->nb;
                $idsList = explode(', ', $obj->idlist);
                // split the nb in x line to avoid going over the max approval
                while($nb>TIMESHEET_MAX_TTA_APPROVAL)
                {
                    $custIdList = array_slice($idsList, $nb-TIMESHEET_MAX_TTA_APPROVAL, TIMESHEET_MAX_TTA_APPROVAL);
                    $list[] = array("id"=>$obj->id, "idList"=>$custIdList, "label"=>$obj->label
                        .' ('.$j."/".ceil($obj->nb/TIMESHEET_MAX_TTA_APPROVAL).')', "count" => TIMESHEET_MAX_TTA_APPROVAL);
                    $nb -= TIMESHEET_MAX_TTA_APPROVAL;
                    $j++;
                }
                $custIdList = array_slice($idsList, 0, $nb);
                // at minimum a row shoud gnerate one option
                $list[] = array("id"=>$obj->id, "idList"=>$custIdList, "label"=>$obj->label.$obj->nb, ' '
                    .(($obj->nb>TIMESHEET_MAX_TTA_APPROVAL)?'('.$j.'/'
                    .ceil($obj->nb/TIMESHEET_MAX_TTA_APPROVAL).')':''), "count"=>$nb);
            }
            $i++;
        }
    } else {
        dol_print_error($db);
        $list = array();
    }
      //$select .= "\n";
    return $list;
}

 /** get the rows to display
  *
  * @global object $langs lang object
  * @global object $conf    conf object
  * @param array $objectArray   item to display
  * @return string      html code
  */
function getHTMLRows($objectArray)
{
    global $langs, $conf;
    $headers = array('Approval', 'Note', 'Tasks', 'User');
    if (!is_array($objectArray) || !array_key_exists(0,$objectArray) || !is_object($objectArray[0])) return -1;
    echo '<tr class = "liste_titre">';
    echo '<th>'.$langs->trans('Approval').'</th>';
    echo '<th>'.$langs->trans('Note').'</th>';
    echo '<th>'.$langs->trans('Task').'</th>';
    echo '<th>'.$langs->trans('User').'</th>';
    $weeklength = getDayInterval($objectArray[0]->date_start_approval, 
        $objectArray[0]->date_end_approval);
    $format = ($langs->trans("FormatDateShort")!="FormatDateShort"?
        $langs->trans("FormatDateShort"):$conf->format_date_short);
    if (getConf('TIMESHEET_TIME_SPAN') == "month") {
        //remove Year
        $format = str_replace('Y', '', str_replace('%Y', '', str_replace('Y/', '', str_replace('/%Y', '', $format))));
    }
    for ($i = 0;$i<$weeklength;$i++)
    {
        $curDay = $objectArray[0]->date_start_approval+ SECINDAY*$i+SECINDAY/4;
        $htmlDay = (getConf('TIMESHEET_TIME_SPAN') == "month")?
            substr($langs->trans(date('l', $curDay)), 0, 3):$langs->trans(date('l', $curDay));
        echo"\t".'<th width = "60px" style = "text-align:center;" >'
            .$htmlDay.'<br>'.dol_print_date($curDay, $format)."</th>\n";
    }
    echo "<tr>\n";
    foreach ($objectArray as $key => $object) {
 //        $object->getTaskInfo();
        $object->getActuals();
        if ($object->id != -1 or $object->getSavedTimeTotal() != 0){
            echo '<tr>';
            echo $object->getTimesheetLine($headers, 0, '1');
            echo "<tr>\n";
        }

    }
}
 /** function that provide the code of a character
  *
  * @param char $u char to convert
  * @return int Unide number
  */
function uniordHex($u)
{
   return strtoupper(bin2hex(iconv('UTF-8', 'UCS-2BE', $u)));
}
