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

require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
require_once 'class/holidayTimesheet.class.php';
require_once 'class/timesheet.class.php';
require_once 'class/timesheetwhitelist.class.php';
//require_once 'core/lib/timesheet.lib.php';
//dol_include_once('/timesheet/class/projectTimesheet.class.php');
//require_once './projectTimesheet.class.php';
//define('TIMESHEET_BC_FREEZED','909090');
//define('TIMESHEET_BC_VALUE','f0fff0');
class timesheetUser extends CommonObject
{
    //common
    	var $db;							//!< To store db handler
	var $error;							//!< To return error code (or message)
	var $errors=array();				//!< To return several error codes (or messages)
	var $element='timesheetuser';			//!< Id that identify managed objects
	var $table_element='timesheet_user';		//!< Name of table without prefix where object is stored
// from db
        var $id;
	var $userId;
	var $year_week_date='';
	var $status;
	var $target;
	var $project_tasktime_list;
	var $user_approval;
	var $date_creation='';
	var $date_modification='';
	var $user_creation;
	var $user_modification;
        var $note;
        var $fk_timesheet_user;
        var $fk_task;
        

//working variable
    var $user;
    var $yearWeek;
    var $holidays;
    var $taskTimesheet;
    var $headers;
    var $weekDays;
    var $timestamp;
    var $whitelistmode;
    var $userName;
    /**
     *   Constructor
     *
     *   @param		DoliDB		$db      Database handler
     */
    function __construct($db,$userId=0)
    {
        global $user;
        $this->db = $db;
        //$this->holidays=array();
        $this->user=$user;
        $this->userId= ($userId==0)?(is_object($user)?$user->id:$user):$userId;
        $this->headers=explode('||', TIMESHEET_HEADERS);
        $this->get_userName();
    }
    /* Funciton to fect the holiday of a single user for a single week.
    *  @param    string              	$yearWeek            year week like 2015W09
    *  @return     string                                       result
    */    
    function fetchAll($yearWeek,$whitelistmode=false){
        $this->whitelistmode=is_numeric($whitelistmode)?$whitelistmode:TIMESHEET_WHITELIST_MODE;
        $this->yearWeek=$yearWeek;
        $this->year_week_date=  strtotime($this->yearWeek);
        $this->timestamp=time();
        $ret=$this->fetchByWeek();
        $ret+=$this->fetchTaskTimesheet();
        //$ret+=$this->getTaskTimeIds(); 
        //FIXME module holiday should be activated ?
        $ret2=$this->fetchUserHoliday(); 
        //if ($ret<0 || $ret2<0) return -1;
        /*for ($i=0;$i<7;$i++)
        {
           $this->weekDays[$i]=date('d-m-Y',strtotime( $yearWeek.' +'.$i.' day'));
        }*/
        $this->saveInSession();
    }        
    
     /* Funciton to fect the holiday of a single user for a single week.
    *  @param    string              	$yearWeek            year week like 2015W09
    *  @return     string                                       result
    */
    function fetchUserHoliday(){
        $this->holidays=new holidayTimesheet($this->db);
        $ret=$this->holidays->fetchUserWeek($this->userId,$this->yearWeek);
        return $ret;
    }
    /*
 * function to load the parma from the session
 */
function loadFromSession($timestamp,$id){

    $this->fetch($id);
    $this->timestamp=$timestamp;
    $this->userId= $_SESSION['timesheetUser'][$timestamp][$id]['userId'];
    $this->yearWeek= $_SESSION['timesheetUser'][$timestamp][$id]['yearWeek'];
    $this->holidays=  unserialize( $_SESSION['timesheetUser'][$timestamp][$id]['holiday']);
    $this->taskTimesheet=  unserialize( $_SESSION['timesheetUser'][$timestamp][$id]['taskTimesheet']);;
}

