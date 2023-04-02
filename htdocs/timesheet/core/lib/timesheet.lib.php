<?php
/*
 * Copyright (C) 2014           Patrick DELCROIX     <patrick@pmpd.eu>
 *
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
//const STATUS = [
require_once 'generic.lib.php';

//Define("NULL", 0);
Define("DRAFT", 1);
Define("SUBMITTED", 2);
Define("APPROVED", 3);
Define("CANCELLED", 4);
Define("REJECTED", 5);
Define("CHALLENGED", 6);
Define("INVOICED", 7);
Define("UNDERAPPROVAL", 8);
Define("PLANNED", 9);
Define("STATUSMAX", 10);
Define("VALUE", 100);
Define("FROZEN", 101);
//APPFLOW
//const LINKED_ITEM = [
Define("USER", 0);
Define("TEAM", 1);
Define("PROJECT", 2);
Define("CUSTOMER", 3);
Define("SUPPLIER", 4);
Define("OTHER", 5);
Define("ALL", 6);
Define("ADMIN", 7);
Define("ROLEMAX", 8);
// back ground colors
$statusColor = array(
    DRAFT=>getConf('TIMESHEET_COL_DRAFT'),
    VALUE=>getConf('TIMESHEET_COL_VALUE'),
    FROZEN=>getConf('TIMESHEET_COL_FROZEN'),
    SUBMITTED=>getConf('TIMESHEET_COL_SUBMITTED'),
    APPROVED=>getConf('TIMESHEET_COL_APPROVED'),
    CANCELLED=>getConf('TIMESHEET_COL_CANCELLED'),
    REJECTED=>getConf('TIMESHEET_COL_REJECTED'),
    CHALLENGED=>getConf('TIMESHEET_COL_REJECTED'),
    INVOICED=>getConf('TIMESHEET_COL_APPROVED'),
    UNDERAPPROVAL=>getConf('TIMESHEET_COL_SUBMITTED'),
    PLANNED=>getConf('TIMESHEET_COL_DRAFT'));
// attendance
define('EVENT_AUTO_START', -2);
define('EVENT_HEARTBEAT', 1);
define('EVENT_START', 2);
define('EVENT_STOP', 3);
define('EVENT_AUTO_STOP', 4);

// number of second in a day, used to make the code readable
define('SECINDAY', 86400);


// for display trads
global $langs;
$roles = array(0 => 'user', 1 => 'team', 2 => 'project', 3 => 'customer', 4 => 'supplier', 5 => 'other');
$statusA = array(0=> $langs->trans('null'), 1 => $langs->trans('draft'), 2=>$langs->trans('submitted'),
    3=>$langs->trans('approved'), 4=>$langs->trans('cancelled'), 5=>$langs->trans('rejected'),
    6=>$langs->trans('challenged'), 7=>$langs->trans('invoiced'), 8=>$langs->trans('underapproval'),
    9=>$langs->trans('planned'));
$apflows = str_split(getConf('TIMESHEET_APPROVAL_FLOWS',''));



//global $db;
// to get the whitlist object
//require_once 'class/TimesheetFavourite.class.php';
//require_once 'class/TimesheetUserTasks.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';

 /*
 * function to genegate list of the subordinate ID
 *
  *  @param    object            $db             database objet
 *  @param    array(int)/int        $id                    array of manager id
 *  @param     int               $depth          depth of the recursivity
 *  @param    array(int)/int                $ecludeduserid  exection that shouldn't be part of the result(to avoid recursive loop)
 *  @param     string               $role           team will look for organigram subordinate, project for project subordinate
 *  @param     int               $entity         entity where to look for
  *  @return     array(userId)                                                  html code
 */
