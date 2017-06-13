<?php
/*
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
 *  \file       htdocs/admin/project.php
 *  \ingroup    project
 *  \brief      Page to setup project module
 */

include '../core/lib/includeMain.lib.php';
include '../core/lib/generic.lib.php';

require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/project.lib.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/task.class.php';

$langs->load("admin");
$langs->load("errors");
$langs->load("other");
$langs->load("timesheet@timesheet");
        
if (!$user->admin) {
    $accessforbidden = accessforbidden("you need to be admin");           
}
$action = GETPOST('action','alpha');
$timetype=TIMESHEET_TIME_TYPE;
$hoursperday=TIMESHEET_DAY_DURATION;
$maxhoursperday=TIMESHEET_DAY_MAX_DURATION;
$maxApproval=TIMESHEET_MAX_APPROVAL;
$hidedraft=TIMESHEET_HIDE_DRAFT;
$hideref=TIMESHEET_HIDE_REF;
$hidezeros=TIMESHEET_HIDE_ZEROS;
$approvalbyweek=TIMESHEET_APPROVAL_BY_WEEK;
$headers=TIMESHEET_HEADERS;
$whiteListMode=TIMESHEET_WHITELIST_MODE;
$whiteList=TIMESHEET_WHITELIST;
$dropdownAjax=MAIN_DISABLE_AJAX_COMBOX;
$draftColor=TIMESHEET_COL_DRAFT;
$submittedColor=TIMESHEET_COL_SUBMITTED;
$approvedColor=TIMESHEET_COL_APPROVED;
$rejectedColor=TIMESHEET_COL_REJECTED;
$cancelledColor=TIMESHEET_COL_CANCELLED;
$addholidaytime=TIMESHEET_ADD_HOLIDAY_TIME;
$adddocs=TIMESHEET_ADD_DOCS;
$opendays=str_split(TIMESHEET_OPEN_DAYS);
//Invoice part

$invoicemethod=TIMESHEET_INVOICE_METHOD;
$invoicetasktime=TIMESHEET_INVOICE_TASKTIME;
$invoiceservice=TIMESHEET_INVOICE_SERVICE;
$invoiceshowtask=TIMESHEET_INVOICE_SHOW_TASK;
$invoiceshowuser=TIMESHEET_INVOICE_SHOW_USER;

