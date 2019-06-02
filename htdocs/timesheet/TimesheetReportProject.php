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
 * but WITHOUT ANY WARRANTY;without even the implied warranty of
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
//require_once DOL_DOCUMENT_ROOT.'/core/modules/export/modules_export.php';
$htmlother = new FormOther($db);
//$objmodelexport=new ModeleExports($db);
$id                 = GETPOST('id', 'int');
$action                 = GETPOST('action', 'alpha');
//$dateStart         = GETPOST('dateStart', 'alpha');
$exportfriendly = GETPOST('exportfriendly', 'alpha');
$optioncss = GETPOST('optioncss', 'alpha');
$short = GETPOST('short', 'int');
$mode = GETPOST('mode', 'alpha');
$model = GETPOST('model', 'alpha');
if(empty($mode)){
    $mode = 'UTD';
}

$projectSelectedId = GETPOST('projectSelected');
$year = GETPOST('year', 'int');
$month = GETPOST('month', 'alpha');//strtotime(str_replace('/', '-', $_POST['Date']))
// Load traductions files requiredby by page
//$langs->load("companies");
//$firstDay = ($month)?strtotime('01-'.$month.'-'. $year):strtotime('first day of previous month');
//$lastDay = ($month)?strtotime('last day of this month', $firstDay):strtotime('last day of previous month');
$langs->load("main");
$langs->load("projects");
$langs->load('timesheet@timesheet');
//find the right week
//find the right week
$dateStart = strtotime(GETPOST('dateStart', 'alpha'));
$dateStartday = GETPOST('dateStartday', 'int');// to not look for the date if action not goTodate
$dateStartmonth = GETPOST('dateStartmonth', 'int');
$dateStartyear = GETPOST('dateStartyear', 'int');
$dateStart = parseDate($dateStartday, $dateStartmonth, $dateStartyear, $dateStart);
$dateEnd = strtotime(GETPOST('dateEnd', 'alpha'));
$dateEndday = GETPOST('dateEndday', 'int');// to not look for the date if action not goTodate
$dateEndmonth = GETPOST('dateEndmonth', 'int');
$dateEndyear = GETPOST('dateEndyear', 'int');
$dateEnd = parseDate($dateEndday, $dateEndmonth, $dateEndyear, $dateEnd);
$invoicabletaskOnly = GETPOST('invoicabletaskOnly', 'int');
if(empty($dateStart) || empty($dateEnd) || empty($projectSelectedId)) {
    $step = 0;
    $dateStart = strtotime("first day of previous month", time());
    $dateEnd = strtotime("last day of previous month", time());
}
$userid = is_object($user)?$user->id:$user;
//querry to get the project where the user have priviledge;either project responsible or admin
$sql = 'SELECT pjt.rowid, pjt.ref, pjt.title, pjt.dateo, pjt.datee FROM '.MAIN_DB_PREFIX.'projet as pjt';
if(!$user->admin) {
    $sql .= ' JOIN '.MAIN_DB_PREFIX.'element_contact AS ec ON pjt.rowid = element_id ';
    $sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_type_contact as ctc ON ctc.rowid = ec.fk_c_type_contact';
    $sql .= ' WHERE ((ctc.element in (\'project_task\') AND ctc.code LIKE \'%EXECUTIVE%\')OR (ctc.element in (\'project\') AND ctc.code LIKE \'%LEADER%\')) AND ctc.active = \'1\'  ';
    $sql .= ' AND fk_socpeople = \''.$userid.'\' and fk_statut = \'1\'';
    $sql.= " AND pjt.entity IN (".getEntity('projet').")";
} else{
    $sql .= ' WHERE fk_statut = \'1\' ';
    $sql.= " AND pjt.entity IN (".getEntity('projet').")";
}
dol_syslog('timesheet::report::projectList ', LOG_DEBUG);
//launch the sql querry
$resql = $db->query($sql);
$numProject = 0;
$projectList = array();
if($resql) {
        $numProject = $db->num_rows($resql);
        $i = 0;
        // Loop on each record found, so each couple (project id, task id)
        while($i < $numProject)
        {
                $error = 0;
                $obj = $db->fetch_object($resql);
                $projectList[$obj->rowid]=array('value' => $obj->rowid, "label" =>  $obj->ref.' - '.$obj->title);
                //$projectList[$obj->rowid] = new TimesheetReport($db);
                //$projectList[$obj->rowid]->initBasic($obj->rowid, '', $obj->ref.' - '.$obj->title, $dateStart, $dateEnd, $mode, $invoicabletaskOnly);
                $i++;
        }
        $db->free($resql);
} else {
        dol_print_error($db);
}
$projectIdlist=array();
$reportName=$langs->trans('ReportProject');
if($projectSelectedId<>-999){
    $projectIdlist[]=$projectSelectedId;
    $reportName=$projectList[$projectSelectedId]['label'];
} else {
    $projectIdlist= array_keys($projectList);
}
$reportStatic = new TimesheetReport($db);
$reportStatic->initBasic($projectIdlist, '', '', $dateStart, $dateEnd, $mode, $invoicabletaskOnly);
if($action == 'getpdf') {
    $pdf = new pdf_rat($db);
    //$outputlangs = $langs;
    if($pdf->writeFile($reportStatic, $langs)>0) {
        header("Location: ".DOL_URL_ROOT."/document.php?modulepart=timesheet&file=reports/".$reportStatic->name.".pdf");
        return;
    }
    ob_end_flush();
    exit();
}elseif($action == 'getExport'){
    $max_execution_time_for_export = (empty($conf->global->EXPORT_MAX_EXECUTION_TIME)?300:$conf->global->EXPORT_MAX_EXECUTION_TIME);    // 5mn if not defined
    $max_time = @ini_get("max_execution_time");
    if($max_time && $max_time < $max_execution_time_for_export)
    {
        @ini_set("max_execution_time", $max_execution_time_for_export); // This work only if safe mode is off. also web servers has timeout of 300
    }
    $name=$reportStatic->buildFile($model, false);
    if(!empty($name)){
        header("Location: ".DOL_URL_ROOT."/document.php?modulepart=export&file=".$name);
        return;
    }
    ob_end_flush();
    exit();
}
//$_SESSION["dateStart"] = $dateStart ;
llxHeader('', $langs->trans('projectReport'), '');

