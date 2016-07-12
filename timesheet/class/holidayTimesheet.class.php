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
        /* Status
         *   1) DraftCP
         *   2) ToReviewCP
         *   3) ApprovedCP
         *   4) CancelCP
         *   5) RefuseCP
         */
$statusColor=array('1'=>TIMESHEET_COL_DRAFT,'2'=>TIMESHEET_COL_SUBMITTED,'3'=>TIMESHEET_COL_APPROVED,'4'=>TIMESHEET_COL_CANCELLED,'5'=>TIMESHEET_COL_REJECTED);

require_once DOL_DOCUMENT_ROOT.'/holiday/class/holiday.class.php';
//dol_include_once('/timesheet/class/projectTimesheet.class.php');
//require_once './projectTimesheet.class.php';
define('TIMESHEET_BC_FREEZED','909090');
define('TIMESHEET_BC_VALUE','f0fff0');
class holidayTimesheet extends Holiday 
{
        private $holidaylist;
        private $holidayPresent;
 	

    public function __construct($db) 
	{
		$this->db=$db;
                
		//$this->date_end=strtotime('now -1 year');
		//$this->date_start=strtotime('now -1 year');
	}

        /*public function initTimeSheet($weekWorkLoad,$taskTimeId) 
    {
            $this->weekWorkLoad=$weekWorkLoad;
            $this->taskTimeId=$taskTimeId;

    }*/
    public function fetchUserWeek($userId,$yearWeek)
    {
       
        $datestart=strtotime($yearWeek);
        $datestop=strtotime($yearWeek.' + 7 days');
        $SQLfilter=  " AND (cp.date_fin>=".$this->db->idate($datestart).") ";
        $SQLfilter.= " AND (cp.date_debut<".$this->db->idate($datestop).")";
        $ret=$this->fetchByUser($userId,'',$SQLfilter);
        $this->holidayPresent=($ret==1);
        $this->holidaylist=array();
        //fixme fill the holiday list 
        /*
         * id       --> id of the holiday task if any
         * prev     --> is it the holiday starting the day before
         * am       --> is the morning off
         * pm       --> is the afternoon off
         * next     --> is it the holiday continuing the day after
         * status   --> is the holiday submitted or approuved ( none if id=0)
         */
 
        for($dayOfWeek=0;$dayOfWeek<7;$dayOfWeek++)
        {
            
                $curDay=strtotime($yearWeek.' + '.$dayOfWeek.' days');
                $this->holidaylist[$dayOfWeek]=array('amId'=>'0','pmId'=>'0','prev'=>false,'am'=>false,'pm'=>false,'next'=>false,'amStatus'=>0,'pmStatus'=>0);
                //FIXME: support 2 holiday in one day , 1 id per dqy ..
                foreach($this->holiday as $record){
                    if($record['date_debut']<=$curDay && $record['date_fin']>=$curDay){
                     $prev=($record['date_debut']<$curDay)?true:false;
                     $next=($record['date_fin']>$curDay)?true:false;
                     $am=false;
                     $pm=false;
                     switch ($record['halfday']){
                         case -1://Holiday start the afteroon and end the afternnon -+++
                             $am=$prev;
                             $pm=true;
                             break;
                          case 1: //Holiday start the morning and end the morning    +++-
                             $am=true;
                             $pm=$next;
                             break;                    
                         case 2: //Holiday start the afternoon and end the morning  -++-
                             $am=$prev;
                             $pm=$next;
                            break;
                         case 0: //Holiday start the morning and end the afternnon  ++++
                         default:
                             $am=true;
                             $pm=true;
                             break;   
                     }  
                     // in case of 2 holiday present in the half day order 3,2,1,5,4
                     $oldSatus=$this->holidaylist[$dayOfWeek]['amStatus'];
                     $amOverride=($this->holidaylist[$dayOfWeek]['amId']==0) || (($record['statut']>3 && $oldSatus >3 && $record['statut']>$oldSatus)||($record['statut']<=3 && ($record['statut']>$oldSatus || $oldSatus >3 ) ));
                     $oldSatus=$this->holidaylist[$dayOfWeek]['amStatus'];
                     $pmOverride=($this->holidaylist[$dayOfWeek]['pmId']==0) ||(($record['statut']>3 && $oldSatus >3 && $record['statut']>$oldSatus)||($record['statut']<=3 && ($record['statut']>$oldSatus || $oldSatus >3 ) ));

                     if($am && $amOverride){
                         $this->holidaylist[$dayOfWeek]['am']=true;
                         $this->holidaylist[$dayOfWeek]['amId']=$record['rowid'];
                         $this->holidaylist[$dayOfWeek]['prev']=$prev;
                         $this->holidaylist[$dayOfWeek]['amStatus']=$record['statut'];
                     }

                     if($pm && $pmOverride){
                         $this->holidaylist[$dayOfWeek]['pm']=true;
                         $this->holidaylist[$dayOfWeek]['pmId']=$record['rowid'];
                         $this->holidaylist[$dayOfWeek]['next']=$next;
                         $this->holidaylist[$dayOfWeek]['pmStatus']=$record['statut'];                         
                     }
                     
                     //$this->holidaylist[$dayOfWeek]=array('idam'=>$record['rowid'],'idpm'=>$record['rowid'],'prev'=>$prev,'am'=>$am,'pm'=>$pm,'next'=>$next,'status'=>$record['statut']);
                    }
                }
                       
        }


    }
    
    

    
     

