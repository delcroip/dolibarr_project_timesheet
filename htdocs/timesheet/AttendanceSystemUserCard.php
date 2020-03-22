<?php
/* 
 * Copyright (C) 2007-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2018	   Patrick DELCROIX     <pmpdelcroix@gmail.com>
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
 *   	\file       dev/AttendanceSystemUsers/AttendanceSystemUserPage.php
 *		\ingroup    mymodule othermodule1 othermodule2
 *		\brief      This file is an example of a php page
 *					Initialy built by build_class_from_table on 2020-03-15 16:01
 */

//if (! defined('NOREQUIREUSER'))  define('NOREQUIREUSER','1');
//if (! defined('NOREQUIREDB'))    define('NOREQUIREDB','1');
//if (! defined('NOREQUIRESOC'))   define('NOREQUIRESOC','1');
//if (! defined('NOREQUIRETRAN'))  define('NOREQUIRETRAN','1');
//if (! defined('NOCSRFCHECK'))    define('NOCSRFCHECK','1');			// Do not check anti CSRF attack test
//if (! defined('NOSTYLECHECK'))   define('NOSTYLECHECK','1');			// Do not check style html tag into posted data
//if (! defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL','1');		// Do not check anti POST attack test
//if (! defined('NOREQUIREMENU'))  define('NOREQUIREMENU','1');			// If there is no need to load and show top and left menu
//if (! defined('NOREQUIREHTML'))  define('NOREQUIREHTML','1');			// If we don't need to load the html.form.class.php
//if (! defined('NOREQUIREAJAX'))  define('NOREQUIREAJAX','1');
//if (! defined("NOLOGIN"))        define("NOLOGIN",'1');				// If this page is public (can be called outside logged session)

// Change this following line to use the correct relative path (../, ../../, etc)
include 'core/lib/includeMain.lib.php';
// Change this following line to use the correct relative path from htdocs
//include_once(DOL_DOCUMENT_ROOT.'/core/class/formcompany.class.php');
//require_once 'lib/mymodule.lib.php';
require_once 'class/AttendanceSystemUser.class.php';
require_once 'core/lib/generic.lib.php';
require_once 'core/lib/AttendanceSystemUser.lib.php';
dol_include_once('/core/lib/functions2.lib.php');
//document handling
dol_include_once('/core/lib/files.lib.php');
//dol_include_once('/core/lib/images.lib.php');
dol_include_once('/core/class/html.formfile.class.php');
dol_include_once('/core/class/html.formother.class.php');
dol_include_once('/core/class/html.formprojet.class.php');
$PHP_SELF=$_SERVER['PHP_SELF'];
// Load traductions files requiredby by page
//$langs->load("companies");
$langs->load("AttendanceSystemUser@mymodule");

// Get parameter
$id			= GETPOST('id','int');
$ref                    = GETPOST('ref','alpha');
$action		= GETPOST('action','alpha');
$backtopage = GETPOST('backtopage');
$cancel=GETPOST('cancel');
$confirm=GETPOST('confirm');
$tms= GETPOST('tms','alpha');
//// Get parameters
/*
$sortfield = GETPOST('sortfield','alpha'); 
$sortorder = GETPOST('sortorder','alpha')?GETPOST('sortorder','alpha'):'ASC';
$removefilter=isset($_POST["removefilter_x"]) || isset($_POST["removefilter"]);
//$applyfilter=isset($_POST["search_x"]) ;//|| isset($_POST["search"]);
if (!$removefilter )		// Both test must be present to be compatible with all browsers
{
    $ls_fields1=GETPOST('ls_fields1','int');
    $ls_fields2=GETPOST('ls_fields2','alpha');
}
*/






 // uncomment to avoid resubmision
//if(isset( $_SESSION['AttendanceSystemUser_class'][$tms]))
//{

 //   $cancel=TRUE;
 //  setEventMessages('Internal error, POST not exptected', null, 'errors');
//}



// Right Management
 /*
if ($user->societe_id > 0 || 
       (!$user->rights->mymodule->add && ($action=='add' || $action='create')) ||
       (!$user->rights->mymodule->view && ($action=='list' || $action='view')) ||
       (!$user->rights->mymodule->delete && ($action=='confirm_delete')) ||
       (!$user->rights->mymodule->edit && ($action=='edit' || $action='update')))
{
	accessforbidden();
}
*/

