<?php
/*
 * This program is free software;you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation;either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY;without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */
/**
 *  \file       htdocs/admin/project.php
 *  \ingroup    project
 *  \brief      Page to setup project module
 */
include '../core/lib/includeMain.lib.php';
include '../core/lib/generic.lib.php';
require_once '../core/lib/timesheet.lib.php';

require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/project.lib.php';
require_once DOL_DOCUMENT_ROOT . '/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT . '/projet/class/task.class.php';

$langs->load("admin");
$langs->load("errors");
$langs->load("other");
$langs->load("timesheet@timesheet");
if (!$user->admin) {
    $accessforbidden = accessforbidden("you need to be admin");
}
$action = getpost('action', 'alpha');
$attendance = getConf('TIMESHEET_ATTENDANCE');

$timetype = getConf('TIMESHEET_TIME_TYPE','hours');
$hoursperday = getConf('TIMESHEET_DAY_DURATION',8);
$timeSpan = getConf('TIMESHEET_TIME_SPAN');
//hide/show
$hidedraft = getConf('TIMESHEET_HIDE_DRAFT');
$hidezeros = getConf('TIMESHEET_HIDE_ZEROS');
$headers = getConf('TIMESHEET_HEADERS');
$hideref = getConf('TIMESHEET_HIDE_REF');
$showTimespentNote = getConf('TIMESHEET_SHOW_TIMESPENT_NOTE');

$adddocs = getConf('TIMESHEET_ADD_DOCS');


$addForOther = getConf('TIMESHEET_ADD_FOR_OTHER');
$whiteListMode = getConf('TIMESHEET_WHITELIST_MODE');
$whiteList = getConf('TIMESHEET_WHITELIST');

$draftColor = getConf('TIMESHEET_COL_DRAFT');
$valueColor = getConf('TIMESHEET_COL_VALUE');
$frozenColor = getConf('TIMESHEET_COL_FROZEN');
$submittedColor = getConf('TIMESHEET_COL_SUBMITTED');
$approvedColor = getConf('TIMESHEET_COL_APPROVED');
$cancelledColor = getConf('TIMESHEET_COL_CANCELLED');
$rejectedColor = getConf('TIMESHEET_COL_REJECTED');
$maxhoursperday = getConf('TIMESHEET_DAY_MAX_DURATION');
$addholidaytime = getConf('TIMESHEET_ADD_HOLIDAY_TIME');
$blockholiday = getConf('TIMESHEET_BLOCK_HOLIDAY');
$addpublicholidaytime = getConf('TIMESHEET_ADD_PUBLICHOLIDAY_TIME');
$blockpublicholiday = getConf('TIMESHEET_BLOCK_PUBLICHOLIDAY');
$overtimecheckweeks = getConf('TIMESHEET_OVERTIME_CHECK_WEEKS');
$opendays = str_split(getConf('TIMESHEET_OPEN_DAYS',"_1111100"));

//approval
$approvalbyweek = getConf('TIMESHEET_APPROVAL_BY_WEEK');
$maxApproval = getConf('TIMESHEET_MAX_APPROVAL');
$apflows = str_split(getConf('TIMESHEET_APPROVAL_FLOWS'));
if (count($apflows) != 6) {
    $apflows = array('_', '0', '0', '0', '0', '0');
}

//Invoice part
$invoicemethod = getConf('TIMESHEET_INVOICE_METHOD');
$invoicetasktime = getConf('TIMESHEET_INVOICE_TASKTIME');
$invoicetimetype = getConf('TIMESHEET_INVOICE_TIMETYPE','Days');
$invoiceservice = getConf('TIMESHEET_INVOICE_SERVICE');
$invoiceshowtask = getConf('TIMESHEET_INVOICE_SHOW_TASK');
$invoiceshowuser = getConf('TIMESHEET_INVOICE_SHOW_USER');

//event
$maxhoursperevent = getConf('TIMESHEET_EVENT_MAX_DURATION');
$minsecondsperevent = getConf('TIMESHEET_EVENT_MIN_DURATION');
$defaulthoursperevent = getConf('TIMESHEET_EVENT_DEFAULT_DURATION');
$blockTimespent = getConf('TIMESHEET_EVENT_NOT_CREATE_TIMESPENT');
//pdf
$pdfhidesignbox = intval(getConf('TIMESHEET_PDF_HIDE_SIGNBOX'));
$noteOnPDF = getConf('TIMESHEET_PDF_NOTEISOTASK');
$pdfHideName = intval(getConf('TIMESHEET_PDF_HIDE_NAME'));
//advanced
$exportFormat = getConf('TIMESHEET_EXPORT_FORMAT');
$evalAddLine = getConf('TIMESHEET_EVAL_ADDLINE');
$tsRound = intval(getConf('TIMESHEET_ROUND'));
$importagenda = intval(getConf('TIMESHEET_IMPORT_AGENDA'));
$dropdownAjax = getConf('MAIN_DISABLE_AJAX_COMBOX');
$searchbox = intval(getConf('TIMESHEET_SEARCHBOX'));
$unblockInvoiced = getConf('TIMESHEET_UNBLOCK_INVOICED');
$unblockClosed = getConf('TIMESHEET_UNBLOCK_CLOSED');
$reportInvoicedCol= getConf('TIMESHEET_REPORT_INVOICED_COL');
$reportUngroup = getConf('TIMESHEET_REPORT_UNGROUP');
$allowPublic = getConf('TIMESHEET_ALLOW_PUBLIC');


//headers handling
$showProject = getpost('showProject', 'int')?:0;
$showTaskParent = getpost('showTaskParent', 'int')?:0;
$showTasks = getpost('showTasks', 'int')?:0;
$showDateStart = getpost('showDateStart', 'int')?:0;
$showDateEnd = getpost('showDateEnd', 'int')?:0;
$showProgress = getpost('showProgress', 'int')?:0;
$showCompany = getpost('showCompany', 'int')?:0;
$showNote = getpost('showNote', 'int')?:0;
$showTotal = getpost('showTotal', 'int')?:0;
$showProgressDeclared = getpost('showProgressDeclared', 'int')?:0;

if (count($opendays)!=8) {
    $opendays = array('_', '0', '0', '0', '0', '0', '0', '0');
}

$error = 0;
/** make sure that there is a 0 iso null
 *
 * @param mixed $var var can be an int of empty string
 * @param type $int defautl value is var is null
 * @return int
 */
function null2int($var, $int = 0)
{
    return($var == '' || !is_numeric($var))?$int:$var;
}

