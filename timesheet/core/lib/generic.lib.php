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
global $langs;


/*
 * function to genegate a select list from a table, the showed text will be a concatenation of some 
 * column defined in column bit, the Least sinificative bit will represent the first colum 
 * 
 *  @param    object             	$db                 db Object to do the querry
 *  @param    string              	$table              table which the enum refers to (without prefix)
 *  @param    string              	$fieldValue         field of the table which the enum refers to
 *  @param    string              	$htmlName           name to the form select
 *  @param    string              	$selected           which value must be selected
 *  @param    string              	$selectparam          to add parameters to the select
 *  @param    array(string)             $addtionnalChoices    array of additionnal fields Array['VALUE']=string to show
 *  @return string                                                   html code
 */
 
function select_enum($table, $fieldValue,$htmlName,$selected='',$selectparam='',$addtionnalChoices=null){
global $langs;
global $db;   
if($table=='' || $fieldValue=='' || $htmlName=='' )
    {
        return 'error, one of the mandatory field of the function  select_enum is missing';
    } 
    $sql='SHOW COLUMNS FROM ';//llx_hr_event_time LIKE 'audience'";
    $sql.=MAIN_DB_PREFIX.$table.' WHERE Field="';
    $sql.=$fieldValue.'"';
    //$sql.= " ORDER BY t.".$field;
       
    dol_syslog('form::select_enum ', LOG_DEBUG);
    
    $resql=$db->query($sql);
    
    if ($resql)
    {
        $i=0;
         //return $table."this->db".$field;
        $num = $db->num_rows($resql);
        if($num)
        {
           
            $obj = $db->fetch_object($resql);
            if ($obj && strpos($obj->Type,'enum(')===0)
            {
                if(empty($selected) && !empty($obj->Default))$selected="'{$obj->Default}'";
                    $select.='<select class="flat minwidth200" id="'.$htmlName.'Select" name="'.$htmlName.'"'.$nodatarole.' '.$selectparam.'>';
                    $select.= '<option value="-1" '.(empty($selected)?'selected="selected"':'').">&nbsp;</option>\n";

                $enums= explode(',',substr($obj->Type, 5,-1));
                foreach ($enums as $enum){
                    $select.= '<option value="'.(substr($enum,1,-1)).'" ';
                    $select.=((substr($enum,1,-1)===$selected)?'selected="selected" >':'>');                    
                    $select.=$langs->trans(strtolower(substr($enum,1,-1)));          
                    $select.="</option>\n";
                }
                if($addtionnalChoices)foreach($addtionnalChoices as $value => $choice){
                     $select.='<option value="'.$value.'" '.(($selected==$value)?'selected':'').">{$choice}</option>\n";
                }
                $select.= '<option value="NULL" '.(($selected=='NULL')?'selected':'').">NULL</option>\n";
                $select.="</select>\n";
            }else{
                $select="<input selected=\"{$selected}\" id=\"{$htmlName} \" name=\"{$htmlName}\">";
            }
 
        }else{
                $select="<input selected=\"{$selected}\" id=\"{$htmlName} \" name=\"{$htmlName}\">";
        }
    }
    else
    {
        $error++;
        dol_print_error($db);
       $select="<input selected=\"{$selected}\" id=\"{$htmlName} \" name=\"{$htmlName}\">";
    }
      
      return $select;
    
 }
/*
 * function to genegate a select list from a table, the showed text will be a concatenation of some 
 * column defined in column bit, the Least sinificative bit will represent the first colum 
 * 
 *  @param    string              	$table                 table which the fk refers to (without prefix)
 *  @param    string              	$fieldValue         field of the table which the fk refers to, the one to put in the Valuepart
 *  @param    string              	$htmlName        name to the form select
 *  @param    string              	$fieldToShow1    first part of the concatenation
 *  @param    string              	$fieldToShow2    second part of the concatenation
 *  @param    string              	$selected            which value must be selected
 *  @param    string              	$separator          separator between the tow contactened fileds
*  @param    string              	$sqlTailWhere              to limit per entity, to filter ...
*  @param    string              	$selectparam          to add parameters to the select
 *  @param    array(string)             $addtionnalChoices    array of additionnal fields Array['VALUE']=string to show
*  @param    string              	$sqlTailTAble              to add join ... 
*  @param    string              	$ajaxUrl             path to the ajax handler ajaxSelectGenericHandler.php

 *  @return string                                                   html code
 */
