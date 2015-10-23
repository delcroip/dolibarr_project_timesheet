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
$res=0;
if (! $res && file_exists("../main.inc.php")) $res=@include '../main.inc.php';					// to work if your module directory is into dolibarr root htdocs directory
if (! $res && file_exists("../../main.inc.php")) $res=@include '../../main.inc.php';// to work if your module directory is into a subdir of root htdocs directory
//if (! $res && file_exists("/home/patrick/gitrespo/dolibarr/htdocs/main.inc.php")) $res=@include "/home/patrick/gitrespo/dolibarr/htdocs/main.inc.php";     // Used on dev env only
if(strpos($_SERVER['PHP_SELF'], 'dolibarr_min')>0 && !$res && file_exists("/var/www/dolibarr_min/htdocs/main.inc.php")) $res=@include "/var/www/dolibarr_min/htdocs/main.inc.php";     // Used on dev env only
else if (! $res && file_exists("/var/www/dolibarr/htdocs/main.inc.php")) $res=@include '/var/www/dolibarr/htdocs/main.inc.php';   // Used on dev env only
if (! $res && file_exists("../../../../dolibarr/htdocs/main.inc.php")) $res=@include '../../../../dolibarr/htdocs/main.inc.php';   // Used on dev env only
if (! $res) die("Include of main fails");
// Change this following line to use the correct relative path from htdocs
// 
//to get the form funciton
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
//to get the timesheet lib
require_once 'lib/timesheet.lib.php';


$action             = GETPOST('action');
$yearWeek           = GETPOST('yearweek');
$ajax               = GETPOST('ajax');
$optioncss = GETPOST('optioncss','alpha');