if(sizeof($opendays)!=8)$opendays=array('_','0','0','0','0','0','0','0');
$apflows=str_split(TIMESHEET_APPROVAL_FLOWS);
if(sizeof($apflows)!=6)$apflows=array('_','0','0','0','0','0');
switch($action)
{
    case save:
        if(GETPOST('timeType','alpha')==''){ // if no POST data
           break;
        }
        //general option
        $timetype=GETPOST('timeType','alpha');
        $hoursperday=GETPOST('hoursperday','alpha');
        $maxhoursperday=GETPOST('maxhoursperday','alpha');
        $hidedraft=GETPOST('hidedraft','alpha');
        $hidezeros=GETPOST('hidezeros','alpha');
        $maxApproval=GETPOST('maxapproval','int');
        $approvalbyweek=GETPOST('approvalbyweek','int');
        $hideref=GETPOST('hideref','alpha');        
        $whiteListMode=GETPOST('blackWhiteListMode','int');
        $whiteList=GETPOST('blackWhiteList','int');
        $dropdownAjax=GETPOST('dropdownAjax','int');
        $res=dolibarr_set_const($db, "TIMESHEET_TIME_TYPE", $timetype, 'chaine', 0, '', $conf->entity);
        if (! $res > 0) $error++;
        $res=dolibarr_set_const($db, "TIMESHEET_DAY_DURATION", $hoursperday, 'chaine', 0, '', $conf->entity);
        if (! $res > 0) $error++;
        $res=dolibarr_set_const($db, "TIMESHEET_DAY_MAX_DURATION", $maxhoursperday, 'chaine', 0, '', $conf->entity);
        if (! $res > 0) $error++;
        $res=dolibarr_set_const($db, "TIMESHEET_HIDE_DRAFT", $hidedraft, 'chaine', 0, '', $conf->entity);
        if (! $res > 0) $error++;
        $res=dolibarr_set_const($db, "TIMESHEET_HIDE_ZEROS", $hidezeros, 'chaine', 0, '', $conf->entity);
        if (! $res > 0) $error++;
        $res=dolibarr_set_const($db, "TIMESHEET_APPROVAL_BY_WEEK", $approvalbyweek, 'chaine', 0, '', $conf->entity);
        if (! $res > 0) $error++;
        $res=dolibarr_set_const($db, "TIMESHEET_MAX_APPROVAL", $maxApproval, 'chaine', 0, '', $conf->entity);
        if (! $res > 0) $error++;
        $res=dolibarr_set_const($db, "TIMESHEET_HIDE_REF", $hideref, 'chaine', 0, '', $conf->entity);
        if (! $res > 0) $error++;
         $res=dolibarr_set_const($db, "TIMESHEET_WHITELIST_MODE", $whiteList?$whiteListMode:2, 'int', 0, '', $conf->entity);
        if (! $res > 0) $error++;
        $res=dolibarr_set_const($db, "TIMESHEET_WHITELIST", $whiteList, 'int', 0, '', $conf->entity);
        if (! $res > 0) $error++;
        $res=dolibarr_set_const($db, "MAIN_DISABLE_AJAX_COMBOX", $dropdownAjax, 'int', 0, '', $conf->entity);
        if (! $res > 0) $error++;
        //headers handling
        $showProject=GETPOST('showProject','int');
        $showTaskParent=GETPOST('showTaskParent','int');
        $showTasks=GETPOST('showTasks','int');
        $showDateStart=GETPOST('showDateStart','int');
        $showDateEnd=GETPOST('showDateEnd','int');
        $showProgress=GETPOST('showProgress','int');
        $showCompany=GETPOST('showCompany','int');
        $showNote=GETPOST('showNote','int');
        
        $headers=$showNote?'Note':'';
        $headers.=$showCompany?(empty($headers)?'':'||').'Company':'';
        $headers.=$showProject?(empty($headers)?'':'||').'Project':'';
        $headers.=$showTaskParent?(empty($headers)?'':'||').'TaskParent':'';
        $headers.=$showTasks?(empty($headers)?'':'||').'Tasks':'';
        $headers.=$showDateStart?(empty($headers)?'':'||').'DateStart':'';
        $headers.=$showDateEnd?(empty($headers)?'':'||').'DateEnd':'';
        $headers.=$showProgress?(empty($headers)?'':'||').'Progress':'';

        $res=dolibarr_set_const($db, "TIMESHEET_HEADERS", $headers, 'chaine', 0, '', $conf->entity);
        if (! $res > 0) $error++;
        //color handling
        $draftColor=GETPOST('draftColor','alpha');
        $res=dolibarr_set_const($db, "TIMESHEET_COL_DRAFT", $draftColor, 'chaine', 0, '', $conf->entity);
        if (! $res > 0) $error++;
        $submittedColor=GETPOST('submittedColor','alpha');
        $res=dolibarr_set_const($db, "TIMESHEET_COL_SUBMITTED", $submittedColor, 'chaine', 0, '', $conf->entity);
        if (! $res > 0) $error++;
        $approvedColor=GETPOST('approvedColor','alpha');
        $res=dolibarr_set_const($db, "TIMESHEET_COL_APPROVED", $approvedColor, 'chaine', 0, '', $conf->entity);
        if (! $res > 0) $error++;
        $rejectedColor=GETPOST('rejectedColor','alpha');
        $res=dolibarr_set_const($db, "TIMESHEET_COL_REJECTED", $rejectedColor, 'chaine', 0, '', $conf->entity);
        if (! $res > 0) $error++;
        $cancelledColor=GETPOST('cancelledColor','alpha');
        $res=dolibarr_set_const($db, "TIMESHEET_COL_CANCELLED", $cancelledColor, 'chaine', 0, '', $conf->entity);
        if (! $res > 0) $error++;
        $addholidaytime=GETPOST('addholidaytime','alpha');
        $res=dolibarr_set_const($db, "TIMESHEET_ADD_HOLIDAY_TIME", $addholidaytime, 'chaine', 0, '', $conf->entity);
        if (! $res > 0) $error++;
        $adddocs=GETPOST('adddocs','alpha');
        $res=dolibarr_set_const($db, "TIMESHEET_ADD_DOCS", $adddocs, 'chaine', 0, '', $conf->entity);
        if (! $res > 0) $error++;        
        $opendays=array('_','0','0','0','0','0','0','0');
        foreach($_POST['opendays'] as $key => $day){
            $opendays[$key]=$day;
        }
        $res=dolibarr_set_const($db, "TIMESHEET_OPEN_DAYS", implode('',$opendays), 'chaine', 0, '', $conf->entity);
        if (! $res > 0) $error++;        

        $apflows=array('_','0','0','0','0','0');
        foreach($_POST['apflows'] as $key => $flow){
            $apflows[$key]=$flow;
        }
        
        //INVOICE
        $res=dolibarr_set_const($db, "TIMESHEET_APPROVAL_FLOWS", implode('',$apflows), 'chaine', 0, '', $conf->entity);
        if (! $res > 0) $error++;        
        $invoicemethod=GETPOST('invoiceMethod','alpha');
        $res=dolibarr_set_const($db, "TIMESHEET_INVOICE_METHOD", $invoicemethod, 'chaine', 0, '', $conf->entity);
        if (! $res > 0) $error++;
        $invoicetasktime=GETPOST('invoiceTaskTime','alpha');
        $res=dolibarr_set_const($db, "TIMESHEET_INVOICE_TASKTIME", $invoicetasktime, 'chaine', 0, '', $conf->entity);
        if (! $res > 0) $error++;
        $invoiceservice=GETPOST('invoiceService','alpha');
        $res=dolibarr_set_const($db, "TIMESHEET_INVOICE_SERVICE", $invoiceservice, 'chaine', 0, '', $conf->entity);
        if (! $res > 0) $error++;
        $invoiceshowtask=GETPOST('invoiceShowTask','alpha');
        $res=dolibarr_set_const($db, "TIMESHEET_INVOICE_SHOW_TASK", $invoiceshowtask, 'chaine', 0, '', $conf->entity);
        if (! $res > 0) $error++;
        $invoiceshowuser=GETPOST('invoiceShowUser','alpha');
        $res=dolibarr_set_const($db, "TIMESHEET_INVOICE_SHOW_USER", $invoiceshowuser, 'chaine', 0, '', $conf->entity);
        if (! $res > 0) $error++;
        // error handling
        
        if (! $error)
        {
            setEventMessage($langs->trans("SetupSaved"));
        }
        else
        {
            setEventMessage($langs->trans("Error"),'errors');
        }
        break;
    default:
        break;
}
$headersT=explode('||',$headers);
foreach ($headersT as $header) {
    switch($header){
        case 'Project':
            $showProject=1;
            Break;
        case 'TaskParent':
            $showTaskParent=1;
            Break;
        case 'Tasks':
            $showTasks=1;
            Break;
        case 'DateStart':
            $showDateStart=1;
            Break;
        case 'DateEnd':
            $showDateEnd=1;
            Break;
        case 'Progress':
            $showProgress=1;
            Break;
        case 'Company':
            $showCompany=1;
            Break;
        case 'Note':
            $showNote=1;
            Break;
        default:
            break;
    }
    
}