// to be taken into account when passing a Ajax handler
//$conf->global->COMPANY_USE_SEARCH_TO_SELECT
 //$conf->global->PRODUIT_USE_SEARCH_TO_SELECT
 //$conf->global->PROJECT_USE_SEARCH_TO_SELECT
 //$conf->global->RESOURCE_USE_SEARCH_TO_SELECT
//$conf->global->BARCODE_USE_SEARCH_TO_SELECT
//$conf->global->CONTACT_USE_SEARCH_TO_SELECT
 
function select_generic($table, $fieldValue,$htmlName,$fieldToShow1,$fieldToShow2='',$selected='',$separator=' - ',$sqlTailWhere='', $selectparam='', $addtionnalChoices=array('NULL'=>'NULL'),$sqlTailTable='', $ajaxNbChar='',$showempty=1){

   
    
    global $conf,$langs,$db,$dolibarr_main_url_root,$dolibarr_main_url_root_alt;
     $ajax=$conf->use_javascript_ajax ;
    if($table=='' || $fieldValue=='' || $fieldToShow1=='' || $htmlName=='' )
    {
        return 'error, one of the mandatory field of the function  select_generic is missing';
    }
    
    $select="\n";
    if ($ajax)
    {

        include_once DOL_DOCUMENT_ROOT . '/core/lib/ajax.lib.php';
       $token=getToken();

        $urloption='token='.$token;
        $comboenhancement = '';
        //$ajaxUrl='';
        $searchfields='';
        if($ajaxNbChar ){
            $ajaxUrl=$dolibarr_main_url_root;
            if($dolibarr_main_force_https || strpos('ttps://',$_SERVER['PHP_SELF'])>0){
                $ajaxUrl=str_replace('http://','https://',$ajaxUrl);
            }
            if(strpos($dolibarr_main_url_root_alt,$_SERVER['PHP_SELF'])>0)
            {
                 $ajaxUrl.='/'.$dolibarr_main_url_root_alt;
            }
            $ajaxUrl.='/timesheet/core/ajaxGenericSelectHandler.php';
            $_SESSION['ajaxQuerry'][$token]=array('table'=>$table, 'fieldValue'=>$fieldValue,'htmlName'=> $htmlName,'fieldToShow1'=>$fieldToShow1,'fieldToShow2'=>$fieldToShow2,'separator'=> $separator,'sqlTailTable'=>$sqlTailTable,'sqlTailWhere'=>$sqlTailWhere,'addtionnalChoices'=>$addtionnalChoices);
            $comboenhancement = ajax_autocompleter($selected, $htmlName, $ajaxUrl, $urloption,$ajaxNbChar);
            $sqlTail.=" LIMIT 5";
            // put \\ before barket so the js will work for Htmlname before it is change to seatch HTMLname
            $htmlid=str_replace('[','\\\\[',str_replace(']','\\\\]',$htmlName));
            $comboenhancement=str_replace('#'.$htmlName, '#'.$htmlid,$comboenhancement);
            $comboenhancement=str_replace($htmlName.':', '"'.$htmlName.'":',$comboenhancement); // #htmlname doesn't cover everything
            $htmlName='search_'.$htmlName;
        }else{
            $comboenhancement = ajax_combobox($htmlName);
        }
        // put \\ before barket so the js will work
        $htmlid='#'.str_replace('[','\\\\[',str_replace(']','\\\\]',$htmlName));
        $comboenhancement=str_replace('#'.$htmlName, $htmlid,$comboenhancement);
        //incluse js code in the html response
        $select.=$comboenhancement;
        $nodatarole=($comboenhancement?' data-role="none"':'');
        
    }
    
    //dBQuerry

    $SelectOptions='';
    $selectedValue='';
    $sql='SELECT DISTINCT';
    $sql.=' t.'.$fieldValue;
    $sql.=' ,'.$fieldToShow1;
    if(!empty($fieldToShow2))
        $sql.=' ,'.$fieldToShow2;
    $sql.= ' FROM '.MAIN_DB_PREFIX.$table.' as t';
    if(!empty($sqlTailTable))
            $sql.=' '.$sqlTailTable;
    if(!empty($sqlTailWhere))
            $sql.=' WHERE '.$sqlTailWhere;
       
    dol_syslog('form::select_generic ', LOG_DEBUG);
    
    $resql=$db->query($sql);
   
    if ($resql)
    {
          // support AS in the fields ex $field1='CONTACT(u.firstname,' ',u.lastname) AS fullname'
        // with sqltail= 'JOIN llx_user as u ON t.fk_user=u.rowid'
        $starfields1=strpos($fieldToShow1,' AS ');
        if($starfields1>0)
            $fieldToShow1=  substr($fieldToShow1, $starfields1+4);
        $starfields2=strpos($fieldToShow2,' AS ');
        if($starfields2>0)
            $fieldToShow2=  substr($fieldToShow2, $starfields2+4);

        if($showempty==1)$selectOptions.= "<option value=\"-1\" ".(empty($selected)?"selected":"").">&nbsp;</option>\n";
        $i=0;
         //return $table."this->db".$field;
        $num = $db->num_rows($resql);
        while ($i < $num)
        {
            
            $obj = $db->fetch_object($resql);
            
            if ($obj)
            {
                $fieldtoshow=$obj->{$fieldToShow1}.((!empty($fieldToShow2))?$separator.$obj->{$fieldToShow2}:''); 
                $selectOptions.= "<option value=\"".$obj->{$fieldValue}."\" ";
                if($obj->{$fieldValue}==$selected){
                     $selectOptions.='selected=\"selected\"';
                     $selectedValue=$fieldtoshow;
                 }
                 $selectOptions.=">";                    
                 $selectOptions.=$fieldtoshow;

                 $selectOptions.="</option>\n";
            } 
            $i++;
        }
        if($addtionnalChoices)foreach($addtionnalChoices as $value => $choice){
            $selectOptions.= '<option value="'.$value.'" '.(($selected==$value)?'selected':'').">{$choice}</option>\n";
        }

        
    }
    else
    {
        $error++;
        dol_print_error($db);
       $select.= "<option value=\"-1\" selected=\"selected\">ERROR</option>\n";
    }
     
   
    if($ajaxNbChar && $ajax){
        if ($selectedValue=='' && is_array($addtionnalChoices)){
            $selectedValue=$addtionnalChoices[$selected];
        }
        $select.='<input type="text" class="minwidth200" name="'.$htmlName.'" id="'.$htmlName.'" value="'.$selectedValue.'"'.$selectparam.' />';
    }else{
        $select.='<select class="flat minwidth200" id="'.$htmlName.'" name="'.$htmlName.'"'.$nodatarole.' '.$selectparam.'>';
        $select.=$selectOptions;
        $select.="</select>\n";
    }
   // }
      return $select;
    
 }
 
 