function getSubordinates($db, $userid, $depth = 5, $ecludeduserid = array(), $role = TEAM, $entity = '1')
{
    //FIX ME handle multicompany
    $list = array();
    if ($userid == "") {
        return array();
    }
    if ($role == TEAM || $role == ALL){
      global $user;
      $list = $user->getAllChildIds();

    }
    if ($role == PROJECT || $role == ALL || $role == ADMIN){
        $sql[0] = 'SELECT DISTINCT fk_socpeople as userid FROM '.MAIN_DB_PREFIX.'element_contact';
        if ($role != ADMIN){
            $sql[0] .= ' WHERE element_id in (SELECT element_id';
            $sql[0] .= ' FROM '.MAIN_DB_PREFIX.'element_contact AS ec';
            $sql[0] .= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_type_contact as ctc ON ctc.rowid = ec.fk_c_type_contact';
            if($role == PROJECT)$sql[0] .= ' WHERE ctc.active = \'1\' AND ctc.element in (\'project\', \'project_task\') AND  (ctc.code LIKE \'%LEADER%\' OR ctc.code LIKE \'%BILLING%\' OR ctc.code LIKE \'%EXECUTIVE%\')';
            // only billing can see all
            if($role == ALL)$sql[0] .= ' WHERE ctc.active = \'1\' AND ctc.element in (\'project\', \'project_task\') AND  (ctc.code LIKE \'%BILLING%\')';
            $sql[0] .= ' AND fk_socpeople in (';
            $sql[2] = ')) AND fk_socpeople not in (';
            $sql[4] = ')';
            $idlist = '';
            if (is_array($userid)) {
                $ecludeduserid = array_merge($userid, $ecludeduserid);
                $idlist = implode(", ", $userid);
            } else{
                $ecludeduserid[] = $userid;
                $idlist = $userid;
            }
            $sql[1] = $idlist;
            $idlist = '';
            if (is_array($ecludeduserid)) {
                $idlist = implode(", ", $ecludeduserid);
            } elseif (!empty($ecludeduserid)) {
                $idlist = $ecludeduserid;
            }
            $sql[3] = $idlist;
        }
        ksort($sql, SORT_NUMERIC);
        $sqlused = implode($sql);
        dol_syslog('form::get_subordinate role='.$role, LOG_DEBUG);

        $resql = $db->query($sqlused);
        if ($resql) {
            $i = 0;
            $num = $db->num_rows($resql);
            while($i<$num)
            {
                $obj = $db->fetch_object($resql);
                if ($obj) {
                    $list[] = $obj->userid;
                }
                $i++;
            }
            if (count($list)>0 && $depth>1) {
                //this will get the same result plus the subordinate of the subordinate
                $result = getSubordinates($db, $list, $depth-1, $ecludeduserid, $role, $entity);
                if (is_array($result)) {
                    $list = array_merge($list, $result);
                }
            }
            if (is_array($userid)) {
                $list = array_merge($list, $userid);
            } else {
                //$list[] = $userid;
            }
        } else {
            dol_print_error($db);
            $list = array();
        }
    }
    //$select .= "\n";
    return array_unique($list);
}
  /*
 * function to genegate list of the task that can have approval pending
 *
  *  @param    object            $db             database objet
 *  @param    array(int)/int        $id                    array of manager id
 *  @param     int               $depth          depth of the recursivity
 *  @param    array(int)/int                $ecludeduserid  exection that shouldn't be part of the result(to avoid recursive loop)
 *  @param     string               $role           team will look for organigram subordinate, project for project subordinate
 *  @param     int               $entity         entity where to look for
  *  @return     string                                                   html code
 */