switch($action) {
    case "save":
        //general option
        $hoursperday = getpost('hoursperday', 'int')?:0;
        if ($hoursperday == 0) {
            //error handling if hour per day is empty
            $hoursperday = getConf('TIMESHEET_DAY_DURATION',8);
            setEventMessage($langs->transnoentitiesnoconv("HourPerDayNotNull"), 'errors');
            break;
        }
        dolibarr_set_const($db, "TIMESHEET_DAY_DURATION", $hoursperday, 'chaine', 0, '', $conf->entity);
        $timetype = getpost('timeType', 'alpha')?:'d';
        dolibarr_set_const($db, "TIMESHEET_TIME_TYPE", $timetype, 'chaine', 0, '', $conf->entity);
        $timeSpan = getpost('timeSpan', 'alpha');
        if ($timeSpan!=getConf('TIMESHEET_TIME_SPAN')) {
            // delete the unsubmitted timesheet so the new time span will be applied
            $sql = 'DELETE FROM '.MAIN_DB_PREFIX.'project_task_timesheet';
            $sql .= ' WHERE status IN (1, 5)';//'DRAFT', 'REJECTED'
            dol_syslog('timesheetsetu:deletedraft', LOG_DEBUG);
            $resql = $db->query($sql);
        }
        dolibarr_set_const($db, "TIMESHEET_TIME_SPAN", $timeSpan, 'chaine', 0, '', $conf->entity);
        $attendance = getpost('attendance', 'int');
        dolibarr_set_const($db, "TIMESHEET_ATTENDANCE", $attendance, 'int', 0, '', $conf->entity);
        $maxhoursperevent = getpost('maxhoursperevent', 'int');
        dolibarr_set_const($db, "TIMESHEET_EVENT_MAX_DURATION", $maxhoursperevent, 'int', 0, '', $conf->entity);
        $minsecondsperevent = getpost('minSecondsPerEvent', 'int');
        dolibarr_set_const($db, "TIMESHEET_EVENT_MIN_DURATION", $minsecondsperevent, 'int', 0, '', $conf->entity);
        $defaulthoursperevent = getpost('defaulthoursperevent', 'int');
        dolibarr_set_const($db, "TIMESHEET_EVENT_DEFAULT_DURATION", $defaulthoursperevent, 'int', 0, '', $conf->entity);
        $maxhoursperday = getpost('maxhoursperday', 'int');
        dolibarr_set_const($db, "TIMESHEET_DAY_MAX_DURATION", $maxhoursperday, 'int', 0, '', $conf->entity);
        $hidedraft = getpost('hidedraft', 'int');
        dolibarr_set_const($db, "TIMESHEET_HIDE_DRAFT", $hidedraft, 'int', 0, '', $conf->entity);
        $hidezeros = getpost('hidezeros', 'int');
        dolibarr_set_const($db, "TIMESHEET_HIDE_ZEROS", $hidezeros, 'int', 0, '', $conf->entity);
        $maxApproval = getpost('maxapproval', 'int');
        dolibarr_set_const($db, "TIMESHEET_MAX_APPROVAL", $maxApproval, 'int', 0, '', $conf->entity);
        $approvalbyweek = getpost('approvalbyweek', 'int');
        dolibarr_set_const($db, "TIMESHEET_APPROVAL_BY_WEEK", $approvalbyweek, 'int', 0, '', $conf->entity);
        $hideref = getpost('hideref', 'int');
        dolibarr_set_const($db, "TIMESHEET_HIDE_REF", $hideref, 'int', 0, '', $conf->entity);
        $whiteListMode = getpost('blackWhiteListMode', 'int');
        dolibarr_set_const($db, "TIMESHEET_WHITELIST_MODE", $whiteList?$whiteListMode:2, 'int', 0, '', $conf->entity);
        $whiteList = getpost('blackWhiteList', 'int');
        dolibarr_set_const($db, "TIMESHEET_WHITELIST", $whiteList, 'int', 0, '', $conf->entity);
        $dropdownAjax = getpost('dropdownAjax', 'int');
        dolibarr_set_const($db, "MAIN_DISABLE_AJAX_COMBOX", $dropdownAjax, 'int', 0, '', $conf->entity);
        $addForOther = getpost('addForOther', 'int');
        dolibarr_set_const($db, "TIMESHEET_ADD_FOR_OTHER", $addForOther, 'int', 0, '', $conf->entity);

        $headers = $showNote?'Note':'';
        $headers .= $showCompany?(empty($headers)?'':'||').'Company':'';
        $headers .= $showProject?(empty($headers)?'':'||').'Project':'';
        $headers .= $showTaskParent?(empty($headers)?'':'||').'TaskParent':'';
        $headers .= $showTasks?(empty($headers)?'':'||').'Tasks':'';
        $headers .= $showDateStart?(empty($headers)?'':'||').'DateStart':'';
        $headers .= $showDateEnd?(empty($headers)?'':'||').'DateEnd':'';
        $headers .= $showProgress?(empty($headers)?'':'||').'Progress':'';
        $headers .= $showProgressDeclared?(empty($headers)?'':'||').'ProgressDeclared':'';
        $headers .= $showTotal?(empty($headers)?'':'||').'Total':'';
        dolibarr_set_const($db, "TIMESHEET_HEADERS", $headers, 'chaine', 0, '', $conf->entity);
        //color handling
        $draftColor = getpost('draftColor', 'alpha');
        dolibarr_set_const($db, "TIMESHEET_COL_DRAFT", $draftColor, 'chaine', 0, '', $conf->entity);
        $valueColor = getpost('valueColor', 'alpha');
        dolibarr_set_const($db, "TIMESHEET_COL_VALUE", $valueColor, 'chaine', 0, '', $conf->entity);
        $frozenColor = getpost('frozenColor', 'alpha');
        dolibarr_set_const($db, "TIMESHEET_COL_FROZEN", $frozenColor, 'chaine', 0, '', $conf->entity);
        $submittedColor = getpost('submittedColor', 'alpha');
        dolibarr_set_const($db, "TIMESHEET_COL_SUBMITTED", $submittedColor, 'chaine', 0, '', $conf->entity);
        $approvedColor = getpost('approvedColor', 'alpha');
        dolibarr_set_const($db, "TIMESHEET_COL_APPROVED", $approvedColor, 'chaine', 0, '', $conf->entity);
        $rejectedColor = getpost('rejectedColor', 'alpha');
        dolibarr_set_const($db, "TIMESHEET_COL_REJECTED", $rejectedColor, 'chaine', 0, '', $conf->entity);
        $cancelledColor = getpost('cancelledColor', 'alpha');
        dolibarr_set_const($db, "TIMESHEET_COL_CANCELLED", $cancelledColor, 'chaine', 0, '', $conf->entity);
        //holiday
        $addholidaytime = getpost('addholidaytime', 'alpha');
        dolibarr_set_const($db, "TIMESHEET_ADD_HOLIDAY_TIME", $addholidaytime, 'chaine', 0, '', $conf->entity);
        // block holday
        $blockholiday = getpost('blockholiday', 'alpha');
        dolibarr_set_const($db, "TIMESHEET_BLOCK_HOLIDAY", $blockholiday, 'chaine', 0, '', $conf->entity);
        //public holiday
        $addpublicholidaytime = getpost('addpublicholidaytime', 'alpha');
        dolibarr_set_const($db, "TIMESHEET_ADD_PUBLICHOLIDAY_TIME", $addpublicholidaytime, 'chaine', 0, '', $conf->entity);
        // block public holday
        $blockpublicholiday = getpost('blockpublicholiday', 'alpha');
        dolibarr_set_const($db, "TIMESHEET_BLOCK_PUBLICHOLIDAY", $blockpublicholiday, 'chaine', 0, '', $conf->entity);

        // number of week to check for overtime box
        $overtimecheckweeks = getpost('overtimecheckweeks', 'alpha');
        dolibarr_set_const($db, "TIMESHEET_OVERTIME_CHECK_WEEKS", $overtimecheckweeks, 'chaine', 0, '', $conf->entity);
        //docs
        $adddocs = getpost('adddocs', 'int');
        dolibarr_set_const($db, "TIMESHEET_ADD_DOCS", $adddocs, 'chaine', 0, '', $conf->entity);
        // open days
        $opendays = array('_', '0', '0', '0', '0', '0', '0', '0');
        foreach (getpost('opendays', 'array') as $key => $day) {
            $opendays[$key] = $day;
        }
        dolibarr_set_const($db, "TIMESHEET_OPEN_DAYS", implode('', $opendays), 'chaine', 0, '', $conf->entity);
        //approval flows
        $apflows = array('_', '0', '0', '0', '0', '0');
        foreach (getpost('apflows', 'array') as $key => $flow) {
            $apflows[$key] = $flow;
        }
        //INVOICE
        dolibarr_set_const($db, "TIMESHEET_APPROVAL_FLOWS", implode('', $apflows), 'chaine', 0, '', $conf->entity)  ;
        $invoicemethod = getpost('invoiceMethod', 'alpha');
        dolibarr_set_const($db, "TIMESHEET_INVOICE_METHOD", $invoicemethod, 'chaine', 0, '', $conf->entity);
        $invoicetasktime = getpost('invoiceTaskTime', 'alpha');
        dolibarr_set_const($db, "TIMESHEET_INVOICE_TASKTIME", $invoicetasktime, 'chaine', 0, '', $conf->entity);
        $invoicetimetype = getpost('invoiceTimeType', 'alpha');
        dolibarr_set_const($db, "TIMESHEET_INVOICE_TIMETYPE", $invoicetimetype, 'chaine', 0, '', $conf->entity);
        $invoiceservice = getpost('invoiceservice', 'int');
        dolibarr_set_const($db, "TIMESHEET_INVOICE_SERVICE", $invoiceservice, 'int', 0, '', $conf->entity);
        $invoiceshowtask = getpost('invoiceShowTask', 'int');
        dolibarr_set_const($db, "TIMESHEET_INVOICE_SHOW_TASK", $invoiceshowtask, 'int', 0, '', $conf->entity);
        $invoiceshowuser = getpost('invoiceShowUser', 'int');
        dolibarr_set_const($db, "TIMESHEET_INVOICE_SHOW_USER", $invoiceshowuser, 'int', 0, '', $conf->entity);
        $showTimespentNote = getpost('showTimespentNote', 'int');
        dolibarr_set_const($db, "TIMESHEET_SHOW_TIMESPENT_NOTE", $showTimespentNote, 'int', 0, '', $conf->entity);
        $noteOnPDF = getpost('noteOnPDF', 'alpha');
        dolibarr_set_const($db, "TIMESHEET_PDF_NOTEISOTASK", $noteOnPDF, 'chaine', 0, '', $conf->entity);
        $pdfhidesignbox = getpost('pdfHideSignbox', 'alpha');
        dolibarr_set_const($db, "TIMESHEET_PDF_HIDE_SIGNBOX", $pdfhidesignbox, 'chaine', 0, '', $conf->entity);
        $pdfHideName = getpost('pdfHideName', 'alpha');
        dolibarr_set_const($db, "TIMESHEET_PDF_HIDE_NAME", $pdfHideName, 'chaine', 0, '', $conf->entity);
        // serach box
        $searchbox = getpost('searchBox', 'int');
        dolibarr_set_const($db, "TIMESHEET_SEARCHBOX", $searchbox, 'int', 0, '', $conf->entity);
        setEventMessage($langs->transnoentitiesnoconv("ConfigurationSaved"));
        $blockTimespent = getpost('blockTimespent', 'int');
        dolibarr_set_const($db, "TIMESHEET_EVENT_NOT_CREATE_TIMESPENT", $blockTimespent, 'chaine', 0, '', $conf->entity);
        $evalAddLine = getpost('evalAddLine', 'alpha');
        dolibarr_set_const($db, "TIMESHEET_EVAL_ADDLINE", $evalAddLine, 'int', 0, '', $conf->entity);
        $exportFormat = getpost('exportFormat', 'alpha');
        dolibarr_set_const($db, "TIMESHEET_EXPORT_FORMAT", $exportFormat, 'int', 0, '', $conf->entity);
        $maxApproval = getpost('maxapproval', 'int');
        dolibarr_set_const($db, "TIMESHEET_MAX_APPROVAL", $maxApproval, 'int', 0, '', $conf->entity);
        $unblockInvoiced = getpost('unblockInvoiced', 'int');
        dolibarr_set_const($db, "TIMESHEET_UNBLOCK_INVOICED", $unblockInvoiced, 'int', 0, '', $conf->entity);
        $unblockClosed = getpost('unblockClosed', 'int');
        dolibarr_set_const($db, "TIMESHEET_UNBLOCK_CLOSED", $unblockClosed, 'int', 0, '', $conf->entity);
        $reportInvoicedCol = getpost('reportInvoicedCol', 'int');
        dolibarr_set_const($db, "TIMESHEET_REPORT_INVOICED_COL", $reportInvoicedCol, 'int', 0, '', $conf->entity);
        $reportUngroup = getpost('reportUngroup', 'int');
        dolibarr_set_const($db, "TIMESHEET_REPORT_UNGROUP", $reportUngroup, 'int', 0, '', $conf->entity);
        $allowPublic = getpost('allowPublic', 'int');
        dolibarr_set_const($db, "TIMESHEET_ALLOW_PUBLIC", $allowPublic, 'int', 0, '', $conf->entity);
        $tsRound = getpost('tsRound', 'int');
        dolibarr_set_const($db, "TIMESHEET_ROUND", $tsRound, 'int', 0, '', $conf->entity);
        $importagenda = getpost('importagenda', 'int');
        dolibarr_set_const($db, "TIMESHEET_IMPORT_AGENDA", $importagenda, 'int', 0, '', $conf->entity);

        break;
    default:
        break;
}
$headersT = explode('||', $headers);
foreach ($headersT as $header) {
    switch($header) {
        case 'Project':
            $showProject = 1;
            break;
        case 'TaskParent':
            $showTaskParent = 1;
            break;
        case 'Tasks':
            $showTasks = 1;
            break;
        case 'DateStart':
            $showDateStart = 1;
            break;
        case 'DateEnd':
            $showDateEnd = 1;
            break;
        case 'Progress':
            $showProgress = 1;
            break;
        case 'Company':
            $showCompany = 1;
            break;
        case 'Note':
            $showNote = 1;
            break;
        case 'Total':
            $showTotal = 1;
            break;
        case 'ProgressDeclared':
            $showProgressDeclared = 1;
            break;
        default:
            break;
    }
}

