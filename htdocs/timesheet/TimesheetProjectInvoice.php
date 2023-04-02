<?php
 /* Copyright (C) 2017 delcroip <patrick@pmpd.eu>
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
/*
define('getConf('TIMESHEET_INVOICE_METHOD')', 'user');
define('getConf('TIMESHEET_INVOICE_TASKTIME')', 'user');
define('getConf('TIMESHEET_INVOICE_SERVICE')', '1');
define('getConf('TIMESHEET_INVOICE_SHOW_TASK')', '1');
define('getConf('TIMESHEET_INVOICE_SHOW_USER')', '1');
*/
//load class
include 'core/lib/includeMain.lib.php';
include 'core/lib/generic.lib.php';
include 'core/lib/timesheet.lib.php';
$token = getToken();
require_once DOL_DOCUMENT_ROOT .'/core/lib/functions.lib.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/project.lib.php';
$PHP_SELF = $_SERVER['PHP_SELF'];
//get param
$staticProject = new Project($db);
$projectId = GETPOST('projectid', 'int');
$propalId = GETPOST('propalid', 'int');
$socid = GETPOST('socid', 'int');
//$month = GETPOST('month', 'alpha');
//$year = GETPOST('year', 'int');
$mode = GETPOST('invoicingMethod', 'alpha');
$step = GETPOST('step', 'alpha');
$ts2Invoice = GETPOST('ts2Invoice', 'alpha');
$tsNotInvoiced = GETPOST('tsNotInvoiced', 'alpha');
$userid = is_object($user)?$user->id:$user;
//init handling object
$form = new Form($db);
$dateStart = strtotime(GETPOST('startDate', 'alpha'));
$dateStartday = GETPOST('startDateday', 'int');// to not look for the date if action not goToDate
$dateStartmonth = GETPOST('startDatemonth', 'int');
$dateStartyear = GETPOST('startDateyear', 'int');


$dateStart = parseDate($dateStartday, $dateStartmonth, $dateStartyear, $dateStart);


