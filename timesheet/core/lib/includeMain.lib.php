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
// FIXME Ver. 3 
// Define status

define("STATUS", [
     0 => "NULL",
     1 => "DRAFT",
     2 => "SUBMITTED",
     3 => "APPROVED",
     4 => "CANCELLED",
     5 => "REJECTED",
     6 => "CHALLENGED",
     7 => "INVOICED",
     8 => "UNDERAPPROVAL",
     9 => "'PLANNED"
]);
define("REDUNDANCY", [
     0 => "NULL",
     1 => "NONE",
     2 => "WEEK",
     3 => "MONTH",
     4 => "QUARTER",
     5 => "YEAR"
]);
define("LINKED_ITEM", [
     0 => "NULL",
     1 => "NONE",
     2 => "TASK",
     3 => "PROJECT",
     4 => "TIMESPENT"
]);
$roles=array(0=> 'team', 1=> 'project',2=>'customer',3=>'supplier',4=>'other');

$res=0;

if (! $res && file_exists("../main.inc.php")) $res=@include '../main.inc.php';		
if (! $res && file_exists("../../main.inc.php")) $res=@include '../../main.inc.php';
if (! $res && file_exists("../../../main.inc.php")) $res=@include '../../../main.inc.php';
if (! $res && file_exists("../../../../main.inc.php")) $res=@include '../../../../main.inc.php';
if (! $res && file_exists("core/lib/dev.inc.php")) $res=@include 'core/lib/dev.inc.php';
if (! $res && file_exists("../core/lib/dev.inc.php")) $res=@include '../core/lib/dev.inc.php';
if (! $res && file_exists("../../core/lib/dev.inc.php")) $res=@include '../../core/lib/dev.inc.php';
if (! $res) die("Include of main fails")


?>
