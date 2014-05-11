<?php

/* 
 * Copyright (C) 2014 delcroip <delcroip@gmail.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
$yearWeek=$_SESSION["yearWeek"];
$db=$_SESSION["db"];
dol_include_once('/timesheet/class/timesheet.class.php');
 # will generate the form line

		
        $Form =  '<table class="noborder" width="50%">
                    <tr> 
                        <th> 
                            <a href="?action=list&yearweek='.date('Y\WW',strtotime($yearWeek." -1 week")).
                            '">  &lt&lt '.$langs->trans("NextWeek").' </a> 
                        </th> 
                        <th>
                            <form name="goToDate" action="?action=goToDate" method="POST" >
                             Go to date: <input type="date" name="toDate" size="10" value="'.date('d/m/Y',strtotime( $yearWeek.' +0 day')).'"/>   '.
                            '<input type="submit" value="Go" /></form>
                        </th> 
                        <th> 
                            <a href="?action=list&yearweek='.date('Y\WW',strtotime($yearWeek." +1 week")).
                            '">'.$langs->trans("PreviousWeek").' &gt&gt </a> 
                         </th>
                    </tr> 
                  </table>
                  ';



        $Form .='<form name="timesheet" action="?action=submit&yearweek='.$yearWeek.'" method="POST" > 
                  <table class="noborder" width="100%">
                    <tr class="liste_titre" >
                        <th>
                            '.$langs->trans('Project').'
                        </th>
                        <th>
                            '.$langs->trans('Tasks').'
                        </th>
                        <th>
                            '.$langs->trans('TaskDateStart').'
                        </th>
                        <th>
                            '.$langs->trans('TaskDateEnd').'
                        </th>
                        <th width="10%"> 
                            <input type="hidden" name="weekDays[0]" value="'.date('d-m-Y',strtotime( $yearWeek.' +0 day')).
                             '"/>
                             '.$langs->trans(date('l',strtotime( $yearWeek.' +0 day'))).'<br>'.date('d/m/y',strtotime( $yearWeek.' +0 day'))."
                        </th>
                        <th width='10%'> 
                            <input type='hidden' name=weekDays[1] value='".date('d-m-Y',strtotime( $yearWeek.' +1 day')).
                            "'/>
                            ".$langs->trans(date('l',strtotime( $yearWeek.' +1 day'))).'<br>'.date('d/m/y',strtotime( $yearWeek.' +1 day'))."</th> 
                        <th width='10%'> 
                            <input type='hidden' name=weekDays[2] value='".date('d-m-Y',strtotime( $yearWeek.' +2 day')).
                            "'/>
                            ".$langs->trans(date('l',strtotime( $yearWeek.' +2 day'))).'<br>'.date('d/m/y',strtotime( $yearWeek.' +2 day'))."</th>
                        <th width='10%'> 
                            <input type='hidden' name=weekDays[3] value='".date('d-m-Y',strtotime( $yearWeek.' +3 day')).
                            "'/>
                            ".$langs->trans(date('l',strtotime( $yearWeek.' +3 day'))).'<br>'.date('d/m/y',strtotime( $yearWeek.' +3 day'))."
                        </th>
                        <th width='10%'> 
                            <input type='hidden' name=weekDays[4] value='".date('d-m-Y',strtotime( $yearWeek.' +4 day')).
                            "'/>
                            ".$langs->trans(date('l',strtotime( $yearWeek.' +4 day'))).'<br>'.date('d/m/y',strtotime( $yearWeek.' +4 day'))."

                         </th>
                        <th width='10%'> 
                            <input type='hidden' name=weekDays[5] value='".date('d-m-Y',strtotime( $yearWeek.' +5 day')).
                            "'/>
                            ".$langs->trans(date('l',strtotime( $yearWeek.' +5 day'))).'<br>'.date('d/m/y',strtotime( $yearWeek.' +5 day'))."
                        </th>
                        <th width='10%'> 
                            <input type='hidden' name=weekDays[6] value='".date('d-m-Y',strtotime( $yearWeek.' +6 day')).
                            "'/>
                            ".$langs->trans(date('l',strtotime( $yearWeek.' +6 day'))).'<br>'.date('d/m/y',strtotime( $yearWeek.' +6 day')).'
                            <input type="hidden" name="yearWeek" value="'.$yearWeek.'" /> 
                        </th>
                    </tr>
                    ';
                //retrivetask
                $tasksList=array();
		$sql ="SELECT element_id FROM ".MAIN_DB_PREFIX."element_contact "; 
		$sql.="WHERE (fk_c_type_contact='181' OR fk_c_type_contact='180') AND fk_socpeople='".$user->id."'";
                
		dol_syslog("timesheet::getTasksTimesheet sql=".$sql, LOG_DEBUG);
                //$db->begin();
                $resql=$db->query($sql);
		if ($resql)
		{
			$num = $db->num_rows($resql);
			$i = 0;
			// Loop on each record found, so each couple (project id, task id)
			while ($i < $num)
			{
				$error=0;
				$obj = $db->fetch_object($resql);
				$tasksList[$i] 	= NEW timesheet($db, $obj->element_id);
				$i++;
			}
			$db->free($resql);
		}
		else
		{
			dol_print_error($db);
		}
		
		//$tasks=getTasksTimesheet($db,$user->id);
		$i=0;
		
		foreach($tasksList as $row)
		{
			$row->getTaskInfo();
			if($row->isOpenThisWeek($yearWeek))
			{
				$row->getActuals($yearWeek,$user->id);
				$Form.=$row->getFormLine( $yearWeek,$i); 
                                $i++;
			}
			
		}
                $Form .= '
                    </table>
                    <input type="hidden" id="numberOfLines" name="numberOfLines" '.
                    'value="'.$i.'"/>
                        ';
		$Form .= '<table class="noborder" width="80%" align="right">
                            <tr>
                                <th width="10%">Total</th>
                                <th width="10%"><div id="totalDay[0]">&nbsp;</div></th>
                                <th width="10%"><div id="totalDay[1]">&nbsp;</div></th>
                                <th width="10%"><div id="totalDay[2]">&nbsp;</div></th>
                                <th width="10%"><div id="totalDay[3]">&nbsp;</div></th>
                                <th width="10%"><div id="totalDay[4]">&nbsp;</div></th>
                                <th width="10%"><div id="totalDay[5]">&nbsp;</div></th>
                                <th width="10%"><div id="totalDay[6]">&nbsp;</div></th>
                            </tr>
                        </table>';
		$Form .= '<input type="submit" value="'.$langs->trans('Save').'" />';
		$Form .='</form> 
                         <script type="text/javascript" src="timesheet.js"></script>
                         <script type="text/javascript">
                         updateTotal(0);updateTotal(1);updateTotal(2);updateTotal(3);updateTotal(4);updateTotal(5);updateTotal(6);
                         </script>';
               // $db->close();
                echo$Form;

        