$querryRes = '';
if($projectSelectedId   &&!empty($dateStart)) {
    if($exportfriendly){
        $querryRes .= $reportStatic->getHTMLreportExport();
    }else {
        $querryRes .= $reportStatic->getHTMLreport($short,
            dol_print_date($dateStart, 'day').'-'.dol_print_date($dateEnd, 'day'));
    }
}
$Form .= "<div id='quicklinks'>";
//This week quick link
$Form .= "<a class='tab' href ='?action=reportUser&projectSelected=".$projectSelectedId."&dateStart=".dol_print_date(strtotime("first day of this week"), 'dayxcard');
$Form .= "&dateEnd=".dol_print_date(strtotime("last day of this week"), 'dayxcard')."'>".$langs->trans('thisWeek')."</a>";
//This month quick link
$Form .= "<a class='tab' href ='?action=reportUser&projectSelected=".$projectSelectedId."&dateStart=".dol_print_date(strtotime("first day of this month"), 'dayxcard');
$Form .= "&dateEnd=".dol_print_date(strtotime("last day of this month"), 'dayxcard')."'>".$langs->trans('thisMonth')."</a>";
//last week quick link
$Form .= "<a class='tab' href ='?action=reportUser&projectSelected=".$projectSelectedId."&dateStart=".dol_print_date(strtotime("first day of previous week"), 'dayxcard');
$Form .= "&dateEnd=".dol_print_date(strtotime("last day of previous week"), 'dayxcard')."'>".$langs->trans('lastWeek')."</a>";
//Last month quick link
$Form .= "<a class='tab' href ='?action=reportUser&projectSelected=".$projectSelectedId."&dateStart=".dol_print_date(strtotime("first day of previous month"), 'dayxcard');
$Form .= "&dateEnd=".dol_print_date(strtotime("last day of previous month"), 'dayxcard')."'>".$langs->trans('lastMonth')."</a>";
//today
$today = dol_print_date(mktime(), 'dayxcard');
$Form .= "<a class='tab' href ='?action=reportUser&projectSelected=".$projectSelectedId."&dateStart=".$today;
$Form .= "&dateEnd=".$today."'>".$langs->trans('today')."</a> ";
$Form .= "</div>";
$Form .= '<form action="?action=reportproject'.(($optioncss != '')?'&amp;optioncss='.$optioncss:'').'" method = "POST">
        <table class = "noborder"  width = "100%">
        <tr>
        <td>'.$langs->trans('Project').'</td>
        <td>'.$langs->trans('DateStart').'</td>
        <td>'.$langs->trans('DateEnd').'</td>
        <td>'.$langs->trans('short').'</td>
        <td>'.$langs->trans('InvoicableOnly').'</td>
        <td>'.$langs->trans('exportfriendly').'</td>
        <td>'.$langs->trans('Mode').'</td>
        <td></td>
        </tr>
        <tr >
        <td><select  name = "projectSelected">
        ';
// select project
foreach($projectList as $pjt) {
    $Form .= '<option value = "'.$pjt["value"].'" '.(($projectSelectedId == $pjt["value"])?"selected":'').' >'.$pjt["label"].'</option>'."\n";
}
$Form .= '<option value = "-999" '.(($projectSelectedId == "-999")?"selected":'').' >'.$langs->trans('All').'</option>'."\n";

$Form .= '</select></td>';
        //}
