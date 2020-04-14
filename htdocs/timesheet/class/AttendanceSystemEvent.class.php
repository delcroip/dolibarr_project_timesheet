<?php
/* Copyright (C) 2007-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2014	   Juanjo Menent		<jmenent@2byte.es>
 * Copyright (C) 2018	   Patrick DELCROIX     <pmpdelcroix@gmail.com>
 * Copyright (C) ---Put here your own copyright and developer email---
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
 *  \file       dev/attendancesystemevents/attendancesystemevent.class.php
 *  \ingroup    timesheet othermodule1 othermodule2
 *  \brief      This file is an example for a CRUD class file (Create/Read/Update/Delete)
 *				Initialy built by build_class_from_table on 2020-03-28 19:05
 */

// Put here all includes required by your class file
require_once DOL_DOCUMENT_ROOT."/core/class/commonobject.class.php";
//require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
//require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
//require_once(DOL_DOCUMENT_ROOT.'/projet/class/project.class.php');
require_once 'core/lib/generic.lib.php';
$attendancesystemeventStatusPictoArray = array(0 => 'statut7', 1 => 'statut3', 2 => 'statut8', 3 => 'statut4', 4 => 'statut4', 5 => 'statut4', 6 => 'statut4');
$attendancesystemeventStatusArray = array(0 => 'loaded', 1 => 'in', 2 => 'out', 3 => 'ErrorSingle' , 4 => 'ErrorDouble', 5 => 'ErrorIn', 6 => 'ErrorOut');
/**
 *	Put here description of your class
 */
class AttendanceSystemEvent extends CommonObject
{
    /**
     * @var string ID to identify managed object
     */				//!< To return several error codes (or messages)
    public $element = 'attendancesystemevent';			//!< Id that identify managed objects
    /**
     * @var string Name of table without prefix where object is stored
     */    
    public $table_element = 'attendance_system_event';		//!< Name of table without prefix where object is stored

    public $id;
    // BEGIN OF automatic var creation
    
	public $date_time_event = '';
	public $attendance_system;
	public $date_modification = '';
    public $attendance_system_user;
    public $user = '';
    public $status;
    public $state;
    public $event_type;
    public $attendance_event;

    
    // END OF automatic var creation


    /**
     *  Constructor
     *
     *  @param	DoliDb		$db      Database handler
     */
    function __construct($db)
    {
        $this->db = $db;
        return 1;
    }


