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
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
require_once 'core/lib/timesheet.lib.php';
require_once 'core/lib/generic.lib.php';
require_once 'class/TimesheetReport.class.php';
require_once './core/modules/pdf/pdf_rat.modules.php';
//require_once DOL_DOCUMENT_ROOT.'/core/modules/export/modules_export.php';
$form_output = new Form($db);
$htmlother = new FormOther($db);
$userid = is_object($user)?$user->id:$user;
$id = GETPOST('id', 'int');
$action = GETPOST('action', 'alpha');
$view = GETPOST('view', 'alpha');
if ($view != ''){
    $action = $view;
}
$userIdSelected = GETPOST('userSelected', 'int');
$exportFriendly = GETPOST('exportFriendly', 'alpha');
if (empty($userIdSelected))$userIdSelected = $userid;
$exportfriendly = GETPOST('exportfriendly', 'alpha');
$optioncss = GETPOST('optioncss', 'alpha');
$admin = $user->admin || $user->rights->timesheet->report->admin || $user->rights->timesheet->timesheet->admin;
if (!$user->rights->timesheet->report->user && !$admin) {
    $accessforbidden = accessforbidden("You don't have the report user or admin right");
}
// Load translation files required by the page
$langs->loadLangs(
	array(
		'main',
		'projects',
//		'companies',
		'timesheet@timesheet',
	)
);


//find the right week
//$toDate = GETPOST('toDate', 'alpha');
//$toDateday = (!empty($toDate) && $action == 'goToDate')? GETPOST('toDateday', 'int'):0;// to not look for the date if action not goToDate
//$toDatemonth = GETPOST('toDatemonth', 'int');
//$toDateyear = GETPOST('toDateyear', 'int');
$mode = GETPOST('mode', 'alpha');
$short = GETPOST('short', 'int');
$invoicedCol = GETPOST('invoicedcol', 'int');
$ungroup = GETPOST('ungroup', 'int');
$model = GETPOST('model', 'alpha');
if (empty($mode)){
    $mode = 'PTD';
    $ungroup = getConf('TIMESHEET_REPORT_UNGROUP');
    $invoicedCol = getConf('TIMESHEET_REPORT_INVOICED_COL');
}
$short = GETPOST('short', 'int');
$invoicedCol = GETPOST('invoicedcol', 'int');
$ungroup = GETPOST('ungroup', 'int');
$show_all = GETPOST('showAll', 'int');

//$userSelected = $userList[$userIdSelected];
$year = GETPOST('year', 'int');
//$month = GETPOST('month', 'int');;//strtotime(str_replace('/', '-', $_POST['Date']));
//$firstDay = ($month)?strtotime('01-'.$month.'-'. $year):strtotime('first day of previous month');
//$lastDay = ($month)?strtotime('last day of this month', $firstDay):strtotime('last day of previous month');
$dateStart = strtotime(GETPOST('startDate', 'alpha'));
$dateStartday = GETPOST('startDateday', 'int');// to not look for the date if action not goToDate
$dateStartmonth = GETPOST('startDatemonth', 'int');
$dateStartyear = GETPOST('startDateyear', 'int');
$dateStart = parseDate($dateStartday, $dateStartmonth, $dateStartyear, $dateStart);
$dateEnd = strtotime(GETPOST('dateEnd', 'alpha'));
$dateEndday = GETPOST('dateEndday', 'int');// to not look for the date if action not goToDate
$dateEndmonth = GETPOST('dateEndmonth', 'int');
$reporttab = GETPOST('reporttab', 'alpha');
$dateEndyear = GETPOST('dateEndyear', 'int');
$dateEnd = parseDate($dateEndday, $dateEndmonth, $dateEndyear, $dateEnd);
$invoicabletaskOnly = GETPOST('invoicabletaskOnly', 'int');
if (empty($dateStart) || empty($dateEnd) || empty($userIdSelected)) {
    $step = 0;
    $dateStart = strtotime("first day of previous month", time());
    $dateEnd = strtotime("last day of previous month", time());
}

