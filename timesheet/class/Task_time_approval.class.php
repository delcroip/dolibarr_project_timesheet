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
require_once 'class/Task_timesheet.class.php';

//dol_include_once('/timesheet/class/projectTimesheet.class.php');
//require_once './projectTimesheet.class.php';
define('SECINDAY',86400);
define('TIMESHEET_BC_FREEZED','909090');
define('TIMESHEET_BC_VALUE','f0fff0');
class Task_time_approval extends Task 
{
    	var $element='Task_time_approval';			//!< Id that identify managed objects
	var $table_element='project_task_time_approval';		//!< Name of table without prefix where object is stored
        static $statusList,$apflows,$roleList,$statusColor;
        private $ProjectTitle		=	"Not defined";
        var $tasklist;
       // private $fk_project;
        private $taskParentDesc;
        //company info
        private $companyName;
        private $companyId;
        //project info
	private $startDatePjct;
	private $stopDatePjct;
	private $pStatus;
        //whitelist
        private $hidden; // in the whitelist 
	//time
        // from db
        var $appId;
        var $planned_workload_approval;
	var $userId;
	var $date_start_approval=''; 
        var $date_end_approval;
	var $status;
	var $sender;
	var $recipient;
        var $note; 
        var $user_app;

        //basic DB logging
	var $date_creation='';
	var $date_modification='';
//	var $user_creation;
	var $user_modification;
        var $task_timesheet;
        

//working variable

    var $duration;
    var $weekDays;
    var $userName;
    
    /**
     *  init the static variable
     *
     *  @return void          	no return
     */    
    public function init() 
    {
        /* key used upon update of the TS via the TTA
         * canceled or planned shouldn't affect the TS status update
         * draft will be stange at this stage but could be retrieved automatically // FIXME
         * invoiced should appear when there is no Submitted, underapproval, Approved, challenged, rejected
         * Approved should apear when there is no Submitted, underapproval,  challenged, rejected left
         * Submitted should appear when no approval action is started: underapproval, Approved, challenged, rejected
         * 
         */
        self::$statusColor=array('PLANNED'=>TIMESHEET_COL_DRAFT,'DRAFT'=>TIMESHEET_COL_DRAFT,'SUBMITTED'=>TIMESHEET_COL_SUBMITTED,'UNDERAPPROVAL'=>TIMESHEET_COL_SUBMITTED,'CHALLENGED'=>TIMESHEET_COL_REJECTED,'APPROVED'=>TIMESHEET_COL_APPROVED,'INVOICED'=>TIMESHEET_COL_APPROVED,'CANCELLED'=>TIMESHEET_COL_CANCELLED,'REJECTED'=>TIMESHEET_COL_REJECTED);
        self::$statusList=array(0=>'CANCELLED',1=>'PLANNED',2=>'DRAFT',3=>'INVOICED',4=>'APPROVED',5=>'SUBMITTED',6=>'UNDERAPPROVAL',7=>'CHALLENGED',8=>'REJECTED');
        self::$roleList=array(0=> 'user',1=> 'team', 2=> 'project',3=>'customer',4=>'supplier',5=>'other');
        self::$apflows=str_split(TIMESHEET_APPROVAL_FLOWS); //remove the leading _
    
    }
    public function __construct($db,$taskId=0,$id=0) 
	{
		$this->db=$db;
		$this->id=$taskId;
                $this->appId=$id;
                $this->status='DRAFT';
                $this->sender='user';
                $this->recipient='team';

                $this->user_app= array('team'=>0 ,'project'=>0 ,'customer'=>0 ,'supplier'=>0 ,'other'=>0  );
	}

/******************************************************************************
 * 
 * DB methods
 * 
 ******************************************************************************/

