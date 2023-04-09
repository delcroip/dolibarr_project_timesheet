<?php
/*
 * Copyright (C) 2014 delcroip <patrick@pmpd.eu>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY;without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
/*Class to handle a line of timesheet*/

require_once DOL_DOCUMENT_ROOT."/core/class/commonobject.class.php";
require_once DOL_DOCUMENT_ROOT.'/projet/class/task.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
require_once 'TimesheetUserTasks.class.php';
class TimesheetTask extends Task
{
    public $element = 'Task_time_approval';//!< Id that identify managed objects
    public $table_element = 'project_task_time_approval';//!< Name of table without prefix where object is stored
    public $ProjectTitle = "Not defined";
    public $tasklist;
    public $listed;
    // private $fk_project;
    private $taskParentDesc;
    //company info
    public $companyName;
    public $companyId;
    //project info
    private $startDatePjct;
    private $stopDatePjct;
    private $pStatus;
    //whitelist
    public $exclusionlist;// in the whitelist
        //time
    // from db
    public $appId;
    public $planned_workload_approval;
    public $userId;
    public $date_start_approval = '';
    public $date_end_approval;
    public $status;
    public $sender;
    public $recipient;
    public $note;
    public $user_app;
    //basic DB logging
    public $date_creation = '';
    public $date_modification = '';
    //public $user_creation;
    public $user_modification;
    public $task_timesheet;
    //working variable
    public $duration;
    public $weekDays;
    public $userName;
    public $isOpen;

    /**
     *  init the static variable
     *
     *  @return void          no return
     */
    public function init()
    {
        /* key used upon update of the TS via the TTA
         * canceled or planned shouldn't affect the TS status update
         * draft will be stange at this stage but could be retrieved automatically 
         * invoiced should appear when there is no Submitted, underapproval, Approved, challenged, rejected
         * Approved should apear when there is no Submitted, underapproval, challenged, rejected left
         * Submitted should appear when no approval action is started: underapproval, Approved, challenged, rejected
         *
         */
    }
    /** Constructor
     *
     * @param object $db  database object
     * @param int $taskId task id
     * @param type $id     object id
     */
    public function __construct($db, $taskId = 0, $id = 0)
    {
        $this->db = $db;
        $this->id = $taskId;
        $this->appId = $id;
        $this->status = DRAFT;
        $this->sender = USER;
        $this->recipient = TEAM;
        $this->user_app = array(TEAM => 0, PROJECT => 0, CUSTOMER => 0, SUPPLIER => 0, OTHER => 0);
    }
    /******************************************************************************
     *
     * DB methods
     *
     ******************************************************************************/
    /**
     *  CREATE object in the database
     *
     *  @param object|int $user     user|user id creating the object
     *  @param        int                $notrigger        block triggers
     *  @return int           <0 if KO, >0 if OK
     */
    public function create($user, $notrigger = 0)
    {
        $error = 0;
        // Clean parameters
        if (!empty($this->userId)) $this->userId = trim($this->userId);
        if (!empty($this->date_start)) $this->date_start = trim($this->date_start);
        if (!empty($this->date_end)) $this->date_end = trim($this->date_end);
        if (!empty($this->date_start_approval)) $this->date_start_approval = trim($this->date_start_approval);
        if (!empty($this->date_end_approval)) $this->date_end_approval = trim($this->date_end_approval);
        if (!empty($this->status)) $this->status = trim($this->status);
        if (!empty($this->sender)) $this->sender = trim($this->sender);
        if (!empty($this->recipient)) $this->recipient = trim($this->recipient);
        if (!empty($this->planned_workload_approval)) $this->planned_workload_approval = trim($this->planned_workload_approval);
        if (!empty($this->user_app[TEAM])) $this->user_app[TEAM] = trim($this->user_app[TEAM]);
        if (!empty($this->user_app[PROJECT])) $this->user_app[PROJECT] = trim($this->user_app[PROJECT]);
        if (!empty($this->user_app[CUSTOMER])) $this->user_app[CUSTOMER] = trim($this->user_app[CUSTOMER]);
        if (!empty($this->user_app[SUPPLIER])) $this->user_app[SUPPLIER] = trim($this->user_app[SUPPLIER]);
        if (!empty($this->user_app[OTHER])) $this->user_app[OTHER] = trim($this->user_app[OTHER]);
        //if (!empty($this->date_creation)) $this->date_creation = trim($this->date_creation);
        //if (!empty($this->date_modification)) $this->date_modification = trim($this->date_modification);
        if (!empty($this->user_creation)) $this->user_creation = trim($this->user_creation);
        if (!empty($this->user_modification)) $this->user_modification = trim($this->user_modification);
        if (!empty($this->id)) $this->id = trim($this->id);
        if (!empty($this->note)) $this->note = trim($this->note);
        if (!empty($this->task_timesheet)) $this->task_timesheet = trim($this->task_timesheet);
        $userId = (is_object($user)?$user->id:$user);
        $sql = "INSERT INTO ".MAIN_DB_PREFIX.$this->table_element."(";
        $sql .= 'fk_userid, ';
        $sql .= 'date_start, ';
        $sql .= 'date_end, ';
        $sql .= 'status, ';
        $sql .= 'sender, ';
        $sql .= 'recipient, ';
        $sql .= 'planned_workload, ';
        $sql .= 'fk_user_app_team, ';
        $sql .= 'fk_user_app_project, ';
        $sql .= 'fk_user_app_customer, ';
        $sql .= 'fk_user_app_supplier, ';
        $sql .= 'fk_user_app_other, ';
        $sql .= 'date_creation, ';
        $sql .= 'date_modification, ';
        $sql .= 'fk_user_creation, ';
        $sql .= 'fk_projet_task, ';
        $sql .= 'fk_project_task_timesheet, ';
        $sql .= 'note';
        $sql .= ") VALUES(";
        $sql .= ' '.(empty($this->userId)?'NULL':'\''.$this->userId.'\'').', ';
        $sql .= ' '.(empty($this->date_start_approval) || dol_strlen($this->date_start_approval) == 0?'NULL':'\''
            .$this->db->idate($this->date_start_approval).'\'').', ';
        $sql .= ' '.(empty($this->date_end_approval) || dol_strlen($this->date_end_approval) == 0?'NULL':'\''
            .$this->db->idate($this->date_end_approval).'\'').', ';
        $sql .= ' '.(empty($this->status)?'1':'\''.$this->status.'\'').', ';
        $sql .= ' '.(empty($this->sender)?USER:'\''.$this->sender.'\'').', ';
        $sql .= ' '.(empty($this->recipient)?TEAM:'\''.$this->recipient.'\'').', ';
        $sql .= ' '.(empty($this->planned_workload_approval)?'NULL':'\''.$this->planned_workload_approval.'\'').', ';
        $sql .= ' '.(empty($this->user_app[TEAM])?'NULL':'\''.$this->user_app[TEAM].'\'').', ';
        $sql .= ' '.(empty($this->user_app[PROJECT])?'NULL':'\''.$this->user_app[PROJECT].'\'').', ';
        $sql .= ' '.(empty($this->user_app[CUSTOMER])?'NULL':'\''.$this->user_app[CUSTOMER].'\'').', ';
        $sql .= ' '.(empty($this->user_app[SUPPLIER])?'NULL':'\''.$this->user_app[SUPPLIER].'\'').', ';
        $sql .= ' '.(empty($this->user_app[OTHER])?'NULL':'\''.$this->user_app[OTHER].'\'').', ';
        $sql .= ' NOW(), ';
        $sql .= ' NOW(), ';
        $sql .= ' \''.$userId.'\', ';
        $sql .= ' '.(empty($this->id)?'NULL':'\''.$this->id.'\'').', ';
        $sql .= ' '.(empty($this->task_timesheet)?'NULL':'\''.$this->task_timesheet.'\'').', ';
        $sql .= ' '.(empty($this->note)?'NULL':'\''.$this->db->escape(dol_html_entity_decode($this->note, ENT_QUOTES)).'\'');
        $sql .= ")";
        $this->db->begin();
        dol_syslog(__METHOD__, LOG_DEBUG);
        $resql = $this->db->query($sql);
        if (! $resql) {
            $error++;$this->errors[] = "Error ".$this->db->lasterror();
        }
        if (! $error) {
            $this->appId = $this->db->last_insert_id(MAIN_DB_PREFIX.$this->table_element);
            if (! $notrigger) {
        // Uncomment this and change MYOBJECT to your own tag if you
        // want this action calls a trigger.
        //// Call triggers
        //$result = $this->call_trigger('MYOBJECT_CREATE', $user);
        //if ($result < 0){ $error++;//Do also what you must do to rollback action if trigger fail}
        //// End call triggers
            }
        }
        // Commit or rollback
        if ($error) {
            foreach ($this->errors as $errmsg) {
                dol_syslog(__METHOD__." ".$errmsg, LOG_ERR);
                $this->error .= ($this->error?', '.$errmsg:$errmsg);
            }
                $this->db->rollback();
                return -1*$error;
        } else {
                $this->db->commit();
                return $this->appId;
        }
    }
    /**
    *  Load object in memory from database
    *
    *  @param        int                $id                                        Id object
    *  @param        int                $ref                                ref object
    *  @param        int                $loadparentdata                Also load parent data
    *  @return int                                 <0 if KO, 0 if not found, >0 if OK
     */
    public function fetch($id, $ref = '', $loadparentdata = 0)
    {
        if (!empty($ref) && empty($id)) {
            $temp = explode('_', $ref);
            if (isset($temp[2])) {
                $id = $temp[2];
            }
        }
        global $langs;
        $sql = "SELECT";
        $sql .= " t.rowid, ";
        $sql .= ' t.fk_userid, ';
        $sql .= ' t.date_start, ';
        $sql .= ' t.date_end, ';
        $sql .= ' t.status, ';
        $sql .= ' t.sender, ';
        $sql .= ' t.recipient, ';
        $sql .= ' t.planned_workload, ';
        $sql .= 't.fk_user_app_team, ';
        $sql .= 't.fk_user_app_project, ';
        $sql .= 't.fk_user_app_customer, ';
        $sql .= 't.fk_user_app_supplier, ';
        $sql .= 't.fk_user_app_other, ';
        $sql .= ' t.date_creation, ';
        $sql .= ' t.date_modification, ';
        $sql .= ' t.fk_user_creation, ';
        $sql .= ' t.fk_user_modification, ';
        $sql .= ' t.fk_projet_task, ';
        $sql .= ' t.fk_project_task_timesheet, ';
        $sql .= ' t.note';
        $sql .= " FROM ".MAIN_DB_PREFIX.$this->table_element." as t";
        $sql .= " WHERE t.rowid = ".$id;
        dol_syslog(__METHOD__, LOG_DEBUG);
        $resql = $this->db->query($sql);
        if ($resql) {
            if ($this->db->num_rows($resql)) {
                $obj = $this->db->fetch_object($resql);
                $this->appId = $obj->rowid;
                $this->userId = $obj->fk_userid;
                $this->date_start_approval = $this->db->jdate($obj->date_start);
                $this->date_end_approval = $this->db->jdate($obj->date_end);
                $this->status = $obj->status;
                $this->sender = $obj->sender;
                $this->recipient = $obj->recipient;
                $this->planned_workload_approval = $obj->planned_workload;
                $this->user_app[TEAM] = $obj->fk_user_app_team;
                $this->user_app[OTHER] = $obj->fk_user_app_other;
                $this->user_app[SUPPLIER] = $obj->fk_user_app_supplier;
                $this->user_app[CUSTOMER] = $obj->fk_user_app_customer;
                $this->user_app[PROJECT] = $obj->fk_user_app_project;
                $this->date_creation = $this->db->jdate($obj->date_creation);
                $this->date_modification = $this->db->jdate($obj->date_modification);
                $this->user_creation = $obj->fk_user_creation;
                $this->user_modification = $obj->fk_user_modification;
                $this->id = $obj->fk_projet_task;
                $this->task_timesheet = $obj->fk_project_task_timesheet;
                $this->note = $obj->note;
            }
            $this->db->free($resql);
            $this->ref = $this->date_start_approval.'_'.$this->userId.'_'.$this->id;
            $this->whitelistmode = 2;// no impact
            if ($loadparentdata)$this->getTaskInfo();
            return 1;
        } else {
            $this->error = "Error ".$this->db->lasterror();
            return -1;
        }
    }
 