    /*
 * function to load the parma from the session
 */
function saveInSession(){
    $_SESSION['timesheetUser'][$this->timestamp][$this->id]['userId']=$this->userId;
    //$_SESSION['timesheetUser'][$this->timestamp]['id']=$this->id;
    $_SESSION['timesheetUser'][$this->timestamp][$this->id]['yearWeek']=$this->yearWeek;
    $_SESSION['timesheetUser'][$this->timestamp][$this->id]['holiday']= serialize($this->holidays);
    $_SESSION['timesheetUser'][$this->timestamp][$this->id]['taskTimesheet']= serialize($this->taskTimesheet);
}
 /*
  * funciton getTaskTimeIds will load the tasktime id from the timesheet in case of draft timsesheet uesr
  */
function getSQLfetchtask(){
    //FIXME
    switch($this->status){
        case "DRAFT":
        case "REJECTED":
            //get the tasktime ID from the task timesheet
            break;
        Default:
            //do nothing the task timesheet are already present
            // could be goo to check that there is no discrepency
            break;
        
    }
}

/*
 * function to genegate the timesheet tab
 * 
 *  @param    int              	$userid                   user id to fetch the timesheets
 *  @return     array(string)                                             array of timesheet (serialized)
 */
 function fetchTaskTimesheet($userid=''){     

    if($userid==''){$userid=$this->userId;}
    $whiteList=array();
    $staticWhiteList=new Timesheetwhitelist($this->db);
    $datestart=strtotime($this->yearWeek.' +0 day');
    $datestop=strtotime($this->yearWeek.' +6 day');
    $whiteList=$staticWhiteList->fetchUserList($userid, $datestart, $datestop);
     // Save the param in the SeSSION
     $tasksList=array();
     $whiteListNumber=count($whiteList);
    if( $this->status=="DRAFT" || $this->status=="REJECTED"){
        $sql ="SELECT DISTINCT element_id as taskid,prj.fk_soc,tsk.fk_projet,tsk.fk_task_parent,tsk.rowid";
        if($whiteListNumber){
            $sql.=', (CASE WHEN tsk.rowid IN ('.implode(",",  $whiteList).') THEN \'1\' ';
            $sql.=' ELSE \'0\' END ) AS listed';
        }
        $sql.=" FROM ".MAIN_DB_PREFIX."element_contact "; 
        $sql.=' JOIN '.MAIN_DB_PREFIX.'projet_task as tsk ON tsk.rowid=element_id ';
        $sql.=' JOIN '.MAIN_DB_PREFIX.'projet as prj ON prj.rowid= tsk.fk_projet ';
        $sql.=" WHERE (fk_c_type_contact='181' OR fk_c_type_contact='180') AND fk_socpeople='".$userid."' ";
        if(TIMESHEET_HIDE_DRAFT=='1'){
             $sql.=' AND prj.fk_statut<>"0" ';
        }
        $sql.=' AND (prj.datee>='.$this->db->idate($datestart).' OR prj.datee IS NULL)';
        $sql.=' AND (prj.dateo<='.$this->db->idate($datestop).' OR prj.dateo IS NULL)';
        $sql.=' AND (tsk.datee>='.$this->db->idate($datestart).' OR tsk.datee IS NULL)';
        $sql.=' AND (tsk.dateo<='.$this->db->idate($datestop).' OR tsk.dateo IS NULL)';
        $sql.='  ORDER BY '.($whiteListNumber?'listed,':'').'prj.fk_soc,tsk.fk_projet,tsk.fk_task_parent,tsk.rowid ';

         dol_syslog("timesheet::getTasksTimesheet full sql=".$sql, LOG_DEBUG);
     }else{
         $list=empty($this->project_tasktime_list)?'0':$this->project_tasktime_list;
         $sql ='SELECT DISTINCT fk_task as taskid';
         if($whiteListNumber){
            $sql.=', (CASE WHEN fk_task IN ('.implode(",",  $whiteList).') THEN \'1\' ';
            $sql.=' ELSE \'0\' END ) AS listed';
        }
        $sql.=' FROM '.MAIN_DB_PREFIX.'projet_task_time';
        $sql.=' WHERE rowid in ('.$list.')';
 //       $sql.=' AND task_date>='.$this->db->idate($datestart);
 //       $sql.=' AND task_date<='.$this->db->idate($datestop);
         
        dol_syslog("timesheet::getTasksTimesheet reducted sql=".$sql, LOG_DEBUG);
     }
   
    $resql=$this->db->query($sql);
    if ($resql)
    {
        $this->taskTimesheet=array();
            $num = $this->db->num_rows($resql);
            $i = 0;
            // Loop on each record found, so each couple (project id, task id)
            while ($i < $num)
            {
                    $error=0;
                    $obj = $this->db->fetch_object($resql);
                    $tasksList[$i] = NEW timesheet($this->db, $obj->taskid);
                    $tasksList[$i]->listed=$obj->listed;
                    $i++;
            }
            $this->db->free($resql);
             $i = 0;
            if(isset($this->taskTimesheet))unset($this->taskTimesheet);
             foreach($tasksList as $row)
            {
                    dol_syslog("Timesheet::timesheetUser.class.php task=".$row->id, LOG_DEBUG);
                    $row->getTaskInfo();
                    if($this->status=="DRAFT" || $this->status=="REJECTED"){
                        $row->getActuals($this->yearWeek,$userid); 
                    }else{
                        //$row->getActuals($this->yearWeek,$userid); 
                        $row->getActuals($this->yearWeek,$userid,$this->project_tasktime_list); 
                    }
                    //unset($row->db);
                    $this->taskTimesheet[]=  $row->serialize();                   
            }

                
                return 1;

    }else
    {
            dol_print_error($this->db);
            return -1;
    }
 }
 
 
 
