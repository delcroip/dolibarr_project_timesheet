<?php
/* Copyright (C) 2007-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2016 delcroip <delcroip@gmail.com>
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

/**
 *   	\file       dev/skeletons/skeleton_page.php
 *		\ingroup    mymodule othermodule1 othermodule2
 *		\brief      This file is an example of a php page
 *					Put here some comments
 */



// hide left menu
//$_POST['dol_hide_leftmenu']=1;
// Change this following line to use the correct relative path (../, ../../, etc)
include 'core/lib/includeMain.lib.php';
require_once 'core/lib/timesheet.lib.php';
require_once 'core/lib/generic.lib.php';
require_once 'class/timesheetUser.class.php';
if(!$user->rights->timesheet->approval){
        $accessforbidden = accessforbidden("you need to have the approver rights");           
}
//$userId            = GETPOST('userid');

$userId=  is_object($user)?$user->id:$user;
$action             = GETPOST('action');
//should return the XMLDoc
$ajax               = GETPOST('ajax');
$xml               = GETPOST('xml');
if(!is_numeric($offset))$offset=0;
$print=(GETPOST('optioncss')=='print')?true:false;
$current=GETPOST('target','int');
//$toDate                 = GETPOST('toDate');

$timestamp=GETPOST('timestamp');


//$userid=  is_object($user)?$user->id:$user;




// Load traductions files requiredby by page
//$langs->load("companies");
$langs->load("main");
$langs->load("projects");
$langs->load('timesheet@timesheet');


$timesheetUser= new timesheetUser($db);



/*******************************************************************
* ACTIONS
*
* Put here all code to do according to value of "action" parameter
********************************************************************/
if($action== 'submit'){
         if (isset($_SESSION['timesheetAp'][$timestamp]))
        {
            // $_SESSION['timesheetAp'][$timestamp]['tsUser']
            $tsApproved=0;
            $tsRejected=0;
            $ret=0;
            $errors=0;
            $count=0;
            //$timesheetUser->db=$db;
           // var_dump(unserialize($timesheetUser->taskTimesheet[0])->tasklist);
            if (!empty($_POST['approval']))
            {
                $approvals=$_POST['approval'];
                foreach($_SESSION['timesheetAp'][$timestamp]['tsUser'] as $key => $tsUser){
                    $count++;
                    switch($approvals[$key]){
                        case 'Approved':
                           $ret=$timesheetUser->setAppoved($user,$key); 
                            if(ret<0)$errors++;
                            else $tsApproved++;
                            break;
                        case 'Rejected':
                            $ret=$timesheetUser->setRejected($user,$key);
                            if(ret<0)$errors++;
                            else $tsRejected++;
                            break;
                        case 'Submitted':
                        default:
                            break;
                                
                    }
                    
                }
                if(($tsRejected+$tsApproved)>0){
                    $current--;
                }
               // $offset-=($tsApproved+$tsRejected);       

				//$ret =postActuals($db,$user,$_POST['task'],$timestamp);
                    if($ret>0)
                    {
                        if($tsApproved)setEventMessage($langs->transnoentitiesnoconv("NumberOfTimesheetApproved").$tsApproved);
                        if($tsRejected)setEventMessage($langs->transnoentitiesnoconv("NumberOfTimesheetRejected").$tsRejected);
                        if($errors)setEventMessage($langs->transnoentitiesnoconv("NumberOfErrors").$errors);
                    }
                    else
                    {
                        if($errors==0){
                            setEventMessage($langs->transnoentitiesnoconv("NothingChanged"),'warning');
                        }else {
                            setEventMessage( $langs->transnoentitiesnoconv("InternalError").':'.$ret,'errors');
                        }
                    }        
            }else
                    setEventMessage( $langs->transnoentitiesnoconv("NothingChanged"),'warning');// shoudn't happend
        }else
                setEventMessage( $langs->transnoentitiesnoconv("InternalError"),'errors');
	

}
if(!empty($timestamp)){
       unset($_SESSION['timesheetAp'][$timestamp]);
}
$timestamp=time();
$subId=get_subordinate($db,$userId, 2,array($userId),'team');
$selectList=getSelectAps($subId);
$level=intval(TIMESHEET_MAX_APPROVAL);
$offset=0;
if( is_array($selectList)&& count($selectList)){
        if($current>=count($selectList))$current=0;
        $offset=0;
        for($i=0;$i<$current;$i++){
            $offset+= $selectList[$i]['count'];
        }
        $level=$selectList[$i]['count'];
}