function getTasks($db, $userid, $role = 'project')
{
    $sql = 'SELECT tk.fk_projet as project, tk.rowid as task';
    $sql .= ' FROM '.MAIN_DB_PREFIX.'projet_task as tk';
    $sql .= ' JOIN '.MAIN_DB_PREFIX.'element_contact AS ec ON  tk.fk_projet = ec.element_id ';
    $sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_type_contact as ctc ON ctc.rowid = ec.fk_c_type_contact';
    $sql .= ' WHERE ctc.element in (\''.$role.'\') AND ctc.active = \'1\' AND ctc.code LIKE \'%LEADER%\' ';
    $sql .= ' AND fk_socpeople = \''.$userid.'\'';
    $sql .= ' UNION ';
    $sql .= ' SELECT tk.fk_projet as project, tk.rowid as task';
    $sql .= ' FROM '.MAIN_DB_PREFIX.'projet_task as tk';
    $sql .= ' JOIN '.MAIN_DB_PREFIX.'element_contact as ec on(tk.rowid = element_id)';
    $sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_type_contact as ctc ON ctc.rowid = ec.fk_c_type_contact';
    $sql .= ' WHERE ctc.element in (\'project_task\') AND ctc.active = \'1\' AND ctc.code LIKE \'%EXECUTIVE%\' ';
    $sql .= ' AND ec.fk_socpeople = \''.$userid.'\'';
   dol_syslog('timesheet::report::projectList ', LOG_DEBUG);
   //launch the sql querry
   $resql = $db->query($sql);
   $numTask = 0;
   $taskList = array();
   if ($resql) {
           $numTask = $db->num_rows($resql);
           $i = 0;
           // Loop on each record found, so each couple (project id, task id)
           while($i < $numTask)
           {
                   $error = 0;
                   $obj = $db->fetch_object($resql);
                   $taskList[$obj->task] = $obj->project;
                   $i++;
           }
           $db->free($resql);
   } else {
           dol_print_error($db);
   }
   return $taskList;
}
 /*
 * function to get the name from a list of ID
 *
  *  @param    object            $db             database objet
 *  @param    array(int)/int        $userids        array of manager id
  *  @return  array(int => String)                                array(ID => userName)
 */
function getUsersName($userids)
{
    global $db;
    if ($userids == "") {
        return array();
    }
    $sql = "SELECT usr.rowid, CONCAT(usr.firstname, ' ', usr.lastname) as username, usr.lastname FROM ".MAIN_DB_PREFIX.'user AS usr WHERE';
    if (is_array($userids)) {
            $sql .= ' usr.rowid in ('.implode(', ', $userids).')';
    } else{
        $sql .= ' usr.rowid = '.$userids;
    }
        /*
        $nbIds = (is_array($userids))?count($userids)-1:0;

        for($i = 0;$i<$nbIds-1 ;$i++)
        {
                $sql."'".$userids[$i]."', ";
        }
        $sql .= ((is_array($userids))?("'".$userids[$nbIds-1]."'"):('"'.$userids.'"')).')';
        */
    $sql .= ' ORDER BY usr.lastname ASC';
    dol_syslog('form::get_userName '.$sql, LOG_DEBUG);
    $list = array();
    $resql = $db->query($sql);
    if ($resql) {
        $i = 0;
        $num = $db->num_rows($resql);
        while($i<$num)
        {
            $obj = $db->fetch_object($resql);
            if ($obj) {
                $list[$obj->rowid] = $obj->username;
            }
            $i++;
        }
    } else {
        dol_print_error($db);
        $list = array();
    }
      //$select .= "\n";
    return $list;
}

if (!is_callable("GETPOSTISSET")) {
/**
 * Return true if we are in a context of submitting a parameter
 *
 * @param        string        $paramname                Name or parameter to test
 * @return        boolean                                        True if we have just submit a POST or GET request with the parameter provided(even if param is empty)
 */
    function GETPOSTISSET($paramname)
    {
            return(isset($_POST[$paramname]) || isset($_GET[$paramname]));
    }
}





if (!is_callable("setEventMessages")) {
    // function from /htdocs/core/lib/function.lib.php in Dolibarr 3.8
    function setEventMessages($mesg, $mesgs, $style = 'mesgs')
    {
            if (! in_array((string) $style, array('mesgs', 'warnings', 'errors'))) dol_print_error('', 'Bad parameter for setEventMessage');
            if (empty($mesgs)) setEventMessage($mesg, $style);
            else {
                    if (! empty($mesg) && ! in_array($mesg, $mesgs)) setEventMessage($mesg, $style);        // Add message string if not already into array
                    setEventMessage($mesgs, $style);
            }
    }
}
/*
 * function retrive the dolibarr eventMessages ans send then in a XML format
 *
 *  @return     string                                         XML
 */
