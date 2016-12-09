<?php
/* Copyright (C) 2007-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2014 delcroip <delcroip@gmail.com>
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
 *		\ingroup    mymodule othermodule1 othermodule2
 *		\brief      This file is an example of a php page
 *					Put here some comments
 */


// hide left menu
//$_POST['dol_hide_leftmenu']=1;
// Change this following line to use the correct relative path (../, ../../, etc)
include 'core/lib/includeMain.lib.php';
require_once 'core/lib/timesheet.lib.php';
require_once 'class/timesheetUser.class.php';


$action             = GETPOST('action');
$yearWeek           = GETPOST('yearweek');
//should return the XMLDoc
$ajax               = GETPOST('ajax');
$xml               = GETPOST('xml');
$optioncss = GETPOST('optioncss','alpha');

$id=GETPOST('id');
//$toDate                 = GETPOST('toDate');
$toDate                 = GETPOST('toDate');
$toDateday =(!empty($toDate) && $action=='goToDate')? GETPOST('toDateday'):0; // to not look for the date if action not goTodate
$toDatemonth                 = GETPOST('toDatemonth');
$toDateyear                 = GETPOST('toDateyear');

$timestamp=GETPOST('timestamp');
$whitelistmode=GETPOST('wlm','int');
$userid=  is_object($user)?$user->id:$user;
$timesheetUser= new timesheetUser($db,$userid);
$confirm=GETPOST('confirm');

if($yearWeek==0 && isset($_SESSION["yearWeek"])) $yearWeek=$_SESSION["yearWeek"];
//if($yearWeek==0 ) $yearWeek=$_SESSION["yearWeek"];
$yearWeek=getYearWeek($toDateday,$toDatemonth,$toDateyear,$yearWeek);
$_SESSION["yearWeek"]=$yearWeek ;







// Load traductions files requiredby by page
//$langs->load("companies");
$langs->load("main");
$langs->load("projects");
$langs->load('timesheet@timesheet');

/*
// Get parameters

$id			= GETPOST('id','int');
$action		= GETPOST('action','alpha');
$myparam	= GETPOST('myparam','alpha');

// Protection if external user
if ($user->societe_id > 0)
{
	//accessforbidden();
}

*/
  
$timesheetUser= new timesheetUser($db,$userid);

/*******************************************************************
* ACTIONS
*
* Put here all code to do according to value of "action" parameter
********************************************************************/

switch($action){
    case 'submit':
        if(isset($_SESSION['timesheetUser'][$timestamp]))
        {
            
            if(isset($_POST['task']))
			{
				 foreach($_POST['task'] as $key => $tasktab){
					 $timesheetUser->loadFromSession($timestamp,$key);                  
					 $timesheetUser->note=$_POST['Note'][$key];
                                         if(isset($_POST['submit'])){
						 $timesheetUser->status="SUBMITTED";
						 $ret=0;
					 }          
					 $ret=$timesheetUser->updateActuals($tasktab);

                		//$ret =postActuals($db,$user,$_POST['task'],$timestamp);
					 if(!empty($ret))
					 {
						 if(isset($_POST['submit']))setEventMessage($langs->transnoentitiesnoconv("timesheetSumitted"));
						 if($_SESSION['timesheetUser'][$timestamp]['timeSpendCreated'])setEventMessage($langs->transnoentitiesnoconv("NumberOfTimeSpendCreated").$_SESSION['timesheetUser'][$timestamp]['timeSpendCreated']);
						 if($_SESSION['timesheetUser'][$timestamp]['timeSpendModified'])setEventMessage($langs->transnoentitiesnoconv("NumberOfTimeSpendModified").$_SESSION['timesheetUser'][$timestamp]['timeSpendModified']);
						 if($_SESSION['timesheetUser'][$timestamp]['timeSpendDeleted'])setEventMessage($langs->transnoentitiesnoconv("NumberOfTimeSpendDeleted").$_SESSION['timesheetUser'][$timestamp]['timeSpendDeleted']);
					 }else
					 {
						 if($_SESSION['timesheetUser'][$timestamp]['updateError']){
							 setEventMessage( $langs->transnoentitiesnoconv("InternalError").$langs->transnoentitiesnoconv(" Update failed").':'.$ret,'errors');
						 }else {
							 setEventMessage($langs->transnoentitiesnoconv("NothingChanged"),'warnings');
						 }
					 }
				 }
            }else if(isset($_POST['recall'])){
				$timesheetUser->loadFromSession($timestamp,$_POST['tsUserId']); /*FIXME to support multiple TS sent*/
				$timesheetUser->status="DRAFT";
                $ret=$timesheetUser->update($user);
                if($ret>0)setEventMessage($langs->transnoentitiesnoconv("timesheetRecalled"));
                else setEventMessage($langs->transnoentitiesnoconv("timesheetNotRecalled"),'errors');
            }else{
                    setEventMessage( $langs->transnoentitiesnoconv("NoTaskToUpdate"),'errors');
            }
        }else
                setEventMessage( $langs->transnoentitiesnoconv("InternalError").$langs->transnoentitiesnoconv(" : timestamp missmatch"),'errors');

        break;
    case 'deletefile':
        $action='delete'; // to trigger the delete action in the linkedfiles.inc.php
        break;
    default:
        break;

}

        


