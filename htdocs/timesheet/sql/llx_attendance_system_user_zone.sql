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


CREATE TABLE llx_attendance_system_user_zone
(
rowid                  SERIAL ,
zone integer not null,
fk_attendance_system_user                integer  NOT NULL,  -- to link with the zk user
date_modification      TIMESTAMP     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,       -- timesheet user (redondant)
fk_user_modification   integer  DEFAULT NULL,
PRIMARY KEY (rowid)
)
ENGINE=innodb;