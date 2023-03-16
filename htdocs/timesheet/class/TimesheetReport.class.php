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
require_once DOL_DOCUMENT_ROOT.'/projet/class/task.class.php';
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
    public $short;
    public $ungroup;
    public $invoicedCol;
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
    public function initBasic($projectid, $userid, $name, $startDate, $stopDate, $mode, $invoiceableOnly = '0',$short = 0,$invoicedCol = 0,$ungroup = 0, $taskarray = null)
    {
        global  $conf, $user, $langs;
        if ($userid && !$user->admin && empty($projectid)){
            // FIXME check is the $use is a N+1 or specific rights
        } elseif ($projectid && !$user->admin){
            //FIXME check that the user has project rights
        }
        
        $first = false;
        $this->ref=array();
        $this->invoiceableOnly = $invoiceableOnly;
        $this->taskarray = $taskarray;
        $this->projectid = $projectid;// coul
        $this->userid = $userid;// coul
        if ($userid && !is_array($userid)) {
            $this->user[$userid] = new User($this->db);
            $this->user[$userid]->fetch($userid);
            if (count($this->ref) == 0){
                $this->ref[$userid]= $this->user[$userid]->lastname.' '.$this->user[$userid]->firstname;
            }
        } elseif (is_array($userid)) {
            foreach ($userid as $id){
                $this->user[$id] = new User($this->db);
                $this->user[$id]->fetch($id);
                if (count($this->ref) == 0){
                    $this->ref[$id]= $this->user[$id]->lastname.' '.$this->user[$id]->firstname;
                }
            }
        }
        $this->startDate = $startDate;
        $this->stopDate = $stopDate;
        $this->mode = $mode;
        $this->short = $short;
        $this->ungroup = $ungroup;
        $this->invoicedCol = $invoicedCol;
        if ( $name <> ''){
            $this->name =  $name;
        }else if (count($this->ref) == 1){
            $this->name .= reset($this->ref);
        }else{
            $this->name = '';
        }
        
        
        $this->name .= ', '.str_replace('/', '-', dol_print_date($startDate, 'day')).','
            .str_replace('/', '-', dol_print_date($stopDate, 'day'));
        switch($mode){
            case 'PDT': //project  / task / Days //FIXME dayoff missing
                $this->modeSQLOrder = 'ORDER BY ptt.fk_user,tsk.fk_projet, DATE(ptt.task_datehour), tsk.rowid ASC   ';
                //title
                $this->lvl0Title = 'userName';
                $this->lvl1Title = 'projectLabel';
                $this->lvl2Title = 'dateDisplay';
                $this->lvl3Title = 'taskLabel';
                //links
                $this->lvl0Link = 'userLink';
                $this->lvl1Link = 'projectLink';
                $this->lvl2Link = 'dateDisplay';
                $this->lvl3Link = 'taskLink';
                //keys
                $this->lvl0Key = 'userId';
                $this->lvl1Key = 'projectId';
                $this->lvl2Key = 'dateDisplay';
                break;
            case 'DPT'://day /project /task
                $this->modeSQLOrder = 'ORDER BY ptt.fk_user,DATE(ptt.task_datehour), tsk.fk_projet, tsk.rowid ASC   ';
                //title
                $this->lvl0Title = 'userName';
                $this->lvl1Title = 'dateDisplay';
                $this->lvl2Title = 'projectLabel';
                $this->lvl3Title = 'taskLabel';
                //links
                $this->lvl0Link = 'userLink';
                $this->lvl1Link = 'dateDisplay';
                $this->lvl2Link = 'projectLink';
                $this->lvl3Link = 'taskLink';
                //keys
                $this->lvl0Key = 'userId';
                $this->lvl1Key = 'dateDisplay';
                $this->lvl2Key = 'projectId';
                break;
            case 'PTD'://day /project /task
                $this->modeSQLOrder = 'ORDER BY ptt.fk_user,tsk.fk_projet, tsk.rowid, DATE(ptt.task_datehour) ASC   ';
                //title
                $this->lvl0Title = 'userName';
                $this->lvl1Title = 'projectLabel';
                $this->lvl2Title = 'taskLabel';
                $this->lvl3Title = 'dateDisplay';
                //links
                $this->lvl0Link = 'userLink';
                $this->lvl1Link = 'projectLink';
                $this->lvl2Link = 'taskLink';
                $this->lvl3Link = 'dateDisplay';
                //keys
                $this->lvl0Key = 'userId';
                $this->lvl1Key = 'projectId';
                $this->lvl2Key = 'taskId';
                break;
            case 'UDT': //project  / task / Days //FIXME dayoff missing
                $this->modeSQLOrder = 'ORDER BY tsk.fk_projet,ptt.fk_user, DATE(ptt.task_datehour), tsk.rowid ASC   ';
                //title
                $this->lvl0Title = 'projectLabel';
                $this->lvl1Title = 'userName';
                $this->lvl2Title = 'dateDisplay';
                $this->lvl3Title = 'taskLabel';
                //links
                $this->lvl0Link= 'projectLink';
                $this->lvl1Link = 'userLink';
                $this->lvl2Link = 'dateDisplay';
                $this->lvl3Link = 'taskLink';
                //keys
                $this->lvl0Key = 'projectId';
                $this->lvl1Key = 'userId';
                $this->lvl2Key = 'dateDisplay';
                break;
            case 'DUT'://day /project /task
                $this->modeSQLOrder = 'ORDER BY tsk.fk_projet,DATE(ptt.task_datehour), ptt.fk_user, tsk.rowid ASC   ';
                //title
                $this->lvl0Title = 'projectLabel';
                $this->lvl1Title = 'dateDisplay';
                $this->lvl2Title = 'userName';
                $this->lvl3Title = 'taskLabel';
                //links
                $this->lvl0Link = 'projectLink';
                $this->lvl1Link = 'dateDisplay';
                $this->lvl2Link = 'userLink';
                $this->lvl3Link = 'taskLink';
                //keys
                $this->lvl0Key = 'projectId';
                $this->lvl1Key = 'dateDisplay';
                $this->lvl2Key = 'userId';
                break;
            case 'UTD'://day /project /task
                $this->modeSQLOrder = ' ORDER BY tsk.fk_projet,ptt.fk_user, tsk.rowid, DATE(ptt.task_datehour) ASC   ';
                $this->lvl0Title='projectLabel';
                $this->lvl1Title = 'userName';
                $this->lvl2Title = 'taskLabel';
                $this->lvl3Title = 'dateDisplay';
                //links
                $this->lvl0Link = 'projectLink';
                $this->lvl1Link = 'userLink';
                $this->lvl2Link = 'taskLink';
                $this->lvl3Link = 'dateDisplay';
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
     * @param   int    $forceGroup   will return only the invoicable task
     * @return array()
     */
    public function  getReportArray($forceGroup = false)
    {
        global $conf;
        $resArray = array();
        $first = true;

        $sql = 'SELECT tsk.fk_projet as projectid, ptt.fk_user  as userid, tsk.rowid as taskid, ';
        if (version_compare(DOL_VERSION, "4.9.9") >= 0) {
            $sql .= ' (ptt.invoice_id > 0 or ptt.invoice_line_id>0)  AS invoiced,';
        }else{
            $sql .= ' 0 AS invoiced,';
        }
        if ($forceGroup == 1){
            if ($this->db->type!='pgsql') {
                $sql .= " MAX(ptt.rowid) as id, GROUP_CONCAT(ptt.note SEPARATOR '. ') as note, MAX(tske.invoiceable) as invoicable, ";
            } else {
                $sql .= " MAX(ptt.rowid) as id, STRING_AGG(ptt.note, '. ') as note, MAX(tske.invoiceable) as invoicable, ";
            }
            $sql .= ' DATE(ptt.task_datehour) AS task_date, SUM(ptt.task_duration) as duration ';
        }else{
            $sql .= " ptt.rowid as id, ptt.note  as note, tske.invoiceable as invoicable, ";
            $sql .= ' DATE(ptt.task_datehour) AS task_date, ptt.task_duration as duration ';
        } 
        $sql .= ' FROM '.MAIN_DB_PREFIX.'projet_task_time as ptt ';
        $sql .= ' JOIN '.MAIN_DB_PREFIX.'projet_task as tsk ON tsk.rowid = fk_task ';
        $sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'projet_task_extrafields as tske ON tske.fk_object = fk_task ';
        $sql .= ' WHERE ';
        if (!empty($this->userid)) {
            $sql .= ' ptt.fk_user IN (\''.implode("','", array_keys($this->user)).'\') ';
            $first = false;
        }
        if (!empty($this->projectid)) {
            $sql .= ($first?'':'AND ').'tsk.fk_projet IN (\''.implode("','", $this->projectid).'\') ';
            $first = false;
        }
        if (is_array($this->taskarray) && count($this->taskarray)>1) {
            $sql .= ($first?'':'AND ').'tsk.rowid in ('.explode($this->taskarray, ', ').') ';
        }
        if ($this->invoiceableOnly == 1) {
            $sql .= ($first?'':'AND ').'tske.invoiceable = \'1\'';
        }

        $sql .= ($first?'':'AND ').' DATE(task_datehour) >= \''.$this->db->idate($this->startDate).'\'';
        $sql .= ' AND DATE(task_datehour) <= \''.$this->db->idate($this->stopDate).'\'';
        $sql .= ' AND (ptt.task_duration > 0 or LENGTH(ptt.note)>0)';
        if ($forceGroup == 1)$sql .= ' GROUP BY ptt.fk_user,  tsk.fk_projet, tsk.rowid, DATE(ptt.task_datehour), (ptt.invoice_id > 0 or ptt.invoice_line_id>0)';
        $sql .= $this->modeSQLOrder;
        dol_syslog(__METHOD__, LOG_DEBUG);
        $resql = $this->db->query($sql);
        if ($resql) {
            $numTaskTime = $this->db->num_rows($resql);
            $i = 0;
            $odlpjtid=0;
            $objpjt = new Project($this->db);
            $odltskid=0;
            $objtsk = new Task($this->db);
            $odlusrid=0;
            $objusr = new User($this->db);
            $oldsocid = 0;
            $objsoc = new Societe($this->db);
            // Loop on each record found,
            while($i < $numTaskTime)
            {
                $error = 0;
                $obj = $this->db->fetch_object($resql);
                // fetch user
                if ($odlusrid != $obj->userid){
                    $objusr->fetch($obj->userid);
                    $odlusrid = $obj->userid;
                } 
                // fecth taks                    
                if ($odltskid != $obj->taskid){
                    $objtsk->fetch($obj->taskid);
                    $odltskid = $obj->taskid;
                } 
                // fetch project 
                if ($odlpjtid != $obj->projectid){
                    $objpjt->fetch($obj->projectid);
                    $odlpjtid = $obj->projectid;
                }
                if ($oldsocid != $objpjt->socid && $objpjt->socid > 0){  
                    $objsoc->fetch($objpjt->socid);
                }
                # save the project info
                if(!isset($this->project[$objpjt->id])){
                    $this->project[$objpjt->id] = $objpjt;
                    $this->ref[$objpjt->id] = $objpjt->ref.((getConf('TIMESHEET_HIDE_REF') == 1)
                        ?'':' - '.$objpjt->title);
                    $first = true;
                    $this->thirdparty[$objpjt->id] = new Societe($this->db);
                    $this->thirdparty[$objpjt->id]->fetch($objpjt->id);
                }

                //update third party
                
                $resArray[$obj->id] = array('projectId' => $obj->projectid,
                    'projectLabel' => $objpjt->ref.((getConf('TIMESHEET_HIDE_REF') == false)?'':' - '.$objpjt->title),
                    'projectRef' => $objpjt->ref,
                    'projectTitle' => $objpjt->title,
                    'projectLink' => $objpjt->getNomUrl(0, '', getConf('TIMESHEET_HIDE_REF')),
                    'taskId' => $obj->taskid,
                    'taskLabel' => $objtsk->ref.((getConf('TIMESHEET_HIDE_REF') == false)?'':' - '.$objtsk->label),
                    'taskRef' => $objtsk->ref,
                    'taskLink' => $objtsk->getNomUrl(0, "withproject", "task", getConf('TIMESHEET_HIDE_REF')),
                    'tasktitle' => $objtsk->label,
                    'date' => $this->db->jdate($obj->task_date),
                    'dateDisplay' => dol_print_date($this->db->jdate($obj->task_date), 'day'),
                    'duration' => $obj->duration,
                    'durationHours' => formatTime($obj->duration, 0),
                    'durationDays' => formatTime($obj->duration, -3),
                    'userId' => $obj->userid,
                    'userName' => trim($objusr->firstname).' '.trim($objusr->lastname),
                    'firstName' => trim($objusr->firstname),
                    'lastName' => trim($objusr->lastname),
                    'userLink' => $objusr->getNomUrl(0),
                    'note' =>($obj->note),
                    'invoiceable' => (isset($obj->invoiceable) && $obj->invoiceable==1)?'1':'0',
                    'invoiced' => ($obj->invoiced==1)?'1':'0',
                    'socname' => $objpjt->socid>0?($objsoc->code_client != ''? $objsoc->code_client.' - ':'').$objsoc->getNomUrl():''  
                    );
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
        $resArray = $this->getReportArray(!($this->ungroup ==1));
        $titleWidth = array();
        $HTMLRes = '<h1>'.$this->name.'</h1>';
        $HTMLRes .= $this->getHTMLReportHeaders();
        foreach ($resArray as $key => $item) {
           $item['date'] = dol_print_date($item['date'], 'day');
           $HTMLRes .= '<tr class = "oddeven" align = "left">';//<th width = "200px">'.$this->name.'</th>';
           $HTMLRes .= '<th '.(isset($titleWidth[$this->lvl0Title])?'width = "'
            .$titleWidth[$this->lvl0Title].'"':'').'>'.$item[$this->lvl0Title].'</th>';
           $HTMLRes .= '<th '.(isset($titleWidth[$this->lvl1Title])?'width = "'
            .$titleWidth[$this->lvl1Title].'"':'').'>'.$item[$this->lvl1Title].'</th>';
           $HTMLRes .= '<th '.(isset($titleWidth[$this->lvl2Title])?'width = "'
            .$titleWidth[$this->lvl2Title].'"':'').'>'.$item[$this->lvl2Title].'</th>';
           $HTMLRes .= '<th '.(isset($titleWidth[$this->lvl3Title])?'width = "'
            .$titleWidth[$this->lvl3Title].'"':'').'>'.$item[$this->lvl3Title].'</th>';
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
        global $langs,$conf;;
        $HTMLHeaders = '<h1>'.$this->name.'</h1>';
        $title = array('projectLabel' => 'Project', 'dateDisplay' => 'Day', 'taskLabel' => 'Tasks', 'userName' => 'User');
        $titleWidth = array('4' => '120', '7' => '200');
        $HTMLHeaders = '<table class = "noborder" width = "100%">';
        $HTMLHeaders .= '<tr class = "liste_titre">';//<th>'.$langs->trans('Name').'</th>';
        $HTMLHeaders .= '<th>'.$langs->trans($title[$this->lvl0Title]).'</th>';
        $HTMLHeaders .= '<th>'.$langs->trans($title[$this->lvl1Title]).'</th>';
        $HTMLHeaders .= '<th>'.$langs->trans($title[$this->lvl2Title]).'</th>';
        if ($this->short == 0)$HTMLHeaders .= '<th>'.$langs->trans($title[$this->lvl3Title]).'</th>';
        $HTMLHeaders .= '<th>'.$langs->trans('Duration').':'.$langs->trans('hours').'</th>';
        $HTMLHeaders .= '<th>'.$langs->trans('Duration').':'.$langs->trans('Days').'</th>';
        $HTMLHeaders .= '<th>'.$langs->trans('Note').'</th>';
        if ($this->invoicedCol == 1) $HTMLHeaders .= '<th>'.$langs->trans('Invoiced').'</th></tr>';
        $HTMLHeaders .= '</tr>';
        return $HTMLHeaders;
    }

    /**
    *  Function to generate HTML for the report
    * @param   string  $periodTitle   title of the period
    * @return string
    * mode layout PTD project/task /day, PDT, DPT
    * periodeTitle give a name to the report
    * timemode show time using day or hours(== 0)
    */
    public function getHTMLreport( $periodTitle)
    {
    // HTML buffer
        global $langs,$conf;
        $HTMLRes = '';
        $lvl0HTML = $lvl1HTML = $lvl3HTML = $lvl2HTML = '';
        // partial totals
        $lvl3SubTotal = $lvl3Total = $lvl2Total = $lvl1Total = $lvl0Total = 0;
        $Curlvl0 = $Curlvl1 = $Curlvl2 = $Curlvl3 = 0;
        $lvl3Notes = "";
        $lvl3SubNotes = "";
        $lvl3SubInvoiced = '';
        $sqltail = '';
        $resArray = $this->getReportArray(!($this->ungroup ==1));
        $numTaskTime = count($resArray);
        $i = 0;
        $Curlvl0 = 0; // related to get array
        $Curlvl1 = 0;
        $Curlvl2 = 0;
        $Curlvl3 = '';
        $lvl3SubPrev = 0;
        if ($numTaskTime > 0){
            // current
            foreach ($resArray as $key => $item) {
                if ($i == 0){
                    $Curlvl0 = $key; // related to get array
                    $Curlvl1 = $key;
                    $Curlvl2 = $key;
                    $Curlvl3 = $item[$this->lvl3Title];
                }

                // reformat date to avoid UNIX time
                //add the LVL 2 total when  change detected in Lvl 2 & 1 &0
                if (($resArray[$Curlvl2][$this->lvl2Key] != $item[$this->lvl2Key])
                        || ($resArray[$Curlvl1][$this->lvl1Key] != $item[$this->lvl1Key])
                        || ($resArray[$Curlvl0][$this->lvl0Key] != $item[$this->lvl0Key]))
                {
                    //Link, total,short, lvl3Html, lvl3 notes
                    $lvl2HTML .= $this->getLvl2HTML($resArray[$Curlvl2][$this->lvl2Link], 
                        $lvl3Total, $lvl3HTML, $lvl3Notes);
                    //empty lvl 3 Notes to start anew
                    $lvl3Notes = '';
                    //empty lvl 3 HTML to start anew
                    $lvl3HTML = '';
                    // empty lvl3 invoiced
                    $lvl3Invoice = '';
                    //add the LVL 3 total to LVL3
                    $lvl2Total += $lvl3Total;
                    //empty lvl 3 total to start anew
                    $lvl3Total = 0;
                    // save the new lvl2 ref
                    $Curlvl2 = $key;
                    //creat the LVL 1 Link line when lvl 1 or 0 change detected
                    if (($resArray[$Curlvl1][$this->lvl1Key] != $item[$this->lvl1Key])
                            ||($resArray[$Curlvl0][$this->lvl0Key] != $item[$this->lvl0Key]))
                    {
                        $lvl1HTML .= $this->getLvl1HTML($resArray[$Curlvl1][$this->lvl1Link], 
                            $lvl2Total, $lvl2HTML);
                        //addlvl 2 total to lvl1
                        $lvl1Total += $lvl2Total;
                        //empty lvl 2 total tyo start anew
                        $lvl2HTML = '';
                        $lvl2Total = 0;
                        // save the new lvl1 ref
                        $Curlvl1 = $key;
                        //creat the LVL 0 Link line when lvl  0 change detected
                        if (($resArray[$Curlvl0][$this->lvl0Key]!=$item[$this->lvl0Key]))
                        {
                           $lvl0HTML .= $this->getLvl0HTML($resArray[$Curlvl0][$this->lvl0Link], 
                            $lvl1Total, $lvl1HTML);
                           //addlvl 2 total to lvl1
                           $lvl0Total += $lvl1Total;
                           //empty lvl 2 total tyo start anew
                           $lvl1HTML = '';
                           $lvl1Total = 0;
                           // save the new lvl1 ref
                           $Curlvl0 = $key;
                        }
                    }
                }

                // add lvl3 lines
                if (!empty($item['note'])) {
                    if (strlen($lvl3Notes)>0) $lvl3Notes .= "<br>";
                    $lvl3Notes .= $item['note'];
                }
                $lvl3Total += $item['duration'];
                // show the LVL 3 only if not short
                if ($this->short == 0) {
                    /*if ($this->ungroup == 0){
                        // erase previous value 
                        if ($item[$this->lvl3Title] != $Curlvl3 
                        || ($resArray[$Curlvl2][$this->lvl2Key] != $item[$this->lvl2Key])
                        || ($resArray[$Curlvl1][$this->lvl1Key] != $item[$this->lvl1Key])
                        || ($resArray[$Curlvl0][$this->lvl0Key] != $item[$this->lvl0Key])){
                            $lvl3HTML .= $this->getLvl3HTML($item[$this->lvl3Link], $lvl3SubTotal, $lvl3SubNotes, $lvl3SubInvoiced);
                            $Curlvl3 = $item[$this->lvl3Title];
                            $lvl3SubTotal = 0;
                            $lvl3SubNotes = '';
                            $lvl3SubPrev = 0;
                            $lvl3SubInvoiced = '';
                        }
                        if (is_numeric($lvl3SubInvoiced)>0){
                            $lvl3SubInvoiced = 'id['.$lvl3SubPrev.']:'.$lvl3SubInvoiced."<br>";
                            $lvl3SubInvoiced .= 'id['.$key.']:'.$item['invoiced'];
                        }elseif (strlen($lvl3SubInvoiced)>0)
                        {
                            $lvl3SubInvoiced .= "<br>";
                            $lvl3SubInvoiced .= 'id['.$key.']:'.$item['invoiced'];
                        }else{
                            $lvl3SubInvoiced = $item['invoiced'];
                        }
                        $lvl3SubPrev = $key;
                        $lvl3SubTotal += $item['duration'];
                        if (strlen($lvl3SubNotes)>0) $lvl3SubNotes .= "<br>";
                        $lvl3SubNotes .= $item['note'];
                    }else{ */
                        $lvl3HTML .= $this->getLvl3HTML($item[$this->lvl3Link], 
                            $item['duration'], $item['note'], $item['invoiced']);
                    //}

                } 
                $i++;
                // show the last block
                if ( $i == $numTaskTime){
                    //if ( $this->short == 0) $lvl3HTML .= $this->getLvl3HTML($item[$this->lvl3Link], $lvl3SubTotal, $lvl3SubNotes, $lvl3SubInvoiced); 
                    $lvl2HTML .= $this->getLvl2HTML($resArray[$Curlvl2][$this->lvl2Link], 
                        $lvl3Total, $lvl3HTML, $lvl3Notes);
                    //empty lvl 3 Notes to start anew
                    $lvl3Notes = '';
                    //empty lvl 3 HTML to start anew
                    $lvl3HTML = '';
                    //add the LVL 3 total to LVL3
                    $lvl2Total += $lvl3Total;
                    //empty lvl 3 total to start anew
                    $lvl3Total = 0;
                  //creat the LVL 1 Link line
                    $lvl1HTML .= $this->getLvl1HTML($resArray[$Curlvl1][$this->lvl1Link], 
                        $lvl2Total, $lvl2HTML);
                    //addlvl 2 total to lvl1
                    $lvl1Total += $lvl2Total;
                    //empty lvl 2 total tyo start anew
                    $lvl2HTML = '';
                    $lvl2Total = 0;             }
            } 
            
            $lvl0HTML .= $this->getLvl0HTML($resArray[$Curlvl0][$this->lvl0Link], 
                $lvl1Total, $lvl1HTML);
            $lvl0Total += $lvl1Total;
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
    public function buildFile($model = 'excel2017new', $save = false)
    {
        if ($model == 'excel2017new' ||$model == 'excel2017' || $model == 'excel'){
            if (!(extension_loaded('zip') && extension_loaded('xml'))){
                $this->error = "missing php extention(xml or zip)";
                dol_syslog(__METHOD__."::build_file Error: ".$this->error, LOG_ERR);
                return -1;
            }
        }
        global $conf, $langs, $user;
        $dir = DOL_DOCUMENT_ROOT . "/core/modules/export/";
        $file = "export_".$model.".modules.php";
        $classname = "Export".ucfirst($model);
        require_once $dir.$file;
	    $objmodel = new $classname($this->db);
        $arrayTitle = array('projectRef' => 'projectRef', 'projectTitle' => 'projectTitle', 
            'taskRef' => 'taskRef', 'tasktitle' => 'taskTitle', 'dateDisplay' => 'Date', 
            'durationHours' => 'Hours', 'durationDays' => 'Days', 'userId' => 'userId', 
            'firstName' => 'Firstname', 'lastName' => 'Lastname', 'note' => 'Note', 
            'invoiceable' => 'Invoiceable','invoiced' => 'Invoiced', 'socname' => 'ThirdParty' );
        $arrayTypes = array('projectRef' => 'TextAuto', 'projectTitle' => 'TextAuto', 
            'taskRef' => 'TextAuto', 'tasktitle' => 'TextAuto', 'dateDisplay' => 'Date', 
            'durationHours' => 'TextAuto', 'durationDays' => 'Numeric', 'userId' => 'Numeric', 
            'firstName' => 'TextAuto', 'lastName' => 'TextAuto', 'note' => 'TextAuto', 
            'invoiceable' => 'Numeric','invoiced' => 'TextAuto', 'socname' => 'TextAuto');
        $arraySelected = array('projectRef' => 'projectRef', 'projectTitle' => 'projectTitle', 
            'taskRef' => 'taskRef', 'tasktitle' => 'tasktitle', 'userId' => 'userId', 
            'firstName' => 'firstName', 'lastName' => 'lastName', 'dateDisplay' => 'date', 
            'durationHours' => 'durationHours', 'durationDays' => 'durationDays', 'note' => 'note', 
            'invoiceable' => 'invoiceable','invoiced' => 'invoiced', 'socname' => 'socname');

        $resArray = $this->getReportArray(!($this->ungroup ==1));

        if (is_array($resArray))
        {
            //$dirname = $conf->timesheet->dir_output.'/reports';
            //
            $dirname = $conf->export->dir_temp.'/'.$user->id;
            /*
            if ($save){
                $dirname = $conf->user->dir_output."/".$this->userid.'/reports';
                if ($this->projectid > 0){
                    $this->project = new Project($this->db);
                    $this->project->fetch($projectid);
                    $dirname = $conf->projet->dir_output.'/'.dol_sanitizeFileName($project->ref).'/reports';
                }
            } else{
                $dirname = $conf->timesheet->dir_output.'/reports';
            }*/
            $filename = "report.".$objmodel->getDriverExtension();
                    //str_replace(array('/', ' ', "'", '"', '&', '?'), '_', $this->ref).'.'.$objmodel->getDriverExtension();
            $outputlangs = clone $langs; // We clone to have an object we can modify(for example to change output charset by csv handler) without changing original value
            // Open file
            dol_mkdir($dirname);
            $result = $objmodel->open_file($dirname."/".$filename, $outputlangs);//FIXME
            if ($result >= 0)
            {

                // Genere en-tete
                $objmodel->write_header($outputlangs);
                // Genere ligne de titre
                $objmodel->write_title($arrayTitle, $arraySelected, $outputlangs, $arrayTypes);
                foreach ($resArray as $row)
                {
                    $object = (object) $row;
                   /* $object = new stdClass();
                    foreach ($row as $key => $value){
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
                dol_syslog(__METHOD__."::build_file Error: ".$this->error, LOG_ERR);
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
 * @param string $lvl3Notes Lvl3 in case lvl3 details is hiden
 * @return string
 */
    public function getLvl2HTML($lvl2title, $lvl3Total, $lvl3HTML, $lvl3Notes) 
    {
        global $conf;
        $lvl2HTML = '<tr class = "oddeven" align = "left"><td colspan="2"></td><td>'
            .$lvl2title.'</td>';
        // add an empty cell on row if short version(in none short mode tdere is an additionnal column
        if ($this->short == 0)$lvl2HTML .= '<td></td>';
        // add tde LVL 3 total hours on tde LVL 2 title
        $lvl2HTML .= '<td>'.formatTime($lvl3Total, 0).'</td>';
        // add tde LVL 3 total day on tde LVL 2 title
        $lvl2HTML .= '<td>'.formatTime($lvl3Total, -3).'</td><td>';
        if ($this->short == 1) {
            $lvl2HTML .= $lvl3Notes;

        }
        if ($this->invoicedCol){
            $lvl2HTML .= "</td><td>";
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
    public function getLvl1HTML($lvl1title, $lvl2Total, $lvl2HTML)
    {
        global $conf;
        $lvl1HTML = '<tr class = "oddeven" align = "left"><th colspan="1"></th><th >'
            .$lvl1title.'</th><th></th>';
        // add an empty cell on row if short version(in none short mode there is an additionnal column
        if ($this->short == 0)$lvl1HTML .= '<th></th>';
        $lvl1HTML .= '<th>'.formatTime($lvl2Total, 0).'</th>';
        $lvl1HTML .= '<th>'.formatTime($lvl2Total, -3).'</th><th></th>';
        if ($this->invoicedCol == 1){
            $lvl1HTML .= "<th></th>";
        }
        $lvl1HTML .= '</tr>';
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
 * @return string
 */
    public function getLvl0HTML($lvl0title, $lvl1Total, $lvl1HTML)
    {
        global $conf;
        // make the whole result
        $lvl0HTML = $this->getHTMLReportHeaders();
        $lvl0HTML .= '<tr class = "liste_titre"><th>'.$lvl0title.'<th>';
        $lvl0HTML .= (($this->short == 0)?'<th></th>':'').'<th > TOTAL</th>';
        $lvl0HTML .= '<th>'.formatTime($lvl1Total, 0).'</th>';
        $lvl0HTML .= '<th>'.formatTime($lvl1Total, -3).'</th><th></th>';
        if ($this->invoicedCol){
            $lvl0HTML .= "<th></th>";
        }
        $lvl0HTML .= '</tr>';
        //add the LVL 3 HTML content in lvl1
        $lvl0HTML .= $lvl1HTML;
        $lvl0HTML .= '</table>';
        return $lvl0HTML;
    }

/** Generate LVL HTML report
 *
 * @param string $lvl3title title to show
 * @param int $lvl3Total    total to show
 * @param string $lvl3note  LV3 cnotes
 * @param string $lvl3Invoiced  LV3 invoiced
 * @return string
 */
    public function getLvl3HTML($lvl3title, $lvl3Total, $lvl3Note, $lvl3Invoiced)
    {
        global $conf;
        $lvl3HTML = '';
        $lvl3HTML .= '<tr class = "oddeven" align = "left"><td colspan="3" ></td><td>'
            .$lvl3title.'</td>';
        $lvl3HTML .= '<td>'.formatTime($lvl3Total, 0).'</td>';
        $lvl3HTML .= '<td>'.formatTime($lvl3Total, -3).'</td>';
        $lvl3HTML .= '<td>'.$lvl3Note.'</td>';;
        if ($this->invoicedCol == 1){
            $lvl3HTML .= "<td>".$lvl3Invoiced.'</td>';
        }
        $lvl3HTML .= '</tr>';
        return $lvl3HTML;
    }
}
