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
 *  \file       dev/attendancesystems/attendancesystem.class.php
 *  \ingroup    timesheet othermodule1 othermodule2
 *  \brief      This file is an example for a CRUD class file (Create/Read/Update/Delete)
 *				Initialy built by build_class_from_table on 2020-03-15 20:15
 */

// Put here all includes required by your class file
require_once DOL_DOCUMENT_ROOT."/core/class/commonobject.class.php";
//require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
//require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT."/projet/class/project.class.php";

require_once 'ZKLibrary.class.php';
require_once 'AttendanceSystemUser.class.php';
require_once 'AttendanceSystemEvent.class.php';
$attendancesystemStatusPictoArray = array(0=> 'statut7',1=>'statut3',2=>'statut3',3=>'statut4',4=>'statut8');
$attendancesystemStatusArray = array(0=> 'InOut',1=>'In',2=>'Out',3 => 'Access',4 => 'Deactivated');
/**
 *	Put here description of your class
 */
class AttendanceSystem extends CommonObject
{
    /**
     * @var string ID to identify managed object
     */				//!< To return several error codes (or messages)
    public $element = 'attendancesystem';			//!< Id that identify managed objects
    /**
     * @var string Name of table without prefix where object is stored
     */    
    public $table_element = 'attendance_system';		//!< Name of table without prefix where object is stored

    public $id;
    // BEGIN OF automatic var creation
    
	public $label;
	public $ip;
	public $port;
	public $note;
	public $third_party;
	public $task;
	public $project;
	public $serial_nb;
	public $zone;
	public $passwd;
	public $status;
	public $mode;
	public $date_modification = '';
	public $user_modification;

    
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
        
		$sql .= 'label,';
		$sql .= 'ip,';
		$sql .= 'port,';
		$sql .= 'note,';
		$sql .= 'fk_third_party,';
		$sql .= 'fk_task,';
		$sql .= 'fk_project,';
		$sql .= 'serial_nb,';
		$sql .= 'zone,';
		$sql .= 'passwd,';
		$sql .= 'status,';
		$sql .= 'mode';

        
        $sql .= ") VALUES (";
        
		$sql .= ' '.(empty($this->label)?'NULL':"'".$this->db->escape($this->label)."'").',';
		$sql .= ' '.(empty($this->ip)?'NULL':"'".$this->db->escape($this->ip)."'").',';
		$sql .= ' '.(empty($this->port)?'NULL':"'".$this->port."'").',';
		$sql .= ' '.(empty($this->note)?'NULL':"'".$this->db->escape($this->note)."'").',';
		$sql .= ' '.(empty($this->third_party)?'NULL':"'".$this->third_party."'").',';
		$sql .= ' '.(empty($this->task)?'NULL':"'".$this->task."'").',';
		$sql .= ' '.(empty($this->project)?'NULL':"'".$this->project."'").',';
		$sql .= ' '.(empty($this->serial_nb)?'NULL':"'".$this->serial_nb."'").',';
		$sql .= ' '.(empty($this->zone)?'NULL':"'".$this->zone."'").',';
		$sql .= ' '.(empty($this->passwd)?'NULL':"'".$this->db->escape($this->passwd)."'").',';
		$sql .= ' '.(empty($this->status)?'NULL':"'".$this->status."'").',';
		$sql .= ' '.(empty($this->mode)?'NULL':"'".$this->mode."'").'';

        
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
            //$result = $this->call_trigger('MYOBJECT_CREATE', $user);
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
    function fetch($id, $ref = '')
    {
    	global $langs;
        $sql = "SELECT";
        $sql .= " t.rowid,";
        
		$sql .= ' t.label,';
		$sql .= ' t.ip,';
		$sql .= ' t.port,';
		$sql .= ' t.note,';
		$sql .= ' t.fk_third_party,';
		$sql .= ' t.fk_task,';
		$sql .= ' t.fk_project,';
		$sql .= ' t.serial_nb,';
		$sql .= ' t.zone,';
		$sql .= ' t.passwd,';
		$sql .= ' t.status,';
		$sql .= ' t.mode,';
		$sql .= ' t.date_modification,';
		$sql .= ' t.fk_user_modification';

        
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
                
		$this->label = $obj->label;
		$this->ip = $obj->ip;
		$this->port = $obj->port;
		$this->note = $obj->note;
		$this->third_party = $obj->fk_third_party;
		$this->task = $obj->fk_task;
		$this->project = $obj->fk_project;
		$this->serial_nb = $obj->serial_nb;
		$this->zone = $obj->zone;
		$this->passwd = $obj->passwd;
		$this->status = $obj->status;
		$this->mode = $obj->mode;
		$this->date_modification = $this->db->jdate($obj->date_modification);
		$this->user_modification = $obj->fk_user_modification;

                
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
            //$result = $this->call_trigger('MYOBJECT_MODIFY', $user);
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
     *	@param		string			$htmlcontent 		text to show
     *	@param		int			$id                     Object ID
     *	@param		string			$ref                    Object ref
     *	@param		int			$withpicto		0=_No picto, 1=Includes the picto in the linkn, 2=Picto only
     *	@return		string						String with URL
     */
    function getNomUrl($htmlcontent, $id = 0, $ref = '', $withpicto = 0)
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
        $label = $this->getLabel('text') ;

        $card = '<u>' . $langs->trans("AttendanceSystem") . '</u>';
        $card .= '<br>';
        $card .= $langs->trans("#").': '.$id;

        if (empty($notooltip))
        {
            if (! empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER))
            {
                $label = $langs->trans("AttendanceSystem");
                $linkclose .= ' alt = "'.dol_escape_htmltag($label, 1).'"';
            }
            $linkclose .= ' title = "'.dol_escape_htmltag($card, 1).'"';
            $linkclose .= ' class = "classfortooltip'.($morecss?' '.$morecss:'').'"';
        }else $linkclose = ($morecss?' class = "'.$morecss.'"':'');
        $lien = '<a href = "'.dol_buildpath('/timesheet/AttendanceSystemCard.php',1).'?id='.$id.'&action=view"'.$linkclose.'>';
        $lienfin = empty($lien)?'':'</a>';
    	$picto = 'generic';
     
        
        