 /*AJAX function*/
 
/*
 * function to post on task_time
 * 
 *  @param    object              	$db                  database object
 *  @param    int                       $userid              timesheet object, (task)
 *  @param    string              	$yearWeek            year week like 2015W09
 *  @param     int              	$whitelistmode        whitelist mode, shows favoite o not 0-whiteliste,1-blackliste,2-non impact
 *  @return     string                                         XML result containing the timesheet info
 */
function GetTimeSheetXML()
{
    global $langs;
    $xml.= "<timesheet yearWeek=\"{$this->yearWeek}\" timestamp=\"{$this->timestamp}\" timetype=\"".TIMESHEET_TIME_TYPE."\"";
    $xml.=' nextWeek="'.date('Y\WW',strtotime($this->yearWeek."+3 days +1 week")).'" prevWeek="'.date('Y\WW',strtotime($this->yearWeek."+3 days -1 week")).'">';
    //error handling
    $xml.=getEventMessagesXML();
    //header
    $i=0;
    $xmlheaders=''; 
    foreach($this->headers as $header){
        if ($header=='Project'){
            $link=' link="'.DOL_URL_ROOT.'/projet/card.php?id="';
        }elseif ($header=='Tasks' || $header=='TaskParent'){
            $link=' link="'.DOL_URL_ROOT.'/projet/tasks/task.php?withproject=1&amp;id="';
        }elseif ($header=='Company'){
            $link=' link="'.DOL_URL_ROOT.'/societe/soc.php?socid="';
        }else{
            $link='';
        }
        $xmlheaders.= "<header col=\"{$i}\" name=\"{$header}\" {$link}>{$langs->transnoentitiesnoconv($header)}</header>";
        $i++;
    }
    $xml.= "<headers>{$xmlheaders}</headers>";
        //days
    $xmldays='';
    for ($i=0;$i<7;$i++)
    {
       $curDay=strtotime( $this->yearWeek.' +'.$i.' day');
       //$weekDays[$i]=date('d-m-Y',$curDay);
       $curDayTrad=$langs->trans(date('l',$curDay)).'  '.dol_mktime($curDay);
       $xmldays.="<day col=\"{$i}\">{$curDayTrad}</day>";
    }
    $xml.= "<days>{$xmldays}</days>";
        
        $tab=$this->fetchTaskTimesheet();
        $i=0;
        $xml.="<userTs userid=\"{$this->userId}\"  count=\"".count($this->taskTimesheet)."\" userName=\"{$this->userName}\" >";
        foreach ($this->taskTimesheet as $timesheet) {
            $row=new timesheet($this->db);
             $row->unserialize($timesheet);
            $xml.= $row->getXML($this->yearWeek);//FIXME
            $i++;
        }  
        $xml.="</userTs>";
    //}
    $xml.="</timesheet>";
    return $xml;
}	

    
    /*
     * DISPLAY FUNCTION
     */
     /*
 * function to genegate the timesheet table header
 * 
  *  @param    bool           $ajax     ajax of html behaviour
  *  @return     string                                                   html code
 */
 function getHTMLHeader($ajax=false){
     global $langs;
    $html="\n<table id=\"timesheetTable_{$this->id}\" class=\"noborder\" width=\"100%\">\n";

     $html.='<tr class="liste_titre" id="">'."\n";
     
     foreach ($this->headers as $key => $value){
         $html.="\t<th ";
//         if ($headersWidth[$key]){
 //               $html.='width="'.$headersWidth[$key].'"';
 //        }
         $html.=">".$langs->trans($value)."</th>\n";
     }
    $opendays=str_split(TIMESHEET_OPEN_DAYS);
    for ($i=0;$i<7;$i++)
    {
        $curDay=strtotime( $this->yearWeek.' +'.$i.' day');
//        $html.="\t".'<th width="60px"  >'.$langs->trans(date('l',$curDay)).'<br>'.dol_mktime($curDay)."</th>\n";
        $html.="\t".'<th width="60px"  >'.$langs->trans(date('l',$curDay)).'<br>'.dol_print_date($curDay,'day')."</th>\n";
    }
     $html.="</tr>\n";
     $html.='<tr id="hiddenParam" style="display:none;">';
     $html.= '<td colspan="'.($this->headers.lenght+7).'"> ';
    $html .= '<input type="hidden" name="timestamp" value="'.$this->timestamp."\"/>\n";
    $html .= '<input type="hidden" name="yearWeek" value="'.$this->yearWeek.'" />';  
     $html .= '<input type="hidden" name="tsUserId" value="'.$this->id.'" />'; 
    $html .='</td></tr>';

     return $html;
     
 }
 
/* function to genegate the timesheet table header
 * 
  *  @param    bool           $ajax     ajax of html behaviour
  *  @return     string                                                   html code
 */
 function getHTMLFormHeader($ajax=false){
     global $langs;
    $html ='<form id="timesheetForm" name="timesheet" action="?action=submit&wlm='.$this->whitelistmode.'" method="POST"';
    if($ajax)$html .=' onsubmit=" return submitTimesheet(0);"'; 
    $html .='>';
     return $html;
     
 }
  /* function to genegate ttotal line
 * 
  *  @param     string           $nameId            html name of the line
  *  @return     string                                                   html code
 */
 function getHTMLTotal(){

    $html .="<tr>\n";
    $html .='<th colspan="'.count($this->headers).'" align="right" > TOTAL </th>';
    for ($i=0;$i<7;$i++)
    {
       $html .="<th><div class=\"Total[{$this->id}][{$i}]\">&nbsp;</div></th>";
     }
    $html .='</tr>';
    return $html;
     
 }

