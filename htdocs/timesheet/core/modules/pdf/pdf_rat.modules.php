<?php
/* Copyright (C) 2010-2012 Regis Houssin  <regis.houssin@capnetworks.com>
 * Copyright (C) 2018      Laurent Destailleur <eldy@users.sourceforge.net>
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
 * or see http://www.gnu.org/
 */
/**
 *        \file       htdocs/core/modules/project/doc/pdf_rat.modules.php
 *        \ingroup    project
 *        \brief      File of class to generate project document Baleine
 *        \author            Regis Houssin
 */
require_once './class/TimesheetReport.class.php';
require_once './core/modules/modules_timesheet.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
/**
 *        Class to manage generation of project document Baleine
 */
class pdf_rat extends ModelPDFTimesheetReport
{
    public $emetteur;        // Objet societe qui emet
    public $posxworker;
    public $posxdate;
    public $posxduration;
    /**
     *        Constructor
     *
     *  @param                DoliDB                $db      Database handler
     */
    public function __construct($db)
    {
        global $conf, $langs, $mysoc;
        $langs->load("main");
        $langs->load("projects");
        $langs->load("companies");
        $this->noteISOtask=$conf->global->TIMESHEET_PDF_NOTEISOTASK;
        $this->db = $db;
        $this->name = "rat";
        $this->description = $langs->trans("DocumentModelRat");
        // Dimension page pour format A4
        $this->type = 'pdf';
        $formatarray=pdf_getFormat();
        $this->page_largeur = $formatarray['width'];
        $this->page_hauteur = $formatarray['height'];
        $this->format = array($this->page_largeur, $this->page_hauteur);
        $this->marge_gauche=isset($conf->global->MAIN_PDF_MARGIN_LEFT)?$conf->global->MAIN_PDF_MARGIN_LEFT:10;
        $this->marge_droite=isset($conf->global->MAIN_PDF_MARGIN_RIGHT)?$conf->global->MAIN_PDF_MARGIN_RIGHT:10;
        $this->marge_haute =isset($conf->global->MAIN_PDF_MARGIN_TOP)?$conf->global->MAIN_PDF_MARGIN_TOP:10;
        $this->marge_basse =isset($conf->global->MAIN_PDF_MARGIN_BOTTOM)?$conf->global->MAIN_PDF_MARGIN_BOTTOM:10;
        $this->option_logo = 1;// Affiche logo FAC_PDF_LOGO
        $this->option_tva = 1;// Gere option tva FACTURE_TVAOPTION
        $this->option_codeproduitservice = 1;// Affiche code produit-service
        // Recupere emmetteur
        $this->emetteur=$mysoc;
        if(! $this->emetteur->country_code) $this->emetteur->country_code=substr($langs->defaultlang, -2);// By default if not defined
        // Defini position des colonnes
        $this->posxref=$this->marge_gauche+1;
        $this->posxdate=$this->marge_gauche+9;
        //$this->posxworker=$this->marge_gauche+27;
        //$this->posxlabel=$this->marge_gauche+65;
        $this->posxlabel=$this->marge_gauche+33;
        //$this->posxworkload=$this->marge_gauche+120;
        //$this->posxprogress=$this->marge_gauche+140;
        //$this->posxdatestart=$this->marge_gauche+152;
        //$this->posxdateend=$this->marge_gauche+170;
        $this->posxduration=$this->marge_gauche+175;
        if($this->page_largeur < 210) {
            // To work with US executive format {
            $this->posxref-=20;
            $this->posxlabel-=20;
            //$this->posxworker-=20;
            $this->posxdate-=20;
            $this->posxduration-=20;
        }
    }
/**
 *        Fonction generant le projet sur le disque
 *
 *        @param        Project                $object                Object project a generer
 *        @param        Translate        $outputlangs        Lang output object
 *        @return        int                                  1 if OK, <=0 if KO
 */
public function writeFile($object, $outputlangs)
{
    global $conf, $hookmanager, $langs, $user;
    if(! is_object($outputlangs)) $outputlangs=$langs;
    // For backward compatibility with FPDF, force output charset to ISO, because FPDF expect text to be encoded in ISO
    if(! empty($conf->global->MAIN_USE_FPDF)) $outputlangs->charset_output='ISO-8859-1';
    //load langs
    $outputlangs->load("main");
    $outputlangs->load("dict");
    $outputlangs->load("companies");
    $outputlangs->load("projects");
    $outputlangs->load("timesheet@timesheet");

    //get save dir
    if($conf->projet->dir_output) {
        $objectref =  dol_sanitizeFileName($object->name);
        $dir = $conf->timesheet->dir_output.'/reports/';
        $file = $dir . "/" . $objectref . ".pdf";
        if(! file_exists($dir)) {
            // dir doesn't exist
            if(dol_mkdir($dir) < 0) {
                $this->error=$langs->transnoentities("ErrorCanNotCreateDir", $dir);
                return 0;
            }
        } else { //dir does exist
            // Add pdfgeneration hook
            if(! is_object($hookmanager)) {
                include_once DOL_DOCUMENT_ROOT.'/core/class/hookmanager.class.php';
                $hookmanager=new HookManager($this->db);
            }
            $hookmanager->initHooks(array('pdfgeneration'));
            $parameters=array('file'=>$file, 'object'=>$object, 'outputlangs'=>$outputlangs);
            global $action;
            $reshook=$hookmanager->executeHooks('beforePDFCreation', $parameters, $object, $action);// Note that $action and $object may have been modified by some hooks
            // Create pdf instance
            $pdf=pdf_getInstance($this->format);

            $pdf->SetAutoPageBreak(1, 0);
            if(class_exists('TCPDF')) {
                $pdf->setPrintHeader(false);
                $pdf->setPrintFooter(false);
            }
            $pdf->SetFont(pdf_getPDFFont($outputlangs));
            // Set path to the background PDF File
            if(empty($conf->global->MAIN_DISABLE_FPDI) && ! empty($conf->global->MAIN_ADD_PDF_BACKGROUND)) {
                $pagecount = $pdf->setSourceFile($conf->mycompany->dir_output.'/'.$conf->global->MAIN_ADD_PDF_BACKGROUND);
                $tplidx = $pdf->importPage(1);
            }
            //get data
            $tasktimearray=$object->getReportArray();
            $TotalLines=array();
            $userTaskArray=array();
            //order data per user id and calc total per user
            foreach($tasktimearray as $line) {
                $projectid=$line['projectId'];
                $userTaskArray[$projectid][$line['userId']]['lines'][]=$line;
                $TotalLines[$projectid][$line['userId']]+=$line['duration'];
                $TotalLines[$projectid]['Total']+=$line['duration'];
            }
           /* add a line with the total*/
            foreach($userTaskArray as $projectid => $userArray){
                foreach($userArray as $userid => $taskArray) {
                    $userTaskArray[$projectid][$userid]['Total']=formatTime($TotalLines[$projectid][$userid], -2);
                }
                //$userTaskArray[$projectid]['Total']=formatTime($TotalLines[$projectid]['Total'], -2);
            }
            //init the pdf
            $pdf->Open();
            $pagenb=0;
            $pdf->SetDrawColor(128, 128, 128);
            $pdf->SetTitle($outputlangs->convToOutputCharset($object->name));
            $pdf->SetSubject($outputlangs->transnoentities("ProjectTimeReport"));
            $pdf->SetCreator("Dolibarr ".DOL_VERSION);
            $pdf->SetAuthor($outputlangs->convToOutputCharset($user->getFullName($outputlangs)));
            $pdf->SetKeyWords(implode(',', $object->ref));//FIXME add all project refs
            if(! empty($conf->global->MAIN_DISABLE_PDF_COMPRESSION)) $pdf->SetCompression(false);
            $pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite);// Left, Top, Right
            //generate pages per userid
            foreach($userTaskArray as $projectid => $userArray){
                //if($pagenb!=0)
                foreach($userArray as $userid => $taskArray) {
                  // New page
                    $pagenb++;
                    $pagenb=$this->writeUser($pdf, $tplidx, $object, $outputlangs, $pagenb, $taskArray, $projectid);
                }
            }
            //if(method_exists($pdf, 'AliasNbPages')) $pdf->AliasNbPages();
            //close pdf
            $pdf->Close();
            $pdf->Output($file, 'F');
            // Add pdfgeneration hook
            $hookmanager->initHooks(array('pdfgeneration'));
            $parameters=array('file'=>$file, 'object'=>$object, 'outputlangs'=>$outputlangs);
            global $action;
            $reshook=$hookmanager->executeHooks('afterPDFCreation', $parameters, $this, $action);// Note that $action and $object may have been modified by some hooks
            if(! empty($conf->global->MAIN_UMASK))
                    @chmod($file, octdec($conf->global->MAIN_UMASK));
            $this->result = array('fullpath'=>$file);
            return 1;// Pas d'erreur
        }
    } else {
        $this->error=$langs->transnoentities("ErrorConstantNotDefined", "PROJECT_OUTPUTDIR");
        return 0;
    }
}

    /** function to write the user pages
     *
     * @param PDF $pdf  pdf object
     * @param int $tplidx template id
     * @param object $object Current object
     * @param LANGS $outputlangs language object
     * @param int $pagenb   page start
     * @param array $tasktimearray data to dispaly
     *  @param  int             $projectid project of the page
     * @return int  number of pages at the end
     */
    public function writeUser(&$pdf, $tplidx, $object, $outputlangs, $pagenb, $tasktimearray, $projectid)
    {
        global $conf;
        //constant
        $default_font_size = pdf_getPDFFontSize($outputlangs);        // Must be after pdf_getInstance
        //$heightforfreetext= (isset($conf->global->MAIN_PDF_FREETEXT_HEIGHT)?$conf->global->MAIN_PDF_FREETEXT_HEIGHT:5);        // Height reserved to output the free text on last page
        $tab_top = 42;
        $tab_top_newpage = (empty($conf->global->MAIN_PDF_DONOTREPEAT_HEAD)?42:10);
        $writable_height = 170;
        $tab_height_newpage = (empty($conf->global->MAIN_PDF_DONOTREPEAT_HEAD)?170:202);
        $height_note=0;
        $tab_height = $writable_height - $height_note;
        //$cur_tab_height=$tab_height;
        $HeightSignBox=30;
        $heightforinfotot = 40;
        $heightforfooter = $this->marge_basse+1;        // Height reserved to output the footer(value include bottom margin)
        $pageposbefore=0;
        $heightoftitleline =6;
        $bottomlasttab=$this->page_hauteur - $heightforinfotot - $heightforfooter + 1;
        $widthSignBox=($this->page_largeur-$this->marge_gauche-$this->marge_droite)/2-1;
        $cur_tab_height=$tab_height;
        $pdf->AddPage();
        //init pdf cursor
        $pdf->setPage($pagenb);
        if(! empty($tplidx)) $pdf->useTemplate($tplidx);
        $pdf->setPageOrientation('', 1, $heightforfooter);        // The only function to edit the bottom margin of current page to set it.
        $this->pageHead($pdf, $object, 1, $outputlangs, $projectid, $tasktimearray['lines'][0]['userName']);
        $pdf->SetFont('', '', $default_font_size - 1);
        $pdf->MultiCell(0, 3, '');                // Set interline to 3
        $pdf->SetTextColor(0, 0, 0);
        $nexY = $tab_top + $heightoftitleline + 1;
        $nblignes=count($tasktimearray['lines']);
        // Loop on each lines but total
        for ($i = 0 ;$i < $nblignes ;$i++) {
            // move the cusor to add space between records
            $curY = ($i == 0)? $nexY:$nexY+2;
            $pageposbefore=$pdf->getPage();
            $posybefore=$curY;
            //try to write line
            $tasktimearray['lines'][$i]['ref']=$i;
            $pdf->startTransaction();
            $posyafter=$this->writeLine($pdf, $tasktimearray['lines'][$i], $curY, $outputlangs);
            $pageposafter=$pdf->getPage();
            // looks if the record fit int he current page
            $addpagebreak=false;
            $lineheight=$posyafter-$posybefore;
            if($pageposafter>$pageposbefore) {
                // auto page break
                $addpagebreak=true;
            } elseif($posyafter>$this->page_hauteur - $heightforfooter - $heightforinfotot) {
                // in the sign zone, check if a new page will be required
                if($posyafter >($this->page_hauteur -($heightforfooter))) {
                    // There is a pagebreak, shouldn't happen as this should trigger a auto page break {
                    $addpagebreak=true;
                } elseif(($nblignes-$i)*($lineheight)< $this->page_hauteur - $heightforfooter - $posyafter) {
                    // not enough space for te remaining line and the sign box {
                    $addpagebreak=true;
                }
            } elseif(isset($tasktimearray['lines'][$i+1]['pagebreak']) && $tasktimearray['lines'][$i+1]['pagebreak']) {
                //pagebreak mentionned on the next line
                $addpagebreak=true;
            }
           // action when a page break is required : rollback and write on the next page
            if($addpagebreak == true) {
                $cur_tab_height=$tab_height_newpage;
                $pdf->rollbackTransaction(true);
                // new page
                $pageposafter=$pageposbefore+1;
                $pdf->AddPage();
                // init page
                if(! empty($tplidx)) $pdf->useTemplate($tplidx);
                $pdf->setPage($pageposafter);
                if(empty($conf->global->MAIN_PDF_DONOTREPEAT_HEAD)) $this->pageHead($pdf, $object, 1, $outputlangs, $projectid, $tasktimearray['lines'][0]['userName']);
                $pdf->SetFont('', '', $default_font_size - 1);// On repositionne la police par defaut
                $pdf->MultiCell(0, 3, '');                // Set interline to 3
                $pdf->SetTextColor(0, 0, 0);
                $pdf->setPageOrientation('', 1, $heightforfooter);        // The only function to edit the bottom margin of current page to set it.
                //write on the next page
                $curY = $tab_top_newpage + $heightoftitleline + 1;
                // Detect if some page were added automatically and output _tableau for past pages
                $nexY =$this->writeLine($pdf, $tasktimearray['lines'][$i], $curY, $outputlangs);
                $pdf->setPage($pagenb);
                //Write the table border and footer on the previous page
                if($pagenb == 1) {
                    $this->tableau($pdf, $tab_top, $this->page_hauteur - $tab_top - $heightforfooter, $heightoftitleline, $outputlangs, 0);
                } else {
                    $this->tableau($pdf, $tab_top_newpage, $this->page_hauteur - $tab_top_newpage - $heightforfooter, $heightoftitleline, $outputlangs, 1);
                }
                //$this->pageFoot($pdf, $object, $outputlangs, 1);
                $pdf->setPage($pagenb);
                $pdf->setPageOrientation('', 1, 0);        // The only function to edit the bottom margin of current page to set it.
                if(empty($conf->global->MAIN_PDF_DONOTREPEAT_HEAD)) $this->pageHead($pdf, $object, 0, $outputlangs, $startday, $stopday, $projectid);
                $pagenb++;
                $pdf->setPage($pageposafter);
                $pageposafter=$pdf->getPage();
            } else {
                // No pagebreak, commit transaction {
                $pdf->commitTransaction();
                $nexY =$posyafter;
                $pageposafter=$pdf->getPage();
            }
        }
        // Show table border for last page
        if($pagenb == 1) {
            $this->tableau($pdf, $tab_top, $this->page_hauteur - $tab_top - $heightforinfotot -   $heightforfooter, $heightoftitleline, $outputlangs, 0);
        } else {
            $this->tableau($pdf, $tab_top_newpage, $this->page_hauteur - $tab_top_newpage - $heightforinfotot   - $heightforfooter, $heightoftitleline, $outputlangs, 0);
        }// show the sign box & total on the last page for the user
        if(empty($conf->global->TIMESHEET_PDF_HIDE_SIGNBOX)) {
            $pdf->SetFont('', 'B', $default_font_size);
            $txtTotal= $tasktimearray['Total']." ".(($conf->global->TIMESHEET_INVOICE_TIMETYPE == "days")?$outputlangs->transnoentities('Days'):$outputlangs->transnoentities('Hours'));
            $pdf->writeHTMLCell(60, 3, $this->page_largeur-$this->marge_droite-60, $bottomlasttab, $outputlangs->transnoentities('Total').": ", 0, 1, 0, true, 'L');
            $pdf->writeHTMLCell(60, 3, $this->page_largeur-$this->marge_droite-60, $bottomlasttab, $txtTotal, 0, 1, 0, true, 'R');
            $pdf->SetFont('', '', $default_font_size - 1);
            $pdf->writeHTMLCell(80, 3, $this->marge_gauche+1, $bottomlasttab+7, $outputlangs->transnoentities('employeeSignature'), 0, 1);
            $pdf->writeHTMLCell(80, 3, $this->marge_gauche+$widthSignBox+2, $bottomlasttab+7, $outputlangs->transnoentities('customerSignature'), 0, 1);
            $nexY = $pdf->GetY();
            //$height_note=$nexY-$tab_top;
            //Rect prend une longueur en 3eme param
            $pdf->SetDrawColor(192, 192, 192);
            //draw left rect for total
            $pdf->Rect($this->page_largeur-$this->marge_droite-60, $bottomlasttab, 60, 5);
             //draw right rect for employee sign
            $pdf->Rect($this->marge_gauche, $bottomlasttab+6, $widthSignBox, $HeightSignBox);
             //draw left rect for provider sign
            $pdf->Rect($this->marge_gauche+$widthSignBox+1, $bottomlasttab+6, $widthSignBox, $HeightSignBox);
        }
        // Pied de page
        //$this->pageFoot($pdf, $object, $outputlangs);
        //$nexY = $tab_top + $heightoftitleline + 1;
        return $pagenb;
    }
