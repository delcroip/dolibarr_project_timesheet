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

dol_include_once('/timesheet/class/timesheet.class.php');
 # will generate the form line
	function getTasksTimesheet($db,$userId)
	{	
		$tasksList=array();
		$sql ="SELECT element_id FROM ".MAIN_DB_PREFIX."element_contact "; 
		$sql.="WHERE fk_c_type_contact='181' AND fk_socpeople='".$userId."'";
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
		return $tasksList;
	}
	function getTimesheetForm($db,$userId, $yearWeek,$action,$method='POST')
	{

		
		$Form =  '<table class="noborder" width="100%">
                            <tr> 
                                <th> 
                                    <a href="?action=list&yearweek='.date('Y\WW',strtotime($yearWeek." -1 week")).
                                    '">  &lt&lt Pevious week </a> 
                                </th> 
                                <th>
                                    <form name="goToDate" action="?action=goToDate" method="'.$method.'" >
                                     Go to date: <input type="date" name="toDate" size="10" value="'.date('d/m/Y',strtotime( $yearWeek.' +0 day')).'"/>   '.
                                    '<input type="submit" value="Go" /></form>
                                </th> 
                                <th> 
                                    <a href="?action=list&yearweek='.date('Y\WW',strtotime($yearWeek." +1 week")).
                                    '">  Next week &gt&gt </a> 
                                 </th>
                            </tr> 
                          </table>
                          ';
                
                
		
		$Form .='<form name="timesheet" action="'.$action.'" method="'.$method.'" > 
                          <table class="noborder" width="100%">
                            <tr class="liste_titre" >
                                <th>
                                    Project
                                </th>
                                <th>
                                    Task
                                </th>
                                <th>
                                    Start date
                                </th>
                                <th>
                                    End date
                                </th>
                                <th> 
                                    <input type="hidden" name="weekDays[0]" value="'.date('d-m-Y',strtotime( $yearWeek.' +0 day')).
                                     '"/>
                                     '.date('l \<\b\r\>d/m/y',strtotime( $yearWeek.' +0 day'))."
                                </th>
                                <th> 
                                    <input type='hidden' name=weekDays[1] value='".date('d-m-Y',strtotime( $yearWeek.' +1 day')).
                                    "'/>
                                    ".date('l \<\b\r\>d/m/y',strtotime( $yearWeek.' +1 day'))."</th> 
                                <th> 
                                    <input type='hidden' name=weekDays[2] value='".date('d-m-Y',strtotime( $yearWeek.' +2 day')).
                                    "'/>
                                    ".date('l \<\b\r\>d/m/y',strtotime( $yearWeek.' +2 day'))."</th>
                                <th> 
                                    <input type='hidden' name=weekDays[3] value='".date('d-m-Y',strtotime( $yearWeek.' +3 day')).
                                    "'/>
                                    ".date('l \<\b\r\>d/m/y',strtotime( $yearWeek.' +3 day'))."
                                </th>
                                <th> 
                                    <input type='hidden' name=weekDays[4] value='".date('d-m-Y',strtotime( $yearWeek.' +4 day')).
                                    "'/>
                                    ".date('l \<\b\r\>d/m/y',strtotime( $yearWeek.' +4 day'))."
                                        
                                 </th>
                                <th> 
                                    <input type='hidden' name=weekDays[5] value='".date('d-m-Y',strtotime( $yearWeek.' +5 day')).
                                    "'/>
                                    ".date('l \<\b\r\>d/m/y',strtotime( $yearWeek.' +5 day'))."
                                </th>
                                <th> 
                                    <input type='hidden' name=weekDays[6] value='".date('d-m-Y',strtotime( $yearWeek.' +6 day')).
                                    "'/>
                                    ".date('l \<\b\r\>d/m/y',strtotime( $yearWeek.' +6 day')).'
                                    <input type="hidden" name="yearWeek" value="'.$yearWeek.'" /> 
                                </th>
                            </tr>
                            ';

		$tasks=getTasksTimesheet($db,$userId);
		$i=0;
		
		foreach($tasks as $row)
		{
			$row->getTaskInfo();
			if($row->isOpenThisWeek($yearWeek))
			{
				$row->getActuals($yearWeek,$userId);
				$Form.=$row->getFormLine( $yearWeek,$i); 
                                $i++;
			}
			
		}
		$Form .= '<tr><th><input type="hidden" id="numberOfLines" name="numberOfLines" ';
		$Form .='value="'.$i.'"/></th>';
                $Form .='<th></th><th></th><th>Total</th>
                        <th><div id="totalDay[0]">&nbsp;</div></th>
                        <th><div id="totalDay[1]">&nbsp;</div></th>
                        <th><div id="totalDay[2]">&nbsp;</div></th>
                        <th><div id="totalDay[3]">&nbsp;</div></th>
                        <th><div id="totalDay[4]">&nbsp;</div></th>
                        <th><div id="totalDay[5]">&nbsp;</div></th>
                        <th><div id="totalDay[6]">&nbsp;</div></th>
                            </tr>';
		$Form .="</table > ";
		$Form .= '<input type="submit" value="SUBMIT" />';
		$Form .="</form> ";
                $Form .='<script type="text/javascript">';
                $Form .='updateTotal(0);updateTotal(1);updateTotal(2);updateTotal(3);';
                $Form .='updateTotal(4);updateTotal(5);updateTotal(6);';
                $Form .='</script>';
               // $db->close();
		return $Form;
	}

        