    	if ($withpicto == 1){ 
            $result .= ($lien.img_object($label, $picto).$htmlcontent.$lienfin);
        }else if ($withpicto == 2) {
            $result .= $lien.img_object($label, $picto).$lienfin;
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
            global $attendancesystemStatusPictoArray, $attendancesystemStatusArray;
            return $form->selectarray($htmlname, $attendancesystemStatusArray, $this->status);
	}   
    /**
	 *  Retourne le libelle du status (actif, inactif)
	 *
	 *  @param	int		$mode          0 = libelle long, 1 = libelle court, 2 = Picto + Libelle court, 3 = Picto, 4 = Picto + Libelle long, 5 = Libelle court + Picto
	 *  @return	string 			       Label of status
	 */
	function getLibStatut($mode = 0)
	{
		return $this->LibStatut($this->status, $mode);
	}
	/**
	 *  Return the status
	 *
	 *  @param	int		$status        	Id status
	 *  @param  int		$mode          	0 = long label, 1 = short label, 2 = Picto + short label, 3 = Picto, 4 = Picto + long label, 5 = Short label + Picto, 6 = Long label + Picto
	 *  @return string 			       	Label of status
	 */
	static function LibStatut($status, $mode = 0)
	{
		global $langs, $attendancesystemStatusPictoArray, $attendancesystemStatusArray;
		if ($mode == 0)
		{
			$prefix = '';
			return $langs->trans($attendancesystemStatusArray[$status]);
		}
		if ($mode == 1)
		{
			return $langs->trans($attendancesystemStatusArray[$status]);
		}
		if ($mode == 2)
		{
			 return img_picto($attendancesystemStatusArray[$status], $attendancesystemStatusPictoArray[$status]).' '.$langs->trans($attendancesystemStatusArray[$status]);
		}
		if ($mode == 3)
		{
			 return img_picto($attendancesystemStatusArray[$status], $attendancesystemStatusPictoArray[$status]);
		}
		if ($mode == 4)
		{
			 return img_picto($attendancesystemStatusArray[$status], $attendancesystemStatusPictoArray[$status]).' '.$langs->trans($attendancesystemStatusArray[$status]);
		}
		if ($mode == 5)
		{
			 return $langs->trans($attendancesystemStatusArray[$status]).' '.img_picto($attendancesystemStatusArray[$status], $attendancesystemStatusPictoArray[$status]);
		}
		if ($mode == 6)
		{
			 return $langs->trans($attendancesystemStatusArray[$status]).' '.img_picto($attendancesystemStatusArray[$status], $attendancesystemStatusPictoArray[$status]);
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
        //$result = $this->call_trigger('MYOBJECT_DELETE', $user);
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
        global $user, $langs;
        $error = 0;
        $object = new AttendanceSystem($this->db);
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
        
	$this->label = '';
	$this->ip = '';
	$this->port = '';
	$this->note = '';
	$this->third_party = '';
	$this->task = '';
	$this->project = '';
	$this->serial_nb = '';
	$this->zone = '';
	$this->passwd = '';
	$this->status = '';
	$this->mode = '';
	$this->date_modification = '';
	$this->user_modification = '';

        
    }
    /**
     *	will clean the parameters
     *	
     *
     *	@return	void
     */       
    function cleanParam(){
        
			if (!empty($this->label)) $this->label = trim($this->label);
			if (!empty($this->ip)) $this->ip = trim($this->ip);
			if (!empty($this->port)) $this->port = trim($this->port);
			if (!empty($this->note)) $this->note = trim($this->note);
			if (!empty($this->third_party)) $this->third_party = trim($this->third_party);
			if (!empty($this->task)) $this->task = trim($this->task);
			if (!empty($this->project)) $this->project = trim($this->project);
			if (!empty($this->serial_nb)) $this->serial_nb = trim($this->serial_nb);
			if (!empty($this->zone)) $this->zone = trim($this->zone);
			if (!empty($this->passwd)) $this->passwd = trim($this->passwd);
			if (!empty($this->status)) $this->status = trim($this->status);
			if (!empty($this->mode)) $this->mode = trim($this->mode);
			if (!empty($this->date_modification)) $this->date_modification = trim($this->date_modification);
			if (!empty($this->user_modification)) $this->user_modification = trim($this->user_modification);

        
    }
     /**
     *	will create the sql part to update the parameters
     *	
     *
     *	@return	void
     */    
    function setSQLfields($user){
        $sql = '';
        
		$sql .= ' label = '.(empty($this->label) != 0 ? 'null':"'".$this->db->escape($this->label)."'").',';
		$sql .= ' ip = '.(empty($this->ip) != 0 ? 'null':"'".$this->db->escape($this->ip)."'").',';
		$sql .= ' port = '.(empty($this->port) != 0 ? 'null':"'".$this->port."'").',';
		$sql .= ' note = '.(empty($this->note) != 0 ? 'null':"'".$this->db->escape($this->note)."'").',';
		$sql .= ' fk_third_party = '.(empty($this->third_party) != 0 ? 'null':"'".$this->third_party."'").',';
		$sql .= ' fk_task = '.(empty($this->task) != 0 ? 'null':"'".$this->task."'").',';
		$sql .= ' fk_project = '.(empty($this->project) != 0 ? 'null':"'".$this->project."'").',';
		$sql .= ' serial_nb = '.(empty($this->serial_nb) != 0 ? 'null':"'".$this->serial_nb."'").',';
		$sql .= ' zone = '.(empty($this->zone) != 0 ? 'null':"'".$this->zone."'").',';
		$sql .= ' passwd = '.(empty($this->passwd) != 0 ? 'null':"'".$this->db->escape($this->passwd)."'").',';
		$sql .= ' status = '.(empty($this->status) != 0 ? 'null':"'".$this->status."'").',';
		$sql .= ' mode = '.(empty($this->mode) != 0 ? 'null':"'".$this->mode."'").',';
		$sql .= ' date_modification = NOW() ,';
		$sql .= ' fk_user_modification = "'.$user->id.'"';

        
        return $sql;
    }
    /*
    * function to save a attendancesystem as a string
    * @param    int     $mode   0 => serialize, 1 => json_encode, 2 => json_encode PRETTY PRINT 
    * @return   string       serialized object
    */
    public function serialize($mode = 0){
		$ret = '';
		$array = array();
		
		$array['label'] = $this->label;
		$array['ip'] = $this->ip;
		$array['port'] = $this->port;
		$array['note'] = $this->note;
		$array['third_party'] = $this->third_party;
		$array['task'] = $this->task;
		$array['project'] = $this->project;
		$array['serial_nb'] = $this->serial_nb;
		$array['zone'] = $this->zone;
		$array['passwd'] = $this->passwd;
		$array['status'] = $this->status;
		$array['mode'] = $this->mode;
		$array['date_modification'] = $this->date_modification;
		$array['user_modification'] = $this->user_modification;

		
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
     /* function to load a attendancesystem as a string
     * @param   string    $str   serialized object
     * @param    int     $mode   0 => serialize, 1 => json_encode, 2 => json_encode PRETTY PRINT
     * @return  int              OK
     */    
       public function unserialize($str, $mode = 0){
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
        $sql = array('table' => $this->table_element , 'keyfield' => 't.rowid', 'fields' => $this->getLabel('sql'), 'join' => '', 'where' => '', 'tail' => '');
        $html = array('name' => (($htmlname == '')?'AttendanceSystem':$htmlname), 'class' => '', 'otherparam' => '', 'ajaxNbChar' => '', 'separator' => '-');
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
                $ret = "concat( t.label, ' (',t.ip,')') as display";
            break;            
            case 'text':
            default:
                $ret = $this->label.' ('.$this->ip.')';
            break;

        } 
        return $ret;
    }
    
  
    /* Function to import the event from an attendance system
     *  @param int  $mode       simple import or creation of timesheet   
     *  @param path $file       path to an excel to import
     */
    function importEvent($mode, $file = ''){
        # connect to the attendance system or manage the file
        $attendance = array();
        if( $file != ''){
            
        }else if(lenght($this->ip)>6){
            $zkteco = new ZKLibrary($this->ip, $this->port);
            if (is_numeric($zkteco->ping()) && $zkteco->connect()){
                # retrive event
                $zkteco->disableDevice();
                $attendance = $zkteco->getAttendance(); // array($uid, $id, $state, $timestamp)
                $zkteco->clearAttendance();
                // upload finished, disconnect
                $zkteco->enableDevice();
                $zkteco->disconnect();
            } else return -2; // return error is
        }else return -1; // return error if the IP is not set neither the file
        
        
        return $attendance;
        
        # guess start and stop
        # save as attendance event
        # Create or not task time
        
    }

    /** 
     * Function to retrieve the user from the attendance system
     * @return array(ZKuser) array(uid,string name,int role, string passwd)
     */
    function getUsers(){
        $userArray = array();
        if(preg_match("/^(\d{1,3}\.){3}\d{1,3}/", $this->ip)){
            $zkteco = new ZKLibrary($this->ip, $this->port);
            if (is_numeric($zkteco->ping()) && $zkteco->connect()){
                dol_syslog(__METHOD__." Connected to  ".$this->ip, LOG_DEBUG);
                # retrive event
                $zkteco->disableDevice();
                $userArray = $zkteco->getUser(); // array(uid,string name,int role, string passwd)
                dol_syslog(__METHOD__." ${count($userArray)} User retrieved  ".$this->ip, LOG_DEBUG);
                foreach ($userArray as $key => $data){ //[U16 size, U16 PIn, char FingerID, int valid, char|array(template data)]
                    $templateData = $zkteco->getUserTemplateAll($key);
                    $userArray[$key]['data'] = implode(unpack("H*", serialize($templateData)));
                    //reverse $array = unserialize(pack("H*", $hex));
                }
                // upload finished, disconnect
                $zkteco->enableDevice();
                $zkteco->disconnect();
            } else return null; // return error is
        }else return null; // return error if the IP is not set 
        return $userArray;

    }


    /** Function to send the user from the attendance system
     * 
     */
    function setUser(){


    }

    function updateAsUser(){
        $userArray = $this->getUser();
        # FIXME update from name etc ... 
    }



    public function testConnection(){
        if(preg_match("/^(\d{1,3}\.){3}\d{1,3}/", $this->ip)){
            $zkteco = new ZKLibrary($this->ip, $this->port);
            if (is_numeric($zkteco->ping()) && $zkteco->connect() ){
                dol_syslog(__METHOD__." Connected to  ".$this->ip, LOG_DEBUG);
                $zkteco->disconnect();
                return true;
            } else 
            {
                return false; // return error is
                dol_syslog(__METHOD__." Was not able to connect to  ".$this->ip, LOG_ERR);
            }
        }else 
        {
            dol_syslog(__METHOD__." the IP ".$this->ip." is not correct", LOG_ERR);
            return false; // return error if the IP is not set 
            
        }
    }

         /**         *  function to load user in the database from an array
         *  @param  array(ZKTECO User) $userArray array of user // array(uid,string name,int role, string passwd)
         *  @result int/array(int)    ok(1)/ko(-1) badParam(-2)
         */
        function loadAttendanceUserFromArray( $userArray = null){
            $res = array();
            if(is_array($userArray) && count($userArray)>0){
                foreach($userArray as $key => $ZKUser){
                    $attendanceUser = new AttendanceSystemUser($this->db);
                    $res[] = $attendanceUser->loadZKUser($this->id,  $this->mode, $ZKUser);
                }
                if (min($res)<0) return  $res;
                else return OK;
            }else return BADPARAM;
            
    
        }
        /**         *  function to load event in the database from an array
         *  @param  event(ZKTECO event) $EventArray array of user // array( 'uid' => $uid, 'id' => $id, 'state' => $state, 'tms' => $timestamp)
         *  @result int/array(int)    ok(1)/ko(-1) badParam(-2)
         */
        function loadAttendanceUserEventFromArray($EventArray = null){
            $res = array();
            $prevUid = '';
            $curUser = '';
            $attendanceUser = new AttendanceSystemUser($this->db);
            if(is_array($EventArray) && count($EventArray)>0){
                foreach($EventArray as $key => $ZKEvent){
                    $ZKEvent = new AttendanceSystemEvent($this->db);
                    if ($prevUid != $ZKEvent['uid']) $curUser = $attendanceUser->fetchAsUser($this->id, $ZKEvent['uid']);//FIXME need to check that the user is uid
                    $res[] = $ZKEvent->loadZKEvent($this->id,  $this->status, $curUser, $ZKEvent);
                    $prevUid = $ZKEvent['uid'];
                }
                if (min($res)<0) return  $res;
                else return OK;
            }else return BADPARAM;
        }
}
