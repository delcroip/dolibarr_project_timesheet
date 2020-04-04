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
ALALTER TABLE llx_attendance_system_user_link ADD CONSTRAINT fk_ts_asul_user_id  FOREIGN KEY (fk_attendance_system_user) REFERENCES llx_attendance_system_user(rowid) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE llx_attendance_system_user_link ADD CONSTRAINT fk_ts_asul_system_id  FOREIGN KEY (fk_attendance_system) REFERENCES llx_attendance_system(rowid) ON DELETE CASCADE ON UPDATE CASCADE;