// create object and set id or ref if provided as parameter
$object=new AttendanceSystemUser($db);
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
    $object->fetch($id,$ref);
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
        AttendanceSystemUserReloadPage($backtopage,$id,$ref);
}else if (($action == 'add') || ($action == 'update' && ($id>0 || !empty($ref))))
{
    //block resubmit
    if(empty($tms) || (!isset($_SESSION['AttendanceSystemUser'][$tms]))){
            setEventMessage('WrongTimeStamp_requestNotExpected', 'errors');
            $action=($action=='add')?'create':'view';
    }
    //retrive the data
    $object->prop1=GETPOST("field1");
    $object->prop2=GETPOST("field2");

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
        
 }else if ($id==0 && $ref=='' && $action!='create') 
 {
     $action='create';
 }
 
 
  switch($action){		
    case 'update':
        $result=$object->update($user);
        if ($result > 0)
        {
            // Creation OK
            unset($_SESSION['AttendanceSystemUser'][$tms]);
            setEventMessage('RecordUpdated','mesgs');

        }
        else
        {
                // Creation KO
            if (! empty($object->errors)) setEventMessages(null, $object->errors, 'errors');
            else setEventMessage('RecordNotUpdated', 'errors');

        }
        $action='view';
    case 'delete':
        if(isset($_GET['urlfile'])) $action='deletefile';
    case 'view':
    case 'viewinfo':
    case 'edit':
        // fetch the object data if possible
        if ($id > 0 || !empty($ref) )
        {
            $result=$object->fetch($id,$ref);
            if ($result < 0){ 
                dol_print_error($db);
            }else { // fill the id & ref
                if(isset($object->id))$id = $object->id;
                if(isset($object->rowid))$id = $object->rowid;
                if(isset($object->ref))$ref = $object->ref;
            }

        }else
        {
            setEventMessage( $langs->trans('noIdPresent').' id:'.$id,'errors');
            $action='create';
        }
        break;
    case 'add':
        $result=$object->create($user);
        if ($result > 0)
        {
                // Creation OK
            // remove the tms
               unset($_SESSION['AttendanceSystemUser'][$tms]);
               setEventMessage('RecordSucessfullyCreated', 'mesgs');
               AttendanceSystemUserReloadPage($backtopage,$result,'');

        }else
        {
                // Creation KO
                if (! empty($object->errors)) setEventMessages(null, $object->errors, 'errors');
                else  setEventMessage('RecordNotSucessfullyCreated', 'errors');
                $action='create';
        }                            
        break;
     case 'confirm_delete':

            $result=($confirm=='yes')?$object->delete($user):0;
            if ($result > 0)
            {
                // Delete OK
                setEventMessage($langs->trans('RecordDeleted'), 'mesgs');
            }
            else
            {
                // Delete NOK
                if (! empty($object->errors)) setEventMessages(null,$object->errors,'errors');
                else setEventMessage('RecordNotDeleted','errors');
            }
            AttendanceSystemUserReloadPage($backtopage, 0, '');
         break;


          
 }             
//Removing the tms array so the order can't be submitted two times
if(isset( $_SESSION['AttendanceSystemUser'][$tms]))
{
    unset($_SESSION['AttendanceSystemUser'][$tms]);
}
if(($action == 'create') || ($action == 'edit' && ($id>0 || !empty($ref)))){
    $tms=getToken();
    $_SESSION['AttendanceSystemUser'][$tms]=array();
    $_SESSION['AttendanceSystemUser'][$tms]['action']=$action;
            
}

/***************************************************
* VIEW
*
* Put here all code to build page
****************************************************/

llxHeader('','AttendanceSystemUser','');
print "<div> <!-- module body-->";
$form=new Form($db);
$formother=new FormOther($db);
$formproject=new FormProjets($db);   
$fuser=new User($db);
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

