<?php
/*
 * Copyright (C) 2007-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2018 delcroip <patrick@pmpd.eu>
 * Copyright (C) ---Put here your own copyright and developer email---
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
 *        \file       dev/attendanceevents/attendanceevent_page.php
 *                \ingroup    timesheet othermodule1 othermodule2
 *                \brief      This file is an example of a php page
 *                                        Initialy built by build_class_from_table on 2018-11-05 20:22
 */
//if(! defined('NOREQUIREUSER'))  define('NOREQUIREUSER', '1');
//if(! defined('NOREQUIREDB'))    define('NOREQUIREDB', '1');
//if(! defined('NOREQUIRESOC'))   define('NOREQUIRESOC', '1');
//if(! defined('NOREQUIRETRAN'))  define('NOREQUIRETRAN', '1');
//if(! defined('NOCSRFCHECK'))    define('NOCSRFCHECK', '1');                        // Do not check anti CSRF attack test
//if(! defined('NOSTYLECHECK'))   define('NOSTYLECHECK', '1');                        // Do not check style html tag into posted data
//if(! defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL', '1');                // Do not check anti POST attack test
//if(! defined('NOREQUIREMENU'))  define('NOREQUIREMENU', '1');                        // If there is no need to load and show top and left menu
//if(! defined('NOREQUIREHTML'))  define('NOREQUIREHTML', '1');                        // If we don't need to load the html.form.class.php
//if(! defined('NOREQUIREAJAX'))  define('NOREQUIREAJAX', '1');
//if(! defined("NOLOGIN"))        define("NOLOGIN", '1');                                // If this page is public (can be called outside logged session)
// Change this following line to use the correct relative path (../, ../../, etc)
include './core/lib/includeMain.lib.php';
// Change this following line to use the correct relative path from htdocs
//include_once(DOL_DOCUMENT_ROOT.'/core/class/formcompany.class.php');
//require_once 'lib/timesheet.lib.php';
require_once 'class/TimesheetAttendanceEvent.class.php';
require_once 'core/lib/generic.lib.php';
//require_once 'core/lib/attendanceevent.lib.php';
dol_include_once('/core/lib/functions2.lib.php');
//document handling
dol_include_once('/core/lib/files.lib.php');
//dol_include_once('/core/lib/images.lib.php');
dol_include_once('/core/class/html.formfile.class.php');
dol_include_once('/core/class/html.formother.class.php');
dol_include_once('/user/class/user.class.php');
dol_include_once('/projet/class/project.class.php');
dol_include_once('/core/class/html.formother.class.php');
if(!$user->rights->timesheet->attendance->admin) {
    $accessforbidden = accessforbidden("You don't have the attendance/chrono admin right");
}
//dol_include_once('/projet/class/projet.class.php');
$PHP_SELF = $_SERVER['PHP_SELF'];
// Load traductions files requiredby by page
//$langs->load("companies");
//$langs->load("attendance@timesheet");
// Get parameter
$id                         = GETPOST('id', 'int');
$ref = GETPOST('ref', 'alpha');
$action                 = GETPOST('action', 'alpha');
$backtopage = GETPOST('backtopage');
$cancel = GETPOST('cancel');
$confirm = GETPOST('confirm');
$tms = GETPOST('tms', 'alpha');
//// Get parameters
$sortfield = GETPOST('sortfield', 'alpha');
$sortorder = GETPOST('sortorder', 'alpha')?GETPOST('sortorder', 'alpha'):'ASC';
$removefilter = isset($_POST["removefilter_x"]) || isset($_POST["removefilter"]);
//$applyfilter = isset($_POST["search_x"]) ;//|| isset($_POST["search"]);
if(!$removefilter) {
// Both test must be present to be compatible with all browsers {
    $ls_date_time_event_month = GETPOST('ls_date_time_event_month', 'int');
    $ls_date_time_event_year = GETPOST('ls_date_time_event_year', 'int');
    $ls_event_location_ref = GETPOST('ls_event_location_ref', 'alpha');
    $ls_event_type = GETPOST('ls_event_type', 'int');
    $ls_note = GETPOST('ls_note', 'alpha');
    $ls_userid = GETPOST('ls_userid', 'int');
    if($ls_userid == -1)$ls_userid = '';
    $ls_third_party = GETPOST('ls_third_party', 'int');
    $ls_task = GETPOST('ls_task', 'int');
    $ls_project = GETPOST('ls_project', 'int');
    $ls_token = GETPOST('ls_token', 'int');
    $ls_status = GETPOST('ls_status', 'int');
}
$page = GETPOST('page', 'int');
if($page <= 0){
    $page = 0;
}
$limit = GETPOST('limit', 'int')?GETPOST('limit', 'int'):$conf->liste_limit;
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
 // uncomment to avoid resubmision
