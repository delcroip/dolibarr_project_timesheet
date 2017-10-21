<?php
/* 
 * Copyright (C) 2007-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) ---Put here your own copyright and developer email---
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
include 'core/lib/generic.lib.php';
require_once 'class/ResourceActivity.class.php';
dol_include_once('/core/lib/functions2.lib.php');
//document handling
dol_include_once('/core/lib/files.lib.php');
dol_include_once('/core/class/html.formfile.class.php');
//dol_include_once('/timesheet/lib/timesheet.lib.php');
dol_include_once('/core/class/html.formother.class.php');
$PHP_SELF=$_SERVER['PHP_SELF'];
// Load traductions files requiredby by page
//$langs->load("companies");
$langs->load("Timesheetactivity_class");

// Get parameter
$id			= GETPOST('id','int');
$ref                    = GETPOST('ref','alpha');
$action		= GETPOST('action','alpha');
$backtopage = GETPOST('backtopage');
$cancel=GETPOST('cancel');
$confirm=GETPOST('confirm');
$tms= GETPOST('tms','alpha');
//// Get parameters
$sortfield = GETPOST('sortfield','alpha'); 
$sortorder = GETPOST('sortorder','alpha')?GETPOST('sortorder','alpha'):'ASC';
$removefilter=isset($_POST["removefilter_x"]) || isset($_POST["removefilter"]);
//$applyfilter=isset($_POST["search_x"]) ;//|| isset($_POST["search"]);
if (!$removefilter )		// Both test must be present to be compatible with all browsers
{
    	$ls_date_start_month= GETPOST('ls_date_start_month','int');
	$ls_date_start_year= GETPOST('ls_date_start_year','int');
	$ls_date_end_month= GETPOST('ls_date_end_month','int');
	$ls_date_end_year= GETPOST('ls_date_end_year','int');
	$ls_time_start= GETPOST('ls_time_start','int');
	$ls_time_end= GETPOST('ls_time_end','int');
	$ls_weekdays= GETPOST('ls_weekdays','int');
	$ls_redundancy= GETPOST('ls_redundancy','int');
	$ls_timetype= GETPOST('ls_timetype','int');
	$ls_status= GETPOST('ls_status','int');
	$ls_priority= GETPOST('ls_priority','int');
	$ls_note= GETPOST('ls_note','alpha');
	$ls_userid= GETPOST('ls_userid','int');
	if($ls_userid==-1)$ls_userid='';
	$ls_element_id= GETPOST('ls_element_id','int');

    
}


$page = GETPOST('page','int'); //FIXME, need to use for all the list
if ($page == -1) { $page = 0; }
$limit=$conf->liste_limit;
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;




 // uncomment to avoid resubmision
//if(isset( $_SESSION['Timesheetactivity_class'][$tms]))
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
$object=new ResourceActivity($db);
if($id>0)
{
    $object->id=$id; 
    $object->fetch($id);
    $ref=dol_sanitizeFileName($object->ref);
    $upload_dir = $conf->timesheet->dir_output.'/'.get_exdir($object->id,2,0,0,$object,'ResourceActivity').$ref;
    if(empty($action))$action='viewdoc'; //  the doc handling part send back only the ID without actions
}
if(!empty($ref))
{
    $object->ref=$ref; 
    $object->id=$id; 
    $object->fetch($id);
    $ref=dol_sanitizeFileName($object->ref);
    $upload_dir = $conf->timesheet->dir_output.'/'.get_exdir($object->id,2,0,0,$object,'ResourceActivity').$ref;
    
}


/*******************************************************************
* ACTIONS
*
* Put here all code to do according to value of "action" parameter
********************************************************************/

