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

		
		$Form =  '<table class="noborder" width="100%">';
                $Form .= '<tr> <th> <a href="?action=list&yearweek='.date('Y\WW',strtotime($yearWeek." -1 week")).'">  << Pevious week </a> </th> ';
                $Form .= '<form name="goToDate" action="?action=goToDate" method="'.$method.'" >';
                $Form .= '<th> Go to date:</th><th><input type="date" name="toDate" size="10" value="'.date('d/m/Y',strtotime( $yearWeek.' +0 day')).'"/>   ';
                $Form .= '<th><input type="submit" value="Go" /></th></form> ';
                $Form .= '<th> <a href="?action=list&yearweek='.date('Y\WW',strtotime($yearWeek." +1 week")).'">  Next week >> </a> </th> </tr> ';
		$Form .= '<tr class="liste_titre" >';
		$Form .= '<th >Project</th><th>Task</th>';
		$Form .= "<th>Start date</th><th>End date</th>";
                $Form .= '<form name="timesheet" action="'.$action.'" method="'.$method.'" > ';
		$Form .= "<th> <input type='hidden' name=weekDays[0] value='".date('d-m-Y',strtotime( $yearWeek.' +0 day'));
                $Form .= "'/>".date('l \<\b\r\>d/m/y',strtotime( $yearWeek.' +0 day'))."</th>";
		$Form .= "<th> <input type='hidden' name=weekDays[1] value='".date('d-m-Y',strtotime( $yearWeek.' +1 day'));
                $Form .="'/>".date('l \<\b\r\>d/m/y',strtotime( $yearWeek.' +1 day'))."</th>";
		$Form .= "<th> <input type='hidden' name=weekDays[2] value='".date('d-m-Y',strtotime( $yearWeek.' +2 day'));
                $Form .="'/>".date('l \<\b\r\>d/m/y',strtotime( $yearWeek.' +2 day'))."</th>";
		$Form .= "<th> <input type='hidden' name=weekDays[3] value='".date('d-m-Y',strtotime( $yearWeek.' +3 day'));
                $Form .="'/>".date('l \<\b\r\>d/m/y',strtotime( $yearWeek.' +3 day'))."</th>";
		$Form .= "<th> <input type='hidden' name=weekDays[4] value='".date('d-m-Y',strtotime( $yearWeek.' +4 day'));
                $Form .="'/>".date('l \<\b\r\>d/m/y',strtotime( $yearWeek.' +4 day'))."</th>";
		$Form .= "<th> <input type='hidden' name=weekDays[5] value='".date('d-m-Y',strtotime( $yearWeek.' +5 day'));
                $Form .="'/>".date('l \<\b\r\>d/m/y',strtotime( $yearWeek.' +5 day'))."</th>";
		$Form .= "<th> <input type='hidden' name=weekDays[6] value='".date('d-m-Y',strtotime( $yearWeek.' +6 day'));
                $Form .="'/>".date('l \<\b\r\>d/m/y',strtotime( $yearWeek.' +6 day'))."</th>";
		$Form .= "</tr>";
                $Form .= '<input type="hidden" name="yearWeek" value="'.$yearWeek.'" /> ';
		$tasks=getTasksTimesheet($db,$userId);
		$i=0;
		
		foreach($tasks as $key=>$row)
		{
			$row->getTaskInfo();
			if($row->isOpenThisWeek($yearWeek))
			{
				$row->getActuals($yearWeek,$userId);
				$Form.=$row->getFormLine( $yearWeek,$key);
                                $i++;
			}
			
		}
		$Form .= '<input type="hidden" name="numberOfLines" ';
		$Form .='value="'.$i.'"/>';		
		$Form .="</table > ";
		$Form .= '<input type="submit" value="SUBMIT" />';
		$Form .="</form> ";
               // $db->close();
		return $Form;
	}

        