  /* function to genegate the timesheet table header
 * 
  *  @param    array(string)           $headers            array of the header to show
 *  @param    array(int)              	$headersWidth    array defining the header width
 *  @param     int              	$yearWeek           timesheetweek
 *  @param     int              	$timestamp         timestamp
  *  @return     string                                                   html code
 */
 function getHTMLFooter($ajax=false){
     global $langs;
    //form button
    $html .= '</table>';
    $html .= '<div class="tabsAction">';
     $isOpenSatus=($this->status=="DRAFT" || $this->status=="CANCELLED"|| $this->status=="REJECTED");
    if($isOpenSatus){
        $html .= '<input type="submit" class="butAction" name="save" value="'.$langs->trans('Save')."\" />\n";
        //$html .= '<input type="submit" class="butAction" name="submit" onClick="return submitTs();" value="'.$langs->trans('Submit')."\" />\n";
        $html .= '<input type="submit" class="butAction" name="submit"  value="'.$langs->trans('Submit')."\" />\n";
        $html .= '<a class="butActionDelete" href="?action=list&yearweek='.$this->yearWeek.'">'.$langs->trans('Cancel').'</a>';

    }else if($this->status=="SUBMITTED")$html .= '<input type="submit" class="butAction" name="recall" " value="'.$langs->trans('Recall')."\" />\n";

    $html .= '</div>';
    $html .= "</form>\n";
    if($ajax){
    $html .= '<script type="text/javascript">'."\n\t";
    $html .='window.onload = function(){loadXMLTimesheet("'.$this->yearWeek.'",'.$this->userId.');}';

    $html .= "\n\t".'</script>'."\n";
    }
     return $html;
     
 }
   /* function to genegate the timesheet table header
 * 
  *  @param    array(string)           $headers            array of the header to show
 *  @param    array(int)              	$headersWidth    array defining the header width
 *  @param     int              	$yearWeek           timesheetweek
 *  @param     int              	$timestamp         timestamp
  *  @return     string                                                   html code
 */
 function getHTMLFooterAp($current,$timestamp){
     global $langs;
    //form button

    $html .= '<input type="hidden" name="timestamp" value="'.$timestamp."\"/>\n";
    $html .= '<input type="hidden" name="target" value="'.($current+1)."\"/>\n";
    $html .= '<div class="tabsAction">';
    if($offset==0 || $prevOffset!=$offset)$html .= '<input type="submit" class="butAction" name="Send" value="'.$langs->trans('Next')."\" />\n";
    //$html .= '<input type="submit" class="butAction" name="submit" onClick="return submitTs();" value="'.$langs->trans('Submit')."\" />\n";


    $html .= '</div>';
    $html .= "</form>\n";
    if($ajax){
    $html .= '<script type="text/javascript">'."\n\t";
    $html .='window.onload = function(){loadXMLTimesheet("'.$this->yearWeek.'",'.$this->userId.');}';

    $html .= "\n\t".'</script>'."\n";
    }
     return $html;
     
 }
      /*
 * function to genegate the timesheet list
 *  @return     string                                                   html code
 */
 function getHTMLtaskLines($ajax=false){

        $i=0;
        $Lines='';
        if(!$ajax & is_array($this->taskTimesheet)){
            foreach ($this->taskTimesheet as $timesheet) {
                $row=new timesheet($this->db);
                 $row->unserialize($timesheet);
                //$row->db=$this->db;
                $Lines.=$row->getFormLine( $this->yearWeek,$i,$this->headers,$this->whitelistmode,$this->status,$this->id);
				$i++;
            }
        }
        
        return $Lines;
 }    
   /* function to genegate the timesheet note
 * 
  *  @return     string                                                   html code
 */
 function getHTMLNote(){
     global $langs;
     $isOpenSatus=($this->status=="DRAFT" || $this->status=="CANCELLED"|| $this->status=="REJECTED");
     $html='<table class="noborder" width="50%"><tr><td>'.$langs->trans('note').'</td></tr><tr><td>';
   

    if($isOpenSatus){
        $html.='<textarea class="flat"  cols="75" name="Note['.$this->id.']" rows="3" >'.$this->note.'</textarea>';
        $html.='</td></tr></table>';
    }else if(!empty($this->note)){
        $html.=$this->note;
        $html.='</td></tr></table>';
    }else{
        $html="";
    }
    
    return $html;  
 }
        /*
 * function to genegate the timesheet list
 *  @return     string                                                   html code
 */
 function getHTMLHolidayLines($ajax=false){

        $i=0;
        $Lines='';
        if(!$ajax){
            $Lines.=$this->holidays->getHTMLFormLine( $this->yearWeek,$this->headers,$this->id);
        
        }
        
        return $Lines;
 }    
 /*
 * function to print the timesheet navigation header
 * 
 *  @param    string              	$yearWeek            year week like 2015W09
 *  @param     int              	$whitelistmode        whitelist mode, shows favoite o not 0-whiteliste,1-blackliste,2-non impact
 *  @param     object             	$form        		form object
 *  @return     string                                         HTML
 */
function getHTMLNavigation($optioncss, $ajax=false){
	global $langs;
        $form= new Form($this->db);
        $Nav=  '<table class="noborder" width="50%">'."\n\t".'<tr>'."\n\t\t".'<th>'."\n\t\t\t";
	if($ajax){
            $Nav.=  '<a id="navPrev" onClick="loadXMLTimesheet(\''.date('Y\WW',strtotime($this->yearWeek."+3 days  -1 week")).'\',0);';
        }else{
            $Nav.=  '<a href="?action=list&wlm='.$this->whitelistmode.'&yearweek='.date('Y\WW',strtotime($this->yearWeek."+3 days  -1 week"));   
        }
        if ($optioncss != '')$Nav.=   '&amp;optioncss='.$optioncss;
	$Nav.=  '">  &lt;&lt; '.$langs->trans("PreviousWeek").' </a>'."\n\t\t</th>\n\t\t<th>\n\t\t\t";
	if($ajax){
            $Nav.=  '<form name="goToDate" onsubmit="return toDateHandler();" action="?action=goToDate&wlm='.$this->whitelistmode.'" method="POST">'."\n\t\t\t";
        }else{
            $Nav.=  '<form name="goToDate" action="?action=goToDate&wlm='.$this->whitelistmode.'" method="POST" >'."\n\t\t\t";
        }
        $Nav.=   $langs->trans("GoTo").': '.$form->select_date(-1,'toDate',0,0,0,"",1,1,1)."\n\t\t\t";;
	$Nav.=  '<input type="submit" value="Go" /></form>'."\n\t\t</th>\n\t\t<th>\n\t\t\t";
	if($ajax){
            $Nav.=  '<a id="navNext" onClick="loadXMLTimesheet(\''.date('Y\WW',strtotime($this->yearWeek."+3 days  +1 week")).'\',0);';
	}else{
            $Nav.=  '<a href="?action=list&wlm='.$whitelistmode.'&yearweek='.date('Y\WW',strtotime($this->yearWeek."+3 days +1 week"));
            
        }
        if ($optioncss != '') $Nav.=   '&amp;optioncss='.$optioncss;
        $Nav.=  '">'.$langs->trans("NextWeek").' &gt;&gt; </a>'."\n\t\t</th>\n\t</tr>\n </table>\n";
        return $Nav;
}
/*
 * submit funcition FIXME
 */

