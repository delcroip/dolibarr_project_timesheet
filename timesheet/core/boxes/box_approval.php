<?php
/* Copyright (C) 2016 delcroip <pmpdelcroix@gmail.com>
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
 *	\file       htdocs/core/boxes/box_approval.php
 *	\ingroup    factures
 *	\brief      Module de generation de l'affichage de la box factures
 */
include_once DOL_DOCUMENT_ROOT.'/core/boxes/modules_boxes.php';
global $dolibarr_main_url_root_alt;
$res=0;


/**
 * Class to manage the box to show last invoices
 */
class box_approval extends ModeleBoxes
{
	var $boxcode="nbTsToApprove";
	var $boximg="timesheet";
	var $boxlabel="BoxApproval";
	var $depends = array("timesheet");

	var $db;
	var $param;

	var $info_box_head = array();
	var $info_box_contents = array();


	/**
	 *  Load data into info_box_contents array to show array later.
	 *
	 *  @param	int		$max        Maximum number of records to load
     *  @return	void
	 */
	function loadBox($max=5)
	{
		global $conf, $user, $langs, $db;

		$this->max=$max;




        $userid=  is_object($user)?$user->id:$user;
		$text =$langs->trans('Timesheet');
		$this->info_box_head = array(
				'text' => $text,
				'limit'=> dol_strlen($text)
		);
                
        if ($user->rights->timesheet->approval) {
                        $sql = 'SELECT';
           $subordinate=implode(',',  getSubordinates($db, $userid,2));
           if($subordinate=='')$subordinate=0;
           $tasks=implode(',', array_keys(getTasks($db, $userid)));
           if($tasks=='')$tasks=0;
           // $sql.=' COUNT(t.rowid) as nb,';
            $sql.=' COUNT(DISTINCT t.rowid) as nbTsk, count(DISTINCT fk_project_task_timesheet) as nbTm ,t.recipient';
            $sql.= ' FROM '.MAIN_DB_PREFIX.'project_task_time_approval as t';
            $sql.= ' WHERE t.status IN ("SUBMITTED","UNDERAPPROVAL","CHALLENGED") AND ((t.recipient="team"'; 
            $sql.= ' AND t.fk_userid in ('.$subordinate.'))';//fixme should check subordinate and project
            $sql.= ' OR (t.recipient="project" and fk_projet_task in ('.$tasks.')))';
            $sql.= '  GROUP BY t.recipient ';
            $result = $db->query($sql);
            if ($result)
            {
                $num = $db->num_rows($result);
                while ($num>0){
                    $obj = $db->fetch_object($result);
                    if($obj->recipient=='project'){
                        $nbPrj=$obj->nbTsk;
                    }else if($obj->recipient=='team'){
                        $nbTm=$obj->nbTm;
                    }
                    $num--;
                    }

                    $this->info_box_contents[0][] = array(
                        'td' => 'align="left"',
                        'text' => $langs->trans('team').': ',
                        'text2'=> $langs->trans('nbTsToApprove'),
                        'asis' => 1,
                    );

                    $this->info_box_contents[0][] = array(
                        'td' => 'align="right"',
                        'text' => $nbTm,
                        'asis' => 1,
                    );
                    $this->info_box_contents[1][] = array(
                        'td' => 'align="left"',
                        'text' => $langs->trans('project').': ',
                        'text2'=> $langs->trans('nbTsToApprove'),
                        'asis' => 1,
                    );

                    $this->info_box_contents[1][] = array(
                        'td' => 'align="right"',
                        'text' => $nbPrj,
                        'asis' => 1,
                    );

                $db->free($result);
            } else {
                $this->info_box_contents[0][0] = array(
                    'td' => 'align="left"',
                    'maxlength'=>500,
                    'text' => ($db->error().' sql='.$sql),
                );
            }

        } else {
            $this->info_box_contents[0][0] = array(
                'td' => 'align="left"',
                'text' => $langs->trans("ReadPermissionNotAllowed"),
            );
        }
    }

	/**
	 *  Method to show box
	 *
	 *  @param  array   $head       Array with properties of box title
	 *  @param  array   $contents   Array with properties of box lines
	 *  @return void
	 */
	function showBox($head = null, $contents = null,$nooutput = 0)
	{
		parent::showBox($this->info_box_head, $this->info_box_contents);
	}

}

 /*
 * function to genegate list of the subordinate ID
 * 
  *  @param    object           	$db             database objet
 *  @param    array(int)/int        $id    		    array of manager id 
 *  @param     int              	$depth          depth of the recursivity
 *  @param    array(int)/int 		$ecludeduserid  exection that shouldn't be part of the result ( to avoid recursive loop)
 *  @param     string              	$role           team will look for organigram subordinate, project for project subordinate
 *  @param     int              	$entity         entity where to look for
  *  @return     string                                                   html code
 */
