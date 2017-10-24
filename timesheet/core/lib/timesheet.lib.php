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
require_once 'class/TimesheetFavourite.class.php';
require_once 'class/TimesheetUserTasks.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';


 /*
 * function to genegate list of the subordinate ID
 * 
  *  @param    object           	$db             database objet
 *  @param    array(int)/int        $id    		    array of manager id 
 *  @param     int              	$depth          depth of the recursivity
 *  @param    array(int)/int 		$ecludeduserid  exection that shouldn't be part of the result ( to avoid recursive loop)
 *  @param     string              	$role           team will look for organigram subordinate, project for project subordinate
 *  @param     int              	$entity         entity where to look for
  *  @return     array(userId)                                                  html code
 */
function getSubordinates($db,$userid, $depth=5,$ecludeduserid=array(),$role='team',$entity='1'){
    if($userid=="")
    {
        return array();
    }
    $sql['project'][0] ='SELECT DISTINCT fk_socpeople as userid FROM '.MAIN_DB_PREFIX.'element_contact';
    $sql['project'][0] .= ' WHERE element_id in (SELECT element_id';
    $sql['project'][0] .= ' FROM '.MAIN_DB_PREFIX.'element_contact';
    $sql['project'][0] .= ' WHERE (fk_c_type_contact="160" OR fk_c_type_contact="180")';
    $sql['project'][0] .= ' AND fk_socpeople in (';
    $sql['project'][2] = ')) AND fk_socpeople not in (';
    $sql['project'][4] = ')';
    $sql['team'][0]='SELECT usr.rowid as userid FROM '.MAIN_DB_PREFIX.'user AS usr WHERE';
    $sql['team'][0].=' usr.fk_user in (';
    $sql['team'][2]=') AND usr.rowid not in (';
    $sql['team'][4] = ')';
    $idlist='';
    if(is_array($userid)){
        $ecludeduserid=array_merge($userid,$ecludeduserid);
        $idlist=implode(",", $userid);
    }else{
        $ecludeduserid[]=$userid;
        $idlist=$userid;
    }
    $sql[$role][1]=$idlist;
    $idlist='';
    if(is_array($ecludeduserid)){
        $idlist=implode(",", $ecludeduserid);
    }else if (!empty($ecludeduserid)){
        $idlist=$ecludeduserid;
    } 
   $sql[$role][3]=$idlist;
    ksort($sql[$role], SORT_NUMERIC);
    $sqlused=implode($sql[$role]);
    dol_syslog('form::get_subordinate role='.$role, LOG_DEBUG);
    $list=array();
    $resql=$db->query($sqlused);
    
    if ($resql)
    {
        $i=0;
        $num = $db->num_rows($resql);
        while ( $i<$num)
        {
            $obj = $db->fetch_object($resql);
            
            if ($obj)
            {
                $list[]=$obj->userid;        
            }
            $i++;
        }
        if(count($list)>0 && $depth>1){
            //this will get the same result plus the subordinate of the subordinate
            $result=getSubordinates($db,$list,$depth-1,$ecludeduserid, $role, $entity);
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
            //$list[]=$userid;
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
 * function to genegate list of the subordinate ID
 * 
  *  @param    object           	$db             database objet
 *  @param    array(int)/int        $id    		    array of manager id 
 *  @param     int              	$depth          depth of the recursivity
 *  @param    array(int)/int 		$ecludeduserid  exection that shouldn't be part of the result ( to avoid recursive loop)
 *  @param     string              	$role           team will look for organigram subordinate, project for project subordinate
 *  @param     int              	$entity         entity where to look for
  *  @return     string                                                   html code
 */
function getTasks($db,$userid,$role='project'){
    $sql='SELECT tk.fk_projet as project ,tk.rowid as task';
    $sql.= ' FROM '.MAIN_DB_PREFIX.'projet_task as tk';
    $sql.=' JOIN '.MAIN_DB_PREFIX.'element_contact ON  tk.fk_projet= element_id ';
    $sql.=' WHERE fk_c_type_contact = "160" ';
    $sql.=' AND fk_socpeople="'.$userid.'"';
    $sql.=' UNION ';
    $sql.=' SELECT tk.fk_projet as project ,tk.rowid as task';
    $sql.= ' FROM '.MAIN_DB_PREFIX.'projet_task as tk';
    $sql.=' JOIN '.MAIN_DB_PREFIX.'element_contact on (tk.rowid= element_id )';
    $sql.=' WHERE fk_c_type_contact = "180" ';
    $sql.=' AND fk_socpeople="'.$userid.'"';


   dol_syslog('timesheet::report::projectList ', LOG_DEBUG);
   //launch the sql querry

   $resql=$db->query($sql);
   $numTask=0;
   $taskList=array();
   if ($resql)
   {
           $numTask = $db->num_rows($resql);
           $i = 0;
           // Loop on each record found, so each couple (project id, task id)
           while ($i < $numTask)
           {
                   $error=0;
                   $obj = $db->fetch_object($resql);
                   $taskList[$obj->task]=$obj->project;
                   $i++;
           }
           $db->free($resql);
   }else
   {
           dol_print_error($db);
   }
   return $taskList;
}
 /*
 * function to get the name from a list of ID
 * 
  *  @param    object           	$db             database objet
 *  @param    array(int)/int        $userids    	array of manager id 
  *  @return  array (int => String)  				array( ID => userName)
 */
function getUsersName($userids){
    global $db;
	if($userids=="")
    {
        return array();
    }

    $sql="SELECT usr.rowid, CONCAT(usr.firstname,' ',usr.lastname) as userName,usr.lastname FROM ".MAIN_DB_PREFIX.'user AS usr WHERE';

	$sql.=' usr.rowid in (';
	$nbIds=(is_array($userids))?count($userids)-1:0;
	for($i=0; $i<$nbIds-1 ; $i++)
	{
		$sql.='"'.$userids[$i].'",';
	}
	$sql.=((is_array($userids))?('"'.$userids[$nbIds-1].'"'):('"'.$userids.'"')).')';
        $sql.='ORDER BY usr.lastname ASC';

    dol_syslog('form::get_userName '.$sql, LOG_DEBUG);
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
                $list[$obj->rowid]=$obj->userName;        
            }
            $i++;
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

/*
 * function retrive the dolibarr eventMessages ans send then in a XML format
 * 
 *  @return     string                                         XML
 */
function getEventMessagesXML(){
    $xml='';
       // Show mesgs
   if (isset($_SESSION['dol_events']['mesgs'])) {
     $xml.=getEventMessageXML( $_SESSION['dol_events']['mesgs']);
     unset($_SESSION['dol_events']['mesgs']);
   }

   // Show errors
   if (isset($_SESSION['dol_events']['errors'])) {
     $xml.=getEventMessageXML(  $_SESSION['dol_events']['errors'], 'error');
     unset($_SESSION['dol_events']['errors']);
   }

   // Show warnings
   if (isset($_SESSION['dol_events']['warnings'])) {
     $xml.=getEventMessageXML(  $_SESSION['dol_events']['warnings'], 'warning');
     unset($_SESSION['dol_events']['warnings']);
   }
   return $xml;
}

/*
 * function convert the dolibarr eventMessage in a XML format
 * 
 *  @param    string              	$message           message to show
 *  @param    string              	$style            style of the message error | ok | warning
 *  @return     string                                         XML
 */
function getEventMessageXML($messages,$style='ok'){
    $msg='';
    
    if(is_array($messages)){
        $count=count($messages);
        foreach ($messages as $message){
            $msg.=$message;
            if($count>1)$msg.="<br/>";
            $count--;
        }
    }else
        $msg=$messages;
    $ret='';
    if($msg!=""){  
        if($style!='error' && $style!='warning')$style='ok';
        $ret= "<eventMessage style=\"{$style}\"> {$msg}</eventMessage>";
    }
    return $ret;
}



/*
 * function to make the StartDate
 * 
  *  @param    int              $day                    day of the date
 *  @param    int              	$month                   month of the date
 *  @param    int              	$year                    year of the date
 *  @param    string            $date           date on a string format
 *  @param    int               $prevNext       -1 for previous period, +1 for next period
 *  @return     string                                   
 */
function getStartDate($datetime,$prevNext=0){
   global $conf;
    // use the day, month, year value
     $startDate=null;
        // split week of the current week
  /* $prefix='this';
   if($prevNext==1){
        $prefix='next';
   }else if ($prevNext==-1){
       $prefix='previous';
   }
 */
    /**************************
     * calculate the start date form php date
     ***************************/
     switch($conf->global->TIMESHEET_TIME_SPAN){

        case 'month': //by Month   (FIXME/will be reactiated later when a layout solution would be found)   
        //     $startDate=  strtotime('first day of '.$prefix.' month midnight',$datetime  ); 
        //     break;
                if($prevNext==1){
                    $startDate=  strtotime('first day of next month midnight',$datetime  ); 
                }else if($prevNext==0){
                    $startDate=  strtotime('first day of this month midnight',$datetime  ); 
                }else if($prevNext==-1){
                    $startDate=  strtotime('first day of previous month midnight',$datetime  ); 
                }
            break;            
        case 'week': //by user   
                    //     $startDate=  strtotime('first day of '.$prefix.' month midnight',$datetime  ); 
        //     break;
                if($prevNext==1){
                    $startDate=  strtotime('monday next week midnight',$datetime  ); 
                }else if($prevNext==0){
                    $startDate=  strtotime('monday this week midnight',$datetime  ); 
                }else if($prevNext==-1){
                    $startDate=  strtotime('monday previous week midnight',$datetime  ); 
                }
            break;  
        case 'splitedWeek': //by week
        default:

                if($prevNext==1){
                    $startDateMonth=  strtotime('first day of next month midnight',$datetime  ); 
                    $startDateWeek=  strtotime('monday next week midnight',$datetime  ); 
                    $startDate=MIN( $startDateMonth, $startDateWeek);
                }else if($prevNext==0){
                    $startDateMonth=  strtotime('first day of this month midnight',$datetime  ); 
                    $startDateWeek=  strtotime('monday this week midnight',$datetime  ); 
                    $startDate=MAX( $startDateMonth, $startDateWeek);
                }else if($prevNext==-1){
                    $startDateMonth=  strtotime('first day of this month midnight',$datetime  ); 
                    $startDateWeek=  strtotime('monday this week midnight',$datetime  ); 
                    $startDatePrevWeek=  strtotime('monday previous week midnight',$datetime  ); 
                    $startDate=( $startDateMonth>$startDateWeek)?$startDateWeek:$startDatePrevWeek;
                }
            break;
    }
    return $startDate;
}
/*
 * function to make the endDate
 * 
 *  @param    string            $datetime           date on a php format
 *  @return     string                                   
 */
function getEndDate($datetime){
    global $conf;
// use the day, month, year value
    $endDate=null;

    /**************************
     * calculate the end date form php date
     ***************************/
    switch($conf->global->TIMESHEET_TIME_SPAN){

        case 'month': 
            $endDate=strtotime('first day of next month midnight',$datetime); 
            break;
        case 'week':  
            $endDate=strtotime('monday next week midnight',$datetime); 
            break;
        case 'splitedWeek': 
        default:
            $day=date('d',$datetime);
            $dayOfWeek=date('N',$datetime);
            $dayInMonth=date('t',$datetime);
            if ($dayInMonth<$day+(7-$dayOfWeek) ){
                $endDate=strtotime('first day of next month midnight',$datetime); 
            }else{
                $endDate=strtotime('monday next week midnight',$datetime); 
            }
        
            break;
    }

    return $endDate;
}


/*
 * function to make the Date in PHP format
 * 
  *  @param    int              $day                    day of the date
 *  @param    int              	$month                   month of the date
 *  @param    int              	$year                    year of the date
 *  @param    string            $date           date on a string format
 *  @return     string                                   
 */
function parseDate($day=0,$month=0,$year=0,$date=0){  
    $datetime=time(); 
    $splitWeek=0;
    if ($day!=0 && $month!=0 && $year!= 0)
    {
        $datetime=dol_mktime(0,0,0,$month,$day,$year);
    // the date is already in linux format
    }else if(is_numeric($date) && $date!=0){  // if date is a datetime
        $datetime=$date;
    }else if(is_string($date)&& $date!=""){  // if date is a string
        //foolproof: incase the yearweek in passed in date
        if( strlen($date)>3 && substr($date,-3,2)=="_H"){
              if(substr($date,-1,1)==1){
                  $date=substr($date,0,7);
                  $splitWeek=1;
              }else{
                  $date='last day of  week '.substr($date,0,7);
                  $splitWeek=2;
              }
        }
        $datetime=strtotime($date);
    }
    return $datetime;
}





/*
 * function to show the AP tab
 * 

 *  @param    string        $role    	active role
  *  @return  void  				array( ID => userName)
 */
function showTimesheetApTabs($role){
global $langs;
global $conf;
$roles=array(0=> 'team', 1=> 'project',2=>'customer',3=>'supplier',4=>'other');
$rolesUrl=array(0=> 'timesheetAp.php?role=team', 1=> 'otherAp.php?role=project',2=>'otherAp.php?role=customer',3=>'otherAp.php?role=supplier',4=>'otherAp.php?role=other');
$apflows=array_slice(str_split($conf->global->TIMESHEET_APPROVAL_FLOWS),1); //remove the leading _
    foreach($roles as $key => $cur_role){
        if($apflows[$key]==1){
            echo '  <div class="inline-block tabsElem"><a  href="'.$rolesUrl[$key].'&leftmenu=timesheet" class="';
            echo    ($role==$cur_role)?'tabactive':'tabunactive';
            echo   ' tab inline-block" data-role="button">'.$langs->trans($cur_role)."</a></div>\n";
        }
    }

}