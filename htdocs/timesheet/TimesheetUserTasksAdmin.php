<?php
/*
 * Copyright (C) 2007-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) Patrick Delcroix <patrick@pmpd.eu>
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
 *                                        Initialy built by build_class_from_table on 2016-03-26 09:52
 */
//if (! defined('NOREQUIREUSER'))  define('NOREQUIREUSER', '1');
//if (! defined('NOREQUIREDB'))    define('NOREQUIREDB', '1');
//if (! defined('NOREQUIRESOC'))   define('NOREQUIRESOC', '1');
//if (! defined('NOREQUIRETRAN'))  define('NOREQUIRETRAN', '1');
//if (! defined('NOCSRFCHECK'))    define('NOCSRFCHECK', '1');                        // Do not check anti CSRF attack test
//if (! defined('NOSTYLECHECK'))   define('NOSTYLECHECK', '1');                        // Do not check style html tag into posted data
//if (! defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL', '1');                // Do not check anti POST attack test
//if (! defined('NOREQUIREMENU'))  define('NOREQUIREMENU', '1');                        // If there is no need to load and show top and left menu
//if (! defined('NOREQUIREHTML'))  define('NOREQUIREHTML', '1');                        // If we don't need to load the html.form.class.php
//if (! defined('NOREQUIREAJAX'))  define('NOREQUIREAJAX', '1');
//if (! defined("NOLOGIN"))        define("NOLOGIN", '1');                                // If this page is public (can be called outside logged session)
// Change this following line to use the correct relative path (../, ../../, etc)
include 'core/lib/includeMain.lib.php';
if (!$user->rights->timesheet->approval->admin && !$user->admin) {
    $accessforbidden = accessforbidden("you need to have the approver admin rights");
}
require_once 'core/lib/generic.lib.php';
require_once 'class/TimesheetUserTasks.class.php';
require_once 'core/lib/timesheet.lib.php';
include_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
//document handling
include_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
//include_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
// include conditionnally of the dolibarr version
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
$PHP_SELF = $_SERVER['PHP_SELF'];
// Load traductions files requiredby by page
//$langs->load("companies");
$langs->load("timesheet@timesheet");
// Get parameter
$id = GETPOST('id', 'int');
$ref = GETPOST('ref', 'alpha');
$action = GETPOST('action', 'alpha');
$view = GETPOST('view', 'alpha');
if ($view != ''){
    $action = $view;
}
$backtopage = GETPOST('backtopage', 'alpha');
$cancel = GETPOST('cancel', 'alpha');
$confirm = GETPOST('confirm', 'alpha');
$token = GETPOST('$token', 'alpha');
$filter = GETPOST('filter', 'alpha');
$param = GETPOST('param', 'alpha');

//// Get parameters
$sortfield = GETPOST('sortfield', 'alpha');
$sortorder = GETPOST('sortorder', 'alpha')?GETPOST('sortorder', 'alpha'):'ASC';
$removefilter = isset($_POST["removefilter_x"]) || isset($_POST["removefilter"]);
//$applyfilter = isset($_POST["search_x"]) ;//|| isset($_POST["search"]);
if (!$removefilter) {
    // Both test must be present to be compatible with all browsers {
    $ls_userId = GETPOST('ls_userId', 'int');
    if ($ls_userId == -1)$ls_userId = '';
    $ls_date_start_month = GETPOST('ls_date_start_month', 'int');
    $ls_date_start_year = GETPOST('ls_date_start_year', 'int');
    $ls_status = GETPOST('ls_status', 'alpha');
    if ($ls_status == -1)$ls_status = '';
    $ls_target = GETPOST('ls_target', 'alpha');
    if ($ls_target == -1)$ls_target = '';
    $ls_project_tasktime_list = GETPOST('ls_project_tasktime_list', 'alpha');
    $ls_user_approval = GETPOST('ls_user_approval', 'int');
    if ($ls_user_approval == -1)$ls_user_approval = '';
    $ls_timsheetuser = GETPOST('ls_timesheetuser', 'int');
    if ($ls_timsheetuser == -1)$ls_timsheetuser = '';
    $ls_task = GETPOST('ls_task', 'int');
    if ($ls_task == -1)$ls_task = '';
    $ls_note = GETPOST('ls_note', 'alpha');
    if ($ls_note == -1)$ls_note = '';
}
$page = GETPOST('page', 'int');
if ($page <= 0){
    $page = 0;
}
$limit = $conf->liste_limit;
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
//$upload_dir = $conf->timesheet->dir_output.'/Timesheetuser/'.dol_sanitizeFileName($object->ref);
 // uncomment to avoid resubmision