    /**
     *  Update object into database
     *
     *  @param        User        $user        User that modifies
     *  @param  int                $notrigger         0 = launch triggers after, 1 = disable triggers
     *  @return int                         <0 if KO, >0 if OK
     */
    public function update($user = null, $notrigger = 0)
    {
        global $conf, $langs, $user;
        $error = 0;
        // Clean parameters
        if (!empty($this->userId)) $this->userId = trim($this->userId);
        if (!empty($this->date_start_approval)) $this->date_start_approval = trim($this->date_start_approval);
        if (!empty($this->date_end_approval)) $this->date_end_approval = trim($this->date_end_approval);
        if (!empty($this->status)) $this->status = trim($this->status);
        if (!empty($this->sender)) $this->sender = trim($this->sender);
        if (!empty($this->recipient)) $this->recipient = trim($this->recipient);
        if (!empty($this->planned_workload_approval)) $this->planned_workload_approval = trim($this->planned_workload_approval);
        if (!empty($this->user_app[TEAM])) $this->user_app[TEAM] = trim($this->user_app[TEAM]);
        if (!empty($this->user_app[PROJECT])) $this->user_app[PROJECT] = trim($this->user_app[PROJECT]);
        if (!empty($this->user_app[CUSTOMER])) $this->user_app[CUSTOMER] = trim($this->user_app[CUSTOMER]);
        if (!empty($this->user_app[SUPPLIER])) $this->user_app[SUPPLIER] = trim($this->user_app[SUPPLIER]);
        if (!empty($this->user_app[OTHER])) $this->user_app[OTHER] = trim($this->user_app[OTHER]);
        if (!empty($this->date_creation)) $this->date_creation = trim($this->date_creation);
        if (!empty($this->date_modification)) $this->date_modification = trim($this->date_modification);
        if (!empty($this->user_creation)) $this->user_creation = trim($this->user_creation);
        if (!empty($this->user_modification)) $this->user_modification = trim($this->user_modification);
        if (!empty($this->id)) $this->id = trim($this->id);
        if (!empty($this->task_timesheet)) $this->task_timesheet = trim($this->task_timesheet);
        if (!empty($this->note)) $this->note = trim($this->note);
        $userId = (is_object($user)?$user->id:$user);
        $sql = "UPDATE ".MAIN_DB_PREFIX.$this->table_element." SET";
        $sql .= ' fk_userid='.(empty($this->userId) ? 'null':'\''.$this->userId.'\'').', ';
        $sql .= ' date_start='.(dol_strlen($this->date_start_approval)!=0 ? '\''.$this->db->idate($this->date_start_approval).'\'':'null').', ';
        $sql .= ' date_end='.(dol_strlen($this->date_end_approval)!=0 ? '\''.$this->db->idate($this->date_end_approval).'\'':'null').', ';
        $sql .= ' status='.(empty($this->status)? 'null':'\''.$this->status.'\'').', ';
        $sql .= ' sender='.(empty($this->sender) ? 'null':'\''.$this->sender.'\'').', ';
        $sql .= ' recipient='.(empty($this->recipient) ? 'null':'\''.$this->recipient.'\'').', ';
        $sql .= ' planned_workload='.(empty($this->planned_workload_approval) ? 'null':'\''.$this->planned_workload_approval.'\'').', ';
        $sql .= ' fk_user_app_team='.(empty($this->user_app[TEAM]) ? 'NULL':'\''.$this->user_app[TEAM].'\'').', ';
        $sql .= ' fk_user_app_project='.(empty($this->user_app[PROJECT]) ? 'NULL':'\''.$this->user_app[PROJECT].'\'').', ';
        $sql .= ' fk_user_app_customer='.(empty($this->user_app[CUSTOMER]) ? 'NULL':'\''.$this->user_app[CUSTOMER].'\'').', ';
        $sql .= ' fk_user_app_supplier='.(empty($this->user_app[SUPPLIER]) ? 'NULL':'\''.$this->user_app[SUPPLIER].'\'').', ';
        $sql .= ' fk_user_app_other='.(empty($this->user_app[OTHER]) ? 'NULL':'\''.$this->user_app[OTHER].'\'').', ';
        $sql .= ' date_modification = NOW(), ';
        $sql .= ' fk_user_modification = \''.$userId.'\', ';
        $sql .= ' fk_projet_task='.(empty($this->id) ? 'null':'\''.$this->id.'\'').', ';
        $sql .= ' fk_project_task_timesheet='.(empty($this->task_timesheet) ? 'null':'\''.$this->task_timesheet.'\'').', ';
        $sql .= ' note = \''.$this->db->escape(dol_html_entity_decode($this->note, ENT_QUOTES)).'\'';
        $sql .= " WHERE rowid=".$this->appId;
        $this->db->begin();
        dol_syslog(__METHOD__, LOG_DEBUG);
        $resql = $this->db->query($sql);
        if (! $resql) {
            $error++;$this->errors[] = "Error ".$this->db->lasterror();
        }
        if (! $error) {
            if (! $notrigger) {
        // Uncomment this and change MYOBJECT to your own tag if you
        // want this action calls a trigger.
        //// Call triggers
        //$result = $this->call_trigger('MYOBJECT_MODIFY', $user);
        //if ($result < 0){ $error++;//Do also what you must do to rollback action if trigger fail}
        //// End call triggers
            }
        }
        // Commit or rollback
        if ($error) {
            foreach ($this->errors as $errmsg) {
                dol_syslog(__METHOD__." ".$errmsg, LOG_ERR);
                $this->error .= ($this->error?', '.$errmsg:$errmsg);
            }
            $this->db->rollback();
            return -1*$error;
        } else {
            $this->db->commit();
            return 1;
        }
    }
    /**
     *  Delete object in database
     *  @param  object             $user user that delete
     *  @param  int                $notrigger         0 = launch triggers after, 1 = disable triggers
     *  @return        int                                         <0 if KO, >0 if OK
     */
    public function delete($user, $notrigger = 0)
    {
        $error = 0;
        $this->db->begin();
        if (! $error) {
            if (! $notrigger) {
                // Uncomment this and change MYOBJECT to your own tag if you
                // want this action calls a trigger.
                //// Call triggers
                //$result = $this->call_trigger('MYOBJECT_DELETE', $user);
                //if ($result < 0){ $error++;//Do also what you must do to rollback action if trigger fail}
                //// End call triggers
            }
        }
        if (! $error) {
            $sql = "DELETE FROM ".MAIN_DB_PREFIX.$this->table_element;
            $sql .= " WHERE rowid=".$this->appId;
            dol_syslog(__METHOD__, LOG_DEBUG);
            $resql = $this->db->query($sql);
            if (! $resql) {
                $error++;$this->errors[] = "Error ".$this->db->lasterror();
            }
        }
        // Commit or rollback
        if ($error) {
            foreach ($this->errors as $errmsg) {
                dol_syslog(__METHOD__." ".$errmsg, LOG_ERR);
                $this->error .= ($this->error?', '.$errmsg:$errmsg);
            }
            $this->db->rollback();
            return -1*$error;
        } else {
            $this->db->commit();
            return 1;
        }
    }
    /******************************************************************************
     *
     * object methods
     *
     ******************************************************************************/
    /**
     * update the project task time Item
     *
     *  @param      int               $status  status
     *  @return     int               <0 if KO, Id of created object if OK
     */
    Public function updateTaskTime($status, $notrigger = True)
    {
        $error = 0;
        if ($status<0 || $status>STATUSMAX) return -1;// role not valide
        //Update the the fk_tta in the project task time
        $idList = array();
        if (!is_array($this->tasklist)) $this->getActuals($this->date_start_approval, 
            $this->date_end_approval, $this->userId);
        if (is_array($this->tasklist))foreach ($this->tasklist as $item) {
            if ($item['id']!='')$idList[] = $item['id'];
            if (is_array($item['other'])){
                $idList = array_merge($idList, array_column($item['other'], 'id'));
            } 
        }
        $ids = implode(', ', $idList);
        $sql = 'UPDATE '.MAIN_DB_PREFIX.'projet_task_time SET fk_task_time_approval = \'';
        $sql .= $this->appId.'\', status = \''.$status.'\' WHERE rowid in ('.$ids.')';
        // SQL start
        dol_syslog(__METHOD__, LOG_DEBUG);
        $this->db->begin();
        $resql = $this->db->query($sql);
        if (! $resql) {
            $error++;
            $this->errors[] = "Error ".$this->db->lasterror();
        }

        if (! $error) {
            if (! $notrigger) {
                // Uncomment this and change MYOBJECT to your own tag if you
                // want this action calls a trigger.
                //// Call triggers
                //$result = $this->call_trigger('MYOBJECT_MODIFY', $user);
                //if ($result < 0){ $error++;//Do also what you must do to rollback action if trigger fail}
                //// End call triggers
            }
        }
        // Commit or rollback
        if ($error) {
            foreach ($this->errors as $errmsg) {
                dol_syslog(__METHOD__." ".$errmsg, LOG_ERR);
                $this->error .= ($this->error?', '.$errmsg:$errmsg);
            }
            $this->db->rollback();
            return -1*$error;
        } else {
            $this->db->commit();
            return 1;
        }
        return 1;
    }
    /**
     * Get the task information from the dB
     *
     *  @return     int               <0 if KO, Id of created object if OK
     */
    public function getTaskInfo()
    {
        global $conf;
        $Company = true;// fixme should be base on the third party module activation
        $taskParent = strpos(getConf('TIMESHEET_HEADERS'), 'TaskParent')!== false;
        $sql = 'SELECT p.rowid, p.datee as pdatee, p.fk_statut as pstatus, p.dateo as pdateo,'
            .' pt.dateo, pt.datee, pt.planned_workload, pt.duration_effective';
        if (getConf('TIMESHEET_HIDE_REF') == 1) {
            $sql .= ', p.ref as title, pt.ref as label, pt.planned_workload';
            if ($taskParent) $sql .= ', pt.fk_task_parent, ptp.label as task_parent_label';
        } else {
            $sql .= ", CONCAT(p.ref, ' - ', p.title) as title";
            $sql .= ", CONCAT(pt.ref, ' - ', pt.label) as label";
            if ($taskParent) $sql .= ", pt.fk_task_parent, CONCAT(ptp.ref, ' - ', ptp.label) as task_parent_label";
        }
        if ($Company)$sql .= ', p.fk_soc as company_id, s.nom as company_name';
        $sql .= " FROM ".MAIN_DB_PREFIX."projet_task AS pt";
        $sql .= " JOIN ".MAIN_DB_PREFIX."projet as p";
        $sql .= " ON pt.fk_projet = p.rowid";
        if ($taskParent) {
            $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."projet_task as ptp";
            $sql .= " ON pt.fk_task_parent = ptp.rowid";
        }
        if ($Company) {
            $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s";
            $sql .= " ON p.fk_soc = s.rowid";
        }
        $sql .= " WHERE pt.rowid = '".$this->id."'";
        dol_syslog(__METHOD__, LOG_DEBUG);
        $resql = $this->db->query($sql);
        if ($resql) {
            if ($this->db->num_rows($resql)) {
                $obj = $this->db->fetch_object($resql);
                $this->description = $obj->label;
                $this->fk_project = $obj->rowid;
                $this->ProjectTitle = $obj->title;
                $this->date_start = $this->db->jdate($obj->dateo);
                $this->date_end = $this->db->jdate($obj->datee);
                $this->duration_effective = $obj->duration_effective;// total of time spent on this task
                $this->planned_workload = $obj->planned_workload;
                $this->startDatePjct = $this->db->jdate($obj->pdateo);
                $this->stopDatePjct = $this->db->jdate($obj->pdatee);
                $this->pStatus = $obj->pstatus;
                if ($taskParent) {
                    $this->fk_task_parent = $obj->fk_task_parent;
                    $this->taskParentDesc = $obj->task_parent_label;
                }
                if ($Company) {
                    $this->companyName = $obj->company_name;
                    $this->companyId = $obj->company_id;
                }
            }
            $this->db->free($resql);
            return 1;
        } else {
            $this->error = "Error ".$this->db->lasterror();
            dol_syslog(__METHOD__.$this->error, LOG_ERR);
            return -1;
        }
    }


