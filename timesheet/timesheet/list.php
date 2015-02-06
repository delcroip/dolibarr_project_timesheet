<?php

/* 
 * Copyright (C) 2014 delcroip <delcroip@gmail.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
$yearWeek=$_SESSION["yearWeek"];
$db=$_SESSION["db"];
dol_include_once('/timesheet/class/timesheet.class.php');
 # will generate the form line

// navigation form 	
$Form =  '<table class="noborder" width="50%">
            <tr> 
                <th> 
                    <a href="?action=list&yearweek='.date('Y\WW',strtotime($yearWeek."+3 days  -1 week")).
                    '">  &lt&lt '.$langs->trans("PreviousWeek").' </a> 
                </th> 
                <th>
                    <form name="goToDate" action="?action=goToDate" method="POST" >
                     '.$langs->trans("GoToDate").': <input type="date" id="toDate" name="toDate" size="10" value="'.date('d/m/Y',strtotime( $yearWeek.' +0 day')).'"/>   '.
                    '<input type="submit" value="Go" /></form>
                </th> 
                <th> 
                    <a href="?action=list&yearweek='.date('Y\WW',strtotime($yearWeek."+3 days +1 week")).
                    '">'.$langs->trans("NextWeek").' &gt&gt </a> 
                 </th>
            </tr> 
          </table>
          ';

$weekDays=array();

for ($i=0;$i<7;$i++)
{
   $weekDays[$i]=date('d-m-Y',strtotime( $yearWeek.' +'.$i.' day'));
}


// Show the title of the form 
$Form .='<form name="timesheet" action="?action=submit&yearweek='.$yearWeek.'" method="POST" > 
          <table class="noborder" width="100%">
            <tr class="liste_titre" >
                <th width="19%">
                    '.$langs->trans('Project').'
                </th>
                <th width="18%">
                    '.$langs->trans('Tasks').'
                </th>
                <th width="7%">
                    '.$langs->trans('DateStart').'
                </th>
                <th width="7%">
                    '.$langs->trans('DateEnd').'
                </th>
                <th width="7%">
                     '.$langs->trans(date('l',strtotime($weekDays[0]))).'<br>'.$weekDays[0]."
                </th>
                <th width='7%'> 
                    ".$langs->trans(date('l',strtotime($weekDays[1]))).'<br>'.$weekDays[1]."
                </th> 
                <th width='7%'> 
                    ".$langs->trans(date('l',strtotime($weekDays[2]))).'<br>'.$weekDays[2]."
                </th>
                <th width='7%'> 
                    ".$langs->trans(date('l',strtotime($weekDays[3]))).'<br>'.$weekDays[3]."
                </th>
                <th width='7%'> 
                    ".$langs->trans(date('l',strtotime($weekDays[4]))).'<br>'.$weekDays[4]."
                 </th>
                <th width='7%'> 
                    ".$langs->trans(date('l',strtotime($weekDays[5]))).'<br>'.$weekDays[5]."
                </th>
                <th width='7%'> 
                    ".$langs->trans(date('l',strtotime($weekDays[6]))).'<br>'.$weekDays[6].'
                    <input type="hidden" name="yearWeek" value="'.$yearWeek.'" /> 
                </th>
            </tr>
            ';
//retrives and show all the task where the user is defined as responsible or contributor
$tasksList=array();
$sql ="SELECT DISTINCT element_id FROM ".MAIN_DB_PREFIX."element_contact "; 
$sql.="WHERE (fk_c_type_contact='181' OR fk_c_type_contact='180') AND fk_socpeople='".$user->id."'";

dol_syslog("timesheet::getTasksTimesheet sql=".$sql, LOG_DEBUG);
$resql=$db->query($sql);
if ($resql)
{
        $num = $db->num_rows($resql);
        $i = 0;
        // Loop on each record found, so each couple (project id, task id)
        while ($i < $num)
        {
                $error=0;
                $obj = $db->fetch_object($resql);
                $tasksList[$i] 	= NEW timesheet($db, $obj->element_id);
                $i++;
        }
        $db->free($resql);
}
else
{
        dol_print_error($db);
}

//$tasks=getTasksTimesheet($db,$user->id);



 /*
 * create the Session parameters
 */
