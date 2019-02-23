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
require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
require_once 'core/lib/timesheet.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

class TimesheetReport
{
    public $db;
    public $ref;
    public $projectid;
    public $userid;
    public $name;
    public $startDate;
    public $stopDate;
    public $mode;
    public $modeSQLOrder;
    public $lvl1Title;
    public $lvl2Title;
    public $lvl3Title;
    public $lvl1Key;
    public $lvl2Key;
    public $thirdparty;
    public $project;
    public $user;
    /** constructor
     *
     * @param DATABASE $db db object
     * @return null
     */
    public function __construct($db)
    {
        $this->db = $db;
    }
    /** init the report with date ...
     *
     * @global object $conf  conf object
     * @param int|null $projectid id of the project
     * @param int|null $userid  id of the user
     * @param string $name  name of the report
     * @param datetime $startDate start of the report
     * @param datetime $stopDate  end of the report
     * @param string $mode  order of the report's levels
     * @param bool $invoiceableOnly report only on invoicable task
     * @param int[] $taskarray  array of task id on which the report should be
     * @return null
     */
    public function initBasic($projectid, $userid, $name, $startDate, $stopDate, $mode, $invoiceableOnly = '0', $taskarray = null)
    {
    global  $conf,$user;
    if ($userid && !$user->admin && empty($projectid)){
        // FIXME check is the $use is a N+1 or specific rights
    } elseif ($projectid && !$user->admin){
        //FIXME check that the user has project rights
    }
    $this->ref = "";
    $first = false;
    $this->invoiceableOnly = $invoiceableOnly;
    $this->taskarray = $taskarray;
        $this->projectid = $projectid;// coul
        if ($projectid) {
            $this->project = new Project($this->db);
            $this->project->fetch($projectid);
            $this->ref = (($conf->global->TIMESHEET_HIDE_REF == 1)?'':$this->project->ref.' - ').$this->project->title;
            $first = true;
            $this->thirdparty = new Societe($this->db);
            $this->thirdparty->fetch($this->project->socid);
        }
        $this->userid = $userid;// coul
        if ($userid) {
            $this->user = new User($this->db);
            $this->user->fetch($userid);
            if(empty($this->ref)){
                $this->ref= $this->user->lastname.' '.$this->user->firstname;
            }
        }
        $this->startDate = $startDate;
        $this->stopDate = $stopDate;
        $this->mode = $mode;
        $this->name = ($name!="")?$name:$this->ref;// coul
        $this->ref .= '_'.str_replace('/', '-', dol_print_date($startDate, 'day')).'_'.str_replace('/', '-', dol_print_date($stopDate, 'day'));
        switch ($mode) {
        case 'PDT': //project  / task / Days //FIXME dayoff missing
            $this->modeSQLOrder = 'ORDER BY prj.rowid, ptt.task_date, tsk.rowid ASC   ';
            //title
            $this->lvl1Title = 'projectLabel';
            $this->lvl2Title = 'date';
            $this->lvl3Title = 'taskLabel';
            //keys
            $this->lvl1Key = 'projectId';
            $this->lvl2Key = 'date';
            break;
        case 'DPT'://day /project /task
            $this->modeSQLOrder = 'ORDER BY ptt.task_date, prj.rowid, tsk.rowid ASC   ';
            //title
            $this->lvl1Title = 'date';
            $this->lvl2Title = 'projectLabel';
            $this->lvl3Title = 'taskLabel';
            //keys
            $this->lvl1Key = 'date';
            $this->lvl2Key = 'projectId';
            break;
        case 'PTD'://day /project /task
            $this->modeSQLOrder = 'ORDER BY prj.rowid, tsk.rowid, ptt.task_date ASC   ';
            //title
            $this->lvl1Title = 'projectLabel';
            $this->lvl2Title = 'taskLabel';
            $this->lvl3Title = 'date';
            //keys
            $this->lvl1Key = 'projectId';
            $this->lvl2Key = 'taskId';
            break;
        case 'UDT': //project  / task / Days //FIXME dayoff missing
            $this->modeSQLOrder = 'ORDER BY usr.rowid, ptt.task_date, tsk.rowid ASC   ';
            //title
            $this->lvl1Title = 'userName';
            $this->lvl2Title = 'date';
            $this->lvl3Title = 'taskLabel';
            //keys
            $this->lvl1Key = 'userId';
            $this->lvl2Key = 'date';
            break;
        case 'DUT'://day /project /task
            $this->modeSQLOrder = 'ORDER BY ptt.task_date, usr.rowid, tsk.rowid ASC   ';
            //title
            $this->lvl1Title = 'date';
            $this->lvl2Title = 'userName';
            $this->lvl3Title = 'taskLabel';
            //keys
            $this->lvl1Key = 'date';
            $this->lvl2Key = 'userId';
            break;
        case 'UTD'://day /project /task
            $this->modeSQLOrder = ' ORDER BY usr.rowid, tsk.rowid, ptt.task_date ASC   ';
            $this->lvl1Title = 'userName';
            $this->lvl2Title = 'taskLabel';
            $this->lvl3Title = 'date';
            //keys
            $this->lvl1Key = 'userId';
            $this->lvl2Key = 'taskId';
            break;
        default:
            break;
        }
    }
    /* Function to generate array for the resport
     * @param   int    $invoiceableOnly   will return only the invoicable task
     * @param   array(int)   $taskarray   return the report only for those tasks
     * @param   string  $sqltail    sql tail after the where
     * @return array()
     */
    public function getReportArray()
    {
        global $conf;
        $resArray = array();
        $first = true;
        $sql = 'SELECT prj.rowid as projectid, usr.rowid as userid, tsk.rowid as taskid, ';
        if ($db->type!='pgsql') {
            $sql.= ' MAX(prj.title) as projecttitle, MAX(prj.ref) as projectref, MAX(usr.firstname) as firstname, MAX(usr.lastname) as lastname, ';
            $sql.= " MAX(tsk.ref) as taskref, MAX(tsk.label) as tasktitle, GROUP_CONCAT(ptt.note SEPARATOR '. ') as note, MAX(tske.invoiceable) as invoicable, ";
        } else {
            $sql.= ' prj.title as projecttitle, prj.ref as projectref, usr.firstname, usr.lastname, ';
            $sql.= " tsk.ref as taskref, tsk.label as tasktitle, STRING_AGG(ptt.note, '. ') as note, MAX(tske.invoiceable) as invoicable, ";
        }
        $sql.= ' ptt.task_date, SUM(ptt.task_duration) as duration ';
        $sql.= ' FROM '.MAIN_DB_PREFIX.'projet_task_time as ptt ';
        $sql.= ' JOIN '.MAIN_DB_PREFIX.'projet_task as tsk ON tsk.rowid = fk_task ';
        $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'projet_task_extrafields as tske ON tske.fk_object = tsk.rowid ';
        $sql.= ' JOIN '.MAIN_DB_PREFIX.'projet as prj ON prj.rowid = tsk.fk_projet ';
        $sql.= ' JOIN '.MAIN_DB_PREFIX.'user as usr ON ptt.fk_user = usr.rowid ';
        $sql.= ' WHERE ';
        if (!empty($this->userid)) {
            $sql .= ' ptt.fk_user = \''.$this->userid.'\' ';
            $first = false;
        }
        if (!empty($this->projectid)) {
            $sql .= ($first?'':'AND ').'tsk.fk_projet = \''.$this->projectid.'\' ';
            $first = false;
        }
        if (is_array($this->taskarray) && count($this->taskarray)>1) {
            $sql .= ($first?'':'AND ').'tsk.rowid in ('.explode($taskarray, ', ').') ';
        }
        if ($this->invoiceableOnly == 1) {
            $sql .= ($first?'':'AND ').'tske.invoiceable = \'1\'';
        }
         /*if (!empty($startDay))$sql .= 'AND task_date>=\''.$this->db->idate($startDay).'\'';
          else */$sql .= 'AND task_date>=\''.$this->db->idate($this->startDate).'\'';
          /*if (!empty($stopDay))$sql.= ' AND task_date<=\''.$this->db->idate($stopDay).'\'';
          else */$sql.= ' AND task_date<=\''.$this->db->idate($this->stopDate).'\'';
         $sql .= ' GROUP BY usr.rowid, ptt.task_date, tsk.rowid, prj.rowid ';
        /*if (!empty($sqltail)) {
            $sql .= $sqltail;
        }*/
        $sql .= $this->modeSQLOrder;
        dol_syslog("timesheet::userreport::tasktimeList", LOG_DEBUG);
        $resql = $this->db->query($sql);
        if ($resql) {
            $numTaskTime = $this->db->num_rows($resql);
            $i = 0;
            // Loop on each record found,
            while ($i < $numTaskTime)
            {
                $error = 0;
                $obj = $this->db->fetch_object($resql);
                $resArray[$i] = array('projectId' => $obj->projectid,
                    'projectLabel' => (($conf->global->TIMESHEET_HIDE_REF == 1)?'':$obj->projectref.' - ').$obj->projecttitle,
                    'projectRef' => $obj->projectref,
                    'projectTitle' =>$obj->projecttitle,
                    'taskId' => $obj->taskid,
                    'taskLabel' => (($conf->global->TIMESHEET_HIDE_REF == 1)?'':$obj->taskref.' - ').$obj->tasktitle,
                    'taskRef' => $obj->taskref,
                    'tasktitle' => $obj->tasktitle,
                    'date' => $this->db->jdate($obj->task_date),
                    'duration' => $obj->duration,
                    'durationHours' => formatTime($obj->duration, $conf->global->TIMESHEET_DAY_DURATION),
                    'durationDays' => formatTime($obj->duration, 0),
                    'userId' => $obj->userid,
                    'userName' => trim($obj->firstname).' '.trim($obj->lastname),
                    'firstName' => trim($obj->firstname),
                    'lastName' => trim($obj->lastname),
                    'note' => ($obj->note),
                    'invoiceable' => $obj->invoiceable);
                $i++;
            }
            $this->db->free($resql);
            return $resArray;
        } else {
            dol_print_error($this->db);
            return array();
        }
    }
    /*
    *  Function to generate HTML for the report
    * @param   date    $startDay   start date for the query
    * @param   date    $stopDay   start date for the query
    * @param   string   $mode       specify the query type
    * @param
    * @param   string  $sqltail    sql tail after the where
    * @return string
    * mode layout PTD project/task /day, PDT, DPT
    * periodeTitle give a name to the report
    * timemode show time using day or hours (== 0)
    */
    public function getHTMLreport($short, $periodTitle, $hoursperdays, $reportfriendly = 0)
    {
    // HTML buffer
        global $langs;
        $lvl1HTML = '';
        $lvl3HTML = '';
        $lvl2HTML = '';
        // partial totals
        $lvl3Total = 0;
        $lvl2Total = 0;
        $lvl1Total = 0;
        $Curlvl1 = 0;
        $Curlvl2 = 0;
        $Curlvl3 = 0;
        $lvl3Notes = "";
        //mode 1, PER USER
        //get the list of user
        //get the list of task per user
        //sum user
        //mode 2, PER TASK
        //list of task
        //list of user per
        $title = array('projectLabel'=>'Project', 'date'=>'Day', 'taskLabel'=>'Tasks', 'userName'=>'User');
        $titleWidth = array('4'=>'120', '7'=>'200');
        $sqltail = '';
        $resArray = $this->getReportArray();
        $numTaskTime = count($resArray);
        if ($numTaskTime>0) {
            // current
            if ($reportfriendly) {
                //$HTMLRes = '<br><div class = "titre">'.$this->name.', '.$periodTitle.'</div>';
                $HTMLRes .= '<table class = "noborder" width = "100%">';
                $HTMLRes .= '<tr class = "liste_titre"><th>'.$langs->trans('Name');
                $HTMLRes .= '</th><th>'.$langs->trans($title[$this->lvl1Title]).'</th><th>';
                $HTMLRes .= $langs->trans($title[$this->lvl2Title]).'</th>';
                $HTMLRes .= '<th>'.$langs->trans($title[$this->lvl3Title]).'</th>';
                $HTMLRes .= '<th>'.$langs->trans('Duration').':'.$langs->trans('hours').'</th>';
                $HTMLRes .= '<th>'.$langs->trans('Duration').':'.$langs->trans('Days').'</th></tr>';
                foreach ($resArray as $key => $item) {
                   $item['date'] = dol_print_date($item['date'], 'day');
                   $HTMLRes.= '<tr class = "oddeven" align = "left"><th width = "200px">'.$this->name.'</th>';
                   $HTMLRes.= '<th '.(isset($titleWidth[$this->lvl1Title])?'width = "'.$titleWidth[$this->lvl1Title].'"':'').'>'.$item[$this->lvl1Title].'</th>';
                   $HTMLRes .= '<th '.(isset($titleWidth[$this->lvl2Title])?'width = "'.$titleWidth[$this->lvl2Title].'"':'').'>'.$item[$this->lvl2Title].'</th>';
                   $HTMLRes .= '<th '.(isset($titleWidth[$this->lvl3Title])?'width = "'.$titleWidth[$this->lvl3Title].'"':'').'>'.$item[$this->lvl3Title].'</th>';
                   $HTMLRes .= '<th width = "70px">'.$item['durationHours'].'</th>';
                   $HTMLRes .= '<th width = "70px">'.$item['duration*days'].'</th></tr>';
                }
                $HTMLRes .= '</table>';
            } else {
                foreach ($resArray as $key => $item) {
                    if ($Curlvl1 == 0) {
                        $Curlvl1 = $key;
                        $Curlvl2 = $key;
                    }
                    // reformat date to avoid UNIX time
                    $resArray[$key]['date'] = dol_print_date($item['date'], 'day');
                    //add the LVL 2 total when  change detected in Lvl 2 & 1
                    if (($resArray[$Curlvl2][$this->lvl2Key]!=$resArray[$key][$this->lvl2Key])
                            ||($resArray[$Curlvl1][$this->lvl1Key]!=$resArray[$key][$this->lvl1Key]))
                    {
                        //creat the LVL 2 Title line
                        $lvl2HTML .= '<tr class = "oddeven" align = "left"><th></th><th>'
                                .$resArray[$Curlvl2][$this->lvl2Title].'</th>';
                        // add an empty cell on row if short version (in none short mode there is an additionnal column
                        if (!$short)$lvl2HTML .= '<th></th>';
                        // add the LVL 3 total hours on the LVL 2 title
                        $lvl2HTML .= '<th>'.formatTime($lvl3Total, 0).'</th>';
                        // add the LVL 3 total day on the LVL 2 title
                        $lvl2HTML .= '<th>'.formatTime($lvl3Total, $hoursperdays).'</th><th>';
                        if ($short) {
                            $lvl2HTML .= $lvl3Notes;
                        }
                        $lvl3Notes = '';
                        $lvl2HTML .= '</th></tr>';
                        //add the LVL 3 content (details)
                        $lvl2HTML .= $lvl3HTML;
                        //empty lvl 3 HTML to start anew
                        $lvl3HTML = '';
                        //add the LVL 3 total to LVL3
                        $lvl2Total+=$lvl3Total;
                        //empty lvl 3 total to start anew
                        $lvl3Total = 0;
                        // save the new lvl2 ref
                        $Curlvl2 = $key;
                        //creat the LVL 1 Title line when lvl 1 change detected
                        if (($resArray[$Curlvl1][$this->lvl1Key]!=$resArray[$key][$this->lvl1Key])) {
                             //creat the LVL 1 Title line
                            $lvl1HTML .= '<tr class = "oddeven" align = "left"><th >'
                                .$resArray[$Curlvl1][$this->lvl1Title].'</th><th></th>';
                            // add an empty cell on row if short version (in none short mode there is an additionnal column
                            if (!$short)$lvl1HTML .= '<th></th>';
                            $lvl1HTML .= '<th>'.formatTime($lvl2Total, 0).'</th>';
                            $lvl1HTML .= '<th>'.formatTime($lvl2Total, $hoursperdays).'</th></th><th></tr>';
                            //add the LVL 3 HTML content in lvl1
                            $lvl1HTML .= $lvl2HTML;
                             //empty lvl 3 HTML to start anew
                            $lvl2HTML = '';
                            //addlvl 2 total to lvl1
                            $lvl1Total+=$lvl2Total;
                            //empty lvl 3 total tyo start anew
                            $lvl2Total = 0;
                            // save the new lvl1 ref
                            $Curlvl1 = $key;
                        }
                    }
                    // show the LVL 3 only if not short
                    if (!$short) {
                        $lvl3HTML .= '<tr class = "oddeven" align = "left"><th></th><th></th><th>'
                            .$resArray[$key][$this->lvl3Title].'</th><th>';
                        $lvl3HTML .= $item['durationHours'].'</th><th>';
                        $lvl3HTML .= $item['durationDays'].'</th><th>';
                        $lvl3HTML .= $resArray[$key]['note'];
                        $lvl3HTML .= '</th></tr>';
                       /*
                        if ($hoursperdays == 0) {
                            $lvl3HTML .= date('G:i', mktime(0, 0, $resArray[$key]['duration'])).'</th></tr>';
                        } else{
                            $lvl3HTML .= $resArray[$key]['duration']/3600/$hoursperdays.'</th></tr>';
                        }*/
                    } elseif (!empty ($resArray[$key]['note'])) {
                        $lvl3Notes .= "<br>".$resArray[$key]['note'];
                    }
                    $lvl3Total+=$resArray[$key]['duration'];
                }
               //handle the last line : print LV1 & LVL 2 title
                //creat the LVL 2 Title line
                $lvl2HTML .= '<tr class = "oddeven" align = "left"><th></th><th>'
                    .$resArray[$Curlvl2][$this->lvl2Title].'</th>';
                // add an empty cell on row if short version (in none short mode there is an additionnal column
                if (!$short)$lvl2HTML .= '<th></th>';
                // add the LVL 3 total hours on the LVL 2 title
                $lvl2HTML .= '<th>'.formatTime($lvl3Total, 0).'</th>';
                // add the LVL 3 total day on the LVL 2 title
                $lvl2HTML .= '<th>'.formatTime($lvl3Total, $hoursperdays).'</th><th>';
                if ($short) {
                    $lvl2HTML .= $lvl3Notes;
                }
                $lvl2HTML .= '</th></tr>';
                //add the LVL 3 content (details)
                $lvl2HTML .= $lvl3HTML;
                //add the LVL 3 total to LVL3
                $lvl2Total+=$lvl3Total;
                //creat the LVL 1 Title line
                $lvl1HTML .= '<tr class = "oddeven" align = "left"><th >'
                    .$resArray[$Curlvl1][$this->lvl1Title].'</th><th></th>';
                // add an empty cell on row if short version (in none short mode there is an additionnal column
                if (!$short)$lvl1HTML .= '<th></th>';
                $lvl1HTML .= '<th>'.formatTime($lvl2Total, 0).'</th>';
                $lvl1HTML .= '<th>'.formatTime($lvl2Total, $hoursperdays).'</th></tr>';
                //add the LVL 3 HTML content in lvl1
                $lvl1HTML .= $lvl2HTML;
                //empty lvl 3 HTML to start anew
                $lvl2HTML = '';
                //addlvl 2 total to lvl1
                $lvl1Total+=$lvl2Total;
                // make the whole result
                $HTMLRes = '<br><div class = "titre">'.$this->name.', '.$periodTitle.'</div>';
                $HTMLRes .= '<table class = "noborder" width = "100%">';
                $HTMLRes .= '<tr class = "liste_titre"><th>'.$langs->trans($title[$this->lvl1Title]).'</th><th>'
                       .$langs->trans($title[$this->lvl2Title]).'</th>';
                $HTMLRes .= (!$short)?'<th>'.$langs->trans($title[$this->lvl3Title]).'</th>':'';
                $HTMLRes .= '<th>'.$langs->trans('Duration').':'.$langs->trans('hours').'</th>';
                $HTMLRes .= '<th>'.$langs->trans('Duration').':'.$langs->trans('Days').'</th><th>'.$langs->trans('Note').'</th></tr>';
                $HTMLRes .= '<tr class = "liste_titre">'.((!$short)?'<th></th>':'').'<th colspan = 2> TOTAL</th>';
                $HTMLRes .= '<th>'.formatTime($lvl1Total, 0).'</th>';
                $HTMLRes .= '<th>'.formatTime($lvl1Total, $hoursperdays).'</th><th></th></tr>';
                $HTMLRes .= $lvl1HTML;
                $HTMLRes .= '</table>';
            } // end else reportfiendly
        } // end is numtasktime
        return $HTMLRes;
    }
    /**
     *
     * @global object $conf conf object
     * @global object $langs lang object
     * @param string $model   file model (excel excel2007 pdf)
     * @param bool $save    will save the export on the project/user folder or not
     * @return string   filename
     */
    public function buildFile($model = 'excel2017', $save = false)
    {
        if($model == 'excel2017' || $model == 'excel'){
            if(!(extension_loaded ('zip') && extension_loaded('xml'))){
                $this->error = "missing php extention (xml or zip)";
                dol_syslog("Export::build_file Error: ".$this->error, LOG_ERR);
                return -1;
            }
        }
        global $conf,$langs,$user;
        $dir = DOL_DOCUMENT_ROOT . "/core/modules/export/";
        $file = "export_".$model.".modules.php";
        $classname = "Export".$model;
        require_once $dir.$file;
	$objmodel = new $classname($this->db);
        $arrayTitle = array('projectRef' => 'projectRef', 'projectTitle' => 'projectTitle', 'taskRef' => 'taskRef', 'tasktitle' => 'taskTitle', 'date' => 'Date', 'durationHours' => 'Hours', 'durationDays' => 'Days', 'userId' => 'userId', 'firstName' => 'Firstname', 'lastName' => 'Lastname', 'note' => 'Note', 'invoiceable' => 'Invoiceable');
        $arrayTypes = array('projectRef' => 'TextAuto', 'projectTitle' => 'TextAuto', 'taskRef' => 'TextAuto', 'tasktitle' => 'TextAuto', 'date' => 'Date', 'durationHours' => 'TextAuto', 'durationDays' => 'Numeric', 'userId' => 'Numeric', 'firstName' => 'TextAuto', 'lastName' => 'TextAuto', 'note' => 'TextAuto', 'invoiceable' => 'Numeric');
        $arraySelected = array('projectRef' => 'projectRef', 'projectTitle' => 'projectTitle', 'taskRef' => 'taskRef', 'tasktitle' => 'tasktitle', 'userId' => 'userId', 'firstName' => 'firstName', 'lastName' => 'lastName', 'date' => 'date', 'durationHours' => 'durationHours', 'durationDays' => 'durationDays', 'note' => 'note', 'invoiceable' => 'invoiceable');

        $resArray = $this->getReportArray();
        if (is_array($resArray))
        {
            $dirname=$conf->timesheet->dir_output.'/reports';
            //
            //$dirname=$conf->export->dir_temp.'/'.$user->id;
            /*
            if($save){
                $dirname = $conf->user->dir_output."/".$this->userid.'/reports';
                if($this->projectid > 0){
                    $this->project = new Project($this->db);
                    $this->project->fetch($projectid);
                    $dirname = $conf->projet->dir_output.'/'.dol_sanitizeFileName($project->ref).'/reports';
                }
            } else{
                $dirname=$conf->timesheet->dir_output.'/reports';
            }*/
            $filename = "report.".$objmodel->getDriverExtension();
                    //str_replace(array('/', ' ', "'", '"', '&', '?'), '_', $this->ref).'.'.$objmodel->getDriverExtension();
            $outputlangs = clone $langs; // We clone to have an object we can modify (for example to change output charset by csv handler) without changing original value
            // Open file
            dol_mkdir($dirname);
            $result=$objmodel->open_file($dirname."/".$filename, $outputlangs);
            if ($result >= 0)
            {
                // Genere en-tete
                $objmodel->write_header($outputlangs);
                // Genere ligne de titre
                $objmodel->write_title($arrayTitle, $arraySelected, $outputlangs, $arrayTypes);
                foreach($resArray as $row)
                {
                    $object = (object) $row;
                   /* $object = new stdClass();
                    foreach($row as $key => $value){
                        $object->{$key}=$value;
                    }*/
                    $object->date=dol_print_date($object->date);
                    $objmodel->write_record($arraySelected, $object, $outputlangs, $arrayTypes);
                }
                // Genere en-tete
                $objmodel->write_footer($outputlangs);
                // Close file
                $objmodel->close_file();
                return $filename;
            }
            else
            {
                $this->error = $objmodel->error;
                dol_syslog("Export::build_file Error: ".$this->error, LOG_ERR);
                return null;
            }
        }
        else
        {
            $this->error = $this->db->error()." - sql=".$sql;
            return null;
        }
    }
}
