<?php
/* Copyright (C) 2010-2012 Regis Houssin  <regis.houssin@capnetworks.com>
 * Copyright (C) 2018      Laurent Destailleur <eldy@users.sourceforge.net>
 *
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
 * or see http://www.gnu.org/
 */

/**
 *	\file       htdocs/core/modules/project/doc/pdf_rat.modules.php
 *	\ingroup    project
 *	\brief      File of class to generate project document Baleine
 *	\author	    Regis Houssin
 */
require_once './class/TimesheetReport.class.php';
require_once './core/modules/modules_timesheet.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';


/**
 *	Class to manage generation of project document Baleine
 */

class pdf_rat extends ModelPDFTimesheetReport
{
	var $emetteur;	// Objet societe qui emet
        var $posxworker;
        var $posxdate;
        var $posxduration;
	/**
	 *	Constructor
	 *
	 *  @param		DoliDB		$db      Database handler
	 */
	function __construct($db)
	{
		global $conf,$langs,$mysoc;

		$langs->load("main");
		$langs->load("projects");
		$langs->load("companies");

		$this->db = $db;
		$this->name = "rat";
		$this->description = $langs->trans("DocumentModelRat");

		// Dimension page pour format A4
		$this->type = 'pdf';
		$formatarray=pdf_getFormat();
		$this->page_largeur = $formatarray['width'];
		$this->page_hauteur = $formatarray['height'];
		$this->format = array($this->page_largeur,$this->page_hauteur);
		$this->marge_gauche=isset($conf->global->MAIN_PDF_MARGIN_LEFT)?$conf->global->MAIN_PDF_MARGIN_LEFT:10;
		$this->marge_droite=isset($conf->global->MAIN_PDF_MARGIN_RIGHT)?$conf->global->MAIN_PDF_MARGIN_RIGHT:10;
		$this->marge_haute =isset($conf->global->MAIN_PDF_MARGIN_TOP)?$conf->global->MAIN_PDF_MARGIN_TOP:10;
		$this->marge_basse =isset($conf->global->MAIN_PDF_MARGIN_BOTTOM)?$conf->global->MAIN_PDF_MARGIN_BOTTOM:10;

		$this->option_logo = 1;                    // Affiche logo FAC_PDF_LOGO
		$this->option_tva = 1;                     // Gere option tva FACTURE_TVAOPTION
		$this->option_codeproduitservice = 1;      // Affiche code produit-service

		// Recupere emmetteur
		$this->emetteur=$mysoc;
		if (! $this->emetteur->country_code) $this->emetteur->country_code=substr($langs->defaultlang,-2);    // By default if not defined

		// Defini position des colonnes
		$this->posxref=$this->marge_gauche+1;
                $this->posxdate=$this->marge_gauche+9;
                $this->posxworker=$this->marge_gauche+27;
		$this->posxlabel=$this->marge_gauche+65;
                
                //$this->posxworkload=$this->marge_gauche+120;
		//$this->posxprogress=$this->marge_gauche+140;
		//$this->posxdatestart=$this->marge_gauche+152;
                
		//$this->posxdateend=$this->marge_gauche+170;
                $this->posxduration=$this->marge_gauche+180;
		if ($this->page_largeur < 210) // To work with US executive format
		{
			$this->posxref-=20;
			$this->posxlabel-=20;
			$this->posxworker-=20;
			$this->posxdate-=20;
			$this->posxduration-=20;
		}
	}