/*
 *  VIEW
 *  */
//permet d'afficher la structure dolibarr
$morejs = array("/timesheet/core/js/timesheet.js?v2.0", "/timesheet/core/js/jscolor.js");
llxHeader("", $langs->trans("timesheetSetup"), '', '', '', '', $morejs, '', 0, 0);
if ($action = "save")echo "<script>window.history.pushState('', '', '".explode('?', 
    $_SERVER['REQUEST_URI'], 2)[0]."');</script>";
$linkback = '<a href = "'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans("timesheetSetup"), $linkback, 'title_setup');

/*
 * TABS
 */
echo '<div class="tabs" data-role = "controlgroup" data-type = "horizontal"  >';
	echo '<div id="defaultOpen"  class="inline-block tabsElem" onclick = "openTab(event,\'general\')"><a href="javascript:void(0);"  class="tabunactive tab inline-block" data-role = "button">'.$langs->trans('General').'</a></div>';
	echo '<div class="inline-block tabsElem" onclick="openTab(event,\'advanced\')"><a href="javascript:void(0);" class="tabunactive tab inline-block" data-role = "button">'.$langs->trans('Advanced').'</a></div>';
	echo '<div class="inline-block tabsElem" onclick="openTab(event,\'invoice\')"><a href="javascript:void(0);" class="tabunactive tab inline-block" data-role = "button">'.$langs->trans('Invoice').'</a></div>';
	echo '<div class="inline-block tabsElem" onclick="openTab(event,\'other\')"><a href="javascript:void(0);" class="tabunactive tab inline-block" data-role = "button">'.$langs->trans('Other').'</a></div>';