//if (isset($_SESSION['timesheet'][$token]))
//{
 //   $cancel = true;
 //  setEventMessages('Internal error, POST not exptected', null, 'errors');
//}
// Right Management
 /*
if ($user->societe_id > 0 ||
       (!$user->rights->timesheet->add && ($action == 'add' || $action = 'create')) ||
       (!$user->rights->timesheet->view && ($action == 'list' || $action = 'view')) ||
       (!$user->rights->timesheet->delete && ($action == 'confirm_delete')) ||
       (!$user->rights->timesheet->edit && ($action == 'edit' || $action = 'update')))
{
        accessforbidden();
}
*/
// create object and set id or ref if provided as parameter
$object = new TimesheetUserTasks($db);
if ($id>0) {
    $object->id = $id;
    $object->fetch($id);
    $ref = dol_sanitizeFileName($object->ref);
    $upload_dir = $conf->timesheet->dir_output.'/tasks/'
        .get_exdir($object->id, 2, 0, 0, $object, 'timesheet').$ref;
    if (empty($action))$action = 'viewdoc';//  the doc handling part send back only the ID without actions
}
if (!empty($ref)) {
    $object->ref = $ref;
}
/*******************************************************************
* ACTIONS
*
* Put here all code to do according to value of "action" parameter
********************************************************************/
// Action to add record
$error = 0;
if ($cancel) {
    reloadpage($backtopage, $id, $ref);
} elseif (($action == 'add') || ($action == 'update' && ($id>0 || !empty($ref)))) {
    //block resubmit
    if (empty($token) || (!isset($_SESSION['timesheet'][$token]))) {
        setEventMessage('WrongTimeStamp_requestNotExpected', 'errors');
        $action = ($action == 'add')?'create':'view';
    }
    //retrive the data
    $object->userId = GETPOST('Userid', 'int');
    $object->date_start = dol_mktime(0, 0, 0, GETPOST('startDatedatemonth', 'int'), 
        GETPOST('startDatedateday', 'int'), GETPOST('startDatedateyear', 'int'));
    $object->date_end = dol_mktime(0, 0, 0, GETPOST('dateendmonth', 'int'), 
        GETPOST('dateendday', 'int'), GETPOST('dateendyear', 'int'));
    $object->status = GETPOST('Status', 'alpha');
    $object->note = GETPOST('Note', 'alpha');
// test here if the post data is valide
 /*
 if ($object->prop1 == 0 || $object->prop2 == 0) {
     if ($id>0 || $ref!='')
        $action = 'create';
     else
        $action = 'edit';
 }
  */
} elseif ($id == 0 && $ref == '' && $action!='create') {
    $action = 'list';
}
switch($action) {
    case 'update':
        $result = $object->update($user);
        if ($result > 0) {
            // Creation OK
            unset($_SESSION['timesheet'][$token]);
            setEventMessage('RecordUpdated', 'mesgs');
            // reloadpage($backtopage, $object->id, $ref);
            $action = 'view';
        } else {
            // Creation KO
            if (! empty($object->errors)) setEventMessages(null, $object->errors, 'errors');
            else setEventMessage('RecordNotUpdated', 'errors');
            //reloadpage($backtopage, $object->id, $ref);
                $action = 'view';
        }
        //fallthrough
    case 'delete':
        if (isset($_GET['urlfile'])) $action = 'deletefile';
        //fallthrough
    case 'card':
    case 'viewinfo':
    case 'viewdoc':
    case 'edit':
        // fetch the object data if possible
        if ($id > 0 || !empty($ref)) {
            //$result = $object->fetch($id, $ref);
            if ($result > 0)$result = $object->fetchTaskTimesheet();
            if ($result > 0)$result = $object->fetchUserHolidays();
            if ($result < 0) {
                dol_print_error($db);
            } else { // fill the id & ref
                if (isset($object->id))$id = $object->id;
                if (isset($object->rowid))$id = $object->rowid;
                if (isset($object->ref))$ref = $object->ref;
            }
        } else {
            setEventMessage($langs->trans('noIdPresent').' id:'.$id, 'errors');
            $action = 'list';
        }
        break;
    case 'add':
        $result = $object->create($user);
        if ($result > 0) {
            // Creation OK
            // remove the $token
           unset($_SESSION['timesheet'][$token]);
           setEventMessage('RecordSucessfullyCreated', 'mesgs');
           reloadpage($backtopage, $result, $ref);
        } else {
            // Creation KO
            if (! empty($object->errors)) setEventMessages(null, $object->errors, 'errors');
            else  setEventMessage('RecordNotSucessfullyCreated', 'errors');
            $action = 'create';
        }
        break;
     case 'confirm_delete':
        $result = ($confirm == 'yes')?$object->delete($user):0;
        if ($result > 0) {
            // Delete OK
            setEventMessage($langs->trans('RecordDeleted'), 'mesgs');
            $action = 'list';
        } else {
            // Delete NOK
            if (! empty($object->errors)) setEventMessages(null, $object->errors, 'errors');
            else setEventMessage('RecordNotDeleted', 'errors');
            $action = 'list';
        }
         break;
    case 'list':
    case 'create':
    default:
        if (!empty($_FILES)) $action = 'viewdoc';
        break;
}
        //document handling
