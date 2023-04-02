<?php
/* Copyright (C) 2007-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2014 delcroip <patrick@pmpd.eu>
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
require_once 'class/TimesheetUserTasks.class.php';
$action = GETPOST('action', 'alpha');
$view = GETPOST('view', 'alpha');

if ($view != ''){
    $action = $view;
}

$datestart = GETPOST('startDate', 'int');
//should return the XMLDoc
$ajax = GETPOST('ajax', 'int');
$xml = GETPOST('xml', 'int');
$optioncss = GETPOST('optioncss', 'alpha');
$id = GETPOST('id', 'int');
//$toDate = GETPOST('toDate');
$toDate = GETPOST('toDate', 'alpha')?:'';

$toDateday = (!empty($toDate) && $action == 'goToDate')?GETPOST('toDateday', 'int') :0;// to not look for the date if action not goToDate
$toDatemonth = (!empty($toDate) && $action == 'goToDate')?GETPOST('toDatemonth', 'int'):0;
$toDateyear = (!empty($toDate) && $action == 'goToDate')?GETPOST('toDateyear', 'int'):0;


$token = GETPOST('token', 'alpha');
$whitelistmode = GETPOST('wlm', 'int');
if ($whitelistmode == '') {
    $whitelistmode = getConf('TIMESHEET_WHITELIST_MODE');
}
$userid = is_object($user)?$user->id:$user;
$postUserId = GETPOST('userid', 'int');
$submitted = GETPOST('submit', 'alpha');
$submitted_next = GETPOST('submit_next', 'alpha');
$saved_next = GETPOST('save_next', 'alpha');
$tsUserId = GETPOST('tsUserId', 'int');

$admin = $user->admin || $user->rights->timesheet->timesheet->admin;
if (!$user->rights->timesheet->timesheet->user && !$admin) {
    $accessforbidden = accessforbidden("You don't have the timesheet user or admin right");
}

// if the user can enter ts for other the user id is diferent
if ( getConf('TIMESHEET_ADD_FOR_OTHER') == 1) {
        if (!empty($postUserId)) {
            $newuserid = $postUserId;
        }else{
            $newuserid = $user->id;
        }
    $SubordiateIds = getSubordinates($db, $userid, 2, array(), ALL, $entity = '1');
    //$SubordiateIds[] = $userid;
    if ($newuserid > 0 && in_array($newuserid, $SubordiateIds) || $admin || $newuserid == $userid) {
        $SubordiateIds[] = $userid;
        $userid = $newuserid;
    } elseif ($action == 'getOtherTs') {
        setEventMessage($langs->transnoentitiesnoconv("NotAllowed"), 'errors');
        unset($action);
    }
}
$confirm = GETPOST('confirm', 'alpha');
$dateStart = intval(GETPOST('startDate', 'int'));
if ($dateStart == 0){
    if ($toDateday == 0 && $datestart == 0 && isset($_SESSION["startDate"])) {
        $dateStart = $_SESSION["startDate"];
    } else{
        $dateStart = parseDate($toDateday, $toDatemonth, $toDateyear);
        
    }
}
if ($dateStart == 0)$dateStart = getStartDate(time(), 0);
$_SESSION["startDate"] = $dateStart ;

// Load traductions files requiredby by page
//$langs->load("companies");
$langs->load("main");
$langs->load("projects");
$langs->load('timesheet@timesheet');
/*
// Get parameters
$id = GETPOST('id', 'int');
$action = GETPOST('action', 'alpha');
$myparam = GETPOST('myparam', 'alpha');
// Protection if external user
if ($user->societe_id > 0) {
        //accessforbidden();
}
*/
$task_timesheet = new TimesheetUserTasks($db, $userid);
/*******************************************************************
* ACTIONS
*
* Put here all code to do according to value of "action" parameter
********************************************************************/
$status = '';
$update = false;
switch($action) {
    case 'submit':
        if (isset($_SESSION['timesheet'][$token])) {
            if ($tsUserId>0) {
                $ret = 0;
                $key = GETPOST('tsUserId','int');
                $notesTaskA = GETPOST('notesTask', 'array');
                $notesTask = array_key_exists($tsUserId, $notesTaskA)?$notesTaskA[$tsUserId]:array();
                $progressTaskA = GETPOST('progressTask', 'array');
                $progressTask = array_key_exists($tsUserId, $progressTaskA)?$progressTaskA[$tsUserId]:array();
                $notesTaskApproval = GETPOST('noteTaskApproval', 'array');
                $tasktabA = GETPOST('task', 'array');
                $tasktab = array_key_exists($tsUserId, $tasktabA)?$tasktabA[$tsUserId]:array();
                $task_timesheet->loadFromSession($token, $tsUserId);
                if ($task_timesheet->note != $notesTaskApproval[$key]) {
                    $update = true;
                    $_SESSION['timesheet'][$token]['NoteUpdated'] ++;
                    $task_timesheet->note = $notesTaskApproval[$key];
                    $task_timesheet->update($user);
                }
                $ret = $task_timesheet->updateActuals($tasktab, $notesTask, $progressTask);
                if ($submitted || $submitted_next) {
                        $task_timesheet->setStatus($user, SUBMITTED);
                        $ret++;
                    //$task_timesheet->status = "SUBMITTED";
                } else {
                    $task_timesheet->setStatus($user, DRAFT);
                }
        //$ret = postActuals($db, $user, $_POST['task'], $token);
                TimesheetsetEventMessage($_SESSION['timesheet'][$token]);
            } elseif (GETPOSTISSET('recall')) {
                    $task_timesheet->loadFromSession($token, GETPOST('tsUserId', 'int'));
                    $ret = $task_timesheet->setStatus($user, DRAFT);
                if ($ret > 0) {
                    setEventMessage($langs->transnoentitiesnoconv("timesheetRecalled"));
                } else {
                    setEventMessage($langs->transnoentitiesnoconv("timesheetNotRecalled"), 'errors');
                }
            }elseif (is_array($_SESSION['timesheet'][$token])){
                        setEventMessage($langs->transnoentitiesnoconv("NothingChanged"), 'warnings');
            }else{
                    setEventMessage($langs->transnoentitiesnoconv("NoTaskToUpdate"), 'errors');
            }
        } else{
                setEventMessage($langs->transnoentitiesnoconv("InternalError")
                    .$langs->transnoentitiesnoconv(" : token missmatch"), 'errors');
        }
        break;
    case 'deletefile':
        $action = 'delete';// to trigger the delete action in the linkedfiles.inc.php
        break;
    default:
        break;
}
if (!empty($token)) {
       unset($_SESSION['timesheet'][$token]);
}
$token = getToken();
if ($submitted_next ||$saved_next ){
    $dateStart = getStartDate($dateStart+1, 1);
    $_SESSION["startDate"] = $dateStart ;
}




