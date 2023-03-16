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
//require_once DOL_DOCUMENT_ROOT.'/holiday/class/holiday.class.php';
require_once DOL_DOCUMENT_ROOT."/core/class/commonobject.class.php";
require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
class TimesheetPublicHolidays extends CommonObject
{
        public $holidaylist;
        public $holidayPresent;
        public $userCountryId;
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



    
    /**  Function to get the user contry code
     * @param int $userId
     * @return int ok/ko
     * TODO: establishement not yet taken into account because the user are not yet linked to it
     ***/
    public function getUsercountryId($userId){
        global $mysoc;
        if(!is_numeric($userId))return -1;
        //fetch the user object
        $countryId = '';
        $user = new User($this->db, $userId);
        if($user->country_id && $user->country_id != ''){
            $this->userCountryId = $user->country_id;
        }else{
            $this->userCountryId = $mysoc->country_id;
        }
        if($countryId != '') return 1;
        else return -1;
    }

    /** Function to get the user public holiday for a given period
     * Support only 1 year - 1 day range max
     * @param int $userid usaer id
     * @param int|timestamp $datestart begining of the period
     * @param int|timestamp $datestop end of the period
     * @return error/success (object updated or not)
     * 
     */
    public function fetchUserWeek($userId, $datestart, $datestop)
    {
        if(!is_object($this->userCountryId)){
            if(!$this->getUsercountryId($userId)){
                //cannot find the contry code --> exit
                return -1;
            }
        }
        // check it the period overlap on 2 years
        $yearStart = date('Y', $datestart);
        $yearStop = date('Y', $datestop);

        //don't take the next day if the hour is 0
        if(date('H', $datestop) == 0){
            $datestop = $datestop -1;
        }
        
        
        $sameYear = $yearStart == $yearStop;
        $this->holidayPresent = false;

        if ($this->db->type == 'pgsql') {
            $sql = 'SELECT code, dayrule, year, month, day  FROM '.MAIN_DB_PREFIX.'c_hrm_public_holiday';
            $sql .= ' WHERE (fk_country=0 or fk_country='.$this->userCountryId;
            $sql .= ') AND active=1';
            // if year is not 0
            $sql .= ' AND (( year != 0 AND TO_DATE';
            $sql .= '(CONCAT(LPAD(CAST(year as TEXT),4,\'0\'), LPAD(CAST(month as TEXT),2,\'0\'), LPAD(CAST(day as TEXT),2,\'0\')),\'YYYYMMDD\')';
            $sql .= ' BETWEEN \''.$this->db->idate($datestart).'\' AND \''.$this->db->idate($datestop).'\')';
            // if year is 0
            $sql .= ' OR ( year = 0 AND TO_DATE(CONCAT('.$yearStart;
            $sql .= ', LPAD(CAST(month as TEXT),2,\'0\'), LPAD(CAST(day as TEXT),2,\'0\')),\'YYYYMMDD\')';
            $sql .= ' BETWEEN \''.$this->db->idate($datestart).'\' AND \''.$this->db->idate($datestop).'\')';
            if (!$sameYear){
                $sql .= ' OR ( year = 0 AND TO_DATE(CONCAT('.$yearStop;
                $sql .= ', LPAD(CAST(month as TEXT),2,\'0\'), LPAD(CAST(day as TEXT),2,\'0\')),\'YYYYMMDD\')';
                $sql .= ' BETWEEN \''.$this->db->idate($datestart).'\' AND \''.$this->db->idate($datestop).'\')';
            }
            $sql .= ')';
        }else{
            $sql = 'SELECT code, dayrule, year, month, day  FROM '.MAIN_DB_PREFIX.'c_hrm_public_holiday';
            $sql .= ' WHERE (fk_country=0 or fk_country='.$this->userCountryId;
            $sql .= ') AND active=1';
            // if year is not 0
            $sql .= ' AND (( year != 0 AND STR_TO_DATE';
            $sql .= '(CONCAT(LPAD(year,4,0), LPAD(month,2,0), LPAD(day,2,0)),\'%Y%m%d\')';
            $sql .= ' BETWEEN \''.$this->db->idate($datestart).'\' AND \''.$this->db->idate($datestop).'\')';
            // if year is 0
            $sql .= ' OR ( year = 0 AND STR_TO_DATE(CONCAT('.$yearStart;
            $sql .= ', LPAD(month,2,0), LPAD(day,2,0)),\'%Y%m%d\')';
            $sql .= ' BETWEEN \''.$this->db->idate($datestart).'\' AND \''.$this->db->idate($datestop).'\')';
            if (!$sameYear){
                $sql .= ' OR ( year = 0 AND STR_TO_DATE(CONCAT('.$yearStop;
                $sql .= ', LPAD(month,2,0), LPAD(day,2,0)),\'%Y%m%d\')';
                $sql .= ' BETWEEN \''.$this->db->idate($datestart).'\' AND \''.$this->db->idate($datestop).'\')';
            }
            $sql .= ')';
        }

        dol_syslog(__METHOD__, LOG_DEBUG);
        $resql = $this->db->query($sql);
        if ($resql) {
            $num = $this->db->num_rows($resql);
            $timespan = getDayInterval($datestart, $datestop);
            if($num){
                for($day = 0;$day<$timespan;$day++){
                    $this->holidaylist[$day] = array('dayoff' => false ,'desc' => '', 'code' => '',
                     'prev' => false, 'next' =>  false );
                }
            }
            $prev = false;
            $next = false;
            for ($i=0; $i < $num; $i++) {
                $obj = $this->db->fetch_object($resql);
                $year =  $obj->year;
                $month = $obj->month;
                $day = $obj->day;
                $code = $obj->code;
                $desc = $obj->dayrule;
                $curDate = null;
                if ($year > 0 ){
                    $curDate = strtotime($year.'-'.$month.'-'.$day);
                }else if ($sameYear){
                    $curDate = strtotime($yearStart.'-'.$month.'-'.$day);
                }else{
                    $curDate = strtotime($yearStart.'-'.$month.'-'.$day);
                    if ($curDate < $datestart){
                        $curDate = strtotime($yearStop.'-'.$month.'-'.$day);
                    }
                }
                $day = date('j',$curDate-$datestart) -1;
                $this->holidaylist[$day] = array('dayoff' =>true ,'desc' => $desc, 'code' => $code,
                    'prev' => false, 'next' =>  false );
                $this->holidayPresent = true;
                if($day > 0){
                    $this->holidaylist[$day-1]['prev'] = true;
                }
                if($day+1 < $timespan){
                    $this->holidaylist[$day+1]['prev'] = true;
                }
            }
            
            $this->db->free($resql);
        } else {
            $this->error = "Error ".$this->db->lasterror();
            return -1;
        }
    }
 /**
 * function to form a HTMLform line for this timesheet
 *
 *  @param  array   $headers    header to shows
 *  @param  string  $tsUserId   id that will be used for the total
 *  @param  int $UserId id of the user timesheet
 *  @return string  HTML result containing the timesheet info
 */
    public function getHTMLFormLine($headers, $tsUserId, $userId)
    {
        global $langs;
        global $statusColor;
        global $conf;
        $timetype = getConf('TIMESHEET_TIME_TYPE','hours');
        $dayshours = getConf('TIMESHEET_DAY_DURATION',8);
        if (!is_array($this->holidaylist) || (!$this->holidayPresent)) // don't show the holiday line if nothing present
           return '';
        $html = "<tr id = 'publicholiday'>\n";
        $nbHeader = count($headers);
        $html .= '<th colspan = "'.($nbHeader == 1 ? 2 : $nbHeader).'" align = "right" > '.$langs->trans('PublicHoliday').' </th>';
        $i = 0;
        foreach ($this->holidaylist as $day => $holiday) {
            $value = ($timetype == "hours")?date('H:i', 
                mktime(0, 0, $holiday['dayoff']*$dayshours*3600)):$holiday['dayoff'];
            $html .= '<th style = "margin: 0;padding: 0;">';
            $class = "column_${tsUserId}_${day} user_${userId} line_${tsUserId}_publicholiday";
            if (getConf('TIMESHEET_ADD_PUBLICHOLIDAY_TIME') == 1){
                $html .= '<input type = "hidden" class = "'.$class.'"  value = "'.$value.'">';
            }
            $html .= '<a id = "holiday['.$i.'][0]" ';
            if ($holiday['dayoff']) {
                $html .= 'title="'.$holiday['code'].'-'.$holiday['desc'].'"';
                $Color = 'display: block;background-color:#'.$statusColor[APPROVED].'';
                $Class = ($holiday['prev'])?'':' noPrevHoliday';
                $Class .= ($holiday['next'])?'':' noNextHoliday';
                $html .= ' class = "holiday'.$Class.'" style = "'.$Color.'">&nbsp;</a></li>';
            } else {
                $html .= ' class = "holiday" >&nbsp;</a>';
            }
            $html .= "</th>\n";
            $i++;
        }
        $html .= '</tr>';
        return $html;
    }
}
