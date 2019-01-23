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
 *  \file       dev/attendanceevents/attendanceevent.class.php
 *  \ingroup    timesheet othermodule1 othermodule2
 *  \brief      This file is an example for a CRUD class file (Create/Read/Update/Delete)
 *				Initialy built by build_class_from_table on 2018-11-05 20:22
 */

// Put here all includes required by your class file
require_once(DOL_DOCUMENT_ROOT."/core/class/commonobject.class.php");
//require_once(DOL_DOCUMENT_ROOT."/societe/class/societe.class.php");
//require_once(DOL_DOCUMENT_ROOT."/product/class/product.class.php");
require_once 'class/TimesheetTask.class.php';
require_once 'core/lib/timesheet.lib.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/task.class.php';

DEFINE(EVENT_HEARTBEAT, 1);
define(EVENT_START,2);
define(EVENT_STOP,3);
$attendanceeventStatusPictoArray=array(0=> 'statut7',1=>'statut3',2=>'statut8',3=>'statut4');
$attendanceeventStatusArray=array(0=> 'Draft',1=>'Validated',2=>'Cancelled',3 =>'Payed');
/**
 *	Put here description of your class
 */
class Attendanceevent extends CommonObject
{
    /**
     * @var string ID to identify managed object
     */				//!< To return several error codes (or messages)
    public $element='attendanceevent';			//!< Id that identify managed objects
    /**
     * @var string Name of table without prefix where object is stored
     */    
    public $table_element='attendance_event';		//!< Name of table without prefix where object is stored

    public $id;
    // BEGIN OF automatic var creation (from db)
    
	public $datetime_event='';
	public $event_location_ref;
	public $event_type;
	public $note;
	public $date_modification='';
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
       // private $tasks; // aarray of tasktimesheet

    
    // END OF automatic var creation
public $datetime_event_start;

    /**
     *  Constructor
     *
     *  @param	DoliDb		$db      Database handler
     *  @param	object          $userid    userid 
     */
    function __construct($db,$userid)
    {
        $this->db = $db;
        $this->userid=$userid;
        return 1;
    }


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

		// Clean parameters
        $this->cleanParam();
		// Check parameters
		// Put here code to add control on parameters values