function getSubordinates($db,$userid, $depth=5,$ecludeduserid=array(),$role='team',$entity='1'){
    if($userid=="")
    {
        return array();
    }
    $sql['project'][0] ='SELECT DISTINCT fk_socpeople as userid FROM '.MAIN_DB_PREFIX.'element_contact';
    $sql['project'][0] .= ' WHERE element_id in (SELECT element_id';
    $sql['project'][0] .= ' FROM '.MAIN_DB_PREFIX.'element_contact';
    $sql['project'][0] .= ' WHERE (fk_c_type_contact="160" OR fk_c_type_contact="180")';
    $sql['project'][0] .= ' AND fk_socpeople in (';
    $sql['project'][2] = ')) AND fk_socpeople not in (';
    $sql['project'][4] = ')';
    $sql['team'][0]='SELECT usr.rowid as userid FROM '.MAIN_DB_PREFIX.'user AS usr WHERE';
    $sql['team'][0].=' usr.fk_user in (';
    $sql['team'][2]=') AND usr.rowid not in (';
    $sql['team'][4] = ')';
    $idlist='';
    if(is_array($userid)){
        $ecludeduserid=array_merge($userid,$ecludeduserid);
        $idlist=implode(",", $userid);
    }else{
        $ecludeduserid[]=$userid;
        $idlist=$userid;
    }
    $sql[$role][1]=$idlist;
    $idlist='';
    if(is_array($ecludeduserid)){
        $idlist=implode(",", $ecludeduserid);
    }else if (!empty($ecludeduserid)){
        $idlist=$ecludeduserid;
    } 
   $sql[$role][3]=$idlist;
    ksort($sql[$role], SORT_NUMERIC);
    $sqlused=implode($sql[$role]);
    dol_syslog('form::get_subordinate role='.$role, LOG_DEBUG);
    $list=array();
    $resql=$db->query($sqlused);
    
    if ($resql)
    {
        $i=0;
        $num = $db->num_rows($resql);
        while ( $i<$num)
        {
            $obj = $db->fetch_object($resql);
            
            if ($obj)
            {
                $list[]=$obj->userid;        
            }
            $i++;
        }
        if(count($list)>0 && $depth>1){
            //this will get the same result plus the subordinate of the subordinate
            $result=getSubordinates($db,$list,$depth-1,$ecludeduserid, $role, $entity);
            if(is_array($result))
            {
                $list=array_merge($list,$result);
            }
        }
        if(is_array($userid))
        {
            
            $list=array_merge($list,$userid);
        }else
        {
            //$list[]=$userid;
        }
        
    }
    else
    {
        $error++;
        dol_print_error($db);
        $list= array();
    }
      //$select.="\n";
      return $list;
 }

  /*
 * function to genegate list of the subordinate ID
 * 
  *  @param    object           	$db             database objet
 *  @param    array(int)/int        $id    		    array of manager id 
 *  @param     int              	$depth          depth of the recursivity
 *  @param    array(int)/int 		$ecludeduserid  exection that shouldn't be part of the result ( to avoid recursive loop)
 *  @param     string              	$role           team will look for organigram subordinate, project for project subordinate
 *  @param     int              	$entity         entity where to look for
  *  @return     string                                                   html code
 */
function getTasks($db,$userid,$role='project'){
    $sql='SELECT tk.fk_projet as project ,tk.rowid as task';
    $sql.= ' FROM '.MAIN_DB_PREFIX.'projet_task as tk';
    $sql.=' JOIN '.MAIN_DB_PREFIX.'element_contact ON  tk.fk_projet= element_id ';
    $sql.=' WHERE fk_c_type_contact = "160" ';
    $sql.=' AND fk_socpeople="'.$userid.'"';
    $sql.=' UNION ';
    $sql.=' SELECT tk.fk_projet as project ,tk.rowid as task';
    $sql.= ' FROM '.MAIN_DB_PREFIX.'projet_task as tk';
    $sql.=' JOIN '.MAIN_DB_PREFIX.'element_contact on (tk.rowid= element_id )';
    $sql.=' WHERE fk_c_type_contact = "180" ';
    $sql.=' AND fk_socpeople="'.$userid.'"';


   dol_syslog('timesheet::report::projectList ', LOG_DEBUG);
   //launch the sql querry

   $resql=$db->query($sql);
   $numTask=0;
   $taskList=array();
   if ($resql)
   {
           $numTask = $db->num_rows($resql);
           $i = 0;
           // Loop on each record found, so each couple (project id, task id)
           while ($i < $numTask)
           {
                   $error=0;
                   $obj = $db->fetch_object($resql);
                   $taskList[$obj->task]=$obj->project;
                   $i++;
           }
           $db->free($resql);
   }else
   {
           dol_print_error($db);
   }
   return $taskList;
}
