<?php
/* 
 * Copyright (C) 2015 delcroip <delcroip@gmail.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 * 
 */


include 'core/lib/includeMain.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once 'core/lib/timesheet.lib.php';
require_once 'class/timesheetreport.class.php';

$htmlother = new FormOther($db);

$userid=  is_object($user)?$user->id:$user;
$id		= GETPOST('id','int');
$action		= GETPOST('action','alpha');
$yearWeek	= GETPOST('yearweek');
$userIdSelected   = GETPOST('userSelected');
$exportFriendly = GETPOST('exportFriendly');
if(empty($userIdSelected))$userIdSelected=$userid;
$exportfriendly=GETPOST('exportfriendly');
$optioncss = GETPOST('optioncss','alpha');

// Load traductions files requiredby by page
//$langs->load("companies");
$langs->load("main");
$langs->load("projects");
$langs->load('timesheet@timesheet');


//find the right week
if(isset($_POST['Date'])){
    $yearWeek=date('Y\WW',strtotime(str_replace('/', '-',$_POST['Date']).' first monday of this month'));
    $_SESSION["yearWeek"]=$yearWeek;
}else if (isset($_GET['yearweek'])) {
    $yearWeek=$_GET['yearweek'];
    $_SESSION["yearWeek"]=$yearWeek;
}else if(isset($_SESSION["yearWeek"]))
{
    $yearWeek=$_SESSION["yearWeek"];
}else
{
    $yearWeek=date('Y\WW');
}

llxHeader('',$langs->trans('userReport'),'');

//querry to get the project where the user have priviledge; either project responsible or admin
$sql='SELECT DISTINCT usr.rowid as userId, usr.lastname , usr.firstname '
     .'FROM '.MAIN_DB_PREFIX.'user as usr ';
        
  
$sql.='JOIN '.MAIN_DB_PREFIX.'element_contact as ctc '
     .'ON ctc.fk_socpeople=usr.rowid '
     .'WHERE (ctc.fk_c_type_contact ="180" OR ctc.fk_c_type_contact="181" OR ctc.fk_c_type_contact="160" OR ctc.fk_c_type_contact="161")';
if(!$user->admin)
{
    $list=get_subordinate($db,$userid,3);
    $list[]=$userid;
    $sql.=' AND (usr.rowid in ('.implode(',',$list).'))';  
}
    

dol_syslog("timesheet::reportuser::userList sql=".$sql, LOG_DEBUG);
//launch the sql querry

$resql=$db->query($sql);
$numUser=0;
$userList=array();
if ($resql)
{
        $numUser = $db->num_rows($resql);
        $i = 0;
        // Loop on each record found, so each couple (project id, task id)
        while ($i < $numUser)
        {
                $error=0;
                $obj = $db->fetch_object($resql);
                $userList[$obj->userId]=new timesheetReport($db);
                $userList[$obj->userId]->initBasic('',$obj->userId,$obj->firstname.' '.$obj->lastname);
                $i++;
        }
        $db->free($resql);
}else
{
        dol_print_error($db);
}


$Form='<form action="?action=reportuser'.(($optioncss != '')?'&amp;optioncss='.$optioncss:'').'" method="POST">
        <table class="noborder"  width="100%">
        <tr>
        <td>'.$langs->trans('User').'</td>
        <td>'.$langs->trans('Month').'</td>
        <td></td>
        </tr>
        <tr >
        <td><select  name="userSelected">
        ';

foreach($userList as $usr){
   // $Form.='<option value="'.$usr->id.'">'.$usr->name.'</option> ';
    $Form.='<option value="'.$usr->userid.'" '.(($userIdSelected==$usr->userid)?"selected":'').' >'.$usr->name.'</option>'."\n";

}
    $Form.='<option value="-999" '.(($userIdSelected=="-999")?"selected":'').' >'.$langs->trans('All').'</option>'."\n";

$mode='PTD';
$querryRes='';



if (!empty($_POST['userSelected']) && is_numeric($_POST['userSelected']) 
        &&!empty($_POST['month']))
{
    $mode=$_POST['mode'];
    $short=$_POST['short'];
    $userSelected=$userList[$userIdSelected];
    $year=$_POST['year'];
    $month=$_POST['month'];//strtotime(str_replace('/', '-',$_POST['Date'])); 
    
    $firstDay= strtotime('01-'.$month.'-'. $year);
    $lastDay=  strtotime('last day of this month',$firstDay);
    if($userIdSelected=='-999'){
        foreach($userList as $userSt){
        $querryRes.=$userSt->getHTMLreport($firstDay,$lastDay,$mode,$short,
            $langs->trans(date('F',strtotime('12/13/1999 +'.$month.' month'))),
            (TIMESHEET_TIME_TYPE=='days')?TIMESHEET_DAY_DURATION:0,$exportfriendly);
        }
    }else{
        $querryRes=$userSelected->getHTMLreport($firstDay,$lastDay,$mode,$short,
            $langs->trans(date('F',strtotime('12/13/1999 +'.$month.' month'))),
            (TIMESHEET_TIME_TYPE=='days')?TIMESHEET_DAY_DURATION:0,$exportfriendly);
    }
    
}else
{
    $year=date('Y',strtotime( $yearWeek.' +0 day'));
    $month=date('m',strtotime( $yearWeek.' +0 day'));
}

$Form.='</select></td>'
        .'<td>'.$htmlother->select_month($month, 'month').' - '.$htmlother->selectyear($year,'year',1,10,3).' </td>' 
        .'<td><input type="checkbox" name="short" value="1" '
        .(($short==1)?'checked>':'>').$langs->trans('short').'</td>'
        .'<td><input type="checkbox" name="exportfriendly" value="1" '
        .(($exportfriendly==1)?'checked>':'>').$langs->trans('exportfriendly').'</td>'
        . '<td><input type="radio" name="mode" value="PTD" '.($mode=='PTD'?'checked':'')
        .'> '.$langs->trans('Project').' / '.$langs->trans('Task').' / '.$langs->trans('Date').'<br>'
        . '<input type="radio" name="mode" value="PDT" '.($mode=='PDT'?'checked':'')
        .'> '.$langs->trans('Project').' / '.$langs->trans('Date').' / '.$langs->trans('Task').'<br>'
        . '<input type="radio" name="mode" value="DPT" '.($mode=='DPT'?'checked':'')
        .'> '.$langs->trans('Date').' / '.$langs->trans('Project').' / '.$langs->trans('Task').'<br>'
        .'<td><input type="submit" value="'.$langs->trans('getReport').'"></td>
        </tr>
         
        </table></form>';
if(!($optioncss != '' && !empty($_POST['userSelected']) )) echo $Form;
// section to generate
if(!empty($querryRes)){
   
    echo $querryRes;
}


llxFooter();
$db->close();
?>