//if(isset($_SESSION['attendanceevent_class'][$tms]))
//{
 //   $cancel = true;
 //  setEventMessages('Internal error, POST not exptected', null, 'errors');
//}
// Right Management
 /*
if($user->societe_id > 0 ||
       (!$user->rights->timesheet->add && ($action == 'add' || $action = 'create')) ||
       (!$user->rights->timesheet->view && ($action == 'list' || $action = 'view')) ||
       (!$user->rights->timesheet->delete && ($action == 'confirm_delete')) ||
       (!$user->rights->timesheet->edit && ($action == 'edit' || $action = 'update')))
{
        accessforbidden();
}
*/
// create object and set id or ref if provided as parameter
$object = new Attendanceevent($db, $user);
if($id>0) {
    $object->id = $id;
    $object->fetch($id);
    $ref = dol_sanitizeFileName($object->ref);
}
$form = new Form($db);
/*******************************************************************
* ACTIONS
*
* Put here all code to do according to value of "action" parameter
********************************************************************/
// Action to remove record
switch($action) {
    case 'confirm_delete':
        $result = ($confirm == 'yes')?$object->delete($user):0;
        if($result > 0) {
            // Delete OK
            setEventMessage($langs->trans('RecordDeleted'), 'mesgs');
        } else {
            // Delete NOK
            if(! empty($object->errors)){
                setEventMessages(null, $object->errors, 'errors');
            } else setEventMessage('RecordNotDeleted', 'errors');
        }
        break;
    case 'add':
        if(empty($tms) || (!isset($_SESSION['Attendanceevent'][$tms]))) {
            setEventMessage('WrongTimeStamp_requestNotExpected', 'errors');
            $action = 'list';
        }
    //retrive the data
        $time = explode(':', GETPOST('DatetimeeventHour'));
        $object->date_time_event = dol_mktime($time[0], $time[1], 0, GETPOST('Datetimeeventmonth'), GETPOST('Datetimeeventday'), GETPOST('Datetimeeventyear'));
        $object->event_location_ref = GETPOST('Eventlocationref');
        $object->event_type = GETPOST('Eventtype');
        $object->note = GETPOST('Note');
        $object->userid = GETPOST('Userid');
        $object->third_party = GETPOST('Thirdparty');
        $object->task = GETPOST('Task');
        $object->project = GETPOST('Project');
        $object->token = GETPOST('Token');
        $object->status = GETPOST('Status');
        $result = $object->create($user);
        if($result > 0) {
                // Creation OK
            // remove the tms
               if($ajax == 1) {
                   $object->serialize(2); //return JSON
                    ob_end_flush();
                    exit();// don't remove the tms. don't continue with the
               }
                   unset($_SESSION['Attendanceevent'][$tms]);
               setEventMessage('RecordSucessfullyCreated', 'mesgs');
               //AttendanceeventReloadPage($backtopage, $result, '');
        } else {
                // Creation KO
                if(! empty($object->errors)) setEventMessages(null, $object->errors, 'errors');
                else  setEventMessage('RecordNotSucessfullyCreated', 'errors');
                $action = 'create';
        }
    break;
}
    //Removing the tms array so the order can't be submitted two times
