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
include 'lib/includeMain.lib.php';
require_once 'lib/timesheet.lib.php';
require_once 'class/userTimesheet.class.php';

$action             = GETPOST('action');
$yearWeek           = GETPOST('yearweek');
//should return the XMLDoc
$ajax               = GETPOST('ajax');
$xml               = GETPOST('xml');
$optioncss = GETPOST('optioncss','alpha');


//$toDate                 = GETPOST('toDate');
$toDate                 = GETPOST('toDate');
if (!empty($toDate) && $action=='goToDate')
{
    $yearWeek =date('Y\WW',strtotime(str_replace('/', '-',$toDate)));  
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
$userTimesheet= new userTimesheet($db,$user);

/*******************************************************************
* ACTIONS
*
* Put here all code to do according to value of "action" parameter
********************************************************************/
if($action== 'submit'){
        if (isset($_SESSION['userTimesheet'][$timestamp]))
        {
            $userTimesheet->loadFromSession($timestamp);
            //$userTimesheet->db=$db;
           // var_dump(unserialize($userTimesheet->taskTimesheet[0])->tasklist);
            if (!empty($_POST['task']))
            {   
                                $ret=$userTimesheet->updateActuals($_POST['task']);
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
	

}


if(!empty($timestamp)){
       unset($_SESSION['userTimesheet'][$timestamp]);
}

$userTimesheet->fetchAll($yearWeek,$whitelistmode);
/***************************************************
* VIEW
*
* Put here all code to build page
****************************************************/

if($xml){
    //renew timestqmp
    ob_clean();
   header("Content-type: text/xml; charset=utf-8");
    echo $userTimesheet->GetTimeSheetXML();
    exit;
}
$morejs=array("/timesheet/js/timesheetAjax.js","/timesheet/js/timesheet.js");
llxHeader('',$langs->trans('Timesheet'),'','','','',$morejs);
//calculate the week days

//tmstp=time();


$ajax=false;
 $Form =$userTimesheet->getHTMLNavigation($optioncss,$ajax);

$Form .=$userTimesheet->getHTMLHeader($ajax);

$Form .=$userTimesheet->getHTMLHolidayLines($ajax);

$Form .=$userTimesheet->getHTMLTotal('totalT');

$Form .=$userTimesheet->getHTMLtaskLines($ajax);

$Form .=$userTimesheet->getHTMLTotal('totalB');

$Form .=$userTimesheet->getHTMLFooter($ajax);


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