/* 
 *  VIEW
 *  */


//permet d'afficher la structure dolibarr
$morejs=array("/timesheet/core/js/timesheet.js","/timesheet/core/js/jscolor.js");
llxHeader("",$langs->trans("timesheetSetup"),'','','','',$morejs,'',0,0);


$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print_fiche_titre($langs->trans("timesheetSetup"),$linkback,'title_setup');
echo '<div class="fiche"><br><br>';
/*
 * TABS
 */

echo '<div class="tabs" data-role="controlgroup" data-type="horizontal"  >';
echo '  <div id="defaultOpen"  class="inline-block tabsElem" onclick="openTab(event, \'General\')"><a  href="javascript:void(0);"  class="tabunactive tab inline-block" data-role="button">'.$langs->trans('General').'</a></div>';
echo '  <div class="inline-block tabsElem" onclick="openTab(event, \'Advanced\')"><a  href="javascript:void(0);" class="tabunactive tab inline-block" data-role="button">'.$langs->trans('Advanced').'</a></div>';
echo '  <div class="inline-block tabsElem"  onclick="openTab(event, \'Invoice\')"><a href="javascript:void(0);" class="tabunactive tab inline-block" data-role="button">'.$langs->trans('Invoice').'</a></div>';
echo '  <div class="inline-block tabsElem"   onclick="openTab(event, \'Other\')"><a href="javascript:void(0);" class="tabunactive tab inline-block" data-role="button">'.$langs->trans('Other').'</a></div>';