$objectArray=getTStobeApproved($level,$offset,'team',$subId);

if(is_array($objectArray)){
$firstTimesheetUser=reset($objectArray);
$curUser=$firstTimesheetUser->userId;
$nextUser=$firstTimesheetUser->userId;
}
$i=0;

//



/***************************************************
* VIEW
*
* Put here all code to build page
****************************************************/

if($xml){
    //renew timestqmp
    ob_clean();
   header("Content-type: text/xml; charset=utf-8");
  //  echo $timesheetUser->GetTimeSheetXML($userId,5); //fixme
    exit;
}

$head=($print)?'<style type="text/css" >@page { size: A4 landscape;marks:none;margin: 1cm ;}</style>':'';
$morejs=array("/timesheet/core/js/jsparameters.php","/timesheet/core/js/timesheet.js");
llxHeader($head,$langs->trans('Timesheet'),'','','','',$morejs);
//calculate the week days

$Form ='';
//tmstp=time();
if(is_object($firstTimesheetUser)){
    if(!$print) echo getHTMLNavigation($optioncss, $selectList,$current);
    $Form .=$firstTimesheetUser->getHTMLFormHeader($ajax);
    foreach($objectArray as $key=> $timesheetUser){
       // if($firstTimesheetUser->userId==$timesheetUser->userId){
            
            if($i<$level){
                $timesheetUser->fetchTaskTimesheet();
        //$ret+=$this->getTaskTimeIds(); 
        //FIXME module holiday should be activated ?
                $timesheetUser->fetchUserHoliday(); 
                $Form .=$timesheetUser->userName." - ".$langs->trans('Week').date(' W (Y)',$timesheetUser->year_week_date);
                $Form .=$timesheetUser->getHTMLHeader(false);
                $Form .=$timesheetUser->getHTMLHolidayLines(false);
                //$Form .=$timesheetUser->getHTMLTotal('totalT');
                $Form .=$timesheetUser->getHTMLtaskLines(false);
                $Form .=$timesheetUser->getHTMLTotal();/*FIXME*/
                $Form .=$timesheetUser->getHTMLNote(false);
                $_SESSION['timesheetAp'][$timestamp]['tsUser'][$timesheetUser->id]=array('status'=>$timesheetUser->status,'Target'=>$timesheetUser->target);
                $Form .= '</table>';
                
                $Form .= '</br>'."\n";
                if(!$print){
                $Form .= '<label class="butAction"><input type="radio"  name="approval['.$timesheetUser->id.']" value="Approved" ><span>'.$langs->trans('APPROVED').'</span></label>'."\n";/*FIXME*/
                $Form .= '<label class="butAction"><input type="radio"  name="approval['.$timesheetUser->id.']" value="Rejected" ><span>'.$langs->trans('REJECTED').'</span></label>'."\n";/*FIXME*/
                $Form .= '<label class="butAction"><input type="radio"  name="approval['.$timesheetUser->id.']" value="Submitted" checked ><span>'.$langs->trans('SUBMITTED').'</span></label>'."\n";/*FIXME*/
                $Form .= '</br></br></br>'."\n";
                }
                $i++;//use for the offset
            }
       // }else{
       //     $nextUser=$timesheetUser->userId;
       //     break;
       // }
    }
   // $offset+=$i;
    if(!$print){
        
        $Form .=$firstTimesheetUser->getHTMLFooterAp($current,$timestamp);
    }else{
        $Form .='<table width="100%"><tr><td align="center">'.$langs->trans('customerSignature').'</td><td align="center">'.$langs->trans('managerSignature').'</td><td align="center">'.$langs->trans('employeeSignature').'</td></tr></table>';
    }
}else{
    $Form .='<h1>'.$langs->trans('NothingToValidate').'</h1>';
    $staticTs=new timesheetUser($db);
    $Form .=$staticTs->getHTMLFooterAp($current,$timestamp);
}