if(isset($_SESSION['Attendanceevent'][$tms])) {
    unset($_SESSION['Attendanceevent'][$tms]);
}
    $tms = getToken();
    $_SESSION['Attendanceevent'][$tms] = array();
    $_SESSION['Attendanceevent'][$tms]['action'] = $action;
/***************************************************
* VIEW
*
* Put here all code to build page
****************************************************/
$morejs = array("/timesheet/core/js/jsparameters.php", "/timesheet/core/js/timesheet.js?".$conf->global->TIMESHEET_VERSION);
llxHeader('', $langs->trans('AttendanceAdmin'), '', '', '', '', $morejs);
print "<div> <!-- module body-->";
$form = new Form($db);
$formother = new FormOther($db);
$fuser = new User($db);
        if($action == 'delete' && ($id>0)) {
         print $form->form_confirm(dol_buildpath('/timesheet/AttendanceEventAdmin.php', 1).'?action=confirm_delete&id='.$id, $langs->trans('DeleteAttendanceevent'), $langs->trans('ConfirmDelete'), 'confirm_delete', '', 0, 1);
         //if($ret == 'html') print '<br />';
         //to have the object to be deleted in the background\
        }
// Put here content of your page
// Example : Adding jquery code
/*print '<script type = "text/javascript" language = "javascript">
jQuery(document).ready(function()
{
        function init_myfunc()
        {
                jQuery("#myid").removeAttr(\'disabled\');
                jQuery("#myid").attr(\'disabled\', \'disabled\');
        }
        init_myfunc();
        jQuery("#mybutton").click(function()
{
                init_needroot();
        });
});
</script>';*/
    $sql = 'SELECT';
    $sql.= ' t.rowid, ';
    $sql .= ' t.date_time_event, ';
    $sql .= ' t.event_location_ref, ';
    $sql .= ' t.event_type, ';
    $sql .= ' t.note, ';
    $sql .= ' t.fk_userid, ';
    $sql .= ' t.fk_third_party, ';
    $sql .= ' t.fk_task, ';
    $sql .= ' t.fk_project, ';
    $sql .= ' t.token, ';
    $sql .= ' t.status, ';
    $sql .= '  st.date_time_event  as date_time_event_start ';
    $sql.= ' FROM '.MAIN_DB_PREFIX.'attendance_event as t';
    $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."attendance_event as st ON t.token = st.token AND ABS(st.event_type)=2";

    $sqlwhere = '';
    if(isset($object->entity))
        $sqlwhere.= ' AND t.entity = '.$conf->entity;
    if($filter && $filter != -1) {
        // GETPOST('filtre') may be a string {
            $filtrearr = explode(', ', $filter);
            foreach($filtrearr as $fil) {
                    $filt = explode(':', $fil);
                    $sqlwhere .= ' AND ' . $filt[0] . ' = ' . $filt[1];
            }
    }
    //pass the search criteria
        if($ls_date_time_event_month)$sqlwhere .= ' AND MONTH(t.date_time_event) = "'.$ls_date_time_event_month."'";
        if($ls_date_time_event_year)$sqlwhere .= ' AND YEAR(t.date_time_event) = "'.$ls_date_time_event_year."'";
        if($ls_event_location_ref) $sqlwhere .= natural_search('t.event_location_ref', $ls_event_location_ref);
        if($ls_event_type) $sqlwhere .= natural_search(array('t.event_type'), $ls_event_type);
        if($ls_note) $sqlwhere .= natural_search('t.note', $ls_note);
        if($ls_userid) $sqlwhere .= natural_search(array('t.fk_userid'), $ls_userid);
        if($ls_third_party) $sqlwhere .= natural_search(array('t.fk_third_party'), $ls_third_party);
        if($ls_task) $sqlwhere .= natural_search(array('t.fk_task'), $ls_task);
        if($ls_project) $sqlwhere .= natural_search(array('t.fk_project'), $ls_project);
        if($ls_token) $sqlwhere .= natural_search(array('t.token'), $ls_token);
        if($ls_status) $sqlwhere .= natural_search(array('t.status'), $ls_status);
    //list limit
    if(!empty($sqlwhere))
        $sql .= ' WHERE '.substr($sqlwhere, 5);