$task_timesheet->fetchAll($dateStart, $whitelistmode);
if ($action == 'importCalandar'){
    $task_timesheet->importCalandar();
}
         
if (getConf('TIMESHEET_ADD_DOCS')) {
    require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
    include_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
    $modulepart = 'timesheet';
    $object = $task_timesheet;
    $ref = dol_sanitizeFileName($object->ref);
    $upload_dir = $conf->timesheet->dir_output.'/users/'
        .get_exdir($object->id, 2, 0, 0, $object, 'timesheet').$ref;
    if (version_compare(DOL_VERSION, "4.0") >= 0) {
        include_once DOL_DOCUMENT_ROOT . '/core/actions_linkedfiles.inc.php';
    } else{
        include_once DOL_DOCUMENT_ROOT . '/core/tpl/document_actions_pre_headers.tpl.php';
        //require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
    }
}
/***************************************************
* VIEW
*
* Put here all code to build page
****************************************************/
if ($xml) {
    //renew timestqmp
    ob_clean();
   header("Content-type: text/xml;charset = utf-8");
    echo $task_timesheet->GetTimeSheetXML();
    ob_end_flush();
exit();
}
$morejs = array("/timesheet/core/js/jsparameters.php", 
    "/timesheet/core/js/timesheet.js?"
    .getConf('TIMESHEET_VERSION'));
llxHeader('', $langs->trans('Timesheet'), '', '', '', '', $morejs);
//calculate the week days
//tokentp = time();
//fetch ts for others

if (getConf('TIMESHEET_ADD_FOR_OTHER') == 1 
    && (count($SubordiateIds)>1|| $admin )) {
    print $task_timesheet->getHTMLGetOtherUserTs($SubordiateIds, $userid, $admin, $token);
}
//$ajax = false;
$Form = $task_timesheet->getHTMLNavigation($optioncss, $token);
$Form .= $task_timesheet->getHTMLFormHeader();
$Form .= $task_timesheet->getHTMLActions();
     if (getConf('TIMESHEET_WHITELIST') == 1) {
        $Form .= '<div class="tabs" data-role="controlgroup" data-type = "horizontal"  >';
        $Form .= '  <div '.(($task_timesheet->whitelistmode == 2)?'id = "defaultOpen"':'')
            .' class="inline-block tabsElem" onclick="showFavoris(event,\'All\')">'
            .'<a  href="javascript:void(0);" class = "tabunactive tab inline-block" data-role = "button">'
            .$langs->trans('All').'</a></div>';
        $Form .= '  <div '.(($task_timesheet->whitelistmode == 0)?'id = "defaultOpen"':'')
            .' class = "inline-block tabsElem" onclick = "showFavoris(event,\'whitelist\')">'
            .'<a href="javascript:void(0);" class = "tabunactive tab inline-block" data-role = "button">'
            .$langs->trans('blackWhiteList').'</a></div>';
        $Form .= '  <div '.(($task_timesheet->whitelistmode == 1)?'id = "defaultOpen"':'')
            .' class = "inline-block tabsElem"  onclick = "showFavoris(event,\'blacklist\')">'
            .'<a href="javascript:void(0);" class = "tabunactive tab inline-block" data-role = "button">'
            .$langs->trans('Others').'</a></div>';
        $Form .= '</div>';
     }
$Form .= $task_timesheet->getHTML();
//simulualate a click on of of the tabs
$Form .= '<script>document.getElementById("defaultOpen").click()</script>';
//Javascript
//$Form .= ' <script type = "text/javascript" src = "core/js/timesheet.js"></script>'."\n";
$Form .= '<script type = "text/javascript">'."\n\t";
$Form .= 'updateAll('.getConf('TIMESHEET_HIDE_ZEROS').');closeNotes();';
$Form .= "\n\t".'</script>'."\n";
// $Form .= '</div>';//TimesheetPage
print $Form;
//add attachement
if (getConf('TIMESHEET_ADD_DOCS') == 1) {
        $object = $task_timesheet;
        $modulepart = 'timesheet';
        $permission = 1;//$user->rights->timesheet->add;
        $filearray = dol_dir_list($upload_dir, 'files', 0, '', '\.meta$', $sortfield, 
            (strtolower($sortorder) == 'desc'?SORT_DESC:SORT_ASC), 1);
        //$param = 'action = submitfile&id='.$object->id;
            $form = new Form($db);
            include_once DOL_DOCUMENT_ROOT . '/core/tpl/document_actions_post_headers.tpl.php';
}
// End of page
llxFooter();
$db->close();