/**
 *   Show lines
 *
 *   @param                PDF                        $pdf                Object PDF
 *   @param                string                  $line                array containing the data to display
 *   @param                int                        $curY                position of the cursor on the pdf page
 *   @param                Translate               $outputlangs        Langs object
 *   @return        void
 */
public function writeLine(&$pdf, $line, $curY, $outputlangs)
{
    global $conf;
    $ref=$line['ref'];
    $libelleline="";
    switch($this->noteISOtask) {
        case 2: // show task and Note
            $libelleline=$line['taskLabel'].(empty($line['note'])?'':': '.$line['note']);
            break;
        case 1:       // show note
            $libelleline=$line['note'];
             break;
        case 0: //show task
        default:
            $libelleline=$line['taskLabel'];
    }
    $userName=$line['userName'];
    $date=dol_print_date($line['date'], 'day');
    //$duration=($tasktimearray[$i]['duration']>0)?convertSecondToTime((int) $tasktimearray[$i]['duration'], 'allhourmin'):'';
    $duration=formatTime($line['duration'], -2);
    // Ref of task
    $pdf->SetXY($this->posxref, $curY);
    $pdf->MultiCell($this->posxdate-$this->posxref, 0, dol_string_nohtmltag($ref), 0, 'L');
    $nexY=max($nexY, $pdf->GetY());
    // date
    $pdf->SetXY($this->posxdate, $curY);
    $pdf->MultiCell($this->posxlabel-$this->posxdate, 0, $date, 0, 'L');
    $nexY=max($nexY, $pdf->GetY());
    //label
    $pdf->SetXY($this->posxlabel, $curY);
    $pdf->MultiCell($this->posxduration-$this->posxlabel, 3, dol_string_nohtmltag($libelleline), 0, 'L');
    $nexY=max($nexY, $pdf->GetY());
    // Workler
    //$pdf->SetXY($this->posxworker, $curY);
    //$pdf->MultiCell($this->posxlabel-$this->posxworker, 0, $userName?substr($userName, 0, 23):'', 0, 'L');
    // Duration
    $pdf->SetXY($this->posxduration, $curY);
    $pdf->MultiCell($this->page_largeur-$this->marge_droite-$this->posxduration, 3, $duration, 0, 'R');
    $nexY=max($nexY, $pdf->GetY());
    // Add dash line between entries
    if(! empty($conf->global->MAIN_PDF_DASH_BETWEEN_LINES)) {
        //$pdf->setPage($pageposafter);
        $pdf->SetLineStyle(array('dash'=>'1, 1', 'color'=>array(80, 80, 80)));
        //$pdf->SetDrawColor(190, 190, 200);
        $pdf->line($this->marge_gauche, $nexY+1, $this->page_largeur - $this->marge_droite, $nexY+1);
        $pdf->SetLineStyle(array('dash'=>0));
    }
    return $nexY;
}
/**
 *   Show table for lines
 *
 *   @param                PDF                        $pdf                Object PDF
 *   @param                string                $tab_top                Top position of table
 *   @param                string                $tab_height                Height of table(rectangle)
 *   @param                int                        $heightoftitleline                height of line
 *   @param                Translate        $outputlangs        Langs object
 *   @param                int                        $hidetop                Hide top bar of array
 *   @return        void
 */