    /**
     *  Create object into database
     *
     *  @param	User	$user        User that creates
     *  @param  int		$notrigger   0=launch triggers after, 1=disable triggers
     *  @return int      		   	 <0 if KO, Id of created object if OK
     */
    function create($user, $notrigger = 0)
    {
    	global $conf, $langs;
		$error = 0;

		// Clean parameters
        $this->cleanParam();

		// Check parameters
		// Put here code to add control on parameters values

        // Insert request
        $sql = "INSERT INTO ".MAIN_DB_PREFIX.$this->table_element."(";
        
		$sql .= 'date_time_event,';
		$sql .= 'fk_attendance_system,';
        $sql .= 'fk_attendance_system_user,';
        $sql .= 'fk_attendance_event,';
        $sql .= 'fk_user,';
        $sql .= 'event_type,';
		$sql .= 'status';
		$sql .= 'state';
        
        $sql .= ") VALUES (";
        
		$sql .= ' '.(empty($this->date_time_event) || dol_strlen($this->date_time_event) == 0?'NULL':"'".$this->db->idate($this->date_time_event)."'").',';
		$sql .= ' '.(empty($this->attendance_system)?'NULL':"'".$this->attendance_system."'").',';
        $sql .= ' '.(empty($this->attendance_system_user)?'NULL':"'".$this->attendance_system_user."'").',';
        $sql .= ' '.(empty($this->attendance_event)?'NULL':"'".$this->attendance_event."'").',';
        $sql .= ' '.(empty($this->user)?'NULL':"'".$this->user."'").',';
        $sql .= ' '.(empty($this->event_type)?'1':"'".$this->event_type."'").',';
		$sql .= ' '.(empty($this->status)?'NULL':"'".$this->status."'").'';
		$sql .= ' '.(empty($this->state)?'NULL':"'".$this->state."'").'';
        
        $sql .= ")";

        $this->db->begin();

        dol_syslog(__METHOD__, LOG_DEBUG);
        $resql = $this->db->query($sql);
    	if (! $resql) { $error++; $this->errors[]="Error ".$this->db->lasterror(); }

        if (! $error)
        {
            $this->id = $this->db->last_insert_id(MAIN_DB_PREFIX.$this->table_element);

            if (! $notrigger)
            {
            // Uncomment this and change MYOBJECT to your own tag if you
            // want this action calls a trigger.

            //// Call triggers
            //$result = $this->call_trigger('MYOBJECT_CREATE',$user);
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
                $this->error .= ($this->error?', '.$errmsg:$errmsg);
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
    function fetch($id,$ref = '')
    {
    	global $langs;
        $sql = "SELECT";
        $sql .= " t.rowid,";
        
		$sql .= ' t.date_time_event,';
		$sql .= ' t.fk_attendance_system,';
		$sql .= ' t.date_modification,';
        $sql .= ' t.fk_attendance_system_user,';
        $sql .= ' t.fk_attendance_event,';
        $sql .= ' t.fk_user,';
        $sql .= ' t.event_type,';
		$sql .= ' t.status';
		$sql .= ' t.state';

        
        $sql .= " FROM ".MAIN_DB_PREFIX.$this->table_element." as t";
        if ($ref) $sql .= " WHERE t.ref = '".$ref."'";
        else $sql .= " WHERE t.rowid = ".$id;
    	dol_syslog(__METHOD__, LOG_DEBUG);
        $resql = $this->db->query($sql);
        if ($resql)
        {
            if ($this->db->num_rows($resql))
            {
                $obj = $this->db->fetch_object($resql);
                $this->id = $obj->rowid;
                $this->date_time_event = $this->db->jdate($obj->date_time_event);
                $this->attendance_system = $obj->fk_attendance_system;
                $this->date_modification = $this->db->jdate($obj->date_modification);
                $this->attendance_system_user = $obj->fk_attendance_system_user;
                $this->attendance_event = $obj->fk_attendance_event;
                $this->user = $obj->fk_user;
                $this->event_type = $obj->event_type;
                $this->status = $obj->status;       
                $this->state = $obj->state;       
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
    function update($user, $notrigger = 0)
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
    	if (! $resql) { $error++; $this->errors[]="Error ".$this->db->lasterror(); }
            if (! $error)
            {
                if (! $notrigger)
                {
            // Uncomment this and change MYOBJECT to your own tag if you
            // want this action calls a trigger.

            //// Call triggers
            //$result = $this->call_trigger('MYOBJECT_MODIFY',$user);
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
                    $this->error .= ($this->error?', '.$errmsg:$errmsg);
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
     *	Return clickable name (with picto eventually)
     *
     *	@param		int			$withpicto		0=_No picto, 1=Includes the picto in the linkn, 2=Picto only
     *	@param		int			$id                     Object ID
     *	@param		string			$ref                    Object ref
     *	@return		string						String with URL
     */
    function getNomUrl($withpicto, $id = 0, $ref = '')
    {
	global $conf, $langs;


        if (! empty($conf->dol_no_mouse_hover)) $notooltip = 1;   // Force disable tooltips
    	$result = '';
        if(empty($ref) && $id == 0){
            if(isset($this->id))  {
                $id = $this->id;
            }else if (isset($this->rowid)){
                $id = $this->rowid;
            }if(isset($this->ref)){
                $ref = $this->ref;
            }
        }
        $linkclose = '';
        $label = '';
        //field to show beside the icon
        $label .= 'field1: '.$this->field1;

        //info card more info could be display
        $card = '<u>' . $langs->trans("AttendanceSystemEvent") . '</u>';
        $card .= '<br>';
        $card .= $langs->trans("#").': '.$id;
        $card .= '<br>';
        $card .= $this->getLibStatut(2);

        if (empty($notooltip))
        {
            if (! empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER))
            {
                $label = $langs->trans("AttendanceSystemEvent");
                $linkclose .= ' alt = "'.dol_escape_htmltag($label, 1).'"';
            }
            $linkclose .= ' title = "'.dol_escape_htmltag($card, 1).'"';
            $linkclose .= ' class = "classfortooltip'.($morecss?' '.$morecss:'').'"';
        }else $linkclose = ($morecss?' class = "'.$morecss.'"':'');
        
        if($id){
            $lien = '<a href = "'.dol_buildpath('/timesheet/AttendanceSystemEventCard.php',1).'?id='.$id.'&action=view"'.$linkclose.'>';
        }else if (!empty($ref)){
            $lien = '<a href = "'.dol_buildpath('/timesheet/AttendanceSystemEventCard.php',1).'?ref='.$ref.'&action=view"'.$linkclose.'>';
        }else{
            $lien = "";
        }
        $lienfin = empty($lien)?'':'</a>';

    	$picto = 'generic';
 
        
        
        
    	if ($withpicto == 1){ 
            $result .= ($lien.img_object($label,$picto).$htmlcontent.$lienfin);
        }else if ($withpicto == 2) {
            $result .= $lien.img_object($label,$picto).$lienfin;
        }else{  
            $result .= $lien.$label.$lienfin;
        }
    	return $result;
    }  
     /**
	 *  Retourne select libelle du status (actif, inactif)
	 *
	 *  @param	object 		$form          form object that should be created	
      *  *  @return	string 			       html code to select status
	 */
	function selectLibStatut($form, $htmlname = 'Status')
	{
            global $attendancesystemeventStatusPictoArray,$attendancesystemeventStatusArray;
            return $form->selectarray($htmlname, $attendancesystemeventStatusArray, $this->status);
	}   
    /**
	 *  Retourne le libelle du status (actif, inactif)
	 *
	 *  @param	int		$mode          0 = libelle long, 1 = libelle court, 2 = Picto + Libelle court, 3 = Picto, 4 = Picto + Libelle long, 5 = Libelle court + Picto
	 *  @return	string 			       Label of status
	 */
	function getLibStatut($mode = 0)
	{
		return $this->LibStatut($this->status,$mode);
	}
	/**
	 *  Return the status
	 *
	 *  @param	int		$status        	Id status
	 *  @param  int		$mode          	0 = long label, 1 = short label, 2 = Picto + short label, 3 = Picto, 4 = Picto + long label, 5 = Short label + Picto, 6 = Long label + Picto
	 *  @return string 			       	Label of status
	 */
	static function LibStatut($status,$mode = 0)
	{
		global $langs,$attendancesystemeventStatusPictoArray,$attendancesystemeventStatusArray;
		if ($mode == 0)
		{
			$prefix = '';
			return $langs->trans($attendancesystemeventStatusArray[$status]);
		}
		if ($mode == 1)
		{
			return $langs->trans($attendancesystemeventStatusArray[$status]);
		}
		if ($mode == 2)
		{
			 return img_picto($attendancesystemeventStatusArray[$status],$attendancesystemeventStatusPictoArray[$status]).' '.$langs->trans($attendancesystemeventStatusArray[$status]);
		}
		if ($mode == 3)
		{
			 return img_picto($attendancesystemeventStatusArray[$status],$attendancesystemeventStatusPictoArray[$status]);
		}
		if ($mode == 4)
		{
			 return img_picto($attendancesystemeventStatusArray[$status],$attendancesystemeventStatusPictoArray[$status]).' '.$langs->trans($attendancesystemeventStatusArray[$status]);
		}
		if ($mode == 5)
		{
			 return $langs->trans($attendancesystemeventStatusArray[$status]).' '.img_picto($attendancesystemeventStatusArray[$status],$attendancesystemeventStatusPictoArray[$status]);
		}
		if ($mode == 6)
		{
			 return $langs->trans($attendancesystemeventStatusArray[$status]).' '.img_picto($attendancesystemeventStatusArray[$status],$attendancesystemeventStatusPictoArray[$status]);
		}
	}

    /**
     *  Delete object in database
     *
    *	@param  User	$user        User that deletes
    *   @param  int		$notrigger	 0 = launch triggers after, 1 = disable triggers
     *  @return	int					 <0 if KO, >0 if OK
     */
    function delete($user, $notrigger = 0)
    {
        global $conf, $langs;
        $error = 0;
        $this->db->begin();
        if (! $error)
        {
            if (! $notrigger)
            {
        // Uncomment this and change MYOBJECT to your own tag if you
        // want this action calls a trigger.
        //// Call triggers
        //$result = $this->call_trigger('MYOBJECT_DELETE',$user);
        //if ($result < 0) { $error++; //Do also what you must do to rollback action if trigger fail}
        //// End call triggers
            }
        }
        if (! $error)
        {
        $sql = "DELETE FROM ".MAIN_DB_PREFIX.$this->table_element;
        $sql .= " WHERE rowid = ".$this->id;

        dol_syslog(__METHOD__, LOG_DEBUG);
        $resql = $this->db->query($sql);
        if (! $resql) { $error++; $this->errors[] = "Error ".$this->db->lasterror(); }
        else if ($this->db->affected_rows($resql) == 0){$error++;$this->errors[] = "Item no found in database"; }

        }

// Commit or rollback
        if ($error)
        {
            foreach($this->errors as $errmsg)
            {
                dol_syslog(__METHOD__." ".$errmsg, LOG_ERR);
                $this->error .= ($this->error?', '.$errmsg:$errmsg);
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
        $error = 0;
        $object = new AttendanceSystemEvent($this->db);
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
        if ($result < 0)
        {
            $this->error = $object->error;
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

    /**
     *	Initialise object with example values
     *	Id must be 0 if object instance is a specimen
     *
     *	@return	void
     */
    function initAsSpecimen()
    {
        $this->id = 0;
        $this->date_time_event = '';
        $this->attendance_system = '';
        $this->date_modification = '';
        $this->attendance_system_user = '';
        $this->attendance_event = '';
        $this->user = '';
        $this->event_type = 1;
        $this->status = '';
    }
    /**
     *	will clean the parameters
     *	
     *
     *	@return	void
     */       
    function cleanParam(){
        
			if (!empty($this->date_time_event)) $this->date_time_event = trim($this->date_time_event);
			if (!empty($this->attendance_system)) $this->attendance_system = trim($this->attendance_system);
			if (!empty($this->date_modification)) $this->date_modification = trim($this->date_modification);
            if (!empty($this->attendance_system_user)) $this->attendance_system_user = trim($this->attendance_system_user);
            if (!empty($this->attendance_event)) $this->attendance_event = trim($this->attendance_event);
            if (!empty($this->user)) $this->user = trim($this->user);
			if (!empty($this->event_type)) $this->event_type = trim($this->event_type);
			if (!empty($this->status)) $this->status = trim($this->status);

        
    }
     /**
     *	will create the sql part to update the parameters
     *	
     *
     *	@return	void
     */    
    function setSQLfields($user){
        $sql = '';
        
		$sql .= ' date_time_event = '.(dol_strlen($this->date_time_event) != 0 ? "'".$this->db->idate($this->date_time_event)."'":'null').',';
		$sql .= ' fk_attendance_system = '.(empty($this->attendance_system) != 0 ? 'null':"'".$this->attendance_system."'").',';
		$sql .= ' date_modification = NOW() ,';
		$sql .= ' fk_attendance_system_user = '.(empty($this->attendance_system_user) != 0 ? 'null':"'".$this->attendance_system_user."'").',';
		$sql .= ' fk_attendance_event = '.(empty($this->attendance_event) != 0 ? 'null':"'".$this->attendance_event."'").',';
		$sql .= ' fk_user = '.(empty($this->user) != 0 ? 'null':"'".$this->user."'").',';
		$sql .= ' event_type = '.(empty($this->event_type) != 0 ? 'null':"'".$this->event_type."'").',';
		$sql .= ' status = '.(empty($this->status) != 0 ? 'null':"'".$this->status."'").'';

        
        return $sql;
    }
    /*
    * function to save a attendancesystemevent as a string
    * @param    int     $mode   0 => serialize, 1 => json_encode, 2 => json_encode PRETTY PRINT 
    * @return   string       serialized object
    */
    public function serialize($mode = 0){
		$ret = '';
		$array = array();
		$array['id'] = $this->id;
		$array['date_time_event'] = $this->date_time_event;
		$array['attendance_system'] = $this->attendance_system;
		$array['date_modification'] = $this->date_modification;
        $array['attendance_system_user'] = $this->attendance_system_user;
        $array['attendance_event'] = $this->attendance_system_user;
        $array['user'] = $this->user;
        $array['event_type'] = $this->attendance_system_user;
		$array['status'] = $this->status;
		$array['processedTime'] = mktime();
        switch($mode)
        {
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
     /* function to load a attendancesystemevent as a string
     * @param   string    $str   serialized object
     * @param    int     $mode   0 => serialize, 1 => json_encode, 2 => json_encode PRETTY PRINT,
     * @return  int              OK
     */    
       public function unserialize($str,$mode = 0){
       $ret = '';
       $array = array();
        switch($mode)
        {
            default:
            case 0:
                $array = unserialize($str);
                break;
            case 1:
            case 2:
                $array = json_decode($str);
                break;

        }
        $this->loadFromArray($array);
       }

     /* function to load a attendancesystemevent from an array
     * @param   array    $array   serialized object
     * @return  int              OK
     */    
    public function loadFromArray($array){

        // automatic unserialisation based on match between property name and key value
        foreach ($array as $key => $value) {
            if(isset($this->{$key}))$this->{$key} = $value;
        }
    }

     /**
     *  Function to generate a sellist
     *  @param string $htmlname name of the sellist input
     *  @param int $selected rowid to be preselected
     *  @return string HTML select list
     */
    
    Public function sellist($htmlname = '', $selected = ''){    
        $sql = array('table' => $this->table_element , 'keyfield' => 't.rowid', 'fields' => $this->getLabel('sql'), 'join' => $this->getLabel('sql'), 'where' => '', 'tail' => '');
        $html = array('name' => (($htmlname == '')?'Skeleton':$htmlname), 'class' => '', 'otherparam' => '', 'ajaxNbChar' => '', 'separator' => '-');
        $addChoices = null;
		return select_sellist($sql, $html, $selected, $addChoices );
    }

    /**     * function to define display of the object
     * @param string $type type of return text or sql
     * @return string Label
     */
    public function getLabel($type = 'text'){
        $ret = '';
        switch ($type){
            case 'sql':
                $ret = "t.fk_attendance_system_user, t.date_time_event";
            break;
            case 'join':
                $ret = " ";
            break;              
            case 'text':
            default:
                $ret = $this->attendance_system_user.'- '.$this->date_time_event;
            break;

        } 
        return $ret;
    }


    /**
    * 
    *  function to load the event from a zkevent
    *  @param  event(ZKTECO event) $ZKEvent  array( 'uid' => $uid, 'id' => $id, 'state' => $state, 'tms' => $timestamp)
    *  @result int/array(int)    ok(1)/ko(-1) badParam(-2)
    */
     
    public function loadZKEvent($fk_attendance_system, $event_type, $as_user, $ZKEvent){
        global $user;
        if(is_array($ZKEvent) ){
            $this->date_time_event = $ZKEvent['tms'];
            $this->attendance_system = $fk_attendance_system;
            $this->attendance_system_user = $as_user;
            $this->attendance_event = '';
            $this->event_type = $event_type;
            $this->state = $ZKEvent['state'];
            $this->state = 0;
            $this->create($user);
            return OK;
        }else return BADPARAM;
    }

}

    /**
    *  function to generate event from system event.
    *  @result int OK/KO
    */
    function parseSystemEvent(){
        $arrayEvent = fetchUnlinkedSystemEvent();
        $ret = array();
        //loop for all user
        if(is_array($arrayEvent))foreach($arrayEvent as $userid => $userEvent){
            //loop for all event
            if(is_array($userEvent))foreach($userEvent as $userid => $Event){
                $userDayList = splitByDay($Event);
                //loop for all the day found
                if(is_array($userDayList))foreach($userDayList as $userDay){
                    $ret[] = parseDaySystemEvent($userDay);
                }
            }
        }

    }

    /**
    * 
    *   function to group event by days.
    *   @param array(event) $EventArray list of event for a single user
    *   @result array( day => array(event))/KO
    */
    function splitByDay($EventArray){
        // assuming the array is sorted asc by date
        global $conf;
        $date = null;
        $fristEvent = null;
        $diffE2D = null;
        $DayEvent = array();
        $day = 1;
        foreach ($EventArray as $event){
            //if there is no event in the day or only one
            if (count($DayEvent[$day]) < 2){
                $date = $event['date_time_event'];
                $fristEvent = $event['date_time_event'];
                $date->setTime(0,0);
            }
            $diffE2E = date_diff($fristEvent, $event['date_time_event']);
            // ensure that the 2 consecutives event are not too distant
            if($diffE2E < $conf->global->ATTENDANCE_MIN_OVERDAY_BREAK){
                $diffE2D = date_diff($date, $event['date_time_event']);
                // ensure that the nex event is not too far int the next day
                if($diffE2D <  $conf->global->ATTENDANCE_MAX_DAY_SPAN){
                    $DayEvent[$day] = $event;
                }else{
                    $day++;
                }
            }else{
                $day++;
            }
            $DayEvent[$day] = $event;
        }
    }


    /**
    * 
    *  function to generate event from a sub list of system event.
    *   @param array(event) $EventArray list of event for a single user for a single day
    *  @result int number of event actually saved as attendance
    */
    function parseDaySystemEvent($EventArray){
        global $conf,$db;
        $inEvent = null; // 0 for in, 1 for out
        $prevEvent = null;
        $totalError = 0;
        $error = 0;
        $nbrEvent = count($EventArray);
        $staticObject = new AttendanceSystemEvent($db);
        foreach ($EventArray as $event){
            $error = 0;
            $nbrEvent = (count($EventArray) - $totalError)%2;
            $durlast = ($prevEvent == null)?
                $conf->global->TIMESHEET_EVENT_MIN_DURATION:
                date_diff($prevEvent['date_time_event'],$event['date_time_event']);
            if($inEvent == null){
                //event type 1-->'heartbeat','sign-in','sign-out','access'
                if ($event['event_type'] == 3){ //out event cannot count as in event
                    $error = 1;
                }elseif ($event['event_type'] == 1 //event
                    && (count($EventArray) - $totalError)%2 == 1 //odd number of valid event
                    && $durlast < $conf->global->TIMESHEET_EVENT_MIN_DURATION){
                       $error = 1;
                }
                if($error == 0){
                    $inEvent = $event;
                    $prevEvent = $event;
                }
            }else{
                $dur = date_diff($inEvent['date_time_event'],$event['date_time_event']);
                if ($event['event_type'] == 2){ //Signin, save the second one an put the first one as error
                    $staticObject->loadFromArray($event);
                    $staticObject->status = 
                    SetASEventError($prevEvent);
                    $nbrEvent -= 1;
                    $inEvent = $event;
                    $prevEvent = $event;
                    $error = 1;
                }else if ($event['event_type'] < 1 || $event['event_type'] > 3 ){ // in case the access event come into the picture
                    SetASEventError($event);
                    $nbrEvent -= 1;
                }elseif ($dur > $conf->global->TIMESHEET_EVENT_MIN_DURATION  // duration more than min
                        && $dur < $conf->global->TIMESHEET_EVENT_MAX_DURATION){ // duration less that may
                    GenerateAttendanceEvent($inEvent, $event);
                    $inEvent = null;
                    $prevEvent = $event;
                }else{
                    SetASEventError($event);
                    SetASEventError($inEvent);
                    $nbrEvent -= 2;
                }
            }
        }
        return $nbrEvent;
    }
    
    /**
    * 
    *  function to get the event not parsed.
    *  @result int OK/KO
    */
    function fetchUnlinkedSystemEvent(){
        global $db;
        $error = 0;
        $ret = array();
        $sql = "SELECT ase.rowid, ase.date_time_event, ase.fk_attendance_system,";
        $sql .= "  ase.attendance_system_user, asu.fk_user,";
        $sql .= "  ase.event_type, ase.status";
        $sql .= " FROM ".MAIN_DB_PREFIX.'attendance_system_event AS ase';
        $sql .= ' JOIN '.MAIN_DB_PREFIX.'attendance_system_user AS asu';
        $sql .= ' ON ase.fk_attendance_system_user = asu.rowid';
        $sql .= ' WHERE ase.fk_attendance_event = NULL';
        $sql .= ' AND ase.event_type > 4'; // don't take access event
        $sql .= ' ORDER BY asu.fk_user = NULL DESC';
        $sql .= ' , ase.date_time_event ASC';
		dol_syslog(__METHOD__, LOG_DEBUG);
        $resql = $db->query($sql);
        if ($resql)
        {
            $num = $this->db->num_rows($resql)

            for($i = 0; $i < $num; $i++){
                $obj = $this->db->fetch_object($resql);
                $ret[$obj->fk_user][$i] = array();
                $ret[$obj->fk_user][$i]['id'] = $obj->rowid;
                $ret[$obj->fk_user][$i]['date_time_event'] = $obj->date_time_event;
                $ret[$obj->fk_user][$i]['attendance_system'] = $obj->fk_attendance_system;
                $ret[$obj->fk_user][$i]['attendance_system_user'] = $obj->fk_attendance_system_user;
                $ret[$obj->fk_user][$i]['event_type'] = $obj->event_type;
                $ret[$obj->fk_user][$i]['status'] = $obj->status;
            }

            $this->db->free($resql);

        }else
        {
      	    $this->error="Error ".$this->db->lasterror();
            $ret = NOK;
        }
        return $ret;
    }