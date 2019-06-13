<?php
/*
 * Copyright (C) 2018           Patrick DELCROIX     <pmpdelcroix@gmail.com>
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
global $langs;
/*
 * function to genegate a select list from a table, the showed text will be a concatenation of some
 * column defined in column bit, the Least sinificative bit will represent the first colum
 *
 *  @param    string               $table                 table which the fk refers to(without prefix)
 *  @param    string               $fieldValue         field of the table which the fk refers to, the one to put in the Valuepart
 *  @param    string               $htmlName        name to the form select
 *  @param    string               $fieldToShow1    first part of the concatenation
 *  @param    string               $fieldToShow2    second part of the concatenation
 *  @param    string               $selected            which value must be selected
 *  @param    string               $separator          separator between the tow contactened fileds
*  @param    string               $sqlTailWhere              to limit per entity, to filter ...
*  @param    string               $selectparam          to add parameters to the select
 *  @param    array(string)             $addtionnalChoices    array of additionnal fields Array['VALUE'] = string to show
*  @param    string               $sqlTailTAble              to add join ...
*  @param    string               $ajaxUrl             path to the ajax handler ajaxSelectGenericHandler.php
 *  @return string                                                   html code
 */
// to be taken into account when passing a Ajax handler
//$conf->global->COMPANY_USE_SEARCH_TO_SELECT
 //$conf->global->PRODUIT_USE_SEARCH_TO_SELECT
 //$conf->global->PROJECT_USE_SEARCH_TO_SELECT
 //$conf->global->RESOURCE_USE_SEARCH_TO_SELECT
//$conf->global->BARCODE_USE_SEARCH_TO_SELECT
//$conf->global->CONTACT_USE_SEARCH_TO_SELECT

/** function to select item
 *
 * @global DoliConf $conf conf object
 * @global DoliLangs $langs lang object
 * @global DoliDB $db   db object
 * @param string[] $sqlarray sql parameters
 * @param string[] $htmlarray html parameters
 * @param int $selected id of the item to be selected
 * @param string[] $addtionnalChoices   additionnal choice not part of the DB
 * @return string HTML code
 */