// select start date
$Form.=   '<td>'.$form->select_date($dateStart, 'dateStart', 0, 0, 0, "", 1, 1, 1)."</td>";
// select end date
$Form.=   '<td>'.$form->select_date($dateEnd, 'dateEnd', 0, 0, 0, "", 1, 1, 1)."</td>";
//$Form .= '<td> '.$htmlother->select_month($month, 'month').' - '.$htmlother->selectyear($year, 'year', 0, 10, 3)
// Select short
$Form .= ' <td><input type = "checkbox" name = "short" value = "1" ';
$Form .= (($short == 1)?'checked>':'>').'</td>' ;
// Select invoiceable only
$Form .= '<td><input type = "checkbox" name = "invoicabletaskOnly" value = "1" ';
$Form .= (($invoicabletaskOnly == 1)?'checked>':'>').'</td>';
// Select Export friendly
$Form .= '<td><input type = "checkbox" name = "exportfriendly" value = "1" ';
$Form .= (($exportfriendly == 1)?'checked>':'>').'</td>';
// Select mode
$Form.= '<td><input type = "radio" name = "mode" value = "UTD" '.($mode == 'UTD'?'checked':'');
$Form .= '> '.$langs->trans('User').' / '.$langs->trans('Task').' / '.$langs->trans('Date').'<br>';
$Form.= '<input type = "radio" name = "mode" value = "UDT" '.($mode == 'UDT'?'checked':'');
$Form .= '> '.$langs->trans('User').' / '.$langs->trans('Date').' / '.$langs->trans('Task').'<br>';
$Form.= '<input type = "radio" name = "mode" value = "DUT" '.($mode == 'DUT'?'checked':'');
$Form .= '> '.$langs->trans('Date').' / '.$langs->trans('User').' / '.$langs->trans('Task').'<br>';
 $Form .= '</td></tr></table>';
 //submit
 $model=$conf->global->TIMESHEET_EXPORT_FORMAT;
 $Form .= '<input class = "butAction" type = "submit" value = "'.$langs->trans('getReport').'">';
if(!empty($querryRes) && ($user->rights->facture->creer || version_compare(DOL_VERSION, "3.7")<=0))$Form .= '<a class = "butAction" href = "TimesheetProjectInvoice.php?step=0&dateStart='.dol_print_date($dateStart, 'dayxcard').'&invoicabletaskOnly='.$invoicabletaskOnly.'&dateEnd='.dol_print_date($dateEnd, 'dayxcard').'&projectid='.$projectSelectedId.'" >'.$langs->trans('Invoice').'</a>';
if(!empty($querryRes))$Form .= '<a class = "butAction" href="?action=getpdf&dateStart='.dol_print_date($dateStart, 'dayxcard').'&dateEnd='.dol_print_date($dateEnd, 'dayxcard').'&projectSelected='.$projectSelectedId.'&mode='.$mode.'&invoicabletaskOnly='.$invoicabletaskOnly.'" >'.$langs->trans('TimesheetPDF').'</a>';
if(!empty($querryRes) && $conf->global->MAIN_MODULE_EXPORT)$Form .= '<a class = "butAction" href="?action=getExport&dateStart='.dol_print_date($dateStart, 'dayxcard').'&dateEnd='.dol_print_date($dateEnd, 'dayxcard').'&projectSelected='.$projectSelectedId.'&mode='.$mode.'&model='.$model.'&invoicabletaskOnly='.$invoicabletaskOnly.'" >'.$langs->trans('Export').'</a>';
if(!empty($querryRes))$Form .= '<a class = "butAction" href="?action=reportproject&dateStart='.dol_print_date($dateStart, 'dayxcard').'&dateEnd='.dol_print_date($dateEnd, 'dayxcard').'&projectSelected='.$projectSelectedId.'&mode='.$mode.'&invoicabletaskOnly='.$invoicabletaskOnly.'" >'.$langs->trans('Refresh').'</a>';
 $Form .= '</form>';
if(!($optioncss != '' && !empty($_POST['userSelected']))) echo $Form;
echo $querryRes;
/*
// List of available export formats
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td class="titlefield">'.$langs->trans("AvailableFormats").'</td>';
print '<td>'.$langs->trans("LibraryUsed").'</td>';
print '<td align="right">'.$langs->trans("LibraryVersion").'</td>';
print '</tr>'."\n";

$liste=$objmodelexport->liste_modeles($db);
$listeall=$liste;
foreach($listeall as $key => $val)
{
    if (preg_match('/__\(Disabled\)__/',$listeall[$key]))
    {
        $listeall[$key]=preg_replace('/__\(Disabled\)__/','('.$langs->transnoentitiesnoconv("Disabled").')',$listeall[$key]);
        unset($liste[$key]);
    }

    print '<tr class="oddeven">';
    print '<td width="16">'.img_picto_common($key,$objmodelexport->getPictoForKey($key)).' ';
    $text=$objmodelexport->getDriverDescForKey($key);
    $label=$listeall[$key];
    print $form->textwithpicto($label,$text).'</td>';
    print '<td>'.$objmodelexport->getLibLabelForKey($key).'</td>';
    print '<td align="right">'.$objmodelexport->getLibVersionForKey($key).'</td>';
    print '</tr>'."\n";
}
print '</table>';*/
llxFooter();
$db->close();
