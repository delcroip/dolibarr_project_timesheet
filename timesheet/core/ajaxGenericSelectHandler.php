<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

include 'lib/includeMain.lib.php';
 global $conf,$langs,$db;
 top_httphead();
//get the token,exit if 
$token=GETPOST('token');

if(!isset($_SESSION['ajaxQuerry'][$token]))exit();

$table=$_SESSION['ajaxQuerry'][$token]['table'];
$fieldValue=$_SESSION['ajaxQuerry'][$token]['fieldValue'];
$fieldToShow1=$_SESSION['ajaxQuerry'][$token]['fieldToShow1'];
$fieldToShow2=$_SESSION['ajaxQuerry'][$token]['fieldToShow2'];
$separator=$_SESSION['ajaxQuerry'][$token]['separator'];
$sqlTailTable=$_SESSION['ajaxQuerry'][$token]['sqlTailTable'];
$sqlTailWhere=$_SESSION['ajaxQuerry'][$token]['sqlTailWhere'];
$htmlName=$_SESSION['ajaxQuerry'][$token]['htmlName'];
$addtionnalChoices=$_SESSION['ajaxQuerry'][$token]['addtionnalChoices'];
 $search=GETPOST($htmlName);
//find if barckets
$posBs=strpos($htmlName,'[');
if($posBs>0){
    $subStrL1= substr($htmlName, 0, $posBs);
    $search=$_GET[$subStrL1];
    while(is_array($search)){// assumption there is only one value in the array
        $search=array_pop($search);
    }
}


    $sql='SELECT DISTINCT';
    $sql.=' '.$fieldValue;
    $sql.=' ,'.$fieldToShow1;
    if(!empty($fieldToShow2))
        $sql.=' ,'.$fieldToShow2;
    $sql.= ' FROM '.MAIN_DB_PREFIX.$table.' as t';
    if(!empty($sqlTailTable))
        $sql.=' AND '.$sqlTailTable;   
    $sql.= ' WHERE ( '.$fieldValue.' LIKE "%'.$search.'%"';
    $sql.= ' OR '.$fieldToShow1.' LIKE "%'.$search.'%"';
    $sql.= ' OR '.$fieldToShow2.' LIKE "%'.$search.'%")';
    if(!empty($sqlTailWhere))
        $sql.=' AND '.$sqlTailWhere;
       
    dol_syslog('form::ajax_select_generic ', LOG_DEBUG);
    $return_arr = array();
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

        $i=0;
         //return $table."this->db".$field;
        $num = $db->num_rows($resql);
        while ($i < $num)
        {
            
            $obj = $db->fetch_object($resql);
            
            if ($obj)
            {
                    
                $label=$obj->{$fieldToShow1};
                $label.=(!empty($fieldToShow2))?($separator.$obj->{$fieldToShow2}):'';
                $row_array['label'] =  $label;
                $value=$obj->{$fieldValue};
		//$row_array['value'] = $value;
                $row_array['value'] =  $label;
	        $row_array['key'] =$value;
                array_push($return_arr,$row_array);
            } 
            $i++;
        }
        if($addtionnalChoices)foreach($addtionnalChoices as $value => $label){
                $row_array['label'] =  $label;
		$row_array['value'] = $value;
	        $row_array['key'] =$value;
            array_push($return_arr,$row_array);
        }

        
    }

 
      echo json_encode($return_arr);
    