echo '</div>';


/*
 * General
 */
echo '<div id="General" class="tabBar">';
print_titre($langs->trans("GeneralOption"));
echo '<form name="settings" action="?action=save" method="POST" >'."\n\t";
echo '<table class="noborder" width="100%">'."\n\t\t";
echo '<tr class="liste_titre" width="100%" ><th width="200px">'.$langs->trans("Name").'</th><th>';
echo $langs->trans("Description").'</th><th width="100px">'.$langs->trans("Value")."</th></tr>\n\t\t";
// type time
echo '<tr class="pair"><th align="left">'.$langs->trans("timeType").'</th><th align="left">'.$langs->trans("timeTypeDesc").'</th>';
echo '<th align="left"><input type="radio" name="timeType" value="hours" ';
echo ($timetype=="hours"?"checked":"").'> '.$langs->trans("hours").'<br>';
echo '<input type="radio" name="timeType" value="days" ';
echo ($timetype=="days"?"checked":"").'> '.$langs->trans("days")."</th></tr>\n\t\t";
//hours perdays
echo '<tr class="impair"><th align="left">'.$langs->trans("hoursperdays");
echo '</th><th align="left">'.$langs->trans("hoursPerDaysDesc").'</th>';
echo '<th align="left"><input type="text" name="hoursperday" value="'.$hoursperday;
echo "\" size=\"4\" ></th></tr>\n\t\t";
//max hours perdays
echo '<tr class="pair"><th align="left">'.$langs->trans("maxhoursperdays"); //FIXTRAD
echo '</th><th align="left">'.$langs->trans("maxhoursPerDaysDesc").'</th>'; // FIXTRAD
echo '<th align="left"><input type="text" name="maxhoursperday" value="'.$maxhoursperday;
echo "\" size=\"4\" ></th></tr>\n\t\t";
// hide draft
echo  '<tr class="impair"><th align="left">'.$langs->trans("hidedraft");
echo '</th><th align="left">'.$langs->trans("hideDraftDesc").'</th>';
echo  '<th align="left"><input type="checkbox" name="hidedraft" value="1" ';
echo (($hidedraft=='1')?'checked':'')."></th></tr>\n\t\t";
// hide ref
echo  '<tr class="pair"><th align="left">'.$langs->trans("hideref");
echo '</th><th align="left">'.$langs->trans("hideRefDesc").'</th>';
echo  '<th align="left"><input type="checkbox" name="hideref" value="1" ';
echo (($hideref=='1')?'checked':'')."></th></tr>\n\t\t";

// hide zeros
echo  '<tr class="impair"><th align="left">'.$langs->trans("hidezeros");
echo '</th><th align="left">'.$langs->trans("hideZerosDesc").'</th>';
echo  '<th align="left"><input type="checkbox" name="hidezeros" value="1" ';
echo (($hidezeros=='1')?'checked':'')."></th></tr>\n";