$dateEnd = strtotime(GETPOST('dateEnd', 'alpha'));
$dateEndday = GETPOST('dateEndday', 'int');// to not look for the date if action not goToDate
$dateEndmonth = GETPOST('dateEndmonth', 'int');
$dateEndyear = GETPOST('dateEndyear', 'int');
$dateEnd = parseDate($dateEndday, $dateEndmonth, $dateEndyear, $dateEnd);
$invoicabletaskOnly = GETPOST('invoicabletaskOnly', 'int');
if ($user->rights->facture->creer && hasProjectRight($userid, $projectId)) {
    if ($projectId>0)$staticProject->fetch($projectId);
    if ($socid == 0 || !is_numeric($socid))$socid = $staticProject->socid;
$edit = 1;
// avoid SQL issue
if (empty($dateStart) || empty($dateEnd) ||$dateStart == $dateEnd) {
    $step = 0;
    $dateStart = strtotime("first day of previous month", time());
    $dateEnd = strtotime("last day of previous month", time());
}
 $langs->load("main");
$langs->load("projects");
$langs->load('timesheet@timesheet');
//steps
    switch($step) {
        case 2:
           $fields = ($mode == 'user')?'fk_user':(($mode == 'taskUser')?'fk_user, fk_task':'fk_task');
            $sql = 'SELECT  '.$fields.', SUM(tt.task_duration) as duration, ';
            if ($db->type!='pgsql') {
                $sql .= " GROUP_CONCAT(tt.rowid  SEPARATOR ', ') as task_time_list";
            } else{
                $sql .= " STRING_AGG(to_char(tt.rowid, '9999999999999999'), ', ') as task_time_list";
            }
             $sql .= ' From '.MAIN_DB_PREFIX.'projet_task_time as tt';
            $sql .= ' JOIN '.MAIN_DB_PREFIX.'projet_task as t ON tt.fk_task = t.rowid';
            if ($invoicabletaskOnly == 1)$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'projet_task_extrafields as tske ON tske.fk_object = t.rowid ';
            $sql .= ' WHERE t.fk_projet='.$projectId;
                $sql .= " AND DATE(tt.task_datehour) BETWEEN '".$db->idate($dateStart);
                $sql .= "' AND '".$db->idate($dateEnd)."'";
             if ($invoicabletaskOnly == 1)$sql .= ' AND tske.invoiceable = \'1\'';
            if ($ts2Invoice!='all') {
                /*$sql .= ' AND tt.rowid IN(SELECT GROUP_CONCAT(fk_project_s SEPARATOR ", ")';
                $sql .= ' FROM '.MAIN_DB_PREFIX.'project_task_time_approval';
                $sql .= ' WHERE status = "APPROVED" AND MONTH(date_start)='.$month;
                $sql .= ' AND YEAR(date_start) = "'.$year.'")';
                $sql .= ' AND YEAR(date_start) = "'.$year.'")';*/
                $sql .= ' AND tt.status = '.APPROVED;
            }
            if ($tsNotInvoiced == 1) {
                $sql .= ' AND tt.invoice_id IS NULL';
            }
            $sql .= ' GROUP BY '.$fields;
            dol_syslog('timesheet::timesheetProjectInvoice step2', LOG_DEBUG);
            $Form = '<form name = "settings" action="?step=3" method = "POST" >'."\n\t";
            $Form .= '<input type = "hidden" name = "propalid" value = "'.$propalId.'">';
            $Form .= '<input type = "hidden" name = "projectid" value = "'.$projectId.'">';
            $Form .= '<input type = "hidden" name = "startDate" value = "'.dol_print_date($dateStart, 'dayxcard').'">';
            $Form .= '<input type = "hidden" name = "dateEnd" value = "'.dol_print_date($dateEnd, 'dayxcard').'">';
            $Form .= '<input type = "hidden" name = "socid" value = "'.$socid.'">';
            $Form .= '<input type = "hidden" name = "invoicingMethod" value = "'.$mode.'">';
            $Form .= '<input type = "hidden" name = "ts2Invoice" value = "'.$ts2Invoice.'">';
            $Form .= '<input type = "hidden" id="csrf-token" name = "token" value = "'.$token.'"/>';

            $resql = $db->query($sql);
            $num = 0;
            $resArray = array();
            if ($resql) {
                $num = $db->num_rows($resql);
                $i = 0;
                // Loop on each record found,
                while($i < $num)
                {
                    $error = 0;
                    $obj = $db->fetch_object($resql);
                    $duration = floor($obj->duration/3600).":".str_pad(floor($obj->duration%3600/60), 2, "0", STR_PAD_LEFT);
                    switch($mode) {
                        case 'user':
                             //step 2.2 get the list of user  (all or approved)
                            $resArray[] = array("USER" => $obj->fk_user, "TASK" => 'any', "DURATION"=>$duration, 'LIST'=>$obj->task_time_list);
                            break;
                        case 'taskUser':
                             //step 2.3 get the list of taskUser  (all or approved)
                            $resArray[] = array("USER" => $obj->fk_user, "TASK" =>$obj->fk_task, "DURATION"=>$duration, 'LIST'=>$obj->task_time_list);
                            break;
                        default:
                        case 'task':
                             //step 2.1 get the list of task  (all or approved)
                            $resArray[] = array("USER" => "any", "TASK" =>$obj->fk_task, "DURATION"=>$duration, 'LIST'=>$obj->task_time_list);
                          break;
                    }
                    $i++;
                }
                $db->free($resql);
            } else {
                dol_print_error($db);
                return '';
            }
             //FIXME asign a service + price to each array elements(or price +auto generate name
            $Form .= '<table class = "noborder" width = "100%">'."\n\t\t";
            $Form .= '<tr class = "liste_titre" width = "100%" ><th colspan = "8">'.$langs->trans('invoicedServiceSelectoin').'</th><th>';
            $Form .= '<tr class = "liste_titre" width = "100%" ><th >'.$langs->trans("User").'</th>';
            $Form .= '<th >'.$langs->trans("Task").'</th><th >'.$langs->trans("Service").':'.$langs->trans("Existing")."/".$langs->trans("Custom").'</th>';
            $Form .= '<th >'.$langs->trans("Custom").':'.$langs->trans("Description").'</th><th >'.$langs->trans("Custom").':'.$langs->trans("UnitPriceHT").'</th>';
            $Form .= '<th >'.$langs->trans("Custom").':'.$langs->trans("VAT").'</th><th >'.$langs->trans("unitDuration").'</th><th >'.$langs->trans("savedDuration").'</th>';
            $form = new Form($db);
            $otherchoices = array('-999'=> $langs->transnoentities('not2invoice'),
                     '-998' => $langs->transnoentities('never2invoice'));
            if ($propalId > 0){
                require_once DOL_DOCUMENT_ROOT."/comm/propal/class/propal.class.php";
                $propal = new Propal($db);
                $propal->fetch($propalId);
                $propal->fetch_lines();
                foreach($propal->lines as $lid => $line){
                    if($line->product_type == 1){
                        if ($line->fk_product) $line->label = getproductlabel($line->fk_product);
                        $otherchoices[-$lid] = $langs->transnoentities('Proposal').":".$line->label;
                    }
                }
            }
        
            foreach ($resArray as $res) {
                $Form .= htmlPrintServiceChoice($res["USER"], $res["TASK"], 'oddeven', $res["DURATION"], $res['LIST'], $mysoc, $socid, $otherchoices);
            }
            $Form .= '</table>';
            $Form .= '<input type = "submit"  class = "butAction" value = "'.$langs->trans('Next')."\">\n</form>";
            break;
        case 3: // review choice and list of item + quantity(editable)
            require_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';
            require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
            $object = new Facture($db);
            $db->begin();
            $error = 0;
            $dateinvoice = time();
                    //$date_pointoftax = dol_mktime(12, 0, 0, $_POST['date_pointoftaxmonth'], $_POST['date_pointoftaxday'], $_POST['date_pointoftaxyear']);
                            // Si facture standard
            $object->socid = $socid;
            $object->type = 0;//Facture::TYPE_STANDARD;
            $object->date = $dateinvoice;
            $object->fk_project = $projectId;
            $object->fetch_thirdparty();
            if ($propalId > 0){
                $object->origin = 'propal';
                $object->origin_id = $propalId;
                $object->linked_objects[$object->origin] = $object->origin_id;
            }
            //origin=propal&originid=34&socid=10
            $id = $object->create($user);
            $resArray = $_POST['userTask'];
            $hoursPerDay = getConf('TIMESHEET_DAY_DURATION',8);
            $task_time_array = array();
            $task_time_array_never = array();
                // copy the propal lines
            $lineCount = 0; 
            // id -> db id
            $propallines = []; 
            
            if ( $propalId > 0) { // PROPAL
                
                require_once DOL_DOCUMENT_ROOT."/comm/propal/class/propal.class.php";
                $propal = new Propal($db);
                $propal->id= $propalId;
                $propal->fetch_lines();
                foreach($propal->lines as $lid => $line){
                    $label = (!empty($line->label) ? $line->label : '');
					$desc = (!empty($line->desc) ? $line->desc : $line->libelle);
                    $lineCount ++; 
                    $product_type = ($line->product_type ? $line->product_type : 0);
                    // Date start
                    $date_start = false;
                    if ($line->date_debut_prevue) {
                        $date_start = $line->date_debut_prevue;
                    }
                    if ($line->date_debut_reel) {
                        $date_start = $line->date_debut_reel;
                    }
                    if ($line->date_start) {
                        $date_start = $line->date_start;
                    }

                    // Date end
                    $date_end = false;
                    if ($line->date_fin_prevue) {
                        $date_end = $line->date_fin_prevue;
                    }
                    if ($line->date_fin_reel) {
                        $date_end = $line->date_fin_reel;
                    }
                    if ($line->date_end) {
                        $date_end = $line->date_end;
                    }

                    // Reset fk_parent_line for no child products and special product
                    if (($line->product_type != 9 && empty($line->fk_parent_line)) || $line->product_type == 9) {
                        $fk_parent_line = 0;
                    }

                    // Extrafields
                    if (method_exists($line, 'fetch_optionals')) {
                        $line->fetch_optionals();
                        $array_options = $line->array_options;
                    }

                    $tva_tx = $line->tva_tx;
                    if (!empty($line->vat_src_code) && !preg_match('/\(/', $tva_tx)) {
                        $tva_tx .= ' ('.$line->vat_src_code.')';
                    }

                    // View third's localtaxes for NOW and do not use value from origin.
                    // TODO Is this really what we want ? Yes if source is template invoice but what if proposal or order ?
                    $localtax1_tx = get_localtax($tva_tx, 1, $object->thirdparty);
                    $localtax2_tx = get_localtax($tva_tx, 2, $object->thirdparty);

                    $result = 0;
                    $postdata['prod_entry_mode'] = 'predef';
                    $postdata['dp_desc'] = $desc;
                    $postdata['tva_tx'] = $$tva_tx;
                    $postdata['price_ht'] =$line->total_ht;
                    $postdata['qty'] = (float) $line->qty;                      
                    if (!getConf('TIMESHEET_EVAL_ADDLINE')){
                        $result = $object->addline(
                            $desc,
                            $line->subprice,
                            $line->qty,
                            $tva_tx,
                            $localtax1_tx,
                            $localtax2_tx,
                            $line->fk_product,
                            $line->remise_percent,
                            $date_start,
                            $date_end,
                            0,
                            $line->info_bits,
                            $line->fk_remise_except,
                            'HT',
                            0,
                            $product_type,
                            $line->rang,
                            $line->special_code,
                            $object->origin,
                            $line->rowid,
                            $fk_parent_line,
                            $line->fk_fournprice,
                            $line->pa_ht,
                            $label,
                            $array_options,
                            $line->situation_percent,
                            $line->fk_prev_id,
                            $line->fk_unit
                        );  
                    }else{
                        $post_temp = $_POST;
                        $_POST = $postdata;
                        ob_start();
                        eval($invoicecard);
                        ob_end_clean();
                        $_POST = $post_temp;
                        $result = get_lastest_id('facture_fourn_det');
                    }
                    if ($result>0)$propallines[$lineCount] = $result;
                }                                
            }
            echo "step 3<br>";
            if ($id > 0  && is_array($resArray)) {
                echo "step 3.1<br>";
                $db->commit();
                $invoicecard = str_replace(
                                array("require '../../main.inc.php';","<?php","\$db->close();"),
                                "",
                                file_get_contents(DOL_DOCUMENT_ROOT.'/compta/facture/card.php'));
                foreach ($resArray as $uId => $userTaskService) {
                        //$userTaskService[$user][$task] = array('duration', 'VAT', 'Desc', 'PriceHT', 'Service', 'unit_duration', 'unit_duration_unit');
                    if (is_array($userTaskService))foreach ($userTaskService as  $tId => $service) {
                         
                        $durationTab = explode(':', $service['duration']);
                        $duration = $durationTab[1]*60 + $durationTab[0]*3600;
                        //$startday = dol_mktime(12, 0, 0, $month, 1, $year);
                        //$endday = dol_mktime(12, 0, 0, $month, date('t', $startday), $year);
                        $details = '';
                        $result = 0;
                        $factor = 1;
                        $unit_duration_unit = $service['unit_duration_unit'];
                        switch($unit_duration_unit){
                            case 'h':
                                $unit_factor = 3600;
                            break;
                            case 'i':
                                $unit_factor = 60;
                            break;
                            case 's':
                                $unit_factor = 1;
                            break;
                            case 'w':
                                $unit_factor = 3600 * $hoursPerDay * 5;
                            break;
                            case 'm':
                                $unit_factor = 3600 * $hoursPerDay * 65 / 3;
                            break;
                            case 'y':
                                $unit_factor = 3600 * $hoursPerDay * 260;
                            break;
                            case 'l':
                                $unit_factor = $duration;
                            case 'd':
                            default:
                                $unit_factor = $hoursPerDay * 3600;
                        }
                        if (($tId!='any') && getConf('TIMESHEET_INVOICE_SHOW_TASK'))$details = "\n".$service['taskLabel'];
                        if (($uId!='any')&& getConf('TIMESHEET_INVOICE_SHOW_USER'))$details .= "\n".$service['userName'];
                        //prepare the CURL params
                        $postdata = array();
                        $postdata['action'] = 'addline';
                        $postdata['id'] = $object->id;
                        $postdata['date_startday'] = date('d', $dateStart);
                        $postdata['date_startmonth'] = date('m', $dateStart);
                        $postdata['date_startyear'] = date('Y', $dateStart);
                        $postdata['date_endday'] = date('d', $dateEnd);
                        $postdata['date_endmonth'] = date('m', $dateEnd);
                        $postdata['date_endyear'] = date('Y', $dateEnd);
                        $postdata['addline']='Add';
                        if ($service['Service'] > 0) {
                            
                            $localtax1_tx = get_localtax($service['VAT'], 1, $object->thirdparty);
                            $localtax2_tx = get_localtax($service['VAT'], 2, $object->thirdparty);
                            $product = new Product($db);
                            $product->fetch($service['Service']);
                            if ($object->thirdparty->default_lang != '' && is_array($product->multilangs[$object->thirdparty->default_lang]))
                            {
                                $desc = $product->multilangs[$object->thirdparty->default_lang]['description'];
                                $label = $product->multilangs[$object->thirdparty->default_lang]['label'];
                            }else{
                                $desc = $product->description;
                                $label = $product->label;
                            }
                            $factor = intval(substr($product->duration, 0, -1));
                            if ($factor == 0) $factor = 1;//to avoid divided by $factor0                         
                            $quantity = ($duration == $factor*$unit_factor) ? 1 :
                                round($duration/($factor*$unit_factor), getConf('TIMESHEET_ROUND'));
                            $postdata['type'] = -1;
                            $postdata['prod_entry_mode'] = 'predef';
                            $postdata['idprod'] = $service['Service'];
                            $postdata['qty'] = (float) $quantity;
            


                            $prices =  $product->getSellPrice( $mysoc,$object->thirdparty);
                            $price_base_type = $prices['price_base_type'];
                            $price_ttc = $prices['pu_ttc'];
                            $tva_tx = $prices['tva_tx'];
                            $price = $prices['pu_ht'];


                            if (!getConf('TIMESHEET_EVAL_ADDLINE')){
                                $result = $object->addline($product->description.$details, $price, $quantity, $tva_tx, 
                                    $localtax1_tx, $localtax2_tx, $service['Service'], 0, $dateStart, $dateEnd, 0, 0, '', 
                                    $price_base_type, $price_ttc, $product->type, -1, 0, '', 0, 0, null, 0, $label, 0, 100, '', 
                                    $product->fk_unit);
                            }else{
                                $result = $lineCount;
                            }
                            $lineCount ++;
                        } elseif ($service['Service'] > -997) { // propal
                            if(isset($task_time_array[$propallines[-$service['Service']]])){
                                $task_time_array[$propallines[-$service['Service']]] .= ",".$service['taskTimeList'];
                            }else{
                                $task_time_array[$propallines[-$service['Service']]] = $service['taskTimeList'];
                            }
                        } elseif ($service['Service'] == -997) { // customized service
                            
                            $localtax1_tx = get_localtax($service['VAT'], 1, $object->thirdparty);
                            $localtax2_tx = get_localtax($service['VAT'], 2, $object->thirdparty);
                            $factor = intval($service['unit_duration']);
                            $quantity = ($duration == $factor*$unit_factor) ? 1 :
                                round($duration/($factor*$unit_factor), getConf('TIMESHEET_ROUND',0));                            
                            $postdata['type'] = 1;
                            $postdata['prod_entry_mode'] = 'free';
                            $postdata['dp_desc'] = $service['Desc'];
                            $postdata['tva_tx'] = $service['VAT'];
                            $postdata['price_ht'] = $service['PriceHT'];
                            $postdata['qty'] = (float) $quantity;
                            if (!getConf('TIMESHEET_EVAL_ADDLINE')){
                                $result = $object->addline($service['Desc'].$details, $service['PriceHT'], 
                                    $quantity, $service['VAT'], $localtax1_tx, $localtax2_tx, '', 
                                    0, $dateStart, $dateEnd, 0, 0, '', 'HT', '', 1, -1, 0, '', 
                                    0, 0, null, 0, '', 0, 100, '', '');
                            }else {
                                $result = get_lastest_id('facture_fourn_det');
                            }
       
                            $lineCount ++;
                        }elseif ($service['Service'] == -998){ // never invoice
                            $task_time_array_never[] = $service['taskTimeList']; 
                        }
                        //add_invoice_line($postdata);
                        
                        
                        //eval used instead of include because the main.in.php cannot be included twice so it had to be removed from
                        if (getConf('TIMESHEET_EVAL_ADDLINE')){
                            $post_temp = $_POST;
                            $_POST = $postdata;
                            ob_start();
                            eval($invoicecard);
                            ob_end_clean();
                            $_POST = $post_temp;
                        }
                        // set the taskTimeList to be updated in case of success of the invoice add line
                        if ($service['taskTimeList'] != '' && ($result>0  )){
                            $task_time_array[$result] = $service['taskTimeList'];
                        }
                    } else $error++;
                }


                // End of object creation, we show it
                if (1) {
                    if (version_compare(DOL_VERSION, "4.9.9") >= 0) {
                        foreach ($task_time_array AS $idLine => $task_time_list) {
                                //dol_syslog("ProjectInvoice::setnvoice".$idLine.' '.$task_time_list, LOG_DEBUG);
                            Update_task_time_invoice($id, $idLine, $task_time_list);
                        }
                        foreach ($task_time_array_never AS $idLine => $task_time_list) {
                            //dol_syslog("ProjectInvoice::setnvoice".$idLine.' '.$task_time_list, LOG_DEBUG);
                        Update_task_time_invoice(-1, -1, $task_time_list);
                    }
                    }
                    ob_start();
                    header('Location: ' . $object->getNomUrl(0, '', 0, 1, ''));
                    ob_end_flush();
                    exit();
                }
            } else {
                $db->rollback();
                //header('Location: ' . $_SERVER["PHP_SELF"] . '?step=0');
                setEventMessages($object->error, $object->errors, 'errors');
            }

           
            break;
        case 1:
            $edit = 0;
        case 0:
        default:
            require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
            $htmlother = new FormOther($db);
            $sqlTail = '';
            //if (!$user->admin) {
            //    $sqlTailJoin = ' JOIN '.MAIN_DB_PREFIX.'element_contact AS ec ON t.rowid = element_id ';
            //    $sqlTailJoin .= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_type_contact as ctc ON ctc.rowid = ec.fk_c_type_contact';
            //    $sqlTailWhere = ' ((ctc.element in (\'project_task\') AND ctc.code LIKE \'%EXECUTIVE%\')OR (ctc.element in (\'project\') AND ctc.code LIKE \'%LEADER%\')) AND ctc.active = \'1\'  ';
            //    $sqlTailWhere .= ' AND fk_socpeople = \''.$userid.'\' and t.fk_statut = \'1\'';
            //}
            $Form = '<form name = "settings" action="?step=2" method = "POST" >'."\n\t";
            $Form .= '<input type = "hidden" id="csrf-token" name = "token" value = "'.$token.'"/>';
            $Form .= '<table class = "noborder" width = "100%">'."\n\t\t";
            $Form .= '<tr class = "liste_titre" width = "100%" ><th colspan = "2">'
                .$langs->trans('generalInvoiceProjectParam').'</th></tr>';
            $invoicingMethod = getConf('TIMESHEET_INVOICE_METHOD');
            //$Form .= '<tr class = "oddeven"><th align = "left" width = "80%">'.$langs->trans('Project').'</th><th align = "left" width = "80%" >';
            //select_generic($table, $fieldValue, $htmlName, $fieldToShow1, $fieldToShow2 = '', $selected = '', $separator = ' - ', $sqlTailWhere = '', $selectparam = '', $addtionnalChoices = array('NULL' => 'NULL'), $sqlTailTable = '', $ajaxUrl = '')
            //$ajaxNbChar = getConf('PROJECT_USE_SEARCH_TO_SELECT');
            //$Form .= select_generic('projet', 'rowid', 'projectid', 'ref', 'title', $projectId, ' - ', $sqlTailWhere, '', null, , $ajaxNbChar);
            //$htmlProjectArray = array('name' => 'projectid', 'ajaxNbChar'=>$ajaxNbChar, 'otherparam' => ' onchange = "reload(this.form)"');
            //$sqlProjectArray = array('table' => 'projet', 'keyfield' => 't.rowid', 'fields' => 't.ref, t.title ', 'join'=>$sqlTailJoin, 'where'=>$sqlTailWhere, 'separator' => ' - ');
            //$Form .= select_sellist($sqlProjectArray, $htmlProjectArray, $projectId);
            $Form .= '<input type = "hidden" name = "projectid" value = "'.$projectId.'">';
            $Form .= '<tr class = "oddeven"><th align = "left" width = "80%">'
                .$langs->trans('DateStart').'</th>';
            $Form .= '<th align = "left" width = "80%">'
                .$form->select_date($dateStart, 'startDate', 0, 0, 0, "", 1, 1, 1)."</th></tr>";
            $Form .= '<tr class = "oddeven"><th align = "left" width = "80%">'
                .$langs->trans('DateEnd').'</th>';
            $Form .= '<th align = "left" width = "80%">'
                .$form->select_date($dateEnd, 'dateEnd', 0, 0, 0, "", 1, 1, 1)."</th></tr>";
            $Form .= '<tr class = "oddeven"><th align = "left" width = "80%">'
                .$langs->trans('invoicingMethod').'</th><th align = "left"><input type = "radio" '
                .'name = "invoicingMethod" value = "task" ';
            $Form .= ($invoicingMethod == "task"?"checked":"").'> '.$langs->trans("Tasks").'<br> ';
            $Form .= '<input type = "radio" name = "invoicingMethod" value = "user" ';
            $Form .= ($invoicingMethod == "user"?"checked":"").'> '.$langs->trans("User")."<br> ";
            $Form .= '<input type = "radio" name = "invoicingMethod" value = "taskUser" ';
            $Form .= ($invoicingMethod == "taskUser"?"checked":"").'> '.$langs->trans("Tasks").' & '
                .$langs->trans("User")."</th></tr>\n\t\t";
    //cust list
            $Form .= '<tr class = "oddeven"><th  align = "left">'.$langs->trans('Customer')
                .'</th><th  align = "left">'.$form->select_company($socid, 'socid', 
                    '(s.client = 1 OR s.client = 2 OR s.client = 3)', 1).'</th></tr>';
    //propal
   
        if (getConf('MAIN_MODULE_PROPALE')){
            //http://localhost:18080/compta/facture/card.php?action=create&origin=propal&originid=34&socid=10
            $joinPropal = ' JOIN '.MAIN_DB_PREFIX.'c_stcomm as stp ON fk_statut = stp.id and stp.active = 1 ';
            $sqlPropal = array('table' => 'propal' , 'keyfield' => 't.rowid', 
                'fields' => 't.ref, stp.libelle', 'join' => $joinPropal , 
                'where' => $socid?('t.fk_soc = '.$socid):'1 = 2', 'tail' => '');
            $htmlPropal = array('name' => 'propalid', 'class' => 'not_mandatory', 'otherparam' => '', 
                'ajaxNbChar' => '', 'separator' => ' - ');
            $addChoices = null;
            $Form .= '<tr class = "oddeven"><th  align = "left">'.$langs->trans('Propal').'</th><th>';
            $Form .= select_sellist($sqlPropal, $htmlPropal, '', $addChoices ).'</th></tr>';
        }
        $Form .= '
        <script type = "text/javascript">
            $("#socid").on("select2:select", function (e) {
                var param_array = window.location.href.split(\'?\')[1].split(\'&\');
                var index;
                var id = "";
                for(index = 0;index < param_array.length;++index)
                {
                    x = param_array[index].split(\'=\');
                    if (x[0] == "projectid") {
                        id = "projectid="+x[1];
                    }

                }
                var socSelect = e.params.data;
                var soc =  socSelect.id;
                var socElement = document.getElementById("socid");
                var socOld = (typeof(socElement.defaultSelected) === \'undefined\')?0:socElement.defaultSelected ;
                var dateStartday = "&dateStartday="+document.getElementById("startDateday").value;
                var dateStartmonth = "&dateStartmonth="+document.getElementById("startDatemonth").value;
                var dateStartyear = "&dateStartyear="+document.getElementById("startDateyear").value;

                var dateEndday = "&dateEndday="+document.getElementById("dateEndday").value;
                var dateEndmonth = "&dateEndmonth="+document.getElementById("dateEndmonth").value;
                var dateEndyear = "&dateEndyear="+document.getElementById("dateEndyear").value;
                
                if ( soc != null && soc != socOld){
                    self.location = "'.$PHP_SELF
                        .'?" + id  + "&socid=" + soc +  dateStartday + dateStartmonth + '
                        .'startDateyear + dateEndday + dateEndmonth + dateEndyear;
                }
            });
         </script>';
    
     //all ts or only approved
           $ts2Invoice = getConf('TIMESHEET_INVOICE_TASKTIME');
            $Form .= '<tr class = "oddeven"><th align = "left" width = "80%">'
                .$langs->trans('TimesheetToInvoice').'</th><th align = "left">'
                .'<input type = "radio" name = "ts2Invoice" value = "approved" ';
            $Form .= ($ts2Invoice == "approved"?"checked":"").'> '
                .$langs->trans("approvedOnly").' <br>';
            $Form .= '<input type = "radio" name = "ts2Invoice" value = "all" ';
            $Form .= ($ts2Invoice == "all"?"checked":"").'> '
                .$langs->trans("All")."</th></tr>";
    // not alreqdy invoice
            if (version_compare(DOL_VERSION, "4.9.9") >= 0) {
                    $Form .= '<tr class = "oddeven"><th align = "left" width = "80%">'
                        .$langs->trans('TimesheetNotInvoiced');
                    $Form .= '</th><th align = "left">'
                        .'<input type = "checkbox" name = "tsNotInvoiced" value = "1" ></th></tr>';
            } else{
                $Form .= '<input type = "hidden" name = "tsNotInvoiced" value = "0">';
            }
            //$invoicabletaskOnly
            $Form .= '<tr class = "oddeven"><th align = "left" width = "80%">'.$langs->trans('InvoicableOnly');
            $Form .= '</th><th align = "left"><input type = "checkbox" name = "invoicabletaskOnly" value = "1" '
                .(($invoicabletaskOnly == 1)?'checked':'').' ></th></tr>';
            $Form .= '</table>';
            $Form .= '<input type = "submit" onclick = "return checkEmptyFormFields(event,\'settings\',\'';
            $Form .= addslashes($langs->trans("pleaseFillAll")).'\')" class = "butAction" value = "'
                .$langs->trans('Next')."\">\n</from>";
           // if ($ajaxNbChar >= 0) $Form .= "\n<script type = 'text/javascript'>\n$('input#Project').change(function() {\nif($('input#search_Project').val().length>2)reload($(this).form)\n;});\n</script>\n";
            break;
    }
} else {
    $accessforbidden = accessforbidden("you don't have enough rights to see this page");
}
/***************************************************
* VIEW
*
* Put here all code to build page
****************************************************/
$morejs = array("/timesheet/core/js/jsparameters.php", 
    "/timesheet/core/js/timesheet.js?".getConf('TIMESHEET_VERSION'));
llxHeader('', $langs->trans('TimesheetToInvoice'), '', '', '', '', $morejs);
print "<div> <!-- module body-->";
$project = new Project($db);
$project->fetch($projectId);
$headProject = project_prepare_head($project);
dol_fiche_head($headProject, 'invoice', $langs->trans("Project"), 0, 'project');
$ref = GETPOST('ref', 'alpha');
// Load object
if ($projectId > 0 || !empty($ref))
{
	$ret = $project->fetch($projectId, $ref); // If we create project, ref may be defined into POST but record does not yet exists into database
	if ($ret > 0) {
		$project->fetch_thirdparty();
		if (!getConf('PROJECT_ALLOW_COMMENT_ON_PROJECT') != false && method_exists($project, 'fetchComments') && empty($project->comments)) $project->fetchComments();
		$id = $project->id;
	}
}

$linkback = '<a href="'.DOL_URL_ROOT.'/projet/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

$morehtmlref = '<div class="refidno">';
// Title
$morehtmlref .= $project->title;
// Thirdparty
if ($project->thirdparty->id > 0)
{
    $morehtmlref .= '<br>'.$langs->trans('ThirdParty').' : '.$project->thirdparty->getNomUrl(1, 'project');
}
$morehtmlref .= '</div>';

dol_banner_tab($project, 'projectid', $linkback, ($user->socid ? 0 : 1), 'ref','ref',$morehtmlref);
print '<div class="underbanner clearboth"></div>';

dol_fiche_end();

print $Form;
//javascript to reload the page with the poject selected
/*print '
<SCRIPT type = "text/javascript">
function reload(form)
{
    var pjt = document.getElementById("projectid").value;
    self.location="?projectid=" + pjt ;
}
</script>';*/
llxFooter();
$db->close();
/***************************************************
* FUNCTIONS
*
* Put here all code of the functions
****************************************************/
/** Function to print the line to chose between a predefined service or an ad-hoc one
 *
 * @global object $form form object
 * @global objec $langs lang object
 * @global object $conf  conf object
 * @param int $user  userid on which the time was spent
 * @param int $task  Taskid on which the time was spent
 * @param string $class     html class
 * @param int $duration     duration of the time spend
 * @param string $tasktimelist list of the tasktimespendid on which the time was spent
 * @param type $seller  Seller id to calculate VAT
 * @param type $buyer   buyer id to calculate VAT
 * @param array(id=> desc) otherchoice
 * @return string   HTML code
 */
function htmlPrintServiceChoice($user, $task, $class, $duration, $tasktimelist, $seller, $buyer, $addchoices)
{
    global $form, $langs, $conf, $db;
    $taskLabel = '';
    $userName = ($user == 'any')?
        (' - '):print_generic('user', 'rowid', $user, 'lastname', 'firstname', ' ');
    if ($task == 'any'){
        $taskLabel = ' - ';
        $taskHTML = ' - ';
    } else {
        require_once DOL_DOCUMENT_ROOT.'/projet/class/task.class.php';
        $objtemp = new Task($db);
        $objtemp->fetch($task);
        $taskLabel = $objtemp->label ;
        $taskHTML = str_replace('classfortooltip', 'classfortooltip colTasks', 
            $objtemp->getNomUrl(0, "withproject", "task", getConf('TIMESHEET_HIDE_REF')));
    }

    $html = '<tr class = "'.$class.'"><th align = "left" width = "20%">'.$userName;
    $html .= '</th><th align = "left" width = "20%">'.$taskHTML;
    $html .= '<input type = "hidden"   name = "userTask['.$user.']['.$task.'][userName]" value = "'.$userName.'">';
    $html .= '<input type = "hidden"   name = "userTask['.$user.']['.$task.'][taskLabel]"  value = "'. $taskLabel.'">';
    $html .= '<input type = "hidden"   name = "userTask['.$user.']['.$task.'][taskTimeList]"  value = "'. $tasktimelist.'">';
    $defaultService = getDefaultService($user, $task);
    $addchoices[-997] = $langs->transnoentities('Custom').': '.$taskLabel;
    $ajaxNbChar = getConf('PRODUIT_USE_SEARCH_TO_SELECT');
    $html .= '</th><th >';
    $html .= select_sellist(array('table' => 'product', 
        'keyfield' => 'rowid', 'fields' => 'ref,label', 
        'where' => ' tosell = 1 AND fk_product_type = 1'),
        array('name' => 'userTask['.$user.']['.$task.'][Service]',  'separator' => ' - '),
                $defaultService,  $addchoices);
    $html .= '</th>';
    $unitValue = '0.0';
    if (($user>0)){ // if the is no defaulf service, use the thm if available, if not use the tjm
        $curUser = new User($db);
        $curUser->fetch($user);
        if ($curUser->thm)$unitValue = $curUser->thm;
        else if ($curUser->tjm)$unitValue = $curUser->tjm;
    }

    $html .= '<th ><input type = "text"  size = "30" name = "userTask['
        .$user.']['.$task.'][Desc]" ></th>';
    $html .= '<th><input type = "text"  size = "6" name = "userTask['
        .$user.']['.$task.'][PriceHT]" value="'.number_format($unitValue,2).'" ></th>';
    //$html .= '<th><input type = "text" size = "6" name = "userTask['.$user.']['.$task.']["VAT"]" ></th>';
    $html .= '<th>'.$form->load_tva('userTask['.$user.']['.$task.'][VAT]', 
        -1, $seller, $buyer, 0, 0, '', false, 1).'</th>';
    $html .= '<th><input type = "text" size = "2" maxlength = "2" name = "userTask['
        .$user.']['.$task.'][unit_duration]" value = "1" >';
    $html .= '<br><input name = "userTask['.$user.']['
        .$task.'][unit_duration_unit]" type = "radio" value = "h" '
        .((getConf('TIMESHEET_TIME_TYPE','hours') == "days")?'':'checked').' />'.$langs->trans('Hour');
    $html .= '<br><input name = "userTask['
        .$user.']['.$task.'][unit_duration_unit]" type = "radio" value = "d" '
        .((getConf('TIMESHEET_TIME_TYPE','hours') == "days")?'checked':'').' />'.$langs->trans('Days');
    $html .= '<br><input name = "userTask['
        .$user.']['.$task.'][unit_duration_unit]" type = "radio" value = "l"/>'.$langs->trans('Lumpsum').'</th>';
    $html .= '<th><input type = "text" size = "2" onkeypress="return regexEvent(this,event,\'timeChr\')"'
        .' maxlength = "5" name = "userTask['.$user.']['.$task.'][duration]" value = "'.$duration.'" />';
    $html .= '</th</tr>';
    return $html;
}
/**
 *
 * @global object  $db
 * @global object $conf
 * @param type $userid id of the user
 * @param type $taskid id of the tasl
 * @return int  service id
 */
function getDefaultService($userid, $taskid)
{
    global $db, $conf;
    $res = 0;
    $sql = ' SELECT fk_service FROM '.MAIN_DB_PREFIX.'projet_task_extrafields WHERE fk_object = \''.$taskid.'\'';
    $sql .= ' UNION ALL';
    $sql .= ' SELECT fk_service FROM '.MAIN_DB_PREFIX.'user_extrafields WHERE fk_object = \''.$userid.'\'';
    $sql .= ' LIMIT 1';
     dol_syslog("ProjectInvoice::getDefaultService", LOG_DEBUG);
    $resql = $db->query($sql);
    if ($db->num_rows($resql)>0) {
        $obj = $db->fetch_object($resql);
        $res = $obj->fk_service;
    }
    return($res>0)?$res:getConf('TIMESHEET_INVOICE_SERVICE');
}
/** to check who has the rights
 *
 * @global object $db database object
 * @global object $user current user connected
 * @param object $userid    user to check
 * @param int $projectid project to check
 * @return boolean  has right
 */
function hasProjectRight($userid, $projectid)
{
    global $db, $user;
    $res = true;
    if ($projectid && !($user->admin)) {
        $res = false;
        $sql = ' SELECT ec.rowid FROM '.MAIN_DB_PREFIX.'element_contact as ec ';
        $sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_type_contact as ctc ON ctc.rowid = ec.fk_c_type_contact';
        $sql .= ' WHERE element_id = \''.$projectid;
        $sql .= '\' AND (ctc.element in (\'project\')'
            .' AND (ctc.code LIKE \'%LEADER%\' OR ctc.code LIKE \'%BILLING%\'))'
            .' AND ctc.active = \'1\'  ';
        $sql .= ' AND fk_socpeople = \''.$userid.'\' ';
        dol_syslog("ProjectInvoice::hasProjectRight", LOG_DEBUG);
        $resql = $db->query($sql);
        if ($db->num_rows($resql))$res = true;
    }
    return $res;
}
/** update invoice number
 *
 * @global object $db
 * @param int $idInvoice id of invoice
 * @param int $idLine id of invoice line
 * @param sring $task_time_list id task separated by comma
 * @return boolean
 */
function Update_task_time_invoice($idInvoice, $idLine, $task_time_list)
{
    global $db;
    $res = false;
    $sql = 'UPDATE '.MAIN_DB_PREFIX.'projet_task_time';
    $sql .= " SET invoice_id = '{$idInvoice}', invoice_line_id = '{$idLine}'";
    $sql .= " WHERE rowid in ({$task_time_list})";
    dol_syslog("ProjectInvoice::setnvoice", LOG_DEBUG);
    $resql = $db->query($sql);
    if ($db->num_rows($resql))$res = true;
    return $res;
}

/** get the label of a product
 * @param int $productId $product Id
 * @return sting label
 */
function getproductlabel($productId){
    global $db;
    require_once  DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
    $product = new Product($db);
    $product->fetch($productId);
    return $product->getNomUrl(0,'',0,-1,0);
}

function get_lastest_id($table, $id){
    global $db;
    $sql = 'SELECT TOP 1 rowid as lastid FROM'.MAIN_DB_PREFIX.$table
        .' WHERE fk_facture_fourn ='.$id
        .' ORDER BY rowid DESC';
    $resql = $db->query($sql);
    $num = 0;
    $resArray = array();
    if ($resql) {
        $num = $db->num_rows($resql);
        if($num == 1 )
        {
            $obj = $db->fetch_object($resql);
            return $obj->lastid;
        }
    }
    return 0;
}