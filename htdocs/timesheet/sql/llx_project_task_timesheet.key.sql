-- ===================================================================
-- Copyright (C) 2018  Patrick Delcroix <patrick@pmpd.eu>
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
-- TS Revision 2.0.2

ALTER TABLE llx_project_task_timesheet ADD CONSTRAINT fk_ptts_user_idc  FOREIGN KEY (fk_userid) REFERENCES llx_user(rowid) ON DELETE NO ACTION ON UPDATE CASCADE;
ALTER TABLE llx_project_task_timesheet ADD CONSTRAINT fk_ptts_user_idm  FOREIGN KEY (fk_user_modification) REFERENCES llx_user(rowid) ON DELETE NO ACTION ON UPDATE CASCADE;

--/*llx_project_task_timesheet remove enum 2.3.3.5 --> 2.4*/
ALTER TABLE llx_project_task_timesheet MODIFY COLUMN status   integer default 1;
