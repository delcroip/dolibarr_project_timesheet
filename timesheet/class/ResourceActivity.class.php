<?php
/* Copyright (C) 2007-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2014	   Juanjo Menent	<jmenent@2byte.es>
 * Copyright (C) 2017      Patrick Delcroix    <pmpdelcroix@gmail.com>
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


// Put here all includes required by your class file
require_once(DOL_DOCUMENT_ROOT."/core/class/commonobject.class.php");
//require_once(DOL_DOCUMENT_ROOT."/societe/class/societe.class.php");
//require_once(DOL_DOCUMENT_ROOT."/product/class/product.class.php");


/**
 *	Put here description of your class
 */
class ResourceActivity extends CommonObject
{
    var $db;							//!< To store db handler
    var $error;							//!< To return error code (or message)
    var $errors=array();				//!< To return several error codes (or messages)
    var $element='ResourceActivity';			//!< Id that identify managed objects
    var $table_element='resource_activity';		//!< Name of table without prefix where object is stored

    var $id;
    
	var $date_start='';
	var $date_end='';
	var $time_start;
	var $time_end;
	var $weekdays;
	var $redundancy;
	var $timetype;
	var $status;
	var $priority;
	var $note;
	var $date_creation='';
	var $date_modification='';
	var $userid;
	var $user_creation;
	var $user_modification;
	var $element_id;

    


    /**
     *  Constructor
     *
     *  @param	DoliDb          $db      Database handler
     *  @param	Object/int		$user    user object
     */
    function __construct($db,$user=null)
    {
        $this->db = $db;
        $this->userid=  is_object($user)?$user->id:$user;
        return 1;
        
    }

 /******************************************************************************
  * 
  *              database funciton
  * 
  *****************************************************************************/ 
    /**
     *  Create object into database
     *
     *  @param	User	$user        User that creates
     *  @param  int		$notrigger   0=launch triggers after, 1=disable triggers
     *  @return int      		   	 <0 if KO, Id of created object if OK
     */
    function create($user, $notrigger=0)
    {
    	global $conf, $langs;
		$error=0;

        $this->cleanVariables();

        // Check parameters
        // Put here code to add control on parameters values

        // Insert request
        $sql = "INSERT INTO ".MAIN_DB_PREFIX.$this->table_element."(";
        
		$sql.= 'date_start,';
		$sql.= 'date_end,';
		$sql.= 'time_start,';
		$sql.= 'time_end,';
		$sql.= 'weekdays,';
		$sql.= 'redundancy,';
		$sql.= 'timetype,';
		$sql.= 'status,';
		$sql.= 'priority,';
		$sql.= 'note,';
		$sql.= 'date_creation,';
		$sql.= 'fk_userid,';
		$sql.= 'fk_user_creation,';
		$sql.= 'fk_element_id';

        
        $sql.= ") VALUES (";
        
		$sql.=' '.(! isset($this->date_start) || dol_strlen($this->date_start)==0?'NULL':'"'.$this->db->idate($this->date_start).'"').',';
		$sql.=' '.(! isset($this->date_end) || dol_strlen($this->date_end)==0?'NULL':'"'.$this->db->idate($this->date_end).'"').',';
		$sql.=' '.(! isset($this->time_start)?'NULL':'"'.$this->time_start.'"').',';
		$sql.=' '.(! isset($this->time_end)?'NULL':'"'.$this->time_end.'"').',';
		$sql.=' '.(! isset($this->weekdays)?'NULL':'"'.$this->weekdays.'"').',';
		$sql.=' '.(! isset($this->redundancy)?'NULL':'"'.$this->redundancy.'"').',';
		$sql.=' '.(! isset($this->timetype)?'NULL':'"'.$this->timetype.'"').',';
		$sql.=' '.(! isset($this->status)?'NULL':'"'.$this->status.'"').',';
		$sql.=' '.(! isset($this->priority)?'NULL':'"'.$this->priority.'"').',';
		$sql.=' '.(! isset($this->note)?'NULL':'"'.$this->db->escape($this->note).'"').',';
		$sql.=' NOW() ,';
		$sql.=' '.(! isset($this->userid)?'NULL':'"'.$this->userid.'"').',';
		$sql.=' "'.$user->id.'",';
		$sql.=' '.(! isset($this->element_id)?'NULL':'"'.$this->element_id.'"').'';

        
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
        }else
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
        
		$sql.=' t.date_start,';
		$sql.=' t.date_end,';
		$sql.=' t.time_start,';
		$sql.=' t.time_end,';
		$sql.=' t.weekdays,';
		$sql.=' t.redundancy,';
		$sql.=' t.timetype,';
		$sql.=' t.status,';
		$sql.=' t.priority,';
		$sql.=' t.note,';
		$sql.=' t.date_creation,';
		$sql.=' t.date_modification,';
		$sql.=' t.fk_userid,';
		$sql.=' t.fk_user_creation,';
		$sql.=' t.fk_user_modification,';
		$sql.=' t.fk_element_id';

        
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
                