    /**
     *  FUNCTION TO GET THE ACTUALS FOR A WEEK AND AN USER
     *  @param    Datetime              $timeStart       start date to look for actuals
     *  @param    Datetime              $timeEnd        end date to look for actuals
     *  @param     int               $userid         used in the form processing
     *  @return     int                                 success(1) / failure(-1)
     */
    public function getActuals($timeStart = 0, $timeEnd = 0, $userid = 0)
    {
        // change the time to take all the TS per day
        //$timeStart = floor($timeStart/SECINDAY)*SECINDAY;
        //$timeEnd = ceil($timeEnd/SECINDAY)*SECINDAY;
        if ($timeStart == 0){
            $timeStart = $this->date_start_approval;
        } else {
            $this->date_start_approval = $timeStart;
        }
        if ($timeEnd == 0){
            $timeEnd = $this->date_end_approval;
        } else {
            $this->date_end_approval = $timeEnd;
        }
        if ($userid == 0){
            $userid = $this->userId;
        } else {
            $this->userId= $userid;
        }
        $this->timespent_fk_user = $userid;
        $dayelapsed = getDayInterval($timeStart, $timeEnd);
        if ($dayelapsed<1)return -1;
        $sql = "SELECT ptt.rowid, ptt.task_duration, DATE(ptt.task_datehour) AS task_date, ptt.note";
         if (version_compare(DOL_VERSION, "4.9.9") >= 0) {
            $sql .= ', (ptt.invoice_id > 0 or ptt.invoice_line_id>0)  AS invoiced';
        }else{
            $sql .= ', 0 AS invoiced';
        }
        $sql .= " FROM ".MAIN_DB_PREFIX."projet_task_time AS ptt";
        $sql .= " WHERE ";
        if ($this->id == -1 && is_array($this->exclusionlist)){
            $sql .= " ptt.fk_task not in  ('".implode("','",$this->exclusionlist)."') ";
            $sql .= " AND (ptt.fk_user = '".$userid."') ";
            $sql .= " AND (DATE(ptt.task_datehour) >= '".$this->db->idate($timeStart)."') ";
            $sql .= " AND (DATE(ptt.task_datehour)<'".$this->db->idate($timeEnd)."')";
        }elseif (in_array($this->status, array(SUBMITTED, UNDERAPPROVAL, APPROVED, CHALLENGED, INVOICED))) {
            $sql .= ' ptt.fk_task_time_approval = \''.$this->appId.'\'';
        } else {
            $sql .= " ptt.fk_task = '".$this->id."' ";
            $sql .= " AND (ptt.fk_user = '".$userid."') ";
            $sql .= " AND (DATE(ptt.task_datehour) >= '".$this->db->idate($timeStart)."') ";
            $sql .= " AND (DATE(ptt.task_datehour)<'".$this->db->idate($timeEnd)."')";
        }
        dol_syslog(__METHOD__, LOG_DEBUG);
        $other = array();
        for($i = 0;$i<$dayelapsed;$i++)
        {
            $other[$i] = array();    
            $this->tasklist[$i] = array('id' => 0, 'duration' => 0, 
                'date'=>$timeStart+SECINDAY*$i+SECINDAY/4, 'other' => null);
        }
        $resql = $this->db->query($sql);
        
        if ($resql) {
            $num = $this->db->num_rows($resql);
            $i = 0;
            unset($this->tasklistAll);
            // Loop on each record found, so each couple (project id, task id)
            while($i < $num)
            {
                $error = 0;

                $obj = $this->db->fetch_object($resql);
                $dateCur = $this->db->jdate($obj->task_date);
                $day = getDayInterval($timeStart, $dateCur);
                if(!isset($this->tasklist[$day]['note'])){
                    $this->tasklist[$day]['note'] = '';
                }
                // put the other timesheet on the "other" list
                if ($this->tasklist[$day]['duration'] > 0 || strlen($this->tasklist[$day]['note']) > 0){
                    $other[$day][] = array( 'duration' => $this->tasklist[$day]['duration'], 
                        'id' => $this->tasklist[$day]['id'], 'note' => $this->tasklist[$day]['note']);
                }
                
                $this->tasklist[$day] = array('id'=>$obj->rowid, 'date'=>$dateCur, 
                    'duration'=> $obj->task_duration, 'note'=>$obj->note, 
                'invoiced' => $obj->invoiced);
                if (is_array($other[$day]) > 0)
                    $this->tasklist[$day]['other'] = $other[$day];
 //               $this->tasklistAll[] =  array('day' => $day, 'id'=>$obj->rowid, 'date'=>$dateCur, 'duration'=>$obj->task_duration, 'note'=>$obj->note, 'invoiced' => $obj->invoiced);
                $i++;
            }
            $this->db->free($resql);
            return 1;
        } else {
            $this->error = "Error ".$this->db->lasterror();
            dol_syslog(__METHOD__.$this->error, LOG_ERR);
            return -1;
        }
    }

