<?php
/* Copyright (C) 2015 Patrick Delcoix  <delcroip@gmail.com>
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

/**
 *   	\file       dev/skeletons/skeleton_page.php
 *		\ingroup    timesheet othermodule1 othermodule2
 *		\brief      This file is an example of a php page
 *					Initialy built by build_class_from_table on 2015-08-01 08:59
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
include 'core/lib/includeMain.lib.php';;
// Change this following line to use the correct relative path from htdocs
require_once 'class/timesheetwhitelist.class.php';
require_once 'core/lib/timesheet.lib.php';
require_once 'core/lib/generic.lib.php'; 
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
//document handling
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
//dol_include_once('/core/lib/images.lib.php');
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
// include conditionnally of the dolibarr version
//if((version_compare(DOL_VERSION, "3.8", "<"))){
global $db;
//}
include_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
$PHP_SELF=$_SERVER['PHP_SELF'];
// Load traductions files requiredby by page
//$langs->load("companies");
$langs->load("Timesheetwhitelist_class");

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
    	$ls_user= GETPOST('ls_user','int');
	if($ls_user==-1)$ls_user='';
	$ls_project= GETPOST('ls_project','int');
	if($ls_project==-1)$ls_project='';
	$ls_project_task= GETPOST('ls_project_task','int');
	if($ls_project_task==-1)$ls_project_task='';
	$ls_subtask= GETPOST('ls_subtask','int');
	$ls_date_start_month= GETPOST('ls_date_start_month','int');
	$ls_date_start_year= GETPOST('ls_date_start_year','int');
	$ls_date_end_month= GETPOST('ls_date_end_month','int');
	$ls_date_end_year= GETPOST('ls_date_end_year','int');

    
}


$page = GETPOST('page','int'); //FIXME, need to use for all the list
if ($page == -1) { $page = 0; }
$limit=$conf->liste_limit;
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;


//$upload_dir = $conf->timesheet->dir_output.'/Timesheetwhitelist/'.dol_sanitizeFileName($object->ref);


 // uncomment to avoid resubmision
//if(isset( $_SESSION['Timesheetwhitelist_class'][$tms]))
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
$object=new Timesheetwhitelist($db);
if($id>0)
{
    $object->id=$id; 
}
if(!empty($ref))
{
    $object->ref=$ref; 
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
    if($tms=='') //to keep the tms on javvascript reload
    {
        $tms=time();
        $_SESSION['Timesheetwhitelist_'.$tms]=array();
        $_SESSION['Timesheetwhitelist_'.$tms]['action']=$action;
    }else {
        $editedUser=GETPOST('User');
        $editedProject=GETPOST('Project');		
        
    }
    
}else if (($action == 'add') || ($action == 'update' && ($id>0 || !empty($ref))))
{
        //block resubmit
        if(empty($tms) || (!isset($_SESSION['Timesheetwhitelist_'.$tms]))){
                setEventMessage('WrongTimeStamp_requestNotExpected', 'errors');
                $action=($action=='add')?'create':'edit';
        }
        //retrive the data
             $object->user=($user->admin)?GETPOST('User'):$user->id;
            $object->project=GETPOST('Project');
            if($object->project==-1)$object->project='';
            $object->project_task=GETPOST('Projecttask');
            if($object->project_task==-1)$object->project_task='';
            $object->subtask=GETPOST('Subtask');
            $object->date_start=dol_mktime(0, 0, 0,GETPOST('Datestartmonth'),GETPOST('Datestartday'),GETPOST('Datestartyear'));
            $object->date_end=dol_mktime(0, 0, 0,GETPOST('Dateendmonth'),GETPOST('Dateendday'),GETPOST('Dateendyear'));

        
        
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
                            
                            $result=($user->admin || ($user->id==$object->user))?$object->update($user):-1;
                            if ($result > 0)
                            {
                                // Creation OK
                                unset($_SESSION['Timesheetwhitelist_'.$tms]);
                                    setEventMessage('RecordUpdated', 'mesgs');
                                    reloadpage($backtopage,$object->id,$ref); 
                            }
                            else
                            {
                                    // Creation KO
                                    if (! empty($object->errors)) setEventMessage( $object->errors, 'errors');
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
                                    // only admin can check the whitelist of other
                                    if(!$user->admin && $user->id != $object->user){
                                        setEventMessage( $langs->trans('notYourWhitelist').' id:'.$id,'errors');
                                        reloadpage();
                                    }
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
                                    reloadpage(null,$id,$ref);
                            }
                            break;
                    case 'add':
                            $result=$object->create($user);
                            if ($result > 0)
                            {
                                    // Creation OK
                                // remove the tms
                                   unset($_SESSION['Timesheetwhitelist_'.$tms]);
                                   setEventMessage('RecordSucessfullyCreated', 'mesgs');
                                   reloadpage($backtopage,$result,$ref);
                                    
                            }else
                            {
                                    // Creation KO
                                    if (! empty($object->errors)) setEventMessage( $object->errors, 'errors');
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
                                    reloadpage();
                                    
                            }
                            else
                            {
                                    // Delete NOK
                                    if (! empty($object->errors)) setEventMessage($object->errors,'errors');
                                    else setEventMessage('RecordNotDeleted','errors');
                                    reloadpage(null,$id,$ref);
                            }
                         break;
                    case 'list':
                    case 'create':
                    default:
                        //document handling
                        include_once DOL_DOCUMENT_ROOT . '/core/tpl/document_actions_pre_headers.tpl.php';
                        if(isset($_GET['urlfile'])) $action='viewdoc';
                            break;
            }             
//Removing the tms array so the order can't be submitted two times
if(isset( $_SESSION['Timesheetwhitelist_class'][$tms]))
{
    unset($_SESSION['Timesheetwhitelist_class'][$tms]);
}

/***************************************************
* VIEW
*
* Put here all code to build page
****************************************************/

