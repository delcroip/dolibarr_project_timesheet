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


//$toDate                 = GETPOST('toDate');
$toDate                 = GETPOST('toDate');
$toDateday                 = GETPOST('toDateday');
$toDatemonth                 = GETPOST('toDatemonth');
$toDateyear                 = GETPOST('toDateyear');
if (!empty($toDate) && $action=='goToDate')
{
    //$yearWeek =date('Y\WW',strtotime(str_replace('/', '-',$toDate)));  
    $yearWeek =date('Y\WW',dol_mktime(0,0,0,$toDatemonth,$toDateday,$toDateyear));  
    $_SESSION["yearWeek"]=$yearWeek ;      
}else if ($yearWeek!=0) {
        $_SESSION["yearWeek"]=$yearWeek;
}else if(isset($_SESSION["yearWeek"])){
        $yearWeek=$_SESSION["yearWeek"];
}else
{
        $yearWeek=date('Y\WW');
        $_SESSION["yearWeek"]=$yearWeek;
}
$timestamp=GETPOST('timestamp');
$whitelistmode=GETPOST('wlm','int');

$userid=  is_object($user)?$user->id:$user;




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

if($action== 'submit'){
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

}


if(!empty($timestamp)){
       unset($_SESSION['timesheetUser'][$timestamp]);
}

$timesheetUser->fetchAll($yearWeek,$whitelistmode);
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

// End of page
llxFooter();
$db->close();
?>
