<?php
/* Copyright (C) 2007-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2014           Juanjo Menent                <jmenent@2byte.es>
 * Copyright (C) Patrick Delcroix <patrick@pmpd.eu>
 *
 * This program is free software;you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation;either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY;without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */
/**
 *  \file       dev/timesheetFavourites/timesheetFavourite.class.php
 *  \ingroup    timesheet othermodule1 othermodule2
 *  \brief      This file is an example for a CRUD class file(Create/Read/Update/Delete)
 *                                Initialy built by build_class_from_table on 2015-08-01 08:59
 */
// Put here all includes required by your class file
require_once DOL_DOCUMENT_ROOT."/core/class/commonobject.class.php";
//require_once(DOL_DOCUMENT_ROOT."/societe/class/societe.class.php");
//require_once(DOL_DOCUMENT_ROOT."/product/class/product.class.php");
/**
 *        Put here description of your class
 */
class TimesheetFavourite extends CommonObject
{
    public $db;                                                        //!< To store db handler
    public $error;                                                        //!< To return error code(or message)
    public $errors = array();                                //!< To return several error codes(or messages)
    public $element = 'timesheetFavourite';                        //!< Id that identify managed objects
    public $table_element = 'timesheet_whitelist';                //!< Name of table without prefix where object is stored
    public $id;
    public $user;
    public $project;
    public $project_task;
    public $subtask;
    public $date_start = '';
    public $date_end = '';
    /**
     *  Constructor
     *
     *  @param        DoliDb                $db      Database handler
     */
    public function __construct($db)
    {
        $this->db = $db;
        return 1;
    }
    /**
     *  Create object into database
     *
     *  @param  int                $notrigger   0 = launch triggers after, 1 = disable triggers
     *  @return int                         <0 if KO, Id of created object if OK
     */
    public function create($notrigger = 0)
    {
        $error = 0;
        // Clean parameters
        if(!empty($this->user)) $this->user = trim($this->user);
        if(!empty($this->project)) $this->project = trim($this->project);
        if(!empty($this->project_task)) $this->project_task = trim($this->project_task);
        if(!empty($this->subtask)) $this->subtask = trim($this->subtask);
        if(!empty($this->date_start)) $this->date_start = trim($this->date_start);
        if(!empty($this->date_end)) $this->date_end = trim($this->date_end);
        // Check parameters
        // Put here code to add control on parameters values
        // Insert request
        $sql = "INSERT INTO ".MAIN_DB_PREFIX.$this->table_element."(";
        $sql.= 'fk_user, ';
        $sql.= 'fk_project, ';
        $sql.= 'fk_project_task, ';
        $sql.= 'subtask, ';
        $sql.= 'date_start, ';
        $sql.= 'date_end';
        $sql.= ") VALUES(";
        $sql .= ' \''.$this->user.'\', ';
        $sql .= ' \''.$this->project.'\', ';
        $sql .= ' '.(empty($this->project_task)?'NULL':'\''.$this->project_task.'\'').', ';
        $sql .= ' '.(($this->subtask) ? 'TRUE':'FALSE').', ';
        $sql .= ' '.(empty($this->date_start) || dol_strlen($this->date_start) == 0?'NULL':'\''.$this->db->idate($this->date_start).'\'').', ';
        $sql .= ' '.(empty($this->date_end) || dol_strlen($this->date_end) == 0?'NULL':'\''.$this->db->idate($this->date_end).'\'').'';
        $sql.= ")";
        $this->db->begin();
        dol_syslog(__METHOD__, LOG_DEBUG);
        $resql = $this->db->query($sql);
        if(! $resql) {
            $error++;$this->errors[] = "Error ".$this->db->lasterror();
        }
        if(! $error) {
            $this->id = $this->db->last_insert_id(MAIN_DB_PREFIX.$this->table_element);
            if(! $notrigger) {
                //// Call triggers :$result = $this->call_trigger('MYOBJECT_CREATE', $user);if($result < 0){ $error++;//Do also what you must do to rollback action if trigger fail}
            }
        }
        // Commit or rollback
        if($error) {
            foreach($this->errors as $errmsg) {
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
     *  Load the list of the user whitelist open between those date
     *
     *  @param        int      $userid        Id object
    *  @param        date        $datestart        start date
    *  @param        date        $datestop        stopdate
     *  @return             array)timesheetFavourite          return the list of the user whiteliste
     */
    public function fetchUserList($userid, $datestart, $datestop)
    {
        $List = array();
        $Listtask = array();
        $sql = "SELECT";
        $sql.= " t.rowid, ";

        $sql .= ' t.fk_user, ';
        $sql .= ' t.fk_project, ';
        $sql .= ' t.fk_project_task, ';
        $sql .= ' t.subtask, ';
        $sql .= ' t.date_start, ';
        $sql .= ' t.date_end';
        $sql.= " FROM ".MAIN_DB_PREFIX.$this->table_element." as t";
        $sql.= " WHERE t.fk_user = ".$userid;
        if($datestart)
            $sql.= ' AND (t.date_end >\''.$this->db->idate($datestart).'\' OR t.date_end IS NULL)';
        if($datestop)
            $sql.= ' AND (t.date_start <\''.$this->db->idate($datestop).'\' OR t.date_start IS NULL)';
        dol_syslog(get_class($this)."::fetchUserList");
        $resql = $this->db->query($sql);
        if($resql) {
            $num = $this->db->num_rows($resql);
            $i = 0;
            while($i<$num)
            {
                $obj = $this->db->fetch_object($resql);
                $List[$i] = new TimesheetFavourite($this->db);
                $List[$i]->id = $obj->rowid;
                $List[$i]->user = $obj->fk_user;
                $List[$i]->project = $obj->fk_project;
                $List[$i]->project_task = $obj->fk_project_task;
                $List[$i]->subtask = $obj->subtask;
                $List[$i]->date_start = $this->db->jdate($obj->date_start);
                $List[$i]->date_end = $this->db->jdate($obj->date_end);
                $i++;
            }
            $this->db->free($resql);
            foreach($List as $row) {
                //$Listtask = array_merge($Listtask, $row->getTaskList());
                $subListtask = $row->getTaskList();
                foreach($subListtask as $key => $value) {
                    $Listtask[$key] = $value;
                }
            }
        } else {
            $this->error = "Error ".$this->db->lasterror();
        }
        if(count($Listtask)>0)
            return  $Listtask;
        else
            return  null;
    }
   /**
     *  get all the task open with this line
     *
     *  @return int           task list
     */
    public function getTaskList()
    {
        $sql = "SELECT";
        $sql.= " t.rowid";
        $sql.= " FROM ".MAIN_DB_PREFIX."projet_task as t";
        if($this->project_task && $this->subtask) {
            $sql.= '  WHERE  (t.rowid = \''.$this->project_task.'\'';
            $sql.= '  OR  t.fk_task_parent = \''.$this->project_task.'\')';
        } elseif($this->project_task) {
            $sql.= '  WHERE t.rowid = \''.$this->project_task.'\'';
        } else {
            $sql.= ' WHERE t.fk_projet = \''.$this->project.'\'';
        }
        dol_syslog(get_class($this)."::getTaskList");
        $resql = $this->db->query($sql);
        if($resql) {
            $Listtask = Array();
            $num = $this->db->num_rows($resql);
            $i = 0;
            while($i<$num)
            {
                $obj = $this->db->fetch_object($resql);
                $Listtask[$obj->rowid] = $this->id;
                $i++;
            }
            $this->db->free($resql);
            return $Listtask;
        } else {
            $this->error = "Error ".$this->db->lasterror();
            return null;
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
        $sql = "SELECT";
        $sql.= " t.rowid, ";
        $sql .= ' t.fk_user, ';
        $sql .= ' t.fk_project, ';
        $sql .= ' t.fk_project_task, ';
        $sql .= ' t.subtask, ';
        $sql .= ' t.date_start, ';
        $sql .= ' t.date_end';
        $sql.= " FROM ".MAIN_DB_PREFIX.$this->table_element." as t";
        if($ref) $sql.= ' WHERE t.ref = \''.$ref.'\'';
        else $sql.= " WHERE t.rowid = ".$id;
        dol_syslog(get_class($this)."::fetch");
        $resql = $this->db->query($sql);
        if($resql) {
            if($this->db->num_rows($resql)) {
                $obj = $this->db->fetch_object($resql);
                $this->id = $obj->rowid;
                $this->user = $obj->fk_user;
                $this->project = $obj->fk_project;
                $this->project_task = $obj->fk_project_task;
                $this->subtask = $obj->subtask;
                $this->date_start = $this->db->jdate($obj->date_start);
                $this->date_end = $this->db->jdate($obj->date_end);
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
     *  @param  int                $notrigger         0 = launch triggers after, 1 = disable triggers
     *  @return int                         <0 if KO, >0 if OK
     */
    public function update($notrigger = 0)
    {
        $error = 0;
        // Clean parameters
        if(!empty($this->user)) $this->user = trim($this->user);
        if(!empty($this->project)) $this->project = trim($this->project);
        if(!empty($this->project_task)) $this->project_task = trim($this->project_task);
        if(!empty($this->subtask)) $this->subtask = trim($this->subtask);
        if(!empty($this->date_start)) $this->date_start = trim($this->date_start);
        if(!empty($this->date_end)) $this->date_end = trim($this->date_end);
        // Check parameters
        // Put here code to add a control on parameters values
        // Update request
        $sql = "UPDATE ".MAIN_DB_PREFIX.$this->table_element." SET";
        $sql .= ' fk_user='.(empty($this->user)!=0 ? 'null':'\''.$this->user.'\'').', ';
        $sql .= ' fk_project='.(empty($this->project)!=0 ? 'null':'\''.$this->project.'\'').', ';
        $sql .= ' fk_project_task='.(empty($this->project_task)!=0 ? 'null':'\''.$this->project_task.'\'').', ';
        $sql .= ' subtask='.(($this->subtask) ? 'TRUE':'FALSE').', ';
        $sql .= ' date_start='.(dol_strlen($this->date_start)!=0 ? '\''.$this->db->idate($this->date_start).'\'':'null').', ';
        $sql .= ' date_end='.(dol_strlen($this->date_end)!=0 ? '\''.$this->db->idate($this->date_end).'\'':'null').'';
        $sql.= " WHERE rowid=".$this->id;
        $this->db->begin();
        dol_syslog(__METHOD__);
        $resql = $this->db->query($sql);
        if(! $resql) {
            $error++;$this->errors[] = "Error ".$this->db->lasterror();
        }
        if(! $error) {
            if(! $notrigger) {
                //// Call triggers :$result = $this->call_trigger('MYOBJECT_UPDATE', $user);if($result < 0){ $error++;//Do also what you must do to rollback action if trigger fail}
            }
        }
        // Commit or rollback
        if($error) {
            foreach($this->errors as $errmsg) {
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
        global $langs;
        $result = '';
        if(empty($ref) && $id == 0) {
            if(!empty($this->id)) {
                $id = $this->id;
            } elseif(!empty($this->rowid)) {
                $id = $this->rowid;
            }if(!empty($this->ref)) {
                $ref = $this->ref;
            }
        }
        if($id) {
            $lien = '<a href = "'.DOL_URL_ROOT.'/timesheet/timesheetFavouriteAdmin.php?id='.$id.'&action=view">';
        } elseif(!empty($ref)) {
            $lien = '<a href = "'.DOL_URL_ROOT.'/timesheet/timesheetFavouriteAdmin.php?ref='.$ref.'&action=view">';
        } else{
            $lien = "";
        }
        $lienfin = empty($lien)?'':'</a>';
        $picto = 'timesheet@timesheet';
        if($ref) {
            $label = $langs->trans("Show").': '.$ref;
        } elseif($id) {
            $label = $langs->trans("Show").': '.$id;
        }
        if($withpicto == 1) {
            $result .= ($lien.img_object($label, $picto).$htmlcontent.$lienfin);
        } elseif($withpicto == 2) {
            $result .= $lien.img_object($label, $picto).$lienfin;
        } else{
            $result .= $lien.$htmlcontent.$lienfin;
        }
        return $result;
    }
    /**
     *  Delete object in database
     *
     *  @param  int                $notrigger         0 = launch triggers after, 1 = disable triggers
     *  @return        int                                         <0 if KO, >0 if OK
     */
    public function delete($notrigger = 0)
    {
        $error = 0;
        $this->db->begin();
        if(! $error) {
            if(! $notrigger) {
        //// Call triggers :$result = $this->call_trigger('MYOBJECT_DELETE', $user);if($result < 0){ $error++;//Do also what you must do to rollback action if trigger fail}
            }
        }
        if(! $error) {
            $sql = "DELETE FROM ".MAIN_DB_PREFIX.$this->table_element;
            $sql.= " WHERE rowid=".$this->id;
            dol_syslog(__METHOD__);
            $resql = $this->db->query($sql);
            if(! $resql) {
                $error++;$this->errors[] = "Error ".$this->db->lasterror();
            }
        }
        // Commit or rollback
        if($error) {
            foreach($this->errors as $errmsg) {
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
        $object = new TimesheetFavourite($this->db);
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
        if($result < 0) {
            $this->error = $object->error;
            $error++;
        }
        if(! $error) {
        }
        // End
        if(! $error) {
            $this->db->commit();
            return $object->id;
        } else {
            $this->db->rollback();
            return -1;
        }
    }
    /**
     *        Initialise object with example values
     *        Id must be 0 if object instance is a specimen
     *
     *        @return        void
     */
    public function initAsSpecimen()
    {
        $this->id = 0;
        $this->user = '';
        $this->project = '';
        $this->project_task = '';
        $this->subtask = '';
        $this->date_start = '';
        $this->date_end = '';
    }
}
