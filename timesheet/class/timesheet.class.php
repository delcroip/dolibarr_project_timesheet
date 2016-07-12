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

$statusTsColor=array('DRAFT'=>TIMESHEET_COL_DRAFT,'SUBMITTED'=>TIMESHEET_COL_SUBMITTED,'APPROVED'=>TIMESHEET_COL_APPROVED,'CANCELLED'=>TIMESHEET_COL_CANCELLED,'REJECTED'=>TIMESHEET_COL_REJECTED);

//dol_include_once('/timesheet/class/projectTimesheet.class.php');
//require_once './projectTimesheet.class.php';
define('TIMESHEET_BC_FREEZED','909090');
define('TIMESHEET_BC_VALUE','f0fff0');
class timesheet extends Task 
{
        private $ProjectTitle		=	"Not defined";
        var $tasklist;
        private $fk_project2;
        private $taskParentDesc;
        private $companyName;
        private $companyId;
		private $startDatePjct;
		private $stopDatePjct;
		private $pStatus;
        private $hidden; // in the whitelist 
	

    public function __construct($db,$taskId=0) 
	{
		$this->db=$db;
		$this->id=$taskId;
		//$this->date_end=strtotime('now -1 year');
		//$this->date_start=strtotime('now -1 year');
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
        $sql ='SELECT p.rowid,p.datee as pdatee, p.fk_statut as pstatus, p.dateo as pdateo, pt.dateo,pt.datee, pt.planned_workload, pt.duration_effective';
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
                        $this->startDatePjct=$this->db->jdate($obj->pdateo);
			$this->stopDatePjct=$this->db->jdate($obj->pdatee);
			$this->pStatus=$obj->pstatus;
                        
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
    
    
/*
 *  FUNCTION TO GET THE ACTUALS FOR A WEEK AND AN USER
 *  @param    string              	$yearWeek            year week like 2015W09
 *  @param     int              	$userId         used in the form processing
 *  @param    string              	$tasktimeIds      limit the seach if defined
 *  @return     int                                       success (1) / failure (-1)
 */
    
    
    public function getActuals( $yearWeek,$userid,$tasktimeIds='')
    {

        $sql = "SELECT ptt.rowid, ptt.task_duration, ptt.task_date";	
        $sql .= " FROM ".MAIN_DB_PREFIX."projet_task_time AS ptt";
        $sql .= " WHERE ptt.fk_task='".$this->id."' ";
        $sql .= " AND (ptt.fk_user='".$userid."') ";
       # $sql .= "AND WEEKOFYEAR(ptt.task_date)='".date('W',strtotime($yearWeek))."';";
        #$sql .= "AND YEAR(ptt.task_date)='".date('Y',strtotime($yearWeek))."';";
        $sql .= " AND (ptt.task_date>=".$this->db->idate(strtotime($yearWeek)).") ";
        $sql .= " AND (ptt.task_date<".$this->db->idate(strtotime($yearWeek.' + 7 days')).")";
        if(!empty($tasktimeIds))$sql .= ' AND ptt.rowid in ('.$tasktimeIds.')';
        dol_syslog(get_class($this)."::fetchActuals sql=".$sql, LOG_DEBUG);
		for($i=0;$i<7;$i++){
			//fixme get ride of the db format for the date
			$this->tasklist[$i]=array('id'=>0,'duration'=>0,'date'=>strtotime( $yearWeek.' +'.$i.' day'));
		}

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

                        $this->tasklist[$day]=array('id'=>$obj->rowid,'date'=>$this->db->jdate($obj->task_date),'duration'=>$obj->task_duration);
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
    

 /*
 * function to form a HTMLform line for this timesheet
 * 
 *  @param    string              	$yearWeek            year week like 2015W09
 *  @param     int              	$line number         used in the form processing
 *  @param    string              	$headers             header to shows
 *  @param     int              	$whitelistemode           0-whiteliste,1-blackliste,2-non impact
 *  @param    string              	$status                 status of the Timesheet user
 *  @param    string              	$usUserId             id that will be used for the total
 *  @return     string                                        HTML result containing the timesheet info
 */
       public function getFormLine( $yearWeek,$lineNumber,$headers,$whitelistemode,$status="DRAFT",$tsUserId)
    {
           global $statusTsColor;
       if(empty($yearWeek)||empty($headers))
           return '<tr>ERROR: wrong parameters for getFormLine'.empty($yearWeek).'|'.empty($headers).'</tr>';
        
    $timetype=TIMESHEET_TIME_TYPE;
    $dayshours=TIMESHEET_DAY_DURATION;
    $hidezeros=TIMESHEET_HIDE_ZEROS;
    $hidden=false;
    if(($whitelistemode==0 && !$this->listed)||($whitelistemode==1 && $this->listed))$hidden=true;
        $linestyle=(($hidden)?'display:none;':'').(($this->pStatus == "2")?'background:#'.TIMESHEET_BC_FREEZED.'";':'');
        $html= '<tr '.((!empty($linestyle))?'style="'.$linestyle.'"':'').' class="'.(($lineNumber%2=='0')?'pair':'impair').'">'."\n"; 
        //title section
         foreach ($headers as $key => $title){
             $html.="\t<th align=\"left\">";
             switch($title){
                 case 'Project':
                     if(version_compare(DOL_VERSION,"3.7")>=0){
                     //if(file_exists("../projet/card.php")||file_exists("../../projet/card.php")){
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
                     $html.=$this->date_start?dol_print_date($this->date_start,'day'):'';
                     break;
                 case 'DateEnd':
                     $html.=$this->date_end?dol_print_date($this->date_end,'day'):'';
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
         $isOpenSatus=($status=="DRAFT" || $status=="CANCELLED"|| $status=="REJECTED");
         $opendays=str_split(TIMESHEET_OPEN_DAYS);

         for($dayOfWeek=0;$dayOfWeek<7;$dayOfWeek++)
         {
                //$shrinkedStyle=(!$opendays[$dayOfWeek+1] && $shrinked)?'display:none;':'';
                $today= strtotime($yearWeek.' +'.($dayOfWeek).' day  ');
                # to avoid editing if the task is closed 
                $dayWorkLoadSec=isset($this->tasklist[$dayOfWeek])?$this->tasklist[$dayOfWeek]['duration']:0;
                
                if ($timetype=="days")
                {
                    $dayWorkLoad=$dayWorkLoadSec/3600/$dayshours;
                }else {
                    $dayWorkLoad=date('H:i',mktime(0,0,$dayWorkLoadSec));
                }
			 	$startDates=($this->date_start>$this->startDatePjct )?$this->date_start:$this->startDatePjct;
			 	$stopDates=(($this->date_end<$this->stopDatePjct && $this->date_end!=0) || $this->stopDatePjct==0)?$this->date_end:$this->stopDatePjct;
                if($isOpenSatus){
                    $isOpen=$isOpenSatus && (($startDates==0) || ($startDates <= $today +86399));
                    $isOpen= $isOpen && (($stopDates==0) ||($stopDates >= $today ));
                    
                    $isOpen= $isOpen && ($this->pStatus < "2") && $opendays[$dayOfWeek+1];
                   // var_dump($opendays[$dayOfWeek+1]);
                    $bkcolor='';
                    
                    if($isOpen){
                        if($dayWorkLoadSec!=0)$bkcolor='background:#'.TIMESHEET_BC_VALUE;
                    }else{
                        $bkcolor='background:#'.TIMESHEET_BC_FREEZED;
                    } 
                    
                    
                    $html .= '<th  ><input type="text" '.(($isOpen)?'':'readonly').' class="time4day['.$tsUserId.']['.$dayOfWeek.']" ';
//                    $html .= 'name="task['.$tsUserId.']['.$this->id.']['.$dayOfWeek.']" '; if one whant multiple ts per validation
                    $html .= 'name="task['.$tsUserId.']['.$this->id.']['.$dayOfWeek.']" ';
                    $html .=' value="'.((($hidezeros==1) && ($dayWorkLoadSec==0))?"":$dayWorkLoad);
                    $html .='" maxlength="5" style="width: 90%;'.$bkcolor.'" ';
                    $html .='onkeypress="return regexEvent(this,event,\'timeChar\')" ';
                    $html .= 'onblur="validateTime(this,'.$tsUserId.','.$dayOfWeek.',0)" />';
                    $html .= "</th>\n"; 
                }else{
                    $bkcolor='background:#'.$statusTsColor[$status];
                    $html .= ' <th style="'.$bkcolor.'"><a class="time4day['.$tsUserId.']['.$dayOfWeek.']"';
                    //$html .= ' name="task['.$tsUserId.']['.$this->id.']['.$dayOfWeek.']" ';if one whant multiple ts per validation
                    $html .= ' name="task['.$this->id.']['.$dayOfWeek.']" ';
                    $html .= ' style="width: 90%;"';
                    $html .=' >'.((($hidezeros==1) && ($dayWorkLoadSec==0))?"":$dayWorkLoad);
                    $html .='</a> ';
                    $html .= "</th>\n"; 

                        
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
 * function to form a XML for this timesheet
 * 
 *  @param    string              	$yearWeek            year week like 2015W09
  *  @return     string                                         XML result containing the timesheet info
 */
    public function getXML( $yearWeek)
    {
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
        //return utf8_encode($xml);

    }	
/*
 * function to save a time sheet as a string
 */
function serialize(){
    $arRet=array();
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
    $arRet['pSatus']= $this->pStatus;
                      
    return serialize($arRet);
    
}
/*
 * function to load a time sheet as a string
 */
function unserialize($str){
    $arRet=unserialize($str);
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
    $this->companyId=$arRet['companyId'];
    $this->pStatus=$arRet['pSatus'];
}
 
    public function getTaskTab()
    {
        return $this->tasklist;
    }
public function updateTimeUsed()
    {
    $this->db->begin();
    $error=0;
          $sql ="UPDATE ".MAIN_DB_PREFIX."projet_task AS pt "
               ."SET pt.duration_effective=(SELECT SUM(ptt.task_duration) "
               ."FROM ".MAIN_DB_PREFIX."projet_task_time AS ptt "
               ."WHERE ptt.fk_task ='".$this->id."') "
               ."WHERE pt.rowid='".$this->id."' ";
   
            dol_syslog(get_class($this)."::UpdateTimeUsed sql=".$sql, LOG_DEBUG);


            $resql=$this->db->query($sql);
            if ($resql)
            {
                   // return 1;
            }
            else
            {
                    $this->error="Error ".$this->db->lasterror();
                    dol_syslog(get_class($this)."::fetch ".$this->error, LOG_ERR);

                    $error++;
            }	
                    // Commit or rollback
        if ($error)
        {
            foreach($this->errors as $errmsg)
            {
                dol_syslog(get_class($this)."::create ".$errmsg, LOG_ERR);
                $this->error.=($this->error?', '.$errmsg:$errmsg);
            }
            $this->db->rollback();
            return -1*$error;
        }else
        {
            $this->db->commit();
            return $this->id;
        }

    }
    function parseTaskTime($taskTime){
        
        $ret=floor($taskTime/3600).":".str_pad (floor($taskTime%3600/60),2,"0",STR_PAD_LEFT);
        
        return $ret;
        //return '00:00';
          
    }

    
	

 /* function to post on task_time
 * 
 *  @param    int              	$user                    user id to fetch the timesheets
 *  @param    object             	$tasktime             timesheet object, (task)
 *  @param    array(int)              	$tasktimeid          the id of the tasktime if any
 *  @param     int              	$timestamp          timesheetweek
 *  @return     int                                                       1 => succes , 0 => Failure
 */
function postTaskTimeActual($timesheetPost,$userId,$Submitter,$timestamp)
{
    $ret=0;
    $idList='';
	dol_syslog("Timesheet.class::postTaskTimeActual  taskTimeId=".$this->id, LOG_DEBUG);
        $this->timespent_fk_user=$userId;
    foreach ($timesheetPost as $dayKey => $wkload){		
        $item=$this->tasklist[$dayKey];
        
        if(TIMESHEET_TIME_TYPE=="days")
        {
           $duration=$wkload*TIMESHEET_DAY_DURATION*3600;
        }else
        {
         $durationTab=date_parse($wkload);
         $duration=$durationTab['minute']*60+$durationTab['hour']*3600;
        }
         dol_syslog("Timesheet.class::postTaskTimeActual    duration Old=".$item['duration']." New=".$duration." Id=".$item['id'].", date=".$item['date'], LOG_DEBUG);
        $this->timespent_date=$item['date'];
        if(isset( $this->timespent_datehour))
        {
            $this->timespent_datehour=$item['date'];
        }
        if($item['id']>0)
        {

            $this->timespent_id=$item['id'];
            $this->timespent_old_duration=$item['duration'];
            $this->timespent_duration=$duration; 
            if($item['duration']!=$duration)
            {
                if($this->timespent_duration>0){ 
                    dol_syslog("Timesheet::Submit.php  taskTimeUpdate", LOG_DEBUG);
                    if($this->updateTimeSpent($Submitter,0)>=0)
                    {
                        $ret++; 
                        $_SESSION['timesheetUser'][$timestamp]['timeSpendModified']++;
                    }else{
                        $_SESSION['timesheetUser'][$timestamp]['updateError']++;
                    }
                }else {
                    dol_syslog("Timesheet::Submit.php  taskTimeDelete", LOG_DEBUG);
                    if($this->delTimeSpent($Submitter,0)>=0)
                    {
                        $ret++;
                        $_SESSION['timesheetUser'][$timestamp]['timeSpendDeleted']++;
                        $this->tasklist[$dayKey]['id']=0;
                    }else{
                        $_SESSION['timesheetUser'][$timestamp]['updateError']++;
                    }
                }
            }
        } elseif ($duration>0)
        { 
            $this->timespent_duration=$duration; 
            $newId=$this->addTimeSpent($Submitter,0);
            if($newId>=0)
            {
                $ret++;
                $_SESSION['timesheetUser'][$timestamp]['timeSpendCreated']++;
                $this->tasklist[$dayKey]['id']=$newId;
            }else{
                $_SESSION['timesheetUser'][$timestamp]['updateError']++;
           }
        }
         //update the task list
        

        
    }
    if($ret)$this->updateTimeUsed();
    return $ret;
    //return $idList;
}
 
  /* function get the Id of the task
 * 
 *  @return     string      list of the id separated by coma
 */
function getIdList()
{
    $ret='';
    foreach ($this->tasklist as $tasktime){
        if(isset($tasktime['id']) && $tasktime['id']>0){
            $ret.=(empty($ret)?'':',').$tasktime['id'];
        }     
    }
    return $ret;
}

        	/**
	 *	function that will send email to 
	 *
	 *	@return	void
	 */
              function sendApprovalReminders(){
                  global $langs;
            $sql = 'SELECT';
            $sql.=' COUNT(t.rowid) as nb,';
            $sql.=' u.email,';
            $sql.=' u.fk_user as approverid';
            $sql.= ' FROM '.MAIN_DB_PREFIX.'timesheet_user as t';
            $sql.= ' JOIN '.MAIN_DB_PREFIX.'user as u on t.fk_userid=u.rowid ';
            $sql.= ' WHERE t.status="SUBMITTED" AND t.target="team"';
            $sql.= ' GROUP BY u.fk_user';
             dol_syslog(get_class($this)."::sendApprovalReminders sql=".$sql, LOG_DEBUG);
            $resql=$this->db->query($sql);
            
            if ($resql)
            {
                $num = $this->db->num_rows($resql);
                for( $i=0;$i<$num;$i++)
                {
                    $obj = $this->db->fetch_object($resql);
                    if ($obj)
                    {

                        $message=str_replace("__NB_TS__", $obj->nb, str_replace('\n', "\n",$langs->transnoentities('YouHaveApprovalPendingMsg')));
                        //$message="Bonjour,\n\nVous avez __NB_TS__ feuilles de temps à approuver, veuillez vous connecter à Dolibarr pour les approuver.\n\nCordialement.\n\nVotre administrateur Dolibarr.";
                        $sendto=$obj->email;
                        $replyto=$obj->email;
                        $subject=$langs->transnoentities("YouHaveApprovalPending");
                        if(!empty($sendto) && $sendto!="NULL"){
                           require_once DOL_DOCUMENT_ROOT .'/core/class/CMailFile.class.php';
                           $mailfile = new CMailFile(
	                        $subject,
	                        $sendto,
	                        $replyto,
	                        $message
	                    );
                           $mailfile->sendfile();
                        }
                        
                    }
                }

            }
            else
            {
                $error++;
                dol_print_error($db);
                $list= array();
            }
        }
}

?>
