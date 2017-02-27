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
--fk
ALTER TABLE llx_timesheet_user ADD FOREIGN KEY (fk_user_approval) REFERENCES llx_user(rowid);
ALTER TABLE llx_timesheet_user ADD FOREIGN KEY (fk_user_creation ) REFERENCES llx_user(rowid);
ALTER TABLE llx_timesheet_user ADD FOREIGN KEY (fk_user_modification) REFERENCES llx_user(rowid);
ALTER TABLE llx_timesheet_user ADD FOREIGN KEY (fk_timesheet_user) REFERENCES llx_timesheet_user(rowid);
ALTER TABLE llx_timesheet_user ADD FOREIGN KEY (fk_userid) REFERENCES llx_user(rowid);
ALTER TABLE llx_timesheet_user ADD FOREIGN KEY (fk_task) REFERENCES llx_projet_task(rowid);


-- UPDATE from 1.5.1
ALTER TABLE llx_timesheet_user ADD stop_date DATE NOT NULL; -- start date of the period
ALTER TABLE llx_timesheet_user CHANGE COLUMN year_week_date start_date DATE NOT NULL;
-- add the sto date to the row without
Update llx_timesheet_user SET stop_date = DATE_ADD(start_date,INTERVAL 7 DAY) Where stop_date = 0
-- delete the line draft and without note to start from a good base
DELETE FROM `llx_timesheet_user` WHERE `status` ='DRAFT' and (`note`='' OR `note`IS NULL)