    /**
     * funciton get_week_total
     * 
     * @return number of second
     */

     public function getSavedTimeTotal()
     {
        if (is_array($this->tasklist))
        {
            $total = 0;
            foreach ($this->tasklist as $key => $daytasktime) {
                $total+= $daytasktime['duration'];
            }
        }
        return $total;
     }

    /**
     * function to form a HTMLform line for this timesheet
     *
     *  @param      array(string)       $headers             Headers to display
     *  @param      string              $tsUserId            id that will be used for the total
     *  @param      int|array           $blockOveride         0- no effect;-1 - force edition;(1) - block edition 
     *  @param      array|null          $holiday                list of holiday
     *  @return     string                                   HTML result containing the timesheet info
     */
    public function getTimesheetLine($headers, $tsUserId = 0, $blockOveride = 0, $holiday = array() )
    {
        $totalDuration = $this->getDuration();
        //hide closed project if no time
        if ($this->pStatus >1 and $totalDuration == 0){
            return '';
        }
        global $langs, $conf, $statusColor;
        // change the time to take all the TS per day
        $dayelapsed = getDayInterval($this->date_start_approval, $this->date_end_approval);
        if (($dayelapsed<1)||empty($headers))
           return '<tr>ERROR: wrong parameters for getTimesheetLine|'.$headers.'</tr>';
        if ($tsUserId!=0)$this->userId = $tsUserId;
        $Class = 'oddeven '.(($this->listed)?
            'timesheet_whitelist':'timesheet_blacklist').' timesheet_line line_'.$tsUserId;
        $htmltail = '';
        $linestyle = '';
        if (($this->pStatus > 1)) {
            $linestyle .= 'background:#'.$statusColor[FROZEN].';';
        } elseif ($statusColor[$this->status]!='' &&  $statusColor[$this->status]!='FFFFFF') {
            $linestyle .= 'background:#'.$statusColor[$this->status].';';
        }
        /*
        * Open task ?
        */
        if ($this->status == INVOICED)$blockOveride = 1;// once invoice it should not change
        $isOpenStatus = $this->isOpen && in_array($this->status, array(DRAFT, CANCELLED, REJECTED, PLANNED));
        if( $blockOveride == 1){
            $isOpenStatus = false;
        }else if ($blockOveride == -1){
            $isOpenStatus = true && $isOpenStatus;
        }
        
        /*
         * info section
         */
        $html = '<tr class = "'.$Class.'" '.((!empty($linestyle))?'style = "'.$linestyle.'"':'');
        if (!empty($this->note))$html .= ' title = "'.htmlentities($this->note).'"';
        $html .= ' id="userTask_'.$tsUserId.'_'.$this->id.'" ';
        $html .= '>'."\n";
        //title section
        $html .= $this->getHTMLlineInfoCell($headers);
        if(is_array($holiday) && $isOpenStatus){
            $html .= $this->getHTMLLineDayCell($isOpenStatus, $holiday);
        }else{
            $html .= $this->getHTMLLineDayCell($isOpenStatus);
        }
        $html .= "</tr>\n";

        return $html.$htmltail;
    }
    /**
    * function to generate the day cell content for HTML Display
    *
    *  @param      int               $isOpenStatus            is the line open for time entry
    *  @param      array             $holidayList  
    *  @return     string                                         HTML result containing the timesheet info
    */
    public function getHTMLLineDayCell($isOpenStatus, $holidayList=array())
    {
        global $langs, $conf, $statusColor;
        $isOpen = false;
        $html = '';
        $dayelapsed = getDayInterval($this->date_start_approval, $this->date_end_approval);
        // day section
        $unblockInvoiced = getConf('TIMESHEET_UNBLOCK_INVOICED');
        $unblockClosedDay = getConf('TIMESHEET_UNBLOCK_CLOSED');
        $hidezeros = getConf('TIMESHEET_HIDE_ZEROS');
        $blockholiday = getConf('TIMESHEET_BLOCK_HOLIDAY');
        $blockPublicHoliday = getConf('TIMESHEET_BLOCK_PUBLICHOLIDAY');
        $opendays = str_split(getConf('TIMESHEET_OPEN_DAYS','_1111100'));
        $hidden = false;
        $default_timezone = (empty($_SESSION["dol_tz_string"])?
            @date_default_timezone_get():$_SESSION["dol_tz_string"]);
        $timezoneoffset = get_timezone_offset($default_timezone, 'UTC');
        for($dayCur = 0;$dayCur<$dayelapsed;$dayCur++)
        {

            $today = $this->date_start_approval+SECINDAY*$dayCur ;
            $today_end = $today + SECINDAY-1 ;
            // to avoid editing if the task is closed
            $dayWorkLoadSec = isset($this->tasklist[$dayCur])?$this->tasklist[$dayCur]['duration']:0;
            $dayWorkLoad = formatTime($dayWorkLoadSec, -1);
            
            
            $startDates = ($this->date_start>$this->startDatePjct)?$this->date_start:$this->startDatePjct;

            $stopDates = (($this->date_end<$this->stopDatePjct && $this->date_end!=0) 
                || $this->stopDatePjct == 0)?$this->date_end:$this->stopDatePjct;
            //take the end of the day
            
            $noteother = '';
            $otherSec = 0;
            if (is_array($this->tasklist[$dayCur]['other'])){
                $noteother = implode("\n", array_column($this->tasklist[$dayCur]['other'], 'note'));
                $otherSec = array_sum(array_column($this->tasklist[$dayCur]['other'], 'duration'));
            }
            $other = formatTime($otherSec, -1);
            
            if ($isOpenStatus && $this->id>0) {
                $isOpen = $isOpenStatus && (($startDates == 0) || ($startDates <= $today_end ));
                $isOpen = $isOpen && (empty($stopDates) ||($stopDates >= $today));
                $isOpen = $isOpen && ($this->pStatus < "2") ;
                $isOpenDay = $opendays[date("N", $today)];
                $isInvoiced = isset($this->tasklist[$dayCur]['invoiced'])?$this->tasklist[$dayCur]['invoiced']:0;
                if ($unblockClosedDay == 0) $isOpen = $isOpen  && $isOpenDay;
                if ($unblockInvoiced == 0) $isOpen = $isOpen  && !$isInvoiced;
                if (isset($holidayList[$dayCur])){
                    $isOpen = $isOpen && 
                    !($blockholiday == 1 && array_key_exists('am',$holidayList[$dayCur]) && $holidayList[$dayCur]['am'] == true && array_key_exists('pm',$holidayList[$dayCur]) && $holidayList[$dayCur]['pm'] == true) &&
                    !($blockPublicHoliday == 1 && $holidayList[$dayCur]['dayoff']);

                }


                $bkcolor = '';
                if (!$isOpenDay){
                    $bkcolor = 'background:#'.$statusColor[FROZEN];
                } elseif ($isOpen) {
                    $bkcolor = 'background:#'.$statusColor[$this->status];
                    if ($dayWorkLoadSec!=0 && $this->status == DRAFT){
                        $bkcolor = 'background:#'.$statusColor[VALUE];
                    }
                }else {
                    $bkcolor = 'background:#'.$statusColor[FROZEN];
                }
                if ($isInvoiced){
                    $bkcolor = 'background:#'.$statusColor[INVOICED];
                }
                
                $html .= "<td>\n";
                // add note popup
                if ($isOpen && getConf('TIMESHEET_SHOW_TIMESPENT_NOTE')) {
                    $html .= img_picto('Note', empty($this->tasklist[$dayCur]['note'])?'filenew':'file', '  id="img_note_'
                        .$this->userId.'_'.$this->id.'_'.$dayCur.
                        '" style = "display:inline-block;float:right;" onClick = "openNote(\'note_'
                        .$this->userId.'_'.$this->id.'_'.$dayCur.'\')"');
                //note code
                $html .= '<div class = "modal" id = "note_'.$this->userId.'_'.$this->id.'_'.$dayCur.'" >';
                $html .= '<div class = "modal-content">';
                $html .= '<span class = "close " onclick = "closeNotes()">&times;</span>';
                $html .= '<a>'.$langs->trans('Note').' ('.$this->ProjectTitle.', '
                    .$this->description.', '.dol_print_date($today, 'day').")".'</a><br>';
                $html .= '<textarea class = "flat"  rows = "3" style = "width:350px;top:10px"';
                $html .= ' name = "task['.$this->userId.']['.$this->id.']['.$dayCur.'][1]" ';
                $html .= '>'.(array_key_exists('note', $this->tasklist[$dayCur])?$this->tasklist[$dayCur]['note']:'').'</textarea>';
                $html .= '</div></div>';
                }
                //add input day
                $html .= '<div style = "display:inline-block;"><input  type = "text" '
                    .(($isOpen)?'':'readonly').' class = "column_'
                    .$this->userId.'_'.$dayCur.' user_'.$this->userId.' line_'.$this->userId.'_'.$this->id.'" ';
                $html .= ' name = "task['.$this->userId.']['.$this->id.']['.$dayCur.'][0]" ';
                $html .= ' value = "'.((($hidezeros == 1) && ($dayWorkLoadSec == 0))?"":$dayWorkLoad);
                $html .= '" maxlength = "5" size = "2" style = "'.$bkcolor.'" ';
                $html .= 'onkeypress = "return regexEvent(this,event,\'timeChar\')" ';
                $html .= 'onblur = "validateTime(this, \''.$this->userId.'_'.$dayCur.'\')" /></div>';
                 //end note code
                 if ( $otherSec > 0){
                    $html .= '<br><a class = "column_'.$this->userId.'_'.$dayCur.' user_'
                        .$this->userId.' line_'.$this->userId.'_'.$this->id.'"';
                    if (!empty($noteother))$html .= ' title = "'.htmlentities($noteother).'"';
                    //$html .= ' name = "task['.$this->id.']['.$dayCur.']" ';
                    $html .= ' style = "width: 90%;"';
                    $html .= ' >'.((($hidezeros == 1) && ($otherSec == 0))?"":$other);
                    $html .= '</a> ';
                 }

                $html .= "</td>\n";
            } else {
                $html .= ' <td><a class = "column_'.$this->userId.'_'.$dayCur.' user_'
                    .$this->userId.' line_'.$this->userId.'_'.$this->id.'"';
                if (!empty($this->tasklist[$dayCur]['note'])){
                    $html .= ' title = "'.htmlentities($this->tasklist[$dayCur]['note']
                        .((strlen($noteother)>0)?"\n".$noteother:'')).'"';
                }
                $dayWorkLoadAllSec = $dayWorkLoadSec + $otherSec;
                $dayWorkLoadAll = formatTime($dayWorkLoadAllSec, -1);
                $html .= ' name = "task['.$this->id.']['.$dayCur.']" ';
                $html .= ' style = "width: 90%;"';
                $html .= ' >'.((($hidezeros == 1) && ($dayWorkLoadAllSec == 0))?"":$dayWorkLoadAll);
                $html .= '</a> ';
                $html .= "</td>\n";
            }
        }
        return $html;
    }
    /**
    * function to generate the info  cell content for HTML Display
    *
    *  @param    string[]   $headers            headers to diplay
    *  @return   string                         HTML result containing the timesheet info
    **/
    public function getHTMLlineInfoCell($headers)
    {
        global $langs, $conf;
        $htmlTitle = '';
        foreach ($headers as $key => $title) {
            $html = '';
            if($this->id == -1){
                $html .= '<div class="colOther">'.$langs->trans('Other').' </div>';    
            }else switch($title) {
                case 'Project':
                    $html .= '<div class="colProject">';
                    $objtemp = new Project($this->db);
                    $objtemp->fetch($this->fk_project);
                    $html .= str_replace('classfortooltip', 'classfortooltip colProject', 
                        $objtemp->getNomUrl(0, '', getConf('TIMESHEET_HIDE_REF')));
                    $html .= '</div>';
                    break;
                case 'TaskParent':
                    $html .= '<div class="colTaskParent">';
                    $objtemp = new Task($this->db);
                    $objtemp->fetch($this->fk_task_parent);
                    $html .= str_replace('classfortooltip', 'classfortooltip colTaskParent', 
                        $objtemp->getNomUrl(0, "withproject", "task", getConf('TIMESHEET_HIDE_REF')));
                    $html .= '</div>';
                    break;
                case 'Tasks':
                    if (getConf('TIMESHEET_WHITELIST') == 1)$html .= '<img id = "'
                        .$this->listed.'" src = "img/fav_'.(($this->listed>0)?'on':'off')
                        .'.png" onClick = favOnOff(event,'.$this->fk_project.','
                        .$this->id.') style = "cursor:pointer;">  ';
                    $objtemp = new Task($this->db);
                    $objtemp->fetch($this->id);
                    $html .= str_replace('classfortooltip', 'classfortooltip colTasks', 
                        $objtemp->getNomUrl(0, "withproject", "task", getConf('TIMESHEET_HIDE_REF')));
                    break;
                case 'DateStart':
                    $html .= '<div class="colDateStart">';
                    $html .= $this->date_start?dol_print_date($this->date_start, 'day'):'';
                    $html .= '</div>';
                    break;
                case 'DateEnd':
                    $html .= '<div class="colDateEnd">';
                    $html .= $this->date_end?dol_print_date($this->date_end, 'day'):'';
                    $html .= '</div>';
                    break;
                case 'Company':
                    $soc = new Societe($this->db);
                    $soc->fetch($this->companyId);
                    $html .= str_replace('classfortooltip', 'classfortooltip colCompany', $soc->getNomUrl());
                    break;
                case 'Progress':
                    $html .= $this->parseTaskTime($this->duration_effective).'/';
                    if ($this->planned_workload>0) {
                        $html .= $this->parseTaskTime($this->planned_workload).'('
                            .floor($this->duration_effective/$this->planned_workload*100).'%)';
                    } else {
                        $html .= "-:--(-%)";
                    }
                    if ($this->planned_workload_approval) {
                        // show the time planned for the week {
                        $html .= '('.$this->parseTaskTime($this->planned_workload_approval).')';
                    }
                    break;
                case 'ProgressDeclared':
                    require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
                    $formother = new FormOther($db);
                    $html .= $formother->select_percent($this->progress, 
                        'progressTask['.$this->userId.']['.$this->id.']', 0, 5, 0, 100, 1);
                    break;
                case 'User':
                    $userName = getUsersName($this->userId);
                    $html .= '<div class="colUser">';
                    $html .= $userName[$this->userId];
                    $html .= '</div>';
                    break;
                case 'Total':
                    $html .= '<div class = "lineTotal colTotal" id = "'.$this->userId.'_'.$this->id.'">&nbsp;</div>';
                    break;
                case 'Approval':
                    $html .= "<input type = 'text' style = 'border: none;' class = 'approval_switch'";
                    $html .= ' name = "approval['.$this->appId.']" ';
                    $html .= ' id = "task_'.$this->userId.'_'.$this->appId.'_approval" ';
                    $html .= " onfocus = 'this.blur()' readonly = 'true' size = '1' value = '&#x2753;'"
                        ." onclick = 'tristate_Marks(this)' />\n";
                    break;
                case 'Note':
                    $html .= img_picto('Note', empty($this->note)?'filenew':'file',
                         ' id="img_noteTask_'.$this->userId
                        .'_'.$this->id.'" onClick = "openNote(\'noteTask_'.$this->userId.'_'.$this->id.'\');"');
                    $html .= '<div class = "modal" id = "noteTask_'.$this->userId.'_'.$this->id.'" >';
                    $html .= '<div class = "modal-content">';
                    $html .= '<span class = "close " onclick = "closeNotes();">&times;</span>';
                    $html .= '<a align = "left">'.$langs->trans('Note').' ('
                        .$this->ProjectTitle.', '.$this->description.")".'</a><br>';
                    $html .= '<textarea class = "flat colNote"  rows = "3" style = "width:350px;top:10px"';
                    $html .= ' name = "notesTask['.$this->userId.']['.$this->id.']" ';
                    $html .= '>'.$this->note.'</textarea>';
                    $html .= '</div></div>';
            }
           $htmlTitle .= '<td'.((count($headers) == 1)?' colspan = "2" ':'').'>'.$html."</td>\n";
        }
        return $htmlTitle;
    }
    /**
     * function to form a HTMLform line for this timesheet
     *
     *  @param    array(string)       $headers             Headers to display
     *  @param    int                 $start             indicated if the task is already started
     *  @return    string                                   HTML result containing the timesheet info
     */
    public function getAttendanceLine($headers, $start = false)
    {
        global $langs, $conf;
        $Class = 'oddeven '.(($this->listed)?
            'timesheet_whitelist':'timesheet_blacklist').' timesheet_line ';
        $html = '<tr class = "'.$Class.'" ';
        if (!empty($this->note))$html .= ' title = "'
            .htmlentities($this->note).'"';
        $html .= '>'."\n";
        //title section
        $html .= $this->getHTMLlineInfoCell($headers);
        $html .= $this->getHTMLlinePlayStop($start);
        $html .= "</tr>\n";
        return $html;
    }
        /*
     * function to get the start time of the attendance related to the tasktime
     *
     *  @param    int       userid              UNIX start time(-1 no start)
     *  @return     string                       html code to display the images(play/stop)
     * */
    public function getHTMLlinePlayStop($start)
    {
        $html = '<td>';
        $html .= '<img height = "32px" class = "playStopButton" id = "playStop_'
            .$this->id.'" src = "img/'.(($start == false)?'play-arrow':'stop-square');
        $html .= '.png" onClick = startStop(event,'
            .$this->userId.','.$this->id.') style = "cursor:pointer;">  ';
        //if ($start>0)$html .= dol_print_date($start, 'hour');
        $html .= '</td>';
        return $html;
    }
    /**
    * function to form a XML for this timesheet
    *
    *  @param    string               $startDate            year week like 2015W09
    *  @return     string                                         XML result containing the timesheet info
    *//*
    public function getXML($startDate)
    {
        $timetype = getConf('TIMESHEET_TIME_TYPE','hours');
        $dayshours = getConf('TIMESHEET_DAY_DURATION',8);
        $hidezeros = getConf('TIMESHEET_HIDE_ZEROS');
        $xml = "<task id = \"{$this->id}\" >";
        //title section
        $xml .= "<Tasks id = \"{$this->id}\">{$this->description} </Tasks>";
        $xml .= "<Project id = \"{$this->fk_project}\">{$this->ProjectTitle} </Project>";
        $xml .= "<TaskParent id = \"{$this->fk_task_parent}\">{$this->taskParentDesc} </TaskParent>";
        //$xml .= "<task id = \"{$this->id}\" name = \"{$this->description}\">\n";
        $xml .= "<startDate unix = \"$this->date_start\">";
        if ($this->date_start)
            $xml .= dol_mktime($this->date_start);
        $xml .= " </startDate>";
        $xml .= "<DateEnd unix = \"$this->date_end\">";
        if ($this->date_end)
            $xml .= dol_mktime($this->date_end);
        $xml .= " </DateEnd>";
        $xml .= "<Company id = \"{$this->companyId}\">{$this->companyame} </Company>";
        $xml .= "<TaskProgress id = \"{$this->companyId}\">";
        if ($this->planned_workload) {
            $xml .= $this->parseTaskTime($this->planned_workload).'('.floor($this->duration_effective/$this->planned_workload*100).'%)';
        } else {
            $xml .= "-:--(-%)";
        }
        $xml .= "</TaskProgress>";
        // day section
        //foreach ($this->weekWorkLoad as $dayOfWeek => $dayWorkLoadSec)
        for($dayOfWeek = 0;$dayOfWeek<7;$dayOfWeek++)
        {
                $today = strtotime($startDate.' +'.($dayOfWeek).' day  ');
                # to avoid editing if the task is closed
                $dayWorkLoadSec = isset($this->tasklist[$dayOfWeek])?$this->tasklist[$dayOfWeek]['duration']:0;
                # to avoid editing if the task is closed
                if ($hidezeros == 1 && $dayWorkLoadSec == 0) {
                    $dayWorkLoad = ' ';
                } elseif ($timetype == "days") {
                    $dayWorkLoad = $dayWorkLoadSec/3600/$dayshours;
                } else {
                    $dayWorkLoad = date('H:i', mktime(0, 0, $dayWorkLoadSec));
                }
                $open = '0';
                if ((empty($this->date_start) || ($this->date_start <= $today +86399)) && (empty($this->date_end) ||($this->date_end >= $today))) {
                    $open = '1';
                }
                $xml .= "<day col = \"{$dayOfWeek}\" open = \"{$open}\">{$dayWorkLoad}</day>";
        }
        $xml .= "</task>";
        return $xml;
        //return utf8_encode($xml);
    }
    */
    /**
    * function to save a time sheet as a string
    * @param    int     $mode   0 => serialize, 1 => json_encode, 2 => json_encode PRETTY PRINT
    * @return   string       serialized object
    */
    public function serialize($mode = 0)
    {
        $arRet = array();
        $arRet['id'] = $this->id;//task id
        $arRet['listed'] = $this->listed;//task id
        $arRet['description'] = $this->description;//task id
        $arRet['appId'] = $this->appId;// Task_time_approval id
        $arRet['tasklist'] = $this->tasklist;
        $arRet['userId'] = $this->userId;// user id booking the time
        $arRet['note'] = $this->note;
        $arRet['fk_project'] = $this->fk_project ;
        $arRet['ProjectTitle'] = $this->ProjectTitle;
        $arRet['date_start'] = $this->date_start;
        $arRet['date_end'] = $this->date_end        ;
        $arRet['date_start_approval'] = $this->date_start_approval;
        $arRet['date_end_approval'] = $this->date_end_approval        ;
        $arRet['duration_effective'] = $this->duration_effective ;
        $arRet['planned_workload'] = $this->planned_workload ;
        $arRet['fk_task_parent'] = $this->fk_task_parent ;
        $arRet['taskParentDesc'] = $this->taskParentDesc ;
        $arRet['companyName'] = $this->companyName  ;
        $arRet['companyId'] = $this->companyId;
        $arRet['pStatus'] = $this->pStatus;
        $arRet['status'] = $this->status;
        $arRet['recipient'] = $this->recipient;
        $arRet['sender'] = $this->sender;
        $arRet['task_timesheet'] = $this->task_timesheet;
        $arRet['progress'] = $this->progress;
        $arRet['isOpen'] = $this->isOpen;
        $arRet['timespent_note'] = $this->timespent_note;
        
        switch($mode) {
            default:
            case 0:
                $ret = serialize($arRet);
                break;
            case 1:
                $ret = json_encode($arRet);
                break;
            case 2:
                $ret = json_encode($arRet, JSON_PRETTY_PRINT);
                break;

        }
        return $ret;
    }
    