public function tableau(&$pdf, $tab_top, $tab_height, $heightoftitleline, $outputlangs, $hidetop = 0)
{
    global $conf;
    //$heightoftitleline = 10;
    $default_font_size = pdf_getPDFFontSize($outputlangs);
    $pdf->SetDrawColor(128, 128, 128);
    // Draw rect of all tab(title + lines). Rect prend une longueur en 3eme param
    $pdf->Rect($this->marge_gauche, $tab_top, $this->page_largeur-$this->marge_gauche-$this->marge_droite, $tab_height);
    // line prend une position y en 3eme param
    if($hidetop == 0){
        $pdf->line($this->marge_gauche, $tab_top+$heightoftitleline, $this->page_largeur-$this->marge_droite, $tab_top+$heightoftitleline);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFont('', 'B', $default_font_size);
        //ref title
        $pdf->SetXY($this->posxref, $tab_top+1);
        $pdf->MultiCell($this->posxdate-$this->posxref, 3, "#", '', 'L');
        //date ttile
        $pdf->SetXY($this->posxdate, $tab_top+1);
        $pdf->MultiCell($this->posxlabel-$this->posxdate, 3, $outputlangs->transnoentities('Date'), 0, 'L');
        // worker title
        //$pdf->SetXY($this->posxworker, $tab_top+1);
        //$pdf->MultiCell($this->posxdate-$this->posxworker, 3, $outputlangs->transnoentities("Name"), 0, 'L');
        //task title
        $pdf->SetXY($this->posxlabel, $tab_top+1);
        $libelleline="";
        switch($this->noteISOtask) {
            case 2: // show task and Note
                $libelleline=$outputlangs->transnoentities("Task").':'.$outputlangs->transnoentities("Note");
                break;
            case 1:       // show note
                $libelleline=$outputlangs->transnoentities("Note");
                 break;
            case 0: //show task
            default:
                $libelleline=$outputlangs->transnoentities("Task");
        }
        $pdf->MultiCell($this->posxduration-$this->posxlabel, 3, $libelleline, 0, 'L');
        //duration title
        $pdf->SetXY($this->posxduration, $tab_top+1);
        if($conf->global->TIMESHEET_INVOICE_TIMETYPE == "hours") {
            $pdf->MultiCell($this->page_largeur - $this->marge_droite - $this->posxduration, 3, 'h:m', 0, 'R');
        } else{
            $pdf->MultiCell($this->page_largeur - $this->marge_droite - $this->posxduration, 3, $outputlangs->transnoentities("Days"), 0, 'R');
        }
    }
}
/**
 *  Show top header of page.
 *
 *  @param        PDF                        $pdf                Object PDF
 *  @param  Project                $object        Object to show
 *  @param  int                $showaddress    0=no, 1=yes
 *  @param  Translate        $outputlangs        Object lang for output
 *  @param  int             $projectid project of the page
 *  @param  string        $userName    user name to be displayed
 *  @return        void
 */
