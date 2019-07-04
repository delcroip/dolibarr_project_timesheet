<?php
/*
 * Copyright (C) 2007-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2018       Patrick DELCROIX     <pmpdelcroix@gmail.com>
 * * Copyright (C) ---Put here your own copyright and developer email---
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *       \file       dev/projettasktimes/projettasktime_page.php
 *        \ingroup    timesheet othermodule1 othermodule2
 *        \brief      This file is an example of a php page
 *                    Initialy built by build_class_from_table on 2019-07-03 22:42
 */

//if (! defined('NOREQUIREUSER'))  define('NOREQUIREUSER','1');
//if (! defined('NOREQUIREDB'))    define('NOREQUIREDB','1');
//if (! defined('NOREQUIRESOC'))   define('NOREQUIRESOC','1');
//if (! defined('NOREQUIRETRAN'))  define('NOREQUIRETRAN','1');
//if (! defined('NOCSRFCHECK'))    define('NOCSRFCHECK','1');            // Do not check anti CSRF attack test
//if (! defined('NOSTYLECHECK'))   define('NOSTYLECHECK','1');            // Do not check style html tag into posted data
//if (! defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL','1');        // Do not check anti POST attack test
//if (! defined('NOREQUIREMENU'))  define('NOREQUIREMENU','1');            // If there is no need to load and show top and left menu
//if (! defined('NOREQUIREHTML'))  define('NOREQUIREHTML','1');            // If we don't need to load the html.form.class.php
//if (! defined('NOREQUIREAJAX'))  define('NOREQUIREAJAX','1');
//if (! defined("NOLOGIN"))        define("NOLOGIN",'1');                // If this page is public (can be called outside logged session)

// Change this following line to use the correct relative path (../, ../../, etc)
include 'core/lib/includeMain.lib.php';
// Change this following line to use the correct relative path from htdocs
//include_once(DOL_DOCUMENT_ROOT.'/core/class/formcompany.class.php');
//require_once 'lib/timesheet.lib.php';
require_once 'class/ProjetTaskTime.class.php';
require_once 'core/lib/generic.lib.php';
require_once 'class/TimesheetTask.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/task.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
require_once 'core/lib/timesheet.lib.php';

dol_include_once('/core/lib/functions2.lib.php');
//document handling
dol_include_once('/core/lib/files.lib.php');
//dol_include_once('/core/lib/images.lib.php');
dol_include_once('/core/class/html.formfile.class.php');
dol_include_once('/core/class/html.formother.class.php');
dol_include_once('/core/class/html.formprojet.class.php');
$PHP_SELF = $_SERVER['PHP_SELF'];
// Load traductions files requiredby by page
//$langs->load("companies");
$langs->load("projettasktime@timesheet");

// Get parameter
$id            = GETPOST('id', 'int');
$ref           = GETPOST('ref', 'alpha');
$action        = GETPOST('action', 'alpha');
$backtopage = GETPOST('backtopage');
$cancel = GETPOST('cancel');
$confirm = GETPOST('confirm');
$tms = GETPOST('tms', 'alpha');
//// Get parameters
/*
$sortfield = GETPOST('sortfield', 'alpha');
$sortorder = GETPOST('sortorder', 'alpha')?GETPOST('sortorder','alpha'):'ASC';
$removefilter=isset($_POST["removefilter_x"]) || isset($_POST["removefilter"]);
//$applyfilter=isset($_POST["search_x"]) ;//|| isset($_POST["search"]);
if (!$removefilter )        // Both test must be present to be compatible with all browsers
{
        $ls_task= GETPOST('ls_task','int');
    $ls_task_date_month= GETPOST('ls_task_date_month','int');
    $ls_task_date_year= GETPOST('ls_task_date_year','int');
    $ls_task_datehour_month= GETPOST('ls_task_datehour_month','int');
    $ls_task_datehour_year= GETPOST('ls_task_datehour_year','int');
    $ls_task_date_withhour= GETPOST('ls_task_date_withhour','int');
    $ls_task_duration= GETPOST('ls_task_duration','int');
    $ls_user= GETPOST('ls_user','int');
    if($ls_user==-1)$ls_user='';
    $ls_thm= GETPOST('ls_thm','int');
    $ls_note= GETPOST('ls_note','alpha');
    $ls_invoice_id= GETPOST('ls_invoice_id','int');
    $ls_invoice_line_id= GETPOST('ls_invoice_line_id','int');
    $ls_import_key= GETPOST('ls_import_key','alpha');
    $ls_status= GETPOST('ls_status','int');
    $ls_task_time_approval= GETPOST('ls_task_time_approval','int');


}
*/






 // uncomment to avoid resubmision