// add holiday time
echo  '<tr class="pair"><th align="left">'.$langs->trans("addholidaytime");
echo '</th><th align="left">'.$langs->trans("addholidaytimeDesc").'</th>';
echo  '<th align="left"><input type="checkbox" name="addholidaytime" value="1" ';
echo (($addholidaytime=='1')?'checked':'')."></th></tr>\n";

// add docs
echo  '<tr class="impair"><th align="left">'.$langs->trans("adddocs");
echo '</th><th align="left">'.$langs->trans("adddocsDesc").'</th>';
echo  '<th align="left"><input type="checkbox" name="adddocs" value="1" ';
echo (($adddocs=='1')?'checked':'')."></th></tr>\n";



echo "\t</table><br>\n";





print_titre($langs->trans("OpenDays")); 
echo '<table class="noborder" width="100%">'."\n\t\t";
echo '<tr class="liste_titre" width="100%" ><th>'.$langs->trans("Monday").'</th><th>';
echo $langs->trans("Tuesday").'</th><th>'.$langs->trans("Wednesday").'</th><th>';
echo $langs->trans("Thursday").'</th><th>'.$langs->trans("Friday").'</th><th>';
echo $langs->trans("Saturday").'</th><th>'.$langs->trans("Sunday").'</th>';
echo '<input type="hidden" name="opendays[0]" value="_">';
echo "</tr><tr>\n\t\t";

for ($i=1; $i<8;$i++){
echo  '<th width="14%" style="text-align:left"><input type="checkbox" name="opendays['.$i.']" value="1" ';
echo (($opendays[$i]=='1')?'checked':'')."></th>\n\t\t";
        }
echo "</tr>\n\t</table><br>\n";

print_titre($langs->trans("ColumnToShow"));
echo '<table class="noborder" width="100%">'."\n\t\t";
echo '<tr class="liste_titre" width="100%" ><th width="200px">'.$langs->trans("Name").'</th><th>';
echo $langs->trans("Description").'</th><th width="100px">'.$langs->trans("Value")."</th></tr>\n\t\t";
// Project
echo  '<tr class="impair"><th align="left">'.$langs->trans("Project");
echo '</th><th align="left">'.$langs->trans("ProjectColDesc").'</th>';
echo  '<th align="left"><input type="checkbox" name="showProject" value="1" ';
echo (($showProject=='1')?'checked':'')."></th></tr>\n\t\t";
// task parent
echo  '<tr class="pair"><th align="left">'.$langs->trans("TaskParent");
echo '</th><th align="left">'.$langs->trans("TaskParentColDesc").'</th>';
echo  '<th align="left"><input type="checkbox" name="showTaskParent" value="1" ';
echo (($showTaskParent=='1')?'checked':'')."></th></tr>\n\t\t";
// task
echo  '<tr class="impair"><th align="left">'.$langs->trans("Tasks");
echo '</th><th align="left">'.$langs->trans("TasksColDesc").'</th>';
echo  '<th align="left"><input type="checkbox" name="showTasks" value="1" ';
echo (($showTasks=='1')?'checked':'')."></th></tr>\n\t\t";
// date de debut
echo  '<tr class="pair"><th align="left">'.$langs->trans("DateStart");
echo '</th><th align="left">'.$langs->trans("DateStartColDesc").'</th>';
echo  '<th align="left"><input type="checkbox" name="showDateStart" value="1" ';
echo (($showDateStart=='1')?'checked':'')."></th></tr>\n\t\t";
// date de fin
echo  '<tr class="impair"><th align="left">'.$langs->trans("DateEnd");
echo '</th><th align="left">'.$langs->trans("DateEndColDesc").'</th>';
echo  '<th align="left"><input type="checkbox" name="showDateEnd" value="1" ';
echo (($showDateEnd=='1')?'checked':'')."></th></tr>\n\t\t";
// Progres
echo  '<tr class="pair"><th align="left">'.$langs->trans("Progress");
echo '</th><th align="left">'.$langs->trans("ProgressColDesc").'</th>';
echo  '<th align="left"><input type="checkbox" name="showProgress" value="1" ';
echo (($showProgress=='1')?'checked':'')."></th></tr>\n\t\t";
// Company
echo  '<tr class="impair"><th align="left">'.$langs->trans("Company");
echo '</th><th align="left">'.$langs->trans("CompanyColDesc").'</th>';
echo  '<th align="left"><input type="checkbox" name="showCompany" value="1" ';
echo (($showCompany=='1')?'checked':'')."></th></tr>\n\t\t";
//note
echo  '<tr class="pair"><th align="left">'.$langs->trans("Note");
echo '</th><th align="left">'.$langs->trans("NoteDesc").'</th>';
echo  '<th align="left"><input type="checkbox" name="showNote" value="1" ';
echo (($showNote=='1')?'checked':'')."></th></tr>\n\t\t";


