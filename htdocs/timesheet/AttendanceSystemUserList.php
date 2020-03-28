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
 *   	\file       dev/AttendanceSystemUsers/AttendanceSystemUsers.php
 *		\ingroup    timesheet othermodule1 othermodule2
 *		\brief      This file is an example of a php page
 *					Initialy built by build_class_from_table on 2020-03-28 12:46
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
//require_once 'lib/timesheet.lib.php';
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
$PHP_SELF = $_SERVER['PHP_SELF'];
// Load traductions files requiredby by page
//$langs->load("companies");
$langs->load("attendancesystemuser@timesheet");

// Get parameter
$id			 = GETPOST('id','int');
$ref = GETPOST('ref','alpha');
$action		 = GETPOST('action','alpha');
$backtopage = GETPOST('backtopage');
$cancel = GETPOST('cancel');
$confirm = GETPOST('confirm');
$tms = GETPOST('tms','alpha');
//// Get parameters
$sortfield = GETPOST('sortfield','alpha'); 
$sortorder = GETPOST('sortorder','alpha')?GETPOST('sortorder','alpha'):'ASC';
$removefilter = isset($_POST["removefilter_x"]) || isset($_POST["removefilter"]);
//$applyfilter = isset($_POST["search_x"]) ;//|| isset($_POST["search"]);
if (!$removefilter )		// Both test must be present to be compatible with all browsers
{
    	$ls_as_name = GETPOST('ls_as_name','alpha');
	$ls_user = GETPOST('ls_user','int');
	if($ls_user==-1)$ls_user = '';
	$ls_as_uid = GETPOST('ls_as_uid','int');
	$ls_rfid = GETPOST('ls_rfid','int');
	$ls_role = GETPOST('ls_role','int');
	$ls_passwd = GETPOST('ls_passwd','alpha');
	$ls_data = GETPOST('ls_data','alpha');
	$ls_status = GETPOST('ls_status','int');
	$ls_mode = GETPOST('ls_mode','int');

    
}


if ($page == -1 || !is_numeric($page))  { $page = 0; }
if ($page == -1) { $page = 0; }
$limit = GETPOST('limit','int')?GETPOST('limit','int'):$conf->liste_limit;
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;




 // uncomment to avoid resubmision
//if(isset( $_SESSION['attendancesystemuser_class'][$tms]))
//{

 //   $cancel = TRUE;
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
$object = new AttendanceSystemUser($db);
if($id>0)
{
    $object->id = $id; 
    $object->fetch($id);
    $ref = dol_sanitizeFileName($object->ref);
}
if(!empty($ref))
{
    $object->ref = $ref; 
    $object->id = $id; 
    $object->fetch($id,$ref);
    $ref = dol_sanitizeFileName($object->ref);
    
}


/*******************************************************************
* ACTIONS
*
* Put here all code to do according to value of "action" parameter
********************************************************************/
$form = new Form($db);
$formother = new FormOther($db);
$formproject = new FormProjets($db);         
// Action to remove record
 switch($action){
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
               if (! empty($object->errors)) setEventMessages(null,$object->errors,'errors');
               else setEventMessage('RecordNotDeleted','errors');
       }
       break;
    case 'delete':
        if( $action == 'delete' && ($id>0 || $ref != "")){
         $ret = $form->form_confirm(dol_buildpath('/timesheet/AttendanceSystemUserCard.php',1).'?action=confirm_delete&id='.$id,$langs->trans('DeleteAttendanceSystemUser'),$langs->trans('ConfirmDelete'),'confirm_delete', '', 0, 1);
         if ($ret == 'html') print '<br />';
         //to have the object to be deleted in the background\
        }
      
    } 

/***************************************************
* VIEW
*
* Put here all code to build page
****************************************************/

llxHeader('','AttendanceSystemUser','');
print "<div> <!-- module body-->";

$fuser = new User($db);
// Put here content of your page