 /*
 * function to post the all actual submitted
 * 
 *  @param    object             	$db                      db Object to do the querry
 *  @param    int              	$user                   user id to fetch the timesheets
 *  @param    array(int)              	$tabPost               array sent by POST with all info about the task
 *  @return     int                                                        number of tasktime creatd/changed
 */
 function updateActuals($tabPost)
{
    if($this->status=='APPROVED')
        return -1;
    dol_syslog('Entering in Timesheet::timesheetUser.php::updateActuals()');     
    $idList='';
   // $tmpRet=0;
    $_SESSION['timesheetUser'][$this->timestamp]['timeSpendCreated']=0;
    $_SESSION['timesheetUser'][$this->timestamp]['timeSpendDeleted']=0;
    $_SESSION['timesheetUser'][$this->timestamp]['timeSpendModified']=0;
        /*
         * For each task store in matching the session timestamp
         */
        foreach ($this->taskTimesheet as $row) {
            $tasktime= new timesheet($this->db);
            $tasktime->unserialize($row);     
            $ret=$tasktime->postTaskTimeActual($tabPost[$tasktime->id],$this->userId,$this->user, $this->timestamp);
            $taskList=$tasktime->getIdList();
            if(!empty($taskList)){
                $idList.=(empty($idList)?'':',').$taskList;
            }
            
        } 
    if(!empty($idList)){
        $this->project_tasktime_list=$idList;
        $this->update($this->user);
    }
    return $idList;
}

/*
 * function to get the name from a list of ID
 * 
  *  @param    object           	$db             database objet
 *  @param    array(int)/int        $userids    	array of manager id 
  *  @return  array (int => String)  				array( ID => userName)
 */
function get_userName(){


    $sql="SELECT usr.rowid, CONCAT(usr.firstname,' ',usr.lastname) as userName FROM ".MAIN_DB_PREFIX.'user AS usr WHERE';

	$sql.=' usr.rowid = '.$this->userId;
      dol_syslog("timesheetUser::get_userName sql=".$sql, LOG_DEBUG);
    $resql=$this->db->query($sql);
    
    if ($resql)
    {
        $i=0;
        $num = $this->db->num_rows($resql);
        if ( $num)
        {
            $obj = $this->db->fetch_object($resql);
            
            if ($obj)
            {
                $this->userName=$obj->userName;        
            }else{
                return -1;
            }
            $i++;
        }
        
    }
    else
    {
       return -1;
    }
      //$select.="\n";
    return 0;
 }
     /*
 * put the timesheet task in a approuved status
 * 
 *  @param      object/int        $user         user object or user id doing the modif
 *  @param      int               $id           id of the timesheetuser
 *  @return     int      		   	 <0 if KO, Id of created object if OK
 */
    Public function setAppoved($user,$id=0){
            $error=0;
            // Check parameters
            $userid=  is_object($user)?$user->id:$user;
            if($id==0)$id=$this->id;
		

        // Update request
        $sql = "UPDATE ".MAIN_DB_PREFIX.$this->table_element." SET";
        $sql.=' status="APPROVED",';
        $sql.=' fk_user_approval="'.$userid.'",';
        $sql.=' fk_user_modification="'.$userid.'"';
        $sql.= " WHERE rowid=".$id;

		$this->db->begin();

		dol_syslog(__METHOD__.$sql);
        $resql = $this->db->query($sql);
    	if (! $resql) { $error++; $this->errors[]="Error ".$this->db->lasterror(); }

		if (! $error)
		{
			if (! $notrigger)
			{
	            // Uncomment this and change MYOBJECT to your own tag if you
	            // want this action calls a trigger.

	            //// Call triggers
	            //$result=$this->call_trigger('MYOBJECT_MODIFY',$user);
	            //if ($result < 0) { $error++; //Do also what you must do to rollback action if trigger fail}
	            //// End call triggers
			 }
		}

        // Commit or rollback
		if ($error)
		{
			foreach($this->errors as $errmsg)
			{
	            dol_syslog(__METHOD__." ".$errmsg, LOG_ERR);
	            $this->error.=($this->error?', '.$errmsg:$errmsg);
			}
			$this->db->rollback();
			return -1*$error;
		}
		else
		{
			$this->db->commit();
			return 1;
		}

    }
    
 /*
 * put the timesheet task in a rejected status
 * 
 *  @param      object/int        $user         user object or user id doing the modif
 *  @param      int               $id           id of the timesheetuser
 *  @return     int      		   	 <0 if KO, Id of created object if OK
 */
    Public function setRejected($user,$id=0){
            $error=0;
            // Check parameters
            $userid=  is_object($user)?$user->id:$user;
            if($id==0)$id=$this->id;
		

        // Update request
        $sql = "UPDATE ".MAIN_DB_PREFIX.$this->table_element." SET";
        $sql.=' status="REJECTED",';
        $sql.=' fk_user_approval="'.$userid.'",';
        $sql.=' fk_user_modification="'.$userid.'"';
        $sql.= " WHERE rowid=".$id;

		$this->db->begin();

		dol_syslog(__METHOD__.$sql);
        $resql = $this->db->query($sql);
    	if (! $resql) { $error++; $this->errors[]="Error ".$this->db->lasterror(); }

		if (! $error)
		{
			if (! $notrigger)
			{
	            // Uncomment this and change MYOBJECT to your own tag if you
	            // want this action calls a trigger.

	            //// Call triggers
	            //$result=$this->call_trigger('MYOBJECT_MODIFY',$user);
	            //if ($result < 0) { $error++; //Do also what you must do to rollback action if trigger fail}
	            //// End call triggers
			 }
		}

        // Commit or rollback
		if ($error)
		{
			foreach($this->errors as $errmsg)
			{
	            dol_syslog(__METHOD__." ".$errmsg, LOG_ERR);
	            $this->error.=($this->error?', '.$errmsg:$errmsg);
			}
			$this->db->rollback();
			return -1*$error;
		}
		else
		{
			$this->db->commit();
			return 1;
		}
        
    }
    
