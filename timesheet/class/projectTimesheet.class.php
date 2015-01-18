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

    
    public function getHTMLreport($startDay,$stopDay,$mode){
    $HTMLRes='<h1>'.$this->ref.' - '.$this->title.'</h1><br>';
    $taskTotal=0;
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
            
            $sql='SELECT DISTINCT firstname,lastname,userId '
                .'FROM view_pjtTskUsr '
                . 'WHERE projectId="'.$this->id.'" ';   
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
                
                //becareful it use the view
                $sql='SELECT fk_task,taskRef,taskTitle,task_date, SUM(task_duration) as duration '
                    .'FROM '.MAIN_DB_PREFIX.'projet_task_time '
                    .'JOIN view_pjtTskUsr as ptu ON ptu.taskId=fk_task '
                    .'WHERE fk_user="'.$rowid.'" '
                    .'AND ptu.projectId="'.$this->id.'" '
                    .'AND task_date>=FROM_UNIXTIME("'.$startDay.'") '
                    .'AND task_date<=FROM_UNIXTIME("'.$stopDay.'") '
                    .'GROUP BY fk_task,task_date '
                    .'ORDER BY fk_task,task_date ASC';  
                
                        
                dol_syslog("timesheet::report::userTaskList sql=".$sql, LOG_DEBUG);
                $resql=$this->db->query($sql);
                $numTask=0;
                if ($resql)
                {
                        $numTask = $this->db->num_rows($resql);
                        //to mask an user if there is no task found
                        if($numTask>'0'){
                            $HTMLRes.='<p id="user">'.$user.'</p><br>
                        ';
                        }
                        
                        $i = 0;
                        $currentTask='';
                        //
                        while ($i < $numTask)
                        {
                                $error=0;
                                $obj = $this->db->fetch_object($resql);
                                /*//special handeling of the first task
                                if($i==0){
                                    $currentTask=$obj->fk_task;   
                                }*/
                                // if we change task then we show the total of the task;
                                if($currentTask!=$obj->fk_task){
                                    $currentTask=$obj->fk_task;
                                    if($taskTotal>0)
                                        $HTMLRes.='<p id="total"> Total: '.date('H:i',mktime(0,0,$taskTotal)).'</p><br>';
                                    $HTMLRes.= '<p id="task">'.$obj->taskRef.' - '
                                            .$obj->taskTitle.'</p><br>
                                            ';
                                    $taskTotal=0;
                                }
                                $HTMLRes.='<p id="taskdate">'.$obj->task_date.'</p>'
                                        . '<p id="tasktime">'.date('H:i',mktime(0,0,$obj->duration)).'</p><br>
                                            ';
                                $taskTotal=$taskTotal+intval($obj->duration);    
                                
                                $i++;

                        }
                        //show the total of the last task of an user and reset it so it wont be shown for user without task 
                        if($taskTotal>0){
                        $HTMLRes.='<p id="total"> Total: '.date('H:i',mktime(0,0,$taskTotal)).'</p><br>';
                        $taskTotal=0;
                        }
                        $this->db->free($resql);
                }else
                {
                        dol_print_error($this->db);
                }
                
            }

            break;
        case 3://pou un user
                            $sql='SELECT fk_task,task_date, SUM(task_duration) '
                    .'FROM '.MAIN_DB_PREFIX.'projet_task_time'
                    .'WHERE fk_user="'.$rowid.'" AND task_date>="'.$startDay.'" '
                    .'AND task_date<="'.$stopDate.'" '
                    .'GROUP BY fk_task,task_date'
                    .'ORDER BY task_date,fk_task ASC';  
            break;
        case 2:
        default:

            break;
    }



    return $HTMLRes;
    }
    
 }