    /**
     *	Fonction generant le projet sur le disque
     *
     *	@param	Project		$object   		Object project a generer
     *	@param	Translate	$outputlangs	Lang output object
     *	@return	int         				1 if OK, <=0 if KO
     */
    function write_file($object,$outputlangs)
    {
        global $conf, $hookmanager, $langs, $user;

        if (! is_object($outputlangs)) $outputlangs=$langs;
        // For backward compatibility with FPDF, force output charset to ISO, because FPDF expect text to be encoded in ISO
        if (! empty($conf->global->MAIN_USE_FPDF)) $outputlangs->charset_output='ISO-8859-1';

        $outputlangs->load("main");
        $outputlangs->load("dict");
        $outputlangs->load("companies");
        $outputlangs->load("projects");
        $outputlangs->load("timesheet@timesheet");
        if ($conf->projet->dir_output)
        {
                //$nblignes = count($tasktimearray);  // This is set later with array of tasks


             $objectref =  dol_sanitizeFileName($object->ref);
                $dir = $conf->timesheet->dir_output.'/reports/';
            //$dir=DOLIBARR_MAIN_DOCUMENT_ROOT.'./Timesheet/';
                //if (! preg_match('/specimen/i',$objectref)) $dir.= "/" . $objectref;
                $file = $dir . "/" . $objectref . ".pdf";

                if (! file_exists($dir))
                {
                        if (dol_mkdir($dir) < 0)
                        {
                                $this->error=$langs->transnoentities("ErrorCanNotCreateDir",$dir);
                                return 0;
                        }
                }

                if (file_exists($dir))
                {
                        // Add pdfgeneration hook
                        if (! is_object($hookmanager))
                        {
                                include_once DOL_DOCUMENT_ROOT.'/core/class/hookmanager.class.php';
                                $hookmanager=new HookManager($this->db);
                        }
                        $hookmanager->initHooks(array('pdfgeneration'));
                        $parameters=array('file'=>$file,'object'=>$object,'outputlangs'=>$outputlangs);
                        global $action;
                        $reshook=$hookmanager->executeHooks('beforePDFCreation',$parameters,$object,$action);    // Note that $action and $object may have been modified by some hooks

                        // Create pdf instance
                        $pdf=pdf_getInstance($this->format);
                        $default_font_size = pdf_getPDFFontSize($outputlangs);	// Must be after pdf_getInstance
                        $pdf->SetAutoPageBreak(1,0);

                        $heightforinfotot = 40;	// Height reserved to output the info and total part
                $heightforfreetext= (isset($conf->global->MAIN_PDF_FREETEXT_HEIGHT)?$conf->global->MAIN_PDF_FREETEXT_HEIGHT:5);	// Height reserved to output the free text on last page
            $heightforfooter = $this->marge_basse + 8;	// Height reserved to output the footer (value include bottom margin)

        if (class_exists('TCPDF'))
        {
            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(false);
        }
        $pdf->SetFont(pdf_getPDFFont($outputlangs));
        // Set path to the background PDF File
        if (empty($conf->global->MAIN_DISABLE_FPDI) && ! empty($conf->global->MAIN_ADD_PDF_BACKGROUND))
        {
            $pagecount = $pdf->setSourceFile($conf->mycompany->dir_output.'/'.$conf->global->MAIN_ADD_PDF_BACKGROUND);
            $tplidx = $pdf->importPage(1);
        }
            // Complete object by loading several other informations
            //$task = new Task($this->db);
            //$tasktimearray = $task->getTasksArray(0,0,$object->id);
            $tasktimearray=$object->getReportArray('','',' ORDER BY task_date,taskid,userid');
            $TotalLines=array();
            $userTaskArray=array();
            foreach($tasktimearray as $line){
                $userTaskArray[$line['userid']][]=$line;
                $TotalLines[$line['userid']]+=$line['duration'];
            }
           
            foreach($userTaskArray as $userid => $taskArray){
            $userTaskArray[$userid][]=array('projectId'=>'','projectLabel'=>convertSecondToTime($TotalLines[$userid],'allhourmin'),
                'taskId'=>'','taskLabel'=>convertSecondToTime($TotalLines[$userid],'allhourmin'),  
                'userid'=>'','userName'=>$outputlangs->transnoentities('Total'),'duration'=>0,
                'date'=>'');
            }

            $pdf->Open();
            $pagenb=0;
            $pdf->SetDrawColor(128,128,128);

            $pdf->SetTitle($outputlangs->convToOutputCharset($object->name));
            $pdf->SetSubject($outputlangs->transnoentities("ProjectTimeReport"));
            $pdf->SetCreator("Dolibarr ".DOL_VERSION);
            $pdf->SetAuthor($outputlangs->convToOutputCharset($user->getFullName($outputlangs)));
            $pdf->SetKeyWords($outputlangs->convToOutputCharset($object->ref));
            if (! empty($conf->global->MAIN_DISABLE_PDF_COMPRESSION)) $pdf->SetCompression(false);

            $pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite);   // Left, Top, Right

            foreach ($userTaskArray as $userid => $tasktimearray)
            {
                
                // New page
                $pdf->AddPage();
                if (! empty($tplidx)) $pdf->useTemplate($tplidx);
                $pagenb++;
                $this->_pagehead($pdf, $object, 1, $outputlangs,$tasktimearray[0]['userName']);
                $pdf->SetFont('','', $default_font_size - 1);
                $pdf->MultiCell(0, 3, '');		// Set interline to 3
                $pdf->SetTextColor(0,0,0);

                $tab_top = 42;
                $tab_top_newpage = (empty($conf->global->MAIN_PDF_DONOTREPEAT_HEAD)?42:10);
                $tab_height = 170;
                $tab_height_newpage = 190;
                $height_note=0;


                $tab_height = $tab_height - $height_note;
                //$tab_top = $nexY+6;
                $heightoftitleline = 10;
                $iniY = $tab_top + $heightoftitleline + 1;
                $curY = $tab_top + $heightoftitleline + 1;
                $nexY = $tab_top + $heightoftitleline + 1;

                $nblignes=count($tasktimearray);
                // Loop on each lines
                for ($i = 0 ; $i < $nblignes ; $i++)
                {
                    $curY = $nexY;
                    $pdf->SetFont('','', $default_font_size - 1);   // Into loop to work with multipage
                    $pdf->SetTextColor(0,0,0);

                    $pdf->setTopMargin($tab_top_newpage);
                    $pdf->setPageOrientation('', 1, $heightforfooter+$heightforfreetext+$heightforinfotot);	// The only function to edit the bottom margin of current page to set it.
                    $pageposbefore=$pdf->getPage();

                    // Description of line
                    $ref=$i;
                    $libelleline=$tasktimearray[$i]['taskLabel'];
                    $userName=$tasktimearray[$i]['userName'];
                    $date=dol_print_date($tasktimearray[$i]['date'],'day');
                    $duration=($tasktimearray[$i]['duration']>0)?convertSecondToTime((int) $tasktimearray[$i]['duration'],'allhourmin'):'';

                    $showpricebeforepagebreak=1;//FIXME

                    $pdf->startTransaction();
                    // Label
                    $pdf->SetXY($this->posxlabel, $curY);
                    $pdf->MultiCell($this->posxworker-$this->posxlabel, 3, $outputlangs->convToOutputCharset($libelleline), 0, 'L');
                    $pageposafter=$pdf->getPage();
                    if ($pageposafter > $pageposbefore)	// There is a pagebreak
                    {
                        $pdf->rollbackTransaction(true);
                        $pageposafter=$pageposbefore;
                        //print $pageposafter.'-'.$pageposbefore;exit;
                        $pdf->setPageOrientation('', 1, $heightforfooter);	// The only function to edit the bottom margin of current page to set it.
                        // Label
                        $pdf->SetXY($this->posxlabel, $curY);
                        $posybefore=$pdf->GetY();
                        $pdf->MultiCell($this->posxworker-$this->posxlabel, 0, $outputlangs->convToOutputCharset($libelleline), 0, 'L');
                        $pageposafter=$pdf->getPage();
                        $posyafter=$pdf->GetY();
                        if ($posyafter > ($this->page_hauteur - ($heightforfooter+$heightforfreetext+$heightforinfotot)))	// There is no space left for total+free text
                        {
                            if ($i == ($nblignes-1))	// No more lines, and no space left to show total, so we create a new page
                            {
                                $pdf->AddPage('','',true);
                                if (! empty($tplidx)) $pdf->useTemplate($tplidx);
                                if (empty($conf->global->MAIN_PDF_DONOTREPEAT_HEAD)) $this->_pagehead($pdf, $object, 0, $outputlangs);
                                $pdf->setPage($pageposafter+1);
                            }
                        }
                        else
                        {
                            // We found a page break
                            $showpricebeforepagebreak=0;
                            $forcedesconsamepage=1;
                            if ($forcedesconsamepage)
                            {
                                $pdf->rollbackTransaction(true);
                                $pageposafter=$pageposbefore;
                                $pdf->setPageOrientation('', 1, $heightforfooter);	// The only function to edit the bottom margin of current page to set it.

                                $pdf->AddPage('','',true);
                                if (! empty($tplidx)) $pdf->useTemplate($tplidx);
                                if (empty($conf->global->MAIN_PDF_DONOTREPEAT_HEAD)) $this->_pagehead($pdf, $object, 0, $outputlangs);
                                $pdf->setPage($pageposafter+1);
                                $pdf->SetFont('','',  $default_font_size - 1);   // On repositionne la police par defaut
                                $pdf->MultiCell(0, 3, '');		// Set interline to 3
                                $pdf->SetTextColor(0,0,0);

                                $pdf->setPageOrientation('', 1, $heightforfooter);	// The only function to edit the bottom margin of current page to set it.
                                $curY = $tab_top_newpage + $heightoftitleline + 1;

                                // Label
                                $pdf->SetXY($this->posxlabel, $curY);
                                $posybefore=$pdf->GetY();
                                $pdf->MultiCell($this->posxworker-$this->posxlabel, 0, $outputlangs->convToOutputCharset($libelleline), 0, 'L');
                                $pageposafter=$pdf->getPage();
                                $posyafter=$pdf->GetY();
                            }
                        }
                    }
                    else	// No pagebreak
                    {
                            $pdf->commitTransaction();
                    }
                    $posYAfterDescription=$pdf->GetY();

                    $nexY = $pdf->GetY();
                    $pageposafter=$pdf->getPage();
                    $pdf->setPage($pageposbefore);
                    $pdf->setTopMargin($this->marge_haute);
                    $pdf->setPageOrientation('', 1, 0);	// The only function to edit the bottom margin of current page to set it.

                    // We suppose that a too long description is moved completely on next page
                    if ($pageposafter > $pageposbefore && empty($showpricebeforepagebreak)) {
                            $pdf->setPage($pageposafter); $curY = $tab_top_newpage + $heightoftitleline + 1;
                    }

                    $pdf->SetFont('','',  $default_font_size - 1);   // On repositionne la police par defaut

                    // Ref of task
                    $pdf->SetXY($this->posxref, $curY);
                    $pdf->MultiCell($this->posxdate-$this->posxref, 0, $outputlangs->convToOutputCharset($ref), 0, 'L');
                    // date
                    $pdf->SetXY($this->posxdate, $curY);
                    $pdf->MultiCell($this->posxworker-$this->posxdate, 0, $date, 0, 'L');
                    // Workler
                    $pdf->SetXY($this->posxworker, $curY);
                    $pdf->MultiCell($this->posxlabel-$this->posxworker, 0, $userName?substr($userName, 0, 23):'', 0, 'L');

                    // Duration
                    $pdf->SetXY($this->posxduration, $curY);
                    $pdf->MultiCell($this->page_largeur-$this->marge_droite-$this->posxduration, 3, $duration, 0, 'R');

                    // Add line
                    if (! empty($conf->global->MAIN_PDF_DASH_BETWEEN_LINES) && $i < ($nblignes - 1))
                    {
                        $pdf->setPage($pageposafter);
                        $pdf->SetLineStyle(array('dash'=>'1,1','color'=>array(80,80,80)));
                        //$pdf->SetDrawColor(190,190,200);
                        $pdf->line($this->marge_gauche, $nexY+1, $this->page_largeur - $this->marge_droite, $nexY+1);
                        $pdf->SetLineStyle(array('dash'=>0));
                    }

                    $nexY+=2;    // Passe espace entre les lignes

                    // Detect if some page were added automatically and output _tableau for past pages
                    while ($pagenb < $pageposafter)
                    {
                        $pdf->setPage($pagenb);
                        if ($pagenb == 1)
                        {
                                $this->_tableau($pdf, $tab_top, $this->page_hauteur - $tab_top - $heightforfooter, 0, $outputlangs, 0, 1);
                        }
                        else
                        {
                                $this->_tableau($pdf, $tab_top_newpage, $this->page_hauteur - $tab_top_newpage - $heightforfooter, 0, $outputlangs, 1, 1);
                        }
                        $this->_pagefoot($pdf,$object,$outputlangs,1);
                        $pagenb++;
                        $pdf->setPage($pagenb);
                        $pdf->setPageOrientation('', 1, 0);	// The only function to edit the bottom margin of current page to set it.
                        if (empty($conf->global->MAIN_PDF_DONOTREPEAT_HEAD)) $this->_pagehead($pdf, $object, 0, $outputlangs,$startday,$stopday);
                    }
                    if (isset($tasktimearray[$i+1]->pagebreak) && $tasktimearray[$i+1]->pagebreak)
                    {
                        if ($pagenb == 1)
                        {
                                $this->_tableau($pdf, $tab_top, $this->page_hauteur - $tab_top - $heightforfooter, 0, $outputlangs, 0, 1);
                        }
                        else
                        {
                                $this->_tableau($pdf, $tab_top_newpage, $this->page_hauteur - $tab_top_newpage - $heightforfooter, 0, $outputlangs, 1, 1);
                        }
                        $this->_pagefoot($pdf,$object,$outputlangs,1);
                        // New page
                        $pdf->AddPage();
                        if (! empty($tplidx)) $pdf->useTemplate($tplidx);
                        $pagenb++;
                        if (empty($conf->global->MAIN_PDF_DONOTREPEAT_HEAD)) $this->_pagehead($pdf, $object, 0, $outputlangs,$startday,$stopday);
                    }

                }
                //add total

                // Show square
                if ($pagenb == 1)
                        $this->_tableau($pdf, $tab_top, $this->page_hauteur - $tab_top - $heightforinfotot - $heightforfreetext - $heightforfooter, 0, $outputlangs, 0, 0);
                else
                        $this->_tableau($pdf, $tab_top_newpage, $this->page_hauteur - $tab_top_newpage - $heightforinfotot - $heightforfreetext - $heightforfooter, 0, $outputlangs, 1, 0);
                $bottomlasttab=$this->page_hauteur - $heightforinfotot - $heightforfreetext - $heightforfooter + 1;
                if ($showSign=1)
                {
                    $widthSignBox=($this->page_largeur-$this->marge_gauche-$this->marge_droite)/2-1;
                    $HeightSignBox=30;


                    $pdf->SetFont('','', $default_font_size - 1);
                    $pdf->writeHTMLCell(80, 3, $this->marge_gauche+1, $bottomlasttab+1, $outputlangs->transnoentities('employeeSignature'), 0, 1);
                    $pdf->writeHTMLCell(80, 3, $this->marge_gauche+$widthSignBox+2, $bottomlasttab+1, $outputlangs->transnoentities('customerSignature'), 0, 1);
                    $nexY = $pdf->GetY();
                    $height_note=$nexY-$tab_top;

                    // Rect prend une longueur en 3eme param
                    $pdf->SetDrawColor(192,192,192);
                    $pdf->Rect($this->marge_gauche, $bottomlasttab+1, $widthSignBox, $HeightSignBox);
                    $pdf->Rect($this->marge_gauche+$widthSignBox+1, $bottomlasttab+1, $widthSignBox, $HeightSignBox);

                    $tab_height = $tab_height - $height_note;
                    $tab_top = $nexY+6;
                }
                // Pied de page
                $this->_pagefoot($pdf, $object, $outputlangs);
            }
            if (method_exists($pdf, 'AliasNbPages')) $pdf->AliasNbPages();

            $pdf->Close();

            $pdf->Output($file, 'F');

            // Add pdfgeneration hook
            $hookmanager->initHooks(array('pdfgeneration'));
            $parameters=array('file'=>$file, 'object'=>$object, 'outputlangs'=>$outputlangs);
            global $action;
            $reshook=$hookmanager->executeHooks('afterPDFCreation', $parameters, $this, $action);    // Note that $action and $object may have been modified by some hooks

            if (! empty($conf->global->MAIN_UMASK))
                    @chmod($file, octdec($conf->global->MAIN_UMASK));

            $this->result = array('fullpath'=>$file);

            return 1;   // Pas d'erreur
            }else
            {
                $this->error=$langs->transnoentities("ErrorCanNotCreateDir",$dir);
                return 0;
            }
        }
        else
        {
            $this->error=$langs->transnoentities("ErrorConstantNotDefined","PROJECT_OUTPUTDIR");
            return 0;
        }
    }


	/**
	 *   Show table for lines
	 *
	 *   @param		PDF			$pdf     		Object PDF
	 *   @param		string		$tab_top		Top position of table
	 *   @param		string		$tab_height		Height of table (rectangle)
	 *   @param		int			$nexY			Y
	 *   @param		Translate	$outputlangs	Langs object
	 *   @param		int			$hidetop		Hide top bar of array
	 *   @param		int			$hidebottom		Hide bottom bar of array
	 *   @return	void
	 */
	function _tableau(&$pdf, $tab_top, $tab_height, $nexY, $outputlangs, $hidetop=0, $hidebottom=0)
	{
		global $conf,$mysoc;

		$heightoftitleline = 10;

		$default_font_size = pdf_getPDFFontSize($outputlangs);

		$pdf->SetDrawColor(128,128,128);

		// Draw rect of all tab (title + lines). Rect prend une longueur en 3eme param
		$pdf->Rect($this->marge_gauche, $tab_top, $this->page_largeur-$this->marge_gauche-$this->marge_droite, $tab_height);

		// line prend une position y en 3eme param
		$pdf->line($this->marge_gauche, $tab_top+$heightoftitleline, $this->page_largeur-$this->marge_droite, $tab_top+$heightoftitleline);

		$pdf->SetTextColor(0,0,0);
		$pdf->SetFont('','', $default_font_size);

		$pdf->SetXY($this->posxref, $tab_top+1);
		$pdf->MultiCell($this->posxlabel-$this->posxref,3, "#",'','L');
                
                $pdf->SetXY($this->posxdate, $tab_top+1);
		$pdf->MultiCell($this->posxduration-$this->posxdate, 3, $outputlangs->transnoentities('Date'), 0, 'L');


		$pdf->SetXY($this->posxworker, $tab_top+1);
		$pdf->MultiCell($this->posxdate-$this->posxworker, 3, $outputlangs->transnoentities("Name"), 0, 'L');
                
                $pdf->SetXY($this->posxlabel, $tab_top+1);
		$pdf->MultiCell($this->posxworker-$this->posxlabel, 3, $outputlangs->transnoentities("Task"), 0, 'L');




		$pdf->SetXY($this->posxduration, $tab_top+1);
		$pdf->MultiCell($this->page_largeur - $this->marge_droite - $this->posxduration, 3, 'h:m', 0, 'L');
	}

	/**
	 *  Show top header of page.
	 *
	 *  @param	PDF			$pdf     		Object PDF
	 *  @param  Project		$object     	Object to show
	 *  @param  int	    	$showaddress    0=no, 1=yes
	 *  @param  Translate	$outputlangs	Object lang for output
	 *  @return	void
	 */
	function _pagehead(&$pdf, $object, $showaddress, $outputlangs,$userName="")
	{
		global $langs,$conf,$mysoc;

		$default_font_size = pdf_getPDFFontSize($outputlangs);

		pdf_pagehead($pdf,$outputlangs,$this->page_hauteur);

		$pdf->SetTextColor(0,0,60);
		$pdf->SetFont('','B', $default_font_size + 3);

                $posx=$this->page_largeur-$this->marge_droite-100;
		$posy=$this->marge_haute;

		$pdf->SetXY($this->marge_gauche,$posy);
                $height=$default_font_size + 3;
		// Logo
		$logo=$conf->mycompany->dir_output.'/logos/'.$mysoc->logo;
		if ($mysoc->logo)
		{
			if (is_readable($logo))
			{
			    $height=pdf_getHeightForLogo($logo);
			    $pdf->Image($logo, $this->marge_gauche, $posy, 0, $height);	// width=0 (auto)
			}
			else
			{
				$pdf->SetTextColor(200,0,0);
				$pdf->SetFont('','B', $default_font_size - 2);
				$pdf->MultiCell(100, 3, $langs->transnoentities("ErrorLogoFileNotFound",$logo), 0, 'L');
				$pdf->MultiCell(100, 3, $langs->transnoentities("ErrorGoToModuleSetup"), 0, 'L');
			}
		}
		else $pdf->MultiCell(100, 4, $outputlangs->transnoentities($this->emetteur->name), 0, 'L');
                if(!empty($userName)){
                    $pdf->SetXY($this->marge_gauche,$height+$default_font_size + 3);
                    $pdf->MultiCell(100, 4, $outputlangs->transnoentities('Employee').': '.$outputlangs->convToOutputCharset($userName), 0, 'L');
                }
		$pdf->SetFont('','B', $default_font_size + 3);
		$pdf->SetXY($posx,$posy);
		$pdf->SetTextColor(0,0,60);
		$pdf->MultiCell(100, 4, $outputlangs->convToOutputCharset($object->name), '', 'R');
		$pdf->SetFont('','', $default_font_size + 2);

		$posy+=6;
		$pdf->SetXY($posx,$posy);
		$pdf->SetTextColor(0,0,60);
		$pdf->MultiCell(100, 4, $outputlangs->transnoentities("DateStart")." : " . dol_print_date($object->startDate,'day',false,$outputlangs,true), '', 'R');

		$posy+=6;
		$pdf->SetXY($posx,$posy);
		$pdf->MultiCell(100, 4, $outputlangs->transnoentities("DateEnd")." : " . dol_print_date($object->stopDate,'day',false,$outputlangs,true), '', 'R');

		if (is_object($object->thirdparty))
		{
			$posy+=6;
			$pdf->SetXY($posx,$posy);
			$pdf->MultiCell(100, 4, $outputlangs->transnoentities("ThirdParty")." : " . $object->thirdparty->getFullName($outputlangs), '', 'R');
		}

		$pdf->SetTextColor(0,0,60);

		// Add list of linked objects
		/* Removed: A project can have more than thousands linked objects (orders, invoices, proposals, etc....
		$object->fetchObjectLinked();

	    foreach($object->linkedObjects as $objecttype => $objects)
	    {
	    	if ($objecttype == 'commande')
	    	{
	    		$outputlangs->load('orders');
	    		$num=count($objects);
	    		for ($i=0;$i<$num;$i++)
	    		{
	    			$posy+=4;
	    			$pdf->SetXY($posx,$posy);
	    			$pdf->SetFont('','', $default_font_size - 1);
	    			$text=$objects[$i]->ref;
	    			if ($objects[$i]->ref_client) $text.=' ('.$objects[$i]->ref_client.')';
	    			$pdf->MultiCell(100, 4, $outputlangs->transnoentities("RefOrder")." : ".$outputlangs->transnoentities($text), '', 'R');
	    		}
	    	}
	    }
        */
	}

	/**
	 *   	Show footer of page. Need this->emetteur object
     *
	 *   	@param	PDF			$pdf     			PDF
	 * 		@param	Project		$object				Object to show
	 *      @param	Translate	$outputlangs		Object lang for output
	 *      @param	int			$hidefreetext		1=Hide free text
	 *      @return	integer
	 */
	function _pagefoot(&$pdf,$object,$outputlangs,$hidefreetext=0)
	{
		global $conf;
		$showdetails=$conf->global->MAIN_GENERATE_DOCUMENTS_SHOW_FOOT_DETAILS;
		return pdf_pagefoot($pdf,$outputlangs,'PROJECT_FREE_TEXT',$this->emetteur,$this->marge_basse,$this->marge_gauche,$this->page_hauteur,$object,$showdetails,$hidefreetext);
	}

}