 /*
 * put the timesheet task in a pending status
 * 
 *  @param      object/int        $user         user object or user id doing the modif
 *  @param      int               $id           id of the timesheetuser
 *  @return     int      		   	 <0 if KO, Id of created object if OK
*/
    Public function setSubmitted($user,$id=0){
            $error=0;
            // Check parameters
            $userid=  is_object($user)?$user->id:$user;
            if($id==0)$id=$this->id;
		

        // Update request
        $sql = "UPDATE ".MAIN_DB_PREFIX.$this->table_element." SET";
        $sql.=' status="SUBMITTED",';
        $sql.=' fk_user_approval="'.$userid.'",';
        $sql.=' fk_user_modification="'.$userid.'"';
        $sql.= " WHERE rowid=".$id;

		$this->db->begin();

		dol_syslog(__METHOD__.$sql);
        $resql = $this->db->query($sql);
    	if (! $resql) { $error++; $this->errors[]="Error ".$this->db->lasterror(); }

		if (! $error)
		{
			if (! $notrigger)
			{
	            // Uncomment this and change MYOBJECT to your own tag if you
	            // want this action calls a trigger.

	            //// Call triggers
	            //$result=$this->call_trigger('MYOBJECT_MODIFY',$user);
	            //if ($result < 0) { $error++; //Do also what you must do to rollback action if trigger fail}
	            //// End call triggers
			 }
		}

        // Commit or rollback
		if ($error)
		{
			foreach($this->errors as $errmsg)
			{
	            dol_syslog(__METHOD__." ".$errmsg, LOG_ERR);
	            $this->error.=($this->error?', '.$errmsg:$errmsg);
			}
			$this->db->rollback();
			return -1*$error;
		}
		else
		{
			$this->db->commit();
			return 1;
		}
        
    }

// funciton from db
    function create($user, $notrigger=0)
    {
    	global $conf, $langs;
		$error=0;

		// Clean parameters
        
		if (isset($this->userId)) $this->userId=trim($this->userId);
		if (isset($this->year_week_date)) $this->year_week_date=trim($this->year_week_date);
		if (isset($this->status)) $this->status=trim($this->status);
		if (isset($this->target)) $this->target=trim($this->target);
		if (isset($this->project_tasktime_list)) $this->project_tasktime_list=trim($this->project_tasktime_list);
		if (isset($this->user_approval)) $this->user_approval=trim($this->user_approval);
		if (isset($this->date_creation)) $this->date_creation=trim($this->date_creation);
		if (isset($this->date_modification)) $this->date_modification=trim($this->date_modification);
		if (isset($this->user_creation)) $this->user_creation=trim($this->user_creation);
		if (isset($this->user_modification)) $this->user_modification=trim($this->user_modification);
		if (isset($this->timesheetuser)) $this->timesheetuser=trim($this->timesheetuser);
		if (isset($this->task)) $this->task=trim($this->task);
		if (isset($this->note)) $this->note=trim($this->note);

        

		// Check parameters
		// Put here code to add control on parameters values

        // Insert request
		$sql = "INSERT INTO ".MAIN_DB_PREFIX.$this->table_element."(";
		
		$sql.= 'fk_userid,';
		$sql.= 'year_week_date,';
		$sql.= 'status,';
		$sql.= 'target,';
		$sql.= 'fk_project_tasktime_list,';
		$sql.= 'fk_user_approval,';
		$sql.= 'date_creation,';
		$sql.= 'fk_user_creation,';
                $sql.= 'fk_timesheet_user,';
                $sql.= 'fk_task,';
                $sql.= 'note';

		
        $sql.= ") VALUES (";
        
		$sql.=' '.(! isset($this->userId)?'NULL':'"'.$this->userId.'"').',';
		$sql.=' '.(! isset($this->year_week_date) || dol_strlen($this->year_week_date)==0?'NULL':'"'.$this->db->idate($this->year_week_date).'"').',';
		$sql.=' '.(! isset($this->status)?'"DRAFT"':'"'.$this->status.'"').',';
		$sql.=' '.(! isset($this->target)?'"team"':'"'.$this->target.'"').',';
		$sql.=' '.(! isset($this->project_tasktime_list)?'NULL':'"'.$this->db->escape($this->project_tasktime_list).'"').',';
		$sql.=' '.(! isset($this->user_approval)?'NULL':'"'.$this->user_approval.'"').',';

		$sql.=' NOW() ,';
		$sql.=' "'.$user->id.'",'; //fixme 3.5
		$sql.=' '.(! isset($this->timesheetuser)?'NULL':'"'.$this->timesheetuser.'"').',';
		$sql.=' '.(! isset($this->task)?'NULL':'"'.$this->task.'"').',';
		$sql.=' '.(! isset($this->note)?'NULL':'"'.$this->note.'"');
        
		$sql.= ")";

		$this->db->begin();

	   	dol_syslog(__METHOD__, LOG_DEBUG);
        $resql=$this->db->query($sql);
    	if (! $resql) { $error++; $this->errors[]="Error ".$this->db->lasterror(); }

		if (! $error)
        {
            $this->id = $this->db->last_insert_id(MAIN_DB_PREFIX.$this->table_element);

			if (! $notrigger)
			{
	            // Uncomment this and change MYOBJECT to your own tag if you
	            // want this action calls a trigger.

	            //// Call triggers
	            //$result=$this->call_trigger('MYOBJECT_CREATE',$user);
	            //if ($result < 0) { $error++; //Do also what you must do to rollback action if trigger fail}
	            //// End call triggers
			}
        }

        // Commit or rollback
        if ($error)
		{
			foreach($this->errors as $errmsg)
			{
	            dol_syslog(__METHOD__." ".$errmsg, LOG_ERR);
	            $this->error.=($this->error?', '.$errmsg:$errmsg);
			}
			$this->db->rollback();
			return -1*$error;
		}
		else
		{
			$this->db->commit();
            return $this->id;
		}
    }


