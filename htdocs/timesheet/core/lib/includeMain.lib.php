<?php
/*
 * Copyright (C) 2015	   Patrick DELCROIX     <patrick@pmpd.eu>
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
//global $db;
// FIXME Ver. 3
// Define status
$res = 0;
$path = dirname(__FILE__);
if (! $res && file_exists($path."/dev.inc.php"))
{
    $res = @include $path.'/dev.inc.php';
}
if (! $res && ! empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) $res = @include($_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php");
if (! $res && file_exists($path."/../../../main.inc.php"))
{
    $res = @include $path.'/../../../main.inc.php';// in HTdocs
    $_SERVER["CONTEXT_DOCUMENT_ROOT"] = realpath($path."/../../../");
}
if (! $res && file_exists($path."/../../../../main.inc.php"))
{
    $res = @include $path.'/../../../../main.inc.php';//in custom
    $_SERVER["CONTEXT_DOCUMENT_ROOT"] = realpath($path."/../../../../");
}
if (! $res && file_exists($path."/../../../../../main.inc.php"))
{
    $res = @include $path.'/../../../../../main.inc.php';//in custom
    $_SERVER["CONTEXT_DOCUMENT_ROOT"] = realpath($path."/../../");
}
if (! $res) die("Include of main fails") ;