// if the user can see ts for other the user id is diferent
$userIdlist = array();
$userIdlistfull = getSubordinates($db, $userid, 2, array(), $admin ? ADMIN : ALL, $entity = '1', $admin);
$userIdlistfull[] = $userid;
if ($show_all)
{
    
    $userIdlist = $userIdlistfull;
}else if (!empty($userIdSelected)  && $userIdSelected <> $userid) {

    if (in_array($userIdSelected, $userIdlistfull) || $admin ) {
        $userIdlist[] = $userIdSelected;
    } else{
        setEventMessage($langs->transnoentitiesnoconv("NotAllowed"), 'errors');
        unset($action);
        $userIdlist[] = $userid;
    }
} else{
    $userIdlist[] = $userid;
    $userIdSelected = $userid;
}

$reportStatic = new TimesheetReport($db);
$reportName = '';
$reportStatic->initBasic('', $userIdlist, $reportName, $dateStart, $dateEnd,
    $mode, $invoicabletaskOnly,$short,$invoicedCol,$ungroup);
if ($action == 'getpdf') {
    $pdf = new pdf_rat($db);
    //$outputlangs = $langs;
    if ($pdf->writeFile($reportStatic, $langs)>0) {
        header("Location: ".DOL_URL_ROOT."/document.php?modulepart=timesheet&file=reports/"
        .dol_sanitizeFileName($reportStatic->name) . ".pdf");
        return;
    }
    ob_end_flush();
    exit();
} elseif ($action == 'getExport'){
    $max_execution_time_for_export = getConf('EXPORT_MAX_EXECUTION_TIME',300) .
    $max_time = @ini_get("max_execution_time");
    if ($max_time && $max_time < $max_execution_time_for_export)
    {
        @ini_set("max_execution_time", $max_execution_time_for_export); // This work only if safe mode is off. also web servers has timeout of 300
    }
    $name = $reportStatic->buildFile($model, false);
    if (!empty($name)){
        header("Location: ".DOL_URL_ROOT."/document.php?modulepart=export&file=".$name);
        return;
    }
    ob_end_flush();
    exit();
}


llxHeader('', $langs->trans('userReport'), '');

$head = timesheet_report_prepare_head( 'user', $user->id );
print dol_get_fiche_head( $head, $reporttab, $langs->trans( 'TimeSpent' ), - 1, 'clock' );

$form_output = '';

$form_output .= '<form action="?action=reportUser'.(($optioncss != '')?'&amp;optioncss='.$optioncss:'').'" method = "POST">
        <table class = "noborder"  width = "100%">
        <tr>
        <td>'.$langs->trans('User').'</td>
        <td>'.$langs->trans('DateStart').'</td>
        <td>'.$langs->trans('DateEnd').'</td>
        <td>'.$langs->trans('Mode').'</td>
        <td>'.$langs->trans('Options').'</td>
        </tr>
        <tr >
        <td>
        ';
$token = getToken();
$form_output .= '<input type = "hidden" id="csrf-token" name = "token" value = "'.$token.'"/>';

if($admin){
    $form_output .= $form->select_dolusers($userIdSelected, 'userSelected');

} else {
    $form_output .= $form->select_dolusers($userIdSelected, 'userSelected', 0, null, 0, $userIdlistfull);
}
if (count($userIdlistfull)>1) {
    $form_output .= ' <br><input type = "checkbox" name = "showAll" value = "1" ';
    $form_output .= ($show_all?'checked >':'>').$langs->trans('All') ;
}


//$mode = 'PTD';
$querryRes = '';
if (!empty($userIdSelected)
        &&!empty($dateEnd) && !empty($dateStart))
{
    if ($exportfriendly){
        $querryRes .= $reportStatic->getHTMLreportExport();
    }else {
        $querryRes .= $reportStatic->getHTMLreport($short,
            "User report ".dol_print_date($dateStart, 'day').'-'
                .dol_print_date($dateEnd, 'day'));
    }
}

$form_output .= '<td>'.$form->select_date($dateStart, 'startDate', 0, 0, 0, "", 1, 1, 1)."</td>";
// select end date
$form_output .= '<td>'.$form->select_date($dateEnd, 'dateEnd', 0, 0, 0, "", 1, 1, 1)."</td>";
//$form_output .= '<td>'.$htmlother->select_month($month, 'month').' - '.$htmlother->selectyear($year, 'year', 0, 10, 3).' </td>';
// Select mode
$form_output .= '<td><input type = "radio" name = "mode" value = "PTD" '.($mode == 'PTD'?'checked':'');
$form_output .= '> '.$langs->trans('Project').' / '.$langs->trans('Task').' / '.$langs->trans('Date').'<br>';
$form_output .= '<input type = "radio" name = "mode" value = "PDT" '.($mode == 'PDT'?'checked':'');
$form_output .= '> '.$langs->trans('Project').' / '.$langs->trans('Date').' / '.$langs->trans('Task').'<br>';
$form_output .= '<input type = "radio" name = "mode" value = "DPT" '.($mode == 'DPT'?'checked':'');
$form_output .= '> '.$langs->trans('Date').' / '.$langs->trans('Project').' / '.$langs->trans('Task').'<br>';
 $form_output .= '</td>';