/*
 * function to genegate a select list from a table, the showed text will be a concatenation of some 
 * column defined in column bit, the Least sinificative bit will represent the first colum 
 * 
 *  @param    object             	$db                 db Object to do the querry
 *  @param    string              	$table                 table which the fk refers to (without prefix)
 *  @param    string              	$fieldValue         field of the table which the fk refers to, the one to put in the Valuepart
 *  @param    string              	$selected           value selected of the field value column
 *  @param    string              	$fieldToShow1    first part of the concatenation
 *  @param    string              	$fieldToShow1    second part of the concatenation
 *  @param    string              	$separator          separator between the tow contactened fileds
 *  @param    string              	$sqlTail              to limit per entity, to filter ...

 *  @return string                                                   html code
 */
function print_generic($table, $fieldValue,$selected,$fieldToShow1,$fieldToShow2="",$separator=' - ',$sqltail="",$sqljoin=""){
   //return $table.$db.$field;
    global $db;
    if($table=="" || $fieldValue=="" || $fieldToShow1=='')
    {
        return "error, one of the mandatory field of the function  print_generic is missing";
    }else if (empty($selected)){
        return "NuLL";
    }
    
    $sql="SELECT";
    $sql.=" t.".$fieldValue;
    $sql.=" ,".$fieldToShow1;
    if(!empty($fieldToShow2))
        $sql.=" ,".$fieldToShow2;
    $sql.= " FROM ".MAIN_DB_PREFIX.$table." as t";
    if(!empty($sqljoin))
        $sql.=' '.$sqljoin;
    $sql.= " WHERE t.".$fieldValue."=".$selected;
    if(!empty($sqlTail))
            $sql.=' '.$sqlTail;
       
    dol_syslog("form::print_generic ", LOG_DEBUG);
    
    $resql=$db->query($sql);
    
    if ($resql)
    {
    // support AS in the fields ex $field1='CONTACT(u.firstname,' ',u.lastname) AS fullname'
     // with sqltail= 'JOIN llx_user as u ON t.fk_user=u.rowid'
     $starfields1=strpos($fieldToShow1,' AS ');
     if($starfields1>0){
         $fieldToShow1=  substr($fieldToShow1, $starfields1+4);
     }
     $starfields2=strpos($fieldToShow2,' AS ');
     if($starfields2>0){
         $fieldToShow2=substr($fieldToShow2, $starfields2+4);
      }

        $num = $db->num_rows($resql);
        if ( $num)
        {
            $obj = $db->fetch_object($resql);
            
            if ($obj)
            {
                            $select=$obj->{$fieldToShow1};
                            if(!empty($fieldToShow2))
                                 $select.=$separator.$obj->{$fieldToShow2};        
            }else{
                $select= "NULL";
            }
        }else{
            $select= "NULL";
        }
    }
    else
    {
        $error++;
        dol_print_error($db);
       $select.= "ERROR";
    }
      //$select.="\n";
      return $select;
 }
 /*
 * function to print a bitstring (or sting starting  with _)
 * 
 *  @param    string                                            $bitstring              list f bits
 *  @param     array( string label))   $labels                 array of label ( dispaly label) for the bit number key
 *  @param     array(string name))     $names                 array of name (input name) for the bit number key  
 *  @param    int                       $edit             active the  read only mode
 *  @return   string                htmlcode                                       
 */
 
 function printBitStringHTML($bitstring,$labels,$names,$edit=0){
     global $langs;
     $html="error, paramters of printBitStringHTML not valid";
     $numberOfBits=count($labels);
     if(is_array($labels) && count_chars(bitstring)!=($numberOfBits+1)){
          $htmlValue='';
          $html='<table class="noborder" width="100%"><tr class="titre">';  

           for($i=0;$i<$numberOfBits;$i++){
               // labels
               $html.='<td width="'.floor(100/$numberOfBits).'%">'.$labels[$i].'<td>';
               $htmlValue.='<td><input type="checkbox" name="'.$names[$i].'"'.((substr($bitstring, $i+1, 1))?' checked':'').(($edit)?'':' readonly').' ><td>';

           }
           $html.='</tr><tr>'.$htmlValue.'</tr></table>';
     }
     return $html;

 }
 
 
 /*
 * function to genegate a random number
 * 
 *  @param    int            	$min                min seed
 *  @param    int                      $max            max seed
 *  @return   int                                  random number                                         
 */
 
 
 function crypto_rand_secure($min, $max) {
        $range = $max - $min;
        if ($range < 0) return $min; // not so random...
        $log = log($range, 2);
        $bytes = (int) ($log / 8) + 1; // length in bytes
        $bits = (int) $log + 1; // length in bits
        $filter = (int) (1 << $bits) - 1; // set all lower bits to 1
        do {
            $rnd = hexdec(bin2hex(openssl_random_pseudo_bytes($bytes)));
            $rnd = $rnd & $filter; // discard irrelevant bits
        } while ($rnd >= $range);
        return $min + $rnd;
}
 /*
 * function to genegate a random string
 * 
 *  @param    int            	$lentgh                lentgh of the random string
 *  @return   int                                  random sting                                        
 */
function getToken($length=32){
    $token = "";
    $codeAlphabet = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
    $codeAlphabet.= "abcdefghijklmnopqrstuvwxyz";
    $codeAlphabet.= "0123456789";
    for($i=0;$i<$length;$i++){
        $token .= $codeAlphabet[crypto_rand_secure(0,strlen($codeAlphabet))];
    }
    return $token;
}
    