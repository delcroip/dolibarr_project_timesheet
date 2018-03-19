<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */


    
    $devPath='';
    if(strpos($_SERVER['PHP_SELF'], 'dolibarr-min')>0) $devPath="/var/www/html/dolibarr-min";
    else if(strpos($_SERVER['PHP_SELF'], 'dolibarr-6.0.3')>0) $devPath="/var/www/html/dolibarr-6.0.3";
    else if(strpos($_SERVER['PHP_SELF'], 'dolibarr-pgsql')>0) $devPath="/var/www/html/dolibarr-pgsql";
    else $devPath="/var/www/html/dolibarr";
    if (file_exists($devPath."/htdocs/main.inc.php")) $res=@include $devPath."/htdocs/main.inc.php";     // Used on dev env only
    if (! $res && file_exists("../../../../dolibarr/htdocs/main.inc.php")) $res=@include '../../../../dolibarr/htdocs/main.inc.php';   // Used on dev env only  
    