<?php
/*
 * Copyright (C) 2007-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2018 delcroip <patrick@pmpd.eu>
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
 *   	\file       dev/projettasktimes/projettasktime_page.php
 *		\ingroup    timesheet othermodule1 othermodule2
 *		\brief      This file is an example of a php page
 *					Initialy built by build_class_from_table on 2019-07-03 22:42
 */

//if (! defined('NOREQUIREUSER'))  define('NOREQUIREUSER', '1');
//if (! defined('NOREQUIREDB'))    define('NOREQUIREDB', '1');
//if (! defined('NOREQUIRESOC'))   define('NOREQUIRESOC', '1');
//if (! defined('NOREQUIRETRAN'))  define('NOREQUIRETRAN', '1');
//if (! defined('NOCSRFCHECK'))    define('NOCSRFCHECK', '1');			// Do not check anti CSRF attack test
//if (! defined('NOSTYLECHECK'))   define('NOSTYLECHECK', '1');			// Do not check style html tag into posted data
//if (! defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL', '1');		// Do not check anti POST attack test
//if (! defined('NOREQUIREMENU'))  define('NOREQUIREMENU', '1');			// If there is no need to load and show top and left menu
//if (! defined('NOREQUIREHTML'))  define('NOREQUIREHTML', '1');			// If we don't need to load the html.form.class.php
//if (! defined('NOREQUIREAJAX'))  define('NOREQUIREAJAX', '1');
//if (! defined("NOLOGIN"))        define("NOLOGIN", '1');				// If this page is public (can be called outside logged session)

// Change this following line to use the correct relative path (../, ../../, etc)
include 'core/lib/includeMain.lib.php';
// Change this following line to use the correct relative path from htdocs
//include_once(DOL_DOCUMENT_ROOT.'/core/class/formcompany.class.php');
//require_once 'lib/timesheet.lib.php';
require_once 'class/ProjetTaskTime.class.php';
require_once 'core/lib/generic.lib.php';
//require_once 'core/lib/projettasktime.lib.php';
dol_include_once('/core/lib/functions2.lib.php');
require_once 'core/lib/timesheet.lib.php';
//document handling
dol_include_once('/core/lib/files.lib.php');
//dol_include_once('/core/lib/images.lib.php');
dol_include_once('/core/class/html.formfile.class.php');
dol_include_once('/core/class/html.formother.class.php');
dol_include_once('/core/class/html.formprojet.class.php');
require_once DOL_DOCUMENT_ROOT.'/projet/class/task.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
$PHP_SELF=$_SERVER['PHP_SELF'];
// Load traductions files requiredby by page
//$langs->load("companies");
$langs->load("projettasktime@timesheet");

// Get parameter
$id			= GETPOST('id', 'int');
$ref                    = GETPOST('ref', 'alpha');
$action		= GETPOST('action', 'alpha');
$backtopage = GETPOST('backtopage');
$cancel=GETPOST('cancel');
$confirm=GETPOST('confirm');
$tms= GETPOST('tms', 'alpha');
//// Get parameters
$sortfield = GETPOST('sortfield', 'alpha');
$sortorder = GETPOST('sortorder', 'alpha')?GETPOST('sortorder', 'alpha'):'ASC';
$removefilter=isset($_POST["removefilter_x"]) || isset($_POST["removefilter"]);
//$applyfilter=isset($_POST["search_x"]) ;//|| isset($_POST["search"]);
if (!$removefilter )		// Both test must be present to be compatible with all browsers
{
    	$ls_task= GETPOST('ls_task', 'int');
	$ls_task_date_month= GETPOST('ls_task_date_month', 'int');
	$ls_task_date_year= GETPOST('ls_task_date_year', 'int');
	$ls_task_datehour_month= GETPOST('ls_task_datehour_month', 'int');
	$ls_task_datehour_year= GETPOST('ls_task_datehour_year', 'int');
	$ls_task_date_withhour= GETPOST('ls_task_date_withhour', 'int');
	$ls_task_duration= GETPOST('ls_task_duration', 'int');
	$ls_user= GETPOST('ls_user', 'int');
	if($ls_user==-1)$ls_user='';
	$ls_thm= GETPOST('ls_thm', 'int');
	$ls_note= GETPOST('ls_note', 'alpha');
	$ls_invoice_id= GETPOST('ls_invoice_id', 'int');
	$ls_invoice_line_id= GETPOST('ls_invoice_line_id', 'int');
	$ls_import_key= GETPOST('ls_import_key', 'alpha');
	$ls_status= GETPOST('ls_status', 'int');
	$ls_task_time_approval= GETPOST('ls_task_time_approval', 'int');
}


