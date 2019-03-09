<?php
/*
 * Copyright (C) 2014 delcroip <patrick@pmpd.eu>
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
 */
/*Class to handle a line of timesheet*/
        /* Status
         *   1) DraftCP
         *   2) ToReviewCP
         *   3) ApprovedCP
         *   4) CancelCP
         *   5) RefuseCP
         */
require_once DOL_DOCUMENT_ROOT.'/holiday/class/holiday.class.php';
class TimesheetHoliday extends Holiday
{
        private $holidaylist;
        private $holidayPresent;
    /** Object contructor
     *
     * @param database $db db object
     */
    public function __construct($db)
    {
            $this->db = $db;
            //$this->date_end = strtotime('now -1 year');
            //$this->date_start = strtotime('now -1 year');
    }
        /*public function initTimeSheet($weekWorkLoad, $taskTimeId)
    {
            $this->weekWorkLoad = $weekWorkLoad;
            $this->taskTimeId = $taskTimeId;
    }*/
    public function fetchUserWeek($userId, $datestart, $datestop)
    {
        $SQLfilter = " AND (cp.date_fin>='".$this->db->idate($datestart)."') ";
        $SQLfilter.= " AND (cp.date_debut<'".$this->db->idate($datestop)."')";
        $ret = $this->fetchByUser($userId, '', $SQLfilter);
        $this->holidayPresent = ($ret == 1);
        $this->holidaylist = array();
        //fixme fill the holiday list
        /*
         * id       --> id of the holiday task if any
         * prev     --> is it the holiday starting the day before
         * am       --> is the morning off
         * pm       --> is the afternoon off
         * next     --> is it the holiday continuing the day after
         * status   --> is the holiday submitted or approuved(none if id = 0)
         */
        $timespan = getDayInterval($datestart, $datestop);
        for($day = 0;$day<$timespan;$day++)
        {
                $curDay = strtotime(' + '.$day.' days', $datestart);
                $this->holidaylist[$day] = array('amId'=>'0', 'pmId'=>'0', 'prev'=>false, 'am'=>false, 'pm'=>false, 'next'=>false, 'amStatus'=>0, 'pmStatus'=>0);
                foreach($this->holiday as $record) {
                    if($record['date_debut']<=$curDay && $record['date_fin']>=$curDay) {
                     $prev = ($record['date_debut']<$curDay)?true:false;
                     $next = ($record['date_fin']>$curDay)?true:false;
                     $am = false;
                     $pm = false;
                     switch($record['halfday']) {
                         case -1://Holiday start the afteroon and end the afternnon -+++
                             $am = $prev;
                             $pm = true;
                             break;
                          case 1: //Holiday start the morning and end the morning    +++-
                             $am = true;
                             $pm = $next;
                             break;
                         case 2: //Holiday start the afternoon and end the morning  -++-
                             $am = $prev;
                             $pm = $next;
                            break;
                         case 0: //Holiday start the morning and end the afternnon  ++++
                         default:
                             $am = true;
                             $pm = true;
                             break;
                     }
                     // in case of 2 holiday present in the half day order 3, 2, 1, 5, 4
                     $oldSatus = $this->holidaylist[$day]['amStatus'];
                     $amOverride = ($this->holidaylist[$day]['amId'] == 0) || (($record['statut']>3 && $oldSatus >3 && $record['statut']>$oldSatus)||($record['statut']<=3 && ($record['statut']>$oldSatus || $oldSatus >3)));
                     $oldSatus = $this->holidaylist[$day]['amStatus'];
                     $pmOverride = ($this->holidaylist[$day]['pmId'] == 0) ||(($record['statut']>3 && $oldSatus >3 && $record['statut']>$oldSatus)||($record['statut']<=3 && ($record['statut']>$oldSatus || $oldSatus >3)));
                     if($am && $amOverride) {
                         $this->holidaylist[$day]['am'] = true;
                         $this->holidaylist[$day]['amId'] = $record['rowid'];
                         $this->holidaylist[$day]['prev'] = $prev;
                         $this->holidaylist[$day]['amStatus'] = $record['statut'];
                     }
                     if($pm && $pmOverride) {
                         $this->holidaylist[$day]['pm'] = true;
                         $this->holidaylist[$day]['pmId'] = $record['rowid'];
                         $this->holidaylist[$day]['next'] = $next;
                         $this->holidaylist[$day]['pmStatus'] = $record['statut'];
                     }
                     //$this->holidaylist[$dayOfWeek] = array('idam'=>$record['rowid'], 'idpm'=>$record['rowid'], 'prev'=>$prev, 'am'=>$am, 'pm'=>$pm, 'next'=>$next, 'status'=>$record['statut']);
                    }
                }
        }
    }
 /*
 * function to form a HTMLform line for this timesheet
 *
 *  @param    string               $headers             header to shows
 *  @param     int               $tsUserId           id of the user timesheet
 *  @return     string                                        HTML result containing the timesheet info
 */
    public function getHTMLFormLine($headers, $tsUserId)
    {
        global $langs;
        global $statusColor;
        global $conf;
        $timetype = $conf->global->TIMESHEET_TIME_TYPE;
        $dayshours = $conf->global->TIMESHEET_DAY_DURATION;
        if(!is_array($this->holidaylist))
           return '<tr>ERROR: wrong parameters for getFormLine'.empty($startDate).'|'.empty($stopDate).'|'.empty($headers).'</tr>';
        if(!$this->holidayPresent) // don't show the holiday line if nothing present
           return '';
        $html = "<tr id = 'holiday'>\n";
        $html .= '<th colspan = "'.count($headers).'" align = "right" > '.$langs->trans('Holiday').' </th>';
        $i = 0;
        foreach($this->holidaylist as $holiday) {
            $am = $holiday['am'];
            $pm = $holiday['pm'];
            $amId = $holiday['amId'];
            $pmId = $holiday['pmId'];
            $amValue = ($holiday['amStatus'] == 3);
            $pmValue = ($holiday['pmStatus'] == 3);
            $value = ($timetype == "hours")?date('H:i', mktime(0, 0, ($amValue+$pmValue)*$dayshours*1800)):($amValue+$pmValue)/2;
            $html .= '<th style = "margin: 0;padding: 0;">';
            if($conf->global->TIMESHEET_ADD_HOLIDAY_TIME == 1)$html .= '<input type = "hidden" class = "time4day['.$tsUserId.']['.$i.']"  value = "'.$value.'">';
            $html .= '<ul id = "holiday['.$i.']" class = "listHoliday" >';
                $html .= '<li id = "holiday['.$i.'][0]" class = "listItemHoliday" ><a ';
                if($am) {
                    $html .= 'href = "'.DOL_URL_ROOT.'/holiday/card.php?id='.$holiday['amId'].'"';
                    $amColor = ($am?'background-color:#'.$statusColor[$holiday['amStatus']].'':'');
                    $amClass = ($holiday['prev'])?'':' noPrevHoliday';
                    $amClass.= ($pm && $pmId == $amId)?'':' noNextHoliday';
                    $html .= ' class = "holiday'.$amClass.'" style = "'.$amColor.'">&nbsp;</a></li>';
                } else {
                    $html .= ' class = "holiday" >&nbsp;</a></li>';
                }
                $html .= '<li id = "holiday['.$i.'][1]" class = "listItemHoliday" ><a ';
                if($pm) {
                    $html .= 'href = "'.DOL_URL_ROOT.'/holiday/card.php?id='.$holiday['pmId'].'"';
                    $pmColor = ($pm?'background-color:#'.$statusColor[$holiday['pmStatus']].'':'');
                    $pmClass = ($am && $pmId == $amId)?'':' noPrevHoliday';
                    $pmClass.= ($holiday['next'])?'':' noNextHoliday';
                    $html .= ' class = "holiday'.$pmClass.'" style = "'.$pmColor.'">&nbsp;</a></li>';
                } else {
                    $html .= ' class = "holiday" >&nbsp;</a></li>';
                }
           // }
            $html .= "</ul></th>\n";
            $i++;
        }
        $html .= '</tr>';
        return $html;
    }
}
