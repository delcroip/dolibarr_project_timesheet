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

// Change this following line to use the correct relative path (../, ../../, etc)
$res=0;
if (! $res && file_exists("../main.inc.php")) $res=@include '../main.inc.php';					// to work if your module directory is into dolibarr root htdocs directory
if (! $res && file_exists("../../main.inc.php")) $res=@include '../../main.inc.php';			// to work if your module directory is into a subdir of root htdocs directory
if (! $res && file_exists("../../../main.inc.php")) $res=@include '../../../main.inc.php';     // Used on dev env only

if (! $res) die("Include of main fails");
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
$hidedraft=TIMESHEET_HIDE_DRAFT;
switch($action)
{
    case save:
        $timetype=GETPOST('timeType','alpha');
        $hoursperday=GETPOST('hoursperday','alpha');
        $hidedraft=GETPOST('hidedraft','alpha');
        $res=dolibarr_set_const($db, "TIMESHEET_TIME_TYPE", $timetype, 'chaine', 0, '', $conf->entity);
        if (! $res > 0) $error++;
        $res=dolibarr_set_const($db, "TIMESHEET_DAY_DURATION", $hoursperday, 'chaine', 0, '', $conf->entity);
        if (! $res > 0) $error++;
        $res=dolibarr_set_const($db, "TIMESHEET_HIDE_DRAFT", $hidedraft, 'chaine', 0, '', $conf->entity);
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
	

//permet d'afficher la structure dolibarr
llxHeader("",$langs->trans("timesheetSetupModule"));


$Form ='<form name="settings" action="?action=save" method="POST" > 
          <table class="noborder" width="100%">
            <tr class="liste_titre" >
               <th>Affichage du temps</th><th></th><th> </th>
            </tr>
            <tr>
                <th>
                    '.$langs->trans("hours").'
                <th>
                <th>
                    <input type="radio" name="timeType" value="hours" '.($timetype=="hours"?"checked":"").'>
                </th>
            </tr>
            <tr>
                <th>
                    '.$langs->trans("days").'
                <th>
                <th>
                    <input type="radio" name="timeType" value="days" '.($timetype=="days"?"checked":"").'>
                </th>
            </tr>
            <tr>
                <th>
                    '.$langs->trans("hoursperdays").'
                <th>
                <th>
                    <input type="text" name="hoursperday" value="'.$hoursperday.'"  >
                </th>
            </tr>
            <tr>
                <th>
                    '.$langs->trans("hidedraft").'
                <th>
                <th>
                    <input type="checkbox" name="hidedraft" value="1" '.(($hidedraft=='1')?'checked':'').' >
                </th>
            </tr>
            </table>
            <input type="submit" value="'.$langs->trans('Save').'">
            </from>';
$Form.='<script type="text/javascript" src="timesheet.js"></script>';
print $Form;
llxFooter();
?>