function getEventMessagesXML()
{
    $xml = '';
       // Show mesgs
   if (isset($_SESSION['dol_events']['mesgs'])) {
     $xml .= getEventMessageXML($_SESSION['dol_events']['mesgs']);
     unset($_SESSION['dol_events']['mesgs']);
   }
   // Show errors
   if (isset($_SESSION['dol_events']['errors'])) {
     $xml .= getEventMessageXML($_SESSION['dol_events']['errors'], 'error');
     unset($_SESSION['dol_events']['errors']);
   }
   // Show warnings
   if (isset($_SESSION['dol_events']['warnings'])) {
     $xml .= getEventMessageXML($_SESSION['dol_events']['warnings'], 'warning');
     unset($_SESSION['dol_events']['warnings']);
   }
   return $xml;
}
/*
 * function convert the dolibarr eventMessage in a XML format
 *
 *  @param    string               $message           message to show
 *  @param    string               $style            style of the message error | ok | warning
 *  @return     string                                         XML
 */
function getEventMessageXML($messages, $style = 'ok')
{
    $msg = '';
    if (is_array($messages)) {
        $count = count($messages);
        foreach ($messages as $message) {
            $msg .= $message;
            if ($count>1)$msg .= "<br/>";
            $count--;
        }
    } else
        $msg = $messages;
    $ret = '';
    if ($msg!="") {
        if ($style!='error' && $style!='warning')$style = 'ok';
        $ret = "<eventMessage style = \"{$style}\"> {$msg}</eventMessage>";
    }
    return $ret;
}
/*
 * function to make the StartDate
 *
  *  @param    int              $day                    day of the date
 *  @param    int               $month                   month of the date
 *  @param    int               $year                    year of the date
 *  @param    string            $date           date on a string format
 *  @param    int               $prevNext       -1 for previous period, +1 for next period
 *  @return     string
 */
function getStartDate($datetime, $prevNext = 0)
{
    global $conf;
    // use the day, month, year value
    $startDate = null;
        // split week of the current week
  /* $prefix = 'this';
   if ($prevNext == 1) {
        $prefix = 'next';
   } elseif ($prevNext == -1) {
       $prefix = 'previous';
   }
 */
    /**************************
     * calculate the start date form php date
     ***************************/
    switch(getConf('TIMESHEET_TIME_SPAN','week')) {
        case 'month': //by Month
        //     $startDate = strtotime('first day of '.$prefix.' month midnight', $datetime);
        //     break;
            if ($prevNext == 1) {
                $startDate = strtotime('first day of next month midnight', $datetime);
            } elseif ($prevNext == 0) {
                $startDate = strtotime('first day of this month midnight', $datetime);
            } elseif ($prevNext == -1) {
                $startDate = strtotime('first day of previous month midnight', $datetime);
            }
            break;
        case 'week': //by user
                    //     $startDate = strtotime('first day of '.$prefix.' month midnight', $datetime);
        //     break;
            if ($prevNext == 1) {
                $startDate = strtotime('monday next week midnight', $datetime);
            } elseif ($prevNext == 0) {
                $startDate = strtotime('monday this week midnight', $datetime);
            } elseif ($prevNext == -1) {
                $startDate = strtotime('monday previous week midnight', $datetime);
            }
            break;
        case 'splitedWeek': //by week
        default:
            if ($prevNext == 1) {
                $startDateMonth = strtotime('first day of next month  midnight', $datetime);
                $startDateWeek = strtotime('monday next week midnight', $datetime);
                $startDate = MIN($startDateMonth, $startDateWeek);
            } elseif ($prevNext == 0) {
                $startDateMonth = strtotime('first day of this month midnight', $datetime);
                $startDateWeek = strtotime('monday this week  midnight', $datetime);
                $startDate = MAX($startDateMonth, $startDateWeek);
            } elseif ($prevNext == -1) {
                $startDateMonth = strtotime('first day of this month  midnight', $datetime);
                $startDateWeek = strtotime('monday this week  midnight', $datetime);
                $startDatePrevWeek = strtotime('monday previous week  midnight', $datetime);
                if ($startDateMonth>$startDateWeek) {
                    $startDate = $startDateWeek;
                } elseif ($startDateMonth == $startDateWeek){
                    $startDate = $startDatePrevWeek;
                } else {
                    $startDate = ($startDateMonth<$startDatePrevWeek)?$startDatePrevWeek:$startDateMonth;
                }
            }
            break;
    }
    return $startDate;
}
/*
 * function to make the endDate
 *
 *  @param    string            $datetime           date on a php format
 *  @return     string
 */
