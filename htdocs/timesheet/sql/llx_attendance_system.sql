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


CREATE TABLE llx_attendance_system
(
rowid                 SERIAL ,
label                 varchar(64) NOT NULL,  -- to link with the card system
ip                    varchar(64) NOT NULL,
port                  INTEGER DEFAULT '4370',
note                  VARCHAR(1024),
fk_third_party        integer DEFAULT NULL, -- null means time for the company
fk_task               integer DEFAULT NULL,
fk_project            integer DEFAULT NULL,
status               integer DEFAULT NULL,  
date_modification     TIMESTAMP     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,       -- timesheet user (redondant)
fk_user_modification  integer  DEFAULT NULL,
PRIMARY KEY (rowid)
) 
ENGINE=innodb;