// Action to add record
$error=0;
if ($cancel){
        reloadpage($backtopage,$id,$ref);
}else if(($action == 'create') || ($action == 'edit' && ($id>0 || !empty($ref)))){
    $tms=time();
    $_SESSION['Timesheetactivity_'.$tms]=array();
    $_SESSION['Timesheetactivity_'.$tms]['action']=$action;
            
}else if (($action == 'add') || ($action == 'update' && ($id>0 || !empty($ref))))
{
        //block resubmit
        if(empty($tms) || (!isset($_SESSION['Timesheetactivity_'.$tms]))){
                setEventMessage('WrongTimeStamp_requestNotExpected', 'errors');
                $action=($action=='add')?'create':'edit';
        }
        //retrive the data
        		$object->date_start=dol_mktime(0, 0, 0,GETPOST('Datestartmonth'),GETPOST('Datestartday'),GETPOST('Datestartyear'));
		$object->date_end=dol_mktime(0, 0, 0,GETPOST('Dateendmonth'),GETPOST('Dateendday'),GETPOST('Dateendyear'));
		$object->time_start=GETPOST('Timestart');
		$object->time_end=GETPOST('Timeend');
		$object->weekdays=GETPOST('Weekdays');
		$object->redundancy=GETPOST('Redundancy');
		$object->timetype=GETPOST('Timetype');
		$object->status=GETPOST('Status');
		$object->priority=GETPOST('Priority');
		$object->note=GETPOST('Note');
		$object->userid=GETPOST('Userid');
		$object->element_id=GETPOST('Elementid');

        
        
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
     $action='list';
 }
 
 
  switch($action){		
                    case 'update':
                            $result=$object->update($user);
                            if ($result > 0)
                            {
                                // Creation OK
                                unset($_SESSION['Timesheetactivity_'.$tms]);
                                    setEventMessage('RecordUpdated','mesgs');
                                    reloadpage($backtopage,$object->id,$ref); 
                            }
                            else
                            {
                                    // Creation KO
                                    if (! empty($object->errors)) setEventMessages(null, $object->errors, 'errors');
                                    else setEventMessage('RecordNotUpdated', 'errors');
                                    $action='edit';
                            }
                    case 'delete':
                        if(isset($_GET['urlfile'])) $action='deletefile';
                    case 'view':
                    case 'viewinfo':
                    case 'viewdoc':
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
                                    $action='list';
                            }
                            break;
                    case 'add':
                            $result=$object->create($user);
                            if ($result > 0)
                            {
                                    // Creation OK
                                // remove the tms
                                   unset($_SESSION['Timesheetactivity_'.$tms]);
                                   setEventMessage('RecordSucessfullyCreated', 'mesgs');
                                   reloadpage($backtopage,$result,$ref);
                                    
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
                                    $action='list';
                                    
                            }
                            else
                            {
                                    // Delete NOK
                                    if (! empty($object->errors)) setEventMessages(null,$object->errors,'errors');
                                    else setEventMessage('RecordNotDeleted','errors');
                                    $action='list';
                            }
                         break;
                    case 'list':
                    case 'create':
                    default:
                        //document handling
                        if(version_compare(DOL_VERSION,"4.0")>=0){
                            include_once DOL_DOCUMENT_ROOT . '/core/actions_linkedfiles.inc.php';
                        }else{
                            include_once DOL_DOCUMENT_ROOT . '/core/tpl/document_actions_pre_headers.tpl.php';
                        }
                        if(!empty($_FILES)) $action='viewdoc';
                            break;
            }             
//Removing the tms array so the order can't be submitted two times
if(isset( $_SESSION['Timesheetactivity_class'][$tms]))
{
    unset($_SESSION['Timesheetactivity_class'][$tms]);
}

/***************************************************
* VIEW
*
* Put here all code to build page
****************************************************/