// Example : Adding jquery code
/*print '<script type = "text/javascript" language = "javascript">
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


    $sql = 'SELECT';
    $sql .= ' t.rowid,';
    
	$sql .= ' t.as_name,';
	$sql .= ' t.fk_user,';
	$sql .= ' t.as_uid,';
	$sql .= ' t.rfid,';
	$sql .= ' t.role,';
	$sql .= ' t.passwd,';
	$sql .= ' t.data,';
	$sql .= ' t.status,';
	$sql .= ' t.mode';

    
    $sql .= ' FROM '.MAIN_DB_PREFIX.'attendance_system_user as t';
    $sqlwhere = '';
    if(isset($object->entity))
        $sqlwhere .= ' AND t.entity = '.$conf->entity;
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
    	if($ls_as_name) $sqlwhere .= natural_search('t.as_name', $ls_as_name);
	if($ls_user) $sqlwhere .= natural_search(array('t.fk_user'), $ls_user);
	if($ls_as_uid) $sqlwhere .= natural_search(array('t.as_uid'), $ls_as_uid);
	if($ls_rfid) $sqlwhere .= natural_search(array('t.rfid'), $ls_rfid);
	if($ls_role) $sqlwhere .= natural_search(array('t.role'), $ls_role);
	if($ls_passwd) $sqlwhere .= natural_search('t.passwd', $ls_passwd);
	if($ls_data) $sqlwhere .= natural_search('t.data', $ls_data);
	if($ls_status) $sqlwhere .= natural_search(array('t.status'), $ls_status);
	if($ls_mode) $sqlwhere .= natural_search(array('t.mode'), $ls_mode);

    
    //list limit
    if(!empty($sqlwhere))
        $sql .= ' WHERE '.substr ($sqlwhere, 5);
    
// Count total nb of records
$nbtotalofrecords = 0;
if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST))
{
        $sqlcount = 'SELECT COUNT(*) as count FROM '.MAIN_DB_PREFIX.'attendance_system_user as t';
        if(!empty($sqlwhere))
            $sqlcount .= ' WHERE '.substr ($sqlwhere, 5);
	$result = $db->query($sqlcount);
        $nbtotalofrecords = ($result)?$objcount = $db->fetch_object($result)->count:0;
}
    if(!empty($sortfield)){$sql .= $db->order($sortfield,$sortorder);
    }else{ $sortorder = 'ASC';}
    
    if (!empty($limit))
    {
            $sql .= $db->plimit($limit+1, $offset); 
    }
    

    //execute SQL
    dol_syslog($script_file, LOG_DEBUG);
    $resql = $db->query($sql);
    if ($resql)
    {
        $param = '';
        if (! empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param .= '&contextpage='.urlencode($contextpage);
        if ($limit > 0 && $limit != $conf->liste_limit) $param .= '&limit='.urlencode($limit);
        	if (!empty($ls_as_name))	$param .= '&ls_as_name = '.urlencode($ls_as_name);
	if (!empty($ls_user))	$param .= '&ls_user = '.urlencode($ls_user);
	if (!empty($ls_as_uid))	$param .= '&ls_as_uid = '.urlencode($ls_as_uid);
	if (!empty($ls_rfid))	$param .= '&ls_rfid = '.urlencode($ls_rfid);
	if (!empty($ls_role))	$param .= '&ls_role = '.urlencode($ls_role);
	if (!empty($ls_passwd))	$param .= '&ls_passwd = '.urlencode($ls_passwd);
	if (!empty($ls_data))	$param .= '&ls_data = '.urlencode($ls_data);
	if (!empty($ls_status))	$param .= '&ls_status = '.urlencode($ls_status);
	if (!empty($ls_mode))	$param .= '&ls_mode = '.urlencode($ls_mode);

        
        if ($filter && $filter != -1) $param .= '&filtre='.urlencode($filter);
        
        $num = $db->num_rows($resql);
        //print_barre_liste function defined in /core/lib/function.lib.php, possible to add a picto
        print_barre_liste($langs->trans("AttendanceSystemUser"),$page,$PHP_SELF,$param,$sortfield,$sortorder,'',$num,$nbtotalofrecords);
        print_barre_liste($title, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $massactionbutton, $num, $nbtotalofrecords, 'title_companies', 0, '', '', $limit);

        print '<form method = "POST" action = "'.$_SERVER["PHP_SELF"].'">';
        print '<table class = "liste" width = "100%">'."\n";
        //TITLE
        print '<tr class = "liste_titre">';
        	print_liste_field_titre($langs->trans('Asname'),$PHP_SELF,'t.as_name','',$param,'',$sortfield,$sortorder);
	print "\n";
	print_liste_field_titre($langs->trans('User'),$PHP_SELF,'t.fk_user','',$param,'',$sortfield,$sortorder);
	print "\n";
	print_liste_field_titre($langs->trans('Asuid'),$PHP_SELF,'t.as_uid','',$param,'',$sortfield,$sortorder);
	print "\n";
	print_liste_field_titre($langs->trans('Rfid'),$PHP_SELF,'t.rfid','',$param,'',$sortfield,$sortorder);
	print "\n";
	print_liste_field_titre($langs->trans('Role'),$PHP_SELF,'t.role','',$param,'',$sortfield,$sortorder);
	print "\n";
	print_liste_field_titre($langs->trans('Status'),$PHP_SELF,'t.status','',$param,'',$sortfield,$sortorder);
	print "\n";
	print_liste_field_titre($langs->trans('Mode'),$PHP_SELF,'t.mode','',$param,'',$sortfield,$sortorder);
	print "\n";

        
        print '</tr>';
        //SEARCH FIELDS
        print '<tr class = "liste_titre">'; 
        //Search field foras_name
	print '<td class="liste_titre" colspan="1" >';
	print '<input class="flat" size="16" type="text" name="ls_as_name" value="'.$ls_as_name.'">';
	print '</td>';
//Search field foruser
	print '<td class="liste_titre" colspan="1" >';
    $selected = $ls_user;
    $htmlname = 'ls_user';
    print $form->select_dolusers($selected,$htmlname);
	print '</td>';
//Search field foras_uid
	print '<td class="liste_titre" colspan="1" >';
	print '<input class="flat" size="16" type="text" name="ls_as_uid" value="'.$ls_as_uid.'">';
	print '</td>';
//Search field forrfid
	print '<td class="liste_titre" colspan="1" >';
	print '<input class="flat" size="16" type="text" name="ls_rfid" value="'.$ls_rfid.'">';
	print '</td>';
//Search field forrole
	print '<td class="liste_titre" colspan="1" >';
	print '<input class="flat" size="16" type="text" name="ls_role" value="'.$ls_role.'">';
	print '</td>';

//Search field forstatus
	print '<td class="liste_titre" colspan="1" >';
	print '<input class="flat" size="16" type="text" name="ls_status" value="'.$ls_status.'">';
	print '</td>';
//Search field formode
	print '<td class="liste_titre" colspan="1" >';
	print '<input class="flat" size="16" type="text" name="ls_mode" value="'.$ls_mode.'">';
	print '</td>';

        
        
        print '<td width = "15px">';
        print '<input type = "image" class = "liste_titre" name = "search" src = "'.img_picto($langs->trans("Search"),'search.png','','',1).'" value = "'.dol_escape_htmltag($langs->trans("Search")).'" title = "'.dol_escape_htmltag($langs->trans("Search")).'">';
        print '<input type = "image" class = "liste_titre" name = "removefilter" src = "'.img_picto($langs->trans("Search"),'searchclear.png','','',1).'" value = "'.dol_escape_htmltag($langs->trans("RemoveFilter")).'" title = "'.dol_escape_htmltag($langs->trans("RemoveFilter")).'">';
        print '</td>';
        print '</tr>'."\n"; 
        $i = 0;
        $basedurl = dirname($PHP_SELF).'/AttendanceSystemUserCard.php?action=view&id=';
        while ($i < $num && $i<$limit)
        {
            $obj = $db->fetch_object($resql);
            if ($obj)
            {
                // You can use here results
                	print "<tr class=\"oddeven\"  onclick=\"location.href = '";
	print $basedurl.$obj->rowid."'\" >";
	print "<td>".$obj->as_name."</td>";
    $StaticObject = New User($db);
    print "<td>".$StaticObject->getNomUrl('1',$obj->fk_user)."</td>";
	print "<td>".$obj->as_uid."</td>";
	print "<td>".$obj->rfid."</td>";
	print "<td>".$obj->role."</td>";
	print "<td>".$obj->status."</td>";
	print "<td>".$obj->mode."</td>";
	print '<td><a href="AttendanceSystemUserCard.php?action=delete&id='.$obj->rowid.'">'.img_delete().'</a></td>';
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
    print '<a href = "AttendanceSystemUserCard.php?action=create" class = "butAction" role = "button">'.$langs->trans('New');
    print ' '.$langs->trans('AttendanceSystemUser')."</a>\n";

    




// End of page
llxFooter();
$db->close();