function getEndDate($datetime)
{
    global $conf;
// use the day, month, year value
    $endDate = null;
    /**************************
     * calculate the end date form php date
     ***************************/
    switch(getConf('TIMESHEET_TIME_SPAN','week')) {
        case 'month':
            $endDate = strtotime('first day of next month midnight', $datetime);
            break;
        case 'week':
            $endDate = strtotime('monday next week midnight', $datetime);
            break;
        case 'splitedWeek':
        default:
            $day = date('d', $datetime);
            $dayOfWeek = date('N', $datetime);
            $dayInMonth = date('t', $datetime);
            if ($dayInMonth<$day+(7-$dayOfWeek)) {
                $endDate = strtotime('first day of next month midnight', $datetime);
            } else{
                $endDate = strtotime('monday next week midnight', $datetime);
            }
            break;
    }
    return $endDate;
}
/*
 * function to make the Date in PHP format
 *
  *  @param    int              $day                    day of the date
 *  @param    int               $month                   month of the date
 *  @param    int               $year                    year of the date
 *  @param    string            $date           date on a string format
 *  @return     string
 */
function parseDate($day = 0, $month = 0, $year = 0, $date = 0)
{
    if ($day == 0 && $month == 0 && $year == 0 && $date == 0){
        return 0;
    }
    $datetime = time();
    $splitWeek = 0;
    if ($day!=0 && $month!=0 && $year!= 0 && $day!='' && $month!='' && $year!= '') {
        $datetime = dol_mktime(0, 0, 0, $month, $day, $year);
    // the date is already in linux format
    } elseif (is_numeric($date) && $date!=0) {  // if date is a datetime
        $datetime = $date;
    } elseif (is_string($date)&& $date!="") {  // if date is a string
        //foolproof: incase the yearweek in passed in date
        if (strlen($date)>3 && substr($date, -3, 2) == "_H") {
              if (substr($date, -1, 1) == 1) {
                  $date = substr($date, 0, 7);
                  $splitWeek = 1;
              } else{
                  $date = 'last day of  week '.substr($date, 0, 7);
                  $splitWeek = 2;
              }
        }
        $datetime = strtotime($date);
    }
    return $datetime;
}
/*
 * function to show the AP tab
 *
 *  @param    string        $role        active role
  *  @return  void                                array(ID => userName)
 */
function showTimesheetApTabs($role_key)
{
global $langs, $roles, $apflows;
global $conf;
//$roles = array(0 => 'team', 1 => 'project', 2 => 'customer', 3 => 'supplier', 4 => 'other');
$rolesUrl = array(1 => 'TimesheetTeamApproval.php?role=team', 2 => 'TimesheetOtherApproval.php?role=project', 3 => 'TimesheetOtherApproval.php?role=customer', 4 => 'TimesheetOtherApproval.php?role=supplier', 5 => 'TimesheetOtherApproval.php?role=other');
    foreach ($apflows as $key => $value) {
        if ($value == 1) {
            echo '  <div class = "inline-block tabsElem"><a  href = "'.$rolesUrl[$key].'&leftmenu=timesheet" class = "';
            echo    ($role_key == $key)?'tabactive':'tabunactive';
            echo   ' tab inline-block" data-role = "button">'.$langs->trans($roles[$key])."</a></div>\n";
        }
    }
}
/*
 * function calculate the number of day between two dates
 *
 *  @param    string        $dateStart        start date
 *  @param    string        $datEnd             end date
  *  @return  void                                array(ID => userName)
 */
function getDayInterval($dateStart, $dateEnd)
{
    return round(($dateEnd-$dateStart)/SECINDAY, 0, PHP_ROUND_HALF_UP);
}
/* Function to format the duration
 *
 *  @param    int        $duration             time in seconds
 *  @param    int        $hoursperdays          mode -1 fetch from config, 0 show in hours, >0 shows in days
 *  @return  string                        time display
 */