echo '</div>';

/*
 * TAB General
 */
echo '<div id="general" class="tabBar">';
print '<span class="opacitymedium">'.$langs->trans("GeneralTabDesc").'</span>';
print load_fiche_titre( $langs->trans( "GeneralOption" ), '', '' );

echo '<form name="settings" action="?action=save" method="POST">';
$token = getToken();
echo '<input type = "hidden" id="csrf-token" name = "token" value = "'.$token.'"/>';
echo '<table class="noborder" width = "100%">';
echo '<tr class="liste_titre" width = "100%" ><th width = "200px">'.$langs->trans("Name").'</th><th>';
echo $langs->trans("Description").'</th><th>'.$langs->trans("Value")."</th></tr>";
// activate attendance
echo  '<tr class="oddeven"><td align="left">'.$langs->trans("Attendance");
echo '</td><td align="left">'.$langs->trans("AttendanceDesc").'</td>';
echo  '<td align="left"><input type = "checkbox" name = "attendance" value="1" ';
echo (($attendance == '1')?'checked':'')."></td></tr>";

// type time
echo '<tr class="oddeven"><td align="left">'.$langs->trans("timeType").'</td><td align="left">'.$langs->trans("timeTypeDesc").'</td>';
echo '<td align="left"><input type = "radio" name = "timeType" value="hours" ';
echo ($timetype == "hours"?"checked":"").'> '.$langs->trans("Hours").'<br>';
echo '<input type = "radio" name = "timeType" value="days" ';
echo ($timetype == "days"?"checked":"").'> '.$langs->trans("Days")."</td></tr>";
//hours perdays
echo '<tr class="oddeven"><td align="left">'.$langs->trans("hoursperdays");
echo '</td><td align="left">'.$langs->trans("hoursPerDaysDesc").'</td>';
echo '<td align="left"><input type = "text" name = "hoursperday" value="'.$hoursperday;
echo "\" size = \"4\" ></td></tr>";
//max hours perdays
echo '<tr class="oddeven"><td align="left">'.$langs->trans("maxhoursperdays");//FIXTRAD
echo '</td><td align="left">'.$langs->trans("maxhoursPerDaysDesc").'</td>';// FIXTRAD
echo '<td align="left"><input type = "text" name = "maxhoursperday" value="'.$maxhoursperday;
echo "\" size = \"4\" ></td></tr>";
// time span
echo '<tr class="oddeven"><td align="left">'.$langs->trans("timeSpan").'</td><td align="left">'.$langs->trans("timeSpanDesc").'</td>';
echo '<td align="left"><input type = "radio" name = "timeSpan" value="week" ';
echo ($timeSpan == "week"?"checked":"").'> '.$langs->trans("Week").'<br>';
echo '<input type = "radio" name = "timeSpan" value="splitedWeek" ';
echo ($timeSpan == "splitedWeek"?"checked":"").'> '.$langs->trans("splitedWeek").'<br>';
echo '<input type = "radio" name = "timeSpan" value="month" ';
echo ($timeSpan == "month"?"checked":"").'> '.$langs->trans("Month")."</td></tr>";
// add holiday time
echo  '<tr class="oddeven"><td align="left">'.$langs->trans("addholidaytime");
echo '</td><td align="left">'.$langs->trans("addholidaytimeDesc").'</td>';
echo  '<td align="left"><input type = "checkbox" name = "addholidaytime" value="1" ';
echo (($addholidaytime == '1')?'checked':'')."></td></tr>";
// block holiday 
echo  '<tr class="oddeven"><td align="left">'.$langs->trans("blockholiday");
echo '</td><td align="left">'.$langs->trans("blockholidayDesc").'</td>';
echo  '<td align="left"><input type = "checkbox" name = "blockholiday" value="1" ';
echo (($blockholiday == '1')?'checked':'')."></td></tr>";
// add public holiday time
echo  '<tr class="oddeven"><td align="left">'.$langs->trans("addpublicholidaytime");
echo '</td><td align="left">'.$langs->trans("addpublicholidaytimeDesc").'</td>';
echo  '<td align="left"><input type = "checkbox" name = "addpublicholidaytime" value="1" ';
echo (($addpublicholidaytime == '1')?'checked':'')."></td></tr>";
// block public holiday 
echo  '<tr class="oddeven"><td align="left">'.$langs->trans("blockpublicholiday");
echo '</td><td align="left">'.$langs->trans("blockpublicholidayDesc").'</td>';
echo  '<td align="left"><input type = "checkbox" name = "blockpublicholiday" value="1" ';
echo (($blockpublicholiday == '1')?'checked':'')."></td></tr>";
// overtime week to check 
echo '<tr class="oddeven"><td align="left">'.$langs->trans("overtimeCheckWeeks");//FIXTRAD
echo '</td><td align="left">'.$langs->trans("overtimeCheckWeeksDesc").'</td>';// FIXTRAD
echo '<td align="left"><input type = "text" name = "overtimecheckweeks" value="'.$overtimecheckweeks;
echo "\" size = \"4\" ></td></tr>";

// add docs
echo  '<tr class="oddeven"><td align="left">'.$langs->trans("adddocs");
echo '</td><td align="left">'.$langs->trans("adddocsDesc").'</td>';
echo  '<td align="left"><input type = "checkbox" name = "adddocs" value="1" ';
echo (($adddocs == '1')?'checked':'')."></td></tr>";
//Add for other
echo  '<tr class="oddeven"><td align="left">'.$langs->trans("addForOther");
echo '</td><td align="left">'.$langs->trans("addForOtherDesc").'</td>';
echo  '<td align="left"><input type = "checkbox" name = "addForOther" value="1" ';
echo (($addForOther == '1')?'checked':'')."></td></tr>";
echo "</table><br>";

