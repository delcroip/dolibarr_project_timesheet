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
//require_once('mysql.class.php');
require_once DOL_DOCUMENT_ROOT."/core/class/commonobject.class.php";
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
require_once 'TimesheetHoliday.class.php';
require_once 'TimesheetPublicHoliday.class.php';
require_once 'TimesheetTask.class.php';
require_once 'TimesheetFavourite.class.php';

require_once 'core/lib/generic.lib.php';
require_once 'core/lib/timesheet.lib.php';



class TimesheetUserTasks extends CommonObject
{
    //common
    public $db;                                                        //!< To store db handler
    public $error;                                                        //!< To return error code(or message)
    public $errors = array();                                //!< To return several error codes(or messages)
    public $element = 'timesheetuser';                        //!< Id that identify managed objects
    public $table_element = 'project_task_timesheet';                //!< Name of table without prefix where object is stored
// from db
    public $id;
    public $userId;
    public $date_start = '';
    public $date_end;
    public $status;
    public $note;
//basic DB logging
    public $date_creation = '';
    public $date_modification = '';
    public $user_creation;
    public $user_modification;
//working variable
    public $duration;
    public $ref;
    public $user;
    public $holidays;
    public $publicHolidays;
    public $taskTimesheet;
    public $headers;
    public $weekDays;
    public $token;
    public $whitelistmode;
    public $userName;
    /**
     *   Constructor
     *
     * @param        DoliDB                $db      Database handler
     * @param   int             $userId if of the user
     * @return null
     */
    public function __construct($db, $userId = 0)
    {
        global $user, $conf;
        $this->db = $db;
        $this->user = $user;
        $this->userId = ($userId == 0)?(is_object($user)?$user->id:$user):$userId;
        $this->headers = explode('||', getConf('TIMESHEET_HEADERS',''));
        $this->getUserName();
    }
 /******************************************************************************
 *
 * DB methods
 *
 ******************************************************************************/
    /**
     *  cREATE object into database
     *
     *  @param        User        $user        User that modifies
     *  @param  int                $notrigger         0 = launch triggers after, 1 = disable triggers
     *  @return int                         <0 if KO, >0 if OK
     */
    public function create($user, $notrigger = 0)
    {
        global $conf, $langs;
        $error = 0;
        // Clean parameters
        if (isset($this->userId)) $this->userId = trim($this->userId);
        if (isset($this->date_start)) $this->date_start = trim($this->date_start);
        if (isset($this->date_end)) $this->date_end = trim($this->date_end);
        if (isset($this->status)) $this->status = trim($this->status);
        if (isset($this->date_creation)) $this->date_creation = trim($this->date_creation);
        if (isset($this->date_modification)) $this->date_modification = trim($this->date_modification);
        if (isset($this->user_modification)) $this->user_modification = trim($this->user_modification);
        if (isset($this->note)) $this->note = trim($this->note);
        $userId = (is_object($user)?$user->id:$user);
        // Check parameters
        // Put here code to add control on parameters values
        // Insert request
        $sql = "INSERT INTO ".MAIN_DB_PREFIX.$this->table_element."(";

        $sql .= 'fk_userid, ';
        $sql .= 'date_start, ';
        $sql .= 'date_end, ';
        $sql .= 'status, ';
        $sql .= 'date_creation, ';
        $sql .= 'date_modification, ';
        $sql .= 'fk_user_modification, ';
        $sql .= 'note';

        $sql .= ") VALUES(";
        $sql .= ' '.(! isset($this->userId)?'NULL':'\''.$this->userId.'\'').', ';
        $sql .= ' '.(! isset($this->date_start) || dol_strlen($this->date_start) == 0?'NULL':'\''.$this->db->idate($this->date_start).'\'').', ';
        $sql .= ' '.(! isset($this->date_end) || dol_strlen($this->date_end) == 0?'NULL':'\''.$this->db->idate($this->date_end).'\'').', ';
        $sql .= ' '.(! isset($this->status)?DRAFT:$this->status).', ';
        $sql .= ' NOW(), ';
        $sql .= ' NOW(), ';
        $sql .= ' \''.$userId.'\', ';
        $sql .= ' '.(! isset($this->note)?'NULL':'\''.$this->db->escape(dol_html_entity_decode($this->note, ENT_QUOTES)).'\'');
        $sql .= ")";
        $this->db->begin();
        dol_syslog(__METHOD__, LOG_DEBUG);
        $resql = $this->db->query($sql);
        if (! $resql) {
            $error++;$this->errors[] = "Error ".$this->db->lasterror();
        }
        if (! $error) {
            $this->id = $this->db->last_insert_id(MAIN_DB_PREFIX.$this->table_element);
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
            return $this->id;
        }
    }
    /**
     *  Load object in memory from the database
     *
     *  @param        int                $id        Id object
     *  @param        string        $ref        Ref
     *  @return int           <0 if KO, >0 if OK
     */
    public function fetch($id, $ref = '')
    {
        global $langs;
        $sql = "SELECT";
        $sql .= " t.rowid, ";
        $sql .= ' t.fk_userid, ';
        $sql .= ' t.date_start, ';
        $sql .= ' t.date_end, ';
        $sql .= ' t.status, ';
        $sql .= ' t.date_creation, ';
        $sql .= ' t.date_modification, ';
        $sql .= ' t.fk_user_modification, ';
        $sql .= ' t.note';
        $sql .= " FROM ".MAIN_DB_PREFIX.$this->table_element." as t";
        if ($ref) $sql .= " WHERE t.ref = '".$ref."'";
        else $sql .= " WHERE t.rowid = ".$id;
        dol_syslog(__METHOD__, LOG_DEBUG);
        $resql = $this->db->query($sql);
        if ($resql) {
            if ($this->db->num_rows($resql)) {
                $obj = $this->db->fetch_object($resql);
                $this->id = $obj->rowid;
                $this->userId = $obj->fk_userid;
                $this->date_start = $this->db->jdate($obj->date_start);
                $this->date_end = $this->db->jdate($obj->date_end);
                $this->status = $obj->status;
                $this->date_creation = $this->db->jdate($obj->date_creation);
                $this->date_modification = $this->db->jdate($obj->date_modification);
                $this->user_modification = $obj->fk_user_modification;
                $this->note = $obj->note;
            }
            $this->db->free($resql);
            $this->ref = $this->date_start.'_'.$this->userId;
            return 1;
        } else {
            $this->error = "Error ".$this->db->lasterror();
            return -1;
        }
    }
    /**
     *  Load object in memory from the database
     *
     *  @return int           <0 if KO, >0 if OK
     */
    public function fetchByWeek()
    {
        global $langs;
        $sql = "SELECT";
        $sql .= " t.rowid, ";
        $sql .= ' t.fk_userid, ';
        $sql .= ' t.date_start, ';
        $sql .= ' t.date_end, ';
        $sql .= ' t.status, ';
        $sql .= ' t.date_creation, ';
        $sql .= ' t.date_modification, ';
        $sql .= ' t.fk_user_modification, ';
        $sql .= ' t.note';
        $sql .= " FROM ".MAIN_DB_PREFIX.$this->table_element." as t";
        $sql .= " WHERE t.date_start = '".$this->db->idate($this->date_start)."'";
        $sql .= " AND t.fk_userid = '".$this->userId."'";


        //$sql .= " AND t.rowid = ".$id;
        dol_syslog(__METHOD__, LOG_DEBUG);
        $resql = $this->db->query($sql);
        if ($resql) {
            if ($this->db->num_rows($resql)) {
                $obj = $this->db->fetch_object($resql);
                $this->id = $obj->rowid;
                $this->userId = $obj->fk_userid;
                $this->date_start = $this->db->jdate($obj->date_start);
                $this->date_end = $this->db->jdate($obj->date_end);
                $this->status = $obj->status;
                $this->date_creation = $this->db->jdate($obj->date_creation);
                $this->date_modification = $this->db->jdate($obj->date_modification);
                $this->user_modification = $obj->fk_user_modification;
                $this->note = $obj->note;
            } else{
                unset($this->status) ;
                unset($this->date_modification);
                unset($this->user_modification);
                unset($this->note);
                unset($this->date_creation);
                //$this->date_end = getEndWeek($this->date_start);
                $this->create($this->user);
                $this->fetch($this->id);
            }
            $this->db->free($resql);
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
    public function update($user, $notrigger = 0)
    {
        global $conf, $langs;
                $error = 0;
                // Clean parameters
                if (isset($this->userId)) $this->userId = trim($this->userId);
                if (isset($this->date_start)) $this->date_start = trim($this->date_start);
                if (isset($this->date_end)) $this->date_end = trim($this->date_end);
                if (isset($this->status)) $this->status = trim($this->status);
                if (isset($this->date_creation)) $this->date_creation = trim($this->date_creation);
                if (isset($this->date_modification)) $this->date_modification = trim($this->date_modification);
                if (isset($this->user_modification)) $this->user_modification = trim($this->user_modification);
                if (isset($this->note)) $this->note = trim($this->note);
                $userId = (is_object($user)?$user->id:$user);
                // Check parameters
                // Put here code to add a control on parameters values
        // Update request
        $sql = "UPDATE ".MAIN_DB_PREFIX.$this->table_element." SET";
                $sql .= ' fk_userid='.(empty($this->userId) ? 'null':'\''.$this->userId.'\'').', ';
                $sql .= ' date_start='.(dol_strlen($this->date_start)!=0 ? '\''.$this->db->idate($this->date_start).'\'':'null').', ';
                $sql .= ' date_end='.(dol_strlen($this->date_end)!=0 ? '\''.$this->db->idate($this->date_end).'\'':'null').', ';
                $sql .= ' status='.(empty($this->status)? DRAFT:$this->status).', ';
                $sql .= ' date_modification = NOW(), ';
                $sql .= ' fk_user_modification = \''.$userId.'\', ';
                $sql .= ' note = \''.$this->db->escape(dol_html_entity_decode($this->note, ENT_QUOTES)).'\'';
        $sql .= " WHERE rowid=".$this->id;
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
     *
     *  @param  int                $notrigger         0 = launch triggers after, 1 = disable triggers
     *  @return        int                                         <0 if KO, >0 if OK
     */
    public function delete($notrigger = 0)
    {
            global $conf, $langs;
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
                $sql .= " WHERE rowid=".$this->id;
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
    /**
     *        Load an object from its id and create a new one in database
     *
     *        @param        int                $fromid     Id of object to clone
     *        @return        int                                        New id of clone
     */
    public function createFromClone($fromid)
    {
        global $user, $langs;
        $error = 0;
        $object = new Timesheetuser($this->db);
        $this->db->begin();
        // Load source object
        $object->fetch($fromid);
        $object->id = 0;
        $object->statut = 0;
        // Clear fields
        // ...
        // Create clone
        $result = $object->create();
        // Other options
        if ($result < 0) {
                $this->error = $object->error;
                $error++;
        }
        if (! $error) {
        }
        // End
        if (! $error) {
                $this->db->commit();
                return $object->id;
        } else {
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
    *  @param    string               $startDate            start date in php format
    *  @return     string                                       result
    */
    public function fetchAll($startdate, $whitelistmode = false)
    {
        global $conf;
        $this->whitelistmode = (is_numeric($whitelistmode)&& !empty($whitelistmode))?$whitelistmode:getConf('TIMESHEET_WHITELIST_MODE');
        $this->date_start = getStartDate($startdate);
        $this->ref = $this->date_start.'_'.$this->userId;
        $this->date_end = getEndDate($this->date_start);
        $this->token = getToken();
        $this->fetchByWeek();
        $this->fetchTaskTimesheet();
        //$ret += $this->getTaskTimeIds();
        //FIXME module holiday should be activated ?
        $this->fetchUserHolidays();
        $this->fetchUserPublicHolidays();
        $this->saveInSession();
    }
    /* Funciton to fect the holiday of a single user for a single week.
    *  @return     string                                       result
    */
    public function fetchUserHolidays()
    {
       $this->holidays = new TimesheetHoliday($this->db);
       $ret = $this->holidays->fetchUserWeek($this->userId, $this->date_start, $this->date_end);
       return $ret;
    }

        /* Funciton to fect the public holiday of a single user for a single week.
    *  @return     string                                       result
    */
    public function fetchUserPublicHolidays()
    {
       $this->publicHolidays = new TimesheetPublicHolidays($this->db);
       $ret = $this->publicHolidays->fetchUserWeek($this->userId, $this->date_start, $this->date_end);
       return $ret;
    }

    /** function to load from session [//FIXME: to be removed not REST appraoch]
     *
     * @param string $token ref to load
     * @param int $id   object id
     * @return null
     */
   public function loadFromSession($token, $id)
   {
       $this->fetch($id);
       $this->token = $token;
       $this->userId = $_SESSION['timesheet'][$token][$id]['userId'];
       $this->date_start = $_SESSION['timesheet'][$token][$id]['startDate'];
       $this->date_end = $_SESSION['timesheet'][$token][$id]['dateEnd'];
       $this->ref = $_SESSION['timesheet'][$token][$id]['ref'];
       $this->note = $_SESSION['timesheet'][$token][$id]['note'];
       $this->holidays = unserialize($_SESSION['timesheet'][$token][$id]['holiday']);
       $this->publicHolidays = unserialize($_SESSION['timesheet'][$token][$id]['publicHolidays']);
       $this->taskTimesheet = unserialize($_SESSION['timesheet'][$token][$id]['taskTimesheet']);;
   }
/*
 * function to load the parma from the session
 * @return void
 */
public function saveInSession()
{
    $_SESSION['timesheet'][$this->token][$this->id]['userId'] = $this->userId;
    $_SESSION['timesheet'][$this->token][$this->id]['ref'] = $this->ref;
    $_SESSION['timesheet'][$this->token][$this->id]['startDate'] = $this->date_start;
    $_SESSION['timesheet'][$this->token][$this->id]['dateEnd'] = $this->date_end;
    $_SESSION['timesheet'][$this->token][$this->id]['note'] = $this->note;
    $_SESSION['timesheet'][$this->token][$this->id]['holiday'] = serialize($this->holidays);
    $_SESSION['timesheet'][$this->token][$this->id]['publicHolidays'] = serialize($this->publicHolidays);
    $_SESSION['timesheet'][$this->token][$this->id]['taskTimesheet'] = serialize($this->taskTimesheet);
}
/*
 * function to genegate the timesheet tab
 *
 *  @param    int               $userid                   user id to fetch the timesheets
 *  @return     array(string)                             array of timesheet(serialized)
 */
public function fetchTaskTimesheet($userid = '')
{
    global $conf, $user, $langs;
    $res = array();
    if ($userid == '') {
        $userid = $this->userId;
    }

    $whiteList = array();
    
    $datestart = $this->date_start;
    $datestop = $this->date_end;
    $staticWhiteList = new TimesheetFavourite($this->db);
    $whiteList = $staticWhiteList->fetchUserList($userid, $datestart, $datestop);
     // Save the param in the SeSSION
    $tasksList = array();
    $sql = 'SELECT DISTINCT tsk.rowid as taskid, prj.fk_soc, tsk.fk_projet, tsk.progress, ctc.element as ectype, ';
    $sql .= 'tsk.fk_task_parent, tsk.rowid, app.rowid as appid, prj.ref as prjRef, tsk.ref as tskRef, prj.fk_statut as p_status';
    //$sql .= '(CASE   WHEN ctc.element=\'project_task\' THEN 1 else 2 END) as prio';
    $sql .= " FROM ".MAIN_DB_PREFIX."projet_task as tsk";
    //$sql .= " FROM ".MAIN_DB_PREFIX."element_contact as ec";
    $sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'element_contact as ec ON tsk.rowid = ec.element_id and ec.fk_socpeople = '.$userid;
    $sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_type_contact as ctc ON(ctc.rowid = ec.fk_c_type_contact  AND ctc.active = \'1\') ';
    $sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'projet_task_time as tskt ON tsk.rowid = tskt.fk_task and tskt.fk_user = '.$userid;
    $sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'projet as prj ON prj.rowid = tsk.fk_projet ' ;


    //approval
    if ($this->status == DRAFT || $this->status == REJECTED) {
        $sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'project_task_time_approval as app ';
    } else{ // take only the ones with a task_time linked
        $sql .= 'JOIN '.MAIN_DB_PREFIX.'project_task_time_approval as app ';
    }
    $sql .= ' ON tsk.rowid = app.fk_projet_task AND app.fk_userid = fk_socpeople';
    $sql .= ' AND app.date_start = \''.$this->db->idate($datestart).'\'';
    $sql .= ' AND app.date_end = \''.$this->db->idate($datestop).'\'';
    //end approval
    $sql .= " WHERE ((ec.fk_socpeople = '".$userid."' AND ctc.element = 'project_task') ";

    // SHOW TASK ON PUBLIC PROEJCT
    if (getConf('TIMESHEET_ALLOW_PUBLIC') == '1') {
        $sql .= '  OR  prj.public =  \'1\'';
    }
    $sql .= '  OR  tskt.task_duration >  0';
    $sql .= ' )';
    
    if (getConf('TIMESHEET_HIDE_DRAFT') == '1') {
        $sql .= ' AND prj.fk_statut !=  \'0\'';
    }
    $sql .= ' AND (prj.datee >= \''.$this->db->idate($datestart).'\' OR prj.datee IS NULL)';
    $sql .= ' AND (prj.dateo <= \''.$this->db->idate($datestop).'\' OR prj.dateo IS NULL)';
    $sql .= ' AND (tsk.datee >= \''.$this->db->idate($datestart).'\' OR tsk.datee IS NULL)';
    $sql .= ' AND (tsk.dateo <= \''.$this->db->idate($datestop).'\' OR tsk.dateo IS NULL)';
    // show task only of people on the same project (not used for team leader)
    if ( !$user->admin && $userid != $user->id && !in_array($userid, $user->getAllChildIds())){
        $sql .= " AND ((tsk.rowid in (SELECT element_id FROM ".MAIN_DB_PREFIX."element_contact as ec LEFT JOIN ".MAIN_DB_PREFIX."c_type_contact as ctc ON(ctc.rowid = ec.fk_c_type_contact AND ctc.active = '1')";
        $sql .= " WHERE  ctc.element = 'project_task' AND element_id = tsk.rowid ))";
        $sql .= " OR (prj.rowid in (SELECT element_id FROM ".MAIN_DB_PREFIX."element_contact as ec LEFT JOIN ".MAIN_DB_PREFIX."c_type_contact as ctc ON(ctc.rowid = ec.fk_c_type_contact AND ctc.active = '1')";
        $sql .= " WHERE ec.fk_socpeople = '".$user->id."' AND ctc.element = 'project'  AND element_id = prj.rowid )))";
    }
    $sql .= '  ORDER BY prj.fk_soc, prjRef, tskRef';
    dol_syslog(__METHOD__, LOG_DEBUG);
    $resql = $this->db->query($sql);
    if ($resql) {
        $this->taskTimesheet = array();
            $num = $this->db->num_rows($resql);
            $tasksList = array();
            $i = 0;
            // Loop on each record found, so each couple (project id, task id)
            $ret = array();
            while($i < $num)
            {
                $error = 0;
                $obj = $this->db->fetch_object($resql);
                if (!(array_key_exists($obj->taskid,$tasksList) && $tasksList[$obj->taskid]->isOpen )) {
                    $tasksList[$obj->taskid] = NEW TimesheetTask($this->db, $obj->taskid);
                    //$tasksList[$obj->taskid]->id = $obj->taskid;
                    if ($obj->appid) {
                        $tasksList[$obj->taskid]->fetch($obj->appid);
                    }
                    $tasksList[$obj->taskid]->userId = $this->userId;
                    $tasksList[$obj->taskid]->date_start_approval = $this->date_start;
                    $tasksList[$obj->taskid]->date_end_approval = $this->date_end;
                    $tasksList[$obj->taskid]->task_timesheet = $this->id;
                    $tasksList[$obj->taskid]->progress = $obj->progress;
                    $tasksList[$obj->taskid]->isOpen = $obj->ectype == 'project_task' ? true : false;
                    $tasksList[$obj->taskid]->listed =  (is_array($whiteList) && array_key_exists($obj->taskid, $whiteList) )?$whiteList[$obj->taskid]:null;
                // $tasksList[$obj->taskid]->pStatus = $obj->p_status;

                    $ret[$obj->taskid] = $obj->appid;
                }
                $i++;

            }
            $this->db->free($resql);
            $i = 0;
            $othertaskid = array();
            if(isset($this->taskTimesheet))unset($this->taskTimesheet);
            $this->taskTimesheet = array();
            foreach ($tasksList as $row) {
                $othertaskid[] = $row->id;
                dol_syslog(__METHOD__.'::task='.$row->id, LOG_DEBUG);
                $row->getTaskInfo();// get task info include in fetch
                $row->getActuals($datestart, $datestop, $userid);
                $this->taskTimesheet[$row->id] = $row->serialize();
            }
            unset($tasksList);
            // bundle all other time in a line
            $other = NEW TimesheetTask($this->db, -1);
            $other->exclusionlist = $othertaskid;
            $other->date_start_approval = $this->date_start;
            $other->date_end_approval = $this->date_end;
            $other->userId = $this->userId;
            $other->description = $langs->trans("Other");
            $other->title= $langs->trans("Other");
            $other->getActuals($datestart, $datestop, $userid);
            $this->taskTimesheet[0] = $other->serialize();

            return $ret;
    } else {
            dol_print_error($this->db);
            return -1;
    }
}
 /*
 * function to post the all actual submitted
 *
 *  @param    array(int)               $tabPost               array sent by POST with all info about the task
 *  @param    array(int)               $notes                  array sent by POST with the task notes
 *  @param    array(int)               $progress               array sent by POST with the task dstated progress
 *  @return     int                                                        number of tasktime creatd/changed
 */
public function updateActuals($tabPost, $notes = array(), $progresses = array())
{
    
     //FIXME, tta should be creted
    if ($this->status == APPROVED)return -1;

    dol_syslog(__METHOD__, LOG_DEBUG);
    $ret = 0;
   // $tmpRet = 0;
    //$_SESSION['timesheet'][$this->token]['timeSpendCreated'] = 0;
    //$_SESSION['timesheet'][$this->token]['timeSpendDeleted'] = 0;
    //$_SESSION['timesheet'][$this->token]['timeSpendModified'] = 0;
    // $_SESSION['timesheet'][$token]['NoteUpdated'] = 0
        /*
         * For each task store in matching the session token
         */
        if (is_array($this->taskTimesheet)){ 
            foreach ($this->taskTimesheet as $key => $row) { 
                $tasktime = new TimesheetTask($this->db);
                $tasktime->unserialize($row);
                if (isset($tabPost[$tasktime->id])){
                    $note = array_key_exists($tasktime->id,$notes)?$notes[$tasktime->id]:null;
                    $progress = array_key_exists($tasktime->id,$progresses)?$progresses[$tasktime->id]:null;
                    $ret += $tasktime->postTaskTimeActual($tabPost[$tasktime->id], 
                        $this->userId, $this->user, $this->token, $note, $progress);
                }
                $this->taskTimesheet[$key] = $tasktime->serialize();
            }
        }
        /*
    if (!empty($idList)) {
        //$this->project_tasktime_list = $idList;
        $this->update($this->user);
    }
    */
    return $ret;
}
/*
 * function to get the name from a list of ID
 *
  *  @param    object            $db             database objet
 *  @param    array(int)/int        $userids        array of manager id
  *  @return  array(int => String)                                array(ID => userName)
 */
public function getUserName()
{
    $sql = "SELECT usr.rowid, CONCAT(usr.firstname, ' ', usr.lastname) as username FROM ".MAIN_DB_PREFIX.'user AS usr WHERE';
    $sql .= ' usr.rowid = '.$this->userId;
    dol_syslog(__METHOD__, LOG_DEBUG);
    $resql = $this->db->query($sql);
    if ($resql) {
        $i = 0;
        $num = $this->db->num_rows($resql);
        if ($num) {
            $obj = $this->db->fetch_object($resql);
            if ($obj) {
                $this->userName = $obj->username;
            } else{
                return -1;
            }
            $i++;
        }
    } else {
       return -1;
    }
      //$select .= "\n";
    return 0;
}
  /*
 * update the status based on the underlying Task_time_approval
 *
 *  @param    object/int                $user           timesheet object, (task)
 *  @param    string               $status              to overrule the logic if the status enter has an higher priority
 *  @return     string                         status updated of KO(-1)
 */
public function updateStatus($user, $status = 0)
{
    if ($this->id <= 0)return -1;
    if ($status!='') {
        if ($status<0 || $status> STATUSMAX)return -1;// status not valid
        $updatedStatus = $status;
    } elseif (!empty($this->status)) {
         $updatedStatus = $this->status;
    } else{ // no status
        $updatedStatus = 2;
    }
    if (!is_array($this->taskTimesheet) || count($this->taskTimesheet)<1)$this->fetchTaskTimesheet();
    if ($status == $this->status) { // to avoid eternal loop
        return 1;
    }
    $Priority = array(
    DRAFT => 0,
    SUBMITTED => 1,
    APPROVED => 2,
    CANCELLED => 4,
    REJECTED => 5,
    CHALLENGED => 6,
    INVOICED => 7,
    UNDERAPPROVAL => 3,
    PLANNED => 9);
    //look for the status to apply to the TS  from the TTA
    if (count($this->taskTimesheet ))foreach ($this->taskTimesheet as $row) {
        $tta = new TimesheetTask($this->db);
        $tta->unserialize($row);
        if ($tta->appId>0) { // tta already created
            $tta->fetch($tta->appId);
            $statusPriorityCur = $tta->status;
            $updatedStatus = ($Priority[$updatedStatus]>$Priority[$statusPriorityCur])?$updatedStatus:$statusPriorityCur;
        }// no else as the tta should be created upon submission of the TS not status update
    }
    $this->status = $updatedStatus;
    $this->update($user);
     return $this->status;
}
 /*
 * change the status of an approval
 *
 *  @param      object/int        $user         user object or user id doing the modif
 *  @param      int               $id           id of the timesheetuser
 *  @return     int                         <0 if KO, Id of created object if OK
 */
Public function setStatus($user, $status, $id = 0)
{
    //role ?
    $error = 0;
    //if the satus is not an ENUM status
    if ($status<0 || $status>STATUSMAX) {
        dol_syslog(__METHOD__.": this status '{$status}' is not part or the enum list", LOG_ERR);
        return false;
    }
    $Approved = (in_array($status, array(APPROVED, UNDERAPPROVAL)));
    $Rejected = (in_array($status, array(REJECTED, CHALLENGED)));
    $Submitted = ($status == SUBMITTED)?true:false;
    $draft = ($status == DRAFT)?true:false;
    // Check parameters
    if ($id!=0)$this->fetch($id);
    $this->status = $status;
    // Update request
    $error = ($this->id <= 0)?$this->create($user):$this->update($user);
    if ($error>0) {
        if ($status == REJECTED)$this->sendRejectedReminders($user);
        if (is_array($this->taskTimesheet) && count($this->taskTimesheet)<1) {
        $this->fetch($id);
        }
        $this->status = DRAFT;// SET THE STATUS TO DRAFT TO GET ALL
        $this->fetchTaskTimesheet();
        $this->status = $status;
        $this->status = $status;
        if (is_array($this->taskTimesheet) && count($this->taskTimesheet)>0)foreach ($this->taskTimesheet as $ts) {
            $tasktime = new TimesheetTask($this->db);
            $tasktime->unserialize($ts);
            //$tasktime->appId = $this->id;
            if ($Approved)$ret = $tasktime->approved($user, TEAM, false);
            elseif ($Rejected)$ret = $tasktime->challenged($user, TEAM, false);
            elseif ($Submitted)$ret = $tasktime->submitted($user);
            elseif ($draft)$ret = $tasktime->setStatus($user, DRAFT);
        }
          //if ($ret>0)$this->db->commit();
        return 1;
    }
}
/******************************************************************************
 *
 * HTML  methods
 *
 ******************************************************************************/
/* function to genegate the tHTML view of the TS
 *  @param    bool           $ajax     ajax of html behaviour
 *  @return     string                                                   html code
 */
public function getHTML( $ajax = false, $Approval = false)
{
    global $langs;
    $Form = $this->getHTMLHeader(true);
    // show the filter
    $Form .= $this->getHTMLHolidayLines($ajax);
    $Form .= $this->getHTMLPublicHolidayLines($ajax);
    //if (!$Approval)$Form .= $this->getHTMLTotal();
    //$Form .= '<tbody style = "overflow:auto;">';
    $Form .= $this->getHTMLtaskLines( $ajax);
    //$Form .= '</tbody>';// overflow div
    //$Form .= $this->getHTMLTotal();
    $Form .= '</table>';
    $Form .= $this->getHTMLNote($ajax);
    if (!$Approval) {
        $Form .= $this->getHTMLFooter($ajax);
    }
    $Form .= '<br>'."\n";
    return $Form;
}
/* function to genegate the timesheet table header
 *   @param    bool    $search    dd search
 *   @return     string                                                   html code
 */
public function getHTMLHeader($search = false)
{
    global $langs, $conf;
    $weeklength = getDayInterval($this->date_start, $this->date_end);
    $maxColSpan = $weeklength+count($this->headers);
    $format = ($langs->trans("FormatDateShort")!="FormatDateShort"?$langs->trans("FormatDateShort"):$conf->format_date_short);
    $html = '<input type = "hidden" name = "startDate" value = "'.$this->date_start.'" />';
    $html .= '<input type = "hidden" name = "tsUserId" value = "'.$this->id.'" />';
    $html .= "\n<table id = \"timesheetTable_{$this->id}\" class = \"noborder\" width = \"100%\">\n";
    if ($search) {
        $html .= '<tr  id = "searchline">';
        $html .= '<td><a>'.$langs->trans("Search").'</a></td>';
        $html .= '<td span = "0"><input type = "texte" name = "taskSearch" onkeyup = "searchTask(this)"></td></tr>';
    }
    ///Whitelist tab
    if (getConf('TIMESHEET_TIME_SPAN') == "month") {
        $format = "%d";
        $html .= '<tr class = "liste_titre" id = "">'."\n";
        $html .= '<td colspan = "'.$maxColSpan.'" align = "center"><a >'.$langs->trans(date('F', $this->date_start)).' '.date('Y', $this->date_start).'</a></td>';
        $html .= '</tr>';
    }
    $html .= '<tr class = "liste_titre" id = "">'."\n";
    
    foreach ($this->headers as $key => $value) {
        $html .= "\t<th ";
        if (count($this->headers) == 1) {
                $html .= 'colspan = "2" ';
        }
        $html .= "> <a onclick=\"sortTable('timesheetTable_{$this->id}', 'col{$value}', 'asc');\">".$langs->trans($value)."</a></th>\n";
    }
    for ($i = 0;$i<$weeklength;$i++)
    {
        $curDay = $this->date_start+ SECINDAY*$i+SECINDAY/4;
        $htmlDay = (getConf('TIMESHEET_TIME_SPAN') == "month")?substr($langs->trans(date('l', $curDay)), 0, 3):$langs->trans(date('l', $curDay));
        $html .= "\t".'<th class = "daysClass days_'.$this->id.'" id = "'.$this->id.'_'.$i.'" width = "35px" style = "text-align:center;" >'.$htmlDay.'<br>'.dol_print_date($curDay, $format)."</th>\n";
    }
    return $html;
}
/* function to genegate the timesheet table header
 *
  *  @param    bool           $ajax     ajax of html behaviour
  *  @return     string                                                   html code
 */
public function getHTMLFormHeader($ajax = false)
{
     global $langs, $conf, $token;
    $html = '<form id = "timesheetForm" name = "timesheet" onSubmit="removeUnchanged();" action="?action=submit&wlm='.$this->whitelistmode.'&userid='.$this->userId.'" method = "POST"';
    if ($ajax)$html .= ' onsubmit = " return submitTimesheet(0);"';
    $html .= '>';
    if($conf->agenda && getConf('TIMESHEET_IMPORT_AGENDA')){
        $html .= '<a class = "butAction" href="?action=importCalandar&token='.$token.'&startDate='.$this->date_start.'">'.$langs->trans('ImportCalandar').'</a>';
    }
    return $html;
}
  /* function to genegate ttotal line
  *
  *  @return     string
 */
public function getHTMLTotal()
{
    $html = "<tr class = 'liste_titre'>\n";
    $html .= '<th colspan = "'.(count($this->headers)-1).'" align = "right" > TOTAL </th>';
    $length = getDayInterval($this->date_start, $this->date_end);
    $html .= "<th><div class = \"TotalUser_{$this->id}\">&nbsp;</div></th>\n";
    for ($i = 0;$i<$length;$i++)
    {
       $html .= "<th><div class = \"TotalColumn_{$this->id} TotalColumn_{$this->id}_{$i}\">&nbsp;</div></th>\n";
    }
    $html .= "</tr>\n";
    return $html;
}
  /* function to genegate the timesheet table header
 *
 *  @param     int               $ajax         enable ajax handling
 *  @return     string                                               html code
 */
public function getHTMLFooter($ajax = false)
{
    global $langs;
    //form button
    $html = '<input type = "hidden" id="csrf-token" name = "token" value = "'.$this->token."\"/>\n";
    $html .= $this->getHTMLActions();
    $html .= "</form>\n";
    if ($ajax ==true) {
        $html .= '<script type = "text/javascript">'."\n\t";
        $html .= 'window.onload = function()
            {loadXMLTimesheet("'.$this->date_start.'", '.$this->userId.');}';
        $html .= "\n\t".'</script>'."\n";
    }
     return $html;
}

/**
 * 
 * 
 */

public function getHTMLActions(){
    global $langs, $apflows, $token;
    $html = '<div class = "tabsAction">';
    $isOpenSatus = in_array($this->status, array(DRAFT, CANCELLED, REJECTED));
    if ($isOpenSatus) {
        $html .= '<input type = "submit"   class = "butAction" name = "save" value = "'.$langs->trans('Save')."\" />\n";
        //$html .= '<input type = "submit" class = "butAction" name = "submit" onClick = "return submitTs();" value = "'.$langs->trans('Submit')."\" />\n";
        if (in_array('1', array_slice($apflows, 1))) {
            $html .= '<input type = "submit"  class = "butAction" name = "submit"  value = "'.$langs->trans('Submit')."\" />\n";
            $html .= '<input type = "submit"  class = "butAction" name = "submit_next"  value = "'.$langs->trans('SubmitNext')."\" />\n";
        }else{
            $html .= '<input type = "submit"  class = "butAction" name = "save_next"  value = "'.$langs->trans('SaveNext')."\" />\n";
        }
        $html .= '<a class = "butActionDelete" href="?view=list&startDate='.$this->date_start.'">'.$langs->trans('Cancel').'</a>';
    } elseif ($this->status == SUBMITTED)$html .= '<input type = "submit" class = "butAction" name = "recall" " value = "'.$langs->trans('Recall')."\" />\n";
    $html .= '</div>';
    return $html;
 }
   /* function to genegate the timesheet table header
 *
 *  @param    int           $current           number associated with the TS AP

  *  @return     string                                                   html code
 */
public function getHTMLFooterAp($current)
{
     global $langs;
    //form button
    $html = '<input type = "hidden" id="csrf-token"  name = "token" value = "'.$this->token."\"/>\n";
    $html .= '<input type = "hidden" name = "target" value = "'.($current+1)."\"/>\n";
    $html .= '<div class = "tabsAction">';
    $html .= '<input type = "submit" class = "butAction" name = "Send" value = "'.$langs->trans('Next')."\" />\n";
    //$html .= '<input type = "submit" class = "butAction" name = "submit" onClick = "return submitTs();" value = "'.$langs->trans('Submit')."\" />\n";
    $html .= '</div>';
    $html .= "</form>\n";
     return $html;
}
      /*
 * function to genegate the timesheet list

 *  @return     string                                                   html code
 */
public function getHTMLtaskLines( $ajax = false)
{
    $i = 1;
    $Lines = '';
    $nbline = 0;
    $personalHoliday = null;
    $publicHoliday = null;
    if (is_array($this->holidays->holidaylist) && $this->holidays->holidayPresent){
        $personalHoliday = $this->holidays->holidaylist;
    }
    if (isset($this->publicHolidays->holidaylist) && is_array($this->publicHolidays->holidaylist) && $this->publicHolidays->holidayPresent){
        $publicHoliday = $this->publicHolidays->holidaylist;
    }
    //$holiday =  $publicHoliday + $personalHoliday;
    $holiday = array();
    if(is_array($personalHoliday) && is_array($publicHoliday)){
        for($i = 0; $i < max(count($personalHoliday),count($publicHoliday)); $i++){
            $holiday[$i] = array_merge($publicHoliday[$i],$personalHoliday[$i]);
        }
    }else if(is_array($personalHoliday)){
        $holiday = $personalHoliday;
    }else if(is_array($publicHoliday)){
        $holiday = $publicHoliday;
    }
  
    
    if (!$ajax & is_array($this->taskTimesheet)) {
        $nbline = count($this->taskTimesheet);
        foreach ($this->taskTimesheet as $timesheet) {
            $row = new TimesheetTask($this->db);
            $row->unserialize($timesheet);
            //$row->db = $this->db;
            if (in_array($this->status, array(REJECTED, DRAFT, PLANNED, CANCELLED))) {
                $blockOveride = -1;
            } elseif (in_array($this->status, array(UNDERAPPROVAL, INVOICED, APPROVED, CHALLENGED, SUBMITTED))) {
                $blockOveride = 1;
            } else{
                $blockOveride = 0;
            }
            if ($row->id != -1 or $row->getSavedTimeTotal() != 0){
                $Lines .= $row->getTimesheetLine($this->headers, $this->id, $blockOveride, $holiday);
            }
                

                //if ($i%10 == 0 &&  $nbline-$i >5) $Lines .= $this->getHTMLTotal();
            $i++;
        }
    }
    return $Lines;
}
   /* function to genegate the timesheet note
 *
  *  @return     string                                                   html code
 */
public function getHTMLNote()
{
     global $langs;
     $isOpenSatus = (in_array($this->status, array(REJECTED, DRAFT, PLANNED, CANCELLED)));
     $html = '<div class = "noborder"><div  width = "100%">'.$langs->trans('Note').'</div><div width = "100%">';
    if ($isOpenSatus) {
        $html .= '<textarea class = "flat"  cols = "75" name = "noteTaskApproval['.$this->id.']" rows = "3" >'.$this->note.'</textarea>';
        $html .= '</div>';
    } elseif (!empty($this->note)) {
        $html .= $this->note;
        $html .= '></div>';
    } else{
        $html = "";
    }
    return $html;
}
        /*
 * function to genegate the timesheet list
 *  @return     string                                                   html code
 */
public function getHTMLHolidayLines($ajax = false)
{
    $i = 0;
    $Lines = '';
    if (!$ajax) {
        if (is_object($this->holidays)){
            $Lines .= $this->holidays->getHTMLFormLine($this->headers, $this->id, $this->userId);
        }else{
            dol_syslog(__METHOD__.": missing Holiday object", LOG_ERR);

        }
    }
    return $Lines;
}
        /*
 * function to genegate the timesheet list
 *  @return     string                                                   html code
 */
public function getHTMLPublicHolidayLines($ajax = false)
{
    $i = 0;
    $Lines = '';
    if (!$ajax) {
        if (is_object($this->publicHolidays)){
            $Lines .= $this->publicHolidays->getHTMLFormLine($this->headers, $this->id, $this->userId);
        }else{
            dol_syslog(__METHOD__.": missing Holiday object", LOG_ERR);

        }
    }
    return $Lines;
}
 /*
 * function to print the timesheet navigation header
 *
 *  @param    string               $optioncss           printmode
 *  @param     int               $ajax                support the ajax mode(not supported yet)
 *  @param     object              $form                form object
 *  @return     string                                       HTML
 */
public function getHTMLNavigation($optioncss, $token, $ajax = false)
{
    global $langs, $conf;
    $form = new Form($this->db);
    $tail = '';
    if (getConf('TIMESHEET_ADD_FOR_OTHER') == 1){
        $tail = '&userid='.$this->userId;
    }
    $Nav = '<table class = "noborder" width = "50%">'."\n\t".'<tr>'."\n\t\t".'<th>'."\n\t\t\t";
    if ($ajax) {
    } else{
        $Nav .= '<a href="?startDate='.getStartDate($this->date_start, -1).$tail;
    }
    if ($optioncss != '')$Nav .= '&amp;optioncss='.$optioncss;
    $Nav .= '">  &lt;&lt;'.$langs->trans("Previous").' </a>'."\n\t\t</th>\n\t\t<th>\n\t\t\t";
    $Nav .= '<form name = "goToDate" action="?view=goToDate&token='.$token.''.$tail.'" method = "POST" >'."\n\t\t\t";
    //FIXME should take token as input
    $token = getToken();
    $Nav .= '<input type = "hidden" id="csrf-token" name = "token" value = "'.$token.'"/>';

    $Nav .= $langs->trans("GoTo").': '.$form->select_date(-1, 'toDate', 0, 0, 0, "", 1, 1, 1)."\n\t\t\t";;
    $Nav .= '<input type = "submit" value = "Go" /></form>'."\n\t\t</th>\n\t\t<th>\n\t\t\t";
    $Nav .= '<a href="?startDate='.getStartDate($this->date_start, 1).$tail;
    if ($optioncss != '') $Nav .= '&amp;optioncss='.$optioncss;
    $Nav .= '">'.$langs->trans("Next").' &gt;&gt;</a>'."\n\t\t</th>\n\t</tr>\n </table>\n";
    return $Nav;
}
     /**
     *        Return clickable name(with picto eventually)
     *
     *        @param                string                        $htmlcontent                text to show
     *        @param                int                        $id                     Object ID
     *        @param                string                        $ref                    Object ref
     *        @param                int                        $withpicto                0 = _No picto, 1 = Includes the picto in the linkn, 2 = Picto only
     *        @return                string                                                String with URL
     */
    public function getNomUrl($htmlcontent, $id = 0, $ref = '', $withpicto = 0)
    {
        global $langs,$token;
        $result = '';
        if (empty($ref) && $id == 0) {
            if (isset($this->id)) {
                $id = $this->id;
            } elseif (isset($this->rowid)) {
                $id = $this->rowid;
            }if (isset($this->ref)) {
                $ref = $this->ref;
            }
        }
        if ($id) {
            $lien = '<a href = "'.DOL_URL_ROOT.'/timesheet/timesheetuser.php?id='.$id.'&view=card">';
        } elseif (!empty($ref)) {
            $lien = '<a href = "'.DOL_URL_ROOT.'/timesheet/timesheetuser.php?ref='.$ref.'&view=card">';
        } else{
            $lien = "";
        }
        $lienfin = empty($lien)?'':'</a>';
        $picto = 'timesheet@timesheet';
        if ($ref) {
            $label = $langs->trans("Show").': '.$ref;
        } elseif ($id) {
            $label = $langs->trans("Show").': '.$id;
        }
        if ($withpicto == 1) {
            $result .= ($lien.img_object($label, $picto).$htmlcontent.$lienfin);
        } elseif ($withpicto == 2) {
            $result .= $lien.img_object($label, $picto).$lienfin;
        } else{
            $result .= $lien.$htmlcontent.$lienfin;
        }
        return $result;
    }
    /**
    *        Return HTML to get other user
    *
    *        @param                string                        $idsList                list of user id
    *        @param                int                        $selected               id that shoudl be selected
    *        @param                int                        $admin                 is the user an admin
    *        @return                string                                                String with URL
    */
    public function getHTMLGetOtherUserTs($idsList, $selected, $admin)
    {
        global $langs;
        $form = new Form($this->db);
        $HTML = '<form id = "timesheetForm" name = "OtherUser" action="?action=getOtherTs&wlm='.$this->whitelistmode.'" method = "POST">';
        if (!$admin) {
            $HTML .= $form->select_dolusers($selected, 'userid', 0, null, 0, $idsList);
        } else{
            $HTML .= $form->select_dolusers($selected, 'userid');
        }
        $HTML .= '<input type = "hidden" id="csrf-token" name = "token" value = "'.$this->token.'"/>';

        $HTML .= '<input type = "submit" value = "'.$langs->trans('Submit').'"/></form> ';
        
        return $HTML;
    }
    /**
     *        Initialise object with example values
     *        Id must be 0 if object instance is a specimen
     *
     *  @param bool $test to init test speciemen
     *        @return        void
     */
    public function initAsSpecimen($test = false)
    {
        $this->id = 0;

        $this->userId = '';
        $this->date_start = '';
        $this->date_end = '';
        //$this->status = '';
        //$this->sender = '';
        //$this->recipient = '';
        //$this->estimates = '';
        //$this->tracking = '';
        //$this->tracking_ids = '';
        $this->date_creation = '';
        //$this->date_modification = '';
        $this->user_creation = '';
        //$this->user_modification = '';
        $this->task = '';
        $this->note = '';
        if ($test) {
            $this->userId = 1;
            $this->date_start = srttotime('this monday', dol_time());
            $this->date_end = srttotime('next monday', dol_time())-1;
            $this->task = 1;
            $this->note = 'this is a test usertasktime';
        }
    }

/******************************************************************************
 *
 * AJAX methods
 *
 ******************************************************************************/
/*
 * function to get the timesheet in XML format(not up to date)
 *
 *  @return     string                                         XML result containing the timesheet info
 */
        /*
public function GetTimeSheetXML()
{
    global $langs, $conf;
    $xml .= "<timesheet dateStart = \"{$this->date_start}\" token = \"{$this->token}\" timetype = \"".getConf('TIMESHEET_TIME_TYPE','hours')."\"";
    $xml .= ' nextWeek = "'.date('Y\WW', strtotime($this->date_start."+3 days +1 week")).'" prevWeek = "'.date('Y\WW', strtotime($this->date_start."+3 days -1 week")).'">';
    //error handling
    $xml .= getEventMessagesXML();
    //header
    $i = 0;
    $xmlheaders = '';
    foreach ($this->headers as $header) {
        if ($header == 'Project') {
            $link = ' link = "'.DOL_URL_ROOT.'/projet/card.php?id="';
        } elseif ($header == 'Tasks' || $header == 'TaskParent') {
            $link = ' link = "'.DOL_URL_ROOT.'/projet/tasks/task.php?withproject=1&amp;id="';
        } elseif ($header == 'Company') {
            $link = ' link = "'.DOL_URL_ROOT.'/societe/soc.php?socid="';
        } else{
            $link = '';
        }
        $xmlheaders .= "<header col = \"{$i}\" name = \"{$header}\" {$link}>{$langs->transnoentitiesnoconv($header)}</header>";
        $i++;
    }
    $xml .= "<headers>{$xmlheaders}</headers>";
        //days
    $xmldays = '';
    for ($i = 0;$i<7;$i++)
    {
       $curDay = strtotime($this->date_start.' +'.$i.' day');
       //$weekDays[$i] = date('d-m-Y', $curDay);
       $curDayTrad = $langs->trans(date('l', $curDay)).'  '.dol_mktime($curDay);
       $xmldays .= "<day col = \"{$i}\">{$curDayTrad}</day>";
    }
    $xml .= "<days>{$xmldays}</days>";
        $tab = $this->fetchTaskTimesheet();
        $i = 0;
        $xml .= "<userTs userid = \"{$this->userId}\"  count = \"".count($this->taskTimesheet)."\" userName = \"{$this->userName}\" >";
        foreach ($this->taskTimesheet as $timesheet) {
            $row = new TimesheetTask($this->db);
             $row->unserialize($timesheet);
            $xml .= $row->getXML($this->date_start);//FIXME
            $i++;
        }
        $xml .= "</userTs>";
    //}
    $xml .= "</timesheet>";
    return $xml;
}        */
    /**
     *  Function that will send email to
     *
     * @return bool success / failure
     */
    public function sendApprovalReminders()
    {
        global $langs, $db;
        $ret = true;
        $sql = 'SELECT';
        $sql .= ' t.date_start, t.date_end, ';
        $sql .= ' u.email as w_email, utm.email as tm_email,';
        $sql .= ' u.fk_user as approverid';
        $sql .= ' FROM '.MAIN_DB_PREFIX.'project_task_time_approval as t';
        $sql .= ' JOIN '.MAIN_DB_PREFIX.'user as u on t.fk_userid = u.rowid ';
        $sql .= ' JOIN '.MAIN_DB_PREFIX.'user as utm on u.fk_user = utm.rowid ';
        $sql .= ' WHERE (t.status='.SUBMITTED.' OR t.status='.UNDERAPPROVAL.' OR t.status='.CHALLENGED.') ';
        $sql .= '  AND t.recipient='.TEAM.' ORDER BY u.fk_user';
        dol_syslog(__METHOD__, LOG_DEBUG);
        $emails = array();
        $resql = $this->db->query($sql);
        if ($resql) {
            $num = $this->db->num_rows($resql);
            for ($i = 0;$i<$num;$i++) {
                $obj = $this->db->fetch_object($resql);
                $emails[$obj->tm_email][$obj->w_email][] = array(
                    "date_start" => $obj->date_start,
                    "date_end" => $obj->date_end
                );
            }
        } else {
            dol_print_error($db);
            $list = array();
            $ret = false;
        }
        if ($ret != false) {
            foreach ($emails as $tm_email => $user_approuvals) {
                foreach ($user_approuvals as $w_email => $dates) {
                    $message = str_replace(
                        "__NB_TS__", count($dates), 
                        str_replace('\n', "\n", $langs->trans('YouHaveApprovalPendingMsg'))
                    );
                    foreach ($dates as $date) {
                        $message .= "\n * ".$date["date_start"]." - ".$date["date_end"];
                    }
                    $sendto = $tm_email;
                    $replyto = $w_email;
                    $addr_cc = null; //$addr_cc = $obj->w_email;
                    $subject = $langs->transnoentities("YouHaveApprovalPending");
                    if (!empty($sendto) && $sendto!="NULL") {
                        include_once DOL_DOCUMENT_ROOT .'/core/class/CMailFile.class.php';
                        $mailfile = new CMailFile(
                            $subject,
                            $sendto,
                            $replyto,
                            $message,
                            $filename_list = array(),
                            $mimetype_list = array(),
                            $mimefilename_list = array(),
                            $addr_cc, $addr_bcc = null,
                            $deliveryreceipt = 0,
                            $msgishtml = 1
                        );
                        $ret = $ret && $mailfile->sendfile();
                    }
                }
            }
        }
        return $ret;
    }


    /**
     * Function that will send email upon timesheet not sent
     * @return bool success / failure
     */
    public function sendTimesheetReminders()
    {
        global $db;
    //check date: was yesterday a period end day ?
    $date_start = getStartDate(time(), -1);
    $date_end = getEndDate($date_start);
        $ret = true;
        $sql = "SELECT SUM(pt.task_duration)/3600 as duration,  u.weeklyhours
            u.email, u.weeklyhours
            FROM ".MAIN_DB_PREFIX."element_contact  as ec ON t.rowid = ec.element_id
           LEFT JOIN ".MAIN_DB_PREFIX."c_type_contact as ctc ON ctc.rowid = fk_c_type_contact
            LEFT JOIN ".MAIN_DB_PREFIX."projet_task_time pt ON  pt.fk_user = fk_socpeople
            LEFT JOIN ".MAIN_DB_PREFIX."user u ON u.rowid = fk_socpeople
            WHERE  (ctc.element in (\'project\') 
            and pt.task_date BETWEEN $date_start AND $date_end
            GROUP BY u.rowid "; 

        dol_syslog(__METHOD__, LOG_DEBUG);
        $emails = array();
        $resql = $this->db->query($sql);
        if ($resql) {
            $num = $this->db->num_rows($resql);
            for ($i = 0;$i<$num;$i++) {
                $obj = $this->db->fetch_object($resql);
                // FIXME: addapt weekhour to openday / without holidays (union)
                if ($obj->weeklyhours > $obj->duration) {
                $emails[$obj->email][] = array(
                    "weeklyhour" => $obj->date_start,
                    "duration" => $obj->date_end
                );
            }
            }
        } else {
            dol_print_error($db);
            $list = array();
            $ret = false;
        }
        if ($ret != false) {
            foreach ($emails as $email => $data) {
            //get the list of user that have the ts right
                $$url .= '/timesheet/Timesheet.php?startDate='.$date_start;
                $message = $langs->trans(
                    'YouHaveMissingTimesheetMsg', 
                    date(' d', $date_start), 
                    $url
                );
                $sendto = $email;
        
                $subject = $langs->transnoentities("YouHaveMissingTimesheet");
                if (!empty($sendto) && $sendto!="NULL") {
                    include_once DOL_DOCUMENT_ROOT .'/core/class/CMailFile.class.php';
                    $mailfile = new CMailFile(
                        $subject,
                        $sendto,
                        null,
                        $message,
                        $filename_list = array(),
                        $mimetype_list = array(),
                        $mimefilename_list = array(),
                        $addr_cc, $addr_bcc = 0,
                        $deliveryreceipt = 0,
                        $msgishtml = 1
                    );
                    $mailfile->sendfile();
                }
            }

        }


    }


    /**
     * Function that will send email upon timesheet rejection
     * @param    Doliuser   $user       objet
     * @return        void
     */
    public function sendRejectedReminders($user)
    {
        global $langs, $db, $dolibarr_main_url_root, $dolibarr_main_url_root_alt;
        $tsUser = new User($db);
        $tsUser->fetch($this->userId);
        $url = $dolibarr_main_url_root;
        if (strpos($dolibarr_main_url_root_alt, $_SERVER['PHP_SELF'])>0) {
            $url .= $dolibarr_main_url_root_alt;
        }
        $url .= '/timesheet/Timesheet.php?startDate='.$this->date_start;
        $message = $langs->trans(
            'YouHaveTimesheetRejectedMsg', 
            date(' d', $this->date_start), 
            $url
        );
        $sendto = $tsUser->email;
        $replyto = $user->email;
        $subject = $langs->transnoentities("YouHaveTimesheetRejected");
        if (!empty($sendto) && $sendto!="NULL") {
            include_once DOL_DOCUMENT_ROOT .'/core/class/CMailFile.class.php';
            $mailfile = new CMailFile(
                $subject,
                $sendto,
                $replyto,
                $message,
                $filename_list = array(),
                $mimetype_list = array(),
                $mimefilename_list = array(),
                $addr_cc, $addr_bcc = 0,
                $deliveryreceipt = 0,
                $msgishtml = 1
            );
            $mailfile->sendfile();
        }
    }

        
    /**
     * Function to import the calandar item into the timesheets
     * @param int $userid user to import calcandar
     * @param date $dateStart datestart to use for the import
     * @param date $dateEnd dateEnd to use for the import 
     */
    function importCalandar($userid = '', $dateStart = '', $dateEnd = ''){
        global $conf, $user;
        if(!is_numeric($userid)){
            if (is_numeric($this->userId)){
                $userid = $this->userId;
            }else{
                return -1;
            }
        }
        if(!is_a($dateStart,'DateTime') && !is_a($dateStart,'Date' && !is_numeric($dateStart))){
            $dateStart = $this->date_start;
        }        
        if(!is_a($dateEnd,'DateTime')  && !is_a($dateEnd,'Date') && !is_numeric($dateEnd) ){
            $dateEnd = $this->date_end;
        }
        // get the calandar event that have a task assigned and assigned to userid + busy
        $sql = 'SELECT a.id, a.code, a.label, a.fk_element as taskid, a.datep as datestart,';
        $sql .= 'a.fulldayevent, a.datep2 as dateend ';
        $sql .= " FROM ".MAIN_DB_PREFIX."actioncomm as a ";
        // add the user
        $sql .= " JOIN ".MAIN_DB_PREFIX."actioncomm_resources as r";
        // fectch the current time spent
        $sql .= " ON r.element_type = 'user' AND r.fk_actioncomm = a.id";
        $sql .= " WHERE a.transparency  = 1 AND a.elementtype  = 'task' AND r.fk_element= ".$userid;
        $sql .= " AND a.datep BETWEEN '".$this->db->idate($dateStart)."' AND '".$this->db->idate($dateEnd)."'" ;
        $sql .= " ORDER BY fulldayevent ASC, a.fk_element DESC";
        // execute query
        $resql = $this->db->query($sql);
        //number of day in the period

        $nbDay = ceil(($dateEnd - $dateStart) / 86400);

        // day array for all day events
        $days = array();
        // local list of tasktime
        $dayDuration = array();
        $tasktime = array();
        // execute the fuction only if there is querry results    
        if ($resql && $this->db->num_rows($resql) > 0) {
            //load already saved tasktime
            foreach($this->taskTimesheet as $taskid => $taskline){
                $tasktime[$taskid] = new TimesheetTask($this->db, $taskid);
                $tasktime[$taskid]->unserialize($taskline);
            }
            // go through all querry result    
            while ($obj = $this->db->fetch_object($resql)) {
                //create a tasktime object if not yet present on local liss
                if(!array_key_exists($obj->taskid, $tasktime)){
                    $tasktime[$obj->taskid] = new TimesheetTask($this->db, $obj->taskid);
                    $tasktime[$obj->taskid]->userId = $userid;
                    $tasktime[$obj->taskid]->date_start_approval = $dateStart;
                    $tasktime[$obj->taskid]->date_end_approval = $dateEnd;
                    // search if there is a tasktime for this event 
                }
                $action_date_end = $this->db->jdate($obj->dateend);
                $action_date_start = $this->db->jdate($obj->datestart);
                // for each day
                for($daykey = 0; $daykey < $nbDay; $daykey++){
                    // init the dayduration for later
                    $dayDuration[$daykey] = 0;
                    $duration = 0;  
                    //is the event in day y       
                    if( $action_date_end > ($dateStart + $daykey * SECINDAY +1 ) 
                        &&  $action_date_start <= ($dateStart + ($daykey+1) * SECINDAY )) {
                        if($obj->fulldayevent == 0){
                            //foreach task that are not "all day" define duration as 
                            // duration = cal_duration>MAx? STD:cal_duration
                            $duration = $action_date_end - $action_date_start;
                            $max_dur_day = getConf('TIMESHEET_DAY_MAX_DURATION') * 3600;
                            $duration = min( $duration , $max_dur_day);
                            // write in database the new TS
                            $daynote = $obj->code." - ".$obj->label.": ".formatTime($duration, -1); 
                            // check and update only ifthe meeting is note already in noted
                            if(!is_array($tasktime[$obj->taskid]->tasklist) 
                                || !array_key_exists($daykey, $tasktime[$obj->taskid]->tasklist)
                                || (strpos($tasktime[$obj->taskid]->tasklist[$daykey]['note'], $daynote) === false))  {
                                    $tasktime[$obj->taskid]->saveTaskTime($user, 
                                    $duration,  $daynote, $daykey, true);
                            }
    
                        }else{
                            $days[$daykey][$obj->taskid] = array('id' => $obj->taskid, 
                                'title' => ($obj->code.' - '.$obj->label), 
                            'duration' => 0);
                        }                            
                    } 
                }
            }
            // generate the total per day
            if(is_array($tasktime))foreach($tasktime as $taskline){
                if(is_array($taskline->tasklist))foreach ($taskline->tasklist as $daykey => $item) {
                    $dayDuration[$daykey] += $item['duration'];
                    if(is_array($item['other']) && count($item['other'])>0){
                        $dayDuration[$daykey] += array_sum(array_column($item['other'], 'duration'));
                    }
                }
            }
            // Create timespent for the all day event
            foreach($days as $daykey => $day ){
                $nbFullDayCurDay = count($day);
                $duration = (getConf('TIMESHEET_DAY_DURATION',0) * 3600
                    - $dayDuration[$daykey]) / $nbFullDayCurDay ; 
                //for eachfull day event
                foreach($day as $taskid => $tasktimeDetails){
                    $daynote = $tasktimeDetails['title'].": ".formatTime($duration, -1); 
                    // check and update only ifthe meeting is note already in noted
                    if(!is_array($tasktime[$obj->taskid]->tasklist) 
                        || !array_key_exists($daykey, $tasktime[$obj->taskid]->tasklist)
                        || (strpos($tasktime[$obj->taskid]->tasklist[$daykey]['note'], $daynote) === false))  {
                        $tasktime[$taskid]->saveTaskTime($user, $duration, 
                                    $daynote, $daykey, true);
                    }
                }
            }
            // save the updated taskline in the object
            unset($this->taskTimesheet);
            $this->taskTimesheet = array();
            foreach($tasktime as $taskid => $taskline){
                $this->taskTimesheet[$taskid]= $taskline->serialize();
            }
        }




    }
}
