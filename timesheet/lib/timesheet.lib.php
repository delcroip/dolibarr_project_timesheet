<?php
/*
 * Copyright (C) 2014	   Patrick DELCROIX     <pmpdelcroix@gmail.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */
//global $db;     
global $langs;
// to get the whitlist object
dol_include_once('/timesheet/class/timesheetwhitelist.class.php');
dol_include_once('/timesheet/class/timesheet.class.php');
/*
 * function to genegate a select list from a table, the showed text will be a concatenation of some 
 * column defined in column bit, the Least sinificative bit will represent the first colum 
 * 
 *  @param    object             	$db                 db Object to do the querry
 *  @param    string              	$table              table which the enum refers to (without prefix)
 *  @param    string              	$fieldValue         field of the table which the enum refers to
 *  @param    string              	$htmlName           name to the form select
 *  @param    string              	$selected           which value must be selected
 *  @param    string              	$selectparam          to add parameters to the select
 *  @return string                                                   html code
 */
 
function select_enum($db,$table, $fieldValue,$htmlName,$selected='',$selectparam=''){
global $langs;
    if($table=='' || $fieldValue=='' || $htmlName=='' )
    {
        return 'error, one of the mandatory field of the function  select_enum is missing';
    }    
    $sql='SHOW COLUMNS FROM ';//llx_hr_event_time LIKE 'audience'";
    $sql.=MAIN_DB_PREFIX.$table.' WHERE Field="';
    $sql.=$fieldValue.'"';
    //$sql.= " ORDER BY t.".$field;
       
    dol_syslog('form::select_enum sql='.$sql, LOG_DEBUG);
    
    $resql=$db->query($sql);
    
    if ($resql)
    {
        $i=0;
         //return $table."this->db".$field;
        $num = $db->num_rows($resql);
        if($num)
        {
           
            $obj = $db->fetch_object($resql);
            if ($obj && strpos($obj->Type,'enum(')===0)
            {
                if(empty($selected) && !empty($obj->Default))$selected="'{$obj->Default}'";
                    $select.='<select class="flat minwidth200" id="'.$htmlName.'Select" name="'.$htmlName.'"'.$nodatarole.' '.$selectparam.'>';
                    $select.= '<option value="-1" '.(empty($selected)?'selected="selected"':'').">&nbsp;</option>\n";

                $enums= explode(',',substr($obj->Type, 5,-1));
                foreach ($enums as $enum){
                    $select.= '<option value="'.(substr($enum,1,-1)).'" ';
                    $select.=((substr($enum,1,-1)===$selected)?'selected="selected" >':'>');                    
                    $select.=$langs->trans(substr($enum,1,-1));          
                    $select.="</option>\n";
                }
                $select.= '<option value="NULL" '.(($selected=='NULL')?'selected':'').">NULL</option>\n";
                $select.="</select>\n";
            }else{
                $select="<input selected=\"{$selected}\" id=\"{$htmlName} \" name=\"{$htmlName}\">";
            }
 
        }else{
                $select="<input selected=\"{$selected}\" id=\"{$htmlName} \" name=\"{$htmlName}\">";
        }
    }
    else
    {
        $error++;
        dol_print_error($db);
       $select="<input selected=\"{$selected}\" id=\"{$htmlName} \" name=\"{$htmlName}\">";
    }
      
      return $select;
    
 }
/*
 * function to genegate a select list from a table, the showed text will be a concatenation of some 
 * column defined in column bit, the Least sinificative bit will represent the first colum 
 * 
 *  @param    object             	$db                 db Object to do the querry
 *  @param    string              	$table                 table which the fk refers to (without prefix)
 *  @param    string              	$fieldValue         field of the table which the fk refers to, the one to put in the Valuepart
 *  @param    string              	$htmlName        name to the form select
 *  @param    string              	$fieldToShow1    first part of the concatenation
 *  @param    string              	$fieldToShow1    second part of the concatenation
 *  @param    string              	$selected            which value must be selected
 *  @param    string              	$separator          separator between the tow contactened fileds
*  @param    string              	$sqlTail              to limit per entity, to filter ...
*  @param    string              	$selectparam          to add parameters to the select

 *  @return string                                                   html code
 */
