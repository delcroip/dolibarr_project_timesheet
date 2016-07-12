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
$opendays=str_split(TIMESHEET_OPEN_DAYS);
if(sizeof($opendays)!=8)$opendays=array('_','0','0','0','0','0','0','0');
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

        $headers=$showCompany?'Company':'';
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
        $opendays=array('_','0','0','0','0','0','0','0');
        foreach($_POST['opendays'] as $key => $day){
            $opendays[$key]=$day;
        }
        $res=dolibarr_set_const($db, "TIMESHEET_OPEN_DAYS", implode('',$opendays), 'chaine', 0, '', $conf->entity);
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


/* 
 *  VIEW
 *  */
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
        default:
            break;
    }
    
}
//permet d'afficher la structure dolibarr
$morejs=array("/timesheet/core/js/timesheet.js","/timesheet/core/js/jscolor.js");
llxHeader("",$langs->trans("timesheetSetup"),'','','','',$morejs,'',0,0);


$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print_fiche_titre($langs->trans("timesheetSetup"),$linkback,'title_setup');
print_titre($langs->trans("GeneralOption"));
$Form ='<form name="settings" action="?action=save" method="POST" >'."\n\t";
$Form .='<table class="noborder" width="100%">'."\n\t\t";
$Form .='<tr class="liste_titre" width="100%" ><th width="200px">'.$langs->trans("Name").'</th><th>';
$Form .=$langs->trans("Description").'</th><th width="100px">'.$langs->trans("Value")."</th></tr>\n\t\t";
// type time
$Form .='<tr class="pair"><th align="left">'.$langs->trans("timeType").'</th><th align="left">'.$langs->trans("timeTypeDesc").'</th>';
$Form .='<th align="left"><input type="radio" name="timeType" value="hours" ';
$Form .=($timetype=="hours"?"checked":"").'> '.$langs->trans("hours").'<br>';
$Form .='<input type="radio" name="timeType" value="days" ';
$Form .=($timetype=="days"?"checked":"").'> '.$langs->trans("days")."</th></tr>\n\t\t";
//hours perdays
$Form .='<tr class="impair"><th align="left">'.$langs->trans("hoursperdays");
$Form .='</th><th align="left">'.$langs->trans("hoursPerDaysDesc").'</th>';
$Form .='<th align="left"><input type="text" name="hoursperday" value="'.$hoursperday;
$Form .="\" size=\"4\" ></th></tr>\n\t\t";
//max hours perdays
$Form .='<tr class="pair"><th align="left">'.$langs->trans("maxhoursperdays"); //FIXTRAD
$Form .='</th><th align="left">'.$langs->trans("maxhoursPerDaysDesc").'</th>'; // FIXTRAD
$Form .='<th align="left"><input type="text" name="maxhoursperday" value="'.$maxhoursperday;
$Form .="\" size=\"4\" ></th></tr>\n\t\t";
// hide draft
$Form .= '<tr class="impair"><th align="left">'.$langs->trans("hidedraft");
$Form .='</th><th align="left">'.$langs->trans("hideDraftDesc").'</th>';
$Form .= '<th align="left"><input type="checkbox" name="hidedraft" value="1" ';
$Form .=(($hidedraft=='1')?'checked':'')."></th></tr>\n\t\t";
// hide ref
$Form .= '<tr class="pair"><th align="left">'.$langs->trans("hideref");
$Form .='</th><th align="left">'.$langs->trans("hideRefDesc").'</th>';
$Form .= '<th align="left"><input type="checkbox" name="hideref" value="1" ';
$Form .=(($hideref=='1')?'checked':'')."></th></tr>\n\t\t";

// hide zeros
$Form .= '<tr class="impair"><th align="left">'.$langs->trans("hidezeros");
$Form .='</th><th align="left">'.$langs->trans("hideZerosDesc").'</th>';
$Form .= '<th align="left"><input type="checkbox" name="hidezeros" value="1" ';
$Form .=(($hidezeros=='1')?'checked':'')."></th></tr>\n";


// add holiday time
$Form .= '<tr class="pair"><th align="left">'.$langs->trans("addholidaytime");
$Form .='</th><th align="left">'.$langs->trans("addholidaytimeDesc").'</th>';
$Form .= '<th align="left"><input type="checkbox" name="addholidaytime" value="1" ';
$Form .=(($addholidaytime=='1')?'checked':'')."></th></tr>\n";

// approval by week
$Form .= '<tr class="impair"><th align="left">'.$langs->trans("approvalbyweek");
$Form .='</th><th align="left">'.$langs->trans("approvalbyweekDesc").'</th>';
$Form .= '<th align="left"><input type="checkbox" name="approvalbyweek" value="1" ';
$Form .=(($approvalbyweek=='1')?'checked':'')."></th></tr>\n";
// max approval 
$Form .='<tr class="pair"><th align="left">'.$langs->trans("maxapproval"); //FIXTRAD
$Form .='</th><th align="left">'.$langs->trans("maxapprovalDesc").'</th>'; // FIXTRAD
$Form .='<th align="left"><input type="text" name="maxapproval" value="'.$maxApproval;
$Form .="\" size=\"4\" ></th></tr>\n\t\t";