    /**
     *  Load object in memory from the database
     *
     *  @param	int		$id    	Id object
     *  @param	string	$ref	Ref
     *  @return int          	<0 if KO, >0 if OK
     */
    function fetch($id,$ref='')
    {
    	global $langs;
        $sql = "SELECT";
		$sql.= " t.rowid,";
		
		$sql.=' t.fk_userid,';
		$sql.=' t.year_week_date,';
		$sql.=' t.status,';
		$sql.=' t.target,';
		$sql.=' t.fk_project_tasktime_list,';
		$sql.=' t.fk_user_approval,';

		$sql.=' t.date_creation,';
		$sql.=' t.date_modification,';
		$sql.=' t.fk_user_creation,';
		$sql.=' t.fk_user_modification,';
		$sql.=' t.fk_timesheet_user,';
		$sql.=' t.fk_task,';
		$sql.=' t.note';

		
        $sql.= " FROM ".MAIN_DB_PREFIX.$this->table_element." as t";
        if ($ref) $sql.= " WHERE t.ref = '".$ref."'";
        else $sql.= " WHERE t.rowid = ".$id;

    	dol_syslog(get_class($this)."::fetch");
        $resql=$this->db->query($sql);
        if ($resql)
        {
            if ($this->db->num_rows($resql))
            {
                $obj = $this->db->fetch_object($resql);

                $this->id    = $obj->rowid;
                $this->userId = $obj->fk_userid;
                $this->year_week_date = $this->db->jdate($obj->year_week_date);
                $this->status = $obj->status;
                $this->target = $obj->target;
                $this->project_tasktime_list = $obj->fk_project_tasktime_list;
                $this->user_approval = $obj->fk_user_approval;
                $this->date_creation = $this->db->jdate($obj->date_creation);
                $this->date_modification = $this->db->jdate($obj->date_modification);
                $this->user_creation = $obj->fk_user_creation;
                $this->user_modification = $obj->fk_user_modification;
                $this->timesheetuser = $obj->fk_timesheet_user;
                $this->task = $obj->fk_task;
                $this->note  = $obj->note;

                
            }
            $this->db->free($resql);
            $this->yearWeek=  date('Y\WW',$this->year_week_date);
            $this->whitelistmode=2; // no impact
            return 1;
        }
        else
        {
      	    $this->error="Error ".$this->db->lasterror();
            return -1;
        }
    }
    /**
     *  Load object in memory from the database
     *
     *  @return int          	<0 if KO, >0 if OK
     */
    function fetchByWeek()
    {
        
    	global $langs;
        $sql = "SELECT";
		$sql.= " t.rowid,";
		
		$sql.=' t.fk_userid,';
		$sql.=' t.year_week_date,';
		$sql.=' t.status,';
		$sql.=' t.target,';
		$sql.=' t.fk_project_tasktime_list,';
		$sql.=' t.fk_user_approval,';
		$sql.=' t.date_creation,';
		$sql.=' t.date_modification,';
		$sql.=' t.fk_user_creation,';
		$sql.=' t.fk_user_modification,';
		$sql.=' t.fk_timesheet_user,';
		$sql.=' t.fk_task,';
		$sql.=' t.note';

		
        $sql.= " FROM ".MAIN_DB_PREFIX.$this->table_element." as t";

        $sql.= " WHERE t.year_week_date = '".$this->db->idate($this->year_week_date)."'";
		$sql.= " AND t.fk_userid = '".$this->userId."'";
       # $sql .= "AND WEEKOFYEAR(ptt.year_week_date)='".date('W',strtotime($yearWeek))."';";
        #$sql .= "AND YEAR(ptt.task_date)='".date('Y',strtotime($yearWeek))."';";

        //$sql.= " AND t.rowid = ".$id;

    	dol_syslog(get_class($this)."::fetchByWeek");
        $resql=$this->db->query($sql);
        if ($resql)
        {
            if ($this->db->num_rows($resql))
            {
                $obj = $this->db->fetch_object($resql);

                $this->id    = $obj->rowid;
                
                $this->userId = $obj->fk_userid;
                $this->year_week_date = $this->db->jdate($obj->year_week_date);
                $this->status = $obj->status;
                $this->target = $obj->target;
                $this->project_tasktime_list = $obj->fk_project_tasktime_list;
                $this->user_approval = $obj->fk_user_approval;
                $this->date_creation = $this->db->jdate($obj->date_creation);
                $this->date_modification = $this->db->jdate($obj->date_modification);
                $this->user_creation = $obj->fk_user_creation;
                $this->user_modification = $obj->fk_user_modification;
                $this->timesheetuser = $obj->fk_timesheet_user;
                $this->task = $obj->fk_task;
                $this->note  = $obj->note;

                
            }else{
                unset($this->status) ;
                unset($this->target) ;
                unset($this->project_tasktime_list);
                unset($this->user_approval );
                unset($this->date_creation  );
                unset($this->date_modification );
                unset($this->user_creation );
                unset($this->user_modification );
                unset($this->timesheetuser );
                unset($this->task );
                unset($this->note );
                $this->create($this->user);
                $this->fetch($this->id);
            }
            $this->db->free($resql);

            return 1;
        }
        else
        {
      	    $this->error="Error ".$this->db->lasterror();
            return -1;
        }
    }


    /**
     *  Update object into database
     *
     *  @param	User	$user        User that modifies
     *  @param  int		$notrigger	 0=launch triggers after, 1=disable triggers
     *  @return int     		   	 <0 if KO, >0 if OK
     */
    function update($user, $notrigger=0)
    {
    	global $conf, $langs;
		$error=0;

		// Clean parameters
        
		if (isset($this->userId)) $this->userId=trim($this->userId);
		if (isset($this->year_week_date)) $this->year_week_date=trim($this->year_week_date);
		if (isset($this->status)) $this->status=trim($this->status);
		if (isset($this->target)) $this->target=trim($this->target);
		if (isset($this->project_tasktime_list)) $this->project_tasktime_list=trim($this->project_tasktime_list);
		if (isset($this->user_approval)) $this->user_approval=trim($this->user_approval);
		if (isset($this->date_creation)) $this->date_creation=trim($this->date_creation);
		if (isset($this->date_modification)) $this->date_modification=trim($this->date_modification);
		if (isset($this->user_creation)) $this->user_creation=trim($this->user_creation);
		if (isset($this->user_modification)) $this->user_modification=trim($this->user_modification);
		if (isset($this->timesheetuser)) $this->timesheetuser=trim($this->timesheetuser);
		if (isset($this->task)) $this->task=trim($this->task);
		if (isset($this->note)) $this->note=trim($this->note);

        

		// Check parameters
		// Put here code to add a control on parameters values

        // Update request
        $sql = "UPDATE ".MAIN_DB_PREFIX.$this->table_element." SET";
        
		$sql.=' fk_userid='.(empty($this->userId) ? 'null':'"'.$this->userId.'"').',';
		$sql.=' year_week_date='.(dol_strlen($this->year_week_date)!=0 ? '"'.$this->db->idate($this->year_week_date).'"':'null').',';
		$sql.=' status='.(empty($this->status)? 'null':'"'.$this->status.'"').',';
		$sql.=' target='.(empty($this->target) ? 'null':'"'.$this->target.'"').',';
		$sql.=' fk_project_tasktime_list='.(empty($this->project_tasktime_list) ? 'null':'"'.$this->db->escape($this->project_tasktime_list).'"').',';
		$sql.=' fk_user_approval='.(empty($this->user_approval) ? 'null':'"'.$this->user_approval.'"').',';
		$sql.=' date_modification=NOW() ,';
		$sql.=' fk_user_modification="'.$user->id.'",';
		$sql.=' fk_timesheet_user="'.$this->timesheetuser.'",';
		$sql.=' fk_task="'.$this->task.'",';
		$sql.=' note="'.$this->note.'"';

        
        $sql.= " WHERE rowid=".$this->id;

		$this->db->begin();

		dol_syslog(__METHOD__.$sql);
        $resql = $this->db->query($sql);
    	if (! $resql) { $error++; $this->errors[]="Error ".$this->db->lasterror(); }

		if (! $error)
		{
			if (! $notrigger)
			{
	            // Uncomment this and change MYOBJECT to your own tag if you
	            // want this action calls a trigger.

	            //// Call triggers
	            //$result=$this->call_trigger('MYOBJECT_MODIFY',$user);
	            //if ($result < 0) { $error++; //Do also what you must do to rollback action if trigger fail}
	            //// End call triggers
			 }
		}

        // Commit or rollback
		if ($error)
		{
			foreach($this->errors as $errmsg)
			{
	            dol_syslog(__METHOD__." ".$errmsg, LOG_ERR);
	            $this->error.=($this->error?', '.$errmsg:$errmsg);
			}
			$this->db->rollback();
			return -1*$error;
		}
		else
		{
			$this->db->commit();
			return 1;
		}
    }

