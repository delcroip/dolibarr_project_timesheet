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
require_once 'core/lib/timesheet.lib.php';
require_once 'class/TimesheetReport.class.php';
require_once './core/modules/pdf/pdf_rat.modules.php';
//require_once DOL_DOCUMENT_ROOT.'/core/modules/export/modules_export.php';
$htmlother = new FormOther($db);
$userid = is_object($user)?$user->id:$user;
$id                 = GETPOST('id', 'int');
$action                 = GETPOST('action', 'alpha');
$userIdSelected = GETPOST('userSelected', 'int');
$exportFriendly = GETPOST('exportFriendly', 'alpha');
if(empty($userIdSelected))$userIdSelected = $userid;
$exportfriendly = GETPOST('exportfriendly', 'alpha');
$optioncss = GETPOST('optioncss', 'alpha');
// Load traductions files requiredby by page
//$langs->load("companies");
$langs->load("main");
$langs->load("projects");
$langs->load('timesheet@timesheet');
//find the right week
//$toDate = GETPOST('toDate', 'alpha');
//$toDateday = (!empty($toDate) && $action == 'goToDate')? GETPOST('toDateday', 'int'):0;// to not look for the date if action not goTodate
//$toDatemonth = GETPOST('toDatemonth', 'int');
//$toDateyear = GETPOST('toDateyear', 'int');
$mode = GETPOST('mode', 'alpha');
$model = GETPOST('model', 'alpha');
if(empty($mode))$mode = 'PTD';
$short = GETPOST('short', 'int');;
//$userSelected = $userList[$userIdSelected];
$year = GETPOST('year', 'int');;
//$month = GETPOST('month', 'int');;//strtotime(str_replace('/', '-', $_POST['Date']));
//$firstDay = ($month)?strtotime('01-'.$month.'-'. $year):strtotime('first day of previous month');
//$lastDay = ($month)?strtotime('last day of this month', $firstDay):strtotime('last day of previous month');
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
if(empty($dateStart) || empty($dateEnd) || empty($userIdSelected)) {
    $step = 0;
    $dateStart = strtotime("first day of previous month", time());
    $dateEnd = strtotime("last day of previous month", time());
}
//querry to get the project where the user have priviledge;either project responsible or admin
$sql = 'SELECT DISTINCT usr.rowid as userid, usr.lastname, usr.firstname '
     .'FROM '.MAIN_DB_PREFIX.'user as usr ';
$sql .= 'JOIN '.MAIN_DB_PREFIX.'element_contact as ec '
     .' ON ec.fk_socpeople = usr.rowid '
     .' LEFT JOIN '.MAIN_DB_PREFIX.'c_type_contact as ctc ON ctc.rowid = ec.fk_c_type_contact'
     .' WHERE ctc.element in (\'project_task\', \'project\') AND ctc.active = \'1\' ';
if(!$user->admin) {
    $list = getSubordinates($db, $userid, 3);
    $list[] = $userid;
    $sql .= ' AND (usr.rowid in ('.implode(', ', $list).'))';
}
dol_syslog("timesheet::reportuser::userList", LOG_DEBUG);
//launch the sql querry
$resql = $db->query($sql);
$numUser = 0;
$userList = array();