//if(isset( $_SESSION['projettasktime_class'][$tms]))
//{

 //   $cancel=TRUE;
 //  setEventMessages('Internal error, POST not exptected', null, 'errors');
//}



// Right Management
 /*
if ($user->societe_id > 0 ||
       (!$user->rights->timesheet->add && ($action=='add' || $action='create')) ||
       (!$user->rights->timesheet->view && ($action=='list' || $action='view')) ||
       (!$user->rights->timesheet->delete && ($action=='confirm_delete')) ||
       (!$user->rights->timesheet->edit && ($action=='edit' || $action='update')))
{
    accessforbidden();
}
*/

// create object and set id or ref if provided as parameter
$object=new Projettasktime($db);
if($id>0)
{
    $object->id=$id;
    $object->fetch($id);
    $ref=dol_sanitizeFileName($object->ref);
}
if(!empty($ref))
{
    $object->ref=$ref;
    $object->id=$id;
    $object->fetch($id, $ref);
    $ref=dol_sanitizeFileName($object->ref);
}


/*******************************************************************
* ACTIONS
*
* Put here all code to do according to value of "action" parameter
********************************************************************/

// Action to add record
$error=0;
if ($cancel){
//        ProjettasktimeReloadPage($backtopage,$id,$ref);
}elseif (($action == 'add') || ($action == 'update' && ($id>0 || !empty($ref))))
{
    //block resubmit
    if(empty($tms) || (!isset($_SESSION['Projettasktime'][$tms]))){
        setEventMessage('WrongTimeStamp_requestNotExpected', 'errors');
        $action=($action=='add')?'create':'view';
    }
    //retrive the data
    $object->task = GETPOST('Task');
    $object->task_date = dol_mktime(0, 0, 0, GETPOST('Taskdatemonth'), GETPOST('Taskdateday'), GETPOST('Taskdateyear'));
    $object->task_datehour = dol_mktime(0, 0, 0, GETPOST('Taskdatehourmonth'), GETPOST('Taskdatehourday'), GETPOST('Taskdatehouryear'));
    $object->task_date_withhour = GETPOST('Taskdatewithhour');
    $object->task_duration = GETPOST('Taskduration');
    $object->user = GETPOST('User');
    $object->thm = GETPOST('Thm');
    $object->note = GETPOST('Note');
    $object->invoice_id = GETPOST('Invoiceid');
    $object->invoice_line_id = GETPOST('Invoicelineid');
    $object->import_key = GETPOST('Importkey');
    $object->status = GETPOST('Status');
    $object->task_time_approval = GETPOST('Tasktimeapproval');



// test here if the post data is valide
 /*
 if($object->prop1==0 || $object->prop2==0)
 {
     if ($id>0 || $ref!='')
        $action='create';
     else
        $action='edit';
 }
  */
}elseif ($id == 0 && $ref == '' && $action != 'create')
{
     $action = 'create';
}
switch($action){
    case 'update':
        $result = $object->update($user);
        if ($result > 0)
        {
            // Creation OK
            unset($_SESSION['Projettasktime'][$tms]);
            setEventMessage('RecordUpdated', 'mesgs');
        }
        else
        {
            // Creation KO
            if (! empty($object->errors)) setEventMessages(null, $object->errors, 'errors');
            else setEventMessage('RecordNotUpdated', 'errors');
        }
        $action='view';
    case 'delete':
        if(isset($_GET['urlfile'])) $action = 'deletefile';
    case 'view':
    case 'viewinfo':
    case 'edit':
        // fetch the object data if possible
        if ($id > 0 || !empty($ref) )
        {
            $result = $object->fetch($id, $ref);
            if ($result < 0){
                dol_print_error($db);
            }else { // fill the id & ref
                if(isset($object->id))$id = $object->id;
                if(isset($object->rowid))$id = $object->rowid;
                if(isset($object->ref))$ref = $object->ref;
            }
        }else
        {
            setEventMessage($langs->trans('noIdPresent').' id:'.$id, 'errors');
            $action = 'create';
        }
        break;
    case 'add':
        $result = $object->create($user);
        if ($result > 0)
        {
                // Creation OK
            // remove the tms
               unset($_SESSION['Projettasktime'][$tms]);
               setEventMessage('RecordSucessfullyCreated', 'mesgs');
             //  ProjettasktimeReloadPage($backtopage,$result,'');
        }else
        {
                // Creation KO
                if (! empty($object->errors)) setEventMessages(null, $object->errors, 'errors');
                else  setEventMessage('RecordNotSucessfullyCreated', 'errors');
                $action = 'create';
        }
        break;
     case 'confirm_delete':
            $result = ($confirm == 'yes')?$object->delete($user):0;
            if ($result > 0)
            {
                // Delete OK
                setEventMessage($langs->trans('RecordDeleted'), 'mesgs');
            }
            else
            {
                // Delete NOK
                if (! empty($object->errors)) setEventMessages(null, $object->errors, 'errors');
                else setEventMessage('RecordNotDeleted', 'errors');
            }
           // ProjettasktimeReloadPage($backtopage, 0, '');
         break;
}
//Removing the tms array so the order can't be submitted two times
if(isset($_SESSION['Projettasktime'][$tms]))
{
    unset($_SESSION['Projettasktime'][$tms]);
}
if(($action == 'create') || ($action == 'edit' && ($id>0 || !empty($ref)))){
    $tms=getToken();
    $_SESSION['Projettasktime'][$tms]=array();
    $_SESSION['Projettasktime'][$tms]['action'] = $action;
}

