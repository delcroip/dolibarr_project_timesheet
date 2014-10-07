<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';

class ProjectTimesheet extends Project
{

	

    public function __construct($db) 
	{
            $this->db = $db;
	}
    public function isOpen($startDate,$stopDate)
    {

            if((empty($this->date_start) || ($this->date_start <= $stopDate)) 
                    && (empty($this->date_end) ||($this->date_end >= $startDate )))
 
            {	
                    return true;
            }else
            {	
                    #return true;
                    return FALSE;

            }
    }
 }