/*
// custom FIXME
echo  '<tr class="pair"><th align="left">'.$langs->trans("CustomCol");
echo '</th><th align="left">'.$langs->trans("CustomColDesc").'</th>';
echo  '<th align="left"><input type="checkbox" name="showCustomCol" value="1" ';
echo (($showCustomCol=='1')?'checked':'')."</th></tr>\n\t\t";
*/
echo '</table>';
echo "</div>\n";

/*
 * ADVANCED
 */



echo '<div id="Advanced" class="tabBar">';

print_titre($langs->trans("Approval")); 
echo '<table class="noborder" width="100%">'."\n\t\t";
// approval by week
echo  '<tr class="pair"><th align="left">'.$langs->trans("approvalbyweek");
echo '</th><th align="left">'.$langs->trans("approvalbyweekDesc").'</th>';
echo '<th align="left"><input type="radio" name="approvalbyweek" value="0" ';
echo ($approvalbyweek=='0'?"checked":"").'> '.$langs->trans("User").'<br>';
echo '<input type="radio" name="approvalbyweek" value="1" ';
echo ($approvalbyweek=='1'?"checked":"").'> '.$langs->trans("Week").'<br>';
echo '<input type="radio" name="approvalbyweek" value="2" ';
echo ($approvalbyweek=='2'?"checked":"").'> '.$langs->trans("Month")."</th></tr>\n\t\t";
// max approval 

echo '<tr class="impair" ><th align="left">'.$langs->trans("maxapproval"); //FIXTRAD
echo '</th><th align="left">'.$langs->trans("maxapprovalDesc").'</th>'; // FIXTRAD
echo '<th  align="left"><input type="text" name="maxapproval" value="'.$maxApproval;
echo "\" size=\"4\" ></th></tr>\n\t\t";
// approval flows
echo '</table>'."\n\t\t";
echo '<table class="noborder" width="100%">'."\n\t\t";
echo '<tr class="liste_titre" width="100%" ><th>'.$langs->trans("Team").'</th><th>';
echo $langs->trans("Project").'</th><th hidden>';
echo $langs->trans("Customer").'</th><th hidden>';
echo $langs->trans("Supplier").'</th><th hidden >'.$langs->trans("Other").'</th>';
echo '<input type="hidden" name="apflows[0]" value="_">';
echo "</tr><tr>\n\t\t";

for ($i=1; $i<6;$i++){
    
echo  '<th width="14%" style="text-align:left"><input type="checkbox" name="apflows['.$i.']" value="1" ';
if($i>2){ //FIXME  cust / supp other not yet supported
    echo ' hidden '."></th>\n\t\t";
}else{
    echo (($apflows[$i]=='1')?'checked':'')."></th>\n\t\t";
}
}
        
echo "</tr>\n\t</table><br>\n";