//Javascript
$timetype=TIMESHEET_TIME_TYPE;
//$Form .= ' <script type="text/javascript" src="timesheet.js"></script>'."\n";
$Form .= '<script type="text/javascript">'."\n\t";
$Form .='updateAll();';
$Form .= "\n\t".'</script>'."\n";
// $Form .='</div>';//TimesheetPage
print $Form;

// End of page
llxFooter();
$db->close();
    /* Funciton to fect timesheet to be approuved.
    *  @param    int              	$level            number of ts to fetch
    *  @param    int              	$offset           number of ts to skip
    *  @param    int              	$role             team, project, customer ...
    *  @param    array(int)             $role             if team, array fo subordinate_id, array of task_id for other
    *  @return   array(timesheetUser)                     result
    */    
function getTStobeApproved($level,$offset,$role,$subId){ // FIXME LEVEL ISSUE
global $db;
if(!is_array($subId) || !count($subId))return array();
$byWeek=(TIMESHEET_APPROVAL_BY_WEEK==1)?true:false;
        //if($role='team')
       

        $sql ="SELECT * ";
        $sql.=" FROM ".MAIN_DB_PREFIX."timesheet_user as tu"; 
        $sql.=' WHERE tu.status="SUBMITTED"';
        if($role='team'){$sql.=' AND fk_userid in ('.implode(',',$subId).')';
        }else{$sql.=' AND fk_task in ('.implode(',',$subId).')';}
        $sql.=' AND target="'.$role.'"';
        if($byWeek){
            $sql.=' ORDER BY year_week_date DESC, fk_userid DESC';
        }else {
            $sql.=' ORDER BY fk_userid DESC,year_week_date DESC';
        }
        $sql.=' LIMIT '.$level;
        $sql.=' OFFSET '.$offset;
        dol_syslog("timesheet::getTStobeApproved sql=".$sql, LOG_DEBUG);
     $tsList=array();
   
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
                   
                    $tmpTs = NEW timesheetUser($db,$obj->fk_userid);
                    $tmpTs->id    = $obj->rowid;
                    //$tmpTs->userId = $obj->fk_userid;
                    $tmpTs->year_week_date = $tmpTs->db->jdate($obj->year_week_date);
                    $tmpTs->status = $obj->status;
                    $tmpTs->target = $obj->target;
                    $tmpTs->project_tasktime_list = $obj->fk_project_tasktime_list;
                    $tmpTs->user_approval = $obj->fk_user_approval;
                    $tmpTs->date_creation = $tmpTs->db->jdate($obj->date_creation);
                    $tmpTs->date_modification = $tmpTs->db->jdate($obj->date_modification);
                    $tmpTs->user_creation = $obj->fk_user_creation;
                    $tmpTs->user_modification = $obj->fk_user_modification;            
                    $tmpTs->yearWeek=  date('Y\WW',$tmpTs->year_week_date);
                    $tmpTs->whitelistmode=2; // no impact
                    //if($i>0){
                    //    if(($byWeek && ($tsList[$i-1]->yearWeek==$tmpTs->yearWeek)) ||
                    //           (!$byWeek && $tsList[$i-1]->userId == $tmpTs->userId) ){
                    //            $tsList[$i]=$tmpTs;
                    //    }else{
                    //       break;
                    //    }
                    //}else{
                        $tsList[$i]=$tmpTs;
                    //}
                    $i++;
                    
            }
            $db->free($resql);

                return $tsList;

    }else
    {
            dol_print_error($db);
            return -1;
    }
}


/*
 * function to print the timesheet navigation header
 * 
 *  @param    string              	$yearWeek            year week like 2015W09
 *  @param     int              	$whitelistmode        whitelist mode, shows favoite o not 0-whiteliste,1-blackliste,2-non impact
 *  @param     object             	$form        		form object
 *  @return     string                                         HTML
 */