$Form.="\t</table><br>\n";


print $Form;


print_titre($langs->trans("OpenDays")); //FIXTRAD
$Form ='<table class="noborder" width="100%">'."\n\t\t";
$Form .='<tr class="liste_titre" width="100%" ><th>'.$langs->trans("Monday").'</th><th>';
$Form .=$langs->trans("Tuesday").'</th><th>'.$langs->trans("Wednesday").'</th><th>';
$Form .=$langs->trans("Thursday").'</th><th>'.$langs->trans("Friday").'</th><th>';
$Form .=$langs->trans("Saturday").'</th><th>'.$langs->trans("Sunday").'</th>';
$Form .='<input type="hidden" name="opendays[0]" value="_">';
$Form .="</tr><tr>\n\t\t";

for ($i=1; $i<8;$i++){
$Form .= '<th width="14%" style="text-align:left"><input type="checkbox" name="opendays['.$i.']" value="1" ';
$Form .=(($opendays[$i]=='1')?'checked':'')."></th>\n\t\t";
        }
$Form .="</tr>\n\t</table><br>\n";
print $Form;






print_titre($langs->trans("ColumnToShow"));
$Form ='<table class="noborder" width="100%">'."\n\t\t";
$Form .='<tr class="liste_titre" width="100%" ><th width="200px">'.$langs->trans("Name").'</th><th>';
$Form .=$langs->trans("Description").'</th><th width="100px">'.$langs->trans("Value")."</th></tr>\n\t\t";
// Project
$Form .= '<tr class="impair"><th align="left">'.$langs->trans("Project");
$Form .='</th><th align="left">'.$langs->trans("ProjectColDesc").'</th>';
$Form .= '<th align="left"><input type="checkbox" name="showProject" value="1" ';
$Form .=(($showProject=='1')?'checked':'')."></th></tr>\n\t\t";
// task parent
$Form .= '<tr class="pair"><th align="left">'.$langs->trans("TaskParent");
$Form .='</th><th align="left">'.$langs->trans("TaskParentColDesc").'</th>';
$Form .= '<th align="left"><input type="checkbox" name="showTaskParent" value="1" ';
$Form .=(($showTaskParent=='1')?'checked':'')."></th></tr>\n\t\t";
// task
$Form .= '<tr class="impair"><th align="left">'.$langs->trans("Tasks");
$Form .='</th><th align="left">'.$langs->trans("TasksColDesc").'</th>';
$Form .= '<th align="left"><input type="checkbox" name="showTasks" value="1" ';
$Form .=(($showTasks=='1')?'checked':'')."></th></tr>\n\t\t";
// date de debut
$Form .= '<tr class="pair"><th align="left">'.$langs->trans("DateStart");
$Form .='</th><th align="left">'.$langs->trans("DateStartColDesc").'</th>';
$Form .= '<th align="left"><input type="checkbox" name="showDateStart" value="1" ';
$Form .=(($showDateStart=='1')?'checked':'')."></th></tr>\n\t\t";
// date de fin
$Form .= '<tr class="impair"><th align="left">'.$langs->trans("DateEnd");
$Form .='</th><th align="left">'.$langs->trans("DateEndColDesc").'</th>';
$Form .= '<th align="left"><input type="checkbox" name="showDateEnd" value="1" ';
$Form .=(($showDateEnd=='1')?'checked':'')."></th></tr>\n\t\t";
// Progres
$Form .= '<tr class="pair"><th align="left">'.$langs->trans("Progress");
$Form .='</th><th align="left">'.$langs->trans("ProgressColDesc").'</th>';
$Form .= '<th align="left"><input type="checkbox" name="showProgress" value="1" ';
$Form .=(($showProgress=='1')?'checked':'')."></th></tr>\n\t\t";
// Company
$Form .= '<tr class="impair"><th align="left">'.$langs->trans("Company");
$Form .='</th><th align="left">'.$langs->trans("CompanyColDesc").'</th>';
$Form .= '<th align="left"><input type="checkbox" name="showCompany" value="1" ';
$Form .=(($showCompany=='1')?'checked':'')."></th></tr>\n\t\t";
/*
// custom FIXME
$Form .= '<tr class="pair"><th align="left">'.$langs->trans("CustomCol");
$Form .='</th><th align="left">'.$langs->trans("CustomColDesc").'</th>';
$Form .= '<th align="left"><input type="checkbox" name="showCustomCol" value="1" ';
$Form .=(($showCustomCol=='1')?'checked':'')."</th></tr>\n\t\t";
*/
$Form .='</table>';
print $Form.'<br>';

