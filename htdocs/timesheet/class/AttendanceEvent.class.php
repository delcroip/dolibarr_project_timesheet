<?php
/* Copyright (C) 2007-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2014           Juanjo Menent                <jmenent@2byte.es>
 * Copyright (C) 2018           Patrick DELCROIX     <pmpdelcroix@gmail.com>
 * Copyright (C) ---Put here your own copyright and developer email---
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
 *  \file       dev/attendanceevents/attendanceevent.class.php
 *  \ingroup    timesheet othermodule1 othermodule2
 *  \brief      This file is an example for a CRUD class file(Create/Read/Update/Delete)
 *                                Initialy built by build_class_from_table on 2018-11-05 20:22
 */
// Put here all includes required by your class file
require_once DOL_DOCUMENT_ROOT."/core/class/commonobject.class.php";
//require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
//require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once 'class/TimesheetTask.class.php';
require_once 'core/lib/timesheet.lib.php';
require_once 'class/TimesheetFavourite.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/task.class.php';
$attendanceeventStatusPictoArray = array(-2=> 'status7', 3=> 'statut3', 1=>'statut3', 2=>'statut3', 4=>'statut7');
$attendanceeventStatusArray = array(-2=> $langs->trans('AutoCheckin'), 1=>$langs->trans('Heartbeat'), 2=>$langs->trans('Checkin'), 3=>$langs->trans('Checkout'), 4=>$langs->trans('AutoCheckout'));
/**
 *        Put here description of your class
 */