 /*
 * function to form a HTMLform line for this timesheet
 * 
 *  @param    string              	$yearWeek            year week like 2015W09
 *  @param    string              	$headers             header to shows
 *  @param     int              	$tsUserId           id of the user timesheet
 *  @return     string                                        HTML result containing the timesheet info
 */
       public function getHTMLFormLine($yearWeek,$headers,$tsUserId)
    {
        
        global $langs;
        global $statusColor;
        $timetype=TIMESHEET_TIME_TYPE;
        $dayshours=TIMESHEET_DAY_DURATION;
        if(empty($yearWeek)||empty($headers) || !is_array($this->holidaylist))
           return '<tr>ERROR: wrong parameters for getFormLine'.empty($yearWeek).'|'.empty($headers).'</tr>';       
        if(!$this->holidayPresent) // don't show the holiday line if nothing present
           return '';
        $html ="<tr id='holiday'>\n";
        $html .='<th colspan="'.count($headers).'" align="right" > '.$langs->trans('Holiday').' </th>';
        
        for ($i=0;$i<7;$i++)
        {
            $am=$this->holidaylist[$i]['am'];
            $pm=$this->holidaylist[$i]['pm'];
            $amId=$this->holidaylist[$i]['amId'];
            $pmId=$this->holidaylist[$i]['pmId'];
            $amValue=($this->holidaylist[$i]['amStatus']==3);
            $pmValue=($this->holidaylist[$i]['pmStatus']==3);
            $value=($timetype=="hours")?date('H:i',mktime(0,0,($amValue+$pmValue)*$dayshours*1800)):($amValue+$pmValue)/2;
            
            $html .='<th style="margin: 0;padding: 0;">';
            if(TIMESHEET_ADD_HOLIDAY_TIME==1)$html .='<input type="hidden" class="time4day['.$tsUserId.']['.$i.']"  value="'.$value.'">';
            $html .='<ul id="holiday['.$i.']" class="listHoliday" >';
                 
    //FIXME: SUPPORT COLOR & PUT TIME FOR THE  TOTAL
                $html .='<li id="holiday['.$i.'][0]" class="listItemHoliday" ><a ';
                if($am){
                    $html .='href="'.DOL_URL_ROOT.'/holiday/card.php?id='.$this->holidaylist[$i]['amId'].'"';
                    $amColor=($am?'background-color:#'.$statusColor[$this->holidaylist[$i]['amStatus']].'':''); 
                    $amClass= ($this->holidaylist[$i]['prev'])?'':' noPrevHoliday';
                    $amClass.= ($pm && $pmId==$amId )?'':' noNextHoliday';
                    $html .=' class="holiday'.$amClass.'" style="'.$amColor.'">&nbsp;</a></li>';
                }else{
                    $html .=' class="holiday" >&nbsp;</a></li>';
                }
                $html .='<li id="holiday['.$i.'][1]" class="listItemHoliday" ><a ';
                if($pm){
                    $html .='href="'.DOL_URL_ROOT.'/holiday/card.php?id='.$this->holidaylist[$i]['pmId'].'"';
                    $pmColor=($pm?'background-color:#'.$statusColor[$this->holidaylist[$i]['pmStatus']].'':''); 
                    $pmClass= ($am && $pmId==$amId)?'':' noPrevHoliday';
                    $pmClass.= ($this->holidaylist[$i]['next'])?'':' noNextHoliday';
                    $html .=' class="holiday'.$pmClass.'" style="'.$pmColor.'">&nbsp;</a></li>';
                }else{
                    $html .=' class="holiday" >&nbsp;</a></li>';
                }
                
           // }
            $html .="</ul></th>\n";
            
        }
        $html .='</tr>';
        return $html;

    }	


/*
 * function to form a XML for this timesheet
 * 
 *  @param    string              	$yearWeek            year week like 2015W09
  *  @return     string                                         XML result containing the timesheet info
 */
    public function getXML( $yearWeek)
    {/*
    $timetype=TIMESHEET_TIME_TYPE;
    $dayshours=TIMESHEET_DAY_DURATION;
    $hidezeros=TIMESHEET_HIDE_ZEROS;
    $xml= "<task id=\"{$this->id}\" >";
    //title section
    $xml.="<Tasks id=\"{$this->id}\">{$this->description} </Tasks>";
    $xml.="<Project id=\"{$this->fk_project2}\">{$this->ProjectTitle} </Project>";
    $xml.="<TaskParent id=\"{$this->fk_task_parent}\">{$this->taskParentDesc} </TaskParent>";
    //$xml.="<task id=\"{$this->id}\" name=\"{$this->description}\">\n";
    $xml.="<DateStart unix=\"$this->date_start\">";
    if($this->date_start)
        $xml.=dol_mktime($this->date_start);
    $xml.=" </DateStart>";
    $xml.="<DateEnd unix=\"$this->date_end\">";
    if($this->date_end)
        $xml.=dol_mktime($this->date_end);
    $xml.=" </DateEnd>";
     $xml.="<Company id=\"{$this->companyId}\">{$this->companyName} </Company>";
    $xml.="<TaskProgress id=\"{$this->companyId}\">";
    if($this->planned_workload)
    {
        $xml .= $this->parseTaskTime($this->planned_workload).'('.floor($this->duration_effective/$this->planned_workload*100).'%)';
    }else{
        $xml .= "-:--(-%)";
    }
    $xml.="</TaskProgress>";


  // day section
//        foreach ($this->weekWorkLoad as $dayOfWeek => $dayWorkLoadSec)
         for($dayOfWeek=0;$dayOfWeek<7;$dayOfWeek++)
         {
                $today= strtotime($yearWeek.' +'.($dayOfWeek).' day  ');
                # to avoid editing if the task is closed 
                $dayWorkLoadSec=isset($this->tasklist[$dayOfWeek])?$this->tasklist[$dayOfWeek]['duration']:0;
                # to avoid editing if the task is closed 
				if($hidezeros==1 && $dayWorkLoadSec==0){
					$dayWorkLoad=' ';
				}else if ($timetype=="days")
                {
                    $dayWorkLoad=$dayWorkLoadSec/3600/$dayshours;
                }else {
                    $dayWorkLoad=date('H:i',mktime(0,0,$dayWorkLoadSec));
                }
                $open='0';
                if((empty($this->date_start) || ($this->date_start <= $today +86399)) && (empty($this->date_end) ||($this->date_end >= $today )))
                {             
                    $open='1';                   
                }
                $xml .= "<day col=\"{$dayOfWeek}\" open=\"{$open}\">{$dayWorkLoad}</day>";

        } 
        $xml.="</task>"; 
        return $xml;
        //return utf8_encode($xml);*/

    }	
/*
 * function to save a time sheet as a string
 */
function serialize(){
    /*$arRet=array();
    $arRet['id']=$this->id;
    $arRet['tasklist']=$this->tasklist;
    $arRet['description']=$this->description;			
    $arRet['fk_project2']=$this->fk_project2 ;
    $arRet['ProjectTitle']=$this->ProjectTitle;
    $arRet['date_start']=$this->date_start;			
    $arRet['date_end']=$this->date_end	;		
    $arRet['duration_effective']=$this->duration_effective ;   
    $arRet['planned_workload']=$this->planned_workload ;
    $arRet['fk_task_parent']=$this->fk_task_parent ;
    $arRet['taskParentDesc']=$this->taskParentDesc ;
    $arRet['companyName']=$this->companyName  ;
    $arRet['companyId']= $this->companyId;
                      
    return serialize($arRet);*/
    
}
/*
 * function to load a time sheet as a string
 */
function unserialize($str){
    /*$arRet=unserialize($str);
    $this->id=$arRet['id'];
    $this->tasklist=$arRet['tasklist'];
    $this->description=$arRet['description'];			
    $this->fk_project2=$arRet['fk_project2'] ;
    $this->ProjectTitle=$arRet['ProjectTitle'];
    $this->date_start=$arRet['date_start'];			
    $this->date_end=$arRet['date_end']	;		
    $this->duration_effective=$arRet['duration_effective'] ;   
    $this->planned_workload=$arRet['planned_workload'] ;
    $this->fk_task_parent=$arRet['fk_task_parent'] ;
    $this->taskParentDesc=$arRet['taskParentDesc'] ;
    $this->companyName=$arRet['companyName']  ;
    $this->companyId=$arRet['companyId'];*/
}
 

    function parseTaskTime($taskTime){
        
        $ret=floor($taskTime/3600).":".str_pad (floor($taskTime%3600/60),2,"0",STR_PAD_LEFT);
        
        return $ret;
        //return '00:00';
          
    }

    

}
?>
