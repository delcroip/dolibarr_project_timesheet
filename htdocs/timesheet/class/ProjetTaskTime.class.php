<?php
/* Copyright (C) 2007-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2014       Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2018       Patrick DELCROIX     <pmpdelcroix@gmail.com>
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
 *  \file       dev/projettasktimes/projettasktime.class.php
 *  \ingroup    mymodule othermodule1 othermodule2
 *  \brief      This file is an example for a CRUD class file (Create/Read/Update/Delete)
 *                Initialy built by build_class_from_table on 2019-07-03 22:42
 */

// Put here all includes required by your class file
require_once DOL_DOCUMENT_ROOT."/core/class/commonobject.class.php";
//require_once(DOL_DOCUMENT_ROOT."/societe/class/societe.class.php");
//require_once(DOL_DOCUMENT_ROOT."/product/class/product.class.php");

$projettasktimeStatusPictoArray = array(0=> 'statut7',1=>'statut3',2=>'statut8',3=>'statut4');
$projettasktimeStatusArray = array(0=> 'Draft',1=>'Validated',2=>'Cancelled',3 =>'Payed');
/**
 *    Put here description of your class
 */
class Projettasktime extends CommonObject
{
    /**
     * @var string ID to identify managed object
     */                //!< To return several error codes (or messages)
    public $element='projettasktime';            //!< Id that identify managed objects
    /**
     * @var string Name of table without prefix where object is stored
     */
    public $table_element='projet_task_time';        //!< Name of table without prefix where object is stored

    public $id;
    // BEGIN OF automatic var creation

    public $task;
    public $task_date='';
    public $task_datehour='';
    public $task_date_withhour;
    public $task_duration;
    public $user;
    public $thm;
    public $note;
    public $invoice_id;
    public $invoice_line_id;
    public $import_key;
    public $datec='';
    public $tms='';
    public $status;
    public $task_time_approval;
    // END OF automatic var creation
    /**
     *  Constructor
     *
     *  @param    DoliDb        $db      Database handler
     */
    public function __construct($db)
    {
        $this->db = $db;
        return 1;
    }

