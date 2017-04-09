-- ===================================================================
-- Copyright (C) 2013  Alexandre Spangaro <alexandre.spangaro@gmail.com>
-- Copyright (C) 2015  Patrick Delcroix <pmpdelcroix@gmail.com>
--
-- This program is free software; you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation; either version 3 of the License, or
-- (at your option) any later version.
--
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
--
-- You should have received a copy of the GNU General Public License
-- along with this program. If not, see <http://www.gnu.org/licenses/>.
--
-- ===================================================================
-- TS Revision 1.5.0

-- this table is used to store the timesheet favorit


-- UPDATE from 1.5.1
 --ALTER TABLE llx_timesheet_user MODIFY COLUMN fk_timesheet_user integer default NULL; --allow null value
ALTER TABLE `llx_timesheet_user` ADD stop_date DATE NOT NULL; -- start date of the period
ALTER TABLE `llx_timesheet_user` CHANGE COLUMN year_week_date start_date DATE NOT NULL;
ALTER TABLE `llx_timesheet_user` CHANGE COLUMN fk_timesheet_user fk_project_tasktime_approval integer DEFAUT NULL;

-- add the sto date to the row without
Update `llx_timesheet_user` SET stop_date = DATE_ADD(start_date,INTERVAL 7 DAY) Where stop_date = 0
-- delete the line draft and without note to start from a good base
DELETE FROM `llx_timesheet_user` WHERE `status` ='DRAFT' and (`note`='' OR `note`IS NULL)
RENAME TABLE `llx_timesheet_user` TO `llx_project_tasktime_approval`


ALTER TABLE `llx_project_tasktime_approval` ADD FOREIGN KEY llx_ptta_ptta_id(fk_project_tasktime_approval) REFERENCES llx_project_tasktime_approval(rowid) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE llx_project_tasktime_approval ADD FOREIGN KEY fk_ptta_user_ida(fk_user_approval) REFERENCES llx_user(rowid) ON DELETE NO ACTION ON UPDATE CASCADE;
ALTER TABLE llx_project_tasktime_approval ADD FOREIGN KEY fk_ptta_user_idc(fk_user_creation ) REFERENCES llx_user(rowid) ON DELETE NO ACTION ON UPDATE CASCADE;
ALTER TABLE llx_project_tasktime_approval ADD FOREIGN KEY fk_ptta_user_idm(fk_user_modification) REFERENCES llx_user(rowid) ON DELETE NO ACTION ON UPDATE CASCADE;
ALTER TABLE llx_project_tasktime_approval ADD FOREIGN KEY fk_ptta_user_id(fk_userid) REFERENCES llx_user(rowid) ON DELETE NO ACTION ON UPDATE CASCADE;
ALTER TABLE llx_project_tasktime_approval ADD FOREIGN KEY fk_ptta_task_id(fk_task) REFERENCES llx_projet_task(rowid) ON DELETE NO ACTION ON UPDATE CASCADE;
