<?php
/*
 * Copyright (C) 2014	   Patrick DELCROIX     <pmpdelcroix@gmail.com>
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
//global $db;     
global $langs;
// to get the whitlist object
require_once 'class/timesheetwhitelist.class.php';
require_once 'class/timesheetUser.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';


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
function get_subordinate($db,$userid, $depth=5,$ecludeduserid=array(),$role='team',$entity='1'){
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
    dol_syslog("form::get_subordinate sql=".$sqlused.' role='.$role, LOG_DEBUG);
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
            $result=get_subordinate($db,$list,$depth-1,$ecludeduserid, $role, $entity);
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
 * function to get the name from a list of ID
 * 
  *  @param    object           	$db             database objet
 *  @param    array(int)/int        $userids    	array of manager id 
  *  @return  array (int => String)  				array( ID => userName)
 */
function get_userName($userids){
    global $db;
	if($userids=="")
    {
        return array();
    }

    $sql="SELECT usr.rowid, CONCAT(usr.firstname,' ',usr.lastname) as userName,usr.lastname FROM ".MAIN_DB_PREFIX.'user AS usr WHERE';

	$sql.=' usr.rowid in (';
	$nbIds=(is_array($userids))?count($userids)-1:0;
	for($i=0; $i<$nbIds ; $i++)
	{
		$sql.='"'.$userids[$i].'",';
	}
	$sql.=((is_array($userids))?('"'.$userids[$i].'"'):('"'.$userids.'"')).')';
        $sql.='ORDER BY usr.lastname ASC';

    dol_syslog("form::get_userName sql=".$sql, LOG_DEBUG);
    $list=array();
    $resql=$db->query($sql);
    
    if ($resql)
    {
        $i=0;
        $num = $db->num_rows($resql);
        while ( $i<$num)
        {
            $obj = $db->fetch_object($resql);
            
            if ($obj)
            {
                $list[$obj->rowid]=$obj->userName;        
            }
            $i++;
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



if (!is_callable(setEventMessages)){
    // function from /htdocs/core/lib/function.lib.php in Dolibarr 3.8
    function setEventMessages($mesg, $mesgs, $style='mesgs')
    {
            if (! in_array((string) $style, array('mesgs','warnings','errors'))) dol_print_error('','Bad parameter for setEventMessage');
            if (empty($mesgs)) setEventMessage($mesg, $style);
            else
            {
                    if (! empty($mesg) && ! in_array($mesg, $mesgs)) setEventMessage($mesg, $style);	// Add message string if not already into array
                    setEventMessage($mesgs, $style);

            }
    }
}

/*
 * function retrive the dolibarr eventMessages ans send then in a XML format
 * 
 *  @return     string                                         XML
 */
function getEventMessagesXML(){
    $xml='';
       // Show mesgs
   if (isset($_SESSION['dol_events']['mesgs'])) {
     $xml.=getEventMessageXML( $_SESSION['dol_events']['mesgs']);
     unset($_SESSION['dol_events']['mesgs']);
   }

   // Show errors
   if (isset($_SESSION['dol_events']['errors'])) {
     $xml.=getEventMessageXML(  $_SESSION['dol_events']['errors'], 'error');
     unset($_SESSION['dol_events']['errors']);
   }

   // Show warnings
   if (isset($_SESSION['dol_events']['warnings'])) {
     $xml.=getEventMessageXML(  $_SESSION['dol_events']['warnings'], 'warning');
     unset($_SESSION['dol_events']['warnings']);
   }
   return $xml;
}

/*
 * function convert the dolibarr eventMessage in a XML format
 * 
 *  @param    string              	$message           message to show
 *  @param    string              	$style            style of the message error | ok | warning
 *  @return     string                                         XML
 */
function getEventMessageXML($messages,$style='ok'){
    $msg='';
    
    if(is_array($messages)){
        $count=count($messages);
        foreach ($messages as $message){
            $msg.=$message;
            if($count>1)$msg.="<br/>";
            $count--;
        }
    }else
        $msg=$messages;
    $ret='';
    if($msg!=""){  
        if($style!='error' && $style!='warning')$style='ok';
        $ret= "<eventMessage style=\"{$style}\"> {$msg}</eventMessage>";
    }
    return $ret;
}