//Color
print_titre($langs->trans("Color"));
echo '<table class="noborder" width="100%">'."\n\t\t";
echo '<tr class="liste_titre" width="100%" ><th width="200px">'.$langs->trans("Name").'</th><th>';
echo $langs->trans("Description").'</th><th width="100px">'.$langs->trans("Value")."</th></tr>\n\t\t";
// color draft
echo  '<tr class="impair"><th align="left">'.$langs->trans("draft");
echo '</th><th align="left">'.$langs->trans("draftColorDesc").'</th>';
echo  '<th align="left"><input name="draftColor" class="jscolor" value="';
echo $draftColor."\"></th></tr>\n\t\t";
// color submitted
echo  '<tr class="pair"><th align="left">'.$langs->trans("submitted");
echo '</th><th align="left">'.$langs->trans("submittedColorDesc").'</th>';
echo  '<th align="left"><input name="submittedColor" class="jscolor" value="';
echo $submittedColor."\"></th></tr>\n\t\t";
// color approved
echo  '<tr class="impair"><th align="left">'.$langs->trans("approved");
echo '</th><th align="left">'.$langs->trans("approvedColorDesc").'</th>';
echo  '<th align="left"><input name="approvedColor" class="jscolor" value="';
echo $approvedColor."\"></th></tr>\n\t\t";
// color cancelled
echo  '<tr class="pair"><th align="left">'.$langs->trans("cancelled");
echo '</th><th align="left">'.$langs->trans("cancelledColorDesc").'</th>';
echo  '<th align="left"><input name="cancelledColor" class="jscolor" value="';
echo $cancelledColor."\"></th></tr>\n\t\t";
// color rejected
echo  '<tr class="impair"><th align="left">'.$langs->trans("rejected");
echo '</th><th align="left">'.$langs->trans("rejectedColorDesc").'</th>';
echo  '<th align="left"><input name="rejectedColor" class="jscolor" value="';
echo $rejectedColor."\"></th></tr>\n\t\t";


echo '</table><br>';




//whitelist mode
print_titre($langs->trans("blackWhiteList"));
echo '<table class="noborder" width="100%">'."\n\t\t";
echo '<tr class="liste_titre" width="100%" ><th width="200px">'.$langs->trans("Name").'</th><th>';
echo $langs->trans("Description").'</th><th width="100px">'.$langs->trans("Value")."</th></tr>\n\t\t";
// whitelist on/off
echo  '<tr class="impair"><th align="left">'.$langs->trans("blackWhiteList");
echo '</th><th align="left">'.$langs->trans("blackWhiteListDesc").'</th>';
echo  '<th align="left"><input type="checkbox" name="blackWhiteList" value="1" ';
echo (($whiteList=='1')?'checked':'')."></th></tr>\n\t\t";
// Project
echo  '<tr class="pair"><th align="left">'.$langs->trans("blackWhiteListMode").'</th>';
echo '<th align="left">'.$langs->trans("blackWhiteListModeDesc").'</th>';
echo '<th align="left"><input type="radio" name="blackWhiteListMode" value="0" ';
echo ($whiteListMode=="0"?"checked":"").'> '.$langs->trans("modeWhiteList").'<br>';
echo '<input type="radio" name="blackWhiteListMode" value="1" ';
echo ($whiteListMode=="1"?"checked":"").'> '.$langs->trans("modeBlackList")."<br>";
echo '<input type="radio" name="blackWhiteListMode" value="2" ';
echo ($whiteListMode=="2"?"checked":"").'> '.$langs->trans("modeNone")."</th></tr>\n\t\t";
echo '</table><br>';


echo '<br>';

echo '</div>';

/*
 * INVOICE
 */