llxHeader('','Timesheetwhitelist','');
print "<div> <!-- module body-->";
$form=new Form($db);
$formother=new FormOther($db);

// Put here content of your page
//javascript to reload the page with the poject selected
print '
<SCRIPT type="text/javascript">
function reload(form){
var param_array = window.location.href.split(\'?\')[1].split(\'&\');
var index;
var id="";
var action="create";
for(index = 0; index < param_array.length; ++index){
    x = param_array[index].split(\'=\');
    if(x[0]=="action"){
        action= x[1];
    }
    if(x[0]=="id"){
        id= "&id="+x[1];
    }
}

var pjt=document.getElementById("Project").value;
var usr=document.getElementById("User").value;
self.location="'.$PHP_SELF.'?action=" + action + id +"&tms='.$tms.'&User=" +usr+ "&Project=" + pjt ;
}
</script>';

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
         $ret=$form->form_confirm($PHP_SELF.'?action=confirm_delete&id='.$id,$langs->trans('DeleteTimesheetwhitelist'),$langs->trans('ConfirmDeleteTimesheetwhitelist'),'confirm_delete', '', 0, 1);
         if ($ret == 'html') print '<br />';
         //to have the object to be deleted in the background\
        }
    case 'view':
    {
        // to avoid showing the whitelist of other
       // if(!$user->admin)$object->next_prev_filter=' fk_user="'.$user->id.'"';
 //       

        // tabs
        if($edit==0 && $new==0){ //show tabs
            $head=Timesheetwhitelist_prepare_head($object);
            dol_fiche_head($head,'card',$langs->trans('Timesheetwhitelist'),0,'timesheet@timesheet');            
        }else{
            print_fiche_titre($langs->trans('Timesheetwhitelist'));
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
            print $form->showrefnav($object, 'action=view&id', $linkback, 1, 'rowid', 'rowid', '');
            //reloqd the ref

        }

	print '<table class="border centpercent">'."\n";

            
		print "<tr>";

// show the field user
               
            print '<td class="fieldrequired" width="200px">'.$langs->trans('User').' </td><td>';
            if($edit==1){
                    if(!empty($editedUser))$object->user=$editedUser;
                    else if ($new==1) $object->user=$user->id;
                    print $form->select_dolusers($object->user, 'User', 1, '', !$user->admin );

            }else{
            print print_generic($db,'user', 'rowid',$object->user,'lastname','firstname',' ');
            }
            print "</td>";
            print "</tr>\n";
                print "<tr>";
// show the field project

		print '<td class="fieldrequired">'.$langs->trans('Project').' </td><td>';
		if($edit==1){
                    if(!empty($editedProject))$object->project=$editedProject;
                $formUserWhere=' WHERE (t.datee>='.$object->db->idate(time()).' OR t.datee IS NULL)';
 //               $formUserWhere.=' AND (projet.dateo<=FROM_UNIXTIME("'.time().'") OR prj.dateo IS NULL)';
                if(!$user->admin)
                {
                        $formUserJoin=' RIGHT JOIN '.MAIN_DB_PREFIX.'element_contact  as ec ON t.rowid=ec.element_id';
                        $formUserWhere.=" AND (((fk_c_type_contact='161' OR fk_c_type_contact='160') AND ec.fk_socpeople='".$user->id."' )";
                        $formUserWhere.=" OR (t.public='1') )";

                }
                $formUser.=$formUserJoin.$formUserWhere;
		print select_generic('projet','rowid','Project','ref','title',$object->project,' - ',$formUser,'onchange="reload(this.form)"');
		}else{
		print print_generic('projet','rowid',$object->project,'ref','title');
		}
		print "</td>";
		print "</tr>\n";
		print "<tr>";

// show the field project_task

		print '<td>'.$langs->trans('Task').' </td><td>';
		if($edit==1){
                    if($object->project) $formProject=' WHERE fk_projet="'.$object->project.'"';
                  //if (isset($formProject)){  
                        print select_generic('projet_task','rowid','Projecttask','ref','label',$object->project_task,' - ',(isset($formProject)?$formProject:' WHERE 1=2'));
                  //}else{
                  //      print '<select class="flat minwidth200" id="Projecttask" name="Projecttask"></select>';
                  //}
                    
                }else{
		print print_generic('projet_task','rowid',$object->project_task,'ref','label');
		}
		print "</td>";
		print "</tr>\n";
                print "<tr>";

// show the field subtask

		print '<td>'.$langs->trans('Subtask').' </td><td>';
		if($edit==1){
			print ' <input type="checkbox" value="1" name="Subtask" '.($object->subtask?'checked':'').'>';
		}else{
                        print '<input type="checkbox" '.($object->subtask?'checked':'').' onclick="return false" readonly>';
		}
		print "</td>";
		print "</tr>\n";
		print "<tr>";

// show the field date_start

		print '<td>'.$langs->trans('DateStart').' </td><td>';
		if($edit==1){
		if($new==1){
			print $form->select_date(-1,'Datestart');
		}else{
                                                    if($object->date_start=='')
                                                            $object->date_start=-1;
			print $form->select_date($object->date_start,'Datestart');
		}
		}else{
			print dol_print_date($object->date_start,'day');
		}
		print "</td>";
		print "</tr>\n";

// show the field date_end
                print "<tr>";

		print '<td>'.$langs->trans('DateEnd').' </td><td>';
		if($edit==1){
		if($new==1){
			print $form->select_date(-1,'Dateend');
		}else{
                                                    if($object->date_end=='')
                                                            $object->date_end=-1;
			print $form->select_date($object->date_end,'Dateend');
		}
		}else{
			print dol_print_date($object->date_end,'day');
		}
		print "</td>";
		print "</tr>\n";

            

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

            if (empty($reshook) && ($user->admin || $user->id == $object->user))
            {
                print '<div class="tabsAction">';

                // Boutons d'actions
                //if($user->rights->Timesheetwhitelist->edit)
                //{
                    print '<a href="'.$PHP_SELF.'?id='.$id.'&action=edit" class="butAction">'.$langs->trans('Update').'</a>';
                //}
                
                //if ($user->rights->Timesheetwhitelist->delete)
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
  /*
        case 'viewinfo':
        print_fiche_titre($langs->trans('Timesheetwhitelist'));
        $head=Timesheetwhitelist_prepare_head($object);
        dol_fiche_head($head,'info',$langs->trans("Timesheetwhitelist"),0,'timesheet@timesheet');            
        print '<table width="100%"><tr><td>';
        dol_print_object_info($object);
        print '</td></tr></table>';
        print '</div>';
        break;
    case 'deletefile':
        $action='delete';
    case 'viewdoc':
        print_fiche_titre($langs->trans('Timesheetwhitelist'));
        if (! $sortfield) $sortfield='name';
	$object->fetch_thirdparty();

        $head=Timesheetwhitelist_prepare_head($object);
        dol_fiche_head($head,'documents',$langs->trans("Timesheetwhitelist"),0,'timesheet@timesheet');            
        
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
*/
        
        break;
    case 'delete':
        if( ($id>0 || $ref!='')){
         $ret=$form->form_confirm($PHP_SELF.'?action=confirm_delete&id='.$id,$langs->trans('DeleteTimesheetwhitelist'),$langs->trans('ConfirmDeleteTimesheetwhitelist'),'confirm_delete', '', 0, 1);
         if ($ret == 'html') print '<br />';
         //to have the object to be deleted in the background        
        }
    case 'list':
    default:
        {
    $sql = 'SELECT';
    $sql.= ' t.rowid,';
    
		$sql.=' t.fk_user,';
		$sql.=' t.fk_project,';
		$sql.=' t.fk_project_task,';
		$sql.=' t.subtask,';
		$sql.=' t.date_start,';
		$sql.=' t.date_end';

    
    $sql.= ' FROM '.MAIN_DB_PREFIX.'timesheet_whitelist as t';
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
    	if($ls_user){
            $sqlwhere .= natural_search(array('t.fk_user'), $ls_user);
        }else if (!$user->admin){
            $sqlwhere .= ' AND t.fk_user="'.$user->id.'"';
        }
	if($ls_project) $sqlwhere .= natural_search(array('t.fk_project'), $ls_project);
	if($ls_project_task) $sqlwhere .= natural_search(array('t.fk_project_task'), $ls_project_task);
	if($ls_subtask) $sqlwhere .= natural_search(array('t.subtask'), $ls_subtask);
	if($ls_date_start_month)$sqlwhere .= ' AND MONTH(t.date_start)="'.$ls_date_start_month.'"';
	if($ls_date_start_year)$sqlwhere .= ' AND YEAR(t.date_start)="'.$ls_date_start_year.'"';
	if($ls_date_end_month)$sqlwhere .= ' AND MONTH(t.date_end)="'.$ls_date_end_month.'"';
	if($ls_date_end_year)$sqlwhere .= ' AND YEAR(t.date_end)="'.$ls_date_end_year.'"';

    
    //list limit
    if(!empty($sqlwhere))
        $sql.=' WHERE '.substr ($sqlwhere, 5);
    
// Count total nb of records
$nbtotalofrecords = 0;
if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST))
{
        $sqlcount='SELECT COUNT(*) as count FROM '.MAIN_DB_PREFIX.'timesheet_whitelist as t';
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
        	if (!empty($ls_user))	$param.='&ls_user='.urlencode($ls_user);
	if (!empty($ls_project))	$param.='&ls_project='.urlencode($ls_project);
	if (!empty($ls_project_task))	$param.='&ls_project_task='.urlencode($ls_project_task);
	if (!empty($ls_subtask))	$param.='&ls_subtask='.urlencode($ls_subtask);
	if (!empty($ls_date_start_month))	$param.='&ls_date_start_month='.urlencode($ls_date_start_month);
	if (!empty($ls_date_start_year))	$param.='&ls_date_start_year='.urlencode($ls_date_start_year);
	if (!empty($ls_date_end_month))	$param.='&ls_date_end_month='.urlencode($ls_date_end_month);
	if (!empty($ls_date_end_year))	$param.='&ls_date_end_year='.urlencode($ls_date_end_year);

        
        if ($filter && $filter != -1) $param.='&filtre='.urlencode($filter);
        
        $num = $db->num_rows($resql);
        //print_barre_liste function defined in /core/lib/function.lib.php, possible to add a picto
        print_barre_liste($langs->trans("Timesheetwhitelist"),$page,$PHP_SELF,$param,$sortfield,$sortorder,'',$num,$nbtotalofrecords);
        print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
        print '<table class="liste" width="100%">'."\n";
        //TITLE
        print '<tr class="liste_titre">';
        if($user->admin)print_liste_field_titre($langs->trans('User'),$PHP_SELF,'t.fk_user','',$param,'',$sortfield,$sortorder);
	print "\n";
	print_liste_field_titre($langs->trans('Project'),$PHP_SELF,'t.fk_project','',$param,'',$sortfield,$sortorder);
	print "\n";
	print_liste_field_titre($langs->trans('Task'),$PHP_SELF,'t.fk_project_task','',$param,'',$sortfield,$sortorder);
	print "\n";
	print_liste_field_titre($langs->trans('Subtask'),$PHP_SELF,'t.subtask','',$param,'',$sortfield,$sortorder);
	print "\n";
	print_liste_field_titre($langs->trans('DateStart'),$PHP_SELF,'t.date_start','',$param,'',$sortfield,$sortorder);
	print "\n";
	print_liste_field_titre($langs->trans('DateEnd'),$PHP_SELF,'t.date_end','',$param,'',$sortfield,$sortorder);
	print "\n";

        
        print '</tr>';
        //SEARCH FIELDS
        print '<tr class="liste_titre">'; 
        //Search field foruser
	if($user->admin){
            print '<td class="liste_titre" colspan="1" >';
            print select_generic('user','rowid','ls_user','lastname','firstname',$ls_user);
            print '</td>';
        }
//Search field forproject
	print '<td class="liste_titre" colspan="1" >';
		print select_generic('projet','rowid','ls_project','ref','title',$ls_project);
	print '</td>';
//Search field forproject_task
	print '<td class="liste_titre" colspan="1" >';
		print select_generic('projet_task','rowid','ls_project_task','ref','label',$ls_project_task);
	print '</td>';
//Search field forsubtask
	print '<td class="liste_titre" colspan="1" >';
	print '<input class="flat" size="16" type="text" name="ls_subtask" value="'.$ls_subtask.'">';
	print '</td>';
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
		if($user->admin)print "<td>".print_generic($db,'user','rowid',$obj->fk_user,'lastname','firstname',' ')."</td>";
		print "<td>".print_generic($db,'projet','rowid',$obj->fk_project,'ref','title', ' - ')."</td>";
		print "<td>".print_generic($db,'projet_task','rowid',$obj->fk_project_task,'ref','label', ' - ')."</td>";
		print "<td>".$obj->subtask."</td>";
		print "<td>".dol_print_date($obj->date_start,'day')."</td>";
		print "<td>".dol_print_date($obj->date_end,'day')."</td>";
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
    print ' '.$langs->trans('Timesheetwhitelist')."</a>\n";


    
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
function Timesheetwhitelist_prepare_head($object)
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
    /*
    $head[$h][0] = $_SERVER["PHP_SELF"].'?action=viewdoc&id='.$object->id;
    $head[$h][1] = $langs->trans("Documents");
    $head[$h][2] = 'documents';
    $h++;
    
    $head[$h][0] = $_SERVER["PHP_SELF"].'?action=viewinfo&id='.$object->id;
    $head[$h][1] = $langs->trans("Info");
    $head[$h][2] = 'info';
    $h++;
     */
     

    return $head;
}
// End of page
llxFooter();
$db->close();