/***************************************************
* VIEW
*
* Put here all code to build page
****************************************************/

llxHeader('', 'Projettasktime', '');
print "<div> <!-- module body-->";
$form = new Form($db);
$formother = new FormOther($db);
$formproject = new FormProjets($db);
$fuser = new User($db);
// Put here content of your page

// Example : Adding jquery code
/*print '<script type="text/javascript" language="javascript">
jQuery(document).ready(function() {
    function init_myfunc()
    {
        jQuery("#myid").removeAttr(\'disabled\');
        jQuery("#myid").attr(\'disabled\',\'disabled\');
    }
    init_myfunc();
    jQuery("#mybutton").click(function() {
        init_needroot();
    });
});
</script>';*/

$edit = $new = 0;
switch ($action) {
    case 'create':
        $new = 1;
    case 'edit':
        $edit = 1;
   case 'delete';
        if( $action == 'delete' && ($id>0 || $ref != "")){
         $ret = $form->form_confirm($PHP_SELF.'?action=confirm_delete&id='.$id, $langs->trans('DeleteProjettasktime'), $langs->trans('ConfirmDelete'), 'confirm_delete', '', 0, 1);
         if ($ret == 'html') print '<br />';
         //to have the object to be deleted in the background\
        }
    case 'view':
    {
        // tabs
        if($edit == 0 && $new == 0){ //show tabs
        //    $head = ProjettasktimePrepareHead($object);
            dol_fiche_head("", 'card', $langs->trans('Projettasktime'), 0, 'timesheet@timesheet');
        }else{
            print_fiche_titre($langs->trans('Projettasktime'));
        }
    print '<br>';
        if($edit == 1){
            if($new == 1){
                print '<form method="POST" action="'.$PHP_SELF.'?action=add">';
            }else{
                print '<form method="POST" action="'.$PHP_SELF.'?action=update&id='.$id.'">';
            }

            print '<input type="hidden" name="tms" value="'.$tms.'">';
            print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';
        }else {// show the nav bar
            $basedurl = dol_buildpath("/timesheet/projettasktime_list.php", 1);
            $linkback = '<a href="'.$basedurl.(! empty($socid)?'?socid='.$socid:'').'">'.$langs->trans("BackToList").'</a>';
            if(!isset($object->ref))//save ref if any
                $object->ref = $object->id;
            print $form->showrefnav($object, 'action=view&id', $linkback, 1, 'rowid', 'ref', '');
            //reloqd the ref
        }
    print '<table class="border centpercent">'."\n";

// show the field task

    print "<tr>\n";
    print '<td class="fieldrequired">'.$langs->trans('Task').' </td><td>';
    if($edit ==1 ){
        $sql_task = array('table'=> 'projet_task', 'keyfield'=> 'rowid', 'fields'=>'ref,label', 'join' => '', 'where'=>'', 'tail'=>'');
        $html_task = array('name'=>'Task', 'class'=>'', 'otherparam'=>'', 'ajaxNbChar'=>'', 'separator'=> '-');
        $addChoices_task = null;
        print select_sellist($sql_task, $html_task, $object->task, $addChoices_task);
    }else{
        $sTask = new Task($db);
        $sTask->fetch($object->task);
        print $sTask->getNomUrl(1);
    }
    print "</td>";
    print "\n</tr>\n";

// show the field task_date

    print "<tr>\n";
    print '<td>'.$langs->trans('Taskdate').' </td><td>';
    if($edit == 1){
        if($new == 1){
            print $form->select_date(-1, 'Taskdate');
        }else
        {
            print $form->select_date($object->task_date, 'Taskdate');
        }
    }else{
            print dol_print_date($object->task_date, 'day');
    }
    print "</td>";
    print "\n</tr>\n";

// show the field task_datehour

    print "<tr>\n";
    print '<td>'.$langs->trans('Taskdatehour').' </td><td>';
    if($edit == 1){
        if($new == 1)
        {
            print $form->select_date(-1, 'Taskdatehour');
        }else{
            print $form->select_date($object->task_datehour, 'Taskdatehour');
        }
    }else{
            print dol_print_date($object->task_datehour, 'day');
    }
    print "</td>";
    print "\n</tr>\n";

// show the field task_date_withhour

    print "<tr>\n";
    print '<td>'.$langs->trans('Taskdatewithhour').' </td><td>';
    if($edit == 1){
        print '<input type="text" value="'.$object->task_date_withhour.'" name="Taskdatewithhour">';
    }else{
        print $object->task_date_withhour;
    }
    print "</td>";
    print "\n</tr>\n";

// show the field task_duration

    print "<tr>\n";
    print '<td>'.$langs->trans('Taskduration').' </td><td>';
    if($edit == 1){
        print '<input type="text" value="'.$object->task_duration.'" name="Taskduration">';
    }else{
        print $object->task_duration;
    }
    print "</td>";
    print "\n</tr>\n";

// show the field user

    print "<tr>\n";
    print '<td>'.$langs->trans('User').' </td><td>';
    if($edit == 1){
        $selected=$object->user;
        print $form->select_dolusers($selected, 'User', 1, '', 0);
    }else{
        $sUser = new User($db);
        $sUser->fetch($object->user);
        print  $sUser->getNomUrl(1);
    }
    print "</td>";
    print "\n</tr>\n";

// show the field thm

    print "<tr>\n";
    print '<td>'.$langs->trans('Thm').' </td><td>';
    if($edit == 1){
        print '<input type="text" value="'.$object->thm.'" name="Thm">';
    }else{
        print $object->thm;
    }
    print "</td>";
    print "\n</tr>\n";

// show the field note

    print "<tr>\n";
    print '<td>'.$langs->trans('Note').' </td><td>';
    if($edit == 1){
        print '<input type="text" value="'.$object->note.'" name="Note">';
    }else{
        print $object->note;
    }
    print "</td>";
    print "\n</tr>\n";

// show the field invoice_id

    print "<tr>\n";
    print '<td>'.$langs->trans('Invoiceid').' </td><td>';
    if($edit == 1){
        print '<input type="text" value="'.$object->invoice_id.'" name="Invoiceid">';
    }else{
        print $object->invoice_id;
    }
    print "</td>";
    print "\n</tr>\n";

// show the field invoice_line_id

    print "<tr>\n";
    print '<td>'.$langs->trans('Invoicelineid').' </td><td>';
    if($edit == 1){
        print '<input type="text" value="'.$object->invoice_line_id.'" name="Invoicelineid">';
    }else{
        print $object->invoice_line_id;
    }
    print "</td>";
    print "\n</tr>\n";

// show the field import_key

    print "<tr>\n";
    print '<td>'.$langs->trans('Importkey').' </td><td>';
    if($edit == 1){
        print '<input type="text" value="'.$object->import_key.'" name="Importkey">';
    }else{
        print $object->import_key;
    }
    print "</td>";
    print "\n</tr>\n";

// show the field status

    print "<tr>\n";
    print '<td>'.$langs->trans('Status').' </td><td>';
    if($edit == 1){
        if ($new == 1)
            print '<input type="text" value="1" name="Status">';
        else
            print '<input type="text" value="'.$object->status.'" name="Status">';
    }else{
        print $object->status;
    }
    print "</td>";
    print "\n</tr>\n";

// show the field task_time_approval

    print "<tr>\n";
    print '<td>'.$langs->trans('Tasktimeapproval').' </td><td>';
    $sql_task_time_approval=array('table'=> 'project_task_time_approval', 'keyfield'=> 'rowid', 'fields'=>'date_start,date_end,fk_projet_task,fk_userid', 'join' => '', 'where'=>'', 'tail'=>'');
    if($edit == 1){
        $html_task_time_approval=array('name'=>'Tasktimeapproval', 'class'=>'', 'otherparam'=>'', 'ajaxNbChar'=>'', 'separator'=> '-');
        $addChoices_task_time_approval=null;
        print select_sellist($sql_task_time_approval, $html_task_time_approval, $object->task_time_approval, $addChoices_task_time_approval);
    }else{
        print print_sellist($sql_task_time_approval, $object->task_time_approval);
    }
    print "</td>";
    print "\n</tr>\n";
    print "<td></td></tr>\n";



    print '</table>'."\n";
    print '<br>';
    print '<div class="center">';
        if($edit == 1){
            if($new == 1){
                print '<input type="submit" class="butAction" name="add" value="'.$langs->trans('Add').'">';
            }else{
                print '<input type="submit" name="update" value="'.$langs->trans('Update').'" class="butAction">';
            }
            print ' &nbsp; <input type="submit" class="butActionDelete" name="cancel" value="'.$langs->trans('Cancel').'"></div>';
            print '</form>';
        }else{
            $parameters=array();
            $reshook=$hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action);    // Note that $action and $object may have been modified by hook
            if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

            if (empty($reshook))
            {
                print '<div class="tabsAction">';

                // Boutons d'actions
                //if($user->rights->Projettasktime->edit)
                //{
                    print '<a href="'.$PHP_SELF.'?id='.$id.'&action=edit" class="butAction">'.$langs->trans('Update').'</a>';
                //}

                //if ($user->rights->Projettasktime->delete)
                //{
                    print '<a class="butActionDelete" href="'.$PHP_SELF.'?id='.$id.'&action=delete">'.$langs->trans('Delete').'</a>';
                //}
                //else
                //{
                //    print '<a class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->trans("NotAllowed")).'">'.$langs->trans('Delete').'</a>';
                //}

                print '</div>';
            }
        }
        break;
    }

    case 'delete':
        if( ($id>0 || $ref!='')){
         $ret=$form->form_confirm($PHP_SELF.'?action=confirm_delete&id='.$id, $langs->trans('DeleteProjettasktime'), $langs->trans('ConfirmDelete'), 'confirm_delete', '', 0, 1);
         if ($ret == 'html') print '<br />';
         //to have the object to be deleted in the background
        }
}
dol_fiche_end();

// End of page
llxFooter();
$db->close();