    /**
     *  CREATE object in the database
     *
     *  @param	int		$id    	Id object
     *  @param	string	$ref	Ref
     *  @return int          	<0 if KO, >0 if OK
     */
    function create($user, $notrigger=0)
    {
    	global $conf, $langs;
		$error=0;

		// Clean parameters
        
		if (isset($this->userId)) $this->userId=trim($this->userId);
		if (isset($this->date_start)) $this->date_start=trim($this->date_start);
		if (isset($this->date_end)) $this->date_end=trim($this->date_end);
		if (isset($this->date_start_approval)) $this->date_start_approval=trim($this->date_start_approval);
		if (isset($this->date_end_approval)) $this->date_end_approval=trim($this->date_end_approval);
		if (isset($this->status)) $this->status=trim($this->status);
		if (isset($this->sender)) $this->sender=trim($this->sender);
		if (isset($this->recipient)) $this->recipient=trim($this->recipient);
		if (isset($this->planned_workload_approval)) $this->planned_workload_approval=trim($this->planned_workload_approval);
		if (isset($this->user_app['team'])) $this->user_app['team']=trim($this->user_app['team']);
		if (isset($this->user_app['project'])) $this->user_app['project']=trim($this->user_app['project']);
		if (isset($this->user_app['customer'])) $this->user_app['customer']=trim($this->user_app['customer']);
		if (isset($this->user_app['supplier'])) $this->user_app['supplier']=trim($this->user_app['supplier']);
		if (isset($this->user_app['other'])) $this->user_app['other']=trim($this->user_app['other']);
		if (isset($this->date_creation)) $this->date_creation=trim($this->date_creation);
		if (isset($this->date_modification)) $this->date_modification=trim($this->date_modification);
		if (isset($this->user_creation)) $this->user_creation=trim($this->user_creation);
		if (isset($this->user_modification)) $this->user_modification=trim($this->user_modification);
		if (isset($this->id)) $this->id=trim($this->id);
		if (isset($this->note)) $this->note=trim($this->note);
		if (isset($this->task_timesheet)) $this->task_timesheet=trim($this->task_timesheet);

        

		// Check parameters
		// Put here code to add control on parameters values

        // Insert request
		$sql = "INSERT INTO ".MAIN_DB_PREFIX.$this->table_element."(";
		
		$sql.= 'fk_userid,';
		$sql.= 'date_start,';
                $sql.= 'date_end,';
		$sql.= 'status,';
		$sql.= 'sender,';
		$sql.= 'recipient,';
		$sql.= 'planned_workload,';
                $sql.= 'fk_user_app_team,';
                $sql.= 'fk_user_app_project,';
                $sql.= 'fk_user_app_customer,';
                $sql.= 'fk_user_app_supplier,';
                $sql.= 'fk_user_app_other,';               
		$sql.= 'date_creation,';
		$sql.= 'fk_user_creation,';
                $sql.= 'fk_projet_task,';
                $sql.= 'fk_project_task_timesheet,';
                $sql.= 'note';

		
        $sql.= ") VALUES (";
        
		$sql.=' '.(! isset($this->userId)?'NULL':'"'.$this->userId.'"').',';
		$sql.=' '.(! isset($this->date_start_approval) || dol_strlen($this->date_start_approval)==0?'NULL':'"'.$this->db->idate($this->date_start_approval).'"').',';
		$sql.=' '.(! isset($this->date_end_approval) || dol_strlen($this->date_end_approval)==0?'NULL':'"'.$this->db->idate($this->date_end_approval).'"').',';
		$sql.=' '.(! isset($this->status)?'"DRAFT"':'"'.$this->status.'"').',';
		$sql.=' '.(! isset($this->sender)?'"user"':'"'.$this->sender.'"').',';
		$sql.=' '.(! isset($this->recipient)?'"team"':'"'.$this->recipient.'"').',';
		$sql.=' '.(! isset($this->planned_workload_approval)?'NULL':'"'.$this->planned_workload_approval.'"').',';
		$sql.=' '.(! isset($this->user_app['team'])?'NULL':'"'.$this->user_app['team'].'"').',';
		$sql.=' '.(! isset($this->user_app['project'])?'NULL':'"'.$this->user_app['project'].'"').',';
		$sql.=' '.(! isset($this->user_app['customer'])?'NULL':'"'.$this->user_app['customer'].'"').',';
		$sql.=' '.(! isset($this->user_app['supplier'])?'NULL':'"'.$this->user_app['supplier'].'"').',';
		$sql.=' '.(! isset($this->user_app['other'])?'NULL':'"'.$this->user_app['other'].'"').',';
		$sql.=' NOW() ,';
		$sql.=' "'.$user->id.'",'; //fixme 3.5
		$sql.=' '.(! isset($this->id)?'NULL':'"'.$this->id.'"').',';
		$sql.=' '.(! isset($this->task_timesheet)?'NULL':'"'.$this->task_timesheet.'"').',';
		$sql.=' '.(! isset($this->note)?'NULL':'"'.$this->note.'"');
        
		$sql.= ")";

		$this->db->begin();

	   	dol_syslog(__METHOD__, LOG_DEBUG);
        $resql=$this->db->query($sql);
    	if (! $resql) { $error++; $this->errors[]="Error ".$this->db->lasterror(); }

		if (! $error)
        {
            $this->appId = $this->db->last_insert_id(MAIN_DB_PREFIX.$this->table_element);

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
                        return $this->appId;
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
		$sql.=' t.sender,';
		$sql.=' t.recipient,';
		$sql.=' t.planned_workload,';
                $sql.= 't.fk_user_app_team,';
                $sql.= 't.fk_user_app_project,';
                $sql.= 't.fk_user_app_customer,';
                $sql.= 't.fk_user_app_supplier,';
                $sql.= 't.fk_user_app_other,';
		$sql.=' t.date_creation,';
		$sql.=' t.date_modification,';
		$sql.=' t.fk_user_creation,';
		$sql.=' t.fk_user_modification,';
		$sql.=' t.fk_projet_task,';
		$sql.=' t.fk_project_task_timesheet,';
		$sql.=' t.note';

		
        $sql.= " FROM ".MAIN_DB_PREFIX.$this->table_element." as t";
        $sql.= " WHERE t.rowid = ".$id;

    	dol_syslog(__METHOD__);
        $resql=$this->db->query($sql);
        if ($resql)
        {
            if ($this->db->num_rows($resql))
            {
                $obj = $this->db->fetch_object($resql);

                $this->appId    = $obj->rowid;
                $this->userId = $obj->fk_userid;
                $this->date_start_approval = $this->db->jdate($obj->date_start);
                $this->date_end_approval = $this->db->jdate($obj->date_end);
                $this->status = $obj->status;
                $this->sender = $obj->sender;
                $this->recipient = $obj->recipient;
                $this->planned_workload_approval = $obj->planned_workload;
                $this->user_app['team'] = $obj->fk_user_app_team;
                $this->user_app['other'] = $obj->fk_user_app_other;
                $this->user_app['supplier'] = $obj->fk_user_app_supplier;
                $this->user_app['customer'] = $obj->fk_user_app_customer;
                $this->user_app['project'] = $obj->fk_user_app_project;               
                $this->date_creation = $this->db->jdate($obj->date_creation);
                $this->date_modification = $this->db->jdate($obj->date_modification);
                $this->user_creation = $obj->fk_user_creation;
                $this->user_modification = $obj->fk_user_modification;
                $this->id = $obj->fk_projet_task;
                $this->task_timesheet = $obj->fk_project_task_timesheet;
                $this->note  = $obj->note;

                
            }
            $this->db->free($resql);
            $this->yearWeek= getYearWeek(0,0,0,$this->date_start_approval); //fixme
            $this->ref=$this->yearWeek.'_'.$this->userId;
            $this->whitelistmode=2; // no impact
            $this->getTaskInfo();
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
		$sql.=' t.sender,';
		$sql.=' t.recipient,';
		$sql.=' t.planned_workload,';
                $sql.= 't.fk_user_app_team,';
                $sql.= 't.fk_user_app_project,';
                $sql.= 't.fk_user_app_customer,';
                $sql.= 't.fk_user_app_supplier,';
                $sql.= 't.fk_user_app_other,';
		$sql.=' t.date_creation,';
		$sql.=' t.date_modification,';
		$sql.=' t.fk_user_creation,';
		$sql.=' t.fk_user_modification,';
		$sql.=' t.fk_projet_task,';
		$sql.=' t.fk_project_task_timesheet,';
		$sql.=' t.note';

		
        $sql.= " FROM ".MAIN_DB_PREFIX.$this->table_element." as t";

        $sql.= " WHERE t.date_start = '".$this->db->idate($this->date_start_approval)."'";
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

                $this->appId    = $obj->rowid;
                
                $this->userId = $obj->fk_userid;
                $this->date_start_approval = $this->db->jdate($obj->date_start);
                $this->date_end_approval = $this->db->jdate($obj->date_end);
                $this->status = $obj->status;
                $this->sender = $obj->sender;
                $this->recipient = $obj->recipient;
                $this->user_app['team'] = $obj->fk_user_app_team;
                $this->user_app['other'] = $obj->fk_user_app_other;
                $this->user_app['supplier'] = $obj->fk_user_app_supplier;
                $this->user_app['customer'] = $obj->fk_user_app_customer;
                $this->user_app['project'] = $obj->fk_user_app_project;  
                $this->date_creation = $this->db->jdate($obj->date_creation);
                $this->date_modification = $this->db->jdate($obj->date_modification);
                $this->user_creation = $obj->fk_user_creation;
                $this->user_modification = $obj->fk_user_modification;
                $this->id = $obj->fk_projet_task;
                $this->task_timesheet = $obj->fk_project_task_timesheet;
                $this->note  = $obj->note;

                
            }else{
                unset($this->status) ;
                unset($this->sender) ;
                unset($this->recipient) ;
                unset($this->planned_workload_approvals) ;
                unset($this->tracking) ;
                unset($this->tracking_ids) ;
                unset($this->date_modification );
                unset($this->user_app['team ']);
                unset($this->user_app['project'] );
                unset($this->user_app['customer'] );
                unset($this->user_app['supplier'] );
                unset($this->user_app['other'] );
               // unset($this->date_start ); 
               // unset($this->date_end );
                //unset($this->date_start_approval );
                //unset($this->date_end_approval );
                unset($this->user_creation );
                unset($this->user_modification );
                unset($this->id );
                unset($this->note );
                unset($this->task_timesheet );
                unset($this->date_creation  );
                
                //$this->date_end= getEndWeek($this->date_start_approval);
                $this->create($this->user);
                $this->fetch($this->appId);
            }
            $this->db->free($resql);
            $this->getTaskInfo();
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
		if (isset($this->date_start_approval)) $this->date_start_approval=trim($this->date_start_approval);
		if (isset($this->date_end_approval)) $this->date_end_approval=trim($this->date_end_approval);
		if (isset($this->status)) $this->status=trim($this->status);
		if (isset($this->sender)) $this->sender=trim($this->sender);
		if (isset($this->recipient)) $this->recipient=trim($this->recipient);
		if (isset($this->planned_workload_approval)) $this->planned_workload_approval=trim($this->planned_workload_approval);
		if (isset($this->user_app['team'])) $this->user_app['team']=trim($this->user_app['team']);
		if (isset($this->user_app['project'])) $this->user_app['project']=trim($this->user_app['project']);
		if (isset($this->user_app['customer'])) $this->user_app['customer']=trim($this->user_app['customer']);
		if (isset($this->user_app['supplier'])) $this->user_app['supplier']=trim($this->user_app['supplier']);
		if (isset($this->user_app['other'])) $this->user_app['other']=trim($this->user_app['other']);
		if (isset($this->date_creation)) $this->date_creation=trim($this->date_creation);
		if (isset($this->date_modification)) $this->date_modification=trim($this->date_modification);
		if (isset($this->user_creation)) $this->user_creation=trim($this->user_creation);
		if (isset($this->user_modification)) $this->user_modification=trim($this->user_modification);
		if (isset($this->id)) $this->id=trim($this->id);
		if (isset($this->task_timesheet)) $this->task_timesheet=trim($this->task_timesheet);
		if (isset($this->note)) $this->note=trim($this->note);

        

		// Check parameters
		// Put here code to add a control on parameters values

        // Update request
        $sql = "UPDATE ".MAIN_DB_PREFIX.$this->table_element." SET";
        
		$sql.=' fk_userid='.(empty($this->userId) ? 'null':'"'.$this->userId.'"').',';
		$sql.=' date_start='.(dol_strlen($this->date_start_approval)!=0 ? '"'.$this->db->idate($this->date_start_approval).'"':'null').',';
		$sql.=' date_end='.(dol_strlen($this->date_end_approval)!=0 ? '"'.$this->db->idate($this->date_end_approval).'"':'null').',';
		$sql.=' status='.(empty($this->status)? 'null':'"'.$this->status.'"').',';
		$sql.=' sender='.(empty($this->sender) ? 'null':'"'.$this->sender.'"').',';
		$sql.=' recipient='.(empty($this->recipient) ? 'null':'"'.$this->recipient.'"').',';
		$sql.=' planned_workload='.(empty($this->planned_workload_approval) ? 'null':'"'.$this->planned_workload_approval.'"').',';
		$sql.=' fk_user_app_team='.(empty($this->user_app['team']) ? 'NULL':'"'.$this->user_app['team'].'"').',';
		$sql.=' fk_user_app_project='.(empty($this->user_app['project']) ? 'NULL':'"'.$this->user_app['project'].'"').',';
		$sql.=' fk_user_app_customer='.(empty($this->user_app['customer']) ? 'NULL':'"'.$this->user_app['customer'].'"').',';
		$sql.=' fk_user_app_supplier='.(empty($this->user_app['supplier']) ? 'NULL':'"'.$this->user_app['supplier'].'"').',';
		$sql.=' fk_user_app_other='.(empty($this->user_app['other']) ? 'NULL':'"'.$this->user_app['other'].'"').',';
		$sql.=' date_modification=NOW() ,';
		$sql.=' fk_user_modification="'.$user->id.'",';
		$sql.=' fk_projet_task='.(empty($this->id) ? 'null':'"'.$this->id.'"').',';
		$sql.=' fk_project_task_timesheet='.(empty($this->task_timesheet) ? 'null':'"'.$this->task_timesheet.'"').',';
		$sql.=' note="'.$this->note.'"';

        
        $sql.= " WHERE rowid=".$this->appId;

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
            $sql.= " WHERE rowid=".$this->appId;

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

        
        
        
        
        
        
        
/******************************************************************************
 * 
 * object methods
 * 
 ******************************************************************************/        
 
    /*
 * update the project task time Item
 * 
 *  @param      int               $status  status
 *  @return     int               <0 if KO, Id of created object if OK
 */
  
    Public function updateTaskTime($status){
        if(!in_array($status, self::$statusList) ) return -1; // role not valide      
//Update the the fk_tta in the project task time 
        $idList=array(); 
        if(!is_array($this->tasklist)) $this->getActuals ($this->date_start_approval, $this->date_end_approval, $this->userId);
        if(is_array($this->tasklist))foreach($this->tasklist as $item){
             $idList[]=$item['id'];
        }
        $ids=implode(',',$idList);
        $sql='UPDATE '.MAIN_DB_PREFIX.'projet_task_time SET fk_task_time_approval="';
        $sql.=$this->appId.'", status="'.$status.'" WHERE rowid in ('.$ids.')';
        // SQL start
        dol_syslog(__METHOD__);
        $this->db->begin();
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
        return $ret;// team key is 0 
    }            
        
        
        
        
        
        
        
    public function getTaskInfo()
    {
        $Company=strpos(TIMESHEET_HEADERS, 'Company')===0;
        $taskParent=strpos(TIMESHEET_HEADERS, 'TaskParent')>0;
        $sql ='SELECT p.rowid,p.datee as pdatee, p.fk_statut as pstatus, p.dateo as pdateo, pt.dateo,pt.datee, pt.planned_workload, pt.duration_effective';
        if(TIMESHEET_HIDE_REF==1){
            $sql .= ',p.title as title, pt.label as label';
            if($taskParent)$sql .= ',pt.fk_projet_task_parent,ptp.label as taskParentLabel';	        	
        }else{
            $sql .= ",CONCAT(p.`ref`,' - ',p.title) as title";
            $sql .= ",CONCAT(pt.`ref`,' - ',pt.label) as label";
            if($taskParent)$sql .= ",pt.fk_projet_task_parent,CONCAT(ptp.`ref`,' - ',ptp.label) as taskParentLabel";	
        }
        if($Company)$sql .= ',p.fk_soc as companyId,s.nom as companyName';

        $sql .=" FROM ".MAIN_DB_PREFIX."projet_task AS pt";
        $sql .=" LEFT JOIN ".MAIN_DB_PREFIX."projet as p";
        $sql .=" ON pt.fk_projet=p.rowid";
        if($taskParent){
            $sql .=" LEFT JOIN ".MAIN_DB_PREFIX."projet_task as ptp";
            $sql .=" ON pt.fk_projet_task_parent=ptp.rowid";
        }
        if($Company){
            $sql .=" LEFT JOIN ".MAIN_DB_PREFIX."societe as s";
            $sql .=" ON p.fk_soc=s.rowid";
        }
        $sql .=" WHERE pt.rowid ='".$this->id."'";
        #$sql .= "WHERE pt.rowid ='1'";
        dol_syslog(__METHOD__, LOG_DEBUG);

        $resql=$this->db->query($sql);
        if ($resql)
        {

                if ($this->db->num_rows($resql))
                {

                        $obj = $this->db->fetch_object($resql);

                        $this->description			= $obj->label;
                        $this->fk_project                      = $obj->rowid;
                        $this->ProjectTitle			= $obj->title;
                        #$this->date_start			= strtotime($obj->dateo.' +0 day');
                        #$this->date_end			= strtotime($obj->datee.' +0 day');
                        $this->date_start			= $this->db->jdate($obj->dateo);
                        $this->date_end			= $this->db->jdate($obj->datee);
                        $this->duration_effective           = $obj->duration_effective;		// total of time spent on this task
                        $this->planned_workload_approval             = $obj->planned_workload;
                        $this->startDatePjct=$this->db->jdate($obj->pdateo);
			$this->stopDatePjct=$this->db->jdate($obj->pdatee);
			$this->pStatus=$obj->pstatus;
                        
			if($taskParent){
                            $this->fk_projet_task_parent               = $obj->fk_projet_task_parent;
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
                dol_syslog(__METHOD__.$this->error, LOG_ERR);

                return -1;
        }	
    }
  /*
 *  FUNCTION TO GET THE ACTUALS FOR A WEEK AND AN USER
 *  @param    Datetime              	$timeStart            start date to look for actuals
 *  @param    Datetime              	$timeEnd            end date to look for actuals
 *  @param     int              	$userId         used in the form processing
 *  @param    string              	$tasktimeIds      limit the seach if defined
 *  @return     int                                       success (1) / failure (-1)
 */
    
    
    public function getActuals($timeStart=0,$timeEnd=0,$userid=0)
    {
           // change the time to take all the TS per day
           //$timeStart=floor($timeStart/SECINDAY)*SECINDAY;
           //$timeEnd=ceil($timeEnd/SECINDAY)*SECINDAY;
        
        if($timeStart==0) $timeStart=$this->date_start_approval;
        if($timeEnd==0) $timeEnd=$this->date_end_approval;
        if($userid==0) $userid=$this->userId;
        $dayelapsed=ceil($timeEnd-$timeStart)/SECINDAY;
        if($dayelapsed<1)return -1;
        $sql = "SELECT ptt.rowid, ptt.task_duration, ptt.task_date";	
        $sql .= " FROM ".MAIN_DB_PREFIX."projet_task_time AS ptt";
        $sql .= " WHERE ";
        // FIXME If status above

        if(in_array($this->status,array_slice(self::$statusList, 3,4))){
            $sql.=' ptt.fk_task_time_approval="'.$this->appId.'"';
        }else{
            $sql.=" ptt.fk_task='".$this->id."' ";
            $sql .= " AND (ptt.fk_user='".$userid."') ";
            $sql .= " AND (ptt.task_date>=".$this->db->idate($timeStart).") ";
            $sql .= " AND (ptt.task_date<".$this->db->idate($timeEnd).")";
        }  
        dol_syslog(__METHOD__, LOG_DEBUG);
		for($i=0;$i<$dayelapsed;$i++){
			
			$this->tasklist[$i]=array('id'=>0,'duration'=>0,'date'=>$timeStart+SECINDAY*$i);
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
                        $dateCur=$this->db->jdate($obj->task_date);
                        $day=(floor($dateCur-$timeStart)/SECINDAY);

                        $this->tasklist[$day]=array('id'=>$obj->rowid,'date'=>$dateCur,'duration'=>$obj->task_duration);
                        $i++;
                }
               
                $this->db->free($resql);
                return 1;
         }
        else
        {
                $this->error="Error ".$this->db->lasterror();
                dol_syslog(__METHOD__.$this->error, LOG_ERR);

                return -1;
        }
    }	   
   

 /*
 * function to form a HTMLform line for this timesheet
 * 
 *  @param     int              	$line number         used in the form processing
 *  @param    string              	$usUserId             id that will be used for the total
 *  @param    int                       $openOveride           0- no effect; 1 - force edition; (-1) - block edition

 *  @return     string                                        HTML result containing the timesheet info
 */
       public function getFormLine( $lineNumber,$headers,$tsUserId=0,$openOveride=0)
    {
           // change the time to take all the TS per day

           $dayelapsed=ceil(($this->date_end_approval-$this->date_start_approval)/SECINDAY);
  
   
       if(($dayelapsed<1)||empty($headers))
           return '<tr>ERROR: wrong parameters for getFormLine'.$dayelapsed.'|'.empty($headers).'</tr>';
      if($tsUserId!=0)$this->userId=$tsUserId;
    $timetype=TIMESHEET_TIME_TYPE;
    $dayshours=TIMESHEET_DAY_DURATION;
    $hidezeros=TIMESHEET_HIDE_ZEROS;
    $hidden=false;
    $status=$this->status;

    //if(($whitelistemode==0 && !$this->listed)||($whitelistemode==1 && $this->listed))$hidden=true;
    //$linestyle=(($hidden)?'display:none;':'');
    $Class=(($this->listed)?'timesheet_whitelist':'timesheet_blacklist').' timesheet_line '.(($lineNumber%2=='0')?'pair':'impair');
    
  $linestyle='';
  if(($this->pStatus == "2")){
      $linestyle.='background:#'.TIMESHEET_BC_FREEZED.';';
  }else{
      $linestyle.='background:#'.self::$statusColor[$this->status].';';
  }
    
    
    
    $html= '<tr class="'.$Class.'" '.((!empty($linestyle))?'style="'.$linestyle.'"':'');
    if(!empty($this->note))$html.=' title="'.htmlentities($this->note).'"';
      $html.=  '>'."\n"; 
    //title section
     foreach ($headers as $key => $title){
         $html.='<th align="left" >';
         switch($title){
             case 'Project':
                 if(version_compare(DOL_VERSION,"3.7")>=0){
                 //if(file_exists("../projet/card.php")||file_exists("../../projet/card.php")){
                    $html.='<a href="'.DOL_URL_ROOT.'/projet/card.php?mainmenu=project&id='.$this->fk_project.'">'.$this->ProjectTitle.'</a>';
                 }else{
                    $html.='<a href="'.DOL_URL_ROOT.'/projet/fiche.php?mainmenu=project&id='.$this->fk_project.'">'.$this->ProjectTitle.'</a>';

                 }
                 break;
             case 'TaskParent':
                 $html.='<a href="'.DOL_URL_ROOT.'/projet/tasks/task.php?mainmenu=project&id='.$this->fk_projet_task_parent.'&withproject='.$this->fk_project.'">'.$this->taskParentDesc.'</a>';
                 break;
             case 'Tasks':
                 $html.='<a href="'.DOL_URL_ROOT.'/projet/tasks/task.php?mainmenu=project&id='.$this->id.'&withproject='.$this->fk_project.'">'.$this->description.'</a>';
                 break;
             case 'DateStart':
                 $html.=$this->date_start?dol_print_date($this->date_start,'day'):'';
                 break;
             case 'DateEnd':
                 $html.=$this->date_end?dol_print_date($this->date_end,'day'):'';
                 break;
             case 'Company':
                 $html.='<a href="'.DOL_URL_ROOT.'/societe/soc.php?mainmenu=companies&socid='.$this->companyId.'">'.$this->companyName.'</a>';
                 break;
             case 'Progress':
                 $html .=$this->parseTaskTime($this->duration_effective).'/';
                if($this->planned_workload)
                {
                     $html .= $this->parseTaskTime($this->planned_workload).'('.floor($this->duration_effective/$this->planned_workload*100).'%)';
                }else{
                    $html .= "-:--(-%)";
                }
                if($this->planned_workload_approval) // show the time planned for the week
                {
                     $html .= '('.$this->parseTaskTime($this->planned_workload_approval).')';
                }
                 break;
            case 'User':
                $userName=get_userName($this->userId);
                $html .=  $userName[$this->userId];
                break;
             case 'Approval':
                 $html .= "<input type='text' style='border: none;' class = 'approval_switch'";
                 $html .=' name="approval['.$this->appId.']" ';
                 $html .=' id="task_'.$this->userId.'_'.$this->appId.'_approval" ';
                 $html .= " onfocus='this.blur()' readonly='true' size='1' value='&#x2753;' onclick='tristate_Marks(this)' />\n";

         }

         $html.="</th>\n";
     }

  // day section
        if($status=='INVOICED')$openOveride=-1; // once invoice it should not change
        $isOpenSatus=($openOveride==1) || ($status=='DRAFT' || $status=='CANCELLED'|| $status=='REJECTED' || $status=='PLANNED');
        if($openOveride==-1)$isOpenSatus=false;
        $opendays=str_split(TIMESHEET_OPEN_DAYS);

        for($dayCur=0;$dayCur<$dayelapsed;$dayCur++)
        {

            //$shrinkedStyle=(!$opendays[$dayCur+1] && $shrinked)?'display:none;':'';
            $today= $this->date_start_approval+SECINDAY*$dayCur;
            # to avoid editing if the task is closed 
            $dayWorkLoadSec=isset($this->tasklist[$dayCur])?$this->tasklist[$dayCur]['duration']:0;

            if ($timetype=="days")
            {
                $dayWorkLoad=$dayWorkLoadSec/3600/$dayshours;
            }else {
                $dayWorkLoad=date('H:i',mktime(0,0,$dayWorkLoadSec));
            }
                            $startDates=($this->date_start>$this->startDatePjct )?$this->date_start:$this->startDatePjct;
                            $stopDates=(($this->date_end<$this->stopDatePjct && $this->date_end!=0) || $this->stopDatePjct==0)?$this->date_end:$this->stopDatePjct;
            if($isOpenSatus){
                $isOpen=$isOpenSatus && (($startDates==0) || ($startDates < $today +SECINDAY));
                $isOpen= $isOpen && (($stopDates==0) ||($stopDates >= $today ));

                $isOpen= $isOpen && ($this->pStatus < "2") ;
                  $isOpen= $isOpen  && $opendays[date("N",$today)];

                $bkcolor='';

                if($isOpen){
                    $bkcolor='background:#'.self::$statusColor[$this->status];
                    if($dayWorkLoadSec!=0 && $this->status=='DRAFT' )$bkcolor='background:#'.TIMESHEET_BC_VALUE;
                    
                    
                }else{
                    $bkcolor='background:#'.TIMESHEET_BC_FREEZED;
                } 


                $html .= '<th  ><input type="text" '.(($isOpen)?'':'readonly').' class="time4day['.$this->userId.']['.$dayCur.']" ';
//                    $html .= 'name="task['.$this->userId.']['.$this->id.']['.$dayCur.']" '; if one whant multiple ts per validation
                $html .= 'name="task['.$this->userId.']['.$this->id.']['.$dayCur.']" ';
                $html .=' value="'.((($hidezeros==1) && ($dayWorkLoadSec==0))?"":$dayWorkLoad);
                $html .='" maxlength="5" style="width: 90%;'.$bkcolor.'" ';
                $html .='onkeypress="return regexEvent(this,event,\'timeChar\')" ';
                $html .= 'onblur="validateTime(this,'.$this->userId.','.$dayCur.',0)" />';
                $html .= "</th>\n"; 
            }else{
                //$bkcolor='background:#'.(($dayWorkLoadSec!=0)?(self::$statusColor[$this->status]):'#FFFFFF');
                //$html .= ' <th style="'.$bkcolor.'"><a class="time4day['.$this->userId.']['.$dayCur.']"';
                $html .= ' <th ><a class="time4day['.$this->userId.']['.$dayCur.']"';
                //$html .= ' name="task['.$this->userId.']['.$this->id.']['.$dayCur.']" ';if one whant multiple ts per validation
                $html .= ' name="task['.$this->id.']['.$dayCur.']" ';
                $html .= ' style="width: 90%;"';
                $html .=' >'.((($hidezeros==1) && ($dayWorkLoadSec==0))?"":$dayWorkLoad);
                $html .='</a> ';
                $html .= "</th>\n"; 


            }
        }
        $html .= "</tr>\n";
        return $html;

    }	


    public function test(){ //Fixme
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
 *//*
    public function getXML( $yearWeek)
    {
    $timetype=TIMESHEET_TIME_TYPE;
    $dayshours=TIMESHEET_DAY_DURATION;
    $hidezeros=TIMESHEET_HIDE_ZEROS;
    $xml= "<task id=\"{$this->id}\" >";
    //title section
    $xml.="<Tasks id=\"{$this->id}\">{$this->description} </Tasks>";
    $xml.="<Project id=\"{$this->fk_project}\">{$this->ProjectTitle} </Project>";
    $xml.="<TaskParent id=\"{$this->fk_projet_task_parent}\">{$this->taskParentDesc} </TaskParent>";
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
  * 
  */
/*
 * function to save a time sheet as a string
 */
function serialize(){
    $arRet=array();
    
    $arRet['id']=$this->id; //task id
    $arRet['listed']=$this->listed; //task id
    $arRet['description']=$this->description; //task id
    $arRet['appId']=$this->appId; // Task_time_approval id
    $arRet['tasklist']=$this->tasklist;
    $arRet['userId']=$this->userId; // user id booking the time
    $arRet['note']=$this->note;			
    $arRet['fk_project']=$this->fk_project ;
    $arRet['ProjectTitle']=$this->ProjectTitle;
    $arRet['date_start']=$this->date_start_approval;			
    $arRet['date_end']=$this->date_end_approval	;		
    $arRet['duration_effective']=$this->duration_effective ;   
    $arRet['planned_workload']=$this->planned_workload ;
    $arRet['fk_projet_task_parent']=$this->fk_projet_task_parent ;
    $arRet['taskParentDesc']=$this->taskParentDesc ;
    $arRet['companyName']=$this->companyName  ;
    $arRet['companyId']= $this->companyId;
    $arRet['pSatus']= $this->pStatus;
    $arRet['status']= $this->status; 
    $arRet['recipient']= $this->recipient; 
    $arRet['sender']= $this->sender; 
    $arRet['task_timesheet']= $this->task_timesheet; 

                      
    return serialize($arRet);
    
}
/*
 * function to load a time sheet as a string
 */
function unserialize($str){
    $arRet=unserialize($str);
    $this->id=$arRet['id'];
    $this->listed=$arRet['listed'];
    $this->description=$arRet['description'];
    $this->appId=$arRet['appId'];
    $this->userId=$arRet['userId'];
    $this->tasklist=$arRet['tasklist'];
    $this->note=$arRet['note'];			
    $this->fk_project=$arRet['fk_project'] ;
    $this->ProjectTitle=$arRet['ProjectTitle'];
    $this->date_start_approval=$arRet['date_start'];			
    $this->date_end_approval=$arRet['date_end']	;		
    $this->duration_effective=$arRet['duration_effective'] ;   
    $this->planned_workload=$arRet['planned_workload'] ;
    $this->fk_projet_task_parent=$arRet['fk_projet_task_parent'] ;
    $this->taskParentDesc=$arRet['taskParentDesc'] ;
    $this->companyName=$arRet['companyName']  ;
    $this->companyId=$arRet['companyId'];
    $this->status=$arRet['status'];
    $this->sender=$arRet['sender'];
    $this->recipient=$arRet['recipient'];
    $this->pStatus=$arRet['pSatus'];
    $this->task_timesheet=$arRet['task_timesheet'];
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
   
            dol_syslog(__METHOD__, LOG_DEBUG);


            $resql=$this->db->query($sql);
            if ($resql)
            {
                   // return 1;
            }
            else
            {
                    $this->error="Error ".$this->db->lasterror();
                    dol_syslog(__METHOD__.$this->error, LOG_ERR);

                    $error++;
            }	
                    // Commit or rollback
        if ($error)
        {
            foreach($this->errors as $errmsg)
            {
                dol_syslog(__METHOD__.$errmsg, LOG_ERR);
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
    
      /*
 * change the status of an approval 
 * 
 *  @param      object/int        $user         user object or user id doing the modif
 *  @param      int               $id           id of the timesheetuser
 *  @param      bool              $updateTS      update the timesheet if true
 *  @return     int      		   	 <0 if KO, Id of created object if OK
 */
//    Public function setAppoved($user,$id=0){
Public function setStatus($user,$status,$updateTS=true){ //FIXME
            $error=0;
            $ret=0;
            //if the satus is not an ENUM status
            if(!in_array($status, self::$statusList)){
                dol_syslog(__METHOD__." this status '{$status}' is not part or the enum list", LOG_ERROR);
                return false;
            }
            // Check parameters
            $this->status=$status;
           //if($this->appId && $this->date_start_approval==0)$this->fetch($this->appId);

            if($this->getDuration()>0){
                if($this->appId >0){
                    $ret=$this->update($user);
                } else{
                    $ret=$this->create($user);
                }
            }else if($this->appId >0){
                    $ret=$this->delete($user);
            }
          if($ret>0 && $updateTS==true){// success of the update, then update the timesheet user if possible
              $staticTS= new Task_timesheet($this->db );
              $staticTS->fetch($this->task_timesheet);
              $ret=$staticTS->updateStatus($user,$status);
          }
          return $ret;
    }   
  
      /*
 * change the status of an approval 
 * 
 *  @param      object/int        $user         user object or user id doing the modif
 *  @param      int               $id           id of the timesheetuser
 *  @param      bool              $updateTS      update the timesheet if true
 *  @return     int      		   	 <0 if KO, Id of created object if OK
 */
//    Public function setAppoved($user,$id=0){
Public function getDuration(){ //FIXME
    $ttaDuration=0;
    
    if(!is_array($this->tasklist))$this->getActuals();
    foreach($this->tasklist as $item){
         $ttaDuration+=$item['duration'];
    }
    return $ttaDuration;
}
 /* function to post on task_time
 * 
 *  @param    int              	$user                    user id to fetch the timesheets
 *  @param    object             	$tasktime             timesheet object, (task)
 *  @param    array(int)              	$tasktimeid          the id of the tasktime if any
 *  @param     int              	$timestamp          timesheetweek
 *  @param     sting             	$status          status to be update
 *  @return     int                                                       1 => succes , 0 => Failure
 */
function postTaskTimeActual($timesheetPost,$userId,$Submitter,$timestamp,$status)
{
    $ret=0;
    $idList='';
	dol_syslog("Timesheet.class::postTaskTimeActual  taskTimeId=".$this->id, LOG_DEBUG);
        $this->timespent_fk_user=$userId;
    if(is_array($timesheetPost))foreach ($timesheetPost as $dayKey => $wkload){		
        $item=$this->tasklist[$dayKey];
        
        if(TIMESHEET_TIME_TYPE=="days")
        {
           $duration=$wkload*TIMESHEET_DAY_DURATION*3600;
        }else
        {
         $durationTab=date_parse($wkload);
         $duration=$durationTab['minute']*60+$durationTab['hour']*3600;
        }
         dol_syslog(__METHOD__."   duration Old=".$item['duration']." New=".$duration." Id=".$item['id'].", date=".$item['date'], LOG_DEBUG);
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
                    dol_syslog(__METHOD__."  taskTimeUpdate", LOG_DEBUG);
                    if($this->updateTimeSpent($Submitter,0)>=0)
                    {
                        $ret++; 
                        $_SESSION['task_timesheet'][$timestamp]['timeSpendModified']++;
                    }else{
                        $_SESSION['task_timesheet'][$timestamp]['updateError']++;
                    }
                }else {
                    dol_syslog(__METHOD__."  taskTimeDelete", LOG_DEBUG);
                    if($this->delTimeSpent($Submitter,0)>=0)
                    {
                        $ret++;
                        $_SESSION['task_timesheet'][$timestamp]['timeSpendDeleted']++;
                        $this->tasklist[$dayKey]['id']=0;
                    }else{
                        $_SESSION['task_timesheet'][$timestamp]['updateError']++;
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
                $_SESSION['task_timesheet'][$timestamp]['timeSpendCreated']++;
                $this->tasklist[$dayKey]['id']=$newId;
            }else{
                $_SESSION['task_timesheet'][$timestamp]['updateError']++;
           }
        }
         //update the task list
        

        $this->tasklist[$dayKey]['duration']=$duration;
    }
   if($ret)$this->updateTimeUsed(); // needed upon delete
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
    if(is_array($this->tasklist))foreach ($this->tasklist as $tasktime){
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
            $sql.= ' FROM '.MAIN_DB_PREFIX.'project_task_time_approval as t';
            $sql.= ' JOIN '.MAIN_DB_PREFIX.'user as u on t.fk_userid=u.rowid ';
            $sql.= ' WHERE t.status="SUBMITTED" AND t.recipient="team"';
            $sql.= ' GROUP BY u.fk_user';
             dol_syslog(__METHOD__, LOG_DEBUG);
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


/*
 * pget the next approval in the chaine
 * 
 *  @param      object/int        $user         user object or user id doing the modif
 *  @param      string            $role         role who challenge
 *  @param      bool              $updteTS      update the timesheet if true
 *  @return     int      		   	 <0 if KO, Id of created object if OK
 */
  
    Public function Approved($sender,$role, $updteTS =true){
        
        if(!in_array($role, self::$roleList)) return -1; // role not valide
        $nextStatus='';
        $ret=0;

        //set the approver
        $this->user_app[$role]=$sender;
        //update the roles
        $rolepassed=false;
        foreach(self::$apflows as $key=> $value){         
            if ($value==1 ){  
                if ( self::$roleList[$key]==$role){
                    $this->sender= self::$roleList[$key];
                    //$this->user_app['{$role}=$sender;
                     $rolepassed=true;
                }else if ($rolepassed){
                    $this->recipient= self::$roleList[$key];
                    $ret=$key;      
                    break;
                }                         
            }

        }
        
        if($ret>0){//other approval found
            $nextStatus='UNDERAPPROVAL';
        }else{
            $nextStatus='APPROVED'; 
            // if approved,recipient 
            $this->recipient='user';
            //$this->recipient= self::$roleList[array_search('1', self::$apflows)];
        }
        // save the change in the db
         $ret=$this->setStatus($sender,$nextStatus,$updteTS);
         if($ret>0)$ret=$this->updateTaskTime($nextStatus);

    
        
        
        return $ret;
    }
        
/*
 * challenge the tsak time approval
 * 
 *  @param      object/int        $user         user object or user id doing the modif
 *  @param      string            $role         role who challenge
 *  @param      bool              $updteTS      update the timesheet if true
 *  @return     int      		   	 <0 if KO, Id of created object if OK
 */
  
    Public function challenged($sender,$role,$updteTS=true){
       $nextStatus='';
        if(!in_array($role, self::$roleList)) return -1; // role not valide      
        $ret=-1;
       //unset the approver ( could be set previsouly)
        $this->user_app[$role]=$sender;
        //update the roles
        foreach(self::$apflows as $key=> $recipient){
            if ($recipient==1){  
                if ( self::$roleList[$key]==$role){
                    $this->sender= self::$roleList[$key];
                    break;
                }else{
                    $this->recipient= self::$roleList[$key];
                    $ret=$key;      
                    
                }                
                          
            }
        } 
        
        if($ret>0){//other approval found
            $nextStatus='CHALLENGED';
        }else{
            $nextStatus='REJECTED';
             $this->recipient='user';
        }
       $ret=$this->setStatus($user,$nextStatus,$updteTS);
        if($ret>0)$ret=$this->updateTaskTime($nextStatus);
        return $ret+1;// team key is 0 
    }        
        

/*
 * submit the TS 
 * 
 *  @param      bool              $updteTS      update the timesheet if true
 *  @return     int      		   	 <0 if KO, Id of created object if OK
 */
  
    Public function submitted($updteTS=false){
      //Update the the fk_tta in the project task time 
        $ret=$this->setStatus($user,'SUBMITTED',$updteTS); // must be executed first to get the appid
        if($ret>0)$ret=$this->updateTaskTime('SUBMITTED');
       
        return $ret+1;// team key is 0 
    }        
        
    function dump(){
       var_dump($this->serialize());
    }

    
    
    
}
Task_time_approval::init()
?>