    /**
     *  Create object into database
     *
     *  @param    User    $user        User that creates
     *  @param  int        $notrigger   0 = launch triggers after, 1 = disable triggers
     *  @return int                      <0 if KO, Id of created object if OK
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
        $sql.= 'fk_task,';
        $sql.= 'task_date,';
        $sql.= 'task_datehour,';
        $sql.= 'task_date_withhour,';
        $sql.= 'task_duration,';
        $sql.= 'fk_user,';
        $sql.= 'thm,';
        $sql.= 'note,';
        $sql.= 'invoice_id,';
        $sql.= 'invoice_line_id,';
        $sql.= 'import_key,';
        $sql.= 'datec,';
        $sql.= 'status,';
        $sql.= 'fk_task_time_approval';
        $sql.= ") VALUES (";
        $sql.=' '.(empty($this->task)?'NULL':"'".$this->task."'").',';
        $sql.=' '.(empty($this->task_date) || dol_strlen($this->task_date)==0?'NULL':"'".$this->db->idate($this->task_date)."'").',';
        $sql.=' '.(empty($this->task_datehour) || dol_strlen($this->task_datehour)==0?'NULL':"'".$this->db->idate($this->task_datehour)."'").',';
        $sql.=' '.(empty($this->task_date_withhour)?'NULL':"'".$this->task_date_withhour."'").',';
        $sql.=' '.(empty($this->task_duration)?'NULL':"'".$this->task_duration."'").',';
        $sql.=' '.(empty($this->user)?'NULL':"'".$this->user."'").',';
        $sql.=' '.(empty($this->thm)?'NULL':"'".$this->thm."'").',';
        $sql.=' '.(empty($this->note)?'NULL':"'".$this->db->escape($this->note)."'").',';
        $sql.=' '.(empty($this->invoice_id)?'NULL':"'".$this->invoice_id."'").',';
        $sql.=' '.(empty($this->invoice_line_id)?'NULL':"'".$this->invoice_line_id."'").',';
        $sql.=' '.(empty($this->import_key)?'NULL':"'".$this->db->escape($this->import_key)."'").',';
        $sql.=' NOW() ,';
        $sql.=' '.(empty($this->status)?'NULL':"'".$this->status."'").',';
        $sql.=' '.(empty($this->task_time_approval)?'NULL':"'".$this->task_time_approval."'").'';
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
            //$result=$this->call_trigger('MYOBJECT_CREATE', $user);
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
     *  @param    int        $id        Id object
     *  @param    string    $ref    Ref
     *  @return int              <0 if KO, >0 if OK
     */
    public function fetch($id, $ref = '')
    {
        global $langs;
        $sql = "SELECT";
        $sql.= " t.rowid,";
        $sql.=' t.fk_task,';
        $sql.=' t.task_date,';
        $sql.=' t.task_datehour,';
        $sql.=' t.task_date_withhour,';
        $sql.=' t.task_duration,';
        $sql.=' t.fk_user,';
        $sql.=' t.thm,';
        $sql.=' t.note,';
        $sql.=' t.invoice_id,';
        $sql.=' t.invoice_line_id,';
        $sql.=' t.import_key,';
        $sql.=' t.datec,';
        $sql.=' t.tms,';
        $sql.=' t.status,';
        $sql.=' t.fk_task_time_approval';
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
                $this->task = $obj->fk_task;
                $this->task_date = $this->db->jdate($obj->task_date);
                $this->task_datehour = $this->db->jdate($obj->task_datehour);
                $this->task_date_withhour = $obj->task_date_withhour;
                $this->task_duration = $obj->task_duration;
                $this->user = $obj->fk_user;
                $this->thm = $obj->thm;
                $this->note = $obj->note;
                $this->invoice_id = $obj->invoice_id;
                $this->invoice_line_id = $obj->invoice_line_id;
                $this->import_key = $obj->import_key;
                $this->datec = $this->db->jdate($obj->datec);
                $this->tms = $this->db->jdate($obj->tms);
                $this->status = $obj->status;
                $this->task_time_approval = $obj->fk_task_time_approval;
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
     *  @param    User    $user        User that modifies
     *  @param  int        $notrigger     0 = launch triggers after, 1 = disable triggers
     *  @return int                     <0 if KO, >0 if OK
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
            //$result=$this->call_trigger('MYOBJECT_MODIFY', $user);
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
     *    Return clickable name (with picto eventually)
     *
     *    @param        string            $htmlcontent         text to show
     *    @param        int            $id                     Object ID
     *    @param        string            $ref                    Object ref
     *    @param        int            $withpicto        0 = _No picto, 1 = Includes the picto in the linkn, 2 = Picto only
     *    @return        string                        String with URL
     */
    public function getNomUrl($htmlcontent, $id = 0, $ref = '', $withpicto = 0)
    {
    global $conf, $langs;
        if (! empty($conf->dol_no_mouse_hover)) $notooltip = 1;   // Force disable tooltips
        $result='';
        if(empty($ref) && $id==0){
            if(isset($this->id))  {
                $id=$this->id;
            }elseif (isset($this->rowid)){
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
        }else{
            $linkclose = ($morecss?' class="'.$morecss.'"':'');
        }

        if($id){
            $lien = '<a href="'.dol_buildpath('/mymodule/Projettasktime_card.php', 1).'id='.$id.'&action = view"'.$linkclose.'>';
        }elseif (!empty($ref))
        {
            $lien = '<a href="'.dol_buildpath('/mymodule/Projettasktime_card.php', 1).'?ref='.$ref.'&action = view"'.$linkclose.'>';
        }else
        {
            $lien =  "";
        }
        $lienfin = empty($lien)?'':'</a>';
        $picto='generic';
        $label = '<u>' . $langs->trans("spread") . '</u>';
        $label.= '<br>';
        if($ref)
        {
            $label.=$langs->trans("Red").': '.$ref;
        }elseif($id)
        {
            $label.=$langs->trans("#").': '.$id;
        }
        
        if ($withpicto == 1 )
        {
            $result .= ($lien.img_object($label, $picto).$htmlcontent.$lienfin);
        }elseif ($withpicto == 2 )
        {
            $result .= $lien.img_object($label, $picto).$lienfin;
        }else
        {
            $result .= $lien.$htmlcontent.$lienfin;
        }
        return $result;
    }
        /**
     *  Retourne select libelle du status (actif, inactif)
     *
     *  @param    object         $form          form object that should be create
     *  @param    string         $htmlname     name
         *  @return    string                    html code to select status
     */
    public function selectLibStatut($form, $htmlname = 'Status')
    {
            global $projettasktimeStatusPictoArray, $projettasktimeStatusArray;
            return $form->selectarray($htmlname, $projettasktimeStatusArray, $this->status);
    }
         /**
     *  Retourne le libelle du status (actif, inactif)
     *
     *  @param    int        $mode          0 = libelle long, 1 = libelle court, 2 = Picto + Libelle court, 3 = Picto, 4 = Picto + Libelle long, 5 = Libelle court + Picto
     *  @return    string                    Label of status
     */
    public function getLibStatut($mode = 0)
    {
        return $this->LibStatut($this->status, $mode);
    }
    /**
     *  Return the status
     *
     *  @param    int        $status            Id status
     *  @param  int        $mode              0 = long label, 1 = short label, 2 = Picto + short label, 3 = Picto, 4 = Picto + long label, 5 = Short label + Picto, 6 = Long label + Picto
     *  @return string                        Label of status
     */
    static public function libStatut($status, $mode = 0)
    {
        global $langs, $projettasktimeStatusPictoArray, $projettasktimeStatusArray;
        if ($mode == 0)
        {
            $prefix='';
            return $langs->trans($projettasktimeStatusArray[$status]);
        }
        if ($mode == 1)
        {
            return $langs->trans($projettasktimeStatusArray[$status]);
        }
        if ($mode == 2)
        {
            return img_picto($projettasktimeStatusArray[$status], $projettasktimeStatusPictoArray[$status]).' '.$langs->trans($projettasktimeStatusArray[$status]);
        }
        if ($mode == 3)
        {
            return img_picto($projettasktimeStatusArray[$status], $projettasktimeStatusPictoArray[$status]);
        }
        if ($mode == 4)
        {
            return img_picto($projettasktimeStatusArray[$status], $projettasktimeStatusPictoArray[$status]).' '.$langs->trans($projettasktimeStatusArray[$status]);
        }
        if ($mode == 5)
        {
            return $langs->trans($projettasktimeStatusArray[$status]).' '.img_picto($projettasktimeStatusArray[$status], $projettasktimeStatusPictoArray[$status]);
        }
        if ($mode == 6)
        {
            return $langs->trans($projettasktimeStatusArray[$status]).' '.img_picto($projettasktimeStatusArray[$status], $projettasktimeStatusPictoArray[$status]);
        }
    }

    /**
     *  Delete object in database
     *
    *    @param  User    $user        User that deletes
    *   @param  int        $notrigger     0 = launch triggers after, 1 = disable triggers
     *  @return    int                     <0 if KO, >0 if OK
     */
    public function delete($user, $notrigger = 0)
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
        //$result=$this->call_trigger('MYOBJECT_DELETE', $user);
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
        elseif ($this->db->affected_rows($resql)==0){$error++;$this->errors[]="Item no found in database"; }
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
     *    Load an object from its id and create a new one in database
     *
     *    @param    int        $fromid     Id of object to clone
     *     @return    int                    New id of clone
     */
    public function createFromClone($fromid)
    {
        global $user, $langs;
        $error = 0;
        $object = new Projettasktime($this->db);
        $this->db->begin();
        // Load source object
        $object->fetch($fromid);
        $object->id = 0;
        $object->statut = 0;
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
     *    Initialise object with example values
     *    Id must be 0 if object instance is a specimen
     *
     *    @return    void
     */
    public function initAsSpecimen()
    {
        $this->id = 0;
        $this->task='';
        $this->task_date='';
        $this->task_datehour='';
        $this->task_date_withhour='';
        $this->task_duration='';
        $this->user='';
        $this->thm='';
        $this->note='';
        $this->invoice_id='';
        $this->invoice_line_id='';
        $this->import_key='';
        $this->datec='';
        $this->tms='';
        $this->status='';
        $this->task_time_approval='';
    }
    /**
     *    will clean the parameters
     *
     *
     *    @return    void
     */
    public function cleanParam()
    {
        if (!empty($this->task)) $this->task = trim($this->task);
        if (!empty($this->task_date)) $this->task_date = trim($this->task_date);
        if (!empty($this->task_datehour)) $this->task_datehour = trim($this->task_datehour);
        if (!empty($this->task_date_withhour)) $this->task_date_withhour = trim($this->task_date_withhour);
        if (!empty($this->task_duration)) $this->task_duration = trim($this->task_duration);
        if (!empty($this->user)) $this->user = trim($this->user);
        if (!empty($this->thm)) $this->thm = trim($this->thm);
        if (!empty($this->note)) $this->note = trim($this->note);
        if (!empty($this->invoice_id)) $this->invoice_id = trim($this->invoice_id);
        if (!empty($this->invoice_line_id)) $this->invoice_line_id = trim($this->invoice_line_id);
        if (!empty($this->import_key)) $this->import_key = trim($this->import_key);
        if (!empty($this->datec)) $this->datec = trim($this->datec);
        if (!empty($this->status)) $this->status = trim($this->status);
        if (!empty($this->task_time_approval)) $this->task_time_approval = trim($this->task_time_approval);
        }
    /**
    *    will create the sql part to update the parameters
    *
    *   @param object   $user   user changing the sql
    *    @return    void
    */
    public function setSQLfields($user)
    {
        $sql='';
        $sql.=' fk_task='.(empty($this->task)!=0 ? 'null':"'".$this->task."'").',';
        $sql.=' task_date='.(dol_strlen($this->task_date)!=0 ? "'".$this->db->idate($this->task_date)."'":'null').',';
        $sql.=' task_datehour='.(dol_strlen($this->task_datehour)!=0 ? "'".$this->db->idate($this->task_datehour)."'":'null').',';
        $sql.=' task_date_withhour='.(empty($this->task_date_withhour)!=0 ? 'null':"'".$this->task_date_withhour."'").',';
        $sql.=' task_duration='.(empty($this->task_duration)!=0 ? 'null':"'".$this->task_duration."'").',';
        $sql.=' fk_user='.(empty($this->user)!=0 ? 'null':"'".$this->user."'").',';
        $sql.=' thm='.(empty($this->thm)!=0 ? 'null':"'".$this->thm."'").',';
        $sql.=' note='.(empty($this->note)!=0 ? 'null':"'".$this->db->escape($this->note)."'").',';
        $sql.=' invoice_id='.(empty($this->invoice_id)!=0 ? 'null':"'".$this->invoice_id."'").',';
        $sql.=' invoice_line_id='.(empty($this->invoice_line_id)!=0 ? 'null':"'".$this->invoice_line_id."'").',';
        $sql.=' import_key='.(empty($this->import_key)!=0 ? 'null':"'".$this->db->escape($this->import_key)."'").',';
        $sql.=' status='.(empty($this->status)!=0 ? 'null':"'".$this->status."'").',';
        $sql.=' fk_task_time_approval='.(empty($this->task_time_approval)!=0 ? 'null':"'".$this->task_time_approval."'").'';
        return $sql;
    }
    /*
    * function to save a projettasktime as a string
    * @param    int     $mode   0=>serialize, 1=> json_encode, 2 => json_encode PRETTY PRINT
    * @return   string       serialized object
    */
    public function serialize($mode = 0)
    {
        $ret='';
        $array = array();
        $array['task']=$this->task;
        $array['task_date']=$this->task_date;
        $array['task_datehour']=$this->task_datehour;
        $array['task_date_withhour']=$this->task_date_withhour;
        $array['task_duration']=$this->task_duration;
        $array['user']=$this->user;
        $array['thm']=$this->thm;
        $array['note']=$this->note;
        $array['invoice_id']=$this->invoice_id;
        $array['invoice_line_id']=$this->invoice_line_id;
        $array['import_key']=$this->import_key;
        $array['datec']=$this->datec;
        $array['status']=$this->status;
        $array['task_time_approval']=$this->task_time_approval;
        $array['processedTime']= mktime();
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
    /* function to load a projettasktime as a string
    * @param   string    $str   serialized object
    * @param    int     $mode   0=>serialize, 1=> json_encode, 2 => json_encode PRETTY PRINT
    * @return  int              OK
    */
    public function unserialize($str, $mode = 0)
    {
        $ret='';
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
        foreach($array as $key => $value)
        {
            if(isset($this->{$key}))$this->{$key} = $value;
        }
    }
}
