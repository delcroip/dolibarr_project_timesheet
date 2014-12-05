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

require '../../../main.inc.php';
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
switch($action)
{
    case save:
        $res=dolibarr_set_const($db, "TIMESHEET_TIME_TYPE", GETPOST('timeType','alpha'), 'chaine', 0, '', $conf->entity);
        if (! $res > 0) $error++;
        $res=dolibarr_set_const($db, "TIMESHEET_DAY_DURATION", GETPOST('hoursperday','alpha'), 'chaine', 0, '', $conf->entity);
        if (! $res > 0) $error++;
        break;
    default:
        break;
}
	
// error handling
    if (! $error)
    {
        setEventMessage($langs->trans("SetupSaved"));
    }
    else
    {
        setEventMessage($langs->trans("Error"),'errors');
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
                    <input type="radio" name="timeType" value="hours" checked>
                </th>
            </tr>
            <tr>
                <th>
                    '.$langs->trans("days").'
                <th>
                <th>
                    <input type="radio" name="timeType" value="days" >
                </th>
            </tr>
            <tr>
                <th>
                    '.$langs->trans("hoursperdays").'
                <th>
                <th>
                    <input type="text" name="hoursperday" value="8" >
                </th>
            </tr>
            </table>
            <input type="submit" value="'.$langs->trans('save').'">
            </from>';

print $Form;
?>