if(!empty($timestamp)){
       unset($_SESSION['timesheetUser'][$timestamp]);
}

$timesheetUser->fetchAll($yearWeek,$whitelistmode);

if(TIMESHEET_ADD_DOCS){
    dol_include_once('/core/class/html.formfile.class.php');
    dol_include_once('/core/lib/files.lib.php');
    $modulepart = 'timesheet';
    $object=$timesheetUser;
    $ref=dol_sanitizeFileName($object->ref);
    $upload_dir = $conf->timesheet->dir_output.'/'.get_exdir($object->id,2,0,0,$object,'timesheet').$ref;
    if(version_compare(DOL_VERSION,"4.0")>=0){
        include_once DOL_DOCUMENT_ROOT . '/core/actions_linkedfiles.inc.php';
    }else{
        include_once DOL_DOCUMENT_ROOT . '/core/tpl/document_actions_pre_headers.tpl.php';
        //dol_include_once('/core/class/html.form.class.php');
        
    }
}

/***************************************************
* VIEW
*
* Put here all code to build page
****************************************************/

if($xml){
    //renew timestqmp
    ob_clean();
   header("Content-type: text/xml; charset=utf-8");
    echo $timesheetUser->GetTimeSheetXML();
    exit;
}
$morejs=array("/timesheet/core/js/jsparameters.php","/timesheet/core/js/timesheet.js");
llxHeader('',$langs->trans('Timesheet'),'','','','',$morejs);
//calculate the week days

//tmstp=time();


$ajax=false;
$Form =$timesheetUser->getHTMLNavigation($optioncss,$ajax);
$Form .=$timesheetUser->getHTMLFormHeader($ajax);
$Form .=$timesheetUser->getHTMLHeader($ajax);

$Form .=$timesheetUser->getHTMLHolidayLines($ajax);

$Form .=$timesheetUser->getHTMLTotal();

$Form .=$timesheetUser->getHTMLtaskLines($ajax);
$Form .=$timesheetUser->getHTMLTotal();
$Form .=$timesheetUser->getHTMLNote($ajax);
$Form .=$timesheetUser->getHTMLFooter($ajax);


//Javascript
$timetype=TIMESHEET_TIME_TYPE;
//$Form .= ' <script type="text/javascript" src="core/js/timesheet.js"></script>'."\n";
$Form .= '<script type="text/javascript">'."\n\t";
$Form .='updateAll();';
$Form .= "\n\t".'</script>'."\n";
// $Form .='</div>';//TimesheetPage
print $Form;
//add attachement
if(TIMESHEET_ADD_DOCS){
        
        $object=$timesheetUser;
        $modulepart = 'timesheet';
        $permission = 1;//$user->rights->timesheet->add;
        $filearray=dol_dir_list($upload_dir,'files',0,'','\.meta$',$sortfield,(strtolower($sortorder)=='desc'?SORT_DESC:SORT_ASC),1);
        //$param = 'action=submitfile&id='.$object->id;
            $form=new Form($db);
            include_once DOL_DOCUMENT_ROOT . '/core/tpl/document_actions_post_headers.tpl.php';

}
// End of page
llxFooter();
$db->close();
?>