if (getConf('TIMESHEET_ADD_DOCS') && $id>0) {
    $object->fetch($id);
    $ref = dol_sanitizeFileName($object->ref);
    $upload_dir = $conf->timesheet->dir_output.'/tasks/'
        .get_exdir($object->id, 2, 0, 0, $object, 'timesheet').$ref;
    if (version_compare(DOL_VERSION, "4.0") >= 0) {
       include_once DOL_DOCUMENT_ROOT . '/core/actions_linkedfiles.inc.php';
    } else{
       include_once DOL_DOCUMENT_ROOT . '/core/tpl/document_actions_pre_headers.tpl.php';
    }
}
//Removing the $token array so the order can't be submitted two times
if (isset($_SESSION['timesheet'][$token])) {
    unset($_SESSION['timesheet'][$token]);
}
if (($action == 'create') || ($action == 'edit' && ($id>0 || !empty($ref)))) {
    
    $_SESSION['timesheet'][$token] = array();
    $_SESSION['timesheet'][$token]['action'] = $action;
}
// new token
$token = getToken();
/***************************************************
* VIEW
*
* Put here all code to build page
****************************************************/
$morejs = array("/timesheet/core/js/jsparameters.php", "/timesheet/core/js/timesheet.js?"
    .getConf('TIMESHEET_VERSION'));
