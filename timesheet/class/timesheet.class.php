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
dol_include_once('/timesheet/class/projectTimesheet.class.php');
//require_once './projectTimesheet.class.php';

class timesheet extends Task 
{
        private $ProjectTitle		=	"Not defined";
        private $taskTimeId = array(0=>0,0,0,0,0,0,0);
        private $weekWorkLoad  = array(0=>0,0,0,0,0,0,0);
        private $fk_project2;
        private $taskParentDesc;
        private $companyName;
        private $companyId;
	

    public function __construct($db,$taskId) 
	{
		$this->db=$db;
		$this->id=$taskId;
		$this->date_end=strtotime('now -1 year');
		$this->date_start=strtotime('now -1 year');
	}

        /*public function initTimeSheet($weekWorkLoad,$taskTimeId) 
    {
            $this->weekWorkLoad=$weekWorkLoad;
            $this->taskTimeId=$taskTimeId;

    }*/
    public function getTaskInfo()
    {
        $Company=strpos(TIMESHEET_HEADERS, 'Company')===0;
        $taskParent=strpos(TIMESHEET_HEADERS, 'TaskParent')>0;
        $sql ='SELECT p.rowid,pt.dateo,pt.datee, pt.planned_workload, pt.duration_effective';
        if(TIMESHEET_HIDE_REF==1){
            $sql .= ',p.title as title, pt.label as label';
            if($taskParent)$sql .= ',pt.fk_task_parent,ptp.label as taskParentLabel';	        	
        }else{
            $sql .= ",CONCAT(p.`ref`,' - ',p.title) as title";
            $sql .= ",CONCAT(pt.`ref`,' - ',pt.label) as label";
            if($taskParent)$sql .= ",pt.fk_task_parent,CONCAT(ptp.`ref`,' - ',ptp.label) as taskParentLabel";	
        }
        if($Company)$sql .= ',p.fk_soc as companyId,s.nom as companyName';

        $sql .=" FROM ".MAIN_DB_PREFIX."projet_task AS pt";
        $sql .=" LEFT JOIN ".MAIN_DB_PREFIX."projet as p";
        $sql .=" ON pt.fk_projet=p.rowid";
        if($taskParent){
            $sql .=" LEFT JOIN ".MAIN_DB_PREFIX."projet_task as ptp";
            $sql .=" ON pt.fk_task_parent=ptp.rowid";
        }
        if($Company){
            $sql .=" LEFT JOIN ".MAIN_DB_PREFIX."societe as s";
            $sql .=" ON p.fk_soc=s.rowid";
        }
        $sql .=" WHERE pt.rowid ='".$this->id."'";
        #$sql .= "WHERE pt.rowid ='1'";
        dol_syslog(get_class($this)."::fetchtasks sql=".$sql, LOG_DEBUG);


        $resql=$this->db->query($sql);
        if ($resql)
        {

                if ($this->db->num_rows($resql))
                {

                        $obj = $this->db->fetch_object($resql);

                        $this->description			= $obj->label;
                        $this->fk_project2                      = $obj->rowid;
                        $this->ProjectTitle			= $obj->title;
                        #$this->date_start			= strtotime($obj->dateo.' +0 day');
                        #$this->date_end			= strtotime($obj->datee.' +0 day');
                        $this->date_start			= $this->db->jdate($obj->dateo);
                        $this->date_end			= $this->db->jdate($obj->datee);
                        $this->duration_effective           = $obj->duration_effective;		// total of time spent on this task
                        $this->planned_workload             = $obj->planned_workload;
                        if($taskParent){
                            $this->fk_task_parent               = $obj->fk_task_parent;
                            $this->taskParentDesc               =$obj->taskParentLabel;
                        }
                        if($Company){
                            $this->companyName                  =$obj->companyName;
                            $this->companyId                    =$obj->companyId;
                        }
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

        $sql = "SELECT ptt.rowid, ptt.task_duration, ptt.task_date";	
        $sql .= " FROM ".MAIN_DB_PREFIX."projet_task_time AS ptt";
        $sql .= " WHERE ptt.fk_task='".$this->id."' ";
        $sql .= " AND (ptt.fk_user='".$userid."') ";
       # $sql .= "AND WEEKOFYEAR(ptt.task_date)='".date('W',strtotime($yearWeek))."';";
        #$sql .= "AND YEAR(ptt.task_date)='".date('Y',strtotime($yearWeek))."';";
        $sql .= " AND (ptt.task_date>=FROM_UNIXTIME('".strtotime($yearWeek)."')) ";
        $sql .= " AND (ptt.task_date<FROM_UNIXTIME('".strtotime($yearWeek.' + 7 days')."'));";

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
                        $this->taskTimeId[$day]= ($obj->rowid)?($obj->rowid):0;
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
    
    
       public function getFormLine( $yearWeek,$lineNumber,$headers)
    {
       if(empty($yearWeek)||empty($headers))
           return '<tr>ERROR: wrong parameters for getFormLine'.empty($yearWeek).'|'.empty($headers).'</tr>';
        
    $timetype=TIMESHEET_TIME_TYPE;
    $dayshours=TIMESHEET_DAY_DURATION;
    $hidezeros=TIMESHEET_HIDE_ZEROS;

    $html= '<tr class="'.(($lineNumber%2=='0')?'pair':'impair').'">'."\n"; 
    //title section
     foreach ($headers as $key => $title){
         $html.="\t<th align=\"left\">";
         switch($title){
             case 'Project':
                 if(file_exists("../projet/card.php")||file_exists("../../projet/card.php")){
                    $html.='<a href="'.DOL_URL_ROOT.'/projet/card.php?id='.$this->fk_project2.'">'.$this->ProjectTitle.'</a>';
                 }else{
                    $html.='<a href="'.DOL_URL_ROOT.'/projet/fiche.php?id='.$this->fk_project2.'">'.$this->ProjectTitle.'</a>';
                     
                 }
                 break;
             case 'TaskParent':
                 $html.='<a href="'.DOL_URL_ROOT.'/projet/tasks/task.php?id='.$this->fk_task_parent.'&withproject='.$this->fk_project2.'">'.$this->taskParentDesc.'</a>';
                 break;
             case 'Tasks':
                 $html.='<a href="'.DOL_URL_ROOT.'/projet/tasks/task.php?id='.$this->id.'&withproject='.$this->fk_project2.'">'.$this->description.'</a>';
                 break;
             case 'DateStart':
                 $html.=$this->date_start?date('d/m/y',$this->date_start):'';
                 break;
             case 'DateEnd':
                 $html.=$this->date_end?date('d/m/y',$this->date_end):'';
                 break;
             case 'Company':
                 $html.='<a href="'.DOL_URL_ROOT.'/societe/soc.php?socid='.$this->companyId.'">'.$this->companyName.'</a>';
                 break;
             case 'Progress':
                 $html .=$this->parseTaskTime($this->duration_effective).'/';
                if($this->planned_workload)
                {
                     $html .= $this->parseTaskTime($this->planned_workload).'('.floor($this->duration_effective/$this->planned_workload*100).'%)';
                }else{
                    $html .= "-:--(-%)";
                }
                 break;
         }
  
         $html.="</th>\n";
     }
    
  // day section
        foreach ($this->weekWorkLoad as $dayOfWeek => $dayWorkLoadSec)
        {
                $today= strtotime($yearWeek.' +'.($dayOfWeek).' day  ');
                # to avoid editing if the task is closed 
                if ($timetype=="days")
                {
                    $dayWorkLoad=$dayWorkLoadSec/3600/$dayshours;
                }else {
                    $dayWorkLoad=date('H:i',mktime(0,0,$dayWorkLoadSec));
                }
              
                if((empty($this->date_start) || ($this->date_start <= $today +86399)) && (empty($this->date_end) ||($this->date_end >= $today )))
                {             
                    $html .= '<th><input type="text" id="task['.$lineNumber.']['.$dayOfWeek.']" ';
                    $html .= 'name="task['.$this->id.']['.$dayOfWeek.']" ';
                    $html .=' value="'.((($hidezeros==1) && ($dayWorkLoadSec==0))?"":$dayWorkLoad);
                    $html .='" maxlength="5" style="width: 90%;'.(($dayWorkLoadSec==0)?'':' background:#f0fff0; ').'" ';
                    $html .='onkeypress="return regexEvent(this,event,\'timeChar\')" ';
                    $html .= 'onblur="regexEvent(this,event,\''.$timetype.'\');updateTotal('.$dayOfWeek.',\''.$timetype.'\')" />';
                    $html .= "</th>\n";                    
                }else
                {
                    $html .= '<th> <div id="task['.$this->id.']['.$dayOfWeek.']">'.$dayWorkLoad."</div></th>\n";
                }
        }
        $html .= "</tr>\n";
        return $html;

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
/*
    public function isOpenThisWeek($yearWeek)
    {
            $yearWeekMonday=strtotime($yearWeek.' +0 days');
            $yearWeekSunday=strtotime($yearWeek.' +6 day');
 
            $projectstatic=new ProjectTimesheet($this->db);
	    $projectstatic->fetch($this->fk_project2);
            if((empty($this->date_start) || ($this->date_start <= $yearWeekSunday)) 
                    && (empty($this->date_end) ||($this->date_end >= $yearWeekMonday )) 
                    && ($projectstatic->isOpen($yearWeekMonday, $yearWeekSunday)))
            {	
                    return true;
            }else
            {	
                    #return true;
                    return FALSE;

            }
    }
 * */
 
    public function getTaskTab()
    {
        $taskTab=array();
        $taskTab[]='id';
        $taskTab['id']=$this->id;
        $taskTab[]='weekWorkLoad';
        $taskTab['weekWorkLoad']=array();
        $weekWorkload=array();
        
        foreach((array)$this->weekWorkload as $key => $value)
        {
            $taskTab['weekWorkLoad'][$key]=$value;
        }
        $taskTab[]='taskTimeId';
        $taskTab['taskTimeId']=array();
        foreach($this->taskTimeId as $key => $value)
        {
           $taskTab['taskTimeId'][$key]=$this->taskTimeId[$key];
        }
        return $taskTab;
    }
public function updateTimeUsed()
    {
          $sql ="UPDATE ".MAIN_DB_PREFIX."projet_task AS pt "
               ."SET pt.duration_effective=(SELECT SUM(ptt.task_duration) "
               ."FROM ".MAIN_DB_PREFIX."projet_task_time AS ptt "
               ."WHERE ptt.fk_task ='".$this->id."') "
               ."WHERE pt.rowid='".$this->id."' ";
   
            dol_syslog(get_class($this)."::UpdateTimeUsed sql=".$sql, LOG_DEBUG);


            $resql=$this->db->query($sql);
            if ($resql)
            {
                    return 1;
            }
            else
            {
                    $this->error="Error ".$this->db->lasterror();
                    dol_syslog(get_class($this)."::fetch ".$this->error, LOG_ERR);

                    return -1;
            }	

    }
    function parseTaskTime($taskTime){
        
        $ret=floor($taskTime/3600).":".str_pad (floor($taskTime%3600/60),2,"0",STR_PAD_LEFT);
        
        return $ret;
        //return '00:00';
          
    }
	
	
}

?>