function select_sellist(
    $sqlarray = array('table'=> 'user', 'keyfield'=> 'rowid', 'fields'=>'firstname, lastname', 'join' => '', 'where'=>'', 'tail'=>''),
    $htmlarray = array('name'=> 'HTMLSellist', 'class'=>'', 'otherparam'=>'', '$ajaxNbChar'=>'', 'separator'=> ' ', 'noajax'=>0),
    $selected = '',
    $addtionnalChoices = array('NULL'=>'NULL')
) {
    global $conf, $langs, $db;
    $noajax = isset($htmlarray['noajax']);
     $ajax = $conf->use_javascript_ajax && !$noajax ;
    if(!isset($sqlarray['table'])|| !isset($sqlarray['keyfield'])||!isset($sqlarray['fields']) || !isset($htmlarray['name'])) {
        return 'error, one of the mandatory field of the function  select_sellist is missing';
    }
    $htmlName = $htmlarray['name'];
    $ajaxNbChar = $htmlarray['ajaxNbChar'];
    $listFields = explode(',', $sqlarray['fields']);
    $fields = array();
    foreach($listFields as $item) {
        $item=trim($item);
        $start = MAX(strpos($item, ' AS '), strpos($item, ' as '));
        $start2 = strpos($item, '.');
        $label = $item;
        if($start) {
            $label = substr($item, $start+4);
        } elseif($start2) {
            $label = substr($item, $start2+1);
        }
        $fields[] = array('select' => $item, 'label'=>trim($label));
    }
    $select = "\n";
    if($ajax) {
        include_once DOL_DOCUMENT_ROOT . '/core/lib/ajax.lib.php';
       $token = getToken();
        $urloption = 'token='.$token;
        $comboenhancement = '';
        //$ajaxUrl = '';
        $searchfields = '';
        if($ajaxNbChar) {
            $ajaxUrl = dol_buildpath('/timesheet/core/ajaxGenericSelectHandler.php', 1);
            $_SESSION['ajaxQuerry'][$token]['sql'] = $sqlarray;
            $_SESSION['ajaxQuerry'][$token]['fields'] = $fields;
            $_SESSION['ajaxQuerry'][$token]['html'] = $htmlarray;
            $_SESSION['ajaxQuerry'][$token]['option'] = $addtionnalChoices;
                    //array('table'=>$table, 'fieldValue'=>$fieldValue, 'htmlName'=> $htmlName, 'fieldToShow1'=>$fieldToShow1, 'fieldToShow2'=>$fieldToShow2, 'separator'=> $separator, 'sqlTailTable'=>$sqlTailTable, 'sqlTailWhere'=>$sqlTailWhere, 'addtionnalChoices'=>$addtionnalChoices);
            $comboenhancement = ajax_autocompleter($selected, $htmlName, $ajaxUrl, $urloption, $ajaxNbChar);
            $sqlTail .= " LIMIT 5";
            // put \\ before barket so the js will work for Htmlname before it is change to seatch HTMLname
            $htmlid = str_replace('[', '\\\\[', str_replace(']', '\\\\]', $htmlName));
            $comboenhancement = str_replace('#'.$htmlName, '#'.$htmlid, $comboenhancement);
            $comboenhancement = str_replace($htmlName.':', '"'.$htmlName.'":', $comboenhancement);// #htmlname doesn't cover everything
            $htmlName = 'search_'.$htmlName;
        } else{
            $comboenhancement = ajax_combobox($htmlName);
        }
        // put \\ before barket so the js will work
        $htmlid = '#'.str_replace('[', '\\\\[', str_replace(']', '\\\\]', $htmlName));
        $comboenhancement = str_replace('#'.$htmlName, $htmlid, $comboenhancement);
        //incluse js code in the html response
        $select .= $comboenhancement;
        $nodatarole = ($comboenhancement?' data-role = "none"':'');
    }
    //dBQuerry
    $SelectOptions = '';
    $selectedValue = '';
    $sql = 'SELECT DISTINCT ';
    $sql .= $sqlarray['keyfield'];
    $sql .= ', '.$sqlarray['fields'];
    $sql.= ' FROM '.MAIN_DB_PREFIX.$sqlarray['table'].' as t';
    if(isset($sqlarray['join']) && !empty($sqlarray['join']))
            $sql .= ' '.$sqlarray['join'];
    if(isset($sqlarray['where']) && !empty($sqlarray['where']))
            $sql .= ' WHERE '.$sqlarray['where'];
    if(isset($sqlarray['tail']) && !empty($sqlarray['tail']))
            $sql .= ' '.$sqlarray['tail'];
    dol_syslog('form::select_sellist ', LOG_DEBUG);
    // remove the 't." if any
    $startkey = strpos($sqlarray['keyfield'], '.');
    $labelKey = ($startkey)?substr($sqlarray['keyfield'], $startkey+1):$sqlarray['keyfield'];
    $resql = $db->query($sql);
    if($resql) {
        $selectOptions.= "<option value = \"-1\" ".(empty($selected)?"selected":"").">&nbsp;</option>\n";
        $i = 0;
        $separator = isset($htmlarray['separator'])?$htmlarray['separator']:' ';
         //return $table."this->db".$field;
        $num = $db->num_rows($resql);
        while($i < $num)
        {
            $obj = $db->fetch_object($resql);
            if($obj) {
                $fieldtoshow = '';
                foreach($fields as $item) {
                    if(!empty($fieldtoshow))$fieldtoshow .= $separator;
                    $fieldtoshow .= $obj->{$item['label']};
                }
                $selectOptions.= "<option value = \"".$obj->{$labelKey}."\" ";
                if($obj->{$labelKey} == $selected) {
                     $selectOptions .= 'selected = \"selected\"';
                     $selectedValue = $fieldtoshow;
                }
                $selectOptions .= ">";
                $selectOptions .= $fieldtoshow;
                $selectOptions .= "</option>\n";
            }
            $i++;
        }
        if($addtionnalChoices)foreach($addtionnalChoices as $value => $choice) {
            $selectOptions.= '<option value = "'.$value.'" '.(($selected == $value)?'selected':'').">{$choice}</option>\n";
        }
    } else {
        $error++;
        dol_print_error($db);
       $select.= "<option value = \"-1\" selected = \"selected\">ERROR</option>\n";
    }
    if($ajaxNbChar && $ajax) {
        if($selectedValue == '' && is_array($addtionnalChoices)) {
            $selectedValue = $addtionnalChoices[$selected];
        }
        $select .= '<input type = "text" class = "minwidth200 '.(isset($htmlarray['class'])?$htmlarray['class']:'').'" name = "'.$htmlName.'" id = "'.(isset($htmlarray['id'])?$htmlarray['id']:$htmlName).'" value = "'.$selectedValue.'"'.$htmlarray['otherparam'].' />';
    } else{
        $select .= '<select class = "flat minwidth200 '.(isset($htmlarray['class'])?$htmlarray['class']:'').'" id = "'.(isset($htmlarray['id'])?$htmlarray['id']:$htmlName).'" name = "'.$htmlName.'"'.$nodatarole.' '.$htmlarray['otherparam'].'>';
        $select .= $selectOptions;
        $select .= "</select>\n";
    }
   // }
      return $select;
}
/** function to select item
 *
 * @global DoliConf $conf conf object
 * @global DoliLangs $langs lang object
 * @global DoliDB $db   db object
 * @param string[] $sqlarray sql parametes
 * @param int $selected id to be selected
 * @param string[] $separator seperator to be shown between fields
 * @param string $url link
 * @return string HTML code
 */