print load_fiche_titre( $langs->trans("DiplayOptions"), '', '' );
echo '<table class="noborder" width = "100%">';
echo '<tr class="liste_titre" width = "100%" ><th width = "200px">'.$langs->trans("Name").'</th><th>';
echo $langs->trans("Description").'</th><th>'.$langs->trans("Value")."</th></tr>";
// hide draft
echo  '<tr class="oddeven"><td align="left">'.$langs->trans("hidedraft");
echo '</td><td align="left">'.$langs->trans("hideDraftDesc").'</td>';
echo  '<td align="left"><input type = "checkbox" name = "hidedraft" value="1" ';
echo (($hidedraft == '1')?'checked':'')."></td></tr>";
// hide ref
echo  '<tr class="oddeven"><td align="left">'.$langs->trans("hideref");
echo '</td><td align="left">'.$langs->trans("hideRefDesc").'</td>';
echo  '<td align="left"><input type = "checkbox" name = "hideref" value="1" ';
echo (($hideref == '1')?'checked':'')."></td></tr>";
// hide zeros
echo  '<tr class="oddeven"><td align="left">'.$langs->trans("hidezeros");
echo '</td><td align="left">'.$langs->trans("hideZerosDesc").'</td>';
echo  '<td align="left"><input type = "checkbox" name = "hidezeros" value="1" ';
echo (($hidezeros == '1')?'checked':'')."></td></tr>";
// show timespentNote
echo  '<tr class="oddeven"><td align="left">'.$langs->trans("ShowTimespentNote");
echo '</td><td align="left">'.$langs->trans("ShowTimespentNoteDesc").'</td>';
echo  '<td align="left"><input type = "checkbox" name = "showTimespentNote" value="1" ';
echo (($showTimespentNote == '1')?'checked':'')."></td></tr>";
echo "</table><br>";

print load_fiche_titre( $langs->trans("OpenDays"), '', '' );
echo '<table class="noborder" width = "100%">';
echo '<tr class="liste_titre" width = "100%" ><th>'.$langs->trans("Monday").'</th><th>';
echo $langs->trans("Tuesday").'</th><th>'.$langs->trans("Wednesday").'</th><th>';
echo $langs->trans("Thursday").'</th><th>'.$langs->trans("Friday").'</th><th>';
echo $langs->trans("Saturday").'</th><th>'.$langs->trans("Sunday").'</th>';
echo '<input type = "hidden" name = "opendays[0]" value="_">';
echo "</tr><tr>";
for ($i = 1; $i<8; $i++) {
    echo  '<td width = "14%" style = "text-align:left"><input type = "checkbox" name = "opendays['.$i.']" value="1" ';
    echo (($opendays[$i] == '1')?'checked':'')."></td>";
}
echo "</tr></table><br>";

print load_fiche_titre( $langs->trans("ColumnToShow"), '', '' );
echo '<table class="noborder" width = "100%">';
echo '<tr class="liste_titre" width = "100%" ><th width = "200px">'.$langs->trans("Name").'</th><th>';
echo $langs->trans("Description").'</th><th>'.$langs->trans("Value")."</th></tr>";
// Project
echo  '<tr class="oddeven"><td align="left">'.$langs->trans("Project");
echo '</td><td align="left">'.$langs->trans("ProjectColDesc").'</td>';
echo  '<td align="left"><input type = "checkbox" name = "showProject" value="1" ';
echo (($showProject == '1')?'checked':'')."></td></tr>";
// task parent
echo  '<tr class="oddeven"><td align="left">'.$langs->trans("TaskParent");
echo '</td><td align="left">'.$langs->trans("TaskParentColDesc").'</td>';
echo  '<td align="left"><input type = "checkbox" name = "showTaskParent" value="1" ';
echo (($showTaskParent == '1')?'checked':'')."></td></tr>";
// task
echo  '<tr class="oddeven"><td align="left">'.$langs->trans("Tasks");
echo '</td><td align="left">'.$langs->trans("TasksColDesc").'</td>';
echo  '<td align="left"><input type = "checkbox" name = "showTasks" value="1" ';
echo (($showTasks == '1')?'checked':'')."></td></tr>";
// date de debut
echo  '<tr class="oddeven"><td align="left">'.$langs->trans("DateStart");
echo '</td><td align="left">'.$langs->trans("DateStartColDesc").'</td>';
echo  '<td align="left"><input type = "checkbox" name = "showDateStart" value="1" ';
echo (($showDateStart == '1')?'checked':'')."></td></tr>";
// date de fin
echo  '<tr class="oddeven"><td align="left">'.$langs->trans("DateEnd");
echo '</td><td align="left">'.$langs->trans("DateEndColDesc").'</td>';
echo  '<td align="left"><input type = "checkbox" name = "showDateEnd" value="1" ';
echo (($showDateEnd == '1')?'checked':'')."></td></tr>";
// Progres
echo  '<tr class="oddeven"><td align="left">'.$langs->trans("Progress");
echo '</td><td align="left">'.$langs->trans("ProgressColDesc").'</td>';
echo  '<td align="left"><input type = "checkbox" name = "showProgress" value="1" ';
echo (($showProgress == '1')?'checked':'')."></td></tr>";
// ProgresD
echo  '<tr class="oddeven"><td align="left">'.$langs->trans("ProgressDeclared");
echo '</td><td align="left">'.$langs->trans("ProgressDeclaredColDesc").'</td>';
echo  '<td align="left"><input type = "checkbox" name = "showProgressDeclared" value="1" ';
echo (($showProgressDeclared == '1')?'checked':'')."></td></tr>";
// Company
echo  '<tr class="oddeven"><td align="left">'.$langs->trans("Company");
echo '</td><td align="left">'.$langs->trans("CompanyColDesc").'</td>';
echo  '<td align="left"><input type = "checkbox" name = "showCompany" value="1" ';
echo (($showCompany == '1')?'checked':'')."></td></tr>";
//note
echo  '<tr class="oddeven"><td align="left">'.$langs->trans("Note");
echo '</td><td align="left">'.$langs->trans("NoteDesc").'</td>';
echo  '<td align="left"><input type = "checkbox" name = "showNote" value="1" ';
echo (($showNote == '1')?'checked':'')."></td></tr>";
//Total
echo  '<tr class="oddeven"><td align="left">'.$langs->trans("Total");
echo '</td><td align="left">'.$langs->trans("TotalDesc").'</td>';
echo  '<td align="left"><input type = "checkbox" name = "showTotal" value="1" ';
echo (($showTotal == '1')?'checked':'')."></td></tr>";
/*
// custom FIXME
echo  '<tr class="oddeven"><th align="left">'.$langs->trans("CustomCol");
echo '</th><th align="left">'.$langs->trans("CustomColDesc").'</th>';
echo  '<th align="left"><input type = "checkbox" name = "showCustomCol" value="1" ';
echo (($showCustomCol == '1')?'checked':'')."</th></tr>";
*/
echo '</table>';
echo "</div>";

/*
 * TAB ADVANCED
 */
echo '<div id="advanced" class="tabBar">';
print '<span class="opacitymedium">'.$langs->trans("AdvancedTabDesc").'</span>';

