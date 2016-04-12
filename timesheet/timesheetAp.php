<?php
/* Copyright (C) 2007-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2016 delcroip <delcroip@gmail.com>
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
include 'lib/includeMain.lib.php';
require_once 'lib/timesheet.lib.php';
require_once 'class/timesheetUser.class.php';

$userId            = GETPOST('userid');
$action             = GETPOST('action');
//should return the XMLDoc
$ajax               = GETPOST('ajax');
$xml               = GETPOST('xml');
$id               = GETPOST('id');

//$toDate                 = GETPOST('toDate');

$timestamp=GETPOST('timestamp');


//$userid=  is_object($user)?$user->id:$user;




// Load traductions files requiredby by page
//$langs->load("companies");
$langs->load("main");
$langs->load("projects");
$langs->load('timesheet@timesheet');

$level=1;
$offset=GETPOST('offset');
$objectArray=getTStobeApproved($level,$offset);/*fixme: create function*/
$timesheetUser=reset($objectArray);
$curUser=$firstTimesheetUser->userId;
$nextUser=$firstTimesheetUser->userId;
$i=0;
if(is_object($firstTimesheetUser)){
    
    foreach($objectArray as $key=> $timesheetUser){
        if($firstTimesheetUser->userId==$timesheetUser->userId){
            $i++;//use for the offset
        }else{
            $nextUser=$timesheetUser->userId;
            break;
        }
    }
}
$offset+=$i;
$timesheetUser= new timesheetUser($db,0);

/*******************************************************************
* ACTIONS
*
* Put here all code to do according to value of "action" parameter
********************************************************************/
if($action== 'submit'){
/*         if (isset($_SESSION['timesheetUser'][$timestamp]))
        {
            $timesheetUser->loadFromSession($timestamp);
            //$timesheetUser->db=$db;
           // var_dump(unserialize($timesheetUser->taskTimesheet[0])->tasklist);
            if (!empty($_POST['task']))
            {   
                                $ret=$timesheetUser->updateActuals($_POST['task']);
				//$ret =postActuals($db,$user,$_POST['task'],$timestamp);
                    if($ret>0)
                    {
                        if($_SESSION['timeSpendCreated'])setEventMessage($langs->transnoentitiesnoconv("NumberOfTimeSpendCreated").$_SESSION['timeSpendCreated']);
                        if($_SESSION['timeSpendModified'])setEventMessage($langs->transnoentitiesnoconv("NumberOfTimeSpendModified").$_SESSION['timeSpendModified']);
                        if($_SESSION['timeSpendDeleted'])setEventMessage($langs->transnoentitiesnoconv("NumberOfTimeSpendDeleted").$_SESSION['timeSpendDeleted']);
                    }
                    else
                    {
                        if($ret==0){
                            setEventMessage($langs->transnoentitiesnoconv("NothingChanged"),'errors');
                        }else {
                            setEventMessage( $langs->transnoentitiesnoconv("InternalError").':'.$ret,'errors');
                        }
                    }        
            }else
                    setEventMessage( $langs->transnoentitiesnoconv("NoTaskToUpdate"),'errors');
        }else
                setEventMessage( $langs->transnoentitiesnoconv("InternalError"),'errors');
	
*/
}


if(!empty($timestamp)){
       unset($_SESSION['timesheetUser'][$timestamp]);
}
$timesheetUser->fetch($this->id);
$timesheetUser->fetchAll($this->yearWeek,2);
/***************************************************
* VIEW
*
* Put here all code to build page
****************************************************/

if($xml){
    //renew timestqmp
    ob_clean();
   header("Content-type: text/xml; charset=utf-8");
    echo $timesheetUser->GetTimeSheetXML($userId,5); //fixme
    exit;
}
$morejs=array("/timesheet/js/timesheetAjax.js","/timesheet/js/timesheet.js");
llxHeader('',$langs->trans('Timesheet'),'','','','',$morejs);
//calculate the week days

//tmstp=time();


$ajax=false;

$Form .=$timesheetUser->userName." - ".$timesheetUser->yearWeek;
$Form .=$timesheetUser->getHTMLFormHeader($ajax);
$Form .=$timesheetUser->getHTMLHeader($ajax);
$Form .=$timesheetUser->getHTMLHolidayLines($ajax);
$Form .=$timesheetUser->getHTMLTotal('totalT');
$Form .=$timesheetUser->getHTMLtaskLines($ajax);
$Form .=$timesheetUser->getHTMLTotal('totalB');
$Form .=$timesheetUser->getHTMLFooterAp($ajax);

//Javascript
$timetype=TIMESHEET_TIME_TYPE;
//$Form .= ' <script type="text/javascript" src="timesheet.js"></script>'."\n";
$Form .= '<script type="text/javascript">'."\n\t";
$Form .='updateAll(\''.$timetype.'\');';
$Form .= "\n\t".'</script>'."\n";
// $Form .='</div>';//TimesheetPage
print $Form;

// End of page
llxFooter();
$db->close();
?>
