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
    
     /*
      * startday ; start of report
      * stopday: end of Querry
      * mode layout PTD project/task /day , PDT, DPT
      * periodeTitle give a name to the report
      * timemode show time using day or hours (==0)
      */
    public function getHTMLreport($startDay,$stopDay,$mode,$short,$periodTitle,$hoursperdays){
    // HTML buffer
    
    $lvl1HTML='';
    $lvl3HTML='';
    $lvl2HTML='';
    // partial totals
    $lvl3Total=0;
    $lvl2Total=0;
    $lvl1Total=0;
    
    $Curlvl1=0;
    $Curlvl2=0;
    $Curlvl3=0;

    //mode 1, PER USER
    //get the list of user
    //get the list of task per user
    //sum user
    //mode 2, PER TASK
    //list of task
    //list of user per task
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
                    .'GROUP BY prj.rowid, ptt.task_date,ptt.fk_task ';
    switch ($mode) {
        case 'PDT': //project  / task / Days //FIXME dayoff missing
                
                $sql.='ORDER BY prj.rowid,ptt.task_date,ptt.fk_task ASC   ';
                //title
                $lvl1Title=1;
                $lvl2Title=4;
                $lvl3Title=3;
                //keys
                $lvl1Key=0;
                $lvl2Key=4;
                break;
        
        case 'DPT'://day /project /task
                $sql.='ORDER BY ptt.task_date,prj.rowid,ptt.fk_task ASC   ';
                //title
                $lvl1Title=4;
                $lvl2Title=1;
                $lvl3Title=3;
                //keys
                $lvl1Key=4;
                $lvl2Key=0;
                break;
        case 'PTD'://day /project /task
                $sql.='ORDER BY prj.rowid,ptt.fk_task,ptt.task_date ASC   ';
                //title
                $lvl1Title=1;
                $lvl2Title=3;
                $lvl3Title=4;
                //keys
                $lvl1Key=0;
                $lvl2Key=1;
                break;
        default:
            break;
    }
            
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
           // current

            
        foreach($resArray as $key => $item)
        {
            

            if(($resArray[$Curlvl2][$lvl2Key]!=$resArray[$key][$lvl2Key])
                    ||($resArray[$Curlvl1][$lvl1Key]!=$resArray[$key][$lvl1Key]))
            {
                $lvl2HTML.='<tr class="pair"><th></th><th>'
                        .$resArray[$Curlvl2][$lvl2Title].'</th><th></th><th>'
                        .$this->formatTime($lvl3Total,$hoursperdays).'</th></tr>';
                $lvl2HTML.=$lvl3HTML;
                $lvl3HTML='';
                $lvl2Total+=$lvl3Total;
                $lvl3Total=0;
                $Curlvl2=$key;
                if(($resArray[$Curlvl1][$lvl1Key]!=$resArray[$key][$lvl1Key]))
                {
                    $lvl1HTML.='<tr class="pair"><th>'
                            .$resArray[$Curlvl1][$lvl1Title].'</th><th></th></th><th><th>'
                            .$this->formatTime($lvl2Total,$hoursperdays).'</th></tr>';
                    $lvl1HTML.=$lvl2HTML;
                    $lvl2HTML='';
                    $lvl1Total+=$lvl2Total;
                    $lvl2Total=0;   
                    $Curlvl1=$key;
                }
            }
            if(!$short)
            {
                $lvl3HTML.='<tr class="impair"><th></th><th></th><th>'
                    .$resArray[$key][$lvl3Title].'</th><th>';
                if($hoursperdays==0)
                {
                    $lvl3HTML.=date('G:i',mktime(0,0,$resArray[$key][5])).'</th></tr>';
                }else{
                    $lvl3HTML.=$resArray[$key][5]/3600/$hoursperdays.'</th></tr>';
                }
            }
            $lvl3Total+=$resArray[$key][5];
            

        }
       //handle the last line 
        $lvl2HTML.='<tr class="pair"><th></th><th>'
                    .$resArray[$Curlvl2][$lvl2Title].'</th><th></th><th>'
                    .$this->formatTime($lvl3Total,$hoursperdays).'</th></tr>';
        $lvl2HTML.=$lvl3HTML;
        $lvl2Total+=$lvl3Total;
        $lvl1HTML.='<tr class="pair"><th>'
                .$resArray[$Curlvl1][$lvl1Title].'</th><th></th></th><th><th>'
                .$this->formatTime($lvl2Total,$hoursperdays).'</th></tr>';
        $lvl1HTML.=$lvl2HTML;
        $lvl1Total+=$lvl2Total;
        // make the whole result
        $HTMLRes='<table class="noborder" width="100%">'
                .'<tr class="liste_titre"><th>'.$this->firstname.' - '
                .$this->lastname.'</th><th>'
                .$periodTitle.'</th><th></th><th>'
                .$this->formatTime($lvl1Total,$hoursperdays).'</th></tr>';
        $HTMLRes.=$lvl1HTML;
        $HTMLRes.='</table>';
        } // end is numtasktime




    return $HTMLRes;
    }
    
    private function formatTime($duration,$hoursperdays)
    {
        if($hoursperdays==0)
        {
            $TotalSec=$duration%60;
            $TotalMin=(($duration-$TotalSec)/60)%60;
            $TotalHours=($duration-$TotalMin)/3600;
            return $TotalHours.':'.sprintf("%02s",$TotalMin);
        }else
        {
            $totalDay=$duration/3600/$hoursperdays;
            return strval($totalDay);
            
        }

    }
    
 }      