$tmstp=time();
if(!isset($_SESSION["timestamps"])){
        $_SESSION["timestamps"]=array();
        $_SESSION["timestamps"][0]=1;
}

//FIXME: LIMIT the size of the timestamps table
//FIXME: ERROR handling: timestamp already present
$_SESSION['timestamps'][$tmstp]=array() ;
//to avoid resend when refresh
$_SESSION["timestamps"][$tmstp]["sent"]=false;
//Yearweek stored so to avoid modifying an otherweek by hacking
$_SESSION["timestamps"][$tmstp]["YearWeek"]=$yearWeek;
$_SESSION["timestamps"][$tmstp]["weekDays"]=$weekDays;
//create the task list:
$_SESSION["timestamps"][$tmstp]["YearWeek"]['tasks']=array();
$i=0;
foreach($tasksList as $row)
{
         dol_syslog("Timesheet::list.php task=".$row->id, LOG_DEBUG);
        $row->getTaskInfo();
        if($row->isOpenThisWeek($yearWeek))
        {
                $row->getActuals($yearWeek,$user->id); 
                $_SESSION["timestamps"][$tmstp]['tasks'][$row->id]=array();
                $_SESSION["timestamps"][$tmstp]['tasks'][$row->id]=$row->getTaskTab();
                $Form.=$row->getFormLineSecured( $yearWeek,$i,TIMESHEET_TIME_TYPE,TIMESHEET_DAY_DURATION); 
                //$Form.=$row->getFormLine( $yearWeek,$i);
                $i++;
                
        }

}
// Total fields
$Form .= '
    </table>
    <input type="hidden" name="timestamp" value="'.$tmstp.'"/>
    <input type="hidden" id="numberOfLines" name="numberOfLines" '.
    'value="'.$i.'"/>
        ';
$Form .= '<table class="noborder"  width="100%">
            <tr>
                <th width="19%"></th>
                <th width="18%"></th>
                <th width="7%"></th>
                <th width="7%">Total</th>
                <th width="7%"><div id="totalDay[0]">&nbsp;</div></th>
                <th width="7%"><div id="totalDay[1]">&nbsp;</div></th>
                <th width="7%"><div id="totalDay[2]">&nbsp;</div></th>
                <th width="7%"><div id="totalDay[3]">&nbsp;</div></th>
                <th width="7%"><div id="totalDay[4]">&nbsp;</div></th>
                <th width="7%"><div id="totalDay[5]">&nbsp;</div></th>
                <th width="7%"><div id="totalDay[6]">&nbsp;</div></th>
            </tr>
        </table>';
$Form .= '<input type="submit" value="'.$langs->trans('Save').'" />
         <input type="button" value="'.$langs->trans('Cancel').'" onClick="document.location.href=\'?action=list&yearweek='.$yearWeek.'\'"/>';

$Form .='</form> 
         <script type="text/javascript" src="timesheet.js"></script>
         <script type="text/javascript">
         updateTotal(0,\''.TIMESHEET_TIME_TYPE.'\');updateTotal(1,\''.TIMESHEET_TIME_TYPE.'\');updateTotal(2,\''.TIMESHEET_TIME_TYPE.'\');updateTotal(3,\''.TIMESHEET_TIME_TYPE.'\');updateTotal(4,\''.TIMESHEET_TIME_TYPE.'\');updateTotal(5,\''.TIMESHEET_TIME_TYPE.'\');updateTotal(6,\''.TIMESHEET_TIME_TYPE.'\');
         </script>';
// $db->close();
echo$Form;