function formatTime($duration, $hoursperdays = -1)
{
    global $conf;
    if ($hoursperdays == -1) {
        $hoursperdays = (getConf('TIMESHEET_TIME_TYPE','hours') == "days")?getConf('TIMESHEET_DAY_DURATION',8):0;
    } elseif ($hoursperdays == -2) {
        $hoursperdays = (getConf('TIMESHEET_INVOICE_TIMETYPE','days') == "days")?getConf('TIMESHEET_DAY_DURATION',8):0;
    } elseif ($hoursperdays == -3) {
        $hoursperdays = getConf('TIMESHEET_DAY_DURATION',8);
    }
    if (!is_numeric($duration))$duration = 0;
    if ($hoursperdays == 0) {
        $TotalSec = $duration%60;
        $TotalMin = (($duration-$TotalSec)/60)%60;
        $TotalHours = floor(($duration-$TotalSec-$TotalMin*60)/3600);
        return sprintf("%02s", $TotalHours).':'.sprintf("%02s", $TotalMin);
    } else {
        $totalDay = round($duration/3600/$hoursperdays, getConf('TIMESHEET_ROUND'));
        return strval($totalDay);
    }
}
    /** function to send the eventmessage to dolibarr
     *
     * @global type $langs
     * @param array $arraymessage array of message to be displayed
     * @param bool $returnstring  to retrun the json version
     * @return string   messages in json format
     */
    function TimesheetsetEventMessage($arraymessage, $returnstring = false)
    {
        global $langs;
        $messages = array();
        if (array_key_exists('timeSpendCreated', $arraymessage) && $arraymessage['timeSpendCreated']) $messages[] = array('type' => 'mesgs', 'text' => 'NumberOfTimeSpendCreated', 'param'=>$arraymessage['timeSpendCreated']);
        if (array_key_exists('timeSpendModified', $arraymessage) && $arraymessage['timeSpendModified'])$messages[] = array('type' => 'mesgs', 'text' => 'NumberOfTimeSpendModified', 'param'=>$arraymessage['timeSpendModified']);
        if (array_key_exists('timeSpendDeleted', $arraymessage) && $arraymessage['timeSpendDeleted'])$messages[] = array('type' => 'mesgs', 'text' => 'NumberOfTimeSpendDeleted', 'param'=>$arraymessage['timeSpendDeleted']);
        $default = array('type' => 'warning', 'text' => 'NothingChanged', 'param' => 0);
        if (array_key_exists('NoteUpdated', $arraymessage) && $arraymessage['NoteUpdated'])$messages[] = array('type' => 'mesgs', 'text' => 'NoteUpdated', 'param'=>$arraymessage['NoteUpdated']);
        if (array_key_exists('ProgressUpdate', $arraymessage) && $arraymessage['ProgressUpdate'])$messages[] = array('type' => 'mesgs', 'text' => 'ProgressUpdate', 'param'=>'');
        if (array_key_exists('updateError', $arraymessage) && $arraymessage['updateError'])$messages[] = array('type' => 'error', 'text' => 'updateError', 'param'=>$arraymessage['updateError']);
        if (array_key_exists('NoActiveEvent', $arraymessage) && $arraymessage['NoActiveEvent'])$messages[] = array('type' => 'warning', 'text' => 'NoActiveEvent', 'param'=>'');
        if (array_key_exists('EventNotActive', $arraymessage) && $arraymessage['EventNotActive'])$messages[] = array('type' => 'error', 'text' => 'EventNotActive', 'param'=>'');
        if (array_key_exists('DbError', $arraymessage) && $arraymessage['DbError'])$messages[] = array('type' => 'error', 'text' => 'DbError', 'param'=>'');

        $nbr = 0;
        foreach ($messages as $key => $message) {
            if ($message['param']>0) {
                if ($returnstring == false)setEventMessage($langs->transnoentitiesnoconv($message['text']).$message['param'], $message['type']);
                else $messages[$key]['text']=$langs->trans($message['text']);
                $nbr++;
            } else{
                if ($returnstring == false)setEventMessage($langs->transnoentitiesnoconv($message['text']), $message['type']);
                else $messages[$key]['text']=$langs->trans($message['text']);
                $nbr++;
            }
        }
        if ($nbr == 0) setEventMessage($langs->transnoentitiesnoconv(
                    $default['text']), $default['type']);
        if ($returnstring == true)return json_encode($messages);
    }

    /**    Returns the offset from the origin timezone to the remote timezone, in seconds.
*    @param string $remote_tz timezone
*    @param string|null $origin_tz timezone 2; If null the servers current timezone is used as the origin.
*    @return int;
*/
function get_timezone_offset($remote_tz, $origin_tz = null)
{
    if ($origin_tz === null) {
        if (!is_string($origin_tz = date_default_timezone_get())) {
            return false; // A UTC token was returned -- bail out!
        }
    }
    $origin_dtz = new DateTimeZone($origin_tz);
    $remote_dtz = new DateTimeZone($remote_tz);
    $origin_dt = new DateTime("now", $origin_dtz);
    $remote_dt = new DateTime("now", $remote_dtz);
    $offset = $origin_dtz->getOffset($origin_dt) - $remote_dtz->getOffset($remote_dt);
    return $offset;
}