print load_fiche_titre( $langs->trans("Approval"), '', '' );
echo '<table class="noborder" width = "100%">';
// approval by week
echo '<tr class="liste_titre" width = "100%" ><th width = "200px">'.$langs->trans("Name").'</th><th>';
echo $langs->trans("Description").'</th><th>'.$langs->trans("Value")."</th></tr>";
echo  '<tr class="oddeven"><td align="left">'.$langs->trans("approvalbyweek");
echo '</td><td align="left">'.$langs->trans("approvalbyweekDesc").'</td>';
echo '<td align="left"><input type = "radio" name = "approvalbyweek" value="0" ';
echo ($approvalbyweek == '0'?"checked":"").'> '.$langs->trans("User").'<br>';
echo '<input type = "radio" name = "approvalbyweek" value="1" ';
echo ($approvalbyweek == '1'?"checked":"").'> '.$langs->trans("Week").'<br>';
echo '<input type = "radio" name = "approvalbyweek" value="2" ';
echo ($approvalbyweek == '2'?"checked":"").'> '.$langs->trans("Month")."</td></tr>";
// max approval
echo '<tr class="oddeven" ><td align="left">'.$langs->trans("maxapproval");//FIXTRAD
echo '</td><td align="left">'.$langs->trans("maxapprovalDesc").'</td>';// FIXTRAD
echo '<td  align="left"><input type = "text" name = "maxapproval" value="'.$maxApproval;
echo "\" size = \"4\" ></td></tr>";
echo '</table>';

// approval flows
print load_fiche_titre( $langs->trans("ApplovalFlow"), '', '' );
echo '<table class="noborder" width = "100%">';
echo '<tr class="liste_titre" width = "100%" ><th width = "200px">'.$langs->trans("Name").'</th><th>';
echo $langs->trans("Description").'</th><th>'.$langs->trans("Value");
echo '<input type = "hidden" name = "apflows[0]" value="_"></th>';
echo "</tr>";
//team
echo '<tr><td>'.$langs->trans("Team").'</td><td>'.$langs->trans("TeamApprovalDesc").'</td><td><input type = "checkbox" name = "apflows[1]" value="1"';
echo (($apflows[1] == '1')?'checked':'').'></td><tr>';
//Project
echo '<tr><td>'.$langs->trans("Project").'</td><td>'.$langs->trans("ProjectApprovalDesc").'</td><td><input type = "checkbox" name = "apflows[2]" value="1"';
echo (($apflows[2] == '1')?'checked':'').'></td><tr>';
/*//Customer
echo '<tr style = "display:none"><td>'.$langs->trans("Customer").'</td><td>'.$langs->trans("CustomerApprovalDesc").'</td><td><input type = "checkbox" name = "apflows[3]" value="1"';
echo (($apflows[3] == '1')?'checked':'').'></td><tr>';
//Supplier
echo '<tr style = "display:none"><td>'.$langs->trans("Supplier").'</td><td>'.$langs->trans("SupplierApprovalDesc").'</td><td><input type = "checkbox" name = "apflows[4]" value="1"';
echo (($apflows[4] == '1')?'checked':'').'></td><tr>';
//Other
echo '<tr style = "display:none"><td>'.$langs->trans("Other").'</td><td>'.$langs->trans("OtherApprovalDesc").'</td><td><input type = "checkbox" name = "apflows[5]" value="1"';
echo (($apflows[5] == '1')?'checked':'').'></td><tr>';
*/
echo "</tr></table><br>";
print load_fiche_titre( $langs->trans("Attendance"), '', '' );
echo '<table class="noborder" width = "100%">';
echo '<tr class="liste_titre" width = "100%" ><th width = "200px">'.$langs->trans("Name").'</th><th>';
echo $langs->trans("Description").'</th><th>'.$langs->trans("Value")."</th></tr>";
//min hours per event
echo '<tr class="oddeven"><td align="left">'.$langs->trans("minSecondsPerEvent");
echo '</td><td align="left">'.$langs->trans("minSecondsPerEventDesc").'</td>';
echo '<td align="left"><input type = "text" name = "minSecondsPerEvent" value="'.$minsecondsperevent;
echo "\" size = \"4\" ></td></tr>";
//max hours per event
echo '<tr class="oddeven"><td align="left">'.$langs->trans("maxHoursPerEvent");
echo '</td><td align="left">'.$langs->trans("maxHoursPerEventDesc").'</td>';
echo '<td align="left"><input type = "text" name = "maxhoursperevent" value="'.$maxhoursperevent;
echo "\" size = \"4\" ></td></tr>";
//default hours per event
echo '<tr class="oddeven"><td align="left">'.$langs->trans("defaultHoursPerEvent");
echo '</td><td align="left">'.$langs->trans("defaultHoursPerEventDesc").'</td>';
echo '<td align="left"><input type = "text" name = "defaulthoursperevent" value="'.$defaulthoursperevent;
echo "\" size = \"4\" ></td></tr>";
// block creation of timespent
echo  '<tr class="oddeven"><td align="left">'.$langs->trans("blockTimespent");
echo '</td><td align="left">'.$langs->trans("blockTimespentDesc").'</td>';
echo  '<td align="left"><input type = "checkbox" name = "blockTimespent" value="1" ';
echo (($blockTimespent == '1')?'checked':'')."></td></tr>";
echo "</table><br>";

//Color
print load_fiche_titre( $langs->trans("Color"), '', '' );
echo '<table class="noborder" width = "100%">';
echo '<tr class="liste_titre" width = "100%" ><th width = "200px">'.$langs->trans("Name").'</th><th>';
echo $langs->trans("Description").'</th><th>'.$langs->trans("Value")."</th></tr>";
// color draft
echo  '<tr class="oddeven"><td align="left">'.$langs->trans("draft");
echo '</td><td align="left">'.$langs->trans("draftColorDesc").'</td>';
echo  '<td align="left"><input name = "draftColor" class="jscolor" value="';
echo $draftColor."\"></td></tr>";
// color value
echo  '<tr class="oddeven"><td align="left">'.$langs->trans("value");
echo '</td><td align="left">'.$langs->trans("valueColorDesc").'</td>';
echo  '<td align="left"><input name = "valueColor" class="jscolor" value="';
echo $valueColor."\"></td></tr>";
// color frozen
echo  '<tr class="oddeven"><td align="left">'.$langs->trans("frozen");
echo '</td><td align="left">'.$langs->trans("frozenColorDesc").'</td>';
echo  '<td align="left"><input name = "frozenColor" class="jscolor" value="';
echo $frozenColor."\"></td></tr>";
// color submitted
echo  '<tr class="oddeven"><td align="left">'.$langs->trans("submitted");
echo '</td><td align="left">'.$langs->trans("submittedColorDesc").'</td>';
echo  '<td align="left"><input name = "submittedColor" class="jscolor" value="';
echo $submittedColor."\"></td></tr>";
// color approved
echo  '<tr class="oddeven"><td align="left">'.$langs->trans("approved");
echo '</td><td align="left">'.$langs->trans("approvedColorDesc").'</td>';
echo  '<td align="left"><input name = "approvedColor" class="jscolor" value="';
echo $approvedColor."\"></td></tr>";
// color cancelled
echo  '<tr class="oddeven"><td align="left">'.$langs->trans("cancelled");
echo '</td><td align="left">'.$langs->trans("cancelledColorDesc").'</td>';
echo  '<td align="left"><input name = "cancelledColor" class="jscolor" value="';
echo $cancelledColor."\"></td></tr>";
// color rejected
echo  '<tr class="oddeven"><td align="left">'.$langs->trans("rejected");
echo '</td><td align="left">'.$langs->trans("rejectedColorDesc").'</td>';
echo  '<td align="left"><input name = "rejectedColor" class="jscolor" value="';
echo $rejectedColor."\"></td></tr>";
echo '</table><br>';

