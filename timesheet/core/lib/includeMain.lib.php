<?php
/*
 * Copyright (C) 2015	   Patrick DELCROIX     <pmpdelcroix@gmail.com>
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
//global $db;     
/* FIXME Ver. 3 
// Define status
define('DRAFT_STATUS',1);
define('SUBMITTED_STATUS',2);
define('APPROVED_STATUS',3);
define('CANCELLED_STATUS',4);
define('REJECTED_STATUS',5);
define('CHALLENGED_STATUS',6);
define('INVOICED_STATUS',7);
define('UNDERAPPROVAL_STATUS',8);
define('PLANNED_STATUS',9);
//define planned time link type
define('NO_LINK',1);
define('TASK_LINK',2);
define('PROJECT_LINK',3);
define('TIMESPENT_LINK',4);

*/


$res=0;
if($_SERVER['SERVER_NAME']== 'ide.pmpd.eu'){
    
    $devPath='';
    if(strpos($_SERVER['PHP_SELF'], 'dolibarr-min')>0) $devPath="/var/www/html/dolibarr-min";
    else $devPath="/var/www/html/dolibarr";
    if (file_exists($devPath."/htdocs/main.inc.php")) $res=@include $devPath."/htdocs/main.inc.php";     // Used on dev env only
    if (! $res && file_exists("../../../../dolibarr/htdocs/main.inc.php")) $res=@include '../../../../dolibarr/htdocs/main.inc.php';   // Used on dev env only  
}
if (! $res && file_exists("../main.inc.php")) $res=@include '../main.inc.php';		
if (! $res && file_exists("../../main.inc.php")) $res=@include '../../main.inc.php';
if (! $res && file_exists("../../../main.inc.php")) $res=@include '../../../main.inc.php';
if (! $res && file_exists("../../../../main.inc.php")) $res=@include '../../../../main.inc.php';

if (! $res) die("Include of main fails")


?>