<?php
/*
 * Copyright (C) 2018 Patric Delcroix <pmpdelcroix@gmail.com>
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
include 'lib/includeMain.lib.php';
 global $conf, $langs, $db;
 top_httphead();
//get the token, exit if
$token = GETPOST('token', 'apha');
if (!isset($_SESSION['ajaxQuerry'][$token])) {
    ob_end_flush();
    exit();
}
$sqlarray = $_SESSION['ajaxQuerry'][$token]['sql'];
$fields = $_SESSION['ajaxQuerry'][$token]['fields'];
$htmlarray = $_SESSION['ajaxQuerry'][$token]['html'];
$addtionnalChoices = $_SESSION['ajaxQuerry'][$token]['option'];
$separator = isset($htmlarray['separator'])?$htmlarray['separator']:' ';
 $search = GETPOST($htmlName, 'alpha');
//find if barckets
$posBs = strpos($htmlName, '[');
if ($posBs>0) {
    $subStrL1 = substr($htmlName, 0, $posBs);
    $search = $_GET[$subStrL1];
    while(is_array($search))
{// assumption there is only one value in the array
        $search = array_pop($search);
    }
}
        $SelectOptions = '';
    $selectedValue = '';
    $sql = 'SELECT DISTINCT ';
    $sql .= $sqlarray['keyfield'];
    $sql .= ', '.$sqlarray['fields'];
    $sql .= ' FROM '.MAIN_DB_PREFIX.$sqlarray['table'].' as t';
    if (isset($sqlarray['join']) && !empty($sqlarray['join']))
            $sql .= ' '.$sqlarray['join'];
    if (isset($sqlarray['where']) && !empty($sqlarray['where']))
            $sql .= ' WHERE '.$sqlarray['where'];
    if (isset($sqlarray['tail']) && !empty($sqlarray['tail']))
            $sql .= ' '.$sqlarray['tail'];
    dol_syslog('form::ajax_select_generic ', LOG_DEBUG);
    $return_arr = array();
    $resql = $db->query($sql);
   //remove the 't. from key fields
    $startkey = strpos($sqlarray['keyfield'], '.');
    $labelKey = ($startkey)?substr($sqlarray['keyfield'], $startkey+1):$sqlarray['keyfield'];
    if ($resql) {
          // support AS in the fields ex $field1 = 'CONTACT(u.firstname, ' ', u.lastname) AS fullname'
        // with sqltail = 'JOIN llx_user as u ON t.fk_user = u.rowid'
        $listFields = explode(', ', $sqlarray['fields']);
        $fields = array();
    foreach ($listFields as $item) {
        $start = MAX(strpos($item, ' AS '), strpos($item, ' as '));
        $start2 = strpos($item, '.');
        $label = $item;
        if ($start) {
            $label = substr($item, $start+4);
        } elseif ($start2) {
            $label = substr($item, $start2+1);
        }
        $fields[] = array('select' => $item, 'label' => trim($label));
    }
        $i = 0;
         //return $table."this->db".$field;
        $num = $db->num_rows($resql);
        while($i < $num)
        {
            $obj = $db->fetch_object($resql);
            if ($obj) {
                $label = '';
                foreach ($fields as $item) {
                    if (!empty($label))$label .= $separator;
                    $label .= $obj->{$item['label']};
                }
                $row_array['label'] = $label;
                $value = $obj->{$labelKey};
                //$row_array['value'] = $value;
                $row_array['value'] = $label;
                $row_array['key'] = $value;
                array_push($return_arr, $row_array);
            }
            $i++;
        }
        if ($addtionnalChoices)foreach ($addtionnalChoices as $value => $label) {
                $row_array['label'] = $label;
                $row_array['value'] = $label;
                $row_array['key'] = $value;
            array_push($return_arr, $row_array);
        }
    }
      echo json_encode($return_arr);