//whitelist mode
print load_fiche_titre( $langs->trans("blackWhiteList"), '', '' );
echo '<table class="noborder" width = "100%">';
echo '<tr class="liste_titre" width = "100%" ><th width = "200px">'.$langs->trans("Name").'</th><th>';
echo $langs->trans("Description").'</th><th>'.$langs->trans("Value")."</th></tr>";
// whitelist on/off
echo  '<tr class="oddeven"><td align="left">'.$langs->trans("blackWhiteList");
echo '</td><td align="left">'.$langs->trans("blackWhiteListDesc").'</td>';
echo  '<td align="left"><input type = "checkbox" name = "blackWhiteList" value="1" ';
echo (($whiteList == '1')?'checked':'')."></td></tr>";
// Project
echo  '<tr class="oddeven"><td align="left">'.$langs->trans("blackWhiteListMode").'</td>';
echo '<td align="left">'.$langs->trans("blackWhiteListModeDesc").'</td>';
echo '<td align="left"><input type = "radio" name = "blackWhiteListMode" value="0" ';
echo ($whiteListMode == "0"?"checked":"").'> '.$langs->trans("modeWhiteList").'<br>';
echo '<input type = "radio" name = "blackWhiteListMode" value="1" ';
echo ($whiteListMode == "1"?"checked":"").'> '.$langs->trans("modeBlackList")."<br>";
echo '<input type = "radio" name = "blackWhiteListMode" value="2" ';
echo ($whiteListMode == "2"?"checked":"").'> '.$langs->trans("modeNone")."</td></tr>";
echo '</table><br>';
echo '<br>';

//advanced behaviour
print load_fiche_titre( $langs->trans("AdvancedBehaviour"), '', '' );
echo '<table class="noborder" width = "100%">';
echo '<tr class="liste_titre" width = "100%" ><th width = "200px">'.$langs->trans("Name").'</th><th>';
echo $langs->trans("Description").'</th><th>'.$langs->trans("Value")."</th></tr>";
// searchbox
echo  '<tr class="oddeven"><td align="left">'.$langs->trans("searchbox");
echo '</td><td align="left">'.$langs->trans("searchboxDesc").'</td>';
echo  '<td align="left"><input type = "checkbox" name = "searchBox" value="1" ';
echo (($searchbox == '1')?'checked':'')."></td></tr>";
// ROUND
echo '<tr class="oddeven" ><td align="left">'.$langs->trans("tsRound");
echo '</td><td align="left">'.$langs->trans("tsRoundDesc").'</td>';
echo '<td  align="left"><input type = "text" name = "tsRound" value="'.$tsRound;
echo "\" size = \"4\" ></td></tr>";
// IMPORT AGENDA
echo '<tr class="oddeven" ><td align="left">'.$langs->trans("ImportAgenda");
echo '</td><td align="left">'.$langs->trans("ImportAgendaDesc").'</td>';
echo  '<td align="left"><input type = "checkbox" name = "importagenda" value="1" ';
echo (($importagenda == '1')?'checked':'')."></td></tr>";
// eval ADDLINE
echo  '<tr class="oddeven"><td align="left">'.$langs->trans("evalAddLine");
echo '</td><td align="left">'.$langs->trans("evalAddLineDesc").'</td>';
echo  '<td align="left"><input type = "checkbox" name = "evalAddLine" value="1" ';
echo (($evalAddLine == '1')?'checked':'')."></td></tr>";
// export format
$formats = array();
foreach (glob(DOL_DOCUMENT_ROOT . "/core/modules/export/export_*.modules.php") as $file) {
    preg_match_all("/export_(?<format>.+)\.modules\.php/", $file, $matches);
    $formats[] = $matches['format'][0];
}
echo '<tr class="oddeven" ><td align="left">'.$langs->trans("exportFormat");
echo '</td><td align="left">'.$langs->trans("exportFormatDesc").'</td>';
echo '<td  align="left"><select name = "exportFormat">';
foreach($formats as $format){
echo "<option value=\"$format\" ".($exportFormat==$format?'selected':'').">$format</option>";
}
echo "</select></td></tr>";
// allow add time on public project
echo  '<tr class="oddeven"><td align="left">'.$langs->trans("allowPublic");
echo '</td><td align="left">'.$langs->trans("allowPublicDesc").'</td>';
echo  '<td align="left"><input type = "checkbox" name = "allowPublic" value="1" ';
echo (($allowPublic == '1')?'checked':'')."></td></tr>";

// unblock invoiced
echo  '<tr class="oddeven"><td align="left">'.$langs->trans("unblockInvoiced");
echo '</td><td align="left">'.$langs->trans("unblockInvoicedDesc").'</td>';
echo  '<td align="left"><input type = "checkbox" name = "unblockInvoiced" value="1" ';
echo (($unblockInvoiced == '1')?'checked':'')."></td></tr>";
// unblock closed day
echo  '<tr class="oddeven"><td align="left">'.$langs->trans("unblockClosed");
echo '</td><td align="left">'.$langs->trans("unblockClosedDesc").'</td>';
echo  '<td align="left"><input type = "checkbox" name = "unblockClosed" value="1" ';
echo (($unblockClosed == '1')?'checked':'')."></td></tr>";
// show invoiced col in reports
echo  '<tr class="oddeven"><td align="left">'.$langs->trans("reportInvoicedCol");
echo '</td><td align="left">'.$langs->trans("reportInvoicedColDesc").'</td>';
echo  '<td align="left"><input type = "checkbox" name = "reportInvoicedCol" value="1" ';
echo (($reportInvoicedCol == '1')?'checked':'')."></td></tr>";
// ungroup lvl3 reports
echo  '<tr class="oddeven"><td align="left">'.$langs->trans("reportUngroup");
echo '</td><td align="left">'.$langs->trans("reportUngroupDesc").'</td>';
echo  '<td align="left"><input type = "checkbox" name = "reportUngroup" value="1" ';
echo (($reportUngroup == '1')?'checked':'')."></td></tr>";



echo '</table>';

echo '</div>';
/*
 * INVOICE
 */
echo '<div id="invoice" class="tabBar">';
print '<span class="opacitymedium">'.$langs->trans("InvoiceTabDesc").'</span>';

