<?php
/* 
 * Copyright (C) 2015 delcroip <delcroip@gmail.com>
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
 * 
 */

/*Project data
 * 
    public $element = 'project';    //!< Id that identify managed objects
    public $table_element = 'projet';  //!< Name of table without prefix where object is stored
    public $table_element_line = 'projet_task';
    public $fk_element = 'fk_projet';
    protected $ismultientitymanaged = 1;  // 0=No test on entity, 1=Test with field entity, 2=Test with link by societe

    var $id;
    var $ref;
    var $description;
    var $statut;
    var $title;
    var $date_start;
    var $date_end;
    var $socid;
    var $user_author_id;    //!< Id of project creator. Not defined if shared project.
    var $public;      //!< Tell if this is a public or private project
    var $note_private;
    var $note_public;
    var $statuts_short;
    var $statuts;
    var $oldcopy;
 * 
    */
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';

class ProjectTimesheet extends Project
{

	

    public function __construct($db) 
	{
            $this->db = $db;
	}
        
    public function initBasic($id,$ref,$title,$date_open,$date_end) 
	{
            $this->id =$id;
            $this->ref =$ref;
            $this->title= $title;
            $this->date_start = $date_open;
            $this->date_end=$date_end;
	}
        
    public function isOpen($startDate,$stopDate)
    {

            if((empty($this->date_start) || ($this->date_start <= $stopDate)) 
                    && (empty($this->date_end) ||($this->date_end >= $startDate )))
 
            {	
                    return true;
            }else
            {	
                    #return true;
                    return FALSE;

            }
    }

    
    public function getHTMLreport($startDay,$stopDay,$mode,$langsMonth){
    //
    
    $HTMLuser='';
    $HTMLTask='';
    $HTMLproject='';
    $taskTotal=0;
    $projectTotal=0;
    $userTotal=0;
    //mode 1, PER USER
    //get the list of user
    //get the list of task per user
    //sum user
    //mode 2, PER TASK
    //list of task
    //list of user per task
    switch ($mode) {
        case 1:
            $sql='SELECT DISTINCT usr.firstname, usr.lastname, usr.rowid as userId '
                .'FROM '.MAIN_DB_PREFIX.'user as usr ' 
                .'JOIN '.MAIN_DB_PREFIX.'element_contact as ctc '
                .'ON ctc.fk_socpeople=usr.rowid '
                .'JOIN '.MAIN_DB_PREFIX.'projet_task as tsk '
                .'ON ctc.element_id=tsk.rowid '
                .'WHERE tsk.fk_projet="'.$this->id.'" ';  
            
            /*$sql='SELECT DISTINCT firstname,lastname,userId '
                .'FROM view_pjtTskUsr '
                . 'WHERE projectId="'.$this->id.'" ';  */ 
            dol_syslog("timesheet::report::userList sql=".$sql, LOG_DEBUG);
            $resql=$this->db->query($sql);
            $numUsers=0;
            $userList=array();
            if ($resql)
            {
                    $numUsers = $this->db->num_rows($resql);
                    $i = 0;
                   
                    // Loop on each record found, so each couple (project id, task id)
                    while ($i < $numUsers)
                    {
                            $error=0;
                            $obj = $this->db->fetch_object($resql);
                            //$userList[$obj->userId]=$obj->firstname.' '.$obj->lastname;
                            $userList[$obj->userId]=$obj->firstname.' '.$obj->lastname;
                            $i++;
                            
                    }
                    $this->db->free($resql);
            }else
            {
                    dol_print_error($this->db);
            }
 
            foreach($userList as $rowid => $user) {
                
                $sql='SELECT ptt.fk_task,tsk.`ref` as taskRef,tsk.label as taskTitle,'
                    .'SUM(ptt.task_duration) as duration '
                    .'FROM '.MAIN_DB_PREFIX.'projet_task_time as ptt '
                    .'JOIN '.MAIN_DB_PREFIX.'projet_task as tsk ON tsk.rowid=fk_task '
                    .'WHERE fk_user="'.$rowid.'" '
                    .'AND tsk.fk_projet="'.$this->id.'" '
                    .'AND task_date>=FROM_UNIXTIME("'.$startDay.'") '
                    .'AND task_date<=FROM_UNIXTIME("'.$stopDay.'") '
                    .'GROUP BY ptt.fk_task '
                    .'ORDER BY ptt.fk_task ASC';  
                
                        
                dol_syslog("timesheet::report::userTaskList sql=".$sql, LOG_DEBUG);
                $resql=$this->db->query($sql);
                $numTask=0;
                if ($resql)
                {
                    
                        $numTask = $this->db->num_rows($resql);
                        //to mask an user if there is no task found
                       
                        $i = 0;

                        //
                        while ($i < $numTask)
                        {
                                $error=0;
                                $obj = $this->db->fetch_object($resql);
                                $taskTotal=intval($obj->duration);
                                $TotalSec=$taskTotal%60;
                                $TotalMin=(($taskTotal-$TotalSec)/60)%60;
                                $TotalHours=($taskTotal-$TotalMin)/3600;
                                $HTMLuser.='<tr class="pair"><th></th><th>'.$obj->taskRef
                                         .' - '.$obj->taskTitle.'</th><th>'
                                         .$TotalHours.':'.sprintf("%02s",$TotalMin).'</th></tr>
                                    ';
                                $HTMLuser.=$HTMLTask;
                                $HTMLTask='';
                                $userTotal+=$taskTotal;
                                    
                                $i++;
                                
                        }

                        
                        $this->db->free($resql);
                }else
                {
                        dol_print_error($this->db);
                }
                if($userTotal>0){
                    $TotalSec=$userTotal%60;
                    $TotalMin=(($userTotal-$TotalSec)/60)%60;
                    $TotalHours=($userTotal-$TotalMin)/3600;
                    $HTMLProject.='<tr class="pair"><th>'.$user.'</th><th></th><th>'
                            .$TotalHours.':'.sprintf("%02s",$TotalMin).'</th></tr>';
                    $HTMLProject.=$HTMLuser;
                    $HTMLuser='';
                    $projectTotal+=$userTotal;
                    $userTotal=0;
                }
                
            }
            $TotalSec=$projectTotal%60;
            $TotalMin=(($projectTotal-$TotalSec)/60)%60;
            $TotalHours=($projectTotal-$TotalMin)/3600;

            $HTMLRes='<table class="noborder" width="100%">'
                    .'<tr class="liste_titre"><th width="30%">'.$this->ref.' - '
                    .$this->title.'</th><th width="30%">'
                    .$langsMonth.'</th><th width="30%">'
                    .$TotalHours.':'.sprintf("%02s",$TotalMin).'</th></tr>';
            $HTMLRes.=$HTMLProject;
            $HTMLRes.='</table>';
            break;
        
        case 2://Project /user/task
        default:
                        $sql='SELECT DISTINCT usr.firstname, usr.lastname, usr.rowid as userId '
                .'FROM '.MAIN_DB_PREFIX.'user as usr ' 
                .'JOIN '.MAIN_DB_PREFIX.'element_contact as ctc '
                .'ON ctc.fk_socpeople=usr.rowid '
                .'JOIN '.MAIN_DB_PREFIX.'projet_task as tsk '
                .'ON ctc.element_id=tsk.rowid '
                .'WHERE tsk.fk_projet="'.$this->id.'" ';  
            
            /*$sql='SELECT DISTINCT firstname,lastname,userId '
                .'FROM view_pjtTskUsr '
                . 'WHERE projectId="'.$this->id.'" ';  */ 
            dol_syslog("timesheet::report::userList sql=".$sql, LOG_DEBUG);
            $resql=$this->db->query($sql);
            $numUsers=0;
            $userList=array();
            if ($resql)
            {
                    $numUsers = $this->db->num_rows($resql);
                    $i = 0;
                   
                    // Loop on each record found, so each couple (project id, task id)
                    while ($i < $numUsers)
                    {
                            $error=0;
                            $obj = $this->db->fetch_object($resql);
                            //$userList[$obj->userId]=$obj->firstname.' '.$obj->lastname;
                            $userList[$obj->userId]=$obj->firstname.' '.$obj->lastname;
                            $i++;
                            
                    }
                    $this->db->free($resql);
            }else
            {
                    dol_print_error($this->db);
            }
 
            foreach($userList as $rowid => $user) {
                
                $sql='SELECT ptt.fk_task,tsk.`ref` as taskRef,tsk.label as taskTitle,'
                    .'ptt.task_date, SUM(ptt.task_duration) as duration '
                    .'FROM '.MAIN_DB_PREFIX.'projet_task_time as ptt '
                    .'JOIN '.MAIN_DB_PREFIX.'projet_task as tsk ON tsk.rowid=fk_task '
                    .'WHERE fk_user="'.$rowid.'" '
                    .'AND tsk.fk_projet="'.$this->id.'" '
                    .'AND task_date>=FROM_UNIXTIME("'.$startDay.'") '
                    .'AND task_date<=FROM_UNIXTIME("'.$stopDay.'") '
                    .'GROUP BY ptt.fk_task,ptt.task_date '
                    .'ORDER BY ptt.fk_task,ptt.task_date ASC';  
                
                        
                dol_syslog("timesheet::report::userTaskList sql=".$sql, LOG_DEBUG);
                $resql=$this->db->query($sql);
                $numTask=0;
                $currentfkTask='';
                $currentTaskTitle='';
                $currentTaskRef='';
                if ($resql)
                {
                    
                        $numTask = $this->db->num_rows($resql);
                        //to mask an user if there is no task found
                       
                        $i = 0;

                        //
                        while ($i < $numTask)
                        {
                                $error=0;
                                $obj = $this->db->fetch_object($resql);
                                if($i==0){
                                    $currentTask=$obj->fk_task; 
                                    $currentTaskRef=$obj->taskRef;
                                    $currentTaskTitle=$obj->taskTitle;
                                }
                                if(($currentTask!=$obj->fk_task) 
                                         && ($taskTotal>0))
                                {
                                $TotalSec=$taskTotal%60;
                                $TotalMin=(($taskTotal-$TotalSec)/60)%60;
                                $TotalHours=($taskTotal-$TotalMin)/3600;
                                $HTMLuser.='<tr class="pair"><th></th><th>'.$currentTaskRef
                                         .' - '.$currentTaskTitle.'</th><th>'
                                         .$TotalHours.':'.sprintf("%02s",$TotalMin).'</th></tr>
                                    ';
                                    $HTMLuser.=$HTMLTask;
                                    $HTMLTask='';
                                    $userTotal+=$taskTotal;
                                    $taskTotal=0;
                                    $currentTask=$obj->fk_task;
                                    $currentTaskRef=$obj->taskRef;
                                    $currentTaskTitle=$obj->taskTitle;
                                    
                                }
                                $HTMLTask.='<tr class="impair"><th></th><th>'
                                        .date('d/m/Y',  strtotime($obj->task_date)).'</th><th>'
                                        .date('H:i',mktime(0,0,$obj->duration))
                                        .'</th></tr>
                                            ';
                                $taskTotal+=intval($obj->duration);    
                                $i++;
                                
                        }

                        
                        $this->db->free($resql);
                }else
                {
                        dol_print_error($this->db);
                }
                if($taskTotal>0)
                {
                    $TotalSec=$taskTotal%60;
                    $TotalMin=(($taskTotal-$TotalSec)/60)%60;
                    $TotalHours=($taskTotal-$TotalMin)/3600;
                    $HTMLuser.='<tr class="pair"><th></th><th>'.$currentTaskRef
                             .' - '.$currentTaskTitle.'</th><th>'
                             .$TotalHours.':'.sprintf("%02s",$TotalMin).'</th></tr>
                                    ';
                    $HTMLuser.=$HTMLTask;
                    $HTMLTask='';
                    $userTotal+=$taskTotal;
                    $taskTotal=0;
                }
                if($userTotal>0){
                    $TotalSec=$userTotal%60;
                    $TotalMin=(($userTotal-$TotalSec)/60)%60;
                    $TotalHours=($userTotal-$TotalMin)/3600;
                    $HTMLProject.='<tr class="pair"><th>'.$user.'</th><th></th><th>'
                            .$TotalHours.':'.sprintf("%02s",$TotalMin).'</th></tr>';
                    $HTMLProject.=$HTMLuser;
                    $HTMLuser='';
                    $projectTotal+=$userTotal;
                    $userTotal=0;
                }
                
            }
            $TotalSec=$projectTotal%60;
            $TotalMin=(($projectTotal-$TotalSec)/60)%60;
            $TotalHours=($projectTotal-$TotalMin)/3600;

            $HTMLRes='<table class="noborder" width="100%">'
                    .'<tr class="liste_titre"><th width="30%">'.$this->ref.' - '
                    .$this->title.'</th><th width="30%">'
                    .$langsMonth.'</th><th width="30%">'
                    .$TotalHours.':'.sprintf("%02s",$TotalMin).'</th></tr>';
            $HTMLRes.=$HTMLProject;
            $HTMLRes.='</table>';
                 $sql='SELECT fk_task,task_date, SUM(task_duration) '
                    .'FROM '.MAIN_DB_PREFIX.'projet_task_time'
                    .'WHERE fk_user="'.$user.'" AND task_date>="'.$startDay.'" '
                    .'AND task_date<="'.$stopDay.'" '
                    .'GROUP BY fk_task,task_date'
                    .'ORDER BY task_date,fk_task ASC';  
            
        
        
            break;
    }



    return $HTMLRes;
    }
    
 }