function print_sellist(
    $sqlarray = array('table'=> 'user', 'keyfield'=> 'rowid', 'fields'=>'firstname, lastname', 'join' => '', 'where'=>'', 'tail'=>''),
    $selected = '',
    $separator = ' ',
    $url = ''
) {
    global $conf, $langs, $db;
    if(!isset($sqlarray['table'])|| !isset($sqlarray['keyfield'])||!isset($sqlarray['fields'])) {
        return 'error, one of the mandatory field of the function  select_sellist is missing:'.$sqlarray['table'].$sqlarray['keyfield'].$sqlarray['fields'];
    } elseif(empty($selected)) {
        return "NuLL";
    }
    $sql = 'SELECT DISTINCT ';
    $sql .= $sqlarray['keyfield'];
    $sql .= ', '.$sqlarray['fields'];
    $sql.= ' FROM '.MAIN_DB_PREFIX.$sqlarray['table'].' as t';
    if(isset($sqlarray['join']) && !empty($sqlarray['join']))
            $sql .= ' '.$sqlarray['join'];
    $sql.= ' WHERE '.$sqlarray['keyfield'].' = \''.$selected.'\'';
    if(isset($sqlarray['where']) && !empty($sqlarray['where']))
            $sql .= ' AND '.$sqlarray['where'];
    if(isset($sqlarray['tail']) && !empty($sqlarray['tail']))
            $sql .= ' '.$sqlarray['tail'];
    dol_syslog('form::print_sellist ', LOG_DEBUG);
    $startkey = strpos($sqlarray['keyfield'], '.');
    $labelKey = ($startkey)?substr($sqlarray['keyfield'], $startkey+1):$sqlarray['keyfield'];
    $resql = $db->query($sql);
    if($resql) {
        $listFields = explode(',', $sqlarray['fields']);
        $fields = array();
        foreach($listFields as $item) {
            $item=trim($item);
            $start = MAX(strpos($item, ' AS '), strpos($item, ' as '));
            $start2 = strpos($item, '.');
            $label = $item;
            if($start) {
                $label = substr($item, $start+4);
            } elseif($start2) {
                $label = substr($item, $start2+1);
            }
            $fields[] = array('select' => $item, 'label'=>trim($label));
        }
        $num = $db->num_rows($resql);
        if($num) {
            $obj = $db->fetch_object($resql);
            if($obj) {
                $select = '';
                foreach($fields as $item) {
                    if(!empty($select))$select .= $separator;
                    $select .= $obj->{$item['label']};
                }
                     if(!empty($url))$select = '<a href = "'.$url.$obj->{$sqlarray['keyfield']}.'">'.$select.'</a>';
            } else{
                $select = "NULL";
            }
        } else{
            $select = "NULL";
        }
    } else {
        $error++;
        dol_print_error($db);
       $select.= "ERROR";
    }
      //$select .= "\n";
      return $select;
}

 /*
 * function to print a bitstring(or sting starting  with _)
 *
 *  @param    string                                            $bitstring              list f bits
 *  @param     array(string label))   $labels                 array of label(dispaly label) for the bit number key
 *  @param     array(string name))     $names                 array of name(input name) for the bit number key
 *  @param    int                       $edit             active the  read only mode
 *  @return   string                htmlcode
 */
function printBitStringHTML($bitstring, $labels, $names, $edit = 0)
{
    global $langs;
    $html = "error, paramters of printBitStringHTML not valid";
    $numberOfBits = count($labels);
    if(is_array($labels) && count_chars(bitstring)!=($numberOfBits+1)) {
        $htmlValue = '';
        $html = '<table class = "noborder" width = "100%"><tr class = "titre">';
        for($i = 0;$i<$numberOfBits;$i++)
        {
            // labels
            $html .= '<td width = "'.floor(100/$numberOfBits).'%">'.$labels[$i].'<td>';
            $htmlValue .= '<td><input type = "checkbox" name = "'.$names[$i].'"'.((substr($bitstring, $i+1, 1))?' checked':'').(($edit)?'':' readonly').' ><td>';
        }
        $html .= '</tr><tr>'.$htmlValue.'</tr></table>';
    }
    return $html;
}
 /*
 * function to genegate a random number
 *
 *  @param    int             $min                min seed
 *  @param    int                      $max            max seed
 *  @return   int                                  random number
 */
