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
/*
function postActuals($db,$user,$weekDays,$tabPost)
{
    //$db->begin();
    $ret=0;
    $yearWeek=date('Y\WW',strtotime($weekDays[0]));
    $_SESSION['timeSpendCreated']=0;
    $_SESSION['timeSpendDeleted']=0;
    $_SESSION['timeSpendModified']=0;
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
                        if($task->timespent_old_duration!=$duration)
                        {
                            if($task->timespent_duration>0)
                            { 
                                dol_syslog("Timesheet::Submit.php  taskTimeUpdate", LOG_DEBUG);
                                if($task->updateTimeSpent($user)>=0)
                                {
                                    $ret++; 
                                    $_SESSION['timeSpendModified']++;
                                }
                            }else {
                                dol_syslog("Timesheet::Submit.php  taskTimeDelete", LOG_DEBUG);
                                if($task->delTimeSpent($user)>=0)
                                {
                                    $ret++;
                                    $_SESSION['timeSpendDeleted']++;
                                }
                            }
                        }
                            
                    }
                    elseif ($duration>0)
                    { 
                        $task->timespent_duration=$duration; 
                        $task->timespent_date=strtotime($weekDays[$dayKey]);
                        
                        if($task->addTimeSpent($user,0)>=0)
                        {
                            $ret++;
                            $_SESSION['timeSpendCreated']++;
                        }
                        
                    }
                           
                }

            }else
            {
                //not valid lines don't lead to an exit but to a message in the log
                dol_syslog("Timesheet::Submit.php  No Taskid in the submit form", LOG_ERR); 
            }
    }
    return $ret;
}
*/
function postActualsSecured($db,$user,$tabPost,$timestamp)
{
    
    $storedTab=array();
    $storedTab=$_SESSION["timestamps"][$timestamp];
    if(isset($storedTab["YearWeek"])) {
        $yearWeek=$storedTab["YearWeek"];
    }else {
        return -1;
    }
    $storedTasks=array();
    if(isset($storedTab['tasks'])) {
        $storedTasks=$storedTab['tasks'];
    }else {
        return -1;
    }
    if(isset($storedTab['weekDays'])) {
        $storedWeekdays=$storedTab['weekDays'];
    }else {
        return -1;
    }
        
    $ret=0;
    $tmpRet=0;
    $_SESSION['timeSpendCreated']=0;
    $_SESSION['timeSpendDeleted']=0;
    $_SESSION['timeSpendModified']=0;
        /*
         * For each task store in matching the session timestamp
         */
    foreach($storedTasks as  $taskId => $taskItem)
    {
      //  $taskId=$taskItem["id"];
        $tasktimeIds=array();
        $tasktimeIds=$taskItem["taskTimeId"];
        $tasktime=new timesheet($db,$taskId);
        $tasktime->timespent_fk_user=$user;
        $tasktime->fetch($taskId);
        dol_syslog("Timesheet::Submit.php::postActualsSecured  task=".$tasktime->id, LOG_DEBUG);
        //use the data stored
        //$tasktime->initTimeSheet($taskItem['weekWorkLoad'], $taskItem['taskTimeId']);
        //refetch actuals
        $tasktime->getActuals($yearWeek, $user); 
        /*
         * for each day of the task store in matching the session timestamp
         */
        //foreach($taskItem['taskTimeId'] as $dayKey => $tasktimeid)
        foreach($tabPost[$taskId] as $dayKey => $wkload)
        {
            dol_syslog("Timesheet::Submit.php::postActualsSecured  task = ".$taskId." tabPost[".$dayKey."]=".$wkload, LOG_DEBUG);

            $tasktimeid=$tasktimeIds[$dayKey];
            
            $ret+=postTaskTimeActual($user,$tasktime,$tasktimeid,$wkload,$storedWeekdays[$dayKey]);
        }
        if($ret!=$tmpRet){ // something changed so need to updae the total duration
            $tasktime->updateTimeUsed();
        }
    } 
    unset($_SESSION["timestamps"][$timestamp]);
    return $ret;
}

function postTaskTimeActual($user,$tasktime,$tasktimeid,$wkload,$date)
{

   $ret=0;
   if(TIMESHEET_TIME_TYPE=="days")
   {
      $duration=$wkload*TIMESHEET_DAY_DURATION*3600;
   }else
   {
    $durationTab=date_parse($wkload);
    $duration=$durationTab['minute']*60+$durationTab['hour']*3600;
   }
    dol_syslog("Timesheet::Submit.php::postTaskTimeActualSecured   timespent_duration=".$duration." taskTimeId=".$tasktimeid, LOG_DEBUG);

    if($tasktimeid>0)
    {
        $tasktime->fetchTimeSpent($tasktimeid); ////////////////////////////
        $tasktime->timespent_old_duration=$tasktime->timespent_duration;
        $tasktime->timespent_duration=$duration; 
        if($tasktime->timespent_old_duration!=$duration)
        {
            if($tasktime->timespent_duration>0){ 
                dol_syslog("Timesheet::Submit.php  taskTimeUpdate", LOG_DEBUG);
                if($tasktime->updateTimeSpent($user,0)>=0)
                {
                    $ret++; 
                    $_SESSION['timeSpendModified']++;
                }
            }else {
                dol_syslog("Timesheet::Submit.php  taskTimeDelete", LOG_DEBUG);
                if($tasktime->delTimeSpent($user,0)>=0)
                {
                    $ret++;
                    $_SESSION['timeSpendDeleted']++;
                }
            }
        }
    } elseif ($duration>0)
    { 
        $tasktime->timespent_duration=$duration; 
        //FIXME
        $tasktime->timespent_date=strtotime($date);
        
        if($tasktime->addTimeSpent($user,0)>=0)
        {
            $ret++;
            $_SESSION['timeSpendCreated']++;
        }
    }
    return $ret;
}

function dol_syslog_array($varName,$array, $lvl)
{
    if(is_array($array))
    {
        dol_syslog("Timesheet::Submit.php::dol_syslog_array ".$varName." level ".$lvl, LOG_DEBUG); 
        foreach($array as $key => $row)
        { 
            if(is_array($row))
                dol_syslog_array($varName."[".$key."]",$array, $lvl+1);
            else
                dol_syslog("Timesheet::Submit.php::dol_syslog_array ".$varName."[".$key."]=".$row, LOG_DEBUG);
        }
    }else
    {
        dol_syslog("Value ".$varName." level ".$lvl." Data ".$array , LOG_DEBUG); 
    }
}