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
-- TS Revision 2.0

-- this table is used to store the timesheet favorite

ALTER TABLE `llx_project_task_time_approval` ADD CONSTRAINT llx_ptta_pttan_id FOREIGN KEY (fk_project_task_time_approval_next) REFERENCES llx_project_task_time_approval(rowid) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `llx_project_task_time_approval` ADD CONSTRAINT llx_ptta_pttap_id FOREIGN KEY (fk_project_task_time_approval_prev) REFERENCES llx_project_task_time_approval(rowid) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `llx_project_task_time_approval` ADD CONSTRAINT fk_ptta_user_ida  FOREIGN KEY (fk_user_approval) REFERENCES llx_user(rowid) ON DELETE NO ACTION ON UPDATE CASCADE;
ALTER TABLE `llx_project_task_time_approval` ADD CONSTRAINT fk_ptta_user_idc  FOREIGN KEY (fk_user_creation ) REFERENCES llx_user(rowid) ON DELETE NO ACTION ON UPDATE CASCADE;
ALTER TABLE `llx_project_task_time_approval` ADD CONSTRAINT fk_ptta_user_idm  FOREIGN KEY (fk_user_modification) REFERENCES llx_user(rowid) ON DELETE NO ACTION ON UPDATE CASCADE;
ALTER TABLE `llx_project_task_time_approval` ADD CONSTRAINT fk_ptta_user_id  FOREIGN KEY (fk_userid) REFERENCES llx_user(rowid) ON DELETE NO ACTION ON UPDATE CASCADE;
ALTER TABLE `llx_project_task_time_approval` ADD CONSTRAINT fk_ptta_task_id  FOREIGN KEY (fk_task) REFERENCES llx_projet_task(rowid) ON DELETE NO ACTION ON UPDATE CASCADE;