public function pageHead(&$pdf, $object, $showaddress, $outputlangs, $projectid, $userName = "")
{
    global $langs, $conf, $mysoc;
    $default_font_size = pdf_getPDFFontSize($outputlangs);
    pdf_pagehead($pdf, $outputlangs, $this->page_hauteur);
    $pdf->SetTextColor(0, 0, 60);
    $pdf->SetFont('', 'B', $default_font_size + 3);
    // define initial position on the cursor on the pdf page
    $posx=$this->page_largeur-$this->marge_droite-100;
    $posy=$this->marge_haute;
    $pdf->SetXY($this->marge_gauche, $posy);
    $height=$default_font_size + 3;
    // Logo or company name
    $logo=$conf->mycompany->dir_output.'/logos/'.$mysoc->logo;
    $logoWidth=0;
    if($mysoc->logo) {
        if(is_readable($logo)) {
            $height=pdf_getHeightForLogo($logo);
            $tmp=dol_getImageSize($logo, $url);
            $logoWidth=$tmp['width']>130?130:$tmp['width'];
            $pdf->Image($logo, $this->marge_gauche, $posy, 0, $height);        // width=0(auto)
        } else {
                $pdf->SetTextColor(200, 0, 0);
                $pdf->SetFont('', 'B', $default_font_size - 2);
                $pdf->MultiCell(100, 3, $langs->transnoentities("ErrorLogoFileNotFound", $logo), 0, 'L');
                $pdf->MultiCell(100, 3, $langs->transnoentities("ErrorGoToModuleSetup"), 0, 'L');
        }
    } else {
        $pdf->MultiCell(100, 4, $outputlangs->transnoentities($this->emetteur->name), 0, 'L');
        if($showaddress == true){
            $pdf->MultiCell(100, 4+$height, $outputlangs->transnoentities($mysoc->address), 0, 'L');
            $pdf->MultiCell(100, 4+$height*2, $outputlangs->transnoentities($mysoc->zip.' - '.$mysoc->town), 0, 'L');
        }
    }
//pdf title
    $pdf->SetFont('', 'B', $default_font_size +1);
    $pdf->SetXY($this->marge_gauche+$logoWidth, $posy);
    $pdf->SetTextColor(0, 0, 60);
    $pdf->MultiCell($this->page_largeur - $this->marge_gauche -  $this->marge_droite  - $logoWidth, 4, $outputlangs->convToOutputCharset($object->ref[$projectid]), '', 'R');
    //worke name
    if(!empty($userName) && !$conf->global->TIMESHEET_HIDE_NAME) {
        $pdf->SetXY($this->marge_gauche, $height+$default_font_size + 3);
        $pdf->MultiCell($this->page_largeur - $this->marge_gauche -  $this->marge_droite, 4, $outputlangs->transnoentities('Employee').': '.$outputlangs->convToOutputCharset($userName), 0, 'L');
    }
    $pdf->SetFont('', '', $default_font_size);
    //dateStart
    $posy+=12;
    $pdf->SetXY($posx, $posy);
    $pdf->SetTextColor(0, 0, 60);
    $pdf->MultiCell(100, 4, $outputlangs->transnoentities("DateStart")." : " . dol_print_date($object->startDate, 'day', false, $outputlangs, true), '', 'R');
    //DateStop
    $posy+=5;
    $pdf->SetXY($posx, $posy);
    $pdf->MultiCell(100, 4, $outputlangs->transnoentities("DateEnd")." : " . dol_print_date($object->stopDate, 'day', false, $outputlangs, true), '', 'R');
    // third party name
    if(is_object($object->thirdparty[$projectid])) {
            $posy+=5;
            $pdf->SetXY($posx, $posy);
            $pdf->MultiCell(100, 4, $outputlangs->transnoentities("ThirdParty")." : " . $object->thirdparty[$projectid]->getFullName($outputlangs), '', 'R');
    }
    $pdf->SetTextColor(0, 0, 60);
}
/**
 *        Show footer of page. Need this->emetteur object
 *
 *        @param        PDF                        $pdf                        PDF
 *                @param        Project                $object                                Object to show
 *      @param        Translate        $outputlangs                Object lang for output
 *      @param        int                        $hidefreetext                1=Hide free text
 *      @return        integer
 */
/*function pageFoot(&$pdf, $object, $outputlangs, $hidefreetext=0)
{
    global $conf;
    //$showdetails=$conf->global->MAIN_GENERATE_DOCUMENTS_SHOW_FOOT_DETAILS;
    //return pdf_pagefoot($pdf, $outputlangs, 'PROJECT_FREE_TEXT', $this->emetteur, $this->marge_basse, $this->marge_gauche, $this->page_hauteur, $object, $showdetails, $hidefreetext);
}*/
}
