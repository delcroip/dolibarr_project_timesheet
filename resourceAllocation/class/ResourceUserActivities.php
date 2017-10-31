<?php

/* Copyright (C) 2007-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2014	   Juanjo Menent	<jmenent@2byte.es>
 * Copyright (C) 2017      Patrick Delcroix    <pmpdelcroix@gmail.com>
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
 */
// Put here all includes required by your class file
require_once(DOL_DOCUMENT_ROOT."/core/class/commonobject.class.php");
//require_once(DOL_DOCUMENT_ROOT."/societe/class/societe.class.php");
//require_once(DOL_DOCUMENT_ROOT."/product/class/product.class.php");
require_once 'class/ResourceActivity.class.php';
/**
 *	Put here description of your class
 */
class ResourceUserActivity extends CommonObject
{
    var $db;							//!< To store db handler
    var $error;							//!< To return error code (or message)
    var $errors=array();				//!< To return several error codes (or messages)
    var $element='ResourceUserActivity';			//!< Id that identify managed objects
    //var $table_element='timesheet_activity';		//!< Name of table without prefix where object is stored
    
// working variables
    var $activities = null;
    var $date_start='';
    var $date_end;
    var $userid;
    var $status;
    var $note;    
    /**
     *  Constructor
     *
     *  @param	DoliDb          $db      Database handler
     *  @param	Object/int		$user    user object
     */
    function __construct($db,$user=null)
    {
        $this->db = $db;
        $userid=  is_object($user)?$user->id:$user;
        return 1;
        
    }


 /******************************************************************************
  * 
  *              Core function
  * 
  *****************************************************************************/ 


/**
 *  will fetch the activities of the user during the specified timespan
 *
 *  @param	DATETIME         $dateStart      date start
 *  @param	DATETIME	 $dateEnd        user object
 *  @param	Object/int	 $user           user object or userId
 *  @return int 1 ok, <0 KO
 */
function getUserActivities($dateStart, $dateEnd,$user=null){
    
}
    //FIXME
}