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
 *  \file       dev/AttendanceSystemUsers/AttendanceSystemUser.class.php
 *  \ingroup    timesheet othermodule1 othermodule2
 *  \brief      This file is an example for a CRUD class file (Create/Read/Update/Delete)
 *				Initialy built by build_class_from_table on 2020-03-28 12:18
 */

// Put here all includes required by your class file
require_once DOL_DOCUMENT_ROOT."/core/class/commonobject.class.php";
//require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
//require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
//require_once(DOL_DOCUMENT_ROOT.'/projet/class/project.class.php');
require_once 'core/lib/generic.lib.php';
require_once 'AttendanceSystemUserLink.class.php';

$AttendanceSystemUserStatusPictoArray = array(0=> 'statut7',1=>'statut3',2=>'statut8',3=>'statut4');
$AttendanceSystemUserStatusArray = array(0=> 'Draft',1=>'Validated',2=>'Cancelled',3 => 'Payed');
/**
 *	Put here description of your class
 */
class AttendanceSystemUser extends CommonObject
{
    /**
     * @var string ID to identify managed object
     */				//!< To return several error codes (or messages)
    public $element = 'attendancesystemuser';			//!< Id that identify managed objects
    /**
     * @var string Name of table without prefix where object is stored
     */    
    public $table_element = 'attendance_system_user';		//!< Name of table without prefix where object is stored

    public $id;
    // BEGIN OF automatic var creation
    
	public $as_name;
	public $user;
	public $as_uid;
	public $rfid;
	public $role;
	public $passwd;
	public $data;
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
        
		$sql .= 'as_name,';
		$sql .= 'fk_user,';
		$sql .= 'as_uid,';
		$sql .= 'rfid,';
		$sql .= 'role,';
        $sql .= 'passwd,';
        if(strlen($this->data)>0)$sql .= 'data,';
		$sql .= 'status,';
		$sql .= 'mode';

        
        $sql .= ") VALUES (";
        
        $sqlSelectUser = "(SELECT rowid from ".MAIN_DB_PREFIX."user AS u ";
        $sqlSelectUser .= " WHERE u.login = '$this->as_name'";
        $sqlSelectUser .= " OR u.login = '$this->as_uid'";
        $sqlSelectUser .= " OR u.email = '$this->as_uid'";
        $sqlSelectUser .= " OR u.ref_int = '$this->as_uid'";
        if($this->name != null)$sqlSelectUser .= " OR CONCAT(u.firstname,'.',u.lastname) = '$name'";
        $sqlSelectUser .= " LIMIT 1 )";
        $sql .= ' '.(empty($this->as_name)?'NULL':"'".$this->db->escape($this->as_name)."'").',';
		$sql .= ' '.(empty($this->user)?$sqlSelectUser:"'".$this->user."'").',';
		$sql .= ' '.(empty($this->as_uid)?'NULL':"'".$this->as_uid."'").',';
		$sql .= ' '.(empty($this->rfid)?'NULL':"'".$this->rfid."'").',';
		$sql .= ' '.(empty($this->role)?'NULL':"'".$this->role."'").',';
		$sql .= ' '.(empty($this->passwd)?'NULL':"'".$this->db->escape($this->passwd)."'").',';
		if(strlen($this->data)>0)$sql .= ' '.(empty($this->data)?'NULL':"'".$this->data."'").',';
		$sql .= ' '.(empty($this->status)?'NULL':"'".$this->status."'").',';
		$sql .= ' '.(empty($this->mode)?'NULL':"'".$this->mode."'").'';
        $sql .= ")";
        if($this->fetchDuplicate()>0){
            return $this->update($user);
        }else{
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
                return -1;
            }
            else
            {
                $this->db->commit();
                return $this->id;
            }
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
        
