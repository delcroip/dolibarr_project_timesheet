<?php
/* Copyright (C) 2016 delcroip <pmpdelcroix@gmail.com>
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

/**
 *	\file       htdocs/core/boxes/box_approval.php
 *	\ingroup    factures
 *	\brief      Module de generation de l'affichage de la box factures
 */
include_once DOL_DOCUMENT_ROOT.'/core/boxes/modules_boxes.php';

/**
 * Class to manage the box to show last invoices
 */
class box_approval extends ModeleBoxes
{
	var $boxcode="nbTsToApprove";
	var $boximg="timesheet";
	var $boxlabel="BoxApproval";
	var $depends = array("timesheet");

	var $db;
	var $param;

	var $info_box_head = array();
	var $info_box_contents = array();


	/**
	 *  Load data into info_box_contents array to show array later.
	 *
	 *  @param	int		$max        Maximum number of records to load
     *  @return	void
	 */
	function loadBox($max=5)
	{
		global $conf, $user, $langs, $db;

		$this->max=$max;

        //require_once 'class/timesheetUser.class.php';



        $userid=  is_object($user)?$user->id:$user;
		$text =$langs->trans('Timesheet');
		$this->info_box_head = array(
				'text' => $text,
				'limit'=> dol_strlen($text)
		);

        if ($user->rights->timesheet->approval) {
                        $sql = 'SELECT';
            $sql.=' COUNT(t.rowid) as nb,';
            $sql.=' u.fk_user as approverid';
            $sql.= ' FROM '.MAIN_DB_PREFIX.'timesheet_user as t';
            $sql.= ' JOIN '.MAIN_DB_PREFIX.'user as u on t.fk_userid=u.rowid ';
            $sql.= ' WHERE t.status="SUBMITTED" AND t.target="team"';
            $sql.= ' AND u.fk_user="'.$userid.'"';
            $sql.= ' GROUP BY u.fk_user';
            $result = $db->query($sql);
            if ($result)
            {
                $num = $db->num_rows($result);
                if ($num){
                    $obj = $db->fetch_object($result);
                    $nb=$obj->nb;
                    }

                    $this->info_box_contents[0][] = array(
                        'td' => 'align="left"',
                        'text' => $langs->trans('nbTsToApprove'),
                        //'text2'=> $late,
                        'asis' => 1,
                    );

                    $this->info_box_contents[0][] = array(
                        'td' => 'align="right"',
                        'text' => $nb,
                        'asis' => 1,
                    );


                $db->free($result);
            } else {
                $this->info_box_contents[0][0] = array(
                    'td' => 'align="left"',
                    'maxlength'=>500,
                    'text' => ($db->error().' sql='.$sql),
                );
            }

        } else {
            $this->info_box_contents[0][0] = array(
                'td' => 'align="left"',
                'text' => $langs->trans("ReadPermissionNotAllowed"),
            );
        }
    }

	/**
	 *  Method to show box
	 *
	 *  @param  array   $head       Array with properties of box title
	 *  @param  array   $contents   Array with properties of box lines
	 *  @return void
	 */
	function showBox($head = null, $contents = null)
	{
		parent::showBox($this->info_box_head, $this->info_box_contents);
	}

}