//Color
print_titre($langs->trans("Color"));
$Form ='<table class="noborder" width="100%">'."\n\t\t";
$Form .='<tr class="liste_titre" width="100%" ><th width="200px">'.$langs->trans("Name").'</th><th>';
$Form .=$langs->trans("Description").'</th><th width="100px">'.$langs->trans("Value")."</th></tr>\n\t\t";
// color draft
$Form .= '<tr class="impair"><th align="left">'.$langs->trans("draft");
$Form .='</th><th align="left">'.$langs->trans("draftColorDesc").'</th>';
$Form .= '<th align="left"><input name="draftColor" class="jscolor" value="';
$Form .=$draftColor."\"></th></tr>\n\t\t";
// color submitted
$Form .= '<tr class="pair"><th align="left">'.$langs->trans("submitted");
$Form .='</th><th align="left">'.$langs->trans("submittedColorDesc").'</th>';
$Form .= '<th align="left"><input name="submittedColor" class="jscolor" value="';
$Form .=$submittedColor."\"></th></tr>\n\t\t";
// color approved
$Form .= '<tr class="impair"><th align="left">'.$langs->trans("approved");
$Form .='</th><th align="left">'.$langs->trans("approvedColorDesc").'</th>';
$Form .= '<th align="left"><input name="approvedColor" class="jscolor" value="';
$Form .=$approvedColor."\"></th></tr>\n\t\t";
// color cancelled
$Form .= '<tr class="pair"><th align="left">'.$langs->trans("cancelled");
$Form .='</th><th align="left">'.$langs->trans("cancelledColorDesc").'</th>';
$Form .= '<th align="left"><input name="cancelledColor" class="jscolor" value="';
$Form .=$cancelledColor."\"></th></tr>\n\t\t";
// color rejected
$Form .= '<tr class="impair"><th align="left">'.$langs->trans("rejected");
$Form .='</th><th align="left">'.$langs->trans("rejectedColorDesc").'</th>';
$Form .= '<th align="left"><input name="rejectedColor" class="jscolor" value="';
$Form .=$rejectedColor."\"></th></tr>\n\t\t";


$Form .='</table><br>';

print $Form.'<br>';


//whitelist mode
print_titre($langs->trans("WhiteList"));
$Form ='<table class="noborder" width="100%">'."\n\t\t";
$Form .='<tr class="liste_titre" width="100%" ><th width="200px">'.$langs->trans("Name").'</th><th>';
$Form .=$langs->trans("Description").'</th><th width="100px">'.$langs->trans("Value")."</th></tr>\n\t\t";
// whitelist on/off
$Form .= '<tr class="impair"><th align="left">'.$langs->trans("blackWhiteList");
$Form .='</th><th align="left">'.$langs->trans("blackWhiteListDesc").'</th>';
$Form .= '<th align="left"><input type="checkbox" name="blackWhiteList" value="1" ';
$Form .=(($whiteList=='1')?'checked':'')."></th></tr>\n\t\t";
// Project
$Form .= '<tr class="pair"><th align="left">'.$langs->trans("blackWhiteListMode").'</th>';
$Form .='<th align="left">'.$langs->trans("blackWhiteListModeDesc").'</th>';
$Form .='<th align="left"><input type="radio" name="blackWhiteListMode" value="0" ';
$Form .=($whiteListMode=="0"?"checked":"").'> '.$langs->trans("modeWhiteList").'<br>';
$Form .='<input type="radio" name="blackWhiteListMode" value="1" ';
$Form .=($whiteListMode=="1"?"checked":"").'> '.$langs->trans("modeBlackList")."<br>";
$Form .='<input type="radio" name="blackWhiteListMode" value="2" ';
$Form .=($whiteListMode=="2"?"checked":"").'> '.$langs->trans("modeNone")."</th></tr>\n\t\t";
$Form .='</table><br>';


print $Form.'<br>';


// Ajax on/off

print_titre($langs->trans("Dolibarr"));
$Form ='<table class="noborder" width="100%">'."\n\t\t";
$Form .='<tr class="liste_titre" width="100%" ><th width="200px">'.$langs->trans("Name").'</th><th>';
$Form .=$langs->trans("Description").'</th><th width="100px">'.$langs->trans("Value")."</th></tr>\n\t\t";
$Form .= '<tr class="impair"><th align="left">'.$langs->trans("dropdownAjax");
$Form .='</th><th align="left">'.$langs->trans("dropdownAjaxDesc").'</th>';
$Form .= '<th align="left"><input type="checkbox" name="dropdownAjax" value="1" ';
$Form .=(($dropdownAjax=='1')?'checked':'')."></th></tr>\n\t\t";

$Form .='</table><br>';


$Form .='<input type="submit" class="butAction" value="'.$langs->trans('Save')."\">\n</from>";
print $Form.'<br><br><br>';
print_titre($langs->trans("Informations"));
print '<br><div><a>'.$langs->trans('reminderEmailProcess').'</a></div>';

llxFooter();
?>