/**
 *  Will register an progree change for an attendance and return the result in json
 *
 *  @param USER $user user that will update
 *  @param  string                $json         json of the request
 *  @return        int                                         <0 if KO, >0 if OK
 */
function ajaxprogress($user, $json)
{
    global $conf, $langs,$db;
    $res = '';
    $arrayRes = array();
    $task = new Task($db);
    if (!empty($json)) {
        $object = json_decode($json);
        if ($object == false || !($object->taskid > 0)){
            $arrayRes['updateError'] ++;
        }else{
            $task->fetch($object->taskid);
            if ($object->progress == '' ||$object->progress == -1){
                $object->progress = $task->progress;
                $res++;
            }else{
                $task->progress = $object->progress;
                $res = $task->update($user);
                if ($res){
                    $arrayRes['ProgressUpdate'] ++;
                }else{
                    $arrayRes['updateError'] ++;
                }
            }
        }
        if (count($arrayRes)) $object->status = TimesheetsetEventMessage($arrayRes, true);
    }else{
        $arrayRes['updateError'] ++;
        $object['status'] = TimesheetsetEventMessage($arrayRes, true);
    }
    return json_encode($object);
}

function timesheet_report_prepare_head( $mode, $item_id , $hidetab=0) {

	global $langs, $conf, $user;

	$head   = array();
	$h      = 0;
	$item = "userSelected=" . $item_id;
	if ( 'project' == $mode ) {
		$item = "projectSelected=" . $item_id;;
	}

	$head[$h][0] = "?".$item."&reporttab=showthisweek&hidetab=".$hidetab."&startDate=".dol_print_date(strtotime("monday this week"), 'dayxcard')."&dateEnd=".dol_print_date(strtotime("sunday this week"), 'dayxcard');
	$head[$h][1] = $langs->trans('thisWeek');
	$head[$h][2] = 'showthisweek';
	$h++;

	$head[$h][0] = "?".$item."&reporttab=showthismonth&hidetab=".$hidetab."&startDate=".dol_print_date(strtotime("first day of this month"), 'dayxcard')."&dateEnd=".dol_print_date(strtotime("last day of this month"), 'dayxcard');
	$head[$h][1] = $langs->trans('thisMonth');
	$head[$h][2] = 'showthismonth';
	$h++;

	$head[$h][0] = "?".$item."&reporttab=showlastweek&hidetab=".$hidetab."&startDate=".dol_print_date(strtotime("monday last week"), 'dayxcard')."&dateEnd=".dol_print_date(strtotime("sunday last week"), 'dayxcard');
	$head[$h][1] = $langs->trans('lastWeek');
	$head[$h][2] = 'showlastweek';
	$h++;

	$head[$h][0] = "?".$item."&reporttab=showlastmonth&hidetab=".$hidetab."&startDate=".dol_print_date(strtotime("first day of previous month"), 'dayxcard')."&dateEnd=".dol_print_date(strtotime("last day of previous month"), 'dayxcard');
	$head[$h][1] = $langs->trans('lastMonth');
	$head[$h][2] = 'showlastmonth';
	$h++;

	$today = dol_print_date(time(), 'dayxcard');

	$head[$h][0] = "?".$item."&reporttab=showtoday&hidetab=".$hidetab."&startDate=".$today."&dateEnd=".$today;
	$head[$h][1] = $langs->trans('Today');
	$head[$h][2] = 'showtoday';

	return $head;
}