llxHeader('','Timesheetactivity','');
print "<div> <!-- module body-->";
$form=new Form($db);
$formother=new FormOther($db);

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
         $ret=$form->form_confirm($PHP_SELF.'?action=confirm_delete&id='.$id,$langs->trans('DeleteTimesheetactivity'),$langs->trans('ConfirmDelete'),'confirm_delete', '', 0, 1);
         if ($ret == 'html') print '<br />';
         //to have the object to be deleted in the background\
        }
    case 'view':
    {
        // tabs
        if($edit==0 && $new==0){ //show tabs
            $head=Timesheetactivity_prepare_head($object);
            dol_fiche_head($head,'card',$langs->trans('Timesheetactivity'),0,'timesheet@timesheet');            
        }else{
            print_fiche_titre($langs->trans('Timesheetactivity'));
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
            $basedurltab=explode("?", $PHP_SELF);
            $basedurl=$basedurltab[0].'?action=list';
            $linkback = '<a href="'.$basedurl.(! empty($socid)?'?socid='.$socid:'').'">'.$langs->trans("BackToList").'</a>';
            if(!isset($object->ref))//save ref if any
                $object->ref=$object->id;
            print $form->showrefnav($object, 'action=view&id', $linkback, 1, 'rowid', 'ref', '');
            //reloqd the ref

        }

	print '<table class="border centpercent">'."\n";

            
		print "<tr>\n";

// show the field date_start

		print '<td class="fieldrequired">'.$langs->trans('Datestart').' </td><td>';
		if($edit==1){
		if($new==1){
			print $form->select_date(-1,'Datestart');
		}else{
			print $form->select_date($object->date_start,'Datestart');
		}
		}else{
			print dol_print_date($object->date_start,'day');
		}
		print "</td>";
		print "\n</tr>\n";
		print "<tr>\n";

// show the field date_end

		print '<td class="fieldrequired">'.$langs->trans('Dateend').' </td><td>';
		if($edit==1){
		if($new==1){
			print $form->select_date(-1,'Dateend');
		}else{
			print $form->select_date($object->date_end,'Dateend');
		}
		}else{
			print dol_print_date($object->date_end,'day');
		}
		print "</td>";
		print "\n</tr>\n";
		print "<tr>\n";

// show the field time_start

		print '<td class="fieldrequired">'.$langs->trans('Timestart').' </td><td>';
		if($edit==1){
			print '<input type="text" value="'.$object->time_start.'" name="Timestart">';
		}else{
			print $object->time_start;
		}
		print "</td>";
		print "\n</tr>\n";
		print "<tr>\n";

// show the field time_end

		print '<td class="fieldrequired">'.$langs->trans('Timeend').' </td><td>';
		if($edit==1){
			print '<input type="text" value="'.$object->time_end.'" name="Timeend">';
		}else{
			print $object->time_end;
		}
		print "</td>";
		print "\n</tr>\n";
		print "<tr>\n";

// show the field weekdays

		print '<td>'.$langs->trans('Weekdays').' </td><td>';
                

                $labels=array($langs->trans("Monday"),$langs->trans("Tuesday"),$langs->trans("Wednesday"),$langs->trans("Thursday"),$langs->trans("Friday"),$langs->trans("Saturday"),$langs->trans("Sunday"));
                $names=array('day[1]','day[2]','day[3]','day[4]','day[5]','day[6]','day[7]');
                
                print '<input type="hidden" name="day[0]" value="_">';
                print     printBitStringHTML($bitstring,$labels,$names,$edit);
		print "</td>";
		print "\n</tr>\n";
		print "<tr>\n";

// show the field redundancy

		print '<td>'.$langs->trans('Redundancy').' </td><td>';
		if($edit==1){
		if ($new==1)
			print '<input type="text" value="1" name="Redundancy">';
		else
				print '<input type="text" value="'.$object->redundancy.'" name="Redundancy">';
		}else{
			print $object->redundancy;
		}
		print "</td>";
		print "\n</tr>\n";
		print "<tr>\n";

// show the field timetype

		print '<td>'.$langs->trans('Timetype').' </td><td>';
		if($edit==1){
		if ($new==1)
			print '<input type="text" value="1" name="Timetype">';
		else
				print '<input type="text" value="'.$object->timetype.'" name="Timetype">';
		}else{
			print $object->timetype;
		}
		print "</td>";
		print "\n</tr>\n";
		print "<tr>\n";

// show the field status

		print '<td>'.$langs->trans('Status').' </td><td>';
		if($edit==1){
		if ($new==1)
			print '<input type="text" value="1" name="Status">';
		else
				print '<input type="text" value="'.$object->status.'" name="Status">';
		}else{
			print $object->status;
		}
		print "</td>";
		print "\n</tr>\n";
		print "<tr>\n";

// show the field priority

		print '<td>'.$langs->trans('Priority').' </td><td>';
		if($edit==1){
		if ($new==1)
			print '<input type="text" value="1" name="Priority">';
		else
				print '<input type="text" value="'.$object->priority.'" name="Priority">';
		}else{
			print $object->priority;
		}
		print "</td>";
		print "\n</tr>\n";
		print "<tr>\n";

// show the field note

		print '<td>'.$langs->trans('Note').' </td><td>';
		if($edit==1){
			print '<input type="text" value="'.$object->note.'" name="Note">';
		}else{
			print $object->note;
		}
		print "</td>";
		print "\n</tr>\n";
		print "<tr>\n";

// show the field userid

		print '<td class="fieldrequired">'.$langs->trans('Userid').' </td><td>';
		if($edit==1){
		print $form->select_dolusers($object->userid, 'Userid', 1, '', 0 );
		}else{
		print print_generic('user', 'rowid',$object->userid,'lastname','firstname',' ');
		}
		print "</td>";
		print "\n</tr>\n";
		print "<tr>\n";

// show the field element_id

		print '<td>'.$langs->trans('Elementid').' </td><td>';
		if($edit==1){
		if ($new==1)
			print '<input type="text" value="1" name="Priority">';
		else
				print '<input type="text" value="'.$object->element_id.'" name="Priority">';
		}else{
			print $object->element_id;
		}
		print "</td>";
		print "\n</tr>\n";

            

	print '</table>'."\n";
	print '<br>';
	print '<div class="center">';
        if($edit==1){
        if($new==1){
                print '<input type="submit" class="button" name="add" value="'.$langs->trans('Add').'">';
            }else{
                print '<input type="submit" name="update" value="'.$langs->trans('Update').'" class="button">';
            }
            print ' &nbsp; <input type="submit" class="button" name="cancel" value="'.$langs->trans('Cancel').'"></div>';
            print '</form>';
        }else{
            $parameters=array();
            $reshook=$hookmanager->executeHooks('addMoreActionsButtons',$parameters,$object,$action);    // Note that $action and $object may have been modified by hook
            if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

            if (empty($reshook))
            {
                print '<div class="tabsAction">';

                // Boutons d'actions
                //if($user->rights->Timesheetactivity->edit)
                //{
                    print '<a href="'.$PHP_SELF.'?id='.$id.'&action=edit" class="butAction">'.$langs->trans('Update').'</a>';
                //}
                
                //if ($user->rights->Timesheetactivity->delete)
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
        print_fiche_titre($langs->trans('Timesheetactivity'));
        $head=Timesheetactivity_prepare_head($object);
        dol_fiche_head($head,'info',$langs->trans("Timesheetactivity"),0,'timesheet@timesheet');            
        print '<table width="100%"><tr><td>';
        dol_print_object_info($object);
        print '</td></tr></table>';
        print '</div>';
        break;
    case 'deletefile':
        $action='delete';
    case 'viewdoc':
        print_fiche_titre($langs->trans('Timesheetactivity'));
        if (! $sortfield) $sortfield='name';
	$object->fetch_thirdparty();

        $head=Timesheetactivity_prepare_head($object);
        dol_fiche_head($head,'documents',$langs->trans("Timesheetactivity"),0,'timesheet@timesheet');            
        $filearray=dol_dir_list($upload_dir,'files',0,'','\.meta$',$sortfield,(strtolower($sortorder)=='desc'?SORT_DESC:SORT_ASC),1);
	$totalsize=0;
	foreach($filearray as $key => $file)
	{
		$totalsize+=$file['size'];
	}
        print '<table class="border" width="100%">';
        $linkback = '<a href="'.$PHP_SELF.(! empty($socid)?'?socid='.$socid:'').'">'.$langs->trans("BackToList").'</a>';
  	// Ref
  	print '<tr><td width="30%">'.$langs->trans("Ref").'</td><td>';
  	print $form->showrefnav($object, 'id', $linkback, 1, 'rowid', 'ref', '');
  	print '</td></tr>';
	// Societe
	//print "<tr><td>".$langs->trans("Company")."</td><td>".$object->client->getNomUrl(1)."</td></tr>";
        print '<tr><td>'.$langs->trans("NbOfAttachedFiles").'</td><td colspan="3">'.count($filearray).'</td></tr>';
        print '<tr><td>'.$langs->trans("TotalSizeOfAttachedFiles").'</td><td colspan="3">'.$totalsize.' '.$langs->trans("bytes").'</td></tr>';
        print '</table>';

        print '</div>';

        $modulepart = 'timesheet';
        $permission = $user->rights->timesheet->add;
        $param = '&id='.$object->id;
        
        include_once DOL_DOCUMENT_ROOT . '/core/tpl/document_actions_post_headers.tpl.php';

        
        break;
    case 'delete':
        if( ($id>0 || $ref!='')){
         $ret=$form->form_confirm($PHP_SELF.'?action=confirm_delete&id='.$id,$langs->trans('DeleteTimesheetactivity'),$langs->trans('ConfirmDelete'),'confirm_delete', '', 0, 1);
         if ($ret == 'html') print '<br />';
         //to have the object to be deleted in the background        
        }
    case 'list':
    default:
        {
    $sql = 'SELECT';
    $sql.= ' t.rowid,';
    
		$sql.=' t.date_start,';
		$sql.=' t.date_end,';
		$sql.=' t.time_start,';
		$sql.=' t.time_end,';
		$sql.=' t.weekdays,';
		$sql.=' t.redundancy,';
		$sql.=' t.timetype,';
		$sql.=' t.status,';
		$sql.=' t.priority,';
		$sql.=' t.note,';
		$sql.=' t.fk_userid,';
		$sql.=' t.fk_element_id';

    
    $sql.= ' FROM '.MAIN_DB_PREFIX.'timesheet_activity as t';
    $sqlwhere='';
    if(isset($object->entity))
        $sqlwhere.= ' AND t.entity = '.$conf->entity;
    if ($filter && $filter != -1)		// GETPOST('filtre') may be a string
    {
            $filtrearr = explode(',', $filter);
            foreach ($filtrearr as $fil)
            {
                    $filt = explode(':', $fil);
                    $sqlwhere .= ' AND ' . $filt[0] . ' = ' . $filt[1];
            }
    }
    //pass the search criteria
    	if($ls_date_start_month)$sqlwhere .= ' AND MONTH(t.date_start)="'.$ls_date_start_month.'"';
	if($ls_date_start_year)$sqlwhere .= ' AND YEAR(t.date_start)="'.$ls_date_start_year.'"';
	if($ls_date_end_month)$sqlwhere .= ' AND MONTH(t.date_end)="'.$ls_date_end_month.'"';
	if($ls_date_end_year)$sqlwhere .= ' AND YEAR(t.date_end)="'.$ls_date_end_year.'"';
	if($ls_time_start) $sqlwhere .= natural_search(array('t.time_start'), $ls_time_start);
	if($ls_time_end) $sqlwhere .= natural_search(array('t.time_end'), $ls_time_end);
	if($ls_weekdays) $sqlwhere .= natural_search(array('t.weekdays'), $ls_weekdays);
	if($ls_redundancy) $sqlwhere .= natural_search(array('t.redundancy'), $ls_redundancy);
	if($ls_timetype) $sqlwhere .= natural_search(array('t.timetype'), $ls_timetype);
	if($ls_status) $sqlwhere .= natural_search(array('t.status'), $ls_status);
	if($ls_priority) $sqlwhere .= natural_search(array('t.priority'), $ls_priority);
	if($ls_note) $sqlwhere .= natural_search('t.note', $ls_note);
	if($ls_userid) $sqlwhere .= natural_search(array('t.fk_userid'), $ls_userid);
	if($ls_element_id) $sqlwhere .= natural_search(array('t.fk_element_id'), $ls_element_id);

    
    //list limit
    if(!empty($sqlwhere))
        $sql.=' WHERE '.substr ($sqlwhere, 5);
    
// Count total nb of records
$nbtotalofrecords = 0;
if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST))
{
        $sqlcount='SELECT COUNT(*) as count FROM '.MAIN_DB_PREFIX.'timesheet_activity as t';
        if(!empty($sqlwhere))
            $sqlcount.=' WHERE '.substr ($sqlwhere, 5);
	$result = $db->query($sqlcount);
        $nbtotalofrecords = ($result)?$objcount = $db->fetch_object($result)->count:0;
}
    if(!empty($sortfield)){$sql.= $db->order($sortfield,$sortorder);
    }else{ $sortorder = 'ASC';}
    
    if (!empty($limit))
    {
            $sql.= $db->plimit($limit+1, $offset); 
    }
    

    //execute SQL
    dol_syslog($script_file, LOG_DEBUG);
    $resql=$db->query($sql);
    if ($resql)
    {
        	if (!empty($ls_date_start_month))	$param.='&ls_date_start_month='.urlencode($ls_date_start_month);
	if (!empty($ls_date_start_year))	$param.='&ls_date_start_year='.urlencode($ls_date_start_year);
	if (!empty($ls_date_end_month))	$param.='&ls_date_end_month='.urlencode($ls_date_end_month);
	if (!empty($ls_date_end_year))	$param.='&ls_date_end_year='.urlencode($ls_date_end_year);
	if (!empty($ls_time_start))	$param.='&ls_time_start='.urlencode($ls_time_start);
	if (!empty($ls_time_end))	$param.='&ls_time_end='.urlencode($ls_time_end);
	if (!empty($ls_weekdays))	$param.='&ls_weekdays='.urlencode($ls_weekdays);
	if (!empty($ls_redundancy))	$param.='&ls_redundancy='.urlencode($ls_redundancy);
	if (!empty($ls_timetype))	$param.='&ls_timetype='.urlencode($ls_timetype);
	if (!empty($ls_status))	$param.='&ls_status='.urlencode($ls_status);
	if (!empty($ls_priority))	$param.='&ls_priority='.urlencode($ls_priority);
	if (!empty($ls_note))	$param.='&ls_note='.urlencode($ls_note);
	if (!empty($ls_userid))	$param.='&ls_userid='.urlencode($ls_userid);
	if (!empty($ls_element_id))	$param.='&ls_element_id='.urlencode($ls_element_id);

        
        if ($filter && $filter != -1) $param.='&filtre='.urlencode($filter);
        
        $num = $db->num_rows($resql);
        //print_barre_liste function defined in /core/lib/function.lib.php, possible to add a picto
        print_barre_liste($langs->trans("Timesheetactivity"),$page,$PHP_SELF,$param,$sortfield,$sortorder,'',$num,$nbtotalofrecords);
        print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
        print '<table class="liste" width="100%">'."\n";
        //TITLE
        print '<tr class="liste_titre">';
        	print_liste_field_titre($langs->trans('Datestart'),$PHP_SELF,'t.date_start','',$param,'',$sortfield,$sortorder);
	print "\n";
	print_liste_field_titre($langs->trans('Dateend'),$PHP_SELF,'t.date_end','',$param,'',$sortfield,$sortorder);
	print "\n";
	print_liste_field_titre($langs->trans('Timestart'),$PHP_SELF,'t.time_start','',$param,'',$sortfield,$sortorder);
	print "\n";
	print_liste_field_titre($langs->trans('Timeend'),$PHP_SELF,'t.time_end','',$param,'',$sortfield,$sortorder);
	print "\n";
	print_liste_field_titre($langs->trans('Weekdays'),$PHP_SELF,'t.weekdays','',$param,'',$sortfield,$sortorder);
	print "\n";
	print_liste_field_titre($langs->trans('Redundancy'),$PHP_SELF,'t.redundancy','',$param,'',$sortfield,$sortorder);
	print "\n";
	print_liste_field_titre($langs->trans('Timetype'),$PHP_SELF,'t.timetype','',$param,'',$sortfield,$sortorder);
	print "\n";
	print_liste_field_titre($langs->trans('Status'),$PHP_SELF,'t.status','',$param,'',$sortfield,$sortorder);
	print "\n";
	print_liste_field_titre($langs->trans('Priority'),$PHP_SELF,'t.priority','',$param,'',$sortfield,$sortorder);
	print "\n";
	print_liste_field_titre($langs->trans('Note'),$PHP_SELF,'t.note','',$param,'',$sortfield,$sortorder);
	print "\n";
	print_liste_field_titre($langs->trans('Userid'),$PHP_SELF,'t.fk_userid','',$param,'',$sortfield,$sortorder);
	print "\n";
	print_liste_field_titre($langs->trans('Elementid'),$PHP_SELF,'t.fk_element_id','',$param,'',$sortfield,$sortorder);
	print "\n";

        
        print '</tr>';
        //SEARCH FIELDS
        print '<tr class="liste_titre">'; 
        //Search field fordate_start
	print '<td class="liste_titre" colspan="1" >';
	print '<input class="flat" type="text" size="1" maxlength="2" name="date_start_month" value="'.$ls_date_start_month.'">';
	$syear = $ls_date_start_year;
	$formother->select_year($syear?$syear:-1,'ls_date_start_year',1, 20, 5);
	print '</td>';
//Search field fordate_end
	print '<td class="liste_titre" colspan="1" >';
	print '<input class="flat" type="text" size="1" maxlength="2" name="date_end_month" value="'.$ls_date_end_month.'">';
	$syear = $ls_date_end_year;
	$formother->select_year($syear?$syear:-1,'ls_date_end_year',1, 20, 5);
	print '</td>';
//Search field fortime_start
	print '<td class="liste_titre" colspan="1" >';
	print '<input class="flat" size="16" type="text" name="ls_time_start" value="'.$ls_time_start.'">';
	print '</td>';
//Search field fortime_end
	print '<td class="liste_titre" colspan="1" >';
	print '<input class="flat" size="16" type="text" name="ls_time_end" value="'.$ls_time_end.'">';
	print '</td>';
//Search field forweekdays
	print '<td class="liste_titre" colspan="1" >';
	print '<input class="flat" size="16" type="text" name="ls_weekdays" value="'.$ls_weekdays.'">';
	print '</td>';
//Search field forredundancy
	print '<td class="liste_titre" colspan="1" >';
	print '<input class="flat" size="16" type="text" name="ls_redundancy" value="'.$ls_redundancy.'">';
	print '</td>';
//Search field fortimetype
	print '<td class="liste_titre" colspan="1" >';
	print '<input class="flat" size="16" type="text" name="ls_timetype" value="'.$ls_timetype.'">';
	print '</td>';
//Search field forstatus
	print '<td class="liste_titre" colspan="1" >';
	print '<input class="flat" size="16" type="text" name="ls_status" value="'.$ls_status.'">';
	print '</td>';
//Search field forpriority
	print '<td class="liste_titre" colspan="1" >';
	print '<input class="flat" size="16" type="text" name="ls_priority" value="'.$ls_priority.'">';
	print '</td>';
//Search field fornote
	print '<td class="liste_titre" colspan="1" >';
	print '<input class="flat" size="16" type="text" name="ls_note" value="'.$ls_note.'">';
	print '</td>';
//Search field foruserid
	print '<td class="liste_titre" colspan="1" >';
		print select_generic('user','rowid','ls_userid','lastname','firstname',$ls_userid);
	print '</td>';
//Search field forelement_id
	print '<td class="liste_titre" colspan="1" >';
	print '<input class="flat" size="16" type="text" name="ls_element_id" value="'.$ls_priority.'">';
	print '</td>';

        
        
        print '<td width="15px">';
        print '<input type="image" class="liste_titre" name="search" src="'.img_picto($langs->trans("Search"),'search.png','','',1).'" value="'.dol_escape_htmltag($langs->trans("Search")).'" title="'.dol_escape_htmltag($langs->trans("Search")).'">';
        print '<input type="image" class="liste_titre" name="removefilter" src="'.img_picto($langs->trans("Search"),'searchclear.png','','',1).'" value="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'" title="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'">';
        print '</td>';
        print '</tr>'."\n"; 
        $i=0;
        $basedurltab=explode("?", $PHP_SELF);
        $basedurl=$basedurltab[0].'?action=view&id=';
        while ($i < $num && $i<$limit)
        {
            $obj = $db->fetch_object($resql);
            if ($obj)
            {
                // You can use here results
                		print "<tr class=\"".(($i%2==0)?'pair':'impair')."\"  onclick=\"location.href='";
	print $basedurl.$obj->rowid."'\" >";
		print "<td>".dol_print_date($obj->date_start,'day')."</td>";
		print "<td>".dol_print_date($obj->date_end,'day')."</td>";
		print "<td>".$obj->time_start."</td>";
		print "<td>".$obj->time_end."</td>";
		print "<td>".$obj->weekdays."</td>";
		print "<td>".$obj->redundancy."</td>";
		print "<td>".$obj->timetype."</td>";
		print "<td>".$obj->status."</td>";
		print "<td>".$obj->priority."</td>";
		print "<td>".$obj->note."</td>";
		print "<td>".print_generic('user','rowid',$obj->fk_userid,'lastname','firstname',' ')."</td>";
		print "<td>".print_generic('element_id','rowid',$obj->fk_element_id,'rowid','description')."</td>";
		print '<td><a href="'.$PHP_SELF.'?action=delete&id='.$obj->rowid.'">'.img_delete().'</a></td>';
		print "</tr>";

                

            }
            $i++;
        }
    }
    else
    {
        $error++;
        dol_print_error($db);
    }

    print '</table>'."\n";
    print '</from>'."\n";
    // new button
    print '<a href="?action=create" class="button" role="button">'.$langs->trans('New');
    print ' '.$langs->trans('Timesheetactivity')."</a>\n";

    
}
        break;
}
dol_fiche_end();