// Count total nb of records
$nbtotalofrecords = 0;
if(empty($conf->global->MAIN_DISABLE_FULL_SCANLIST)) {
        $sqlcount = 'SELECT COUNT(*) as count FROM '.MAIN_DB_PREFIX.'attendance_event as t';
        if(!empty($sqlwhere))
            $sqlcount .= ' WHERE '.substr($sqlwhere, 5);
        $result = $db->query($sqlcount);
        $nbtotalofrecords = ($result)?$objcount = $db->fetch_object($result)->count:0;
}
    if(!empty($sortfield)) {
        $sql.= $db->order($sortfield, $sortorder);
    } else{
       $sql .= ' ORDER BY t.date_time_event DESC';
    }
    if(!empty($limit)) {
            $sql.= $db->plimit($limit+1, $offset);
    }
    //execute SQL
    dol_syslog($script_file, LOG_DEBUG);
    $resql = $db->query($sql);
    if($resql) {
        $param = '';
        if(! empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param .= '&contextpage='.urlencode($contextpage);
        if($limit > 0 && $limit != $conf->liste_limit) $param .= '&limit='.urlencode($limit);
         if(!empty($ls_date_time_event_month))        $param .= '&ls_date_time_event_month='.urlencode($ls_date_time_event_month);
        if(!empty($ls_date_time_event_year))        $param .= '&ls_date_time_event_year='.urlencode($ls_date_time_event_year);
        if(!empty($ls_event_location_ref))        $param .= '&ls_event_location_ref='.urlencode($ls_event_location_ref);
        if(!empty($ls_event_type))        $param .= '&ls_event_type='.urlencode($ls_event_type);
        if(!empty($ls_note))        $param .= '&ls_note='.urlencode($ls_note);
        if(!empty($ls_userid))        $param .= '&ls_userid='.urlencode($ls_userid);
        if(!empty($ls_third_party))        $param .= '&ls_third_party='.urlencode($ls_third_party);
        if(!empty($ls_task))        $param .= '&ls_task='.urlencode($ls_task);
        if(!empty($ls_project))        $param .= '&ls_project='.urlencode($ls_project);
        if(!empty($ls_token))        $param .= '&ls_token='.urlencode($ls_token);
        if(!empty($ls_status))        $param .= '&ls_status='.urlencode($ls_status);
        if($filter && $filter != -1) $param .= '&filtre='.urlencode($filter);
        $num = $db->num_rows($resql);
        //print_barre_liste function defined in /core/lib/function.lib.php, possible to add a picto
        print_barre_liste($langs->trans("Attendance"), $page, $PHP_SELF, $param, $sortfield, $sortorder, '', $num, $nbtotalofrecords);
        // QUICK FOR TO ADD A LINE
        print '<form method = "POST" action="?action=add">';
        print '<table class = "liste" width = "100%">'."\n";
        //TITLE ADD
        print '<tr class = "liste_titre">';
         print_liste_field_titre('Date', $PHP_SELF, 't.date_time_event', '', $param, '', $sortfield, $sortorder);
        print "\n";
        print_liste_field_titre('Eventlocationref', $PHP_SELF, 't.event_location_ref', '', $param, '', $sortfield, $sortorder);
        print "\n";
        print_liste_field_titre('Eventtype', $PHP_SELF, 't.event_type', '', $param, '', $sortfield, $sortorder);
        print "\n";
        print_liste_field_titre('Note', $PHP_SELF, 't.note', '', $param, '', $sortfield, $sortorder);
        print "\n";
        print_liste_field_titre('User', $PHP_SELF, 't.fk_userid', '', $param, '', $sortfield, $sortorder);
        print "\n";
        print_liste_field_titre('ThirdParty', $PHP_SELF, 't.fk_third_party', '', $param, '', $sortfield, $sortorder);// fix translation
        print "\n";
        print_liste_field_titre('Task', $PHP_SELF, 't.fk_task', '', $param, '', $sortfield, $sortorder);
        print "\n";
        print_liste_field_titre('Project', $PHP_SELF, 't.fk_project', '', $param, '', $sortfield, $sortorder);
        print "\n";
        print_liste_field_titre('Token', $PHP_SELF, 't.token', '', $param, '', $sortfield, $sortorder);
        print "\n";
        print_liste_field_titre('Status', $PHP_SELF, 't.status', '', $param, '', $sortfield, $sortorder);
        print "\n";
        print '</tr>';
        //add
        print '<tr><td>';
        print '<input type = "hidden" name = "tms" value = "'.$tms.'">';
        print $form->select_date(time(), 'Datetimeevent');
        print '<input type = "text" maxlength = "5" onkeypress = "return regexEvent(this,event,\'timeChar\')" name = "DatetimeeventHour" value = "'.date('H:m').'"/>';
        print '</td><td>';
        print '<input type = "text" value = "'.$object->event_location_ref.'" name = "Eventlocationref">';
        print '</td><td>';
        print $form->selectarray('Eventtype', $attendanceeventStatusArray, 2);
        //print '<input type = "text" value = "1" name = "Eventtype">';// FIXME ARRAY SELECT
        print '</td><td>';
        print '<input type = "text" value = "'.$object->note.'" name = "Note">';
        print '</td><td>';
        if(empty($object->userid))$object->userid = $user->id;
        print $form->select_dolusers($object->userid, 'Userid', 1, '', 0);
        print '</td><td>';
        //FIXME SOC
        $sql_third_party = array('table'=> 'societe', 'keyfield'=> 'rowid', 'fields'=>'nom', 'join' => '', 'where'=>'', 'tail'=>'');
        $html_third_party = array('name'=>'Thirdparty', 'class'=>'', 'otherparam'=>'', 'ajaxNbChar'=>'', 'separator'=> '-');
        $addChoices_third_party = null;
        print select_sellist($sql_third_party, $html_third_party, $object->third_party, $addChoices_third_party);
        print '</td><td>';
        $sql_task = array('table'=> 'projet_task', 'keyfield'=> 'rowid', 'fields'=>'ref, label', 'join' => '', 'where'=>'', 'tail'=>'');
        $html_task = array('name'=>'Task', 'class'=>'', 'otherparam'=>'', 'ajaxNbChar'=>'', 'separator'=> '-');
        $addChoices_task = null;
        print select_sellist($sql_task, $html_task, $object->task, $addChoices_task);
        print '</td><td>';
        $sql_project = array('table'=> 'projet', 'keyfield'=> 'rowid', 'fields'=>'ref, title', 'join' => '', 'where'=>'', 'tail'=>'');// fixme project open
        $html_project = array('name'=>'Project', 'class'=>'', 'otherparam'=>'', 'ajaxNbChar'=>'', 'separator'=> '-');
        $addChoices_project = null;
        print select_sellist($sql_project, $html_project, $object->project, $addChoices_project);
        print '</td><td>';
        print '<input type = "text"  name = "Token">';
        print '</td><td>';
        print '<input type = "submit" value = "'.$langs->trans('add').'" ">';
        print '</td></tr></table></form>';
        print '<form method = "POST" action = "">';
        print '<table class = "liste" width = "100%">'."\n";
                //TITLE
        print '<tr class = "liste_titre">';
         print_liste_field_titre('Date', $PHP_SELF, 't.date_time_event', '', $param, '', $sortfield, $sortorder);
        print "\n";
        print_liste_field_titre('Eventlocationref', $PHP_SELF, 't.event_location_ref', '', $param, '', $sortfield, $sortorder);
        print "\n";
        print_liste_field_titre('Eventtype', $PHP_SELF, 't.event_type', '', $param, '', $sortfield, $sortorder);
        print "\n";
        print_liste_field_titre('Note', $PHP_SELF, 't.note', '', $param, '', $sortfield, $sortorder);
        print "\n";
        print_liste_field_titre('User', $PHP_SELF, 't.fk_userid', '', $param, '', $sortfield, $sortorder);
        print "\n";
        print_liste_field_titre('Thirdparty', $PHP_SELF, 't.fk_third_party', '', $param, '', $sortfield, $sortorder);
        print "\n";
        print_liste_field_titre('Task', $PHP_SELF, 't.fk_task', '', $param, '', $sortfield, $sortorder);
        print "\n";
        print_liste_field_titre('Project', $PHP_SELF, 't.fk_project', '', $param, '', $sortfield, $sortorder);
        print "\n";
        print_liste_field_titre('Token', $PHP_SELF, 't.token', '', $param, '', $sortfield, $sortorder);
        print "\n";
        print_liste_field_titre('Status', $PHP_SELF, 't.status', '', $param, '', $sortfield, $sortorder);
        print_liste_field_titre('Duration', '', '', '', '', '', '', '');
        print "\n";
        print '</tr>';
        //SEARCH FIELDS
        print '<tr class = "liste_titre">';
        //Search field fordate_time_event
        print '<td class = "liste_titre" colspan = "1" >';
        print '<input class = "flat" type = "text" size = "1" maxlength = "2" name = "date_time_event_month" value = "'.$ls_date_time_event_month.'">';
        $syear = $ls_date_time_event_year;
        $formother->select_year($syear?$syear:-1, 'ls_date_time_event_year', 1, 20, 5);
        print '</td>';
//Search field forevent_location_ref
        print '<td class = "liste_titre" colspan = "1" >';
        print '<input class = "flat" size = "16" type = "text" name = "ls_event_location_ref" value = "'.$ls_event_location_ref.'">';
        print '</td>';
//Search field forevent_type
        print '<td class = "liste_titre" colspan = "1" >';
        print '<input class = "flat" size = "16" type = "text" name = "ls_event_type" value = "'.$ls_event_type.'">';
        print '</td>';
//Search field fornote
        print '<td class = "liste_titre" colspan = "1" >';
        print '<input class = "flat" size = "16" type = "text" name = "ls_note" value = "'.$ls_note.'">';
        print '</td>';
//Search field foruserid
        print '<td class = "liste_titre" colspan = "1" >';
                print $form->select_dolusers('userid', $ls_userid);
        print '</td>';
//Search field forthird_party
        print '<td class = "liste_titre" colspan = "1" >';
                $html_third_party['name'] = 'ls_third_party';
                print select_sellist($sql_third_party, $html_third_party, $ls_third_party, $addChoices_third_party);
        print '</td>';
//Search field fortask
        print '<td class = "liste_titre" colspan = "1" >';
                $html_task['name'] = 'ls_task';
                print select_sellist($sql_task, $html_task, $ls_task, $addChoices_task);
        print '</td>';
//Search field forproject
        print '<td class = "liste_titre" colspan = "1" >';
                $html_project['name'] = 'ls_project';
                print select_sellist($sql_project, $html_project, $ls_project, $addChoices_project);
        print '</td>';
//Search field fortoken
        //print '<td class = "liste_titre" colspan = "1" >';
        //print '<input type = "text" name = "ls_token">';
        //print '</td>';
//Search field forstatus
        print '<td class = "liste_titre" colspan = "1" >';
        print '<input class = "flat" size = "16" type = "text" name = "ls_status" value = "'.$ls_status.'">';//FIXME Array
        print '</td>';
         print '<td class = "liste_titre" colspan = "1" />';
        print '<td width = "15px">';
        print '<input type = "image" class = "liste_titre" name = "search" src = "'.img_picto($langs->trans("Search"), 'search.png', '', '', 1).'" value = "'.dol_escape_htmltag($langs->trans("Search")).'" title = "'.dol_escape_htmltag($langs->trans("Search")).'">';
        print '<input type = "image" class = "liste_titre" name = "removefilter" src = "'.img_picto($langs->trans("Search"), 'searchclear.png', '', '', 1).'" value = "'.dol_escape_htmltag($langs->trans("RemoveFilter")).'" title = "'.dol_escape_htmltag($langs->trans("RemoveFilter")).'">';
        print '</td>';
        print '</tr>'."\n";
        $i = 0;
       // $basedurl = dirname($PHP_SELF).'/attendanceevent_card.php?action=view&id=';
        while($i < $num && $i<$limit)
        {
            $obj = $db->fetch_object($resql);
            if($obj) {
                // You can use here results
                print "<tr class = \"oddeven\"  >";
                print "<td>".dol_print_date($db->jdate($obj->date_time_event), 'dayhour')."</td>";
                print "<td>".$obj->event_location_ref."</td>";
                print "<td>".Attendanceevent::LibStatut($obj->event_type)."</td>";
                print "<td>".$obj->note."</td>";
                print "<td>";
                if($obj->fk_userid>0) {
                $sUser = new User($db);
                $sUser->fetch($obj->fk_userid);
                print  $sUser->getNomUrl(1);
                }
                print "</td>";
//                print "<td>".  print_sellist('third_party', 'rowid', $obj->fk_third_party, 'rowid', 'description')."</td>";
                print "<td>";
                if($obj->fk_third_party>0) {
                $sThirdParty = new Societe($db);
                $sThirdParty->fetch($obj->fk_third_party);
                print $sThirdParty->getNomUrl(1, '');
                }
                print "</td>";
                 print "<td>";
                if($obj->fk_task>0) {
                $sTask = new Task($db);
                $sTask->fetch($obj->fk_task);
                print $sTask->getNomUrl(1, '');
                }
                print "</td>";
                                 print "<td>";
                if($obj->fk_project>0) {
                $sProject = new Project($db);
                $sProject->fetch($obj->fk_project);
                print $sProject->getNomUrl(1);
                }
                print "</td>";
//                print "<td>".print_generic('third_party', 'rowid', $obj->fk_third_party, 'rowid', 'description')."</td>";
                //print "<td>".print_generic('projet_task', 'rowid', $obj->fk_task, 'ref', 'label')."</td>";
                //print "<td>".print_generic('projet', 'rowid', $obj->fk_project, 'ref', 'title')."</td>";
                print "<td>".$obj->token."</td>";
                print "<td>".$obj->status."</td>";
                $duration=($obj->date_time_event_start<>"")?$db->jdate($obj->date_time_event)-$db->jdate($obj->date_time_event_start):'';
                print "<td>".formatTime($duration, 0)."</td>";
                print '<td><a href = "AttendanceEventAdmin.php?action=delete&id='.$obj->rowid.'">'.img_delete().'</a></td>';
                print "</tr>";
            }
            $i++;
        }
    } else {
        $error++;
        dol_print_error($db);
    }
    print '</table>'."\n";
    print '</form>'."\n";
    // new button
   // print '<a href = "attendanceevent_card.php?action=create" class="butAction"role="button">'.$langs->trans('New');
    print ' '.$langs->trans('Attendanceevent')."</a>\n";
// End of page
llxFooter();
$db->close();