llxHeader('', $langs->trans('TimesheetUser'), '', '', '', '', $morejs);
print "<div> <!-- module body-->";
$form = new Form($db);
$formother = new FormOther($db);
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
$edit = $new = 0;
$param = '';
switch($action) {
    case 'create':
        $new = 1;
    case 'edit':
        $edit = 1;
    case 'delete';
        if ($action == 'delete' && ($id>0 || $ref!="")) {
         $ret = $form->form_confirm($PHP_SELF.'?action=confirm_delete&token='.$token.'&id='
            .$id, $langs->trans('DeleteTimesheetuser'), 
            $langs->trans('ConfirmDelete'), 'confirm_delete', '', 0, 1);
        if ($ret == 'html') print '<br />';
         //to have the object to be deleted in the background\
        }
    case 'card':
    {
        // tabs
        if ($edit == 0 && $new == 0) { //show tabs
            $head = Timesheetuser_prepare_head($object);
            dol_fiche_head($head, 'card', $langs->trans('Timesheetuser'), 0, 'timesheet@timesheet');
        } else{
            print_fiche_titre($langs->trans('Timesheetuser'));
        }
        print '<br>';
        if ($edit == 1) {
            if ($new == 1) {
                print '<form method = "POST" action = "'.$PHP_SELF.'?action=add">';
            } else{
                print '<form method = "POST" action = "'.$PHP_SELF.'?action=update&id='.$id.'">';
            }
            print '<input type = "hidden" name = "token" value = "'.$token.'">';
            print '<input type = "hidden" name = "backtopage" value = "'.$backtopage.'">';
        } else {// show the nav bar
            $basedurltab = explode("?", $PHP_SELF);
            $basedurl = $basedurltab[0].'?view=list';
            $linkback = '<a href = "'.$basedurl.(! empty($socid)?'?socid='.$socid:'').'">'
                .$langs->trans("BackToList").'</a>';
            if (!isset($object->ref))//save ref if any
                $object->ref = $object->id;
            print $form->showrefnav($object, 'action = view&id', $linkback, 1, 'rowid', 'ref', '');
            //reloqd the ref
        }
        print '<table class = "border centpercent">'."\n";
                print "<tr>\n";
// show the field userId
                print '<td class = "fieldrequired">'.$langs->trans('User').' </td><td>';
                if ($edit == 1) {
                print $form->select_dolusers($object->userId, 'Userid', 1, '', 0);
                } else{
                print print_generic('user', 'rowid', $object->userId, 'lastname', 'firstname', ' ');
                }
                print "</td>";
                print "\n</tr>\n";
                print "<tr>\n";
// show the field date_start
                print '<td class = "fieldrequired">'.$langs->trans('DateStart').' </td><td>';
                if ($edit == 1) {
                if ($new == 1) {
                        print $form->select_date(-1, 'startDatedate');
                } else{
                        print $form->select_date($object->date_start, 'startDatedate');
                }
                } else{
                        print dol_print_date($object->date_start, 'day');
                }
                print "</td>";
                print "\n</tr>\n";
                print "<tr>\n";
// show the field date_end
                print '<td class = "fieldrequired">'.$langs->trans('DateEnd').' </td><td>';
                if ($edit == 1) {
                if ($new == 1) {
                        print $form->select_date(-1, 'dateend');
                } else{
                        print $form->select_date($object->date_end, 'dateend');
                }
                } else{
                        print dol_print_date($object->date_end, 'day');
                }
                print "</td>";
                print "\n</tr>\n";
                print "<tr>\n";
// show the field status
                print '<td>'.$langs->trans('Status').' </td><td>';
                if ($edit == 1) {
                print  $form->selectarray('Status', $statusA, $object->status);
                } else{
                print $statusA[$object->status];
                }
                print "</td>";
                print "\n</tr>\n";
                print "<tr>\n";
// show the field note
                print '<td>'.$langs->trans('Note').' </td><td>';
                if ($edit == 1) {
            print '<textarea class = "flat"  name = "Note" cols = "40" rows = "5" >'
                .$object->note.'</textarea>';
                } else{
                        print $object->note;
                //print print_generic('project_tasktime_list', 'rowid', $object->project_tasktime_list, 'rowid', 'description');
                }
                print "</td>";
                print "\n</tr>\n";
//                print "<tr>\n";
        print '</table>'."\n";
        print '<br>';
        if ($object->status != DRAFT && $edit!=1) {
            $object->fetchByWeek();
            $object->fetchTaskTimesheet();
            //$ret += $this->getTaskTimeIds();
            //FIXME module holiday should be activated ?
            $object->fetchUserHolidays();
            print $object->userName." - ".dol_print_date($object->date_start, 'day');
            print $object->getHTMLHeader();
            print $object->getHTMLHolidayLines(false);
            print $object->getHTMLPublicHolidayLines(false);
            print $object->getHTMLTotal();
            print $object->getHTMLtaskLines(false);
            print $object->getHTMLTotal();
            print "</table>";
            print  '<script type = "text/javascript">'."\n\t";
            print 'updateAll('.getConf('TIMESHEET_HIDE_ZEROS').');';
            print  "\n\t".'</script>'."\n";
        }
        print '<div class = "center">';
        if ($edit == 1) {
            if ($new == 1) {
                print '<input type = "submit" class = "butAction" name = "add" value = "'
                    .$langs->trans('Add').'">';
            } else{
                print '<input type = "submit" name = "update" value = "'
                    .$langs->trans('Update').'" class = "butAction">';
            }
            print ' &nbsp;<input type = "submit" class = "butActionDelete" name = "cancel" value = "'
                .$langs->trans('Cancel').'"></div>';
            print '</form>';
        } else{
            $parameters = array();
            $reshook = $hookmanager->executeHooks('addMoreActionsButtons', 
                $parameters, $object, $action);// Note that $action and $object may have been modified by hook
            if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
            if (empty($reshook)) {
                print '<div class = "tabsAction">';
                // Boutons d'actions
                //if ($user->rights->Timesheetuser->edit)
                //{
                    print '<a href = "'.$PHP_SELF.'?id='.$id
                        .'&action=edit&token='.$token.'" class = "butAction">'.$langs->trans('Update').'</a>';
                //}
                //if ($user->rights->Timesheetuser->delete)
                //{
                    print '<a class = "butActionDelete" href = "'.$PHP_SELF.'?id='
                        .$id.'&action=delete&token='.$token.'">'.$langs->trans('Delete').'</a>';
                //}
                //else
                //{
                //    print '<a class = "butActionRefused" href = "#" title = "'.dol_escape_htmltag($langs->trans("NotAllowed")).'">'.$langs->trans('Delete').'</a>';
                //}
                print '</div>';
            }
        }
        break;
    }
        case 'viewinfo':
        print_fiche_titre($langs->trans('Timesheetuser'));
        $head = Timesheetuser_prepare_head($object);
        dol_fiche_head($head, 'info', $langs->trans("Timesheetuser"), 0, 'timesheet@timesheet');
        print '<table width = "100%"><tr><td>';
        dol_print_object_info($object);
        print '</td></tr></table>';
        print '</div>';
        break;
    case 'deletefile':
        $action = 'delete';
    case 'viewdoc':
        print_fiche_titre($langs->trans('Timesheetuser'));
        if (! $sortfield) $sortfield = 'name';
        $object->fetch_thirdparty();
        $head = Timesheetuser_prepare_head($object);
        dol_fiche_head($head, 'documents', $langs->trans("Timesheetuser"), 0, 'timesheet@timesheet');
        $filearray = dol_dir_list($upload_dir, 'files', 0, '', '\.meta$', $sortfield, 
            (strtolower($sortorder) == 'desc'?SORT_DESC:SORT_ASC), 1);
        $totalsize = 0;
        foreach ($filearray as $key => $file) {
                $totalsize += $file['size'];
        }
        print '<table class = "border" width = "100%">';
        $linkback = '<a href = "'.$PHP_SELF.(! empty($socid)?'?socid='.$socid:'').'">'
            .$langs->trans("BackToList").'</a>';
        // Ref
        print '<tr><td width = "30%">'.$langs->trans("Ref").'</td><td>';
        print $form->showrefnav($object, 'action = view&id', $linkback, 1, 'rowid', 'ref', '');
        print '</td></tr>';
        // Societe
        //print "<tr><td>".$langs->trans("Company")."</td><td>".$object->client->getNomUrl(1)."</td></tr>";
        print '<tr><td>'.$langs->trans("NbOfAttachedFiles").'</td><td colspan = "3">'.count($filearray).'</td></tr>';
        print '<tr><td>'.$langs->trans("TotalSizeOfAttachedFiles").'</td><td colspan = "3">'.$totalsize.' '
            .$langs->trans("bytes").'</td></tr>';
        print '</table>';
        print '</div>';
        $modulepart = 'timesheet';
        $permission = 1;//$user->rights->timesheet->add;
        $param = '&id='.$object->id;
        include_once DOL_DOCUMENT_ROOT . '/core/tpl/document_actions_post_headers.tpl.php';
        break;
    case 'delete':
        if (($id>0 || $ref!='')) {
         $ret = $form->form_confirm($PHP_SELF.'?action=confirm_delete&token='.$token.'&id='.$id, $langs->trans('DeleteTimesheetuser'), $langs->trans('ConfirmDelete'), 'confirm_delete', '', 0, 1);
         if ($ret == 'html') print '<br />';
         //to have the object to be deleted in the background
        }
    case 'list':
    default:
        {
    $sql = 'SELECT';
    $sql .= ' t.rowid, ';
    $sql .= ' t.fk_userid, ';
    $sql .= ' t.date_start, ';
    $sql .= ' t.date_end, ';
    $sql .= ' t.status';
    $sql .= ' FROM '.MAIN_DB_PREFIX.'project_task_timesheet as t';
    $sqlwhere = '';
    if (isset($object->entity))
        $sqlwhere .= ' AND t.entity = '.$conf->entity;
    if ($filter && $filter != -1) {
        // GETPOST('filtre') may be a string {
        $filtrearr = explode(', ', $filter);
        foreach ($filtrearr as $fil) {
                $filt = explode(':', $fil);
                $sqlwhere .= ' AND ' . $filt[0] . ' = ' . $filt[1];
        }
    }
    //pass the search criteria
    if ($ls_userId) $sqlwhere .= natural_search(array('t.fk_userid'), $ls_userId, 2);
    if ($ls_date_start_month)$sqlwhere .= ' AND MONTH(t.date_start) = \''.$ls_date_start_month.'\'';
    if ($ls_date_start_year)$sqlwhere .= ' AND YEAR(t.date_start) = \''.$ls_date_start_year.'\'';
    if ($ls_status) $sqlwhere .= natural_search(array('t.status'), $ls_status);
    if ($ls_target) $sqlwhere .= natural_search(array('t.target'), $ls_target);
    if ($ls_project_tasktime_list) $sqlwhere .= natural_search('t.fk_project_tasktime_list', $ls_project_tasktime_list);
    if ($ls_user_approval) $sqlwhere .= natural_search(array('t.fk_user_approval'), $ls_user_approval);
    //list limit
    if (!empty($sqlwhere)){
        $sql .= ' WHERE '.substr($sqlwhere, 5);
    }
    // Count total nb of records
    $nbtotalofrecords = 0;
    if (getConf('MAIN_DISABLE_FULL_SCANLIST') != false) {
            $sqlcount = 'SELECT COUNT(*) as count FROM '.MAIN_DB_PREFIX.'project_task_timesheet as t';
            if (!empty($sqlwhere))
                $sqlcount .= ' WHERE '.substr($sqlwhere, 5);
            $result = $db->query($sqlcount);
            $nbtotalofrecords = ($result)?$objcount = $db->fetch_object($result)->count:0;
    }
    $sql .= $db->order($sortfield, $sortorder);
    if (!empty($limit)) {
            $sql .= $db->plimit($limit+1, $offset);
    }
    //execute SQL
    dol_syslog($sql, LOG_DEBUG);
    $resql = $db->query($sql);
    if ($resql) {
        if (!empty($ls_userId))$param .= '&ls_userId='.urlencode($ls_userId);
            if (!empty($ls_date_start_month))$param .= '&ls_date_start_month='.urlencode($ls_date_start_month);
            if (!empty($ls_date_start_year))$param .= '&ls_date_start_year='.urlencode($ls_date_start_year);
            if (!empty($ls_status))$param .= '&ls_status='.urlencode($ls_status);
            if (!empty($ls_target))$param .= '&ls_target='.urlencode($ls_target);
            if (!empty($ls_project_tasktime_list))$param .= '&ls_project_tasktime_list='.urlencode($ls_project_tasktime_list);
            if (!empty($ls_user_approval))$param .= '&ls_user_approval='.urlencode($ls_user_approval);
            if ($filter && $filter != -1)$param .= '&filtre='.urlencode($filter);
            $num = $db->num_rows($resql);
            //print_barre_liste function defined in /core/lib/function.lib.php, possible to add a picto
            print_barre_liste($langs->trans("Timesheetuser"), $page, $PHP_SELF, $param, $sortfield, $sortorder, '', $num, $nbtotalofrecords);
            print '<form method = "POST" action = "'.$_SERVER["PHP_SELF"].'">';
            print '<input type = "hidden" id="csrf-token" name = "token" value = "'.$token.'"/>';
            print '<table class = "liste" style = "border-collapse:separate;" width = "100%">'."\n";
            //TITLE
            print '<tr class = "liste_titre">';
            print_liste_field_titre('User', $PHP_SELF, 't.fk_userid', '', $param, '', $sortfield, $sortorder);
            print "\n";
            print_liste_field_titre('DateStart', $PHP_SELF, 't.date_start', '', $param, '', $sortfield, $sortorder);
            print "\n";
            //print_liste_field_titre('dateend', $PHP_SELF, 't.date_end', '', $param, '', $sortfield, $sortorder);
            //print "\n";
            print_liste_field_titre('Status', $PHP_SELF, 't.status', '', $param, '', $sortfield, $sortorder);
            print "\n";
            //print "\n";
            print '<td class = "liste_titre" colspan = "1" >';
            print '</tr>';
            //SEARCH FIELDS
            print '<tr class = "liste_titre">';
            //Search field foruserId
            print '<td class = "liste_titre" colspan = "1" >';
    //select_generic($table, $fieldValue, $htmlName, $fieldToShow1, $fieldToShow2 = '', $selected = '', $separator = ' - ', $sqlTailWhere = '', $selectparam = '', $addtionnalChoices = array('NULL' => 'NULL'), $sqlTailTable = '', $ajaxUrl = '')

            //print select_generic('user', 'rowid', 'ls_userId', 'lastname', 'firstname', $ls_userId, ' - ', '', '', null, '', $ajaxNbChar);
            print $form->select_users($ls_userId, 'ls_userId');
            print '</td>';
            //Search field fordate_start
            print '<td class = "liste_titre" colspan = "1" >';
            print '<input class = "flat" type = "text" size = "1" maxlength = "2" name = "date_start_month" value = "'.$ls_date_start_month.'">';
            $syear = $ls_date_start_year;
            $formother->select_year($syear?$syear:-1, 'ls_date_start_year', 1, 20, 5);
            print '</td>';
            //Search field forstatus
            print '<td class = "liste_titre" colspan = "1" >';
            print $form->selectarray('ls_status', $statusA, $ls_status);
            print '</td>';
            //Search field fortarget
            //        print '<td class = "liste_titre" colspan = "1" >';
            //                print select_enum('project_task_time_approval', 'target', 'ls_target', $ls_target);
            //        print '</td>';
            //Search field forproject_tasktime_list
            //        print '<td class = "liste_titre" colspan = "1" >';
            //                print '<input class = "flat" size = "16" type = "text" name = "ls_project_tasktime_list" value = "'.$ls_project_tasktimeList.'"/>';
            //        print '</td>';
            //Search field foruser_approval
            //print '<td class = "liste_titre" colspan = "1" >';
            //print select_generic('user', 'rowid', 'ls_user_approval', 'lastname', 'firstname', $ls_user_approval);
            //print '</td>';
            print '<td width = "15px">';
            print '<input type = "image" class = "liste_titre" name = "search" src = "'
                .img_picto($langs->trans("Search"), 'search.png', '', '', 1).'" value = "'
                .dol_escape_htmltag($langs->trans("Search")).'" title = "'
                .dol_escape_htmltag($langs->trans("Search")).'">';
            print '<input type = "image" class = "liste_titre" name = "removefilter" src = "'
                .img_picto($langs->trans("Search"), 'searchclear.png', '', '', 1).'" value = "'
                .dol_escape_htmltag($langs->trans("RemoveFilter")).'" title = "'
                .dol_escape_htmltag($langs->trans("RemoveFilter")).'">';
            print '</td>';
            print '</tr>'."\n";
            $i = 0;
            $basedurltab = explode("?", $PHP_SELF);
            $basedurl = $basedurltab[0].'?view=card&id=';
            while($i < $num && $i<$limit)
            {
                    $obj = $db->fetch_object($resql);
                    if ($obj) {
                            // You can use here results
                            print "<tr class = \"dblist oddeven\"  onclick = \"location.href='";
                            print $basedurl.$obj->rowid."'\" >";
                            print "<td>".print_generic('user', 'rowid', $obj->fk_userid, 'lastname', 'firstname', ' ')."</td>";
                            print "<td>".dol_print_date($obj->date_start, 'day')."</td>";
                            print "<td>".$langs->trans(strtolower($statusA[$obj->status]))."</td>";
                            print '<td><a href = "'.$PHP_SELF.'?action=delete&token='.$token.'&id='.$obj->rowid.'">'.img_delete().'</a></td>';
                            print "</tr>";
                    }
                    $i++;
            }
    } else {
        $error++;
        dol_print_error($db);
    }
    print '</table>'."\n";
    print '</from>'."\n";
    // new button
    //print '<a href="?action=create" class = "button" role = "button">'.$langs->trans('New');
    //print ' '.$langs->trans('Timesheetuser')."</a>\n";
}
    break;
}
dol_fiche_end();
/** function to reload page
 *
 * @param string $backtopage    url source
 * @param int $id               id of the object
 * @return null
 */
