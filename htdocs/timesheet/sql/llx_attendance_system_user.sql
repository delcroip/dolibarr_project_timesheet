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


CREATE TABLE llx_attendance_system_user
(
rowid                  SERIAL ,
fk_user                integer  NOT NULL,  -- to link with the dolibarr user
asu_id                 integer,
rfid                   integer DEFAULT NULL, -- null means time for the company
role                   integer DEFAULT 0, --- role 0 = LEVEL_USER, 2 = LEVEL_ENROLLER,12 = LEVEL_MANAGER,14 = LEVEL_SUPERMANAGER
passwd                 varchar(8), -- password on the attendance systems
data                   varchar DEFAULT NULL, -- data on the attendance system
status                 integer DEFAULT NULL,
date_modification      TIMESTAMP     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,       -- timesheet user (redondant)
fk_user_modification   integer  DEFAULT NULL,
PRIMARY KEY (rowid)
)
ENGINE=innodb;