  /** function to load a skeleton as a string
     * @param   string    $str   serialized object
     * @param    int     $mode   0 => serialize, 1 => json_encode, 2 => json_encode PRETTY PRINT
     * @return  int              OK
     */
    public function unserialize($str, $mode = 0)
    {
        $ret = '';
        if (empty($str))return -1;
        $array = array();
        switch($mode) {
            default:
            case 0:
                $array = unserialize($str);
                break;
            case 1:
            case 2:
                $array = json_decode($str, JSON_OBJECT_AS_ARRAY);
                break;
 /*           case 3:
                $array = $str;
                break;*/
        }
        // automatic unserialisation based on match between property name and key value
        foreach ($array as $key => $value) {
            if (property_exists($this, $key)) {
                $this->{$key} = $value;
            }
        }
    }
    /** return the task list
     *
     * @return int[]
     */
    public function getTaskTab()
    {
        return $this->tasklist;
    }
    /**
     * Function to update the time spent(total) on the task level
     *
     * @return  int              OK/KO
     */
    public function updateTimeUsed()
    {
        $this->db->begin();
        $error = 0;
        $sql = "UPDATE ".MAIN_DB_PREFIX."projet_task AS pt ";
        $sql .= "SET duration_effective = (SELECT SUM(ptt.task_duration) ";
        $sql .= "FROM ".MAIN_DB_PREFIX."projet_task_time AS ptt ";
        $sql .= "WHERE ptt.fk_task = '".$this->id."') ";
        if (isset($this->progress) && $this->progress != '') $sql .= " , progress = '".$this->progress."'";
        $sql .= " WHERE pt.rowid = '".$this->id."' ";
        dol_syslog(__METHOD__, LOG_DEBUG);
        $resql = $this->db->query($sql);
        if ($resql) {
                // return 1;
        } else {
            $this->error = "Error ".$this->db->lasterror();
            dol_syslog(__METHOD__.$this->error, LOG_ERR);
            $error++;
        }
        // Commit or rollback
        if ($error) {
            foreach ($this->errors as $errmsg) {
                dol_syslog(__METHOD__.$errmsg, LOG_ERR);
                $this->error .= ($this->error?', '.$errmsg:$errmsg);
            }
            $this->db->rollback();
            return -1*$error;
        } else {
            $this->db->commit();
            return $this->id;
        }
    }
    /**
     * functio to format the time(in seconds) in like "00:00"
     *
     * @param   int     $taskTime   seconds
     * @return  string              in a format "00:00"
     */
    public function parseTaskTime($taskTime)
    {
        $ret = floor($taskTime/3600).":".str_pad(floor($taskTime%3600/60), 2, "0", STR_PAD_LEFT);
        return $ret;
        //return '00:00';
    }
    /*
     * change the status of an approval
     *
     *  @param      object|int        $user         user object or user id doing the modif
     *  @param      int               $id           id of the timesheetuser
     *  @param      bool              $updateTS      update the timesheet if true
     *  @return     int                         <0 if KO, Id of created object if OK
     */
    Public function setStatus($user, $status, $updateTS = true)
    {
        $error = 0;
        $ret = 0;
        //if the satus is not an ENUM status

        if ($this->status<0 || $this->status> STATUSMAX) {
            dol_syslog(__METHOD__
                ." this status '{$status}' is not part or the possible list", LOG_ERR);
            return false;
        }
        // Check parameters
        $this->status = $status;
        //if ($this->appId && $this->date_start_approval == 0)$this->fetch($this->appId);
        if ($this->getDuration()>0 || $this->note!='') {
            if ($this->appId >0) {
                $ret = $this->update($user);
            } else{
                $ret = $this->create($user);
            }
        } elseif ($this->appId >0) {
                $ret = $this->delete($user);
        }
        if ($ret>0 && $updateTS == true) {// success of the update, then update the timesheet user if possible
            $staticTS = new TimesheetUserTasks($this->db);
            $staticTS->fetch($this->task_timesheet);
            $ret = $staticTS->updateStatus($user, $status);
        }
        return $ret;
    }
    /**
     * get total task list duration
     *

     *  @return     int                        duration in seconds
     */
    Public function getDuration()
    {
        $ttaDuration = 0;
        if (!is_array($this->tasklist))$this->getActuals();
        foreach ($this->tasklist as $item) {
            $ttaDuration += $item['duration'];
        }
        return $ttaDuration;
    }
    /* function to post on task_time
    *
    *  @param    array               $timesheetPost       $_POST part matching the task to update
    *  @param    int               $userid                    user id sending timespent
    *  @param    object              $Submitter             user who submit
    *  @param    array(int)               $tasktimeid          the id of the tasktime if any
    *  @param     int               $token          timesheetweek
    *  @param     string              $note        notes
    *  @param     string              $progress        stated progress
    *  @return     int                                                       1 => succes, 0 => Failure
    */
    public function postTaskTimeActual($timesheetPost, $userId, $Submitter, $token, $note = '', $progress = '')
    {
        global $conf, $user;
        $ret = 0;
        $noteUpdate = 0;
        
        dol_syslog(__METHOD__." taskTimeId=".$this->id, LOG_DEBUG);
        $this->timespent_fk_user = $userId;
        if ($note != $this->note) {
            $this->note = $note;
            $noteUpdate = true;
        }
        $progressUpdate = 0;
        if (!empty($progress) && $progress != $this->progress) {
            $this->progress = $progress;
            $progressUpdate = 1;
        }
        $_SESSION['timesheet'][$token]['timeSpendModified'] = 0;
        $_SESSION['timesheet'][$token]['updateError'] = 0;
        $_SESSION['timesheet'][$token]['timeSpendDeleted'] = 0;
        $_SESSION['timesheet'][$token]['timeSpendCreated'] = 0;
        $_SESSION['timesheet'][$token]['ProgressUpdate'] = 0;
        if (is_array($timesheetPost))
            foreach ($timesheetPost as $dayKey => $dayData) {
                $wkload = $dayData[0];
                $daynote = array_key_exists(1,$dayData )?$dayData[1]:'';
                $duration = 0;
                if (getConf('TIMESHEET_TIME_TYPE', 'hours') == "days") {
                    $duration = (float) $wkload * getConf('TIMESHEET_DAY_DURATION', 8) * 3600;
                } else {
                    $durationTab = date_parse($wkload);
                    $duration = $durationTab['minute'] * 60 + $durationTab['hour'] * 3600;
                }
                $lineresult = $this->saveTaskTime($Submitter, $duration, $daynote, $dayKey);
                $_SESSION['timesheet'][$token]['timeSpendModified'] += array_key_exists('timeSpendModified', $lineresult) ? $lineresult['timeSpendModified'] : 0;
                $_SESSION['timesheet'][$token]['updateError'] += array_key_exists('updateError', $lineresult) ? $lineresult['updateError'] : 0;
                $_SESSION['timesheet'][$token]['timeSpendDeleted'] += array_key_exists('timeSpendDeleted', $lineresult) ? $lineresult['timeSpendDeleted'] : 0;
                $_SESSION['timesheet'][$token]['timeSpendCreated'] += array_key_exists('timeSpendCreated', $lineresult) ? $lineresult['timeSpendCreated'] : 0;
            }
        $nbUpdate = ($_SESSION['timesheet'][$token]['timeSpendModified'] 
            + $_SESSION['timesheet'][$token]['timeSpendDeleted'] 
            + $_SESSION['timesheet'][$token]['timeSpendCreated']) ;
        # update the time used on the task level
        if (($nbUpdate + $progressUpdate) > 0) {
            $this->updateTimeUsed();
            if ($progressUpdate)$_SESSION['timesheet'][$token]['ProgressUpdate']++;
        }
        
        
        if ( $noteUpdate ) {
            $retNote = ($this->appId>0)?$this->update($user):$this->create($user);
            if ($retNote  ) {
                $_SESSION['timesheet'][$token]['NoteUpdated']++;
                
            }else{
                $_SESSION['timesheet'][$token]['updateError']++;
            }
        }

        $ret = 0;
        if (is_array($_SESSION['timesheet'][$token]))
            $ret = $_SESSION['timesheet'][$token]['ProgressUpdate'] 
            + $nbUpdate -$_SESSION['timesheet'][$token]['updateError'];
        return $ret;
        //return $idList;
    }
    /** save the tasktime in the database
     *
     * @param object    $Submitter to save the submitter in the database
     * @param int       $duration duration in sec
     * @param string $daynote note to save
     * @param int $dayKey  day to update(start at 0 based on the get querry)
     * @param type $addmode to add the note and duration to existing one instead of replacing them
     * @return array eventmesage array
     */
    public function saveTaskTime($Submitter, $duration, $daynote, $dayKey, $addmode = false)
    {
        $item = $this->tasklist[$dayKey];
        $resArray = ['timeSpendDeleted'=>0, 'timeSpendModified' => 0, 'timeSpendCreated'=>0, 'updateError'=> 0, ];
        $daynote_old = array_key_exists('note', $item) ? $item['note']:'';

        $is_today=date("Y-m-d") == date("Y-m-d",$item['date']);
        $this->timespent_fk_user = $this->userId;
        dol_syslog(__METHOD__."   duration Old=".$item['duration']." New="
            .$duration." Id=".$item['id'].", date=".$item['date'].",".$is_today, LOG_DEBUG);
        $this->timespent_date = $item['date'];
        if (property_exists($this, 'timespent_datehour')) {
            $this->timespent_withhour = '1';
            if ($is_today) {
                // use current time is saved same day
                $this->timespent_datehour = (time() - $this->timespent_duration);
                dol_syslog("TRACE".$this->timespent_datehour, LOG_DEBUG);
            } else {
                $this->timespent_datehour = $item['date'];
            }
        }
        if ($item['id']>0) {
            $this->timespent_id = $item['id'];
            $this->timespent_old_duration = $item['duration']; 
            if ($addmode) {
                if (!empty($daynote)){
                    $this->timespent_note .= "\n".$daynote;
                }
                $this->timespent_duration = $duration+$this->timespent_old_duration;
            } else{
                $this->timespent_note = $daynote;
                $this->timespent_duration = $duration;
            }


            if (($this->timespent_duration >0 && $this->timespent_old_duration!=$this->timespent_duration )|| $daynote_old!=$daynote && (!empty($daynote) || $this->timespent_duration >0)) {
                    dol_syslog(__METHOD__."  taskTimeUpdate", LOG_DEBUG);
                    if ($this->updateTimeSpent($Submitter, 0) >= 0) {
                        $resArray['timeSpendModified']++;
                    }else {
                        $resArray['updateError']++;
                    }
                
            } else if($this->timespent_duration == 0 && empty($daynote) ) {

                    dol_syslog(__METHOD__."  taskTimeDelete", LOG_DEBUG);
                    if ($this->delTimeSpent($Submitter, 0) >= 0) {
                        $resArray['timeSpendDeleted']++;
                        $this->tasklist[$dayKey]['id'] = 0;
                    } else {
                        $resArray['updateError']++;
                    }
            }
        } elseif ($duration>0 || $daynote!='') {
            $this->timespent_note = $daynote;
            $this->timespent_duration = $duration;

            if (property_exists($this, 'timespent_datehour') && $duration>0 ) {
                $this->timespent_withhour = '1';
                if ($is_today) {
                    // use current time is saved same day
                    $this->timespent_datehour = (time() - $this->timespent_duration);
                } else {
                    $this->timespent_datehour = $item['date'];
                }
            }
            $newId = $this->addTimeSpent($Submitter, 0);
            if ($newId >= 0) {

                $resArray['timeSpendCreated']++;
                $this->tasklist[$dayKey]['id'] = $newId;
            } else {
                $resArray['updateError']++;
            }
        }
        //update the task list
        if(!array_key_exists('duration', $this->tasklist[$dayKey]) || $this->tasklist[$dayKey]['duration'] != $this->timespent_duration){
            $this->tasklist[$dayKey]['duration'] = $this->timespent_duration;
        }
        if(!array_key_exists('note', $this->tasklist[$dayKey]) || $this->tasklist[$dayKey]['note'] != $this->timespent_note){
            $this->tasklist[$dayKey]['note'] = $this->timespent_note;
        }
        
        return $resArray;
    }
    /**
         *        function that will send email to
         *
         *        @return        void
         */
    /*
    public function sendApprovalReminders()
{
            global $langs;
            $sql = 'SELECT';
            $sql .= ' COUNT(t.rowid) as nb, ';
            $sql .= ' u.email, ';
            $sql .= ' u.fk_user as approverid';
            $sql .= ' FROM '.MAIN_DB_PREFIX.'project_task_time_approval as t';
            $sql .= ' JOIN '.MAIN_DB_PREFIX.'user as u on t.fk_userid = u.rowid ';
            $sql .= ' WHERE (t.status = "SUBMITTED" OR t.status = "UNDERAPPROVAL" OR t.status = "CHALLENGED")  AND t.recipient = "team"';
            $sql .= ' GROUP BY u.fk_user';
             dol_syslog(__METHOD__, LOG_DEBUG);
            $resql = $this->db->query($sql);
            if ($resql) {
                $num = $this->db->num_rows($resql);
                for($i = 0;$i<$num;$i++)
                {
                    $obj = $this->db->fetch_object($resql);
                    if ($obj) {
                        $message = str_replace("__NB_TS__", $obj->nb, str_replace('\n', "\n", $langs->transnoentities('YouHaveApprovalPendingMsg')));
                        //$message = "Bonjour, \n\nVous avez __NB_TS__ feuilles de temps à approuver, veuillez vous connecter à Dolibarr pour les approuver.\n\nCordialement.\n\nVotre administrateur Dolibarr.";
                        $sendto = $obj->email;
                        $replyto = $obj->email;
                        $subject = $langs->transnoentities("YouHaveApprovalPending");
                        if (!empty($sendto) && $sendto!="NULL") {
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
            } else {
                $error++;
                dol_print_error($db);
                $list = array();
            }
        }
    */
    /*
     * pget the next approval in the chaine
     *
     *  @param      object/int        $user         user object or user id doing the modif
     *  @param      string            $role         role who challenge
     *  @param      bool              $updteTS      update the timesheet if true
     *  @return     int                         <0 if KO, Id of created object if OK
     */
    Public function approved($user, $role, $updteTS = true)
    {
        global $apflows;
        $userId = is_object($user)?$user->id:$user;
        if ($role<0&& $role>ROLEMAX) return -1;// role not valide
        $nextStatus = 0;
        $ret = -1;
        //set the approver
        $this->user_app[$role] = $userId;
        //update the roles
        $rolepassed = false;
        // look for the role open after the curent role and set it as recipient
        foreach (array_slice($apflows, 1) as $key => $value) {
            $key++;
            if ($value == 1) {
                if ($key == $role) {
                    $this->sender = $key;
                    $rolepassed = true;
                } elseif ($rolepassed) {
                    $this->recipient = $key;
                    $ret = $key;
                    break;
                }
            }
        }
        if ($ret>0) {//other approval found
            $nextStatus = UNDERAPPROVAL;
            $ret = $this->setStatus($user, UNDERAPPROVAL, $updteTS);
        } elseif ($this->sender == $role) { // only if the role was alloed
             $this->recipient = USER;
             $nextStatus = APPROVED;
            // if approved, recipient
            //$this->recipient = self::$roleList[array_search('1', self::$apflows)];
        }
        $ret = $this->setStatus($user, $nextStatus, $updteTS);
        // save the change in the db
        if ($ret>0)$ret = $this->updateTaskTime($nextStatus);
        return $ret;
    }
    /*
     * challenge the tsak time approval
     *
     *  @param      object/int        $user         user object or user id doing the modif
     *  @param      string            $role         role who challenge
     *  @param      bool              $updteTS      update the timesheet if true
     *  @return     int                                <0 if KO, Id of created object if OK
     */
    Public function challenged($user, $role, $updteTS = true)
    {
        global $apflows;
        $userId = is_object($user)?$user->id:$user;
        $nextStatus = 0;
        if ($role<0&& $role>ROLEMAX) return -1;// role not valide
        $ret = -1;
       //unset the approver(could be set previsouly)
        //update the roles, look for the open role and define it as sender and save the previous role open as recipient
        foreach (array_slice($apflows, 1) as $key => $recipient) {
            $key++;
            if ($recipient == 1) {
                if ($key == $role) {
                        $this->sender = $role;
                    break;
                } else {
                    $this->recipient = $key;
                    $ret = $key;
                }
            }
        }
        if ($ret>0) {//other approval found
            $nextStatus = CHALLENGED;
        } elseif ($this->sender == $role) { //update only if the role was allowed
            $this->recipient = USER;
            $nextStatus = REJECTED;
        }
        $ret = $this->setStatus($user, $nextStatus, $updteTS);
        if ($ret>0)$ret = $this->updateTaskTime($nextStatus);
        return $ret;// team key is 0
    }
    /*
     * submit the TS
     *
     *  @param      bool   $updteTS      update the timesheet if true
     *  @return     int                         <0 if KO, Id of created object if OK
     */
    Public function submitted($user, $updteTS = false)
    {
        global $apflows;
        // assign the first role open as recipient, put user as default
        $this->recipient = USER;
        foreach (array_slice($apflows, 1) as $key => $recipient) {
            $key++;
            if ($recipient == 1) {
                $this->recipient = $key;
                break;
            }
        }
        //Update the the fk_tta in the project task time
        $ret = $this->setStatus($user, SUBMITTED, $updteTS);// must be executed first to get the appid
        if ($ret>0)$ret = $this->updateTaskTime(SUBMITTED);
        return $ret+1;// team key is 0
    }

    /***
     * 
     */
    public function getSumDay(){
        $ttaDuration = array();
        if (!is_array($this->tasklist))$this->getActuals();
        foreach ($this->tasklist as $day => $item) {
            $ttaDuration[$day] = $item['duration']
                + array_sum(array_column($item['other'], 'duration'));;
        }
        return $ttaDuration;

    }
}
//TimesheetTask::init();
