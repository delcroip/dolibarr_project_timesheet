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
    public $lvl0Title;
    public $lvl1Title;
    public $lvl2Title;
    public $lvl3Title;
    public $lvl0Key;
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
     * @param int|array|null $projectid id of the project
     * @param int|array|null $userid  id of the user
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
        global  $conf,$user,$langs;
        if($userid && !$user->admin && empty($projectid)){
            // FIXME check is the $use is a N+1 or specific rights
        } elseif($projectid && !$user->admin){
            //FIXME check that the user has project rights
        }
        $first = false;
        $this->ref=array();
        $this->invoiceableOnly = $invoiceableOnly;
        $this->taskarray = $taskarray;
        $this->projectid = $projectid;// coul
        if($projectid && !is_array($projectid)) {
            $this->project[$projectid] = new Project($this->db);
            $this->project[$projectid]->fetch($projectid);
            $this->ref[$projectid] = $this->project[$projectid]->ref.(($conf->global->TIMESHEET_HIDE_REF == 1)?'':' - '.$this->project[$projectid]->title);
            $first = true;
            $this->thirdparty[$projectid] = new Societe($this->db);
            $this->thirdparty[$projectid]->fetch($this->project[$projectid]->socid);
        }elseif(is_array($projectid)){
            foreach($projectid as $id){
                $this->project[$id] = new Project($this->db);
                $this->project[$id]->fetch($id);
                $this->ref[$id] = $this->project[$id]->ref.(($conf->global->TIMESHEET_HIDE_REF == 1)?'':' - '.$this->project[$id]->title);
                $this->thirdparty[$id] = new Societe($this->db);
                $this->thirdparty[$id]->fetch($this->project[$id]->socid);
            }

            $first = true;
        }
        $this->userid = $userid;// coul
        if($userid && !is_array($userid)) {
            $this->user[$userid] = new User($this->db);
            $this->user[$userid]->fetch($userid);
            if(empty($this->ref)){
                $this->ref[$userid]= $this->user->lastname.' '.$this->user->firstname;
            }
        }elseif(is_array($userid)) {
            foreach($userid as $id){
                $this->user[$id] = new User($this->db);
                $this->user[$id]->fetch($id);
                if(empty($this->ref)){
                    $this->ref[$id]= $this->user->lastname.' '.$this->user->firstname;
                }
            }
        }
        $this->startDate = $startDate;
        $this->stopDate = $stopDate;
        $this->mode = $mode;
        if(count($this->ref) == 1){
            $this->name = reset($this->ref);
        }else{
            $this->name = $langs->trans("ProjectReport");
        }
        $this->name .= '_'.str_replace('/', '-', dol_print_date($startDate, 'day')).'_'.str_replace('/', '-', dol_print_date($stopDate, 'day'));
        switch($mode){
            case 'PDT': //project  / task / Days //FIXME dayoff missing
                $this->modeSQLOrder = 'ORDER BY usr.rowid,prj.rowid, DATE(ptt.task_datehour), tsk.rowid ASC   ';
                //title
                $this->lvl0Title='userName';
                $this->lvl1Title = 'projectLabel';
                $this->lvl2Title = 'dateDisplay';
                $this->lvl3Title = 'taskLabel';
                //keys
                $this->lvl0Key = 'userId';
                $this->lvl1Key = 'projectId';
                $this->lvl2Key = 'dateDisplay';
                break;
            case 'DPT'://day /project /task
                $this->modeSQLOrder = 'ORDER BY usr.rowid,DATE(ptt.task_datehour), prj.rowid, tsk.rowid ASC   ';
                //title
                $this->lvl0Title='userName';
                $this->lvl1Title = 'dateDisplay';
                $this->lvl2Title = 'projectLabel';
                $this->lvl3Title = 'taskLabel';
                //keys
                $this->lvl0Key = 'userId';
                $this->lvl1Key = 'dateDisplay';
                $this->lvl2Key = 'projectId';
                break;
            case 'PTD'://day /project /task
                $this->modeSQLOrder = 'ORDER BY usr.rowid,prj.rowid, tsk.rowid, DATE(ptt.task_datehour) ASC   ';
                //title
                $this->lvl0Title='userName';
                $this->lvl1Title = 'projectLabel';
                $this->lvl2Title = 'taskLabel';
                $this->lvl3Title = 'dateDisplay';
                //keys
                $this->lvl0Key = 'userId';
                $this->lvl1Key = 'projectId';
                $this->lvl2Key = 'taskId';
                break;
            case 'UDT': //project  / task / Days //FIXME dayoff missing
                $this->modeSQLOrder = 'ORDER BY prj.rowid,usr.rowid, DATE(ptt.task_datehour), tsk.rowid ASC   ';
                //title
                $this->lvl0Title='projectLabel';
                $this->lvl1Title = 'userName';
                $this->lvl2Title = 'dateDisplay';
                $this->lvl3Title = 'taskLabel';
                //keys
                $this->lvl0Key = 'projectId';
                $this->lvl1Key = 'userId';
                $this->lvl2Key = 'dateDisplay';
                break;
            case 'DUT'://day /project /task
                $this->modeSQLOrder = 'ORDER BY prj.rowid,DATE(ptt.task_datehour), usr.rowid, tsk.rowid ASC   ';
                //title
                $this->lvl0Title='projectLabel';
                $this->lvl1Title = 'dateDisplay';
                $this->lvl2Title = 'userName';
                $this->lvl3Title = 'taskLabel';
                //keys
                $this->lvl0Key = 'projectId';
                $this->lvl1Key = 'dateDisplay';
                $this->lvl2Key = 'userId';
                break;
            case 'UTD'://day /project /task
                $this->modeSQLOrder = ' ORDER BY prj.rowid,usr.rowid, tsk.rowid, DATE(ptt.task_datehour) ASC   ';
                $this->lvl0Title='projectLabel';
                $this->lvl1Title = 'userName';
                $this->lvl2Title = 'taskLabel';
                $this->lvl3Title = 'dateDisplay';
                //keys
                $this->lvl0Key = 'projectId';
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
        if($db->type!='pgsql') {
            $sql.= ' MAX(prj.title) as projecttitle, MAX(prj.ref) as projectref, MAX(usr.firstname) as firstname, MAX(usr.lastname) as lastname, ';
            $sql.= " MAX(tsk.ref) as taskref, MAX(tsk.label) as tasktitle, GROUP_CONCAT(ptt.note SEPARATOR '. ') as note, MAX(tske.invoiceable) as invoicable, ";
        } else {
            $sql.= ' prj.title as projecttitle, prj.ref as projectref, usr.firstname, usr.lastname, ';
            $sql.= " tsk.ref as taskref, tsk.label as tasktitle, STRING_AGG(ptt.note, '. ') as note, MAX(tske.invoiceable) as invoicable, ";
        }
        $sql.= ' DATE(ptt.task_datehour) AS task_date, SUM(ptt.task_duration) as duration ';
        $sql.= ' FROM '.MAIN_DB_PREFIX.'projet_task_time as ptt ';
        $sql.= ' JOIN '.MAIN_DB_PREFIX.'projet_task as tsk ON tsk.rowid = fk_task ';
        $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'projet_task_extrafields as tske ON tske.fk_object = tsk.rowid ';
        $sql.= ' JOIN '.MAIN_DB_PREFIX.'projet as prj ON prj.rowid = tsk.fk_projet ';
        $sql.= ' JOIN '.MAIN_DB_PREFIX.'user as usr ON ptt.fk_user = usr.rowid ';
        $sql.= ' WHERE ';
        if(!empty($this->userid)) {
            $sql .= ' ptt.fk_user IN (\''.implode("','", $this->userid).'\') ';
            $first = false;
        }
        if(!empty($this->projectid)) {
            $sql .= ($first?'':'AND ').'tsk.fk_projet IN (\''.implode("','", $this->projectid).'\') ';
            $first = false;
        }
        if(is_array($this->taskarray) && count($this->taskarray)>1) {
            $sql .= ($first?'':'AND ').'tsk.rowid in ('.explode($taskarray, ', ').') ';
        }
        if($this->invoiceableOnly == 1) {
            $sql .= ($first?'':'AND ').'tske.invoiceable = \'1\'';
        }
         /*if(!empty($startDay))$sql .= 'AND task_date>=\''.$this->db->idate($startDay).'\'';
          else */$sql .= ($first?'':'AND ').' DATE(task_datehour)>=\''.$this->db->idate($this->startDate).'\'';
          /*if(!empty($stopDay))$sql.= ' AND task_date<=\''.$this->db->idate($stopDay).'\'';
          else */$sql.= ' AND DATE(task_datehour)<=\''.$this->db->idate($this->stopDate).'\'';
         $sql .= ' GROUP BY usr.rowid, DATE(ptt.task_datehour),  prj.rowid, tsk.rowid ';
        /*if(!empty($sqltail)) {
            $sql .= $sqltail;
        }*/
        $sql .= $this->modeSQLOrder;
        dol_syslog("timesheet::userreport::tasktimeList", LOG_DEBUG);
        $resql = $this->db->query($sql);
        if($resql) {
            $numTaskTime = $this->db->num_rows($resql);
            $i = 0;
            // Loop on each record found,
            while($i < $numTaskTime)
            {
                $error = 0;
                $obj = $this->db->fetch_object($resql);
                $resArray[$i] = array('projectId' => $obj->projectid,
                    'projectLabel' => $obj->projectref.(($conf->global->TIMESHEET_HIDE_REF == 1)?'':' - '.$obj->projecttitle),
                    'projectRef' => $obj->projectref,
                    'projectTitle' => $obj->projecttitle,
                    'taskId' => $obj->taskid,
                    'taskLabel' => $obj->taskref.(($conf->global->TIMESHEET_HIDE_REF == 1)?'':' - '.$obj->tasktitle),
                    'taskRef' => $obj->taskref,
                    'tasktitle' => $obj->tasktitle,
                    'date' => $this->db->jdate($obj->task_date),
                    'dateDisplay' => dol_print_date($this->db->jdate($obj->task_date), 'day'),
                    'duration' => $obj->duration,
                    'durationHours' => formatTime($obj->duration, 0),
                    'durationDays' => formatTime($obj->duration, -3),
                    'userId' => $obj->userid,
                    'userName' => trim($obj->firstname).' '.trim($obj->lastname),
                    'firstName' => trim($obj->firstname),
                    'lastName' => trim($obj->lastname),
                    'note' =>($obj->note),
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
    * timemode show time using day or hours(== 0)
    */
    public function getHTMLreportExport()
    {
        $resArray = $this->getReportArray();
        $HTMLRes = '<h1>'.$this->name.'</h1>';
        $HTMLRes .= $this->getHTMLReportHeaders();
        foreach($resArray as $key => $item) {
           $item['date'] = dol_print_date($item['date'], 'day');
           $HTMLRes .= '<tr class = "oddeven" align = "left">';//<th width = "200px">'.$this->name.'</th>';
           $HTMLRes .= '<th '.(isset($titleWidth[$this->lvl0Title])?'width = "'.$titleWidth[$this->lvl0Title].'"':'').'>'.$item[$this->lvl0Title].'</th>';
           $HTMLRes .= '<th '.(isset($titleWidth[$this->lvl1Title])?'width = "'.$titleWidth[$this->lvl1Title].'"':'').'>'.$item[$this->lvl1Title].'</th>';
           $HTMLRes .= '<th '.(isset($titleWidth[$this->lvl2Title])?'width = "'.$titleWidth[$this->lvl2Title].'"':'').'>'.$item[$this->lvl2Title].'</th>';
           $HTMLRes .= '<th '.(isset($titleWidth[$this->lvl3Title])?'width = "'.$titleWidth[$this->lvl3Title].'"':'').'>'.$item[$this->lvl3Title].'</th>';
           $HTMLRes .= '<th width = "70px">'.$item['durationHours'].'</th>';
           $HTMLRes .= '<th width = "70px">'.$item['durationDays'].'</th>';
           $HTMLRes .= '<th width = "70px">'.$item['note'].'</th></tr>';
        }
        $HTMLRes .= '</table>';
        return $HTMLRes;
    }

    /** function to generate HTML headers
     *
     * @return string
     */
    public function getHTMLReportHeaders()
    {
        global $langs;
        $HTMLHeaders = '<h1>'.$this->name.'</h1>';
        $title = array('projectLabel'=>'Project', 'dateDisplay'=>'Day', 'taskLabel'=>'Tasks', 'userName'=>'User');
        $titleWidth = array('4'=>'120', '7'=>'200');
        $HTMLHeaders = '<table class = "noborder" width = "100%">';
        $HTMLHeaders .= '<tr class = "liste_titre">';//<th>'.$langs->trans('Name').'</th>';
        $HTMLHeaders .= '<th>'.$langs->trans($title[$this->lvl0Title]).'</th>';
        $HTMLHeaders .= '<th>'.$langs->trans($title[$this->lvl1Title]).'</th>';
        $HTMLHeaders .= '<th>'.$langs->trans($title[$this->lvl2Title]).'</th>';
        $HTMLHeaders .= '<th>'.$langs->trans($title[$this->lvl3Title]).'</th>';
        $HTMLHeaders .= '<th>'.$langs->trans('Duration').':'.$langs->trans('hours').'</th>';
        $HTMLHeaders .= '<th>'.$langs->trans('Duration').':'.$langs->trans('Days').'</th>';
        $HTMLHeaders .= '<th>'.$langs->trans('Note').'</th></tr>';
        return $HTMLHeaders;
    }

    /**
    *  Function to generate HTML for the report
    * @param   string   $short       show or hide the lvl3 detail
    * @param   string  $periodTitle   title of the period
    * @return string
    * mode layout PTD project/task /day, PDT, DPT
    * periodeTitle give a name to the report
    * timemode show time using day or hours(== 0)
    */
    public function getHTMLreport($short, $periodTitle)
    {
    // HTML buffer
        global $langs;
        $lvl0HTML = $lvl1HTML = $lvl3HTML = $lvl2HTML = '';
        // partial totals
        $lvl3Total = $lvl2Total = $lvl1Total = $lvl0Total = 0;
        $Curlvl0 = $Curlvl1 = $Curlvl2 = $Curlvl3 = 0;
        $lvl3Notes = "";
        $sqltail = '';
        $resArray = $this->getReportArray();
        $numTaskTime = count($resArray);
        $i = 0;
        if($numTaskTime > 0){
            // current
            foreach($resArray as $key => $item) {
                if($Curlvl0 == 0) {
                    $Curlvl0 = $key;
                    $Curlvl1 = $key;
                    $Curlvl2 = $key;
                }
                // reformat date to avoid UNIX time
                //add the LVL 2 total when  change detected in Lvl 2 & 1 &0
                if(($resArray[$Curlvl2][$this->lvl2Key] != $item[$this->lvl2Key])
                        || ($resArray[$Curlvl1][$this->lvl1Key] != $item[$this->lvl1Key])
                        || ($resArray[$Curlvl0][$this->lvl0Key] != $item[$this->lvl0Key]))
                {
                    //title, total,short, lvl3Html, lvl3 notes
                    $lvl2HTML .= $this->getLvl2HTML($resArray[$Curlvl2][$this->lvl2Title], $lvl3Total, $lvl3HTML, $short, $lvl3Notes);
                    //empty lvl 3 Notes to start anew
                    $lvl3Notes = '';
                    //empty lvl 3 HTML to start anew
                    $lvl3HTML = '';
                    //add the LVL 3 total to LVL3
                    $lvl2Total+=$lvl3Total;
                    //empty lvl 3 total to start anew
                    $lvl3Total = 0;
                    // save the new lvl2 ref
                    $Curlvl2 = $key;
                    //creat the LVL 1 Title line when lvl 1 or 0 change detected
                    if(($resArray[$Curlvl1][$this->lvl1Key] != $item[$this->lvl1Key])
                            ||($resArray[$Curlvl0][$this->lvl0Key] != $item[$this->lvl0Key]))
                    {
                        $lvl1HTML .= $this->getLvl1HTML($resArray[$Curlvl1][$this->lvl1Title], $lvl2Total, $lvl2HTML, $short);
                        //addlvl 2 total to lvl1
                        $lvl1Total+=$lvl2Total;
                        //empty lvl 2 total tyo start anew
                        $lvl2HTML = '';
                        $lvl2Total = 0;
                        // save the new lvl1 ref
                        $Curlvl1 = $key;
                        //creat the LVL 0 Title line when lvl  0 change detected
                        if(($resArray[$Curlvl0][$this->lvl0Key]!=$item[$this->lvl0Key]))
                        {
                           $lvl0HTML .= $this->getLvl0HTML($resArray[$Curlvl0][$this->lvl0Title], $lvl1Total, $lvl1HTML, $short);
                           //addlvl 2 total to lvl1
                           $lvl0Total+=$lvl1Total;
                           //empty lvl 2 total tyo start anew
                           $lvl1HTML = '';
                           $lvl1Total = 0;
                           // save the new lvl1 ref
                           $Curlvl0 = $key;
                        }
                    }
                }
                // show the LVL 3 only if not short
                if(!$short) {
                    $lvl3HTML .= $this->getLvl3HTML($item);
                } elseif(!empty($item['note'])) {
                    $lvl3Notes .= "<br>".$item['note'];
                }
                $lvl3Total+=$item['duration'];
                $i++;
                if ($i == 1 || $i == $numTaskTime){
                    $lvl2HTML .=$this->getLvl2HTML($resArray[$Curlvl2][$this->lvl2Title], $lvl3Total, $lvl3HTML, $short, $lvl3Notes);
                    //empty lvl 3 Notes to start anew
                    $lvl3Notes = '';
                    //empty lvl 3 HTML to start anew
                    $lvl3HTML = '';
                    //add the LVL 3 total to LVL3
                    $lvl2Total+=$lvl3Total;
                    //empty lvl 3 total to start anew
                    $lvl3Total = 0;
                  //creat the LVL 1 Title line
                    $lvl1HTML .= $this->getLvl1HTML($resArray[$Curlvl1][$this->lvl1Title], $lvl2Total, $lvl2HTML, $short);
                    //addlvl 2 total to lvl1
                    $lvl1Total+=$lvl2Total;
                    //empty lvl 2 total tyo start anew
                    $lvl2HTML = '';
                    $lvl2Total = 0;             }
            }
            
            $lvl0HTML .= $this->getLvl0HTML($resArray[$Curlvl0][$this->lvl0Title], $lvl1Total, $lvl1HTML, $short);
            $lvl0Total+=$lvl1Total;
// make the whole result
            $HTMLRes .= $lvl0HTML;
        } // end is numtasktime
        return $HTMLRes;
    }
    /**
     *
     * @global object $conf conf object
     * @global object $langs lang object
     * @param string $model   file model(excel excel2007 pdf)
     * @param bool $save    will save the export on the project/user folder or not
     * @return string   filename
     */
    public function buildFile($model = 'excel2017', $save = false)
    {
        if($model == 'excel2017' || $model == 'excel'){
            if(!(extension_loaded('zip') && extension_loaded('xml'))){
                $this->error = "missing php extention(xml or zip)";
                dol_syslog("Export::build_file Error: ".$this->error, LOG_ERR);
                return -1;
            }
        }
        global $conf,$langs,$user;
        $dir = DOL_DOCUMENT_ROOT . "/core/modules/export/";
        $file = "export_".$model.".modules.php";
        $classname = "Export".ucfirst($model);
        require_once $dir.$file;
	$objmodel = new $classname($this->db);
        $arrayTitle = array('projectRef' => 'projectRef', 'projectTitle' => 'projectTitle', 'taskRef' => 'taskRef', 'tasktitle' => 'taskTitle', 'dateDisplay' => 'Date', 'durationHours' => 'Hours', 'durationDays' => 'Days', 'userId' => 'userId', 'firstName' => 'Firstname', 'lastName' => 'Lastname', 'note' => 'Note', 'invoiceable' => 'Invoiceable');
        $arrayTypes = array('projectRef' => 'TextAuto', 'projectTitle' => 'TextAuto', 'taskRef' => 'TextAuto', 'tasktitle' => 'TextAuto', 'dateDisplay' => 'Date', 'durationHours' => 'TextAuto', 'durationDays' => 'Numeric', 'userId' => 'Numeric', 'firstName' => 'TextAuto', 'lastName' => 'TextAuto', 'note' => 'TextAuto', 'invoiceable' => 'Numeric');
        $arraySelected = array('projectRef' => 'projectRef', 'projectTitle' => 'projectTitle', 'taskRef' => 'taskRef', 'tasktitle' => 'tasktitle', 'userId' => 'userId', 'firstName' => 'firstName', 'lastName' => 'lastName', 'dateDisplay' => 'date', 'durationHours' => 'durationHours', 'durationDays' => 'durationDays', 'note' => 'note', 'invoiceable' => 'invoiceable');

        $resArray = $this->getReportArray();

        if(is_array($resArray))
        {
            //$dirname=$conf->timesheet->dir_output.'/reports';
            //
            $dirname=$conf->export->dir_temp.'/'.$user->id;
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
            $outputlangs = clone $langs; // We clone to have an object we can modify(for example to change output charset by csv handler) without changing original value
            // Open file
            dol_mkdir($dirname);
            $result = $objmodel->open_file($dirname."/".$filename, $outputlangs);//FIXME
            if($result >= 0)
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
                    //$object->date=dol_print_date($object->date);
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
/** Generate LVL HTML report
 *
 * @param string $lvl2title title to show
 * @param int $lvl3Total    total to show
 * @param string $lvl3HTML  LV3 content to embed
 * @param bool $short       hide lvl3
 * @param string $lvl3Notes Lvl3 in case lvl3 details is hiden
 * @return string
 */
    public function getLvl2HTML($lvl2title, $lvl3Total, $lvl3HTML, $short, $lvl3Notes)
    {
        $lvl2HTML .= '<tr class = "oddeven" align = "left"><td colspan="2"></td><td>'
            .$lvl2title.'</td>';
        // add an empty cell on row if short version(in none short mode tdere is an additionnal column
        if(!$short)$lvl2HTML .= '<td></td>';
        // add tde LVL 3 total hours on tde LVL 2 title
        $lvl2HTML .= '<td>'.formatTime($lvl3Total, 0).'</td>';
        // add tde LVL 3 total day on tde LVL 2 title
        $lvl2HTML .= '<td>'.formatTime($lvl3Total, -3).'</td><td>';
        if($short) {
            $lvl2HTML .= $lvl3Notes;
        }
        $lvl2HTML .= '</td></tr>';
        //add the LVL 3 content(details)
        $lvl2HTML .= $lvl3HTML;
        return $lvl2HTML;
    }
/** Generate LVL HTML report
 *
 * @param string $lvl1title title to show
 * @param int $lvl2Total    total to show
 * @param string $lvl2HTML  LV3 content to embed
 * @param bool $short       hide lvl3
 * @return string
 */
    public function getLvl1HTML($lvl1title, $lvl2Total, $lvl2HTML, $short)
    {
        $lvl1HTML .= '<tr class = "oddeven" align = "left"><th colspan="1"></th><th >'
            .$lvl1title.'</th><th></th>';
        // add an empty cell on row if short version(in none short mode there is an additionnal column
        if(!$short)$lvl1HTML .= '<th></th>';
        $lvl1HTML .= '<th>'.formatTime($lvl2Total, 0).'</th>';
        $lvl1HTML .= '<th>'.formatTime($lvl2Total, -3).'</th></th><th></tr>';
        //add the LVL 3 HTML content in lvl1
        $lvl1HTML .= $lvl2HTML;
         //empty lvl 3 HTML to start anew
        return $lvl1HTML;
    }
/** Generate LVL HTML report
 *
 * @param string $lvl0title title to show
 * @param int $lvl1Total    total to show
 * @param string $lvl1HTML  LV3 content to embed
 * @param bool $short       hide lvl3
 * @return string
 */
    public function getLvl0HTML($lvl0title, $lvl1Total, $lvl1HTML, $short)
    {
        // make the whole result
            $lvl0HTML .= $this->getHTMLReportHeaders();
            $lvl0HTML .= '<tr class = "liste_titre"><th>'.$lvl0title.'<th>';
            $lvl0HTML .=((!$short)?'<th></th>':'').'<th > TOTAL</th>';
            $lvl0HTML .= '<th>'.formatTime($lvl1Total, 0).'</th>';
            $lvl0HTML .= '<th>'.formatTime($lvl1Total, -3).'</th><th></th></tr>';
           //add the LVL 3 HTML content in lvl1
            $lvl0HTML .= $lvl1HTML;
            $lvl0HTML .= '</table>';
        return $lvl0HTML;
    }

/** Generate LVL HTML report
 *
 * @param array $item value to display
 * @return string
 */
    public function getLvl3HTML($item)
    {
        $lvl3HTML .= '<tr class = "oddeven" align = "left"><td colspan="3" ></td><td>'
            .$item[$this->lvl3Title].'</td><td>';
        $lvl3HTML .= $item['durationHours'].'</td><td>';
        $lvl3HTML .= $item['durationDays'].'</td><td>';
        $lvl3HTML .= $item['note'];
        $lvl3HTML .= '</td></tr>';
        return $lvl3HTML;
    }
}
