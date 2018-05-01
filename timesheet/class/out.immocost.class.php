<?php
/* Copyright (C) 2007-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2014	   Juanjo Menent		<jmenent@2byte.es>
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
 *  \file       dev/immocosts/immocost.class.php
 *  \ingroup    mymodule othermodule1 othermodule2
 *  \brief      This file is an example for a CRUD class file (Create/Read/Update/Delete)
 *				Initialy built by build_class_from_table on 2018-04-25 21:06
 */

// Put here all includes required by your class file
require_once(DOL_DOCUMENT_ROOT."/core/class/commonobject.class.php");
//require_once(DOL_DOCUMENT_ROOT."/societe/class/societe.class.php");
//require_once(DOL_DOCUMENT_ROOT."/product/class/product.class.php");


/**
 *	Put here description of your class
 */
class Immocost extends CommonObject
{
    public $db;							//!< To store db handler
    public $error;							//!< To return error code (or message)
    public $errors=array();				//!< To return several error codes (or messages)
    public $element='immocost';			//!< Id that identify managed objects
    public $table_element='immo_cost';		//!< Name of table without prefix where object is stored

    public $id;
    public $prop1;
    public $prop2;


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
        
		$sql.= 'label,';
		$sql.= 'fk_immo_property,';
		$sql.= 'fk_immo_cost_spread,';
		$sql.= 'fk_c_immo_cost_type,';
		$sql.= 'dispatch_status,';
		$sql.= 'date_start,';
		$sql.= 'date_end,';
		$sql.= 'note_public,';
		$sql.= 'amount_ht,';
		$sql.= 'amount_vat,';
		$sql.= 'amount,';
		$sql.= 'fk_user_creation,';
		$sql.= 'date_creation';

        
        $sql.= ") VALUES (";
        
		$sql.=' '.(! isset($this->label)?'NULL':"'".$this->db->escape($this->label)."'").',';
		$sql.=' '.(! isset($this->immo_property)?'NULL':"'".$this->immo_property."'").',';
		$sql.=' '.(! isset($this->immo_cost_spread)?'NULL':"'".$this->immo_cost_spread."'").',';
		$sql.=' '.(! isset($this->c_immo_cost_type)?'NULL':"'".$this->c_immo_cost_type."'").',';
		$sql.=' '.(! isset($this->dispatch_status)?'NULL':"'".$this->dispatch_status."'").',';
		$sql.=' '.(! isset($this->date_start) || dol_strlen($this->date_start)==0?'NULL':"'".$this->db->idate($this->date_start)."'").',';
		$sql.=' '.(! isset($this->date_end) || dol_strlen($this->date_end)==0?'NULL':"'".$this->db->idate($this->date_end)."'").',';
		$sql.=' '.(! isset($this->note_public)?'NULL':"'".$this->db->escape($this->note_public)."'").',';
		$sql.=' '.(! isset($this->amount_ht)?'NULL':"'".$this->amount_ht."'").',';
		$sql.=' '.(! isset($this->amount_vat)?'NULL':"'".$this->amount_vat."'").',';
		$sql.=' '.(! isset($this->amount)?'NULL':"'".$this->amount."'").',';
		$sql.=' "'.$user->id.'",';
		$sql.=' NOW() ';

        
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
     *  @param	string	$ref	Ref
     *  @return int          	<0 if KO, >0 if OK
     */
    function fetch($id,$ref='')
    {
    	global $langs;
        $sql = "SELECT";
		$sql.= " t.rowid,";
		
		$sql.=' t.label,';
		$sql.=' t.fk_immo_property,';
		$sql.=' t.fk_immo_cost_spread,';
		$sql.=' t.fk_c_immo_cost_type,';
		$sql.=' t.dispatch_status,';
		$sql.=' t.date_start,';
		$sql.=' t.date_end,';
		$sql.=' t.note_public,';
		$sql.=' t.amount_ht,';
		$sql.=' t.amount_vat,';
		$sql.=' t.amount,';
		$sql.=' t.fk_user_creation,';
		$sql.=' t.fk_user_modification,';
		$sql.=' t.date_creation,';
		$sql.=' t.date_modification,';
		$sql.=' t.entity';

		
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
                
				$this->label = $obj->label;
				$this->immo_property = $obj->fk_immo_property;
				$this->immo_cost_spread = $obj->fk_immo_cost_spread;
				$this->c_immo_cost_type = $obj->fk_c_immo_cost_type;
				$this->dispatch_status = $obj->dispatch_status;
				$this->date_start = $this->db->jdate($obj->date_start);
				$this->date_end = $this->db->jdate($obj->date_end);
				$this->note_public = $obj->note_public;
				$this->amount_ht = $obj->amount_ht;
				$this->amount_vat = $obj->amount_vat;
				$this->amount = $obj->amount;
				$this->user_creation = $obj->fk_user_creation;
				$this->user_modification = $obj->fk_user_modification;
				$this->date_creation = $this->db->jdate($obj->date_creation);
				$this->date_modification = $this->db->jdate($obj->date_modification);
				$this->entity = $obj->entity;

                
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
	$error=0;
		// Clean parameters
        $this->cleanParam(true);
		// Check parameters
		// Put here code to add a control on parameters values
        // Update request
        $sql = "UPDATE ".MAIN_DB_PREFIX.$this->table_element." SET";
        $sql.= $this->setSQLfields();
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
            $lien = '<a href="'.DOL_URL_ROOT.'/mymodule/immocost_page.php?id='.$id.'&action=view">';
        }else if (!empty($ref)){
            $lien = '<a href="'.DOL_URL_ROOT.'/mymodule/immocost_page.php?ref='.$ref.'&action=view">';
        }else{
            $lien =  "";
        }
        $lienfin=empty($lien)?'':'</a>';

