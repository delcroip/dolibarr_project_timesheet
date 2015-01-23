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

//retrieve the db
$db=$_SESSION["db"];
$yearWeek=$_SESSION["yearWeek"];
$id=$_SESSION["id"];
$mode=($_POST['short']==1)?1:2;
dol_include_once('/timesheet/class/projectTimesheet.class.php');




//querry to get the project where the user have priviledge; either project responsible or admin

$sql='SELECT llx_projet.rowid,ref,title,dateo,datee FROM llx_projet ';
if(!$user->admin){    
    $sql.='JOIN llx_element_contact ON llx_projet.rowid= element_id ';
    $sql.='WHERE fk_c_type_contact = "160" ';
    $sql.='AND fk_socpeople='.$user->id;
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

$Form.='</select></td>'
        .'<td><input type="date" id="Date" name="Date" size="10" value="'
        .date('d/m/Y',strtotime( $yearWeek.' +0 day')).'"/> </td>
        <td><input type="submit" value="'.$langs->trans('getReport').'"></td>
        <td><input type="checkbox" name="short" value="1" '
        .(($mode==1)?'checked>':'>').$langs->trans('short').'</td>
        </tr>
         
        </table>
        
        </form>';
echo $Form;
// section to generate
$querryRes='';
$projectSelected='';
if (!empty($_POST['projectSelected']) && is_numeric($_POST['projectSelected']) 
        &&!empty($_POST['Date']))
{
    $projectSelected=$projectList[$_POST['projectSelected']];
    $mode=($_POST['short'])?1:2;
    $month=strtotime(str_replace('/', '-',$_POST['Date']));  
    $firstDay=  strtotime('first day of this month',$month);
    $lastDay=  strtotime('last day of this month',$month);
        if($projectSelected->isOpen($firstDay, $lastDay)){
            $querryRes=$projectSelected->getHTMLreport($firstDay,$lastDay,$mode,$langs->trans(date('F',$month)));
        }else{
            $querryRes=$langs->trans('projectClosed');
        }   
        
}
echo $querryRes;