<?php
/* 
 * Copyright (C) 2015 delcroip <patrick@pmpd.eu>
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
require_once './core/lib/timesheet.lib.php';
require_once './class/TimesheetReport.class.php';
require_once './core/modules/pdf/pdf_rat.modules.php';

$htmlother = new FormOther($db);


$id		= GETPOST('id','int');
$action		= GETPOST('action','alpha');
//$dateStart	= GETPOST('dateStart','alpha');
$exportfriendly=GETPOST('exportfriendly','alpha');
$optioncss = GETPOST('optioncss','alpha');
$short=GETPOST('short','int');
$mode=GETPOST('mode','alpha');
if(!$mode)$mode='UTD';
$projectSelectedId=GETPOST('projectSelected');
    $year=GETPOST('year','int');
    $month=GETPOST('month','alpha');//strtotime(str_replace('/', '-',$_POST['Date']))
// Load traductions files requiredby by page
//$langs->load("companies");

$firstDay= ($month)?strtotime('01-'.$month.'-'. $year):strtotime('first day of previous month');
$lastDay=  ($month)?strtotime('last day of this month',$firstDay):strtotime('last day of previous month');

$langs->load("main");
$langs->load("projects");
$langs->load('timesheet@timesheet');

//find the right week
//find the right week
$dateStart                 = strtotime(GETPOST('dateStart', 'alpha'));
$dateStartday =(!empty($dateStart) )? GETPOST('dateStartday', 'int'):0; // to not look for the date if action not goTodate
$dateStartmonth                 = GETPOST('dateStartmonth', 'int');
$dateStartyear                 = GETPOST('dateStartyear', 'int');
$dateStart=parseDate($dateStartday,$dateStartmonth,$dateStartyear,$dateStart);

$dateEnd                 = strtotime(GETPOST('dateEnd', 'alpha'));
$dateEndday =(!empty($dateEnd) )? GETPOST('dateEndday', 'int'):0; // to not look for the date if action not goTodate
$dateEndmonth                 = GETPOST('dateEndmonth', 'int');
$dateEndyear                 = GETPOST('dateEndyear', 'int');
$dateEnd=parseDate($dateEndday,$dateEndmonth,$dateEndyear,$dateEnd);

if(empty($dateStart) || empty($dateEnd) || empty($projectSelectedId)){
    $step=0;
    $dateStart=  strtotime("first day of previous month",time());
    $dateEnd=  strtotime("last day of previous month",time());
 }

if($action=='getpdf'){
    $report=new TimesheetReport($db);
   
    $report->initBasic($projectSelectedId,'','',$dateStart,$dateEnd,$mode);
    $pdf=new pdf_rat($db);
    //$outputlangs=$langs;
    if( $pdf->write_file($report, $langs)>0){
        header("Location: ".DOL_URL_ROOT."/document.php?modulepart=timesheet&file=reports/".$report->ref.".pdf");
    	return;
    }
    exit();
}

//$_SESSION["dateStart"]=$dateStart ;


llxHeader('',$langs->trans('projectReport'),'');

$userid=  is_object($user)?$user->id:$user;



//querry to get the project where the user have priviledge; either project responsible or admin

$sql='SELECT pjt.rowid,pjt.ref,pjt.title,pjt.dateo,pjt.datee FROM '.MAIN_DB_PREFIX.'projet as pjt';
if(!$user->admin){    
    $sql.=' JOIN '.MAIN_DB_PREFIX.'element_contact AS ec ON pjt.rowid= element_id ';
    $sql.=' LEFT JOIN '.MAIN_DB_PREFIX.'c_type_contact as ctc ON ctc.rowid=ec.fk_c_type_contact';
    $sql.=' WHERE ((ctc.element in (\'project_task\') AND ctc.code LIKE \'%EXECUTIVE%\')OR (ctc.element in (\'project\') AND ctc.code LIKE \'%LEADER%\')) AND ctc.active=\'1\'  '; 
    $sql.=' AND fk_socpeople=\''.$userid.'\' and fk_statut = \'1\'';
}else{
    $sql.=' WHERE fk_statut = \'1\' '; 
}

dol_syslog('timesheet::report::projectList ', LOG_DEBUG);
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
                $projectList[$obj->rowid]=new TimesheetReport($db);
                $projectList[$obj->rowid]->initBasic($obj->rowid,'',$obj->ref.' - '.$obj->title,$dateStart,$dateEnd,$mode);
                $i++;
        }
        $db->free($resql);
}else
{
        dol_print_error($db);
}

$Form='<form action="?action=reportproject'.(($optioncss != '')?'&amp;optioncss='.$optioncss:'').'" method="POST">
        <table class="noborder"  width="100%">
        <tr>
        <td>'.$langs->trans('Project').'</td>
        <td>'.$langs->trans('DateStart').'</td>
        <td>'.$langs->trans('DateEnd').'</td>
        <td>'.$langs->trans('short').'</td>
        <td>'.$langs->trans('exportfriendly').'</td>
        <td>'.$langs->trans('Mode').'</td>
        <td></td>
        </tr>
        <tr >
        <td><select  name="projectSelected">
        ';
foreach($projectList as $pjt){
    $Form.='<option value="'.$pjt->projectid.'" '.(($projectSelectedId==$pjt->projectid)?"selected":'').' >'.$pjt->name.'</option>'."\n";
}
//    if($user->admin){
        $Form.='<option value="-999" '.(($projectSelectedId=="-999")?"selected":'').' >'.$langs->trans('All').'</option>'."\n";
//    }
   

$querryRes='';
if ($projectSelectedId   &&!empty($dateStart))
{

    $projectSelected=$projectList[$projectSelectedId];

    if($projectSelectedId=='-999'){
        foreach($projectList as $project){
        $querryRes.=$project->getHTMLreport($short,
           dol_print_date($dateStart,'day').'-'.dol_print_date($dateEnd,'day'),
            $conf->global->TIMESHEET_DAY_DURATION,$exportfriendly);
        }
    }else{
    $querryRes=$projectSelected->getHTMLreport($short,
            dol_print_date($dateStart,'day').'-'.dol_print_date($dateEnd,'day'),
            $conf->global->TIMESHEET_DAY_DURATION,$exportfriendly);
    }
    
}else
{
    $year=date('Y',$dateStart);
    $month=date('m',$dateStart);
}

$Form.='</select></td>';
        //}
$Form.=   '<td>'.$form->select_date($dateStart,'dateStart',0,0,0,"",1,1,1)."</td>";
$Form.=   '<td>'.$form->select_date($dateEnd,'dateEnd',0,0,0,"",1,1,1)."</td>";
//$Form.='<td> '.$htmlother->select_month($month, 'month').' - '.$htmlother->selectyear($year,'year',0,10,3)
$Form.=' <td><input type="checkbox" name="short" value="1" '
        .(($short==1)?'checked>':'>').'</td>'
        .'<td><input type="checkbox" name="exportfriendly" value="1" '
        .(($exportfriendly==1)?'checked>':'>').'</td>'
        . '<td><input type="radio" name="mode" value="UTD" '.($mode=='UTD'?'checked':'')
        .'> '.$langs->trans('User').' / '.$langs->trans('Task').' / '.$langs->trans('Date').'<br>'
        . '<input type="radio" name="mode" value="UDT" '.($mode=='UDT'?'checked':'')
        .'> '.$langs->trans('User').' / '.$langs->trans('Date').' / '.$langs->trans('Task').'<br>'
        . '<input type="radio" name="mode" value="DUT" '.($mode=='DUT'?'checked':'')
        .'> '.$langs->trans('Date').' / '.$langs->trans('User').' / '.$langs->trans('Task').'<br>';
 $Form.='</td></tr></table>';
 $Form.='<input class="butAction" type="submit" value="'.$langs->trans('getReport').'">';

if(!empty($querryRes) && ($user->rights->facture->creer || version_compare(DOL_VERSION,"3.7")<=0 ))$Form.='<a class="butAction" href="TimesheetProjectInvoice.php?step=0&dateStart='.dol_print_date($dateStart,'dayxcard').'&dateEnd='.dol_print_date($dateEnd,'dayxcard').'&projectid='.$projectSelectedId.'" >'.$langs->trans('Invoice').'</a>';

if(!empty($querryRes))$Form.='<a class="butAction" href="?action=getpdf&dateStart='.dol_print_date($dateStart,'dayxcard').'&dateEnd='.dol_print_date($dateEnd,'dayxcard').'&projectSelected='.$projectSelectedId.'" >'.$langs->trans('TimesheetPDF').'</a>';
 $Form.='</form>';      
if(!($optioncss != '' && !empty($_POST['userSelected']) )) echo $Form;




echo $querryRes;


llxFooter();
$db->close();
?>
