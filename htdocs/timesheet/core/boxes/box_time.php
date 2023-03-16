<?php
/* Copyright (C) 2021 delcroip <patrick@pmpd.eu>
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
 *        \file       htdocs/core/boxes/box_time.php
 *        \ingroup    factures
 *        \brief      Module de generation de l'affichage de la box factures
 */
include_once DOL_DOCUMENT_ROOT.'/core/boxes/modules_boxes.php';
$path = dirname(dirname(dirname(__FILE__)));
set_include_path($path);
require_once 'core/lib/timesheet.lib.php';
global $dolibarr_main_url_root_alt;
$res = 0;
/**
 * Class to manage the box to show last invoices
 */
class box_time extends ModeleBoxes
{
    public $boxcode = "timecount";
    public $boximg = "timesheet";
    public $boxlabel = "TimesheetDelta";
    public $depends = array("timesheet");
    public $db;
    public $param;
    public $info_box_head = array();
    public $info_box_contents = array();
    /**
     *  Load data into info_box_contents array to show array later.
     *
     *  @param        int                $max        Maximum number of records to load
     *  @return        void
     */
    public function loadBox($max = 5)
    {
        global $conf, $user, $langs, $db;
        $this->max = $max;
        $userid = is_object($user)?$user->id:$user;
        $text = $langs->trans('TimesheetDelta');
        $this->info_box_head = array(
                        'text' => $text,
                        'limit' => dol_strlen($text)
        );
        $admin = $user->admin || $user->rights->timesheet->timesheet->admin;
        if ($user->rights->timesheet->timesheet->user ||$admin) {
            $sqlweek = '';
            if ($this->db->type!='pgsql') {
                $sqlweek = " 
                with digit as (
                    select 0 as d union all 
                    select 1 as d union all select 2 as d union all select 3 as d union all
                    select 4 as d union all select 5 as d union all select 6 as d union all
                    select 7 as d union all select 8 as d union all select 9 as d        
                ),
                seq as (
                    select a.d + (10 * b.d) + (100 * c.d) + (1000 * d.d) as num
                    from digit a
                        cross join
                        digit b
                        cross join
                        digit c
                        cross join
                        digit d
                    order by 1        
                )
                SELECT SUM(pt.task_duration)/3600 as duration, 
                w.week, u.weeklyhours
                FROM (SELECT YEARWEEK(DATE_ADD(NOW(), INTERVAL - num WEEK)) as week 
                    FROM seq WHERE num <= ".getConf('TIMESHEET_OVERTIME_CHECK_WEEKS',4)."
                    AND num > 1 ) as w 
                LEFT JOIN ".MAIN_DB_PREFIX."projet_task_time pt ON YEARWEEK(pt.task_date) = w.week
                LEFT JOIN ".MAIN_DB_PREFIX."user u ON u.rowid =  ".$userid."
                
                WHERE pt.fk_user =  ".$userid." OR pt.fk_user is null
                GROUP BY w.week;";
            }else {
                // to be validated
                $sqlweek = "SELECT SUM(pt.task_duration)/3600 as duration, TO_CHAR(generate_series, 'YYYYWW') as week, u.weeklyhours 
                FROM generate_series(DATE_TRUNC('week', (now() - INTERVAL '".getConf('TIMESHEET_OVERTIME_CHECK_WEEKS',4)." week'))::timestamp, DATE_TRUNC('week', (now() - INTERVAL '1 WEEK' ))::timestamp, interval '1 week') 
                LEFT JOIN ".MAIN_DB_PREFIX."projet_task_time pt ON (generate_series = DATE_TRUNC('week',pt.task_date)) 
                LEFT JOIN ".MAIN_DB_PREFIX."user u on (pt.fk_user = ".$userid.") WHERE pt.fk_user = ".$userid." OR pt.fk_user is null 
                GROUP BY generate_series, u.weeklyhours;";
            }
            $result = $db->query($sqlweek);
            $delta = array();
            if ($result) {
                $p_duration = array();  // FIXME take worktime as delfaut nb day in week * nb hour per day
                $a_duration = array();
                $h_duration = array();
                $num = $db->num_rows($result);
                while($num>0)
                {
                    $obj = $db->fetch_object($result);
                    $p_duration[$obj->week] = isset($obj->weeklyhours) ? $obj->weeklyhours : getConf('TIMESHEET_DAY_DURATION',8) * 5;  // FIXME take worktime as delfaut nb day in week * nb hour per day
                    $a_duration[$obj->week] = isset($obj->duration) ? $obj->duration : 0;
                    $h_duration[$obj->week] = $this->getHolidayTime($obj->week, $userid, $obj->weeklyhours); 
                    $delta[$obj->week] = ($p_duration[$obj->week] - $h_duration[$obj->week] - $a_duration[$obj->week]);
                    $num--;
                }
                $i=0;
                // create the sums
                $max_delta = max($delta);
                $sum_delta = array_sum($delta);
                if ($max_delta > 0){
                    $this->info_box_contents[$i][] = array(
                        'td' => 'align = "left"',
                        'text' => $langs->trans('Max').': ',
                        'text2'=> getConf('TIMESHEET_OVERTIME_CHECK_WEEKS',4).' '.$langs->trans('Weeks'),
                        'asis' => 1,
                    );
                    $this->info_box_contents[$i][] = array(
                        'td' => 'align = "right"',
                        'text' => $max_delta,
                        'asis' => 1,
                    );
                    $i++;
                }
                if ($sum_delta){
                    $this->info_box_contents[$i][] = array(
                        'td' => 'align = "left"',
                        'text' => $langs->trans('Sum').': ',
                        'text2'=> getConf('TIMESHEET_OVERTIME_CHECK_WEEKS',4).' '.$langs->trans('Weeks'),
                        'asis' => 1,
                    );
                    $this->info_box_contents[$i][] = array(
                        'td' => 'align = "right"',
                        'text' => $sum_delta,
                        'asis' => 1,
                    );
                    $i++;
                }
                $db->free($result);
            } else {
                $this->info_box_contents[0][0] = array(
                    'td' => 'align = "left"',
                    'maxlength' => 500,
                    'text' =>($db->error().' sql='.$sqlweek),
                );
            }
        } else {
            $this->info_box_contents[0][0] = array(
                'td' => 'align = "left"',
                'text' => $langs->trans("ReadPermissionNotAllowed"),
            );
        }
    }
    // phpcs:disable Generic.CodeAnalysis.UnusedFunctionParameter.FoundInExtendedClassAfterLastUsed
    /**
     *  Method to show box
     *
     *  @param  array   $head       Array with properties of box title
     *  @param  array   $contents   Array with properties of box lines
     *  @param  INT   $nooutput   BLOCK OUTPUT
     *  @return void
     */
    public function showBox($head = null, $contents = null, $nooutput = 0)
    {
        Parent::showBox($this->info_box_head, $this->info_box_contents);
    }

    /**
     *  Method get the holiday duration 
     *
     *  @param  int   $yearweek      yearweek to use
     *  @param  INT   $userid   id of the user
     *  @param  int   $weeklyhours   number of 
     *  @return void
     */
    public function getHolidayTime($yearweek, $userid, $weeklyhours){ // FIXME should use weeklyhours to get the amout of hours per day. new custom fields nb day worked per week ?
    // FIXME should use weeklyhours to get the amout of hours per day. new custom fields nb day worked per week ?
        return 0;
    }
}