function select_generic($db, $table, $fieldValue,$htmlName,$fieldToShow1,$fieldToShow2='',$selected='',$separator=' - ',$sqlTail='', $selectparam=''){
     //
    global $conf,$langs;
    if($table=='' || $fieldValue=='' || $fieldToShow1=='' || $htmlName=='' )
    {
        return 'error, one of the mandatory field of the function  select_generic is missing';
    }
    $select="\n";
    if ($conf->use_javascript_ajax)
    {
        include_once DOL_DOCUMENT_ROOT . '/core/lib/ajax.lib.php';
        $comboenhancement = ajax_combobox($htmlName);
        $select.=$comboenhancement;
        $nodatarole=($comboenhancement?' data-role="none"':'');
    }
    $select.='<select class="flat minwidth200" id="'.$htmlName.'" name="'.$htmlName.'"'.$nodatarole.' '.$selectparam.'>';
    $sql='SELECT';
    $sql.=' t.'.$fieldValue;
    $sql.=' ,'.$fieldToShow1;
    if(!empty($fieldToShow2))
        $sql.=' ,'.$fieldToShow2;
    $sql.= ' FROM '.MAIN_DB_PREFIX.$table.' as t';
    if(!empty($sqlTail))
            $sql.=' '.$sqlTail;
    //$sql.= " ORDER BY t.".$field;
       
    dol_syslog('form::select_generic sql='.$sql, LOG_DEBUG);
    
    $resql=$db->query($sql);
   
    if ($resql)
    {
          // support AS in the fields ex $field1='CONTACT(u.firstname,' ',u.lastname) AS fullname'
        // with sqltail= 'JOIN llx_user as u ON t.fk_user=u.rowid'
        $starfields1=strpos($fieldToShow1,' AS ');
        if($starfields1>0)
            $fieldToShow1=  substr($fieldToShow1, $starfields1+4);
        $starfields2=strpos($fieldToShow2,' AS ');
        if($starfields2>0)
            $fieldToShow2=  substr($fieldToShow2, $starfields2+4);

        $select.= "<option value=\"-1\" ".(empty($selected)?"selected":"").">&nbsp;</option>\n";
        $i=0;
         //return $table."this->db".$field;
        $num = $db->num_rows($resql);
        while ($i < $num)
        {
            
            $obj = $db->fetch_object($resql);
            
            if ($obj)
            {
                    $select.= "<option value=\"".$obj->{$fieldValue}."\" ";
                    $select.=(($obj->{$fieldValue}==$selected)?"selected=\"selected\" >":">");                    
                    $select.=$obj->{$fieldToShow1};
                    if(!empty($fieldToShow2))
                         $select.=$separator.$obj->{$fieldToShow2};            
                    $select.="</option>\n";
            } 
            $i++;
        }
       $select.= "<option value=\"NULL\" ".(($selected=='NULL')?"selected":"").">NULL</option>\n";
        
    }
    else
    {
        $error++;
        dol_print_error($db);
       $select.= "<option value=\"-1\" selected=\"selected\">ERROR</option>\n";
    }
      $select.="</select>\n";
      return $select;
    
 }
 
 
/*
 * function to genegate a select list from a table, the showed text will be a concatenation of some 
 * column defined in column bit, the Least sinificative bit will represent the first colum 
 * 
 *  @param    object             	$db                 db Object to do the querry
 *  @param    string              	$table                 table which the fk refers to (without prefix)
 *  @param    string              	$fieldValue         field of the table which the fk refers to, the one to put in the Valuepart
 *  @param    string              	$selected           value selected of the field value column
 *  @param    string              	$fieldToShow1    first part of the concatenation
 *  @param    string              	$fieldToShow1    second part of the concatenation
 *  @param    string              	$separator          separator between the tow contactened fileds
 *  @param    string              	$sqlTail              to limit per entity, to filter ...

 *  @return string                                                   html code
 */
