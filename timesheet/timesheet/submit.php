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

require_once DOL_DOCUMENT_ROOT.'/projet/class/task.class.php';
dol_include_once('/timesheet/class/timesheet.class.php');

function postActuals($db,$user,$weekDays,$tabPost)
{
    //$db->begin();
    $ret=0;
   
    $yearWeek=date('Y\WW',strtotime($weekDays[0]));
    if(isset($yearWeek))
    foreach ($tabPost as $key =>$weekTaskTime)
    {
        $ret=0;
        $task=new timesheet($db,$user);
              
            if( isset($weekTaskTime['taskid'])) // if 
            {
                $task->fetch($weekTaskTime['taskid']);//FIX ME IN Timesheet
                $task->getActuals($yearWeek, $user);
                $task->timespent_fk_user=$user;
                //if(is_array($weekTaskTime['weekDays']))
                foreach($weekTaskTime['weekDays'] as $dayKey => $day)
                {
                    $durationTab=date_parse($day['value']);
                    $duration=$durationTab['minute']*60+$durationTab['hour']*3600;
                       
                    dol_syslog("Timesheet::Submit.php  timespent_duration=".$task->timespent_duration, LOG_DEBUG);
                    if($day['tasktimeid']>0)
                    {
                        $task->fetchTimeSpent($day['tasktimeid']);
                        $task->timespent_old_duration=$task->timespent_duration;
                        $task->timespent_duration=$duration; 
                        //save the old time
                       //$task->timespent_note="added with timesheet";
                        //$task->timespent_fk_user=$user;
                        if($task->timespent_old_duration!=$duration)
                        if($task->timespent_duration>0){ 
                            dol_syslog("Timesheet::Submit.php  taskTimeUpdate", LOG_DEBUG);
                            if($task->updateTimeSpent($user)>=0)
                                $ret++;        
                        }else {
                            dol_syslog("Timesheet::Submit.php  taskTimeDelete", LOG_DEBUG);
                            if($task->delTimeSpent($user)>=0)
                                $ret++;
                        }
                            
                    }
                    elseif ($duration>0)
                    { 
                        $task->timespent_duration=$duration; 
                        $task->timespent_date=strtotime($weekDays[$dayKey]);
                        
                        if($task->addTimeSpent($user,0)>0)
                            $ret++;
                    }
                           
                }

            }else
            {
                //one not valid line lead to an exit
                dol_syslog("Timesheet::Submit.php  No Taskid in the submit form", LOG_ERR);
               // return -2; 
            }
        
        //adapt the actuals
        
        
    }
    return $ret;
}
  
/*
function setActuals($user,$tabActuals, $notrigger=0)
{
   // Does nothing if yearWeek is not set because we need to get the Actuals before updating them
   if(isset($this->$yearWeek))
   foreach($this->taskTimeId as $key=> $tasktimeid)
   {   // new or already existing 
       if($this->weekWorkLoad[$key]!=$tabActuals[$key] || $tasktimeid>0 )
       {
               global $conf,$langs;

               $error=0;
               $ret = 0;

               // Clean parameters
               if ($tasktimeid>0)
               {
                   $sql = "UPDATE ".MAIN_DB_PREFIX."projet_task_time SET";
                   $sql.= " task_date = '".$this->db->idate(strtotime($this->yearWeek.' +'.$key."day"))."',";                        
                   $sql.= " task_duration = ".$this->weekWorkLoad[$key].",";
                   $sql.= " fk_user = ".$user.",";
                   $sql.= " WHERE rowid = ".$tasktimeid;
               }
               else
               {
                   $sql = "INSERT INTO ".MAIN_DB_PREFIX."projet_task_time (";
                   $sql.= "fk_task";
                   $sql.= ", task_date";
                   $sql.= ", task_duration";
                   $sql.= ", fk_user";
                   //$sql.= ", note";
                   $sql.= ") VALUES (";
                   $sql.= $this->id;
                   $sql.= ", '".$this->db->idate(strtotime($this->yearWeek.' +'.$key."day"))."'";
                   $sql.= ", ".$this->weekWorkLoad[$key];
                   $sql.= ", ".$user;
                   //$sql.= ", ".(isset($this->timespent_note)?"'".$this->db->escape($this->timespent_note)."'":"null");
                   $sql.= ")";
               }



               dol_syslog(get_class($this)."::setActuals sql=".$sql, LOG_DEBUG);
               if ($this->db->query($sql) )
               {
                   if (! $notrigger)
                   {
                       // Call triggers
                       include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
                       $interface=new Interfaces($this->db);
                       if ($tasktimeid>0)
                           $result=$interface->run_triggers('TASK_TIMESPENT_MODIFY',$this,$user,$langs,$conf);
                       else
                           $result=$interface->run_triggers('TASK_TIMESPENT_CREATE',$this,$user,$langs,$conf);

                       if ($result < 0) { $error++; $this->errors=$interface->errors; }
                       // End call triggers
                   }
                   $ret = 1;
               }
               else
               {
                   $this->error=$this->db->lasterror();
                   dol_syslog(get_class($this)."::updateTimeSpent error -1 ".$this->error,LOG_ERR);
                   $ret = -1;
               }
       }
   }

}*/