				$this->date_start = $this->db->jdate($obj->date_start);
				$this->date_end = $this->db->jdate($obj->date_end);
				$this->time_start = $obj->time_start;
				$this->time_end = $obj->time_end;
				$this->weekdays = $obj->weekdays;
				$this->redundancy = $obj->redundancy;
				$this->timetype = $obj->timetype;
				$this->status = $obj->status;
				$this->priority = $obj->priority;
				$this->note = $obj->note;
				$this->date_creation = $this->db->jdate($obj->date_creation);
				$this->date_modification = $this->db->jdate($obj->date_modification);
				$this->userid = $obj->fk_userid;
				$this->user_creation = $obj->fk_user_creation;
				$this->user_modification = $obj->fk_user_modification;
				$this->element_id = $obj->fk_element_id;

                
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
        $this->cleanVariables();

        // Check parameters
        // Put here code to add a control on parameters values

        // Update request
        $sql = "UPDATE ".MAIN_DB_PREFIX.$this->table_element." SET";
        
		$sql.=' date_start='.(dol_strlen($this->date_start)!=0 ? '"'.$this->db->idate($this->date_start).'"':'null').',';
		$sql.=' date_end='.(dol_strlen($this->date_end)!=0 ? '"'.$this->db->idate($this->date_end).'"':'null').',';
		$sql.=' time_start='.(empty($this->time_start)!=0 ? 'null':'"'.$this->time_start.'"').',';
		$sql.=' time_end='.(empty($this->time_end)!=0 ? 'null':'"'.$this->time_end.'"').',';
		$sql.=' weekdays='.(empty($this->weekdays)!=0 ? 'null':'"'.$this->weekdays.'"').',';
		$sql.=' redundancy='.(empty($this->redundancy)!=0 ? 'null':'"'.$this->redundancy.'"').',';
		$sql.=' timetype='.(empty($this->timetype)!=0 ? 'null':'"'.$this->timetype.'"').',';
		$sql.=' status='.(empty($this->status)!=0 ? 'null':'"'.$this->status.'"').',';
		$sql.=' priority='.(empty($this->priority)!=0 ? 'null':'"'.$this->priority.'"').',';
		$sql.=' note='.(empty($this->note)!=0 ? 'null':'"'.$this->db->escape($this->note).'"').',';
		$sql.=' date_modification=NOW() ,';
		$sql.=' fk_userid='.(empty($this->userid)!=0 ? 'null':'"'.$this->userid.'"').',';
		$sql.=' fk_user_modification="'.$user->id.'",';
		$sql.=' fk_element_id='.(empty($this->element_id)!=0 ? 'null':'"'.$this->element_id.'"').'';

        
        $sql.= " WHERE rowid=".$this->id;

		$this->db->begin();

		dol_syslog(__METHOD__);
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

            $object=new Timesheetactivity($this->db);

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


	/**
	 *	Clean the variables
	 *
	 *	@return	void
	 */
	function cleanVariables()
	{
            		// Clean parameters
            
		if (isset($this->date_start)) $this->date_start=trim($this->date_start);
		if (isset($this->date_end)) $this->date_end=trim($this->date_end);
		if (isset($this->time_start)) $this->time_start=trim($this->time_start);
		if (isset($this->time_end)) $this->time_end=trim($this->time_end);
		if (isset($this->weekdays)) $this->weekdays=trim($this->weekdays);
		if (isset($this->redundancy)) $this->redundancy=trim($this->redundancy);
		if (isset($this->timetype)) $this->timetype=trim($this->timetype);
		if (isset($this->status)) $this->status=trim($this->status);
		if (isset($this->priority)) $this->priority=trim($this->priority);
		if (isset($this->note)) $this->note=trim($this->note);
		if (isset($this->date_creation)) $this->date_creation=trim($this->date_creation);
		if (isset($this->date_modification)) $this->date_modification=trim($this->date_modification);
		if (isset($this->userid)) $this->userid=trim($this->userid);
		if (isset($this->user_creation)) $this->user_creation=trim($this->user_creation);
		if (isset($this->user_modification)) $this->user_modification=trim($this->user_modification);
		if (isset($this->element_id)) $this->element_id=trim($this->element_id);

 /******************************************************************************
  * 
  *              Core funciton
  * 
  *****************************************************************************/ 
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
            $lien = '<a href="'.DOL_URL_ROOT.'/timesheet/TimesheetActivity.php?id='.$id.'&action=view">'; // FIX ME, not possible in the Custom folder
        }else if (!empty($ref)){
            $lien = '<a href="'.DOL_URL_ROOT.'/timesheet/TimesheetActivity.php?ref='.$ref.'&action=view">';
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
                
                
  /******************************************************************************
  * 
  *              display  funciton
  * 
  *****************************************************************************/  
    
    
    
    
    
/******************************************************************************
  * 
  *              test  funciton
  * 
  *****************************************************************************/  
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
		
		$this->date_start='';
		$this->date_end='';
		$this->time_start='';
		$this->time_end='';
		$this->weekdays='';
		$this->redundancy='';
		$this->timetype='';
		$this->status='';
		$this->priority='';
		$this->note='';
		$this->date_creation='';
		$this->date_modification='';
		$this->userid='';
		$this->user_creation='';
		$this->user_modification='';
		$this->element_id='';

		
	}

}
