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


$res=0;
if (! $res && file_exists("../main.inc.php")) $res=@include '../main.inc.php';					// to work if your module directory is into dolibarr root htdocs directory
if (! $res && file_exists("../../main.inc.php")) $res=@include '../../main.inc.php';			// to work if your module directory is into a subdir of root htdocs directory
if (! $res && file_exists("../../../dolibarr/htdocs/main.inc.php")) $res=@include '../../../dolibarr/htdocs/main.inc.php';     // Used on dev env only
if(strpos($_SERVER['PHP_SELF'], 'dolibarr_min')>0 && !$res && file_exists("/var/www/dolibarr_min/htdocs/main.inc.php")) $res=@include "/var/www/dolibarr_min/htdocs/main.inc.php";     // Used on dev env only
else if (! $res && file_exists("/var/www/dolibarr/htdocs/main.inc.php")) $res=@include '/var/www/dolibarr/htdocs/main.inc.php';   // Used on dev env only
if (! $res) die("Include of main fails");
// Change this following line to use the correct relative path from htdocs
//month / year form
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
$htmlother = new FormOther($db);


$id		= GETPOST('id','int');
$action		= GETPOST('action','alpha');
$yearWeek	= GETPOST('yearweek');


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

llxHeader('',$langs->trans('projectReport'),'');
$mode=($_POST['short']==1)?1:2;
dol_include_once('/timesheet/class/projectTimesheet.class.php');
$userid=  is_object($user)?$user->id:$user;



//querry to get the project where the user have priviledge; either project responsible or admin

$sql='SELECT llx_projet.rowid,ref,title,dateo,datee FROM llx_projet ';
if(!$user->admin){    
    $sql.='JOIN llx_element_contact ON llx_projet.rowid= element_id ';
    $sql.='WHERE fk_c_type_contact = "160" ';
    $sql.='AND fk_socpeople='.$userid;
}

dol_syslog("timesheet::report::projectList sql=".$sql, LOG_DEBUG);
//launch the sql querry

$resql=$db->query($sql);
$numProject=0;
$projectList=array();
if ($resql)
{
        $numProject = $db->num_rows($resql);
        $i = 0;
        // Loop on each record found, so each couple (project id, task id)
        while ($i < $numProject)
        {
                $error=0;
                $obj = $db->fetch_object($resql);
                $projectList[$obj->rowid]=new ProjectTimesheet($db);
                $projectList[$obj->rowid]->initBasic($obj->rowid,$obj->ref,$obj->title,$obj->dateo,$obj->datee);
                $i++;
        }
        $db->free($resql);
}else
{
        dol_print_error($db);
}

$Form='<form action="?action=reportproject" method="POST">
        <table class="noborder"  width="100%">
        <tr>
        <td>'.$langs->trans('Project').'</td>
        <td>'.$langs->trans('Month').'</td>
        <td></td>
        </tr>
        <tr >
        <td><select  name="projectSelected">
        ';
foreach($projectList as $pjt){
    $Form.='<option value="'.$pjt->id.'" '.(($_POST['projectSelected']==$pjt->id)?"selected":'').' >'.$pjt->ref.' - '.$pjt->title.'</option>
            ';
}
$mode='UTD';
$querryRes='';
if (!empty($_POST['projectSelected']) && is_numeric($_POST['projectSelected']) 
        &&!empty($_POST['month']))
{
    $mode=$_POST['mode'];
    $short=$_POST['short'];
    $projectSelected=$projectList[$_POST['projectSelected']];
    $year=$_POST['year'];
    $month=$_POST['month'];//strtotime(str_replace('/', '-',$_POST['Date'])); 
    $firstDay= strtotime('01-'.$month.'-'. $year);
    $lastDay=  strtotime('last day of this month',$firstDay);
    $querryRes=$projectSelected->getHTMLreport($firstDay,$lastDay,$mode,$short,
            $langs->trans(date('F',$month)),
            (TIMESHEET_TIME_TYPE=='days')?TIMESHEET_DAY_DURATION:0);
    
}else
{
    $year=date('Y',strtotime( $yearWeek.' +0 day'));
    $month=date('m',strtotime( $yearWeek.' +0 day'));
}

$Form.='</select></td>'
        .'<td> '.$htmlother->select_month($month, 'month').' - '.$htmlother->selectyear($year,'year',1,10,3)
        .' </td><td><input type="checkbox" name="short" value="1" '
        .(($short==1)?'checked>':'>').$langs->trans('short').'</td>'
        . '<td><input type="radio" name="mode" value="UTD" '.($mode=='UTD'?'checked':'')
        .'> '.$langs->trans('User').' / '.$langs->trans('Task').' / '.$langs->trans('Date').'<br>'
        . '<input type="radio" name="mode" value="UDT" '.($mode=='UDT'?'checked':'')
        .'> '.$langs->trans('User').' / '.$langs->trans('Date').' / '.$langs->trans('Task').'<br>'
        . '<input type="radio" name="mode" value="DUT" '.($mode=='DUT'?'checked':'')
        .'> '.$langs->trans('Date').' / '.$langs->trans('User').' / '.$langs->trans('Task').'<br>'
        .'<td><input type="submit" value="'.$langs->trans('getReport')
        .'"></td></tr></table></form>';

echo $Form;




echo $querryRes;
llxFooter();
$db->close();
?>