function getHTMLNavigation($optioncss, $selectList,$current=0){
	global $langs,$db;
        
        $htmlSelect='<select name="target">';
        foreach($selectList as $key => $element){
            $htmlSelect.=' <option value="'.$key.'" '.(($current==$key)?'selected':'').'>'.$element['label'].'</option>';
        }
            
        $htmlSelect.='</select>';    
        
        $form= new Form($db);
        $Nav=  '<table class="noborder" width="50%">'."\n\t".'<tr>'."\n\t\t".'<th>'."\n\t\t\t";
        if($current!=0){
            $Nav.= '<a href="?action=goTo&target='.($current-1).'"';   
            if ($optioncss != '')$Nav.=   '&amp;optioncss='.$optioncss;
            $Nav.=  '">  &lt;&lt; '.$langs->trans("Previous").' </a>'."\n\t\t";
        }
        $Nav.="</th>\n\t\t<th>\n\t\t\t";
	$Nav.=  '<form name="goTo" action="?action=goTo" method="POST" >'."\n\t\t\t";
        $Nav.=   $langs->trans("GoTo").': '.$htmlSelect."\n\t\t\t";;
	$Nav.=  '<input type="submit" value="Go" /></form>'."\n\t\t</th>\n\t\t<th>\n\t\t\t";
	if($current<count($selectList)){
            $Nav.=  '<a href="?action=goTo&target='.($current+1);
            if ($optioncss != '') $Nav.=   '&amp;optioncss='.$optioncss;
            $Nav.=  '">'.$langs->trans("Next").' &gt;&gt; </a>';
        }
        $Nav.="\n\t\t</th>\n\t</tr>\n </table>\n";
        return $Nav;
}

 /*
 * function to get the name from a list of ID
 * 
  *  @param    object           	$db             database objet
 *  @param    array(int)/int        $userids    	array of manager id 
  *  @return  array (int => String)  				array( ID => userName)
 */
function getSelectAps($subId){
    if(!is_array($subId) || !count($subId))return array();
    global $db,$langs;
    if(TIMESHEET_APPROVAL_BY_WEEK==1){
        $sql='SELECT COUNT(ts.year_week_date) as nb,ts.year_week_date as id,';
        $sql.=" DATE_FORMAT(ts.year_week_date,'".$langs->trans('Week')." %u (%Y)') as label";
        $sql.=' FROM '.MAIN_DB_PREFIX.'timesheet_user as ts'; 
        $sql.=' JOIN '.MAIN_DB_PREFIX.'user as usr on ts.fk_userid= usr.rowid ';

    }else{
        $sql='SELECT COUNT(ts.fk_userid) as nb,ts.fk_userid as id,';
        $sql.=" CONCAT(usr.firstname,' ',usr.lastname) as label";
        $sql.=' FROM '.MAIN_DB_PREFIX.'timesheet_user as ts'; 
        $sql.=' JOIN '.MAIN_DB_PREFIX.'user as usr on ts.fk_userid= usr.rowid '; 
    }
    $sql.=' WHERE ts.status="SUBMITTED"'; 
    $sql.=' AND ts.fk_userid in ('.implode(',',$subId).')';
    $sql.=' group by id ORDER BY id DESC'; 
    dol_syslog("timesheetAp::getSelectAps sql=".$sql, LOG_DEBUG);
    $list=array();
    $resql=$db->query($sql);
    
    if ($resql)
    {
        $i=0;
        $j=0;
        $num = $db->num_rows($resql);
        while ( $i<$num)
        {
            $obj = $db->fetch_object($resql);
            
            if ($obj)
            {
                $j=1;
                $nb=$obj->nb;
                while($nb>TIMESHEET_MAX_APPROVAL){
                    $list[]=array("id"=>$obj->id,"label"=>$obj->label.' ('.$j."/".ceil($obj->nb/TIMESHEET_MAX_APPROVAL).')',"count"=>TIMESHEET_MAX_APPROVAL);
                    $nb-=TIMESHEET_MAX_APPROVAL;
                    $j++;
                }
                $list[]=array("id"=>$obj->id,"label"=>$obj->label.' '.(($obj->nb>TIMESHEET_MAX_APPROVAL)?'('.$j.'/'.ceil($obj->nb/TIMESHEET_MAX_APPROVAL).')':''),"count"=>$nb);
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
?>
