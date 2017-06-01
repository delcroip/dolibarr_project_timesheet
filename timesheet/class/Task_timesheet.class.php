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
require_once 'class/Task_time_approval.class.php';
require_once 'class/timesheetwhitelist.class.php';
//require_once 'core/lib/timesheet.lib.php';
//dol_include_once('/timesheet/class/projectTimesheet.class.php');
//require_once './projectTimesheet.class.php';
//define('TIMESHEET_BC_FREEZED','909090');
//define('TIMESHEET_BC_VALUE','f0fff0');
class Task_timesheet extends CommonObject
{
    //common
    var $db;							//!< To store db handler
	var $error;							//!< To return error code (or message)
	var $errors=array();				//!< To return several error codes (or messages)
	var $element='timesheetuser';			//!< Id that identify managed objects
	var $table_element='project_task_timesheet';		//!< Name of table without prefix where object is stored
// from db
    var $id;
	var $userId;
	var $date_start='';
    var $date_end;
	var $status;
//	var $sender;
//	var $recipient;
//  var $estimates;
    var $note;
//  var $tracking;
//  var $tracking_ids;
//  var $fk_task;
    //basic DB logging
	var $date_creation='';
	var $date_modification='';
	var $user_creation;
	var $user_modification;  

//working variable

    var $duration;
    var $ref; 
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
 /******************************************************************************
 * 
 * DB methods
 * 
 ******************************************************************************/
    /**
     *  cREATE object into database
     *
     *  @param	User	$user        User that modifies
     *  @param  int		$notrigger	 0=launch triggers after, 1=disable triggers
     *  @return int     		   	 <0 if KO, >0 if OK
     */
    function create($user, $notrigger=0)
    {
    	global $conf, $langs;
		$error=0;

		// Clean parameters
        
		if (isset($this->userId)) $this->userId=trim($this->userId);
		if (isset($this->date_start)) $this->date_start=trim($this->date_start);
		if (isset($this->date_end)) $this->date_end=trim($this->date_end);
		if (isset($this->status)) $this->status=trim($this->status);
		if (isset($this->date_creation)) $this->date_creation=trim($this->date_creation);
		if (isset($this->date_modification)) $this->date_modification=trim($this->date_modification);
		if (isset($this->user_modification)) $this->user_modification=trim($this->user_modification);
		if (isset($this->note)) $this->note=trim($this->note);

        

		// Check parameters
		// Put here code to add control on parameters values

        // Insert request
		$sql = "INSERT INTO ".MAIN_DB_PREFIX.$this->table_element."(";
		
		$sql.= 'fk_userid,';
		$sql.= 'date_start,';
                $sql.= 'date_end,';
		$sql.= 'status,';
		$sql.= 'date_creation,';
                $sql.= 'fk_user_modification,';
                $sql.= 'note';

		
        $sql.= ") VALUES (";
        
		$sql.=' '.(! isset($this->userId)?'NULL':'"'.$this->userId.'"').',';
		$sql.=' '.(! isset($this->date_start) || dol_strlen($this->date_start)==0?'NULL':'"'.$this->db->idate($this->date_start).'"').',';
		$sql.=' '.(! isset($this->date_end) || dol_strlen($this->date_end)==0?'NULL':'"'.$this->db->idate($this->date_end).'"').',';
		$sql.=' '.(! isset($this->status)?'"DRAFT"':'"'.$this->status.'"').',';
		$sql.=' NOW() ,';
		$sql.=' "'.$user->id.'",'; //fixme 3.5
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
		$sql.=' t.date_start,';
		$sql.=' t.date_end,';
		$sql.=' t.status,';
		$sql.=' t.date_creation,';
		$sql.=' t.date_modification,';
		$sql.=' t.fk_user_modification,';
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
                $this->date_start = $this->db->jdate($obj->date_start);
                $this->date_end = $this->db->jdate($obj->date_end);
                $this->status = $obj->status;
                $this->date_creation = $this->db->jdate($obj->date_creation);
                $this->date_modification = $this->db->jdate($obj->date_modification);
                $this->user_modification = $obj->fk_user_modification;
                $this->note  = $obj->note;

                
            }
            $this->db->free($resql);
            $this->yearWeek= getYearWeek(0,0,0,$this->date_start); //fixme
            $this->ref=$this->yearWeek.'_'.$this->userId;
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
		$sql.=' t.date_start,';
		$sql.=' t.date_end,';
		$sql.=' t.status,';
		$sql.=' t.date_creation,';
		$sql.=' t.date_modification,';
		$sql.=' t.fk_user_modification,';
		//$sql.=' t.fk_task,';
		$sql.=' t.note';

		
        $sql.= " FROM ".MAIN_DB_PREFIX.$this->table_element." as t";