function crypto_rand_secure($min, $max)
{
    $range = $max - $min;
    if($range < 0) return $min;// not so random...
    $log = log($range, 2);
    $bytes = (int) ($log / 8) + 1;// length in bytes
    $bits = (int) $log + 1;// length in bits
    $filter = (int) (1 << $bits) - 1;// set all lower bits to 1
    do {
        $rnd = hexdec(bin2hex(openssl_random_pseudo_bytes($bytes)));
        $rnd = $rnd & $filter;// discard irrelevant bits
    } while($rnd >= $range);
    return $min + $rnd;
}
 /*
 * function to genegate a random string
 *
 *  @param    int             $lentgh                lentgh of the random string
 *  @return   int                                  random sting
 */
function getToken($length = 32)
{
    $token = "";
    $codeAlphabet = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
    $codeAlphabet.= "abcdefghijklmnopqrstuvwxyz";
    $codeAlphabet.= "0123456789";
    for($i = 0;$i<$length;$i++)
    {
        $token .= $codeAlphabet[crypto_rand_secure(0, strlen($codeAlphabet))];
    }
    return $token;
}

/*
 * function to genegate a select list from a table, the showed text will be a concatenation of some
 * column defined in column bit, the Least sinificative bit will represent the first colum
 *
 *  @param    string               $table                 table which the fk refers to(without prefix)
 *  @param    string               $fieldValue         field of the table which the fk refers to, the one to put in the Valuepart
 *  @param    string               $selected           value selected of the field value column
 *  @param    string               $fieldToShow1    first part of the concatenation
 *  @param    string               $fieldToShow2        separator between the tow contactened fileds
 *  @param    string               $sqlTail              to limit per entity, to filter ...
 *  @return string                                                   html code
 */
function print_generic($table, $fieldValue, $selected, $fieldToShow1, $fieldToShow2 = "", $separator = ' - ', $sqltail = "", $sqljoin = "")
{
   //return $table.$db.$field;
    return  print_sellist($sqlarray = array('table' => $table, 'keyfield' => $fieldValue, 'fields' => $fieldToShow1.(empty($fieldToShow2)?'':', '.$fieldToShow2), 'join' => $sqljoin, 'where' => '', 'tail' => $sqltail),
        $selected,
        $separator);
}

/** generic function to call getNoimUrl
 *
 * @global DoliDB $db database object or alias
 * @param string $type object name
 * @param int $htmlcontent show or not htmlcontent
 * @param int  $id  id of the object
 * @param string $ref   ref of the object
 * @return string   getNomUrl HTML code
 */
function getNomUrl($type, $htmlcontent = '1', $id = 0, $ref = '')
{
    global $db;
    $object=null;
    $link='';
    switch (strtolower(str_replace('_', '', $type)))
    {
        case "supplier":
        case "fournisseur":
            $type="Fournisseur";
            if (!class_exists($type)) break;
        case "customer":
        case "Company":
        case "societe":
           $type="Societe";
            break;
        case "invoice":
        case "facture":
        case "invoicecustomer":
        case "customerinvoice":
           $type="Facture";
            break;
        case "invoicesupplier":
        case "supplierinvoice":
        case "facturefourn":
            $type="FactureFournisseur";
            break;
        case "expense":
            break;
        case "bankaccount":
            $type="Account";
            break;
        case "salary":
            $type="PaymentSalary";
            break;
        case "order":
        case "customerorder":
        case "ordercustomer":
            $type="Commande";
            break;
        case "supplierorder":
        case "ordersupplier":
            $type="FactureFournisseur";
            break;
        case "subscriber":
            $type="Adherent";
            break;
        case "donation":
            $type="Don";
            break;
        case "charge":
        case "healthcareexpense":
        case "socialcontributions":
            $type="Chargesociales";
           break;
        case "payment":
            $type= "Paiement";
            break;
        case "vat":
            $type="TVA";
            break;
        case "expense":
            $type="ExpenseReport";
        default:
            break;
    }
    if (class_exists($type))
    {
        $object = new $type($db);
        $object->fetch($id);
        $link = $object->getNomUrl();
    }else
    {
        $link = "ERROR: type:${$type} not supported or class not loaded";
    }
    return $link;
}