        // Insert request
        $sql = "INSERT INTO ".MAIN_DB_PREFIX.$this->table_element."(";
        $sql.= 'datetime_event,';
        $sql.= 'event_location_ref,';
        $sql.= 'event_type,';
        $sql.= 'note,';
        $sql.= 'fk_userid,';
        $sql.= 'fk_third_party,';
        $sql.= 'fk_task,';
        $sql.= 'fk_project,';
        $sql.= 'token,';
        $sql.= 'status,';   
        $sql.= 'date_modification,fk_user_modification';
        $sql.= ") VALUES (";
        $sql.=' '.(empty($this->datetime_event) || dol_strlen($this->datetime_event)==0?'NULL':"'".$this->db->idate($this->datetime_event)."'").',';
        $sql.=' '.(empty($this->event_location_ref)?'NULL':"'".$this->db->escape($this->event_location_ref)."'").',';
        $sql.=' '.(empty($this->event_type)?'NULL':"'".$this->event_type."'").',';
        $sql.=' '.(empty($this->note)?'NULL':"'".$this->db->escape($this->note)."'").',';
        $sql.=' '.(empty($this->userid)?'NULL':"'".$this->userid."'").',';
        $sql.=' '.(empty($this->third_party)?'NULL':"'".$this->third_party."'").',';
        $sql.=' '.(empty($this->task)?'NULL':"'".$this->task."'").',';
        $sql.=' '.(empty($this->project)?'NULL':"'".$this->project."'").',';
        $sql.=' '.(empty($this->token)?'NULL':"'".$this->token."'").',';
        $sql.=' '.(empty($this->status)?'NULL':"'".$this->status."'").'';   
        $sql.=' , NOW(),\''.$user->id.'\'';   
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
     *  @param	object          $user	user to find the latest event wich is not closed
     *  @param	string          $startToken	token used to find the start event
     *  @return int          	<0 if KO, >0 if OK
     */
    function fetch($id,$user=null,$startToken='',$type=3)
    {
    	global $langs;

        if (empty($id) && empty($user) && empty($startToken)){
            return -1;
            dol_syslog(get_class($this)."::fetch",'errors');
        }
        $sql = "SELECT";
        $sql.= " t.rowid,";
        $sql.=' t.datetime_event,';
        $sql.=' t.event_location_ref,';
        $sql.=' t.event_type,';
        $sql.=' t.note,';
        $sql.=' t.date_modification,';
        $sql.=' t.fk_userid,';
        $sql.=' t.fk_user_modification,';
        $sql.=' t.fk_third_party,';
        $sql.=' t.fk_task,';
        $sql.=' t.fk_project,';
        $sql.=' t.token,';
        $sql.=' t.status';
        $sql.= " FROM ".MAIN_DB_PREFIX.$this->table_element." as t";
        $sql.= " WHERE ";
        if (!empty($id))$sql.= "t.rowid = '".$id;
        else if (!empty($user))$sql.=  " t.fk_userid = '".$user->id;
        else if (!empty($startToken))  $sql.= "  t.event_type='".$type."' AND t.token='".$startToken;
        else return -1;
        $sql.= "' ORDER BY datetime_event DESC" ;
        $this->db->plimit(1, 0);
    	dol_syslog(get_class($this)."::fetch");
        $resql=$this->db->query($sql);
        if ($resql && $this->db->num_rows($resql))
        {
            $obj = $this->db->fetch_object($resql);
            // load the object only if  not an stop event while using the user
            if (!($obj->event_type==EVENT_STOP  && !empty($user->id)))
            {
                
                $this->id    = $obj->rowid; 
                $this->datetime_event = $this->db->jdate($obj->datetime_event);
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
                $this->datetime_event_start=$this->datetime_event;
                if($this->event_type!=EVENT_START ){
                    $staticAttendance= new Attendanceevent($this->db, $user->id);
                    if($staticAttendance->fetch('','',$obj->token,2)){
                        $this->datetime_event_start=$staticAttendance->datetime_event;
                    }
                }
            }else{
                $this->initAsSpecimen();
                $this->userid=$user->id;
            }
            $this->db->free($resql);
            $this->getInfo();
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
	$error=0;
        // Clean parameters
        $this->cleanParam(true);
        // Check parameters
        // Put here code to add a control on parameters values
        // Update request
        $sql = "UPDATE ".MAIN_DB_PREFIX.$this->table_element." SET";
        $sql.= $this->setSQLfields($user);
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
	global $conf, $langs;


        if (! empty($conf->dol_no_mouse_hover)) $notooltip=1;   // Force disable tooltips
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
        $linkclose='';
        if (empty($notooltip))
        {
            if (! empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER))
            {
                $label=$langs->trans("Showspread");
                $linkclose.=' alt="'.dol_escape_htmltag($label, 1).'"';
            }
            $linkclose.=' title="'.dol_escape_htmltag($label, 1).'"';
            $linkclose.=' class="classfortooltip'.($morecss?' '.$morecss:'').'"';
        }else $linkclose = ($morecss?' class="'.$morecss.'"':'');
        
        if($id){
            $lien = '<a href="'.dol_buildpath('/timesheet/Attendanceevent_card.php',1).'id='.$id.'&action=view"'.$linkclose.'>';
        }else if (!empty($ref)){
            $lien = '<a href="'.dol_buildpath('/timesheet/Attendanceevent_card.php',1).'?ref='.$ref.'&action=view"'.$linkclose.'>';
        }else{
            $lien =  "";
        }
        $lienfin=empty($lien)?'':'</a>';

    	$picto='generic';
        $label = '<u>' . $langs->trans("spread") . '</u>';
        $label.= '<br>';
        if($ref){
            $label.=$langs->trans("Red").': '.$ref;
        }else if($id){
            $label.=$langs->trans("#").': '.$id;
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
	 *  Retourne select libelle du status (actif, inactif)
	 *
	 *  @param	object 		$form          form object that should be created	
      *  *  @return	string 			       html code to select status
	 */
	function selectLibStatut($form,$htmlname='Status')
	{
            global $attendanceeventStatusPictoArray,$attendanceeventStatusArray;
            return $form->selectarray($htmlname,$attendanceeventStatusArray,$this->status);
	}   
    /**
	 *  Retourne le libelle du status (actif, inactif)
	 *
	 *  @param	int		$mode          0=libelle long, 1=libelle court, 2=Picto + Libelle court, 3=Picto, 4=Picto + Libelle long, 5=Libelle court + Picto
	 *  @return	string 			       Label of status
	 */
	function getLibStatut($mode=0)
	{
		return $this->LibStatut($this->status,$mode);
	}
	/**
	 *  Return the status
	 *
	 *  @param	int		$status        	Id status
	 *  @param  int		$mode          	0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto, 6=Long label + Picto
	 *  @return string 			       	Label of status
	 */
	static function LibStatut($status,$mode=0)
	{
		global $langs,$attendanceeventStatusPictoArray,$attendanceeventStatusArray;
		if ($mode == 0)
		{
			$prefix='';
			return $langs->trans($attendanceeventStatusArray[$status]);
		}
		if ($mode == 1)
		{
			return $langs->trans($attendanceeventStatusArray[$status]);
		}
		if ($mode == 2)
		{
			 return img_picto($attendanceeventStatusArray[$status],$attendanceeventStatusPictoArray[$status]).' '.$langs->trans($attendanceeventStatusArray[$status]);
		}
		if ($mode == 3)
		{
			 return img_picto($attendanceeventStatusArray[$status],$attendanceeventStatusPictoArray[$status]);
		}
		if ($mode == 4)
		{
			 return img_picto($attendanceeventStatusArray[$status],$attendanceeventStatusPictoArray[$status]).' '.$langs->trans($attendanceeventStatusArray[$status]);
		}
		if ($mode == 5)
		{
			 return $langs->trans($attendanceeventStatusArray[$status]).' '.img_picto($attendanceeventStatusArray[$status],$attendanceeventStatusPictoArray[$status]);
		}
		if ($mode == 6)
		{
			 return $langs->trans($attendanceeventStatusArray[$status]).' '.img_picto($attendanceeventStatusArray[$status],$attendanceeventStatusPictoArray[$status]);
		}
	}

    /**
     *  Delete object in database
     *
    *	@param  User	$user        User that deletes
    *   @param  int		$notrigger	 0=launch triggers after, 1=disable triggers
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
        else if ($this->db->affected_rows($resql)==0){$error++;$this->errors[]="Item no found in database"; }

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
        $object=new Attendanceevent($this->db);
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
     *	Initialise object with example values
     *	Id must be 0 if object instance is a specimen
     *
     *	@return	void
     */
    function initAsSpecimen()
    {
        $this->id=0;
        $this->datetime_event='';
        $this->datetime_event_start='';
        $this->event_location_ref='';
        $this->event_type=0;
        $this->note='';
        $this->date_modification='';
        $this->userid='';
        $this->user_modification='';
        $this->third_party='';
        $this->task='';
        $this->project='';
        $this->third_partyLabel='';
        $this->taskLabel='';
        $this->projectLabel='';
        $this->token='';
        $this->status='';

        
    }
    /**
     *	will clean the parameters
     *	
     *
     *	@return	void
     */       
    function cleanParam(){   
        if (!empty($this->datetime_event)) $this->datetime_event=trim($this->datetime_event);
        if (!empty($this->event_location_ref)) $this->event_location_ref=trim($this->event_location_ref);
        if (!empty($this->event_type)) $this->event_type=trim($this->event_type);
        if (!empty($this->note)) $this->note=trim($this->note);
        if (!empty($this->date_modification)) $this->date_modification=trim($this->date_modification);
        if (!empty($this->userid)) $this->userid=trim($this->userid);
        if (!empty($this->user_modification)) $this->user_modification=trim($this->user_modification);
        if (!empty($this->third_party)) $this->third_party=trim($this->third_party);
        if (!empty($this->task)) $this->task=trim($this->task);
        if (!empty($this->project)) $this->project=trim($this->project);
        if (!empty($this->token)) $this->token=trim($this->token);
        if (!empty($this->status)) $this->status=trim($this->status);
    }
     /**
     *	will create the sql part to update the parameters
     *	
     *
     *	@return	void
     */    
    function setSQLfields($user){
        $sql='';
        $sql.=' datetime_event='.(dol_strlen($this->datetime_event)!=0 ? "'".$this->db->idate($this->datetime_event)."'":'null').',';
        $sql.=' event_location_ref='.(empty($this->event_location_ref)!=0 ? 'null':"'".$this->db->escape($this->event_location_ref)."'").',';
        $sql.=' event_type='.(empty($this->event_type)!=0 ? 'null':"'".$this->event_type."'").',';
        $sql.=' note='.(empty($this->note)!=0 ? 'null':"'".$this->db->escape($this->note)."'").',';
        $sql.=' date_modification=NOW() ,';
        $sql.=' fk_userid='.(empty($this->userid)!=0 ? 'null':"'".$this->userid."'").',';
        $sql.=' fk_user_modification="'.$user->id.'",';
        $sql.=' fk_third_party='.(empty($this->third_party)!=0 ? 'null':"'".$this->third_party."'").',';
        $sql.=' fk_task='.(empty($this->task)!=0 ? 'null':"'".$this->task."'").',';
        $sql.=' fk_project='.(empty($this->project)!=0 ? 'null':"'".$this->project."'").',';
        $sql.=' token='.(empty($this->token)!=0 ? 'null':"'".$this->token."'").',';
        $sql.=' status='.(empty($this->status)!=0 ? 'null':"'".$this->status."'").'';
        return $sql;
    }

    /**
     *  Will start a new attendance and return the result in json
     *
     *  @param  int		$customer	 customer id on which the attendance is register 
     *  @param  int		$project	 project id on which the attendance is register 
     *  @param  int		$task            task id on which the attendance is register 
     *  @return	json				 return the json of the object started
     */    
    function ajaxStart($user,$json='',$customer='',$project='',$task=''){

        if(empty($task) && empty($project) && empty($customer)) return '{"errorType":"startError","error":"no event to start"}';
        $location_ref='';
        //load old if any
        if(!empty($json)){
            $this->unserialize($json, 1);
            //save the location ref 
            $location_ref=$this->event_location_ref;
            //close the most recent one if any 
            $this->ajaxStop($user,$json);
            //$this->status=
        }        
//erase the data
        $status=$this->status;
        $this->initAsSpecimen();
        
        $this->userid=$user->id;
        //load the data of the new
        if (!empty($task)){
            $this->task=trim($task);
            $this->getInfo();
        }
        if (!empty($project)) $this->project=trim($project);
        if (!empty($customer)) $this->third_party=trim($customer);
        $this->token=  getToken();
        $this->event_type= EVENT_START;
        $this->datetime_event=  mktime()+1;
        $this->datetime_event_start=  $this->datetime_event;
        $this->event_location_ref=$location_ref;
        $this->create($user);
        
        //$this->getInfo();
        $this->status=$status;
        return $this->serialize(2);
    }


    
    /**
     *  Will stop the  attendance and return the result in json
     *
     *  @param  string		$json	 json of the request
     *  @return	int					 <0 if KO, >0 if OK
     */    
    function ajaxStop($user,$json=''){
        global $conf;   
                $location_ref='';
        $note='';
        $tokenJson='';
        $retJson='';
        if(!empty($json)){
            $this->unserialize($json, 1);
            $this->status="";
            $location_ref=$this->event_location_ref;
            $note=$this->note;
            $tokenJson=$this->token;
            $this->fetch('','',$tokenJson);
        }  else {
             $this->fetch('',$user);   
        }
        
        $tokenDb=$this->token;
        if(empty($tokenDb) ){  //00
            $this->status='{"text":"No active chrono found","type":errors","param:""}';
        }else if($this->event_type==3){
            $this->status='{"text":"Chrono already stopped","type":errors","param:""}';
        }else{// 11 && 10
            if(!empty($tokenJson)){ //11
                $this->event_location_ref=$location_ref;
                $this->note=$note;
            }
            $this->event_type=EVENT_STOP;
            $this->datetime_event=  mktime();
            $this->create($user);
            
        //if ($conf->global->ATTENDANCE_CREATE_TIMESPENT==true) 
            $this->createTimeSpend($user,$tokenDb); //FIXME
            $retJson=$this->serialize(2);
        }
        return $retJson ;
    }
    
    /**
     *  Will register an hearbear for an attendance and return the result in json
     *
     *  @param  int		$customer	 customer id on which the attendance is register 
     *  @param  int		$project	 project id on which the attendance is register 
     *  @param  int		$task            task id on which the attendance is register 
     *  @return	int					 <0 if KO, >0 if OK
     */    
    function ajaxHeartbeat($user,$json){
        $location_ref='';
        $note='';
        $tokenJson='';
        $retJson='';
        if(!empty($json)){
            $this->unserialize($json, 1);
            $location_ref=$this->event_location_ref;
            $note=$this->note;
            $tokenJson=$this->token;
        }
        $this->fetch('',$user);
        $tokenDb=$this->token;
        if(empty($tokenDb) && empty($tokenJson)){  //00
            $retJson='{"errorType":"loadError","error":"no event active"}';
        }else if (empty($tokenDb) && !empty($tokenJson)){ // json recieved with token //01
            $retJson='{"errorType":"heartbeatError","error":"event not active"}';
        }else if(!empty($tokenDb)){// 11 && 10
            if(!empty($tokenJson)){ //11
                $this->event_location_ref=$location_ref;
                $this->note=$note;
            }else{ // info not already loaded 10
                $this->getInfo();
            }
            // update the required fields
                $this->datetime_event=  mktime();
                if($this->event_type!=EVENT_HEARTBEAT){ // create an heartbeat only if there is none
                    $this->event_type=EVENT_HEARTBEAT;
                    $this->create($user);
                }else{
                    $this->update($user);
                }
            
            $retJson=$this->serialize(2);
        }// 
        return $retJson;
        
    }
 /** create timespend on the user
  * @param type $user
  * @param type $token
  */
function createTimeSpend($user,$token=''){
    //if(empty($token))$token=$this->token;
    if(!empty($token)){
        $this->fetch('','',$token);
        if($this->event_type==EVENT_STOP && $this->task>0){
            $start=  strtotime("midnight",(int)$this->datetime_event);
            $end=  strtotime("tomorrow",(int)$this->datetime_event)-1;
            $duration=$this->datetime_event -$this->datetime_event_start;
            $tta= new TimesheetTask($this->db,$this->task);
            $tta->getActuals($start, $end,$this->userid);
            //var_dump($tta->getTaskTab());
            $arrayRes=$tta->saveTaskTime($user,$duration,$this->note,0,true);
            $this->status=TimesheetsetEventMessage($arrayRes,true);
            if(is_array($arrayRes) && array_sum($arrayRes)-$arrayRes['updateError']>0) $tta->updateTimeUsed();
            //TimesheetsetEventMessage($arrayRes);
        }
    }
}
    
    /* Function generate the HTML code to use the clock
    *  @return     html code                                       result
    */    
    function printHTMLTaskList($headers,$userid=''){
        $tasksList=$this->fetchTasks($userid);
        $html='';
        if(is_array($tasksList))foreach($tasksList as $task){
            $html.=$task->getAttendanceLine($headers,($task->id==$this->task));
        }
        return $html;
    }
    /* Function generate the HTML code to use the clock
    *  @return     html code                                       result
    */    
    function printHTMLClock(){ 
        global $langs;
        print '<div>';
            print '<div style="width:50px%;height:60px;float:left;vertical-align:middle" >';
                print '<img height="64px" id = "mainPlayStop" src="img/'.(($this->id==0)?'play-arrow':'stop-square');
                print '.png" onClick=startStop(event,'.$this->userid.',null) style="cursor:pointer;vertical-align:middle">  ';
            print '</div>';          
            print '<div style="width:40%;height:60px;float:left" >';
                print '<textarea name="eventNote" id="eventNote" style="width:80%;height:100%"></textarea>';
            print '</div>';       
            print '<div style="width:40%;float:left">';       
                print '<span id="stopwatch"></span>';
                print '<div>'.$langs->trans('Customer').': <span id="customer">&nbsp;</span></div>';
                print '<div>'.$langs->trans('Project').': <span  id="project">&nbsp;</span></div>';
                print '<div>'.$langs->trans('Task').': <span  id="task">&nbsp;</span></div>';
            print '</div>';   
        print '</div>';  

    }


 
    /*
 * function to genegate the timesheet tab
 * 
 *  @param    int              	$userid                   user id to fetch the timesheets
 *  @return     array(string)                                             array of timesheet (serialized)
 */
 function fetchTasks($userid='',$date=''){     
    global $conf;
    if(empty($date))$date=time();
    if($userid==''){$userid=$this->userid;}
    $this->userid=$userid;
    $datestart=strtotime('yesterday midnight',$date);
    $datestop= strtotime('today midnight',$date);
     $tasksList=array();   
    $sql ='SELECT DISTINCT element_id as taskid,prj.fk_soc,prj.ref,tsk.ref';
    $sql.=" FROM ".MAIN_DB_PREFIX."element_contact as ec"; 
    $sql.=' LEFT JOIN '.MAIN_DB_PREFIX.'c_type_contact as ctc ON (ctc.rowid=ec.fk_c_type_contact  AND ctc.active=\'1\') ';
    $sql.=' LEFT JOIN '.MAIN_DB_PREFIX.'projet_task as tsk ON tsk.rowid=ec.element_id ';
    $sql.=' LEFT JOIN '.MAIN_DB_PREFIX.'projet as prj ON prj.rowid= tsk.fk_projet ';
    $sql.=" WHERE ec.fk_socpeople='".$userid."' AND ctc.element='project_task' ";
    if($conf->global->TIMESHEET_HIDE_DRAFT=='1'){$sql.=' AND prj.fk_statut>\'0\' '; }
    $sql.=' AND (prj.datee>=\''.$this->db->idate($datestart).'\' OR prj.datee IS NULL)';
    $sql.=' AND (prj.dateo<=\''.$this->db->idate($datestop).'\' OR prj.dateo IS NULL)';
    $sql.=' AND (tsk.datee>=\''.$this->db->idate($datestart).'\' OR tsk.datee IS NULL)';
    $sql.=' AND (tsk.dateo<=\''.$this->db->idate($datestop).'\' OR tsk.dateo IS NULL)';
    $sql.='  ORDER BY prj.fk_soc,prj.ref,tsk.ref ';

     dol_syslog("timesheetEvent::fetchTask ", LOG_DEBUG);

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
                    $tasksList[$i] = new TimesheetTask($this->db);
                    $tasksList[$i]->id= $obj->taskid;    
                    $tasksList[$i]->userId= $this->userid;
                    $tasksList[$i]->getTaskInfo();
                      $i++;         
            }
            $this->db->free($resql);
             $i = 0;

            return $tasksList;

    }else
    {
            dol_print_error($this->db);
            return -1;
    }
 }
    /*
    * function to save attendance event as a string
    * @param    int     $mode   0=>serialize, 1=> json_encode, 2 => json_encode PRETTY PRINT
    * @return   string       serialized object
    */
 public function serialize($mode=0){
    $ret='';
    $array= array();
        $array['id']= $this->id;
	$array['datetime_event']= $this->datetime_event;
        $array['datetime_event_start']= $this->datetime_event_start;
	$array['event_location_ref']= $this->event_location_ref;
	$array['event_type']= $this->event_type;
	$array['note']= $this->note;
	$array['date_modification']= $this->date_modification;
	$array['userid']= $this->userid;
	$array['user_modification']= $this->user_modification;
	$array['third_party']= $this->third_party;
	$array['task']= $this->task;
	$array['project']= $this->project;
	$array['third_partyLabel']= $this->third_partyLabel;
	$array['taskLabel']= $this->taskLabel;
	$array['projectLabel']= $this->projectLabel;
	$array['token']= $this->token;
	$array['status']= $this->status;
        $array['processedTime']= mktime();
        // working var
        //$array['']= $this->tasks; // aarray of tasktimesheet
     switch($mode)
     {
         default:
         case 0:
             $ret=  serialize($array);
             break;
         case 1:
             $ret=json_encode($array);
             break;
         case 2:
             $ret=json_encode($array, JSON_PRETTY_PRINT);
             break;
     }
      return $ret;
    }
      /* function to load a skeleton as a string
     * @param   string    $str   serialized object
     * @param    int     $mode   0=>serialize, 1=> json_encode, 2 => json_encode PRETTY PRINT
     * @return  int              OK
     */    
       public function unserialize($str,$mode=0){
       $ret='';
       if (empty($str))return -1;
       $array= array();
        switch($mode)
        {
            default:
            case 0:
                $array= unserialize($str);
                break;
            case 1:
            case 2:
                $array=json_decode($str,JSON_OBJECT_AS_ARRAY);
                break;
 /*           case 3:
                $array=$str;
                break;*/
        }

        // automatic unserialisation based on match between property name and key value
        foreach ($array as $key => $value) {
            if(property_exists($this,$key)){
                $this->{$key}=$value;
            }
        }
    }
    
    /*fucntion to get the labels
     *  
     */
    public function getInfo(){
        if(!empty($this->task)){
            $staticTask= new TimesheetTask($this->db);
            $staticTask->id=($this->task);
            $staticTask->userId=($this->userid);
            //$staticTask->fetch($this->task);
            $staticTask->getTaskInfo();
            $this->project=$staticTask->fk_project;
            $this->taskLabel= $staticTask->description;
            $this->projectLabel=$staticTask->ProjectTitle;
            $this->third_party=$staticTask->companyId;
            $this->third_partyLabel=$staticTask->companyName;     
        }else{
            if(!empty($this->project) && empty($this->projectLabel) ){
                $this->projectLabel=print_sellist(array('table'=>"projet",'keyfield'=> 'rowid','fields'=>'title'),$this->project);
            }
            if(!empty($this->third_party) && empty($this->third_partyLabel)){
                $this->third_partyLabel=print_sellist(array('table'=>"societe",'keyfield'=> 'rowid','fields'=>'nom'), $this->third_party);
            }
        }
    }

}