class Attendanceevent extends CommonObject
{
    /**
     * @var string ID to identify managed object
     */                                //!< To return several error codes(or messages)
    public $element = 'attendanceevent';                        //!< Id that identify managed objects
    /**
     * @var string Name of table without prefix where object is stored
     */
    public $table_element = 'attendance_event';                //!< Name of table without prefix where object is stored
    public $id;
    // BEGIN OF automatic var creation(from db)
        public $date_time_event = '';
        public $event_location_ref;
        public $event_type;
        public $note;
        public $date_modification = '';
        public $userid;
        public $user_modification;
        public $third_party;
        public $task;
        public $project;
        public $token;
        public $status;
        // working var
        public $taskLabel;
        public $projectLabel;
        public $third_partyLabel;
       // private $tasks;// aarray of tasktimesheet
    // END OF automatic var creation
public $date_time_event_start;
    /**
     *  Constructor
     *
     *  @param        DoliDb                $db      Database handler
     *  @param        object          $userid    userid
     */
    public function __construct($db, $userid)
    {
        $this->db = $db;
        $this->userid = $userid;
        return 1;
    }
    /**
     *  Create object into database
     *
     *  @param        User        $user        User that creates
     *  @param  int                $notrigger   0 = launch triggers after, 1 = disable triggers
     *  @return int                         <0 if KO, Id of created object if OK
     */
    public function create($user, $notrigger = 0)
    {
        global $conf, $langs;
                $error = 0;
                // Clean parameters
        $this->cleanParam();
                // Check parameters
                // Put here code to add control on parameters values
        // Insert request
        $sql = "INSERT INTO ".MAIN_DB_PREFIX.$this->table_element."(";
        $sql .= 'date_time_event, ';
        $sql .= 'event_location_ref, ';
        $sql .= 'event_type, ';
        $sql .= 'note, ';
        $sql .= 'fk_userid, ';
        $sql .= 'fk_third_party, ';
        $sql .= 'fk_task, ';
        $sql .= 'fk_project, ';
        $sql .= 'token, ';
        $sql .= 'status, ';
        $sql .= 'date_modification, fk_user_modification';
        $sql .= ") VALUES(";
        $sql .= ' '.(empty($this->date_time_event) || dol_strlen($this->date_time_event) == 0?'NULL':"'".$this->db->idate($this->date_time_event)."'").', ';
        $sql .= ' '.(empty($this->event_location_ref)?'NULL':"'".$this->db->escape($this->event_location_ref)."'").', ';
        $sql .= ' '.(empty($this->event_type)?'NULL':"'".$this->event_type."'").', ';
        $sql .= ' '.(empty($this->note)?'NULL':"'".$this->db->escape($this->note)."'").', ';
        $sql .= ' '.(empty($this->userid)?'NULL':"'".$this->userid."'").', ';
        $sql .= ' '.(empty($this->third_party)?'NULL':"'".$this->third_party."'").', ';
        $sql .= ' '.(empty($this->task)?'NULL':"'".$this->task."'").', ';
        $sql .= ' '.(empty($this->project)?'NULL':"'".$this->project."'").', ';
        $sql .= ' '.(empty($this->token)?'NULL':"'".$this->token."'").', ';
        $sql .= ' '.(empty($this->status)?'NULL':"'".$this->status."'").'';
        $sql .= ', NOW(), \''.$user->id.'\'';
        $sql .= ")";
        $this->db->begin();
        dol_syslog(__METHOD__, LOG_DEBUG);
        $resql = $this->db->query($sql);
        if (! $resql) { $error++;$this->errors[] = "Error ".$this->db->lasterror();}
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
     *  @param        object          $user        user to find the latest event wich is not closed
     *  @param        string          $startToken        token used to find the start event
     *  @return int           <0 if KO, >0 if OK
     */
    public function fetch($id, $user = null, $startToken = '')
    {
        global $langs;
        $sql = "SELECT";
        $sql .= " t.rowid, ";
        $sql .= ' t.date_time_event, ';
        $sql .= ' t.event_location_ref, ';
        $sql .= ' t.event_type, ';
        $sql .= ' t.note, ';
        $sql .= ' t.date_modification, ';
        $sql .= ' t.fk_userid, ';
        $sql .= ' t.fk_user_modification, ';
        $sql .= ' t.fk_third_party, ';
        $sql .= ' t.fk_task, ';
        $sql .= ' t.fk_project, ';
        $sql .= ' t.token, ';
        $sql .= ' t.status, ';
        $sql .= '  st.date_time_event  as date_time_event_start ';
        $sql .= " FROM ".MAIN_DB_PREFIX.$this->table_element." as t";
        $sql .= " LEFT JOIN ".MAIN_DB_PREFIX.$this->table_element
            ." as st ON t.token = st.token AND ABS(st.event_type = 2)";
        $sql .= " WHERE ";
        if (!empty($id))$sql .= "t.rowid = '".$id;
        elseif (!empty($user))$sql .= " t.fk_userid = '".$user->id;
        elseif (!empty($startToken))  $sql .= "  t.token = '".$startToken;
        else{
            $sql .= " t.fk_userid = '".$this->userid;
        }
        $sql .= "' ORDER BY date_time_event DESC" ;
        $this->db->plimit(1, 0);
        dol_syslog(__METHOD__, LOG_DEBUG);
        $resql = $this->db->query($sql);
        if ($resql && $this->db->num_rows($resql)) {
            $obj = $this->db->fetch_object($resql);
            // load the object only if  not an stop event while using the user
            $this->id = $obj->rowid;
            $this->date_time_event = $this->db->jdate($obj->date_time_event);
            $this->event_location_ref = $obj->event_location_ref;
            $this->event_type = $obj->event_type;
            $this->note = $obj->note;
            $this->date_modification = $this->db->jdate($obj->date_modification);
            $this->userid = $obj->fk_userid;
            $this->user_modification = $obj->fk_user_modification;
            $this->third_party = $obj->fk_third_party;
            $this->task = $obj->fk_task;
            $this->project = $obj->fk_project;
            $this->token = $obj->token;
            $this->status = $obj->status;
            $this->date_time_event_start = $this->db->jdate($obj->date_time_event_start);
            $this->db->free($resql);
            $this->getInfo();
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
        $error = 0;
        // Clean parameters
        $this->cleanParam(true);
        // Check parameters
        // Put here code to add a control on parameters values
        // Update request
        $sql = "UPDATE ".MAIN_DB_PREFIX.$this->table_element." SET";
        $sql .= $this->setSQLfields($user);
        $sql .= " WHERE rowid=".$this->id;
        $this->db->begin();
        dol_syslog(__METHOD__, LOG_DEBUG);
        $resql = $this->db->query($sql);
        if (! $resql) { $error++;$this->errors[] = "Error ".$this->db->lasterror();}
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
     *        Return clickable name(with picto eventually)
     *
     *        @param                string                        $htmlcontent                text to show
     *        @param                int                        $id                     Object ID
     *        @param                string                        $ref                    Object ref
     *        @param                int                        $withpicto                0 = _No picto, 1 = Includes the picto in the linkn, 2 = Picto only
     *        @return                string                                                String with URL
     */
    public function getNomUrl($withpicto = 0, $id = 0, $ref = '' )
    {
        global $conf, $langs,$token;
        if (! empty($conf->dol_no_mouse_hover)) $notooltip = 1;// Force disable tooltips
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
        $linkclose = '';
        $label = '';
        //field to show beside the icon
        $label .= $this->getLabel('text');

        //info card more info could be display
        $card = '<u>' . $langs->trans("AttendanceEvent") . '</u>';
        $card .= '<br>';
        if ($ref){
            $card .= $langs->trans("Ref").': '.$ref;
        }else if ($id){
            $card .= $langs->trans("#").': '.$id;
        }
        $morecss = '';
        if (empty($notooltip))
        {
            if (! getConf('MAIN_OPTIMIZEFORTEXTBROWSER') != false)
            {
                $label = $langs->trans("AttendanceEvent");
                $linkclose .= ' alt = "'.dol_escape_htmltag($label, 1).'"';
            }
            $linkclose .= ' title = "'.dol_escape_htmltag($card, 1).'"';
            $linkclose .= ' class = "classfortooltip'.($morecss?' '.$morecss:'').'"';
        } else $linkclose = ($morecss?' class = "'.$morecss.'"':'');
        if ($id) {
            $lien = '<a href = "'.dol_buildpath('/timesheet/AttendanceEventCard.php', 1)
                .'id='.$id.'&view=card"'.$linkclose.'>';
        } elseif (!empty($ref)) {
            $lien = '<a href = "'.dol_buildpath('/timesheet/AttendanceEventCard.php', 1)
                .'?ref='.$ref.'&view=card"'.$linkclose.'>';
        } else{
            $lien = "";
        }
        $lienfin = empty($lien)?'':'</a>';
        $picto = 'generic';
    	if ($withpicto == 1){ 
            $result .= $lien.img_object(''.$picto).$label.$lienfin;
        }else if ($withpicto == 2) {
            $result .= ($lien.img_object($label, $picto).$lienfin);
        }else{  
            $result .= $lien.$label.$lienfin;
        }
        return $result;
    }
    /**
     *  Retourne select libelle du status(actif, inactif)
     *
     *  @param        object                $form          form object that should be created
     *  @param        string                $htmlname      HTML name
     *  @return        string                               html code to select status
     */
    public function selectLibStatut($form, $htmlname = 'Status')
    {
        global $attendanceeventStatusPictoArray, $attendanceeventStatusArray;
        return $form->selectarray($htmlname, $attendanceeventStatusArray, $this->status);
    }
    /**
    *  Retourne le libelle du status(actif, inactif)
    *
    *  @param        int                $mode          0 = libelle long, 1 = libelle court, 2 = Picto + Libelle court, 3 = Picto, 4 = Picto + Libelle long, 5 = Libelle court + Picto
    *  @return        string                               Label of status
    */
   public function getLibStatut($mode = 0)
   {
           return $this->libStatut($this->status, $mode);
   }
    /**
     *  Return the status
     *
     *  @param        int                $status         Id status
     *  @param  int                $mode           0 = long label, 1 = short label, 2 = Picto + short label, 3 = Picto, 4 = Picto + long label, 5 = Short label + Picto, 6 = Long label + Picto
     *  @return string                                Label of status
     */
    public static function libStatut($status, $mode = 0)
    {
        global $langs, $attendanceeventStatusPictoArray, $attendanceeventStatusArray;
        if ($mode == 0) {
            $prefix = '';
            return $langs->trans($attendanceeventStatusArray[$status]);
        }
        if ($mode == 1) {
            return $langs->trans($attendanceeventStatusArray[$status]);
        }
        if ($mode == 2) {
            return img_picto($attendanceeventStatusArray[$status], $attendanceeventStatusPictoArray[$status])
                .' '.$langs->trans($attendanceeventStatusArray[$status]);
        }
        if ($mode == 3) {
            return img_picto($attendanceeventStatusArray[$status], $attendanceeventStatusPictoArray[$status]);
        }
        if ($mode == 4) {
            return img_picto($attendanceeventStatusArray[$status], $attendanceeventStatusPictoArray[$status])
                .' '.$langs->trans($attendanceeventStatusArray[$status]);
        }
        if ($mode == 5) {
            return $langs->trans($attendanceeventStatusArray[$status]).' '
                .img_picto($attendanceeventStatusArray[$status], $attendanceeventStatusPictoArray[$status]);
        }
        if ($mode == 6) {
            return $langs->trans($attendanceeventStatusArray[$status]).' '
                .img_picto($attendanceeventStatusArray[$status], $attendanceeventStatusPictoArray[$status]);
        }
    }
    /**
     *  Delete object in database
     *
    *        @param  User        $user        User that deletes
    *   @param  int                $notrigger         0 = launch triggers after, 1 = disable triggers
     *  @return        int                                         <0 if KO, >0 if OK
     */
    public function delete($user, $notrigger = 0)
    {
        //global $conf, $langs;
        if (empty($user)) return -1;
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
            if (! $resql) 
            { 
                $error++;$this->errors[] = "Error ".$this->db->lasterror();
            } elseif ($this->db->affected_rows($resql) == 0) 
            {
                $error++;$this->errors[] = "Item no found in database";
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
        $object = new Attendanceevent($this->db, $this->userid);
        $this->db->begin();
        // Load source object
        $object->fetch($fromid);
        $object->id = 0;
        $object->statut = 0;
        // Clear fields
        // ...
        // Create clone
        $result = $object->create($user);
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
    /**
     *        Initialise object with example values
     *        Id must be 0 if object instance is a specimen
     *
     *        @return        void
     */
    public function initAsSpecimen()
    {
        $this->id = 0;
        $this->date_time_event = '';
        $this->date_time_event_start = '';
        $this->event_location_ref = '';
        $this->event_type = 0;
        $this->note = '';
        $this->date_modification = '';
        $this->userid = '';
        $this->user_modification = '';
        $this->third_party = '';
        $this->task = '';
        $this->project = '';
        $this->third_partyLabel = '';
        $this->taskLabel = '';
        $this->projectLabel = '';
        $this->token = '';
        $this->status = '';
    }
    /**
     *        will clean the parameters
     *
     *
     *        @return        void
     */
    public function cleanParam()
    {
        if (!empty($this->date_time_event))
            $this->date_time_event = trim($this->date_time_event);
        if (!empty($this->event_location_ref))
            $this->event_location_ref = trim($this->event_location_ref);
        if (!empty($this->event_type))
            $this->event_type = trim($this->event_type);
        if (!empty($this->note))
            $this->note = trim($this->note);
        if (!empty($this->date_modification))
            $this->date_modification = trim($this->date_modification);
        if (!empty($this->userid))
            $this->userid = trim($this->userid);
        if (!empty($this->user_modification))
            $this->user_modification = trim($this->user_modification);
        if (!empty($this->third_party))
            $this->third_party = trim($this->third_party);
        if (!empty($this->task))
            $this->task = trim($this->task);
        if (!empty($this->project))
            $this->project = trim($this->project);
        if (!empty($this->token))
            $this->token = trim($this->token);
        if (!empty($this->status))
            $this->status = trim($this->status);
    }
     /**
     *        will create the sql part to update the parameters
     *
     *  @param USER $user user that will update
     *        @return        string
     */
    public function setSQLfields($user)
    {
        $sql = '';
        $sql .= ' date_time_event='
            .(dol_strlen($this->date_time_event)!=0 ? "'"
            .$this->db->idate($this->date_time_event)."'":'null').', ';
        $sql .= ' event_location_ref='
            .(empty($this->event_location_ref)!=0 ? 'null':"'"
            .$this->db->escape($this->event_location_ref)."'").', ';
        $sql .= ' event_type='.(empty($this->event_type)!=0 ? 'null':"'".$this->event_type."'").', ';
        $sql .= ' note='.(empty($this->note)!=0 ? 'null':"'".$this->db->escape($this->note)."'").', ';
        $sql .= ' date_modification = NOW(), ';
        $sql .= ' fk_userid='.(empty($this->userid)!=0 ? 'null':"'".$this->userid."'").', ';
        $sql .= ' fk_user_modification = "'.$user->id.'", ';
        $sql .= ' fk_third_party='.(empty($this->third_party)!=0 ? 'null':"'".$this->third_party."'").', ';
        $sql .= ' fk_task='.(empty($this->task)!=0 ? 'null':"'".$this->task."'").', ';
        $sql .= ' fk_project='.(empty($this->project)!=0 ? 'null':"'".$this->project."'").', ';
        $sql .= ' token='.(empty($this->token)!=0 ? 'null':"'".$this->token."'").', ';
        $sql .= ' status='.(empty($this->status)!=0 ? 'null':"'".$this->status."'").'';
        return $sql;
    }
    /**
     *  Will start a new attendance and return the result in json
     *
     *  @param  USER                $user                 user object
     *  @param  string                $json           json recieve along the start request(to stop the current task)
     *  @param  int                $customer         customer id on which the attendance is register
     *  @param  int                $project         project id on which the attendance is register
     *  @param  int                $task            task id on which the attendance is register
     *  @return        json                                 return the json of the object started
     */
    public function ajaxStart($user, $json = '', $customer = '', $project = '', $task = '')
    {
        if (empty($task) && empty($project) && empty($customer)) 
            return '{"errorType":"startError", "error":"no event to start"}';
        $location_ref = '';
        //load old if any
        if (!empty($json)) {
            $this->unserialize($json, 1);
            //save the location ref
            $location_ref = $this->event_location_ref;
            //close the most recent one if any
            $this->ajaxStop($user, $json, true);
            //$this->status = 
        }
//erase the data
        $status = $this->status;
        $tmpUserid = $this->userid;
        $this->initAsSpecimen();
        $this->userid = $tmpUserid;
        //$this->userid = $user->id;
        //load the data of the new
        if (!empty($task)) {
            $this->task = trim($task);
            $this->getInfo();
        }
        if (!empty($project)) $this->project = trim($project);
        if (!empty($customer)) $this->third_party = trim($customer);
        $this->token = getToken();
        $this->event_type = EVENT_START;
        $this->date_time_event = time()+1;
        $this->date_time_event_start = $this->date_time_event;
        $this->event_location_ref = $location_ref;
        $this->create($user);
        //$this->getInfo();
        $this->status = $status;
        return $this->serialize(2);
    }
    /**
     *  Will stop the  attendance and return the result in json
     *
     *  @param USER $user user that will update
     *  @param  string                $json         json of the request
     *  @param bool $auto       auto stop, or triggered by user
     *  @return        int                        <0 if KO, >0 if OK
     */
    public function ajaxStop($user, $json = '', $auto = false)
    {
        global $conf, $langs;
        $location_ref = '';
        $note = '';
        $tokenJson = '';
        $retJson = '';
        $arrayRes = array();
        if (!empty($json)) {
            $this->unserialize($json, 1);
            $this->status = "";
            $location_ref = $this->event_location_ref;
            $note = $this->note;
            $tokenJson = $this->token;
            $this->fetch('', '', $tokenJson);
        } else {
             $this->fetch('');
        }
        $ret = 0;
        $tokenDb = $this->token;
        if (empty($tokenDb)) {  // 00 01 no db record found by token or user
            $this->initAsSpecimen();
            if (!$auto){
                $arrayRes["NoActiveEvent"]++ ;
                $this->status = TimesheetsetEventMessage($arrayRes, true);
            }
            // AUTO START ?
        } elseif ($this->event_type >= EVENT_STOP) { // found but already stopped
            $this->initAsSpecimen();
            $arrayRes["EventNotActive"]++;
            $this->status = TimesheetsetEventMessage($arrayRes, true);
        } else{// 11 && 10 found and active
            if (!empty($tokenJson)) { //11
                $this->event_location_ref = $location_ref;
                $this->note = $note;
            }
            $this->event_type = EVENT_STOP;
            $this->date_time_event = time();
            $duration = $this->date_time_event-$this->date_time_event_start;
            //if the max time is breach
            if ((getConf('TIMESHEET_EVENT_MAX_DURATION')>0 &&
                $duration>getConf('TIMESHEET_EVENT_MAX_DURATION',0)*3600))
                {
                // put the max time per default
                    $this->date_time_event = 
                        getConf('TIMESHEET_EVENT_DEFAULT_DURATION',0)*3600
                        +$this->date_time_event_start;
                    if (empty($tokenJson) && $auto) { // if it's auto close but without json sent
                        $this->event_type = EVENT_AUTO_STOP;
                    }
            }else { //there is a start time and it's in the acceptable limit
                if ($duration < getConf('TIMESHEET_EVENT_MIN_DURATION',0)){
                    $this->date_time_event = $this->date_time_event_start 
                        + getConf('TIMESHEET_EVENT_MIN_DURATION');
                }
                $this->event_type = EVENT_STOP;
            }
            $ret = $this->create($user);
            if ($ret>0 && getConf('TIMESHEET_EVENT_NOT_CREATE_TIMESPENT') == 0) {
                $this->createTimeSpend($user, $tokenDb);
            } else if ($ret<0) {
                $this->initAsSpecimen();
                $arrayRes = array();
                $this->status = $arrayRes["DbError"]++ ;
                $this->status = TimesheetsetEventMessage($arrayRes, true);
            }
        }
        return $this->serialize(2);
    }

    /**
     *  Will register an hearbear for an attendance and return the result in json
     *
     *  @param USER $user user that will update
     *  @param  string                $json         json of the request
     *  @return        int                                         <0 if KO, >0 if OK
     */
    public function ajaxHeartbeat($user, $json)
    {
        global $conf, $langs;
        $location_ref = '';
        $note = '';
        $tokenJson = '';
        $arrayRes = array();
        $retJson = '';
        if (!empty($json)) {
            $this->unserialize($json, 1);
            $location_ref = $this->event_location_ref;
            $note = $this->note;
            $tokenJson = $this->token;
        }
        $this->fetch('');
        $tokenDb = $this->token;
        if ((empty($tokenJson) && empty($tokenDb))||
                (!empty($tokenDb) && $this->event_type >= EVENT_STOP))
        {
            //00
            $this->initAsSpecimen();
            if ($this->userid)$arrayRes["NoActiveEvent"]++ ;
            $this->status = TimesheetsetEventMessage($arrayRes, true);
        } elseif (empty($tokenDb) && !empty($tokenJson)) { // json recieved with token //01
            $arrayRes["EventNotActive"]++;
            $this->status = TimesheetsetEventMessage($arrayRes, true);
        } elseif (!empty($tokenDb)) {
            // 11 && 10
            if (!empty($tokenJson)) {
                //11
                $this->event_location_ref = $location_ref;
                $this->note = $note;
            } else{
                // info not already loaded 10
                $this->getInfo();
            }
            // update the required fields
            $this->date_time_event = time();
            if ($this->event_type!=EVENT_HEARTBEAT) {
                // create an heartbeat only if there is none
                $this->event_type = EVENT_HEARTBEAT;
                $this->create($user);
            } else {
                $this->update($user);
            }
        }
        return $this->serialize(2);
    }
 /** create timespend on the user
  * @param USER $user user objuect
  * @param string $token   token
  * @return null
  */
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
    global $langs,$token;
    $form = new Form($this->db);
    $HTML = '<form id = "timesheetForm" name = "OtherUser" action="?action=getOtherTs&token='.$token.'" method = "POST">';
    if (!$admin) {
        $HTML .= $form->select_dolusers($selected, 'userid', 0, null, 0, $idsList);
    } else{
         $HTML .= $form->select_dolusers($selected, 'userid');
    }
    $HTML .= '<input type = "hidden" id="csrf-token" name = "token" value = "'.$this->token.'"/>';

    $HTML .= '<input type = "submit" value = "'.$langs->trans('Submit').'"/></form> ';


    return $HTML;
}

public function createTimeSpend($user, $token = '')
{
    //if (empty($token))$token = $this->token;
    if (!empty($token)) {
        $this->fetch('', '', $token);
        if ($this->event_type == EVENT_STOP && $this->task>0) {
            $start = strtotime("midnight", (int) $this->date_time_event);
            $end = strtotime("tomorrow", (int) $this->date_time_event)-1;
            $duration = $this->date_time_event -$this->date_time_event_start;
            $tta = new TimesheetTask($this->db, $this->task);
            $tta->getActuals($start, $end, $this->userid);
            $arrayRes = $tta->saveTaskTime($user, $duration, $this->note, 0, true);
            $this->status = TimesheetsetEventMessage($arrayRes, true);
            if (is_array($arrayRes) && array_sum($arrayRes)-$arrayRes['updateError']>0) 
                $tta->updateTimeUsed();
            //TimesheetsetEventMessage($arrayRes);
        }
    }
}

    /** Function generate the HTML code to use the clock
     *
     * @param string[] $headers header to display
     * @param    string              $token           CSRF token
     * @param int $userid   user id
     * @return string   HTML code
     */
    public function printHTMLTaskList($headers, $userid = '')
    {
        $tasksList = $this->fetchTasks($userid);
        $html = '';
        if (is_array($tasksList))foreach ($tasksList as $task) {
            $html .= $task->getAttendanceLine($headers, ($task->id == $this->task));
        }
        return $html;
    }
    /** Function generate the HTML code to use the clock
    *  @return     html code                                       result
    */
    public function printHTMLClock()
    {
        global $langs;
        print '<div>';
        print '<div style = "width:50px;height:60px;float:left;vertical-align:middle" >';
        print '<img height = "64px" id = "mainPlayStop" src = "img/'
            .(($this->id == 0)?'play-arrow':'stop-square');
        print '.png" onClick = startStop(event,'.$this->userid
            .',null) style = "cursor:pointer;vertical-align:middle">  ';
        print '</div>';
        print '<div style = "width:40%;height:60px;float:left" >';
        print '<textarea name = "eventNote" id = "eventNote" style = "width:80%;height:100%"></textarea>';
        print '</div>';
        print '<div style = "width:40%;float:left">';
        print '<span id = "stopwatch"></span>';
        print '<div>'.$langs->trans('Customer').': <span id = "customer">&nbsp;</span></div>';
        print '<div>'.$langs->trans('Project').': <span  id = "project">&nbsp;</span></div>';
        print '<div>'.$langs->trans('Task').': <span  id = "task">&nbsp;</span></div>';
        print '</div>';
        print '</div>';
    }
 /**
 * function to genegate the timesheet tab
 *
 *  @param    int               $userid                   user id to fetch the timesheets
 *  @param    dataetime               $date                   user id to fetch the timesheets
 *  @return     array(string)                                             array of timesheet(serialized)
 */
 public function fetchTasks($userid = '', $date = '')
 {
    global $conf;

    if (empty($date))$date = time();
    $staticWhiteList = new TimesheetFavourite($this->db);
    $whiteList = $staticWhiteList->fetchUserList($userid, $date, $date + SECINDAY);
    if ($userid == '') {
        $userid = $this->userid;
    }
    $this->userid = $userid;
    $datestart = strtotime('today midnight', $date);
    $datestop = strtotime(' tomorrow midnight', $date) -1;
     $tasksList = array();
    $sql = 'SELECT DISTINCT element_id as taskid, prj.fk_soc, prj.ref, tsk.ref';
    $sql .= " FROM ".MAIN_DB_PREFIX."element_contact as ec";
    $sql .= ' LEFT JOIN '.MAIN_DB_PREFIX
        .'c_type_contact as ctc ON(ctc.rowid = ec.fk_c_type_contact  AND ctc.active = \'1\') ';
    $sql .= ' JOIN '.MAIN_DB_PREFIX.'projet_task as tsk ON tsk.rowid = ec.element_id ';
    $sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'projet as prj ON prj.rowid = tsk.fk_projet ';
    $sql .= " WHERE ((ec.fk_socpeople = '".$userid."' AND ctc.element = 'project_task') ";
    // SHOW TASK ON PUBLIC PROEJCT
    if (getConf('TIMESHEET_ALLOW_PUBLIC') == '1') {
        $sql .= '  OR  prj.public =  \'1\')';
    }else{
        $sql .= ' )';
    }
    if (getConf('TIMESHEET_HIDE_DRAFT') == '1') {
        $sql .= ' AND prj.fk_statut =  \'1\'';
    }else{
        $sql .= ' AND prj.fk_statut in (\'0\', \'1\')';
    }
    $sql .= ' AND (prj.datee >= \''.$this->db->idate($datestart).'\' OR prj.datee IS NULL)';
    $sql .= ' AND (prj.dateo <= \''.$this->db->idate($datestop).'\' OR prj.dateo IS NULL)';
    $sql .= ' AND (tsk.datee >= \''.$this->db->idate($datestart).'\' OR tsk.datee IS NULL)';
    $sql .= ' AND (tsk.dateo <= \''.$this->db->idate($datestop).'\' OR tsk.dateo IS NULL)';
    $sql .= '  ORDER BY prj.fk_soc, prj.ref, tsk.ref ';
    dol_syslog(__METHOD__, LOG_DEBUG);
    $resql = $this->db->query($sql);
    if ($resql) {
        $this->taskTimesheet = array();
        $num = $this->db->num_rows($resql);
        $i = 0;
        // Loop on each record found, so each couple (project id, task id)
        while($i < $num)
        {
            $error = 0;
            $obj = $this->db->fetch_object($resql);
            $tasksList[$i] = new TimesheetTask($this->db);
            $tasksList[$i]->id = $obj->taskid;
            $tasksList[$i]->userId = $this->userid;
            $tasksList[$i]->getTaskInfo();
            $tasksList[$i]->listed = (is_array($whiteList) && array_key_exists($obj->taskid, $whiteList) )?$whiteList[$obj->taskid]:null;
            $i++;
        }
        $this->db->free($resql);
        $i = 0;
        return $tasksList;
    } else {
        dol_print_error($this->db);
        return -1;
    }
 }
/**
* function to save attendance event as a string
* @param    int     $mode   0=>serialize, 1=> json_encode, 2 => json_encode PRETTY PRINT
* @return   string       serialized object
*/
public function serialize($mode = 0)
{
    $ret = '';
    $array = array();
    $array['id'] = $this->id;
    $array['date_time_event'] = $this->date_time_event;
    $array['date_time_event_start'] = $this->date_time_event_start;
    $array['event_location_ref'] = $this->event_location_ref;
    $array['event_type'] = $this->event_type;
    $array['note'] = $this->note;
    $array['date_modification'] = $this->date_modification;
    $array['userid'] = $this->userid;
    $array['user_modification'] = $this->user_modification;
    $array['third_party'] = $this->third_party;
    $array['task'] = $this->task;
    $array['project'] = $this->project;
    $array['third_partyLabel'] = $this->third_partyLabel;
    $array['taskLabel'] = $this->taskLabel;
    $array['projectLabel'] = $this->projectLabel;
    $array['token'] = $this->token;
    $array['status'] = $this->status;
    $array['processedTime'] = time();
    // working var
    //$array[''] = $this->tasks;// aarray of tasktimesheet
    switch($mode) {
        default:
        case 0:
            $ret = serialize($array);
            break;
        case 1:
            $ret = json_encode($array);
            break;
        case 2:
            $ret = json_encode($array, JSON_PRETTY_PRINT);
            break;
    }
      return $ret;
}
     /** function to load a skeleton as a string
     * @param   string    $str   serialized object
     * @param    int     $mode   0=>serialize, 1=> json_encode, 2 => json_encode PRETTY PRINT
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
    /*fucntion to get the labels
     *
     */
    public function getInfo()
    {
        if (!empty($this->task)) {
            $staticTask = new TimesheetTask($this->db);
            $staticTask->id = ($this->task);
            $staticTask->userId = ($this->userid);
            //$staticTask->fetch($this->task);
            $staticTask->getTaskInfo();
            $this->project = $staticTask->fk_project;
            $this->taskLabel = $staticTask->description;
            $this->projectLabel = $staticTask->ProjectTitle;
            $this->third_party = $staticTask->companyId;
            $this->third_partyLabel = $staticTask->companyName;
        } else {
            if (!empty($this->project) && empty($this->projectLabel)) {
                $this->projectLabel = print_sellist(array('table'=>"projet", 
                    'keyfield'=> 'rowid', 'fields'=>'title'), $this->project);
            }
            if (!empty($this->third_party) && empty($this->third_partyLabel)) {
                $this->third_partyLabel = print_sellist(array('table'=>"societe", 
                    'keyfield'=> 'rowid', 'fields'=>'nom'), $this->third_party);
            }
        }
    }
            /**
     *  Function to generate a sellist
     *  @param string $htmlname name of the sellist input
     *  @param int $selected rowid to be preselected
     *  @return string HTML select list
     */
    
    Public function sellist($htmlname = '', $selected = ''){    
        $sql = array('table' => $this->table_element , 'keyfield' => 't.rowid', 
            'fields' => $this->getLabel('sql'), 'join' =>  $this->getLabel('join'), 
            'where' => '', 'tail' => '');
        $html = array('name' => (($htmlname == '')?'AttendanceEvent':$htmlname), 
            'class' => '', 'otherparam' => '', 'ajaxNbChar' => '', 'separator' => '-');
        $addChoices = null;
		return select_sellist($sql, $html, $selected, $addChoices );
    }

    /**      function to define display of the object
     * @param string $type type of return text or sql
     * @return string Label
     */
    public function getLabel($type = 'text'){
        $ret = '';
        switch ($type){
            case 'sql':
                $ret = "t.fk_userid, t.date_time_event";
            break;
            case 'join':
                $ret = "";
            break;                
            case 'text':
            default:
                $ret = $this->userid.': '.$this->date_time_event;
            break;

        } 
        return $ret;
    }

}