$page = GETPOST('page', 'int');
if ($page == -1 || !is_numeric($page)) { $page = 0; }
$limit = GETPOST('limit', 'int')?GETPOST('limit', 'int'):$conf->liste_limit;
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;




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
$form=new Form($db);
$formother=new FormOther($db);
$formproject=new FormProjets($db);
// Action to remove record
switch($action){
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
               if (! empty($object->errors)) setEventMessages(null, $object->errors, 'errors');
               else setEventMessage('RecordNotDeleted', 'errors');
       }
       break;
    case 'delete':
        if( $action=='delete' && ($id>0 || $ref!="")){
         $ret=$form->form_confirm(dol_buildpath('/timesheet/Projettasktime_card.php', 1).'?action=confirm_delete&id='.$id, $langs->trans('DeleteProjettasktime'), $langs->trans('ConfirmDelete'), 'confirm_delete', '', 0, 1);
         if ($ret == 'html') print '<br />';
         //to have the object to be deleted in the background\
        }
}

/***************************************************
* VIEW
*
* Put here all code to build page
****************************************************/

llxHeader('', 'Projettasktime', '');
print "<div> <!-- module body-->";

$fuser=new User($db);
// Put here content of your page

// Example : Adding jquery code
/*print '<script type="text/javascript" language="javascript">
jQuery(document).ready(function() {
	function init_myfunc()
	{
		jQuery("#myid").removeAttr(\'disabled\');
		jQuery("#myid").attr(\'disabled\', \'disabled\');
	}
	init_myfunc();
	jQuery("#mybutton").click(function() {
		init_needroot();
	});
});
</script>';*/


    $sql = 'SELECT';
    $sql.= ' t.rowid, ';

	$sql.=' t.fk_task, ';
	$sql.=' t.task_date, ';
	$sql.=' t.task_datehour, ';
	$sql.=' t.task_date_withhour, ';
	$sql.=' t.task_duration, ';
	$sql.=' t.fk_user, ';
	$sql.=' t.thm, ';
	$sql.=' t.note, ';
	$sql.=' t.invoice_id, ';
	$sql.=' t.invoice_line_id, ';
	$sql.=' t.import_key, ';
	$sql.=' t.status, ';
	$sql.=' t.fk_task_time_approval';


    $sql.= ' FROM '.MAIN_DB_PREFIX.'projet_task_time as t';
    $sqlwhere='';
    if(isset($object->entity))
        $sqlwhere.= ' AND t.entity = '.$conf->entity;
    if ($filter && $filter != -1)		// GETPOST('filtre') may be a string
    {
            $filtrearr = explode(', ', $filter);
            foreach ($filtrearr as $fil)
            {
                    $filt = explode(':', $fil);
                    $sqlwhere .= ' AND ' . $filt[0] . ' = ' . $filt[1];
            }
    }
    //pass the search criteria
    	if($ls_task) $sqlwhere .= natural_search(array('t.fk_task'), $ls_task);
	if($ls_task_date_month)$sqlwhere .= ' AND MONTH(t.task_date)="'.$ls_task_date_month."'";
	if($ls_task_date_year)$sqlwhere .= ' AND YEAR(t.task_date)="'.$ls_task_date_year."'";
	if($ls_task_datehour_month)$sqlwhere .= ' AND MONTH(t.task_datehour)="'.$ls_task_datehour_month."'";
	if($ls_task_datehour_year)$sqlwhere .= ' AND YEAR(t.task_datehour)="'.$ls_task_datehour_year."'";
	if($ls_task_date_withhour) $sqlwhere .= natural_search(array('t.task_date_withhour'), $ls_task_date_withhour);
	if($ls_task_duration) $sqlwhere .= natural_search(array('t.task_duration'), $ls_task_duration);
	if($ls_user) $sqlwhere .= natural_search(array('t.fk_user'), $ls_user);
	if($ls_thm) $sqlwhere .= natural_search(array('t.thm'), $ls_thm);
	if($ls_note) $sqlwhere .= natural_search('t.note', $ls_note);
	if($ls_invoice_id) $sqlwhere .= natural_search(array('t.invoice_id'), $ls_invoice_id);
	if($ls_invoice_line_id) $sqlwhere .= natural_search(array('t.invoice_line_id'), $ls_invoice_line_id);
	if($ls_import_key) $sqlwhere .= natural_search('t.import_key', $ls_import_key);
	if($ls_status) $sqlwhere .= natural_search(array('t.status'), $ls_status);
	if($ls_task_time_approval) $sqlwhere .= natural_search(array('t.fk_task_time_approval'), $ls_task_time_approval);


    //list limit
    if(!empty($sqlwhere))
        $sql.=' WHERE '.substr($sqlwhere, 5);

