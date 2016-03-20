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
//require_once 'lib/timesheet.lib.php';
//dol_include_once('/timesheet/class/projectTimesheet.class.php');
//require_once './projectTimesheet.class.php';
//define('TIMESHEET_BC_FREEZED','909090');
//define('TIMESHEET_BC_VALUE','f0fff0');
class userTimesheet extends CommonObject
{
    var $userId;
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
    function __construct($db,$user)
    {
        $this->db = $db;
        //$this->holidays=array();
        $this->user=$user;
        $this->userId= is_object($user)?$user->id:$user;
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
        $this->timestamp=time();
        $ret=$this->fetchTaskTimesheet();
        //FIXME module holiday should be activated ?
        $ret2=$this->fetchUserHoliday(); 
        //if ($ret<0 || $ret2<0) return -1;
        for ($i=0;$i<7;$i++)
        {
           $this->weekDays[$i]=date('d-m-Y',strtotime( $yearWeek.' +'.$i.' day'));
        }
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
function loadFromSession($timestamp){
    
    $this->timestamp=$timestamp;
    $this->userId= $_SESSION['userTimesheet'][$timestamp]['userId'];
    $this->yearWeek= $_SESSION['userTimesheet'][$timestamp]['yearWeek'];
    $this->holidays=  unserialize( $_SESSION['userTimesheet'][$timestamp]['holiday']);
    $this->taskTimesheet=  unserialize( $_SESSION['userTimesheet'][$timestamp]['taskTimesheet']);;
}

    /*
 * function to load the parma from the session
 */
function saveInSession(){
    $_SESSION['userTimesheet'][$this->timestamp]['userId']=$this->userId;
    $_SESSION['userTimesheet'][$this->timestamp]['yearWeek']=$this->yearWeek;
    $_SESSION['userTimesheet'][$this->timestamp]['holiday']= serialize($this->holidays);
    $_SESSION['userTimesheet'][$this->timestamp]['taskTimesheet']= serialize($this->taskTimesheet);
}

/*
 * function to genegate the timesheet tab
 * 
 *  @param    object             	$db                 db Object to do the querry
 *  @param    array(string)           $headers            array of the header to show
 *  @param    int              	$user                   user id to fetch the timesheets
 *  @param     int              	$yearWeek           timesheetweek
 *  @param    array(int)              	$whiteList    array defining the header width
 *  @param     int              	$timestamp         timestamp
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
    $sql ="SELECT DISTINCT element_id";
    if($whiteListNumber){
        $sql.=', (CASE WHEN tsk.rowid IN ('.implode(",",  $whiteList).') THEN \'1\' ';
        $sql.=' ELSE \'0\' END ) AS listed';
    }
    $sql.=" FROM ".MAIN_DB_PREFIX."element_contact "; 
    $sql.=' JOIN '.MAIN_DB_PREFIX.'projet_task as tsk ON tsk.rowid=element_id ';
    $sql.=' JOIN '.MAIN_DB_PREFIX.'projet as prj ON prj.rowid= tsk.fk_projet ';
    $sql.=" WHERE (fk_c_type_contact='181' OR fk_c_type_contact='180') AND fk_socpeople='".$userid."' ";
    if(TIMESHEET_HIDE_DRAFT=='1'){
         $sql.=' AND prj.fk_statut="1" ';
    }
    $sql.=' AND (prj.datee>='.$this->db->idate($datestart).' OR prj.datee IS NULL)';
    $sql.=' AND (prj.dateo<='.$this->db->idate($datestop).' OR prj.dateo IS NULL)';
    $sql.=' AND (tsk.datee>='.$this->db->idate($datestart).' OR tsk.datee IS NULL)';
    $sql.=' AND (tsk.dateo<='.$this->db->idate($datestop).' OR tsk.dateo IS NULL)';
    $sql.='  ORDER BY '.($whiteListNumber?'listed,':'').'prj.fk_soc,tsk.fk_projet,tsk.fk_task_parent,tsk.rowid ';

    dol_syslog("timesheet::getTasksTimesheet sql=".$sql, LOG_DEBUG);
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
                    $tasksList[$i] = NEW timesheet($this->db, $obj->element_id);
                    $tasksList[$i]->listed=$obj->listed;
                    $i++;
            }
            $this->db->free($resql);
             $i = 0;
            if(isset($this->taskTimesheet))unset($this->taskTimesheet);
             foreach($tasksList as $row)
            {
                    dol_syslog("Timesheet::userTimesheet.class.php task=".$row->id, LOG_DEBUG);
                    $row->getTaskInfo();
                    $row->getActuals($this->yearWeek,$userid); 
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
       $curDayTrad=$langs->trans(date('l',$curDay)).'  '.date('d/m/y',$curDay);
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
  *  @param    array(string)           $headers            array of the header to show
 *  @param    array(int)              	$headersWidth    array defining the header width
 *  @param     int              	$yearWeek           timesheetweek
 *  @param     int              	$timestamp         timestamp
  *  @return     string                                                   html code
 */
 function getHTMLHeader($ajax=false){
     global $langs;
    $html ='<form id="timesheetForm" name="timesheet" action="?action=submit&wlm='.$this->whitelistmode.'" method="POST"';
    if($ajax)$html .=' onsubmit=" return submitTimesheet(0);"'; 
    $html.=">\n<table id=\"timesheetTable\" class=\"noborder\" width=\"100%\">\n";

     $html.='<tr class="liste_titre" id="">'."\n";
     
     foreach ($this->headers as $key => $value){
         $html.="\t<th ";
//         if ($headersWidth[$key]){
 //               $html.='width="'.$headersWidth[$key].'"';
 //        }
         $html.=">".$langs->trans($value)."</th>\n";
     }
    
    for ($i=0;$i<7;$i++)
    {
        $curDay=strtotime( $this->yearWeek.' +'.$i.' day');
        $html.="\t".'<th width="60px">'.$langs->trans(date('l',$curDay)).'<br>'.date('d/m/y',$curDay)."</th>\n";
    }
     $html.="</tr>\n";
     $html.='<tr id="hiddenParam" style="display:none;"><td colspan="'.($this->headers.lenght+7).'"> ';
    $html .= '<input type="hidden" name="timestamp" value="'.$this->timestamp."\"/>\n";
    $html .= '<input type="hidden" name="yearWeek" value="'.$this->yearWeek.'" />';        
    $html .='</td></tr>';

     return $html;
     
 }
  /* function to genegate ttotal line
 * 
  *  @param    array(string)           $headers            array of the header to show
 *  @param    array(int)              	$headersWidth    array defining the header width
 *  @param     int              	$yearWeek           timesheetweek
 *  @param     int              	$timestamp         timestamp
  *  @return     string                                                   html code
 */
 function getHTMLTotal($nameId){

    $html .="<tr id='{$nameId}'>\n";
    $html .='<th colspan="'.count($this->headers).'" align="right" > TOTAL </th>';
    for ($i=0;$i<7;$i++)
    {
       $html .="<th><div id=\"{$nameId}[{$i}]\">&nbsp;</div></th>";
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
$html .= '<input type="submit" value="'.$langs->trans('Save')."\" />\n";
//$html .= '<input type="button" value="'.$langs->trans('Submit');
//$html .= '" onClick="document.location.href=\'?action=submit&yearweek='.$yearWeek."'\"/>\n"; /*FIXME*/
$html .= '<input type="button" value="'.$langs->trans('Cancel');
$html .= '" onClick="document.location.href=\'?action=list&yearweek='.$this->yearWeek."'\"/>\n";
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
        if(!$ajax){
            foreach ($this->taskTimesheet as $timesheet) {
                $row=new timesheet($this->db);
                 $row->unserialize($timesheet);
                //$row->db=$this->db;
                $Lines.=$row->getFormLine( $this->yearWeek,$i,$this->headers,$this->whitelistmode);
            }
        }
        
        return $Lines;
 }    

       /*
 * function to genegate the timesheet list
 *  @return     string                                                   html code
 */
 function getHTMLHolidayLines($ajax=false){

        $i=0;
        $Lines='';
        if(!$ajax){
            $Lines.=$this->holidays->getHTMLFormLine( $this->yearWeek,$this->headers);
        
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
        $Nav.=   $langs->trans("GoToDate").': '.$form->select_date(-1,'toDate',0,0,0,"",1,0,1)."\n\t\t\t";;
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
    dol_syslog('Entering in Timesheet::userTimesheet.php::updateActuals()');     
    $ret=0;
   // $tmpRet=0;
    $_SESSION['timeSpendCreated']=0;
    $_SESSION['timeSpendDeleted']=0;
    $_SESSION['timeSpendModified']=0;
        /*
         * For each task store in matching the session timestamp
         */
        foreach ($this->taskTimesheet as $row) {
            $tasktime= new timesheet($this->db);
            $tasktime->unserialize($row);     
            $ret+=$tasktime->postTaskTimeActual($tabPost[$tasktime->id],$this->userId,$this->user);
    }   
    return $ret;
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
      dol_syslog("userTimesheet::get_userName sql=".$sql, LOG_DEBUG);
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
 *  @param      string            $yearWeek      year week like 2015W09
 *  @param      int               $userid        change the status for this userid 
 *  @return     int      		   	 <0 if KO, Id of created object if OK
 */
    Public function setAppouved($yearWeek,$userid){
        
    }
    
 /*
 * put the timesheet task in a rejected status
 * 
 *  @param    string              	$yearWeek            year week like 2015W09
 *  @param      int               $userid        change the status for this userid 
 *  @return     int      		   	 <0 if KO, Id of created object if OK
 */
    Public function setRejected($yearWeek,$userid){
        
    }
    
 /*
 * put the timesheet task in a pending status
 * 
 *  @param    string              	$yearWeek            year week like 2015W09
  *  @param      int               $userid        change the status for this userid 
 *  @return     int      		   	 <0 if KO, Id of created object if OK
*/
    Public function setPending($yearWeek,$userid){
        
    }
}
?>


