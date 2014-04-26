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


/*Class to handle a line of timesheet*/
#require_once('mysql.class.php');
require_once DOL_DOCUMENT_ROOT."/core/class/commonobject.class.php";
require_once DOL_DOCUMENT_ROOT.'/projet/class/task.class.php';


class timesheet extends Task 
{
        private $ProjectTitle		=	"Not defined";
	private $taskTimeId = array(0=>0,0,0,0,0,0,0);
        private $weekWorkLoad  = array(0=>0,0,0,0,0,0,0);
	

    public function __construct($db,$taskId) 
	{
		$this->db=$db;
		$this->id=$taskId;
		$this->date_end=strtotime('now -1 year');
		$this->date_start=strtotime('now -1 year');
	}
	
	public function getTaskInfo()
	{
		$sql = "SELECT p.title, pt.label,pt.dateo,pt.datee ";	
		$sql .= "FROM ".MAIN_DB_PREFIX."projet_task AS pt ";
		$sql .= "LEFT JOIN ".MAIN_DB_PREFIX."projet as p ";
		$sql .= "ON pt.fk_projet=p.rowid ";
		$sql .= "WHERE pt.rowid ='".$this->id."';";
		#$sql .= "WHERE pt.rowid ='1'";
		dol_syslog(get_class($this)."::fetchtasks sql=".$sql, LOG_DEBUG);
		
		
		$resql=$this->db->query($sql);
		if ($resql)
		{
			
			if ($this->db->num_rows($resql))
			{
				
				$obj = $this->db->fetch_object($resql);
				$this->description			= $obj->label;
				$this->ProjectTitle			= $obj->title;
				#$this->date_start			= strtotime($obj->dateo.' +0 day');
				#$this->date_end			= strtotime($obj->datee.' +0 day');
				$this->date_start			= $this->db->jdate($obj->dateo);
				$this->date_end			= $this->db->jdate($obj->datee);
			}
			$this->db->free($resql);
			return 1;
		}
		else
		{
			$this->error="Error ".$this->db->lasterror();
			dol_syslog(get_class($this)."::fetch ".$this->error, LOG_ERR);

			return -1;
		}	
	}
		 
	public function getActuals( $yearWeek,$userid)
	{
            
		$sql = "SELECT ptt.rowid, ptt.task_duration, ptt.task_date ";	
		$sql .= "FROM ".MAIN_DB_PREFIX."projet_task_time AS ptt ";
		$sql .= "WHERE ptt.fk_task='".$this->id."' ";
                $sql .= "AND (ptt.fk_user='".$userid."') ";
               # $sql .= "AND WEEKOFYEAR(ptt.task_date)='".date('W',strtotime($yearWeek))."';";
                #$sql .= "AND YEAR(ptt.task_date)='".date('Y',strtotime($yearWeek))."';";
                $sql .= "AND (ptt.task_date>FROM_UNIXTIME('".strtotime($yearWeek.' -1 day')."')) ";
                $sql .= "AND (ptt.task_date<FROM_UNIXTIME('".strtotime($yearWeek.' +6 day')."'));";
		
		dol_syslog(get_class($this)."::fetchActuals sql=".$sql, LOG_DEBUG);
		
		
		$resql=$this->db->query($sql);
		if ($resql)
		{
			
			$num = $this->db->num_rows($resql);
			$i = 0;
			// Loop on each record found, so each couple (project id, task id)
			 while ($i < $num)
			{
				$error=0;
				$obj = $this->db->fetch_object($resql);
				$day=intval(date('N',strtotime($obj->task_date)))-1;
                                //$day=(intval(date('w',strtotime($obj->task_date)))+1)%6;
                                // if several tasktime in one day then only the last is used
                                $this->weekWorkLoad[$day] =  $obj->task_duration;
                                $this->taskTimeId[$day]= $obj->rowid;
				$i++;
			}
			$this->db->free($resql);
                        return 1;
                 }
		else
		{
			$this->error="Error ".$this->db->lasterror();
			dol_syslog(get_class($this)."::fetch ".$this->error, LOG_ERR);

			return -1;
		}
	}	 
	

	
	public function getFormLine( $yearWeek,$lineNumber)
	{

	  //don't show task without open day in the week
		#$dateStart=strtotime($yearWeek);
		 # insert the task id and the form line to retrieve the data later 
		$tableRow = "<tr>";
		//$tableRow .= '<input type="hidden" name="task_'.$lineNumber.'" ';
		//$tableRow .='value="'.$this->id.'" />';
		$tableRow .= "<th>".$this->ProjectTitle."</th><th>".$this->description."</th>";
		$tableRow .= "<th>".date('d/m/y',$this->date_start)."</th><th>".date('d/m/y',$this->date_end)."</th>";
                $tableRow .='<input type="hidden" name="task['.$lineNumber.'][taskid]" ';
		$tableRow .='value="'.$this->id.'"/> ';               
		foreach ($this->weekWorkLoad as $dayOfWeek => $dayWorkLoadSec)
		{
			$today= strtotime($yearWeek.' +'.$dayOfWeek.' day');
			# to avoid editing if the task is closed
                        $dayWorkLoad=date('H:i',mktime(0,0,$dayWorkLoadSec));
			if(($this->date_start > $today) OR ($this->date_end < $today ))
			{
				$tableRow .= "<th>".$dayWorkLoad."</th>";
			}else
			{
                                $tableRow .='<th><input type="hidden" name="task['.$lineNumber.'][weekDays]['.$dayOfWeek.'][tasktimeid]" ';
				$tableRow .='value="'.$this->taskTimeId[$dayOfWeek].'" /> ';
				$tableRow .='<input type="text" name="task['.$lineNumber.'][weekDays]['.$dayOfWeek.'][value]" ';
				$tableRow .='id="task_'.$lineNumber.'_'.$dayOfWeek.'" ';
                                $tableRow .=' value="'.$dayWorkLoad.'" maxlength="5" style="width: 90%" ';
                                $tableRow .='onkeydown="return regexEvent(this,event,\'timeChar\')" ';
                                $tableRow .='onblur="regexEvent(this,event,\'time\')" ';
                                $tableRow .= '/> </th>';
			}
		}
		$tableRow .= "</tr>";
		return $tableRow;
	  
	}

	public function test(){
		$Result=$this->id.' / ';
		$Result.=$this->description.' / ';		
		$Result.=$this->ProjectTitle.' / ';		
		$Result.=$this->date_start.' / ';
		$Result.=$this->date_end.' / ';
		//$Result.=$this->$weekWorkLoad.' / '; 
		return $Result;
    }
	
	public function isOpenThisWeek($yearWeek)
	{
		$yearWeekMonday=strtotime($yearWeek.' +0 days');
		$yearWeekSunday=strtotime($yearWeek.' +6 day');
		if(($this->date_start <= $yearWeekSunday) AND ($this->date_end >= $yearWeekMonday ))
		{	
			return true;
		}else
		{	
			#return true;
			return FALSE;

		}
	}
	
	
}

?>