// Count total nb of records
$nbtotalofrecords = 0;
if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST))
{
        $sqlcount='SELECT COUNT(*) as count FROM '.MAIN_DB_PREFIX.'projet_task_time as t';
        if(!empty($sqlwhere))
            $sqlcount.=' WHERE '.substr($sqlwhere, 5);
	$result = $db->query($sqlcount);
        $nbtotalofrecords = ($result)?$objcount = $db->fetch_object($result)->count:0;
}
    if(!empty($sortfield)){$sql.= $db->order($sortfield, $sortorder);
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
        $param='';
        if (! empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param.='&contextpage='.urlencode($contextpage);
        if ($limit > 0 && $limit != $conf->liste_limit) $param.='&limit='.urlencode($limit);
        	if (!empty($ls_task))	$param.='&ls_task='.urlencode($ls_task);
	if (!empty($ls_task_date_month))	$param.='&ls_task_date_month='.urlencode($ls_task_date_month);
	if (!empty($ls_task_date_year))	$param.='&ls_task_date_year='.urlencode($ls_task_date_year);
	if (!empty($ls_task_datehour_month))	$param.='&ls_task_datehour_month='.urlencode($ls_task_datehour_month);
	if (!empty($ls_task_datehour_year))	$param.='&ls_task_datehour_year='.urlencode($ls_task_datehour_year);
	if (!empty($ls_task_date_withhour))	$param.='&ls_task_date_withhour='.urlencode($ls_task_date_withhour);
	if (!empty($ls_task_duration))	$param.='&ls_task_duration='.urlencode($ls_task_duration);
	if (!empty($ls_user))	$param.='&ls_user='.urlencode($ls_user);
	if (!empty($ls_thm))	$param.='&ls_thm='.urlencode($ls_thm);
	if (!empty($ls_note))	$param.='&ls_note='.urlencode($ls_note);
	if (!empty($ls_invoice_id))	$param.='&ls_invoice_id='.urlencode($ls_invoice_id);
	if (!empty($ls_invoice_line_id))	$param.='&ls_invoice_line_id='.urlencode($ls_invoice_line_id);
	if (!empty($ls_import_key))	$param.='&ls_import_key='.urlencode($ls_import_key);
	if (!empty($ls_status))	$param.='&ls_status='.urlencode($ls_status);
	if (!empty($ls_task_time_approval))	$param.='&ls_task_time_approval='.urlencode($ls_task_time_approval);


        if ($filter && $filter != -1) $param.='&filtre='.urlencode($filter);

        $num = $db->num_rows($resql);
        //print_barre_liste function defined in /core/lib/function.lib.php, possible to add a picto
        print_barre_liste($langs->trans("Projettasktime"), $page, $PHP_SELF, $param, $sortfield, $sortorder, '', $num, $nbtotalofrecords);
        print_barre_liste($title, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $massactionbutton, $num, $nbtotalofrecords, 'title_companies', 0, '', '', $limit);

        print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
        print '<table class="liste" width="100%">'."\n";
        //TITLE
        print '<tr class="liste_titre">';
    print_liste_field_titre($langs->trans('Task'), $PHP_SELF, 't.fk_task', '', $param, '', $sortfield, $sortorder);
	print "\n";
	print_liste_field_titre($langs->trans('Taskdate'), $PHP_SELF, 't.task_date', '', $param, '', $sortfield, $sortorder);
	print "\n";
	print_liste_field_titre($langs->trans('Taskdatehour'), $PHP_SELF, 't.task_datehour', '', $param, '', $sortfield, $sortorder);
	print "\n";
	print_liste_field_titre($langs->trans('Taskdatewithhour'), $PHP_SELF, 't.task_date_withhour', '', $param, '', $sortfield, $sortorder);
	print "\n";
	print_liste_field_titre($langs->trans('Taskduration'), $PHP_SELF, 't.task_duration', '', $param, '', $sortfield, $sortorder);
	print "\n";
	print_liste_field_titre($langs->trans('User'), $PHP_SELF, 't.fk_user', '', $param, '', $sortfield, $sortorder);
	print "\n";
	print_liste_field_titre($langs->trans('Thm'), $PHP_SELF, 't.thm', '', $param, '', $sortfield, $sortorder);
	print "\n";
	print_liste_field_titre($langs->trans('Note'), $PHP_SELF, 't.note', '', $param, '', $sortfield, $sortorder);
	print "\n";
	print_liste_field_titre($langs->trans('Invoiceid'), $PHP_SELF, 't.invoice_id', '', $param, '', $sortfield, $sortorder);
	print "\n";
	print_liste_field_titre($langs->trans('Invoicelineid'), $PHP_SELF, 't.invoice_line_id', '', $param, '', $sortfield, $sortorder);
	print "\n";
	print_liste_field_titre($langs->trans('Importkey'), $PHP_SELF, 't.import_key', '', $param, '', $sortfield, $sortorder);
	print "\n";
	print_liste_field_titre($langs->trans('Status'), $PHP_SELF, 't.status', '', $param, '', $sortfield, $sortorder);
	print "\n";
	//print_liste_field_titre($langs->trans('Tasktimeapproval'), $PHP_SELF, 't.fk_task_time_approval', '', $param, '', $sortfield, $sortorder);
	//print "\n";


        print '</tr>';
        //SEARCH FIELDS
        print '<tr class="liste_titre">';
        //Search field fortask
	print '<td class="liste_titre" colspan="1" >';
	$sql_task=array('table'=> 'projet_task', 'keyfield'=> 'rowid', 'fields'=>'ref, label', 'join' => '', 'where'=>'', 'tail'=>'');
	$html_task=array('name'=>'ls_task', 'class'=>'', 'otherparam'=>'', 'ajaxNbChar'=>'', 'separator'=> '-');
	$addChoices_task=null;
	print select_sellist($sql_task, $html_task, $ls_task, $addChoices_task);
	print '</td>';
//Search field fortask_date
	print '<td class="liste_titre" colspan="1" >';
	print '<input class="flat" type="text" size="1" maxlength="2" name="task_date_month" value="'.$ls_task_date_month.'">';
	$syear = $ls_task_date_year;
	$formother->select_year($syear?$syear:-1, 'ls_task_date_year', 1, 20, 5);
	print '</td>';
//Search field fortask_datehour
	print '<td class="liste_titre" colspan="1" >';
	print '<input class="flat" type="text" size="1" maxlength="2" name="task_datehour_month" value="'.$ls_task_datehour_month.'">';
	$syear = $ls_task_datehour_year;
	$formother->select_year($syear?$syear:-1, 'ls_task_datehour_year', 1, 20, 5);
	print '</td>';
//Search field fortask_date_withhour
	print '<td class="liste_titre" colspan="1" >';
	print '<input class="flat" size="16" type="text" name="ls_task_date_withhour" value="'.$ls_task_date_withhour.'">';
	print '</td>';
//Search field fortask_duration
	print '<td class="liste_titre" colspan="1" >';
	print '<input class="flat" size="16" type="text" name="ls_task_duration" value="'.$ls_task_duration.'">';
	print '</td>';
//Search field foruser
	print '<td class="liste_titre" colspan="1" >';

	print $form->select_dolusers($ls_user, 'user');
	print '</td>';
//Search field forthm
	print '<td class="liste_titre" colspan="1" >';
	print '<input class="flat" size="16" type="text" name="ls_thm" value="'.$ls_thm.'">';
	print '</td>';
//Search field fornote
	print '<td class="liste_titre" colspan="1" >';
	print '<input class="flat" size="16" type="text" name="ls_note" value="'.$ls_note.'">';
	print '</td>';
//Search field forinvoice_id
	print '<td class="liste_titre" colspan="1" >';
	print '<input class="flat" size="16" type="text" name="ls_invoice_id" value="'.$ls_invoice_id.'">';
	print '</td>';
//Search field forinvoice_line_id
	print '<td class="liste_titre" colspan="1" >';
	print '<input class="flat" size="16" type="text" name="ls_invoice_line_id" value="'.$ls_invoice_line_id.'">';
	print '</td>';
//Search field forimport_key
	print '<td class="liste_titre" colspan="1" >';
	print '<input class="flat" size="16" type="text" name="ls_import_key" value="'.$ls_import_key.'">';
	print '</td>';
//Search field forstatus
	print '<td class="liste_titre" colspan="1" >';
	print '<input class="flat" size="16" type="text" name="ls_status" value="'.$ls_status.'">';
	print '</td>';
//Search field fortask_time_approval
/*	print '<td class="liste_titre" colspan="1" >';
	$sql_task_time_approval=array('table'=> 'project_task_time_approval', 'keyfield'=> 'rowid', 'fields'=>'ref', 'join' => '', 'where'=>'', 'tail'=>'');
	$html_task_time_approval=array('name'=>'ls_task_time_approval', 'class'=>'', 'otherparam'=>'', 'ajaxNbChar'=>'', 'separator'=> '-');
	$addChoices_task_time_approval=null;
		print select_sellist($sql_task_time_approval, $html_task_time_approval, $ls_task_time_approval, $addChoices_task_time_approval );
	print '</td>';

        */

        print '<td width="15px">';
        print '<input type="image" class="liste_titre" name="search" src="'.img_picto($langs->trans("Search"), 'search.png', '', '', 1).'" value="'.dol_escape_htmltag($langs->trans("Search")).'" title="'.dol_escape_htmltag($langs->trans("Search")).'">';
        print '<input type="image" class="liste_titre" name="removefilter" src="'.img_picto($langs->trans("Search"), 'searchclear.png', '', '', 1).'" value="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'" title="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'">';
        print '</td>';
        print '</tr>'."\n";
        $i=0;
        $basedurl=dirname($PHP_SELF).'/ProjectTaskTime_card.php?action=view&id=';
        while ($i < $num && $i<$limit)
        {
            $obj = $db->fetch_object($resql);
            if ($obj)
            {
                // You can use here results
                	print "<tr class=\"oddeven\"  onclick=\"location.href='";
                print $basedurl.$obj->rowid."'\" >";
	
		$StaticObject= New Task($db);
		$StaticObject->fetch($obj->fk_task);
		print "<td>".$StaticObject->getNomUrl('1')."</td>";

                print "<td>".dol_print_date($db->jdate($obj->task_date), 'day')."</td>";
                print "<td>".dol_print_date($db->jdate($obj->task_datehour), 'day')."</td>";
                print "<td>".$obj->task_date_withhour."</td>";
                print "<td>".$obj->task_duration."</td>";
		$StaticObject= New User($db);
		$StaticObject->fetch($obj->fk_user);
		print "<td>".$StaticObject->getNomUrl('1')."</td>";
                print "<td>".$obj->thm."</td>";
                print "<td>".$obj->note."</td>";
                print "<td>".$obj->invoice_id."</td>";
                print "<td>".$obj->invoice_line_id."</td>";
                print "<td>".$obj->import_key."</td>";
                print "<td>".$obj->status."</td>";
                /*if(class_exists('Tasktimeapproval')){
                        $StaticObject= New Tasktimeapproval($db);
                        print "<td>".$StaticObject->getNomUrl('1', $obj->fk_task_time_approval)."</td>";
                }else{
                        print print_sellist($sql_task_time_approval, $obj->fk_task_time_approval);
                }*/
                print '<td><a href="ProjectTaskTime_card.php?action=delete&id='.$obj->rowid.'">'.img_delete().'</a></td>';
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
    print '</form>'."\n";
    // new button
    print '<a href="projettasktime_card.php?action=create" class="butAction" role="button">'.$langs->trans('New');
    print ' '.$langs->trans('Projettasktime')."</a>\n";






// End of page
llxFooter();
$db->close();