     /**
     *	Return clickable name (with picto eventually)
     *
     *	@param		string			$htmlcontent 		text to show
     *	@param		int			$id                     Object ID
     *	@param		string			$ref                    Object ref
     *	@param		int			$withpicto		0=_No picto, 1=Includes the picto in the linkn, 2=Picto only
     *	@return		string						String with URL
     */
    function getNomUrl($htmlcontent,$id=0,$ref='',$withpicto=0)
    {
    	global $langs;

    	$result='';
        if(empty($ref) && $id==0){
            if(isset($this->id))  {
                $id=$this->id;
            }else if (isset($this->rowid)){
                $id=$this->rowid;
            }if(isset($this->ref)){
                $ref=$this->ref;
            }
        }
        
        if($id){
            $lien = '<a href="'.DOL_URL_ROOT.'/timesheet/timesheetuser.php?id='.$id.'&action=view">';
        }else if (!empty($ref)){
            $lien = '<a href="'.DOL_URL_ROOT.'/timesheet/timesheetuser.php?ref='.$ref.'&action=view">';
        }else{
            $lien =  "";
        }
        $lienfin=empty($lien)?'':'</a>';

    	$picto='timesheet@timesheet';
        
        if($ref){
            $label=$langs->trans("Show").': '.$ref;
        }else if($id){
            $label=$langs->trans("Show").': '.$id;
        }
    	if ($withpicto==1){ 
            $result.=($lien.img_object($label,$picto).$htmlcontent.$lienfin);
        }else if ($withpicto==2) {
            $result.=$lien.img_object($label,$picto).$lienfin;
        }else{  
            $result.=$lien.$htmlcontent.$lienfin;
        }
    	return $result;
    }    
 	/**
	 *  Delete object in database
	 *
     *	@param  User	$user        User that deletes
     *  @param  int		$notrigger	 0=launch triggers after, 1=disable triggers
	 *  @return	int					 <0 if KO, >0 if OK
	 */
	function delete($user, $notrigger=0)
	{
		global $conf, $langs;
		$error=0;

		$this->db->begin();

		if (! $error)
		{
			if (! $notrigger)
			{
				// Uncomment this and change MYOBJECT to your own tag if you
		        // want this action calls a trigger.

	            //// Call triggers
	            //$result=$this->call_trigger('MYOBJECT_DELETE',$user);
	            //if ($result < 0) { $error++; //Do also what you must do to rollback action if trigger fail}
	            //// End call triggers
			}
		}

		if (! $error)
		{
    		$sql = "DELETE FROM ".MAIN_DB_PREFIX.$this->table_element;
    		$sql.= " WHERE rowid=".$this->id;

    		dol_syslog(__METHOD__);
    		$resql = $this->db->query($sql);
        	if (! $resql) { $error++; $this->errors[]="Error ".$this->db->lasterror(); }
		}

        // Commit or rollback
		if ($error)
		{
			foreach($this->errors as $errmsg)
			{
	            dol_syslog(__METHOD__." ".$errmsg, LOG_ERR);
	            $this->error.=($this->error?', '.$errmsg:$errmsg);
			}
			$this->db->rollback();
			return -1*$error;
		}
		else
		{
			$this->db->commit();
			return 1;
		}
	}



	/**
	 *	Load an object from its id and create a new one in database
	 *
	 *	@param	int		$fromid     Id of object to clone
	 * 	@return	int					New id of clone
	 */
	function createFromClone($fromid)
	{
		global $user,$langs;

		$error=0;

		$object=new Timesheetuser($this->db);

		$this->db->begin();

		// Load source object
		$object->fetch($fromid);
		$object->id=0;
		$object->statut=0;

		// Clear fields
		// ...

		// Create clone
		$result=$object->create($user);

		// Other options
		if ($result < 0)
		{
			$this->error=$object->error;
			$error++;
		}

		if (! $error)
		{


		}

		// End
		if (! $error)
		{
			$this->db->commit();
			return $object->id;
		}
		else
		{
			$this->db->rollback();
			return -1;
		}
	}



	/**
	 *	Initialise object with example values
	 *	Id must be 0 if object instance is a specimen
	 *
	 *	@return	void
	 */
	function initAsSpecimen()
	{
		$this->id=0;
		
		$this->userId='';
		$this->year_week_date='';
		$this->status='';
		$this->target='';
		$this->project_tasktime_list='';
		$this->user_approval='';
		$this->date_creation='';
		$this->date_modification='';
		$this->user_creation='';
		$this->user_modification='';
		$this->timesheetuser='';
		$this->task='';
		$this->note='';

		
	}
      
 }

?>