if($resql) {
    $numUser = $db->num_rows($resql);
    $i = 0;
    // Loop on each record found, so each couple (project id, task id)
    while($i < $numUser)
    {
        $error = 0;
        $obj = $db->fetch_object($resql);
        $userList[$obj->userid] = array('value' => $obj->userid, "label" => $obj->firstname.' '.$obj->lastname);
        //$userList[$obj->userid] = new TimesheetReport($db);
        //$userList[$obj->userid]->initBasic('', $obj->userid, $obj->firstname.' '.$obj->lastname, $dateStart, $dateEnd, $mode);
        $i++;
    }
    $db->free($resql);
} else {
    dol_print_error($db);
}
$userIdlist=array();
$reportName=$langs->trans('ReportProject');
if($userIdSelected<>-999){
    $userIdlist[]=$userIdSelected;
    $reportName=$userList[$userIdSelected]['value'];
} else {
    $userIdlist=array_keys($userList);
}
$reportStatic = new TimesheetReport($db);
$reportStatic->initBasic('', $userIdlist, $reportName, $dateStart, $dateEnd, $mode, $invoicabletaskOnly);
if($action == 'getpdf') {
    $pdf = new pdf_rat($db);
    //$outputlangs = $langs;
    if($pdf->writeFile($reportStatic, $langs)>0) {
        header("Location: ".DOL_URL_ROOT."/document.php?modulepart=timesheet&file=reports/".$report->ref.".pdf");
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


llxHeader('', $langs->trans('userReport'), '');
$Form .= "<div id='quicklinks'>";
//This week quick link
$Form .= "<a class='tab' href ='?action=reportUser&userSelected=".$user->id."&dateStart=".dol_print_date(strtotime("first day of this week"), 'dayxcard');
$Form .= "&dateEnd=".dol_print_date(strtotime("last day of this week"), 'dayxcard')."'>".$langs->trans('thisWeek')."</a>";
//This month quick link
$Form .= "<a class='tab' href ='?action=reportUser&userSelected=".$user->id."&dateStart=".dol_print_date(strtotime("first day of this month"), 'dayxcard');
$Form .= "&dateEnd=".dol_print_date(strtotime("last day of this month"), 'dayxcard')."'>".$langs->trans('thisMonth')."</a>";
//last week quick link
$Form .= "<a class='tab' href ='?action=reportUser&userSelected=".$user->id."&dateStart=".dol_print_date(strtotime("first day of previous week"), 'dayxcard');
$Form .= "&dateEnd=".dol_print_date(strtotime("last day of previous week"), 'dayxcard')."'>".$langs->trans('lastWeek')."</a>";
//Last month quick link
$Form .= "<a class='tab' href ='?action=reportUser&userSelected=".$user->id."&dateStart=".dol_print_date(strtotime("first day of previous month"), 'dayxcard');
$Form .= "&dateEnd=".dol_print_date(strtotime("last day of previous month"), 'dayxcard')."'>".$langs->trans('lastMonth')."</a>";
//today
$today = dol_print_date(mktime(), 'dayxcard');
$Form .= "<a class='tab' href ='?action=reportUser&userSelected=".$user->id."&dateStart=".$today;
$Form .= "&dateEnd=".$today."'>".$langs->trans('today')."</a> ";
$Form .= "</div>";

$Form .= '<form action="?action=reportUser'.(($optioncss != '')?'&amp;optioncss='.$optioncss:'').'" method = "POST">
        <table class = "noborder"  width = "100%">
        <tr>
        <td>'.$langs->trans('User').'</td>
        <td>'.$langs->trans('DateStart').'</td>
        <td>'.$langs->trans('DateEnd').'</td>
        <td>'.$langs->trans('short').'</td>
        <td>'.$langs->trans('InvoicableOnly').'</td>
        <td>'.$langs->trans('exportfriendly').'</td>
        <td>'.$langs->trans('Mode').'</td>
        <td></td>
        </tr>
        <tr >
        <td><select  name = "userSelected">
        ';
foreach($userList as $usr) {
   // $Form .= '<option value = "'.$usr->id.'">'.$usr->name.'</option> ';
    $Form .= '<option value = "'.$usr['value'].'" '.(($userIdSelected == $usr['value'])?"selected":'').' >'.$usr['label'].'</option>'."\n";
}
$Form .= '<option value = "-999" '.(($userIdSelected == "-999")?"selected":'').' >'.$langs->trans('All').'</option>'."\n";
//$mode = 'PTD';
$querryRes = '';
if(!empty($userIdSelected)
        &&!empty($dateEnd) && !empty($dateStart))
{
    if($exportfriendly){
        $querryRes .= $reportStatic->getHTMLreportExport();
    }else {
        $querryRes .= $reportStatic->getHTMLreport($short,
            "User report ".dol_print_date($dateStart, 'day').'-'.dol_print_date($dateEnd, 'day'));
    }
}
$Form .= '</select></td>';
$Form.=   '<td>'.$form->select_date($dateStart, 'dateStart', 0, 0, 0, "", 1, 1, 1)."</td>";
// select end date
$Form.=   '<td>'.$form->select_date($dateEnd, 'dateEnd', 0, 0, 0, "", 1, 1, 1)."</td>";
//$Form.= '<td>'.$htmlother->select_month($month, 'month').' - '.$htmlother->selectyear($year, 'year', 0, 10, 3).' </td>';
$Form .= ' <td><input type = "checkbox" name = "short" value = "1" ';
$Form .= (($short == 1)?'checked>':'>').'</td>' ;
// Select invoiceable only
$Form .= '<td><input type = "checkbox" name = "invoicabletaskOnly" value = "1" ';
$Form .= (($invoicabletaskOnly == 1)?'checked>':'>').'</td>';
// Select Export friendly
$Form .= '<td><input type = "checkbox" name = "exportfriendly" value = "1" ';
$Form .= (($exportfriendly == 1)?'checked>':'>').'</td>';
// Select mode
$Form.= '<td><input type = "radio" name = "mode" value = "PTD" '.($mode == 'PTD'?'checked':'');
$Form .= '> '.$langs->trans('Project').' / '.$langs->trans('Task').' / '.$langs->trans('Date').'<br>';
$Form.= '<input type = "radio" name = "mode" value = "PDT" '.($mode == 'PDT'?'checked':'');
$Form .= '> '.$langs->trans('Project').' / '.$langs->trans('Date').' / '.$langs->trans('Task').'<br>';
$Form.= '<input type = "radio" name = "mode" value = "DPT" '.($mode == 'DPT'?'checked':'');
$Form .= '> '.$langs->trans('Date').' / '.$langs->trans('Project').' / '.$langs->trans('Task').'<br>';
 $Form .= '</td></tr></table>';
 $Form .= '<input class = "butAction" type = "submit" value = "'.$langs->trans('getReport').'">';
 $model=$conf->global->TIMESHEET_EXPORT_FORMAT;
//if(!empty($querryRes))$Form .= '<a class = "butAction" href="?action=getpdf&dateStart='.dol_print_date($dateStart, 'dayxcard').'&dateEnd='.dol_print_date($dateEnd, 'dayxcard').'&projectSelected='.$projectSelectedId.'&mode=DTU&invoicabletaskOnly='.$invoicabletaskOnly.'" >'.$langs->trans('TimesheetPDF').'</a>';
if(!empty($querryRes)  && $conf->global->MAIN_MODULE_EXPORT)$Form .= '<a class = "butAction" href="?action=getExport&dateStart='.dol_print_date($dateStart, 'dayxcard').'&dateEnd='.dol_print_date($dateEnd, 'dayxcard').'&userSelected='.$userIdSelected.'&mode=DTU&model='.$model.'&invoicabletaskOnly='.$invoicabletaskOnly.'" >'.$langs->trans('Export').'</a>';
$Form .= '</form>';
if(!($optioncss != '' && !empty($userIdSelected))) echo $Form;
// section to generate
if(!empty($querryRes)) {
    echo $querryRes;
}
llxFooter();
$db->close();