		$sql .= ' t.as_name,';
		$sql .= ' t.fk_user,';
		$sql .= ' t.as_uid,';
		$sql .= ' t.rfid,';
		$sql .= ' t.role,';
		$sql .= ' t.passwd,';
		$sql .= ' t.data,';
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
                $this->as_name = $obj->as_name;
                $this->user = $obj->fk_user;
                $this->as_uid = $obj->as_uid;
                $this->rfid = $obj->rfid;
                $this->role = $obj->role;
                $this->passwd = $obj->passwd;
                $this->data = $obj->data;
                $this->status = $obj->status;
                $this->mode = $obj->mode;
                $this->date_modification = $this->db->jdate($obj->date_modification);
                $this->user_modification = $obj->fk_user_modification;
            }
            return 1;
        }
        else
        {
      	    $this->error="Error ".$this->db->lasterror();
            return -1;
        }
    }

    /**
     *  Look for duplicate
     *  @return int          	<0 if KO, >0 if OK
     */
    function fetchDuplicate()
    {
        $error = 0;
        $sql = "SELECT rowid, fk_user FROM ".MAIN_DB_PREFIX.$this->table_element;;
        $sql .= " WHERE ";
		$sql .= ' as_name = '.(empty($this->as_name) != 0 ? 'null':"'".$this->db->escape($this->as_name)."'").' AND ';
		$sql .= ' as_uid = '.(empty($this->as_uid) != 0 ? 'null':"'".$this->as_uid."'").' AND ';
		$sql .= ' rfid = '.(empty($this->rfid) != 0 ? 'null':"'".$this->rfid."'").' AND ';
		$sql .= ' role = '.(empty($this->role) != 0 ? 'null':"'".$this->role."'").' AND ';
		$sql .= ' passwd = '.(empty($this->passwd) != 0 ? 'null':"'".$this->db->escape($this->passwd)."'").' AND ';
		$sql .= ' status = '.(empty($this->status) != 0 ? 'null':"'".$this->status."'").' AND ';
        $sql .= ' mode = '.(empty($this->mode) != 0 ? 'null':"'".$this->mode."'");
        $sql .= " LIMIT 1 ";
		dol_syslog(__METHOD__, LOG_DEBUG);
        $resql = $this->db->query($sql);
        if ($resql && $this->db->num_rows($resql)>0)
        {
            $obj = $this->db->fetch_object($resql);
            $this->id = $obj->rowid;
            $this->user = $obj->fk_user;
            $this->db->free($resql);
            return 1;
        }else
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

     *	@param		int			$withpicto		0=_No picto, 1=Includes the picto in the linkn, 2=Picto only
     *	@param		int			$id                     Object ID
     *	@return		string						String with URL
     */
    function getNomUrl($withpicto = 1, $id = 0)
    {
	global $conf, $langs;

        if($id > 0 && $this->id != $id)$this->fetch($id);

        if (! empty($conf->dol_no_mouse_hover)) $notooltip = 1;   // Force disable tooltips
    	$result = '';
        if( $id == 0){
            if(isset($this->id))  {
                $id = $this->id;
            }
        }
        $card = '<u>' . $langs->trans("AttendanceSystemUser") . '</u>';
        $card .= $langs->trans("#").': '.$id;
        $card .= '<br>';
        $card .= '<u>' . $langs->trans("AttendanceSystem") . ' </u>';
        $card .= 'ID : '.$this->as_uid;
        $card .= '<br>';
        if($this->user>0){
            $StUser = new User($this->db);
            $StUser->fetch($this->user);
            $card .= '<br>';
            $card .= $StUser->getNomUrl(0);
        }

        $label .= $this->getLabel('text');
        $linkclose = '';
        if (empty($notooltip))
        {
            if (! empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER))
            {
                $label = $langs->trans("AttendanceSystemUser");
                $linkclose .= ' alt = "'.dol_escape_htmltag($label, 1).'"';
            }
            $linkclose .= ' title = "'.dol_escape_htmltag($card, 1).'"';
            $linkclose .= ' class = "classfortooltip'.($morecss?' '.$morecss:'').'"';
        }else $linkclose = ($morecss?' class = "'.$morecss.'"':'');
        
        if($id){
            $lien = '<a href = "'.dol_buildpath('/timesheet/AttendanceSystemUserCard.php',1).'?id='.$id.'&action=view"'.$linkclose.'>';
        }else if (!empty($ref)){
            $lien = '<a href = "'.dol_buildpath('/timesheet/AttendanceSystemUserCard.php',1).'?ref='.$ref.'&action=view"'.$linkclose.'>';
        }else{
            $lien = "";
        }
        $lienfin = empty($lien)?'':'</a>';

    	$picto = 'generic';
 
        
        
        
    	if ($withpicto == 1){ 
            $result .= ($lien.img_object('', $picto).$label.$lienfin);
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
            global $AttendanceSystemUserStatusPictoArray, $AttendanceSystemUserStatusArray;
            return $form->selectarray($htmlname, $AttendanceSystemUserStatusArray, $this->status);
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
		global $langs, $AttendanceSystemUserStatusPictoArray, $AttendanceSystemUserStatusArray;
		if ($mode == 0)
		{
			$prefix = '';
			return $langs->trans($AttendanceSystemUserStatusArray[$status]);
		}
		if ($mode == 1)
		{
			return $langs->trans($AttendanceSystemUserStatusArray[$status]);
		}
		if ($mode == 2)
		{
			 return img_picto($AttendanceSystemUserStatusArray[$status], $AttendanceSystemUserStatusPictoArray[$status]).' '.$langs->trans($AttendanceSystemUserStatusArray[$status]);
		}
		if ($mode == 3)
		{
			 return img_picto($AttendanceSystemUserStatusArray[$status], $AttendanceSystemUserStatusPictoArray[$status]);
		}
		if ($mode == 4)
		{
			 return img_picto($AttendanceSystemUserStatusArray[$status], $AttendanceSystemUserStatusPictoArray[$status]).' '.$langs->trans($AttendanceSystemUserStatusArray[$status]);
		}
		if ($mode == 5)
		{
			 return $langs->trans($AttendanceSystemUserStatusArray[$status]).' '.img_picto($AttendanceSystemUserStatusArray[$status], $AttendanceSystemUserStatusPictoArray[$status]);
		}
		if ($mode == 6)
		{
			 return $langs->trans($AttendanceSystemUserStatusArray[$status]).' '.img_picto($AttendanceSystemUserStatusArray[$status], $AttendanceSystemUserStatusPictoArray[$status]);
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
        $object = new AttendanceSystemUser($this->db);
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
        
	$this->as_name = '';
	$this->user = '';
	$this->as_uid = '';
	$this->rfid = '';
	$this->role = '';
	$this->passwd = '';
	$this->data = '';
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
        
			if (!empty($this->as_name)) $this->as_name = trim($this->as_name);
			if (!empty($this->user)) $this->user = trim($this->user);
			if (!empty($this->as_uid)) $this->as_uid = trim($this->as_uid);
			if (!empty($this->rfid)) $this->rfid = trim($this->rfid);
			if (!empty($this->role)) $this->role = trim($this->role);
			if (!empty($this->passwd)) $this->passwd = trim($this->passwd);
			if (!empty($this->data)) $this->data = trim($this->data);
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
		$sql .= ' as_name = '.(empty($this->as_name) != 0 ? 'null':"'".$this->db->escape($this->as_name)."'").',';
		$sql .= ' fk_user = '.(empty($this->user) != 0 ? 'null':"'".$this->user."'").',';
		$sql .= ' as_uid = '.(empty($this->as_uid) != 0 ? 'null':"'".$this->as_uid."'").',';
		$sql .= ' rfid = '.(empty($this->rfid) != 0 ? 'null':"'".$this->rfid."'").',';
		$sql .= ' role = '.(empty($this->role) != 0 ? 'null':"'".$this->role."'").',';
		$sql .= ' passwd = '.(empty($this->passwd) != 0 ? 'null':"'".$this->db->escape($this->passwd)."'").',';
		if(strlen($this->data)>0)$sql .= ' data = '.(empty($this->data) != 0 ? 'null':"'".$this->db->escape($this->data)."'").',';
		$sql .= ' status = '.(empty($this->status) != 0 ? 'null':"'".$this->status."'").',';
        $sql .= ' mode = '.(empty($this->mode) != 0 ? 'null':"'".$this->mode."'").',';
        $sql .= ' date_modification = NOW() ,';
        $sql .= ' fk_user_modification = "'.$user->id.'"';

        
        return $sql;
    }
    /*
    * function to save a AttendanceSystemUser as a string
    * @param    int     $mode   0 => serialize, 1 => json_encode, 2 => json_encode PRETTY PRINT 
    * @return   string       serialized object
    */
    public function serialize($mode = 0){
		$ret = '';
		$array = array();
		$array['as_name'] = $this->as_name;
		$array['user'] = $this->user;
		$array['as_uid'] = $this->as_uid;
		$array['rfid'] = $this->rfid;
		$array['role'] = $this->role;
		$array['passwd'] = $this->passwd;
		$array['data'] = $this->data;
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
     /* function to load a AttendanceSystemUser as a string
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

    /**     *  function to load 1 user in the database from an ZKUSer
     *  @param int $attendance_system   zone of the attendance system
     *  @param int $mode   // fngerprint version face id ...
     *  @param  ZKTECO User $ZKUser // array(uid,string name,int role, string passwd, rfid, active, data)
     *  @result int    ok(1)/ko(-1) badParam(-2)
     */
    Public function loadZKUser($attendance_system, $mode, $ZKUser){
        global $user;
        
        if(is_array($ZKUser) ){
            
            $this->mode = $mode;
            $this->user = '';
            $this->as_uid = ($ZKUser['uid']);
            $this->as_name = ($ZKUser['name']);
            $this->role = ($ZKUser['role']);
            $this->passwd = ($ZKUser['passwd']);
            $this->rfid = ($ZKUser['rfid']);
            $this->data = ($ZKUser['data']);
            $this->status = 1; //fixme
            $this->create($user);
            // add link

            if($this->id > 0){
                $asZone = new AttendanceSystemUserLink($this->db);
                $asZone->initAsSpecimen($attendance_system, $this->id);
                $asZone->create($user);
                if($asZone->id < 0){
                    $this->delete($user);
                    return CHILDNOTCREATED;
                }

            }else return NOTCREATED;

            return OK;
        }else return BADPARAM;

    }

    /**
     *  Function to generate a sellist
     *  @param int $selected rowid to be preselected
     *  @return string HTML select list
     */
        Public function sellist($htmlname = '', $selected = ''){    
        $sql = array('table' => $this->table_element , 'keyfield' => 't.rowid', 'fields' => $this->getLabel('sql'), 'join' =>  $this->getLabel('join'), 'where' => '', 'tail' => '');
        $html = array('name' => (($htmlname == '')?'AttendanceSystemUser':$htmlname), 'class' => '', 'otherparam' => '', 'ajaxNbChar' => '', 'separator' => '-');
        $addChoices = null;
		return select_sellist($sql, $html, $selected, $addChoices );
    }

    /**
     *  function to define display of the object
     *  @param string $type type of return text or sql
     *  @return string Label
     **/
    public function getLabel($type = 'text'){
        $ret = '';
        switch ($type){
            case 'sql':
                $ret = 't.as_uid,u.firstname,u.lastname';
            break;
            case 'join':
                $ret = ' JOIN '.MAIN_DB_PREFIX.'user as u ON t.fk_user = u.rowid'; 
            break;                
            case 'text':
            default:
                $ret = $this->as_name.' ('.$this->as_uid.')';
            break;

        } 
        return $ret;
    }
    /**
     *  function to retrieve the dol user based on the attendance_user + link user 
     * @param int $fk_attendance_system id of the attendance system
     * @param int $user_as_id ZKTeco user id
     *  
     **/    
    public function fetchAsUser($fk_attendance_system, $user_as_id){
        $error = 0;
        $ret = '';
        $sql = "SELECT asu.rowid FROM ".MAIN_DB_PREFIX.$this->table_element.' AS asu';
        $sql .= " JOIN ".MAIN_DB_PREFIX.'attendance_system_user_link AS asul';
		$sql .= ' ON  asu.rowid = asul.fk_attendance_system_user';
        $sql .= " AND  asul.fk_attendance_system = ".$fk_attendance_system;
		$sql .= ' WHERE asu.as_uid = '.$fk_attendance_system;
        $sql .= " LIMIT 1 ";
		dol_syslog(__METHOD__, LOG_DEBUG);
        $resql = $this->db->query($sql);
        if ($resql && $this->db->num_rows($resql)>0)
        {
            $obj = $this->db->fetch_object($resql);
            
            if (isset($obj->rowid)){
                $ret = $obj->rowid;;
            }else{
                $ret = NOK;
            }
            $this->db->free($resql);

        }else
        {
      	    $this->error="Error ".$this->db->lasterror();
            $ret = NOK;
        }
        return $ret;
    }

}


  