print load_fiche_titre( $langs->trans("Invoice"), '', '' );
echo '<table class="noborder" width = "100%">';
echo '<tr class="liste_titre" width = "100%" ><th width = "200px">'.$langs->trans("Name").'</th><th>';
echo $langs->trans("Description").'</th><th>'.$langs->trans("Value")."</th></tr>";
//lines invoice method
echo  '<tr class="oddeven"><td align="left">'.$langs->trans("invoiceMethod");
echo '</td><td align="left">'.$langs->trans("invoiceMethodDesc").'</td>';
echo  '<td align="left"><input type = "radio" name = "invoiceMethod" value="task" ';
echo (($invoicemethod == 'task')?'checked':'').">".$langs->trans("Task").'<br>';
echo '<input type = "radio" name = "invoiceMethod" value="user" ';
echo (($invoicemethod == 'user')?'checked':'').">".$langs->trans("User").'<br>';
echo '<input type = "radio" name = "invoiceMethod" value="taskUser" ';
echo (($invoicemethod == 'taskUser')?'checked':'').">".$langs->trans("Tasks").' & '.$langs->trans("User").'<br>';
echo "</td></tr>";
// type time
echo '<tr class="oddeven"><td align="left">'.$langs->trans("invoiceTimeType").'</td><td align="left">'.$langs->trans("invoiceTimeTypeDesc").'</td>';
echo '<td align="left"><input type = "radio" name = "invoiceTimeType" value="hours" ';
echo ($invoicetimetype == "hours"?"checked":"").'> '.$langs->trans("Hours").'<br>';
echo '<input type = "radio" name = "invoiceTimeType" value="days" ';
echo ($invoicetimetype == "days"?"checked":"").'> '.$langs->trans("Days")."</td></tr>";
//line invoice Service
echo  '<tr class="oddeven"><td align="left">'.$langs->trans("invoiceService");
echo '</td><td align="left">'.$langs->trans("invoiceServiceDesc").'</td>';
echo  '<td align="left">';
$addchoices = array('-999'=> $langs->transnoentitiesnoconv('not2invoice'), -997=> $langs->transnoentitiesnoconv('Custom'));
$ajaxNbChar = getConf('PRODUIT_USE_SEARCH_TO_SELECT');
$htmlProductArray = array('name' => 'invoiceservice', 'ajaxNbChar'=>$ajaxNbChar);
$sqlProductArray = array('table' => 'product', 'keyfield' => 'rowid', 'fields' => 'ref, label', 'where' => 'tosell = 1 AND fk_product_type = 1', 'separator' => ' - ');
print select_sellist($sqlProductArray, $htmlProductArray, $invoiceservice, $addchoices);
echo "</td></tr>";
//line tasktime ==
echo  '<tr class="oddeven"><td align="left">'.$langs->trans("invoiceTaskTime");
echo '</td><td align="left">'.$langs->trans("invoiceTaskTimeDesc").'</td>';
echo  '<td align="left"><input type = "radio" name = "invoiceTaskTime" value="all" ';
echo (($invoicetasktime == 'all')?'checked':'').">".$langs->trans("All").'<br>';
echo '<input type = "radio" name = "invoiceTaskTime" value="approved" ';
echo (($invoicetasktime == 'approved')?'checked':'').">".$langs->trans("Approved").'<br>';
echo "</td></tr>";
//line show user
echo  '<tr class="oddeven"><td align="left">'.$langs->trans("invoiceShowUser");
echo '</td><td align="left">'.$langs->trans("invoiceShowUserDesc").'</td>';
echo  '<td align="left"><input type = "checkbox" name = "invoiceShowUser" value="1" ';
echo (($invoiceshowuser == '1')?'checked':'')."></td></tr>";
//line show task
echo  '<tr class="oddeven"><td align="left">'.$langs->trans("invoiceShowTask");
echo '</td><td align="left">'.$langs->trans("invoiceShowTaskDesc").'</td>';
echo  '<td align="left"><input type = "checkbox" name = "invoiceShowTask" value="1" ';
echo (($invoiceshowtask == '1')?'checked':'')."></td></tr>";
//hide signbox
echo  '<tr class="oddeven"><td align="left">'.$langs->trans("pdfHideSignbox");
echo '</td><td align="left">'.$langs->trans("pdfHideSignboxDesc").'</td>';
echo  '<td align="left"><input type = "checkbox" name = "pdfHideSignbox" value="1" ';
echo (($pdfhidesignbox == '1')?'checked':'')."></td></tr>";
//hide name
echo  '<tr class="oddeven"><td align="left">'.$langs->trans("pdfHideName");
echo '</td><td align="left">'.$langs->trans("pdfHideNameDesc").'</td>';
echo  '<td align="left"><input type = "checkbox" name = "pdfHideName" value="1" ';
echo (($pdfHideName == '1')?'checked':'')."></td></tr>";


// Show note on PDF
echo '<tr class="oddeven"><td align="left">'.$langs->trans("NoteOnPDF").'</td><td align="left">'.$langs->trans("NoteOnPDFDesc").'</td>';
echo '<td align="left"><input type = "radio" name = "noteOnPDF" value="0" ';
echo ($noteOnPDF == "0"?"checked":"").'> '.$langs->trans("Task").'<br> <input type = "radio" name = "noteOnPDF" value="1" ';
echo ($noteOnPDF == "1"?"checked":"").'> '.$langs->trans("Note").'<br>  <input type = "radio" name = "noteOnPDF" value="2"';
echo ($noteOnPDF == "2"?"checked":"").'> '.$langs->trans("Task")."&".$langs->trans("Note")."</td></tr>";
echo '</table><br>';
echo '</div>'; // END TAB 'invoice'

/*
 * TAB: Other
 */
echo '<div id="other" class="tabBar">';
	print '<span class="opacitymedium">'.$langs->trans("OtherTabDesc").'</span>';

	print load_fiche_titre( $langs->trans("Dolibarr"), '', '' );
	echo '<table class="noborder" width = "100%">';
		echo '<tr class="liste_titre" width = "100%" ><th width = "200px">'.$langs->trans("Name").'</th><th>';
		echo $langs->trans("Description").'</th><th>'.$langs->trans("Value")."</th></tr>";
		echo  '<tr class="oddeven"><td align="left">'.$langs->trans("dropdownAjax");
		echo '</td><td align="left">'.$langs->trans("dropdownAjaxDesc").'</td>';
		echo  '<td align="left"><input type = "checkbox" name = "dropdownAjax" value="1" ';
		echo (($dropdownAjax == '1')?'checked':'')."></td></tr>";
	echo '</table>';

	// doc
	print load_fiche_titre( $langs->trans("Manual"), '', '' );
	echo '<ul>';
	echo '<li><a href="../doc/Module_timesheet.pdf">  PDF </a></li>';
	echo '<li><a href="../doc/Module_timesheet.docx">  DOCX </a></li>';
    echo '<li><a href="../doc/html/index.html">  HTML </a></li>';
	echo '</ul>';

	print load_fiche_titre( $langs->trans("Feedback"), '', '' );
	echo $langs->trans('feebackDesc').' : <a href = "mailto:patrick@pmpd.eu?subject=TimesheetFeedback"> Patrick Delcroix</a>';

	print load_fiche_titre( $langs->trans("Reminder"), '', '' );
	print '<div>'.$langs->trans('reminderEmailProcess').'</div>';
    print load_fiche_titre( $langs->trans("Traduction"), '', '' );
    print '<a href="https://app.lokalise.com/public/761399855cb829e995d448.06757516">Localize Project</a>';

echo '</div>'; // END TAB 'other'

print '<div class="tabsAction">';
	print '<div class="inline-block divButAction"><input type="submit" class="button butAction" value="' . $langs->trans( 'Save' ) . '" /></div>';
print "</div>";

echo '</form>';
echo '<script>document.getElementById("defaultOpen").click()</script>';

llxFooter();
$db->close();