function reloadpage($backtopage, $id)
{
    global $token;
    if (!empty($backtopage)) {
        header("Location: ".$backtopage);
    //    header("Location: ".$_SERVER["PHP_SELF"].'?view=card&ref='.$ref);
    } elseif ($id>0) {
        header("Location: ".$_SERVER["PHP_SELF"].'?view=card&id='.$id);
    } else{
        header("Location: ".$_SERVER["PHP_SELF"].'?view=list');
    }
    ob_end_flush();
    exit();
}
/** function to prepare hear
 *
 * @global object $langs    lang object
 * @global object $conf     conf object
 * @global object $user     current user
 * @param object  $object   current object browsed
 * @return string
 */
function Timesheetuser_prepare_head($object)
{
    global $langs, $conf, $user, $token;
    $h = 0;
    $head = array();
    $head[$h][0] = $_SERVER["PHP_SELF"].'?view=card&id='.$object->id;
    $head[$h][1] = $langs->trans("Card");
    $head[$h][2] = 'card';
    $h++;
    // Show more tabs from modules
    // Entries must be declared in modules descriptor with line
    // $this->tabs = array('entity:+tabname:Title:@timesheet:/timesheet/mypage.php?id=__ID__');to add new tab
    // $this->tabs = array('entity:-tabname);                                                                                                to remove a tab
    complete_head_from_modules($conf, $langs, $object, $head, $h, 'timesheet');
    complete_head_from_modules($conf, $langs, $object, $head, $h, 'timesheet', 'remove');
    $head[$h][0] = $_SERVER["PHP_SELF"].'?view=carddoc&id='.$object->id;
    $head[$h][1] = $langs->trans("Documents");
    $head[$h][2] = 'documents';
    $h++;
    $head[$h][0] = $_SERVER["PHP_SELF"].'?view=cardinfo&id='.$object->id;
    $head[$h][1] = $langs->trans("Info");
    $head[$h][2] = 'info';
    $h++;
    return $head;
}
// End of page
llxFooter();
$db->close();