// select short
$form_output .= ' <td><input type = "checkbox" name = "short" value = "1" ';
$form_output .= (($short == 1)?'checked>':'>').$langs->trans('short').'</br>' ;
// Select invoiceable only
$form_output .= '<input type = "checkbox" name = "invoicabletaskOnly" value = "1" ';
$form_output .= (($invoicabletaskOnly == 1)?'checked>':'>').$langs->trans('InvoicableOnly').'</br>';
// Select Export friendly
$form_output .= '<input type = "checkbox" name = "exportfriendly" value = "1" ';
$form_output .= (($exportfriendly == 1)?'checked>':'>').$langs->trans('exportfriendly').'</br>';
// Select show invoice
$form_output .= '<input type = "checkbox" name = "invoicedcol" value = "1" ';
$form_output .= (($invoicedCol == 1)?'checked>':'>'). $langs->trans('reportInvoicedCol').'</br>';
// Select Export friendly
$form_output .= '<input type = "checkbox" name = "ungroup" value = "1" ';
$form_output .= (($ungroup == 1)?'checked>':'>').$langs->trans('reportUngroup').'</td>';

 $form_output .= '</tr></table>';

////print '<div class="tabsAction">';
//print '<div class="inline-block divButAction"><input type="submit" class="button butAction" value="' . $langs->trans( 'Save' ) . '" /></div>';
//print "</div>";


$form_output  .= '<div class="tabsAction"><div class="center">';
$form_output  .= '<input class="butAction" type="submit" value="' . $langs->trans( 'getReport' ) . '">';
$model = getConf('TIMESHEET_EXPORT_FORMAT');
//if(!empty($querryRes))$form_output .= '<a class = "butAction" href="?action=getpdf&startDate='.dol_print_date($dateStart, 'dayxcard').'&dateEnd='.dol_print_date($dateEnd, 'dayxcard').'&projectSelected='.$projectSelectedId.'&mode=DTU&invoicabletaskOnly='.$invoicabletaskOnly.'" >'.$langs->trans('TimesheetPDF').'</a>';
if ( ! empty( $querryRes ) && getConf('MAIN_MODULE_EXPORT') ) {
	$form_output .= '<a class = "butAction" href="?action=getExport&startDate=' 
        .dol_print_date( $dateStart, 'dayxcard' ) 
        .'&dateEnd=' . dol_print_date( $dateEnd, 'dayxcard' ) 
        .'&userSelected=' . $userIdSelected 
        .'&mode='.$mode.'&model=' . $model 
        .'&invoicabletaskOnly=' . $invoicabletaskOnly 
        .'&ungroup=' . $ungroup 
        .'&showAll=' . $show_all 
        . '&token='.$token.'" >' . $langs->trans( 'Export' ) . '</a>';
}
if ( ! empty( $querryRes ) ) {
	$form_output .= '<a class = "butAction" href="?action=getpdf&startDate=' 
    . dol_print_date( $dateStart, 'dayxcard' ) 
    . '&dateEnd=' . dol_print_date( $dateEnd, 'dayxcard' ) 
    . '&userSelected=' . $userIdSelected 
    . '&mode='.$mode.'&model=' . $model 
    . '&invoicabletaskOnly='  . $invoicabletaskOnly 
    . '&ungroup=' . $ungroup 
    . '&showAll=' . $show_all 
    . '&token='.$token.'" >' . $langs->trans( 'PDF' ) . '</a>';
}
$form_output .= '</div></div></form>';

if ( ! ( $optioncss != '' && ! empty( $userIdSelected ) ) ) {
	echo $form_output;
}
// section to generate
if ( ! empty( $querryRes ) ) {
	echo $querryRes;
}

llxFooter();
$db->close();