function print_generic($db,$table, $fieldValue,$selected,$fieldToShow1,$fieldToShow2="",$separator=' - ',$sqltail="",$sqljoin=""){
   //return $table.$db.$field;
    if($table=="" || $fieldValue=="" || $fieldToShow1=='')
    {
        return "error, one of the mandatory field of the function  print_generic is missing";
    }else if (empty($selected)){
        return "NuLL";
    }
    
    $sql="SELECT";
    $sql.=" t.".$fieldValue;
    $sql.=" ,".$fieldToShow1;
    if(!empty($fieldToShow2))
        $sql.=" ,".$fieldToShow2;
    $sql.= " FROM ".MAIN_DB_PREFIX.$table." as t";
    if(!empty($sqljoin))
        $sql.=' '.$sqljoin;
    $sql.= " WHERE t.".$fieldValue."=".$selected;
    if(!empty($sqlTail))
            $sql.=' '.$sqlTail;
       
    dol_syslog("form::print_generic sql=".$sql, LOG_DEBUG);
    
    $resql=$db->query($sql);
    
    if ($resql)
    {
    // support AS in the fields ex $field1='CONTACT(u.firstname,' ',u.lastname) AS fullname'
     // with sqltail= 'JOIN llx_user as u ON t.fk_user=u.rowid'
     $starfields1=strpos($fieldToShow1,' AS ');
     if($starfields1>0){
         $fieldToShow1=  substr($fieldToShow1, $starfields1+4);
     }
     $starfields2=strpos($fieldToShow2,' AS ');
     if($starfields2>0){
         $fieldToShow2=substr($fieldToShow2, $starfields2+4);
      }

        $num = $db->num_rows($resql);
        if ( $num)
        {
            $obj = $db->fetch_object($resql);
            
            if ($obj)
            {
                            $select=$obj->{$fieldToShow1};
                            if(!empty($fieldToShow2))
                                 $select.=$separator.$obj->{$fieldToShow2};        
            }else{
                $select= "NULL";
            }
        }else{
            $select= "NULL";
        }
    }
    else
    {
        $error++;
        dol_print_error($db);
       $select.= "ERROR";
    }
      //$select.="\n";
      return $select;
 }

 /*
 * function to genegate a select list from a table, the showed text will be a concatenation of some 
 * column defined in column bit, the Least sinificative bit will represent the first colum 
 * 
 *  @param    object             	$db                 db Object to do the querry
 *  @param    int/array                       $userid             ID of the user you want to get the subordinate liste *  @param    int                       $userid             ID of the user you want to get the subordinate liste
 *  @param    int                       $entity             entity 
 *  @return   array                                         List of the subordinate ids  and level [[id][lvl]]                                          
 */