//$toDate                 = GETPOST('toDate');
$toDate                 = GETPOST('toDate');
if ($yearWeek!=0) {
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
if(!is_numeric($whitelistmode))$whitelistmode=TIMESHEET_WHITELIST_MODE;
$userid=  is_object($user)?$user->id:$user;
if($ajax){
    //renew timestqmp
    echo GetTimeSheetXML($userid,$yearWeek,$whitelistmode);
    exit;
}


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

/*******************************************************************
* ACTIONS
*
* Put here all code to do according to value of "action" parameter
********************************************************************/
switch($action)
{
    case 'submit':
        
        if (isset($_SESSION['timestamps'][$timestamp]))
        {
            if (!empty($_POST['task']))
            {    
                    
                    $ret =postActuals($db,$user,$_POST['task'],$timestamp);
                    if($ret>0)
                    {
                        if($_SESSION['timeSpendCreated'])setEventMessage($langs->trans("NumberOfTimeSpendCreated").$_SESSION['timeSpendCreated']);
                        if($_SESSION['timeSpendModified'])setEventMessage($langs->trans("NumberOfTimeSpendModified").$_SESSION['timeSpendModified']);
                        if($_SESSION['timeSpendDeleted'])setEventMessage($langs->trans("NumberOfTimeSpendDeleted").$_SESSION['timeSpendDeleted']);
                    }
                    else
                    {
                        if($ret==0){
                            setEventMessage($langs->trans("NothingChanged"),'errors');
                        }else {
                            setEventMessage( $langs->trans("InternalError").':'.$ret,'errors');
                        }
                    }        
            }else
                    setEventMessage( $langs->trans("NoTaskToUpdate"),'errors');
        }else
                setEventMessage( $langs->trans("InternalError"),'errors');
    case 'goToDate':
        if (!empty($toDate))
        {
            $yearWeek =date('Y\WW',strtotime(str_replace('/', '-',$toDate)));  
            $_SESSION["yearWeek"]=$yearWeek ;      
        }
    default:
        if(!empty($timestamp)){
             unset($_SESSION["timestamps"][$timestamp]);
        }
            break;
}


/***************************************************
* VIEW
*
* Put here all code to build page
****************************************************/
$morejs=array("/timesheet/js/timesheetAjax.js","/timesheet/js/timesheet.js");
llxHeader('',$langs->trans('Timesheet'),'','','','',$morejs);
//calculate the week days

$tmstp=time();
	 

 $form = new Form($db);
// navigation form 	
$Form =  '<table class="noborder" width="50%">'."\n\t".'<tr>'."\n\t\t".'<th>'."\n\t\t\t";
$Form.=  '<a href="?action=list&yearweek='.date('Y\WW',strtotime($yearWeek."+3 days  -1 week"));
<<<<<<< HEAD
if ($optioncss != '') $Form.=  '&amp;optioncss='.$optioncss;
$Form.=  '">  &lt;&lt; '.$langs->trans("PreviousWeek").' </a>'."\n\t\t</th>\n\t\t<th>\n\t\t\t";
$Form.=  '<form name="goToDate" action="?action=goToDate" method="POST" >'."\n\t\t\t";
$Form.=   $langs->trans("GoToDate").': '.$form->select_date(-1,'toDate',0,0,0,"",1,1,1)."\n\t\t\t";;
$Form.=  '<input type="submit" value="Go" /></form>'."\n\t\t</th>\n\t\t<th>\n\t\t\t";
$Form.=  '<a href="?action=list&yearweek='.date('Y\WW',strtotime($yearWeek."+3 days +1 week"));
if ($optioncss != '') $Form.=  '&amp;optioncss='.$optioncss;
$Form.=  '">'.$langs->trans("NextWeek").' &gt;&gt; </a>'."\n\t\t</th>\n\t</tr>\n </table>\n";

//timesheet
$Form .='<form name="timesheet" action="?action=submit&yearweek='.$yearWeek.'" method="POST" >'; 
$Form .="\n<table class=\"noborder\" width=\"100%\">\n";
//headers

$headers=explode('||', TIMESHEET_HEADERS);
//$headers=explode('||', 'Project||TaskParent||Tasks||DateStart||DateEnd||Progress');
//$headersWidth=explode('||', TIMESHEET_HEADERS_WIDTH);
$headersWidth=explode('||', '');
for ($i=0;$i<7;$i++)
{
   $weekDays[$i]=date('d-m-Y',strtotime( $yearWeek.' +'.$i.' day'));
}
$_SESSION["timestamps"][$tmstp]["YearWeek"]=$yearWeek;
$_SESSION["timestamps"][$tmstp]["weekDays"]=$weekDays;
$Form .=timesheetHeader($headers,$headersWidth, $weekDays );
//total top

$num=count($headers);
$Form .="<tr>\n";
$Form .='<th colspan="'.$num.'" align="right"> TOTAL </th>
<th><div id="totalDay[0]">&nbsp;</div></th>
<th><div id="totalDay[1]">&nbsp;</div></th>
<th><div id="totalDay[2]">&nbsp;</div></th>
<th><div id="totalDay[3]">&nbsp;</div></th>
<th><div id="totalDay[4]">&nbsp;</div></th>
<th><div id="totalDay[5]">&nbsp;</div></th>
<th><div id="totalDay[6]">&nbsp;</div></th>
</tr>';

//line

$Form .=timesheetList($db,$headers,$userid,$yearWeek,$tmstp,$whitelistmode);

//total Bot
$Form .="<tr>\n";
$Form .='<th colspan="'.$num.'" align="right"> TOTAL </th>
<th><div id="totalDayb[0]">&nbsp;</div></th>
<th><div id="totalDayb[1]">&nbsp;</div></th>
<th><div id="totalDayb[2]">&nbsp;</div></th>
<th><div id="totalDayb[3]">&nbsp;</div></th>
<th><div id="totalDayb[4]">&nbsp;</div></th>
<th><div id="totalDayb[5]">&nbsp;</div></th>
<th><div id="totalDayb[6]">&nbsp;</div></th>
</tr>';

$Form .="</table >\n";

//form button
$Form .= '<input type="submit" value="'.$langs->trans('Save')."\" />\n";
$Form .= '<input type="button" value="'.$langs->trans('Cancel');
$Form .= '" onClick="document.location.href=\'?action=list&yearweek='.$yearWeek."\"/>\n";
$Form .= "</form>\n";
//Javascript
$timetype=TIMESHEET_TIME_TYPE;
//$Form .= ' <script type="text/javascript" src="timesheet.js"></script>'."\n";
$Form .= '<script type="text/javascript">'."\n\t";
$Form .= 'updateTotal(0,\''.$timetype.'\');'."\n\t";
$Form .= 'updateTotal(1,\''.$timetype.'\');'."\n\t";
$Form .= 'updateTotal(2,\''.$timetype.'\');'."\n\t";
$Form .= 'updateTotal(3,\''.$timetype.'\');'."\n\t";
$Form .= 'updateTotal(4,\''.$timetype.'\');'."\n\t";
$Form .= 'updateTotal(5,\''.$timetype.'\');'."\n\t";
$Form .= 'updateTotal(6,\''.$timetype.'\');'."\n";
$Form .= '</script>'."\n";
// $db->close();




print $Form;

// End of page
llxFooter();
$db->close();
?>