$edit=$new=0;
switch ($action) {
    case 'create':
        $new=1;
    case 'edit':
        $edit=1;
   case 'delete';
        if( $action=='delete' && ($id>0 || $ref!="")){
         $ret=$form->form_confirm($PHP_SELF.'?action=confirm_delete&id='.$id,$langs->trans('DeleteAttendanceSystemUser'),$langs->trans('ConfirmDelete'),'confirm_delete', '', 0, 1);
         if ($ret == 'html') print '<br />';
         //to have the object to be deleted in the background\
        }
    case 'view':
    {
        // tabs
        if($edit==0 && $new==0){ //show tabs
            $head=AttendanceSystemUserPrepareHead($object);
            dol_fiche_head($head,'card',$langs->trans('AttendanceSystemUser'),0,'mymodule@mymodule');            
        }else{
            print_fiche_titre($langs->trans('AttendanceSystemUser'));
        }

	print '<br>';
        if($edit==1){
            if($new==1){
                print '<form method="POST" action="'.$PHP_SELF.'?action=add">';
            }else{
                print '<form method="POST" action="'.$PHP_SELF.'?action=update&id='.$id.'">';
            }
                        
            print '<input type="hidden" name="tms" value="'.$tms.'">';
            print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';

        }else {// show the nav bar
            $basedurl=dol_buildpath("/mymodule/AttendanceSystemUserList.php", 1);
            $linkback = '<a href="'.$basedurl.(! empty($socid)?'?socid='.$socid:'').'">'.$langs->trans("BackToList").'</a>';
            if(!isset($object->ref))//save ref if any
                $object->ref=$object->id;
            print $form->showrefnav($object, 'action=view&id', $linkback, 1, 'rowid', 'ref', '');
            //reloqd the ref

        }

	print '<table class="border centpercent">'."\n";

        

// show the field user

	print "<tr>\n";
	print '<td class="fieldrequired">'.$langs->trans('User').' </td><td>';
	if($edit == 1){
		$selected=$object->user;
		$htmlname = 'User';
		print $form->select_dolusers($selected,$htmlname);
	}else{
		if($object->fk_user != '' ){
			$StaticObject = New User($db);
			print "<td>".$StaticObject->getNomUrl('1',$object->fk_user)."</td>";
		}else{
			print '<td></td>';
		}
	}
	print "</td>";
	print "\n</tr>\n";

// show the field as_uid

	print "<tr>\n";
	print '<td>'.$langs->trans('Asuid').' </td><td>';
	if($edit == 1){
		print '<input type="text" value="'.$object->as_uid.'" name="Asuid">';
	}else{
		print $object->as_uid;
	}
	print "</td>";
	print "\n</tr>\n";

// show the field rfid

	print "<tr>\n";
	print '<td>'.$langs->trans('Rfid').' </td><td>';
	if($edit == 1){
		print '<input type="text" value="'.$object->rfid.'" name="Rfid">';
	}else{
		print $object->rfid;
	}
	print "</td>";
	print "\n</tr>\n";

// show the field role

	print "<tr>\n";
	print '<td>'.$langs->trans('Role').' </td><td>';
	if($edit == 1){
		print '<input type="text" value="'.$object->role.'" name="Role">';
	}else{
		print $object->role;
	}
	print "</td>";
	print "\n</tr>\n";

// show the field passwd

	print "<tr>\n";
	print '<td>'.$langs->trans('Passwd').' </td><td>';
	if($edit == 1){
		print '<input type="text" value="'.$object->passwd.'" name="Passwd">';
	}else{
		print $object->passwd;
	}
	print "</td>";
	print "\n</tr>\n";

// show the field data

	print "<tr>\n";
	print '<td>'.$langs->trans('Data').' </td><td>';
	if($edit == 1){
		print '<input type="text" value="'.$object->data.'" name="Data">';
	}else{
		print $object->data;
	}
	print "</td>";
	print "\n</tr>\n";

// show the field status

	print "<tr>\n";
	print '<td>'.$langs->trans('Status').' </td><td>';
	if($edit == 1){
		print '<input type="text" value="'.$object->status.'" name="Status">';
	}else{
		print $object->status;
	}
	print "</td>";
	print "\n</tr>\n";

// show the field mode

	print "<tr>\n";
	print '<td>'.$langs->trans('Mode').' </td><td>';
	if($edit == 1){
		print '<input type="text" value="'.$object->mode.'" name="Mode">';
	}else{
		print $object->mode;
	}
	print "</td>";
	print "\n</tr>\n";

        

	print '</table>'."\n";
	print '<br>';
	print '<div class="center">';
        if($edit==1){
        if($new==1){
                print '<input type="submit" class="butAction" name="add" value="'.$langs->trans('Add').'">';
            }else{
                print '<input type="submit" name="update" value="'.$langs->trans('Update').'" class="butAction">';
            }
            print ' &nbsp; <input type="submit" class="butActionDelete" name="cancel" value="'.$langs->trans('Cancel').'"></div>';
            print '</form>';
        }else{
            $parameters=array();
            $reshook=$hookmanager->executeHooks('addMoreActionsButtons',$parameters,$object,$action);    // Note that $action and $object may have been modified by hook
            if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

            if (empty($reshook))
            {
                print '<div class="tabsAction">';

                // Boutons d'actions
                //if($user->rights->AttendanceSystemUser->edit)
                //{
                    print '<a href="'.$PHP_SELF.'?id='.$id.'&action=edit" class="butAction">'.$langs->trans('Update').'</a>';
                //}
                
                //if ($user->rights->AttendanceSystemUser->delete)
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
        case 'viewinfo':
        print_fiche_titre($langs->trans('AttendanceSystemUser'));
        $head=AttendanceSystemUserPrepareHead($object);
        dol_fiche_head($head,'info',$langs->trans("AttendanceSystemUser"),0,'mymodule@mymodule');            
        print '<table width="100%"><tr><td>';
        dol_print_object_info($object);
        print '</td></tr></table>';
        print '</div>';
        break;

    case 'delete':
        if( ($id>0 || $ref!='')){
         $ret=$form->form_confirm($PHP_SELF.'?action=confirm_delete&id='.$id,$langs->trans('DeleteAttendanceSystemUser'),$langs->trans('ConfirmDelete'),'confirm_delete', '', 0, 1);
         if ($ret == 'html') print '<br />';
         //to have the object to be deleted in the background        
        }
}
dol_fiche_end();

// End of page
llxFooter();
$db->close();
