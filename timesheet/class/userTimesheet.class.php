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


require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';

class userTimesheet extends user
{
    public function __construct($db) 
	{
            $this->db = $db;
	}
        
    public function initBasic($id,$firstname,$lastname) 
	{
            $this->id =$id;
            $this->firstname =$firstname;
            $this->lastname= $lastname;
	}
           
    public function getHTMLreport($startDay,$stopDay,$mode,$short,$periodTitle){
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
        case 'PDT': //project  / task / Days //FIXME dayoff missing
                $sql='SELECT prj.rowid as projectId, prj.`ref` as projectRef, '
                    .'prj.title as projectTitle,tsk.rowid as taskId, '
                    .'tsk.`ref` as taskRef,tsk.label as taskTitle,'
                    .'ptt.task_date, SUM(ptt.task_duration) as duration '
                    .'FROM '.MAIN_DB_PREFIX.'projet_task_time as ptt '
                    .'JOIN '.MAIN_DB_PREFIX.'projet_task as tsk ON tsk.rowid=fk_task '
                    .'JOIN '.MAIN_DB_PREFIX.'projet as prj ON prj.rowid= tsk.fk_projet '
                    .'WHERE fk_user="'.$this->id.'" '
                    .'AND task_date>=FROM_UNIXTIME("'.$startDay.'") '
                    .'AND task_date<=FROM_UNIXTIME("'.$stopDay.'")  '
                    .'GROUP BY prj.rowid, ptt.task_date,ptt.fk_task '
                    .'ORDER BY prj.rowid,ptt.task_date,ptt.fk_task ASC   ';
            
            dol_syslog("timesheet::userreport::tasktimeList sql=".$sql, LOG_DEBUG);
            $resql=$this->db->query($sql);
            $numTaskTime=0;
            $resArray=array();
            if ($resql)
            {
                    $numTaskTime = $this->db->num_rows($resql);
                    $i = 0;
                   
                    // Loop on each record found,
                    while ($i < $numTaskTime)
                    {
                            
                        $error=0;
                        $obj = $this->db->fetch_object($resql);
                        $resArray[$i]=[$obj->projectId,
                                    $obj->projectRef.' - '.$obj->projectTitle,
                                    $obj->taskId,
                                    $obj->taskRef.' - '.$obj->taskTitle,
                                    $obj->task_date,
                                    $obj->duration ];
                            
                        $i++;
                            
                    }
                    $this->db->free($resql);
            }else
            {
                    dol_print_error($this->db);
            }
        if($numTaskTime>0) 
        {
            //html part init
            $HTMLTask='';
            $HTMLDay='';
            $HTMLProject='';
            $HTMLRes='';
            //totals init
            $dayTotal=0;
            $taskTotal=0;
            $projectTotal=0;
            // current
            $CurProjectId=0;
            $CurDay=0;
            $CurTask=0;
        foreach($resArray as $key => $item)
        {
            

            if(($resArray[$CurDay][4]!=$resArray[$key][4])
                    ||($resArray[$CurProjectId][0]!=$resArray[$key][0]))
            {
                $TotalSec=$dayTotal%60;
                $TotalMin=(($dayTotal-$TotalSec)/60)%60;
                $TotalHours=($dayTotal-$TotalMin)/3600;
                $HTMLProject.='<tr class="pair"><th></th><th>'
                        .$resArray[$CurDay][4].'</th><th></th><th>'
                        .$TotalHours.':'.sprintf("%02s",$TotalMin).'</th></tr>';
                $HTMLProject.=$HTMLDay;
                $HTMLDay='';
                $projectTotal+=$dayTotal;
                $dayTotal=0;
                $CurDay=$key;
                if(($resArray[$CurProjectId][0]!=$resArray[$key][0]))
                {
                    $TotalSec=$projectTotal%60;
                    $TotalMin=(($projectTotal-$TotalSec)/60)%60;
                    $TotalHours=($projectTotal-$TotalMin)/3600;
                    $HTMLuser.='<tr class="pair"><th>'
                            .$resArray[$CurProjectId][1].'</th><th></th></th><th><th>'
                            .$TotalHours.':'.sprintf("%02s",$TotalMin).'</th></tr>';
                    $HTMLuser.=$HTMLProject;
                    $HTMLProject='';
                    $userTotal+=$projectTotal;
                    $projectTotal=0;   
                    $CurProjectId=$key;
                }
            }
                $HTMLDay.='<tr class="impair"><th></th><th></th><th>'
                    .$resArray[$key][3].'</th><th>'
                    .date('G:i',mktime(0,0,$resArray[$key][5])).'</th></tr>';
                $dayTotal+=$resArray[$key][5];
            

        }
       //handle the last line 
        $TotalSec=$dayTotal%60;
        $TotalMin=(($dayTotal-$TotalSec)/60)%60;
        $TotalHours=($dayTotal-$TotalMin)/3600;
        $HTMLProject.='<tr class="pair"><th></th><th>'
                .$resArray[$CurDay][4].'</th><th></th><th>'
                .$TotalHours.':'.sprintf("%02s",$TotalMin).'</th></tr>';
        $HTMLProject.=$HTMLDay;
        $projectTotal+=$dayTotal;
        $TotalSec=$projectTotal%60;
        $TotalMin=(($projectTotal-$TotalSec)/60)%60;
        $TotalHours=($projectTotal-$TotalMin)/3600;
        $HTMLuser.='<tr class="pair"><th>'
                .$resArray[$CurProjectId][1].'</th><th></th></th><th><th>'
                .$TotalHours.':'.sprintf("%02s",$TotalMin).'</th></tr>';
        $HTMLuser.=$HTMLProject;
        $userTotal+=$projectTotal;
        // make the whole result
        $TotalSec=$userTotal%60;
        $TotalMin=(($userTotal-$TotalSec)/60)%60;
        $TotalHours=($userTotal-$TotalMin)/3600;

        $HTMLRes='<table class="noborder" width="100%">'
                .'<tr class="liste_titre"><th>'.$this->firstname.' - '
                .$this->lastname.'</th><th></th><th>'
                .$periodTitle.'</th><th>'
                .$TotalHours.':'.sprintf("%02s",$TotalMin).'</th></tr>';
        $HTMLRes.=$HTMLuser;
        $HTMLRes.='</table>';
        } // end is numtasktime
            break;
        
        case 'DPT'://Project /user/task
        default:
            break;
    }



    return $HTMLRes;
    }
    
 }      