echo '<div id="Invoice" class="tabBar">';
print_titre($langs->trans("Invoice"));
echo '<table class="noborder" width="100%">'."\n\t\t";
echo '<tr class="liste_titre" width="100%" ><th width="200px">'.$langs->trans("Name").'</th><th>';
echo $langs->trans("Description").'</th><th width="100px">'.$langs->trans("Value")."</th></tr>\n\t\t";
//lines invoice method
echo  '<tr class="pair"><th align="left">'.$langs->trans("invoiceMethod");
echo '</th><th align="left">'.$langs->trans("invoiceMethodDesc").'</th>';
echo  '<th align="left"><input type="radio" name="invoiceMethod" value="task" ';
echo (($invoicemethod=='task')?'checked':'').">".$langs->trans("Task").'<br>';
echo '<input type="radio" name="invoiceMethod" value="user" ';
echo (($invoicemethod=='user')?'checked':'').">".$langs->trans("User").'<br>';
echo '<input type="radio" name="invoiceMethod" value="taskUser" ';
echo (($invoicemethod=='taskUser')?'checked':'').">".$langs->trans("taskUser").'<br>';
echo "</th></tr>\n\t\t";
//line invoice Service
echo  '<tr class="impair"><th align="left">'.$langs->trans("invoiceService");
echo '</th><th align="left">'.$langs->trans("invoiceServiceDesc").'</th>';
echo  '<th align="left">';
$addchoices=array('-999'=> $langs->trans('not2invoice'));
echo select_generic('product','rowid','invoiceService','ref','description',$invoiceservice,$separator=' - ',$sqlTailWhere='tosell=1 AND fk_product_type=1', $selectparam='',$addchoices);
echo "</th></tr>\n\t\t";
//line tasktime ==
echo  '<tr class="pair"><th align="left">'.$langs->trans("invoiceTaskTime");
echo '</th><th align="left">'.$langs->trans("invoiceTaskTimeDesc").'</th>';
echo  '<th align="left"><input type="radio" name="invoiceTaskTime" value="all" ';
echo (($invoicetasktime=='all')?'checked':'').">".$langs->trans("All").'<br>';
echo '<input type="radio" name="invoiceTaskTime" value="appoved" ';
echo (($invoicetasktime=='Approved')?'checked':'').">".$langs->trans("Approved").'<br>';
echo "</th></tr>\n\t\t";
//line show user
echo  '<tr class="impair"><th align="left">'.$langs->trans("invoiceShowUser");
echo '</th><th align="left">'.$langs->trans("invoiceShowUserDesc").'</th>';
echo  '<th align="left"><input type="checkbox" name="invoiceShowUser" value="1" ';
echo (($invoiceshowuser=='1')?'checked':'')."></th></tr>\n\t\t";
//line show task
echo  '<tr class="pair"><th align="left">'.$langs->trans("invoiceShowTask");
echo '</th><th align="left">'.$langs->trans("invoiceShowTaskDesc").'</th>';
echo  '<th align="left"><input type="checkbox" name="invoiceShowTask" value="1" ';
echo (($invoiceshowuser=='1')?'checked':'')."></th></tr>\n\t\t";
echo '</table><br>';

echo '</div>';//taskbar
/*
 * Other
 */
echo '<div id="Other" class="tabBar">';
print_titre($langs->trans("Dolibarr"));
echo '<table class="noborder" width="100%">'."\n\t\t";
echo '<tr class="liste_titre" width="100%" ><th width="200px">'.$langs->trans("Name").'</th><th>';
echo $langs->trans("Description").'</th><th width="100px">'.$langs->trans("Value")."</th></tr>\n\t\t";
echo  '<tr class="impair"><th align="left">'.$langs->trans("dropdownAjax");
echo '</th><th align="left">'.$langs->trans("dropdownAjaxDesc").'</th>';
echo  '<th align="left"><input type="checkbox" name="dropdownAjax" value="1" ';
echo (($dropdownAjax=='1')?'checked':'')."></th></tr>\n\t\t";

echo '</table><br>';
print_titre($langs->trans("Informations"));
echo $langs->trans('feebackDesc').' : <a href="mailto:pmpdelroix@gmail.com?subject=TimesheetFeedback"> Patrick Delcroix</a></br>';
print '<br><div><a>'.$langs->trans('reminderEmailProcess').'</a></div>';
echo '</div>';
echo '</div>'; // end fiche
echo '<input type="submit" class="butAction" value="'.$langs->trans('Save')."\">\n</from>";
echo '<br><br><br>';
echo '<script>document.getElementById("defaultOpen").click()</script>';

llxFooter();
?>