    	$picto='mymodule@mymodule';
        
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

            $object=new Immocost($this->db);

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
            
		$this->label='';
		$this->immo_property='';
		$this->immo_cost_spread='';
		$this->c_immo_cost_type='';
		$this->dispatch_status='';
		$this->date_start='';
		$this->date_end='';
		$this->note_public='';
		$this->amount_ht='';
		$this->amount_vat='';
		$this->amount='';
		$this->user_creation='';
		$this->user_modification='';
		$this->date_creation='';
		$this->date_modification='';
		$this->entity='';

            
	}
 	/**
	 *	will clean the parameters
	 *	
	 *
	 *	@return	void
	 */       
        function cleanParam(){
            
		if (isset($this->label)) $this->label=trim($this->label);
		if (isset($this->immo_property)) $this->immo_property=trim($this->immo_property);
		if (isset($this->immo_cost_spread)) $this->immo_cost_spread=trim($this->immo_cost_spread);
		if (isset($this->c_immo_cost_type)) $this->c_immo_cost_type=trim($this->c_immo_cost_type);
		if (isset($this->dispatch_status)) $this->dispatch_status=trim($this->dispatch_status);
		if (isset($this->date_start)) $this->date_start=trim($this->date_start);
		if (isset($this->date_end)) $this->date_end=trim($this->date_end);
		if (isset($this->note_public)) $this->note_public=trim($this->note_public);
		if (isset($this->amount_ht)) $this->amount_ht=trim($this->amount_ht);
		if (isset($this->amount_vat)) $this->amount_vat=trim($this->amount_vat);
		if (isset($this->amount)) $this->amount=trim($this->amount);
		if (isset($this->user_creation)) $this->user_creation=trim($this->user_creation);
		if (isset($this->user_modification)) $this->user_modification=trim($this->user_modification);
		if (isset($this->date_creation)) $this->date_creation=trim($this->date_creation);
		if (isset($this->date_modification)) $this->date_modification=trim($this->date_modification);

            
        }
         /**
	 *	will create the sql part to update the parameters
	 *	
	 *
	 *	@return	void
	 */    
        function setSQLfields(){
            $sql='';
            
		$sql.=' label='.(empty($this->label)!=0 ? 'null':"'".$this->db->escape($this->label)."'").',';
		$sql.=' fk_immo_property='.(empty($this->immo_property)!=0 ? 'null':"'".$this->immo_property."'").',';
		$sql.=' fk_immo_cost_spread='.(empty($this->immo_cost_spread)!=0 ? 'null':"'".$this->immo_cost_spread."'").',';
		$sql.=' fk_c_immo_cost_type='.(empty($this->c_immo_cost_type)!=0 ? 'null':"'".$this->c_immo_cost_type."'").',';
		$sql.=' dispatch_status='.(empty($this->dispatch_status)!=0 ? 'null':"'".$this->dispatch_status."'").',';
		$sql.=' date_start='.(dol_strlen($this->date_start)!=0 ? "'".$this->db->idate($this->date_start)."'":'null').',';
		$sql.=' date_end='.(dol_strlen($this->date_end)!=0 ? "'".$this->db->idate($this->date_end)."'":'null').',';
		$sql.=' note_public='.(empty($this->note_public)!=0 ? 'null':"'".$this->db->escape($this->note_public)."'").',';
		$sql.=' amount_ht='.(empty($this->amount_ht)!=0 ? 'null':"'".$this->amount_ht."'").',';
		$sql.=' amount_vat='.(empty($this->amount_vat)!=0 ? 'null':"'".$this->amount_vat."'").',';
		$sql.=' amount='.(empty($this->amount)!=0 ? 'null':"'".$this->amount."'").',';
		$sql.=' fk_user_modification="'.$user->id.'",';
		$sql.=' date_modification=NOW() ';

            
            return $sql;
        }
        

}