function get_subordinate($db,$userid, $depth=5,$ecludeduserid=array(),$entity='1'){
    if($userid=="")
    {
        return array();
    }
    
    $sql="SELECT usr.rowid FROM ".MAIN_DB_PREFIX.'user AS usr WHERE';
    if(is_array($userid)){
        $ecludeduserid=array_merge($userid,$ecludeduserid);
        $sql.=' usr.fk_user in (';
        foreach($userid as $id)
        {
            $sql.='"'.$id.'",';
        }
        $sql.='-999)';
    }else{
        $ecludeduserid[]=$userid;
        $sql.=' usr.fk_user ="'.$userid.'"';
    }
    if(is_array($ecludeduserid)){
        $sql.=' AND usr.rowid not in (';
        foreach($ecludeduserid as $id)
        {
            $sql.='"'.$id.'",';
        }
        $sql.='0)';
    }else if (!empty($ecludeduserid)){
        $sql.=' AND usr.rowid <>"'.$ecludeduserid.'"';
    } 
       
    dol_syslog("form::get_subordinate sql=".$sql, LOG_DEBUG);
    $list=array();
    $resql=$db->query($sql);
    
    if ($resql)
    {
        $i=0;
        $num = $db->num_rows($resql);
        while ( $i<$num)
        {
            $obj = $db->fetch_object($resql);
            
            if ($obj)
            {
                $list[]=$obj->rowid;        
            }
            $i++;
        }
        if(count($list)>0 && $depth>1){
            //this will get the same result plus the subordinate of the subordinate
            $result=get_subordinate($db,$list,$depth-1,$ecludeduserid, $entity);
            if(is_array($result))
            {
                $list=array_merge($list,$result);
            }
        }
        if(is_array($userid))
        {
            
            $list=array_merge($list,$userid);
        }else
        {
            $list[]=$userid;
        }
        
    }
    else
    {
        $error++;
        dol_print_error($db);
        $list= array();
    }
      //$select.="\n";
      return $list;
 }

 
 /*
 * function to genegate the timesheet table header
 * 
  *  @param    array(string)           $headers            array of the header to show
 *  @param    array(int)              	$headersWidth    array defining the header width
 *  @param     int              	$yearWeek           timesheetweek
  *  @return     string                                                   html code
 */
 function timesheetHeader($headers,$headersWidth , $weekDays){
     global $langs;
     if(!is_array($weekDays )){
            setEventMessage($langs->trans("InternalError2"),'errors');
            return '';
     }

     $html='<tr class="liste_titre" >'."\n";
     
     foreach ($headers as $key => $value){
         $html.="\t<th ";
         if ($headersWidth[$key]){
                $html.='width="'.$headersWidth[$key].'"';
         }
         $html.=">".$langs->trans($value)."</th>\n";
     }
    
    for ($i=0;$i<7;$i++)
    {
         $html.="\t".'<th width="60px">'.$langs->trans(date('l',strtotime($weekDays[$i]))).'<br>'.date('d/m/y',strtotime($weekDays[$i]))."</th>\n";
    }

     
     $html.="</tr>\n";
     return $html;
     
 }
 
  /*
 * function to genegate the timesheet list
 * 
 *  @param    object             	$db                 db Object to do the querry
 *  @param    array(string)           $headers            array of the header to show
 *  @param    int              	$user                   user id to fetch the timesheets
 *  @param     int              	$yearWeek           timesheetweek
 *  @param    array(int)              	$whiteList    array defining the header width
 *  @param     int              	$timestamp         timestamp
 *  @return     string                                                   html code
 */
 function timesheetList($db,$headers,$userid,$yearWeek,$timestamp){
     $Lines='';
    // get the whitelist
    $whiteList=array();
    $staticWhiteList=new Timesheetwhitelist($db);
    $datestart=strtotime($yearWeek.' +0 day');
    $datestop=strtotime($yearWeek.' +6 day');
    $whiteList=$staticWhiteList->fetchUserList($userid, $datestart, $datestop);
     // Save the param in the SeSSION
     $tasksList=array();

    $sql ="SELECT DISTINCT element_id FROM ".MAIN_DB_PREFIX."element_contact "; 
    $sql.='JOIN '.MAIN_DB_PREFIX.'projet_task as tsk ON tsk.rowid=element_id ';
    $sql.='JOIN '.MAIN_DB_PREFIX.'projet as prj ON prj.rowid= tsk.fk_projet ';
    $sql.="WHERE (fk_c_type_contact='181' OR fk_c_type_contact='180') AND fk_socpeople='".$userid."' ";
    if(TIMESHEET_HIDE_DRAFT=='1'){
         $sql.=' AND prj.fk_statut="1" ';
    }
    $sql.=' AND (prj.datee>=FROM_UNIXTIME("'.$datestart.'") OR prj.datee IS NULL)';
    $sql.=' AND (prj.dateo<=FROM_UNIXTIME("'.$datestop.'") OR prj.dateo IS NULL)';
    $sql.=' AND (tsk.datee>=FROM_UNIXTIME("'.$datestart.'") OR tsk.datee IS NULL)';
    $sql.=' AND (tsk.dateo<=FROM_UNIXTIME("'.$datestop.'") OR tsk.dateo IS NULL)';
   if(is_array($whiteList)){
        $sql.=' AND tsk.rowid in (';
        foreach($whiteList as $value) {
            $sql.=$value.',';
        }              
         $sql.='0) ';
    }else  if(!empty($whiteList)){ 
        $sql.=' AND tsk.rowid=" '.$whiteList.'" ';
    }
    $sql.=" ORDER BY prj.fk_soc,tsk.fk_projet,tsk.fk_task_parent,tsk.rowid ";

    dol_syslog("timesheet::getTasksTimesheet sql=".$sql, LOG_DEBUG);
    $resql=$db->query($sql);
    if ($resql)
    {
            $num = $db->num_rows($resql);
            $i = 0;
            // Loop on each record found, so each couple (project id, task id)
            while ($i < $num)
            {
                    $error=0;
                    $obj = $db->fetch_object($resql);
                    $tasksList[$i] = NEW timesheet($db, $obj->element_id);
                    $i++;
            }
            $db->free($resql);
             $i = 0;
             foreach($tasksList as $row)
            {
                    dol_syslog("Timesheet::list.php task=".$row->id, LOG_DEBUG);
                    $row->getTaskInfo();
                    $row->getActuals($yearWeek,$userid); 
                    $_SESSION["timestamps"][$timestamp]['tasks'][$row->id]=array();
                    $_SESSION["timestamps"][$timestamp]['tasks'][$row->id]=$row->getTaskTab();
                    $Lines.=$row->getFormLine( $yearWeek,$i,$headers); 
                    //$Form.=$row->getFormLine( $yearWeek,$i);
                    $i++;
            }
            // form hiden param
            $Lines .= '<input type="hidden" name="timestamp" value="'.$timestamp."\"/>\n";
            $Lines .= '<input type="hidden" id="numberOfLines" name="numberOfLines" value="'.$i."\"/>\n";
            $Lines .= '<input type="hidden" name="yearWeek" value="'.$yearWeek.'" />'; 
    }else
    {
            dol_print_error($db);
    }
    return $Lines;
     
 }
 /*
 * function to post the all actual submitted
 * 
 *  @param    object             	$db                      db Object to do the querry
 *  @param    int              	$user                   user id to fetch the timesheets
 *  @param    array(int)              	$tabPost               array sent by POST with all info about the task
 *  @param     int              	$timestamp          timestamp
 *  @return     int                                                        number of tasktime creatd/changed
 */
 function postActuals($db,$user,$tabPost,$timestamp)
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
        return -2;
    }
    if(isset($storedTab['weekDays'])) {
        $storedWeekdays=$storedTab['weekDays'];
    }else {
        return -3;
    }
        
    $ret=0;
    $tmpRet=0;
    $_SESSION['timeSpendCreated']=0;
    $_SESSION['timeSpendDeleted']=0;
    $_SESSION['timeSpendModified']=0;
        /*
         * For each task store in matching the session timestamp
         */