function reloadpage($backtopage,$id,$ref){
        if (!empty($backtopage)){
            header("Location: ".$backtopage);            
        }else if (!empty($ref) ){
            header("Location: ".$_SERVER["PHP_SELF"].'?action=view&ref='.$id);
        }else if ($id>0)
        {
            header("Location: ".$_SERVER["PHP_SELF"].'?action=view&id='.$id);
        }else{
            header("Location: ".$_SERVER["PHP_SELF"].'?action=list');

        }
    exit();
}
function Timesheetactivity_prepare_head($object)
{
    global $langs, $conf, $user;
    $h = 0;
    $head = array();

    $head[$h][0] = $_SERVER["PHP_SELF"].'?action=view&id='.$object->id;
    $head[$h][1] = $langs->trans("Card");
    $head[$h][2] = 'card';
    $h++;

    // Show more tabs from modules
    // Entries must be declared in modules descriptor with line
    // $this->tabs = array('entity:+tabname:Title:@timesheet:/timesheet/mypage.php?id=__ID__');   to add new tab
    // $this->tabs = array('entity:-tabname);   												to remove a tab
    complete_head_from_modules($conf,$langs,$object,$head,$h,'timesheet');
    complete_head_from_modules($conf,$langs,$object,$head,$h,'timesheet','remove');
    $head[$h][0] = $_SERVER["PHP_SELF"].'?action=viewdoc&id='.$object->id;
    $head[$h][1] = $langs->trans("Documents");
    $head[$h][2] = 'documents';
    $h++;
    
    $head[$h][0] = $_SERVER["PHP_SELF"].'?action=viewinfo&id='.$object->id;
    $head[$h][1] = $langs->trans("Info");
    $head[$h][2] = 'info';
    $h++;

    return $head;
}
// End of page
llxFooter();
$db->close();
