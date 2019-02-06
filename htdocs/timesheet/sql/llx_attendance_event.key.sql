-- ===================================================================
-- Copyright (C) 2019  Patrick Delcroix <patrick@pmpd.eu>
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
-- TS Revision 4.0.0

-- this table is used to store the timesheet favorit


ALTER TABLE llx_attendance_event ADD CONSTRAINT fk_ts_ae_user_idm  FOREIGN KEY (fk_user_modification) REFERENCES llx_user(rowid) ON DELETE NO ACTION ON UPDATE CASCADE;
ALTER TABLE llx_attendance_event ADD CONSTRAINT fk_ts_ae_user_id  FOREIGN KEY (fk_userid) REFERENCES llx_user(rowid) ON DELETE NO ACTION ON UPDATE CASCADE;
ALTER TABLE llx_attendance_event ADD CONSTRAINT fk_ts_ae_project_id FOREIGN KEY (fk_project) REFERENCES llx_projet(rowid) ON DELETE NO ACTION ON UPDATE CASCADE;
ALTER TABLE llx_attendance_event ADD CONSTRAINT fk_ts_ae_third_party FOREIGN KEY (fk_third_party REFERENCES llx_soc(rowid) ON DELETE NO ACTION ON UPDATE CASCADE;
ALTER TABLE llx_attendance_event ADD CONSTRAINT fk_ts_ae_task FOREIGN KEY (fk_task) REFERENCES llx_projet_task(rowid) ON DELETE NO ACTION ON UPDATE CASCADE;