$userid=  is_object($user)?$user->id:$user;
foreach($storedTasks as  $taskId => $taskItem)
    {
      //  $taskId=$taskItem["id"];
        $tasktimeIds=array();
        $tasktimeIds=$taskItem["taskTimeId"];
        $tasktime=new timesheet($db,$taskId);
        $tasktime->timespent_fk_user=$userid;
        $tasktime->fetch($taskId);
        dol_syslog("Timesheet::Submit.php::postActualsSecured  task=".$tasktime->id, LOG_DEBUG);
        //use the data stored
        //$tasktime->initTimeSheet($taskItem['weekWorkLoad'], $taskItem['taskTimeId']);
        //refetch actuals
        $tasktime->getActuals($yearWeek, $userid); 
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
        $tmpRet=$ret;
    } 
    unset($_SESSION["timestamps"][$timestamp]);
    return $ret;
}

/*
 * function to post on task_time
 * 
 *  @param    int              	$user                    user id to fetch the timesheets
 *  @param    object             	$tasktime             timesheet object, (task)
 *  @param    array(int)              	$tasktimeid          the id of the tasktime if any
 *  @param     int              	$timestamp          timesheetweek
 *  @return     int                                                       1 => succes , 0 => Failure
 */
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

if (!is_callable(setEventMessages)){
    // function from /htdocs/core/lib/function.lib.php in Dolibarr 3.8
    function setEventMessages($mesg, $mesgs, $style='mesgs')
    {
            if (! in_array((string) $style, array('mesgs','warnings','errors'))) dol_print_error('','Bad parameter for setEventMessage');
            if (empty($mesgs)) setEventMessage($mesg, $style);
            else
            {
                    if (! empty($mesg) && ! in_array($mesg, $mesgs)) setEventMessage($mesg, $style);	// Add message string if not already into array
                    setEventMessage($mesgs, $style);

            }
    }
}