        $sql.= " WHERE t.date_start = '".$this->db->idate($this->date_start)."'";
		$sql.= " AND t.fk_userid = '".$this->userId."'";
       # $sql .= "AND WEEKOFYEAR(ptt.date_start)='".date('W',strtotime($yearWeek))."';";
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
                $this->date_start = $this->db->jdate($obj->date_start);
                $this->date_end = $this->db->jdate($obj->date_end);
                $this->status = $obj->status;
                 $this->date_creation = $this->db->jdate($obj->date_creation);
                $this->date_modification = $this->db->jdate($obj->date_modification);
                $this->user_modification = $obj->fk_user_modification;
                $this->note  = $obj->note;
                $this->date_end= getEndWeek($this->date_start);
                
            }else{
                unset($this->status) ;
                unset($this->date_modification );
                unset($this->user_modification );
                unset($this->note );
                unset($this->date_creation  );
            
                //$this->date_end= getEndWeek($this->date_start);
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
		if (isset($this->date_start)) $this->date_start=trim($this->date_start);
		if (isset($this->date_end)) $this->date_end=trim($this->date_end);
		if (isset($this->status)) $this->status=trim($this->status);
		if (isset($this->date_creation)) $this->date_creation=trim($this->date_creation);
		if (isset($this->date_modification)) $this->date_modification=trim($this->date_modification);
		if (isset($this->user_modification)) $this->user_modification=trim($this->user_modification);
		if (isset($this->note)) $this->note=trim($this->note);

        

		// Check parameters
		// Put here code to add a control on parameters values

        // Update request
        $sql = "UPDATE ".MAIN_DB_PREFIX.$this->table_element." SET";
        
		$sql.=' fk_userid='.(empty($this->userId) ? 'null':'"'.$this->userId.'"').',';
		$sql.=' date_start='.(dol_strlen($this->date_start)!=0 ? '"'.$this->db->idate($this->date_start).'"':'null').',';
		$sql.=' date_end='.(dol_strlen($this->date_end)!=0 ? '"'.$this->db->idate($this->date_end).'"':'null').',';
		$sql.=' status='.(empty($this->status)? 'null':'"'.$this->status.'"').',';
		$sql.=' date_modification=NOW() ,';
		$sql.=' fk_user_modification="'.$user->id.'",';
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
    
/******************************************************************************
 * 
 * Other methods
 * 
 ******************************************************************************/    
    
    /* Funciton to fect the holiday of a single user for a single week.
    *  @param    string              	$yearWeek            year week like 2015W09
    *  @return     string                                       result
    */    
    function fetchAll($yearWeek,$whitelistmode=false){
        $this->whitelistmode=is_numeric($whitelistmode)?$whitelistmode:TIMESHEET_WHITELIST_MODE;
        $this->yearWeek=$yearWeek;
        $this->ref=$this->yearWeek.'_'.$this->userId;
        $this->date_start=  parseYearWeek($this->yearWeek);
        $this->date_end= getEndWeek($this->date_start);
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
    $this->userId= $_SESSION['task_timesheet'][$timestamp][$id]['userId'];
    $this->yearWeek= $_SESSION['task_timesheet'][$timestamp][$id]['yearWeek'];
    $this->ref=$this->yearWeek.'_'.$this->userId;
    $this->holidays=  unserialize( $_SESSION['task_timesheet'][$timestamp][$id]['holiday']);
    $this->taskTimesheet=  unserialize( $_SESSION['task_timesheet'][$timestamp][$id]['taskTimesheet']);;
}

    /*
 * function to load the parma from the session
 */
function saveInSession(){
    $_SESSION['task_timesheet'][$this->timestamp][$this->id]['userId']=$this->userId;
    //$_SESSION['task_timesheet'][$this->timestamp]['id']=$this->id;
    $_SESSION['task_timesheet'][$this->timestamp][$this->id]['yearWeek']=$this->yearWeek;
    $_SESSION['task_timesheet'][$this->timestamp][$this->id]['holiday']= serialize($this->holidays);
    $_SESSION['task_timesheet'][$this->timestamp][$this->id]['taskTimesheet']= serialize($this->taskTimesheet);
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
    $datestart=$this->date_start;
    $datestop= $this->date_end;
    $whiteList=$staticWhiteList->fetchUserList($userid, $datestart, $datestop);
     // Save the param in the SeSSION
     $tasksList=array();
     $whiteListNumber=count($whiteList);
     $sqlwhiteList='';
     if($whiteListNumber){
         
            $sqlwhiteList=', (CASE WHEN tsk.rowid IN ('.implode(",",  $whiteList).') THEN \'1\' ';
            $sqlwhiteList.=' ELSE \'0\' END ) AS listed';
    }
  
    
    $sql ='SELECT DISTINCT element_id as taskid,prj.fk_soc,tsk.fk_projet,';
    $sql.='tsk.fk_task_parent,tsk.rowid,app.rowid as appid';
    $sql.=$sqlwhiteList;
    $sql.=" FROM ".MAIN_DB_PREFIX."element_contact "; 
    $sql.=' JOIN '.MAIN_DB_PREFIX.'projet_task as tsk ON tsk.rowid=element_id ';
    $sql.=' JOIN '.MAIN_DB_PREFIX.'projet as prj ON prj.rowid= tsk.fk_projet ';
    //approval
    if( $this->status=="DRAFT" || $this->status=="REJECTED"){
        $sql.=' LEFT JOIN '.MAIN_DB_PREFIX.'project_task_time_approval as app ';
    }else{
        $sql.=' JOIN '.MAIN_DB_PREFIX.'project_task_time_approval as app ';
    }
    $sql.=' ON tsk.rowid= app.fk_projet_task AND app.fk_userid=fk_socpeople'; 

    $sql.=' AND app.date_start="'.$this->db->idate($datestart).'"';    
    $sql.=' AND app.date_end="'.$this->db->idate($datestop).'"';    

    //end approval
    $sql.=" WHERE (fk_c_type_contact='181' OR fk_c_type_contact='180') AND fk_socpeople='".$userid."' ";
    if(TIMESHEET_HIDE_DRAFT=='1'){
         $sql.=' AND prj.fk_statut<>"0" ';
    }
    $sql.=' AND (prj.datee>="'.$this->db->idate($datestart).'" OR prj.datee IS NULL)';
    $sql.=' AND (prj.dateo<="'.$this->db->idate($datestop).'" OR prj.dateo IS NULL)';
    $sql.=' AND (tsk.datee>="'.$this->db->idate($datestart).'" OR tsk.datee IS NULL)';
    $sql.=' AND (tsk.dateo<="'.$this->db->idate($datestop).'" OR tsk.dateo IS NULL)';
    $sql.='  ORDER BY '.($whiteListNumber?'listed,':'').'prj.fk_soc,tsk.fk_projet,tsk.fk_task_parent,tsk.rowid ';

     dol_syslog("timesheet::getTasksTimesheet full ", LOG_DEBUG);

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
                    $tasksList[$i] = NEW Task_time_approval($this->db,$obj->taskid);
                    //$tasksList[$i]->id= $obj->taskid;                     
                    if($obj->appid){
                        $tasksList[$i]->fetch($obj->appid);
                    }              
                    $tasksList[$i]->userId=$this->userId;
                    $tasksList[$i]->date_start_approval=$this->date_start;
                    $tasksList[$i]->date_end_approval=$this->date_end;
                    $tasksList[$i]->task_timesheet=$this->id;
                    $tasksList[$i]->listed=$obj->listed;
                    $i++;
                    
                    
            }
            $this->db->free($resql);
             $i = 0;
            if(isset($this->taskTimesheet))unset($this->taskTimesheet);
             foreach($tasksList as $row)
            {
                    dol_syslog("Timesheet::Task_timesheet.class.php task=".$row->id, LOG_DEBUG);
                    $row->getTaskInfo();
                    //if($this->status=="DRAFT" || $this->status=="REJECTED"){
                        
                    $row->getActuals($datestart,$datestop,$userid); 
                    //$row->task_timesheet=$this->id;
                   // }else{
                        //$row->getActuals($this->yearWeek,$userid); 
                   //     $row->getActuals($datestart,$datestop,$userid,$this->project_tasktime_list); 
                   // }
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

 /*
 * function to post the all actual submitted
 * 
 *  @param    array(int)              	$tabPost               array sent by POST with all info about the task
 *  @return     int                                                        number of tasktime creatd/changed
 */
 function updateActuals($tabPost)
{
     //FIXME, tta should be creted
    if($this->status=='APPROVED')
        return -1;
    dol_syslog('Entering in Timesheet::task_timesheet.php::updateActuals()');     
    $idList='';
   // $tmpRet=0;
    $_SESSION['task_timesheet'][$this->timestamp]['timeSpendCreated']=0;
    $_SESSION['task_timesheet'][$this->timestamp]['timeSpendDeleted']=0;
    $_SESSION['task_timesheet'][$this->timestamp]['timeSpendModified']=0;
        /*
         * For each task store in matching the session timestamp
         */
        foreach ($this->taskTimesheet as $key  => $row) {
            $tasktime= new Task_time_approval($this->db);
            $tasktime->unserialize($row);     
            $ret=$tasktime->postTaskTimeActual($tabPost[$tasktime->id],$this->userId,$this->user, $this->timestamp, $this->status);
            $taskList=$tasktime->getIdList();
            if(!empty($taskList)){
                $idList.=(empty($idList)?'':',').$taskList;
            }
            $this->taskTimesheet[$key]=$tasktime->serialize();
            
        } 
        /*
    if(!empty($idList)){
        //$this->project_tasktime_list=$idList;
        $this->update($this->user);
    }
    */
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
      dol_syslog("task_timesheet::get_userName ", LOG_DEBUG);
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
 * update the status based on the underlying Task_time_approval
 *  
 *  @param    int                       $userid              timesheet object, (task)
 *  @param    string              	$status              to overrule the logic if the status enter has an higher priority
 *  @return     string                         status updated of KO(-1)
 */
function updateStatus($user,$status=''){
    if($this->id<=0)return -1;
    $updatedStatus=2;
    $statusPriorityArray= array(0=>'CANCELLED',1=>'PLANNED',2=>'DRAFT',3=> 'INVOICED',4=>'APPROVED',5=>'UNDERAPPROVAL',6=>'SUBMITTED',7=>'CHALLENGED',8=>'REJECTED');
    if ($status!=''){
        if(!in_array($status,$statusPriorityArray ))return -1; // status not valid
         $updatedStatus=  array_search($status, $statusPriorityArray);;
    }
    
    
    $this->fetchTaskTimesheet();
    if($status==$this->status){ // to avoid eternal loop
        return 1;
    }
    foreach($this->taskTimesheet as $row){
        $tta= new Task_time_approval($db);
        $tta->unserialize($row);
        if($tta->appId<0){ // tta already created
            $tta->fetch();
            $statusPriorityCur=  array_search($tta->status, $statusPriorityArray); //FIXME
            $updatedStatus=($updatedStatus>$statusPriorityCur)?$updatedStatus:$statusPriorityCur;
        }// no else as the tta should be created upon submission of the TS not status update
        
    }
    $this->setStatus($user, $statusPriorityArray[$updatedStatus]);
    return $this->status;
}
 
 /*
 * change the status of an approval 
 * 
 *  @param      object/int        $user         user object or user id doing the modif
 *  @param      int               $id           id of the timesheetuser
 *  @return     int      		   	 <0 if KO, Id of created object if OK
 */
//    Public function setAppoved($user,$id=0){
Public function setStatus($user,$status,$id=0){ 
            $error=0;
            //if the satus is not an ENUM status
            if(!in_array($status, array('DRAFT','SUBMITTED','APPROVED','CANCELLED','REJECTED','CHALLENGED','INVOICED','UNDERAPPROVAL'))){
                dol_syslog(get_class($this)."::setStatus this status '{$status}' is not part or the enum list", LOG_ERROR);
                return false;
            }
            $Approved=(in_array($status, array('APPROVED','UNDERAPPROVAL')));
            $Rejected=(in_array($status, array('REJECTED','CHALLENGED')));
            $Submitted= ($status=='SUBMITTED');
            // Check parameters
            $userid=  is_object($user)?$user->id:$user;
            if($id!=0)$this->fetch($id);
            $this->status=$status;
        // Update request
            $error=($this->id<=0)?$this->create($user):$this->update($user);
            
            if($error>0){
                    if(count($this->taskTimesheet)<1 || $this->id<=0){
                        $this->fetch($id);
                        $this->fetchTaskTimesheet();
                  
                    }
                    foreach($this->taskTimesheet as $ts)
                    {
                        $tasktime= new Task_time_approval($this->db);
                        $tasktime->unserialize($ts);
                        if($Approved)$ret=$tasktime->Approved($userid,'team');
                        if($Rejected)$ret=$tasktime->challenged($userid,'team');
                        if($Submitted)$ret=$tasktime->setStatus($userid,'SUBMITTED',false);
                    }
                      //if($ret>0)$this->db->commit();
			return 1;
		}

    }



    
/******************************************************************************
 * 
 * HTML  methods
 * 
 ******************************************************************************/
 /* function to genegate the timesheet table header
 * 
  *  @param    bool           $ajax     ajax of html behaviour
  *  @return     string                                                   html code
 */
 function getHTMLHeader($ajax=false){
     global $langs;
     

    $html.="\n<table id=\"timesheetTable_{$this->id}\" class=\"noborder\" width=\"100%\">\n";
     ///Whitelist tab
     $html.='<tr class="liste_titre" id="">'."\n";
     
     foreach ($this->headers as $key => $value){
         $html.="\t<th ";
//         if ($headersWidth[$key]){
 //               $html.='width="'.$headersWidth[$key].'"';
 //        }
         $html.=">".$langs->trans($value)."</th>\n";
     }
    $opendays=str_split(TIMESHEET_OPEN_DAYS);
    $weeklength=round(($this->date_end-$this->date_start)/SECINDAY);
    for ($i=0;$i<$weeklength;$i++)
    {
        $curDay=$this->date_start+ SECINDAY*$i;
//        $html.="\t".'<th width="60px"  >'.$langs->trans(date('l',$curDay)).'<br>'.dol_mktime($curDay)."</th>\n";
        $html.="\t".'<th width="60px" style="text-align:center;" >'.$langs->trans(date('l',$curDay)).'<br>'.dol_print_date($curDay,'day')."</th>\n";
    }
     $html.="</tr>\n";
     $html.='<tr id="hiddenParam" style="display:none;">';
     $html.= '<td colspan="'.($this->headers.lenght+$weeklength).'"> ';
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
    $weeklength=round(($this->date_end-$this->date_start)/SECINDAY);
    for ($i=0;$i<$weeklength;$i++)
    {
       $html .="<th><div class=\"Total[{$this->id}][{$i}]\">&nbsp;</div></th>\n";
     }
    $html .="</tr>\n";
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
        
        $apflows=str_split(TIMESHEET_APPROVAL_FLOWS);
        if(in_array('1',$apflows)){
            $html .= '<input type="submit" class="butAction" name="submit"  value="'.$langs->trans('Submit')."\" />\n";
        }
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
                $row=new Task_time_approval($this->db);            
                 $row->unserialize($timesheet);
                //$row->db=$this->db;
                $Lines.=$row->getFormLine( $this->date_start,$this->date_end,$i,$this->headers,$this->whitelistmode,$this->status,$this->id); // fixme
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
     $html='<table class="noborder" width="50%"><tr><td>'.$langs->trans('Note').'</td></tr><tr><td>';
   

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
            $Nav.=  '<a id="navPrev" onClick="loadXMLTimesheet(\''.getPrevYearWeek($this->yearWeek).'\',0);';
        }else{
            $Nav.=  '<a href="?action=list&wlm='.$this->whitelistmode.'&yearweek='.getPrevYearWeek($this->yearWeek);   
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
            $Nav.=  '<a id="navNext" onClick="loadXMLTimesheet(\''.getNextYearWeek($this->yearWeek).'\',0);';
	}else{
            $Nav.=  '<a href="?action=list&wlm='.$whitelistmode.'&yearweek='.getNextYearWeek($this->yearWeek);
            
        }
        if ($optioncss != '') $Nav.=   '&amp;optioncss='.$optioncss;
        $Nav.=  '">'.$langs->trans("NextWeek").' &gt;&gt; </a>'."\n\t\t</th>\n\t</tr>\n </table>\n";
        return $Nav;
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
	 *	Initialise object with example values
	 *	Id must be 0 if object instance is a specimen
	 *
	 *	@return	void
	 */
	function initAsSpecimen()
	{
		$this->id=0;
		
		$this->userId='';
		$this->date_start='';
		$this->date_end='';
		//$this->status='';
		//$this->sender='';
		//$this->recipient='';
		//$this->estimates='';
		//$this->tracking='';
		//$this->tracking_ids='';
		$this->date_creation='';
		//$this->date_modification='';
		$this->user_creation='';
		//$this->user_modification='';
		$this->task='';
		$this->note='';

		
	}
      
 
/******************************************************************************
 * 
 * AJAX methods
 * 
 ******************************************************************************/
 
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
            $row=new Task_time_approval($this->db);
             $row->unserialize($timesheet);
            $xml.= $row->getXML($this->yearWeek);//FIXME
            $i++;
        }  
        $xml.="</userTs>";
    //}
    $xml.="</timesheet>";
    return $xml;
}	



}
?>
