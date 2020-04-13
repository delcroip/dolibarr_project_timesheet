-- ===================================================================
-- Copyright (C) 2020  Patrick Delcroix <patrick@pmpd.eu>
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
-- TS Revision 5.0.0


CREATE TABLE llx_attendance_system_event
(
rowid                   serial ,
date_time_event          DATETIME        NOT NULL , -- start date of the period
fk_attendance_system     int not NULL, 
date_modification     TIMESTAMP     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
fk_attendance_system_user integer  NOT NULL,  
fk_attendance_event integer  NULL, -- will be linked after parsing if the data quality is good enough
fk_user integer NULL, -- user when found upon upload to simplify parsing
event_type              integer default 1,-- (1-->'heartbeat','sign-in','sign-out','access') DEFAULT 'heartbeat' depend on the attendance system,
status               integer DEFAULT NULL,
PRIMARY KEY (rowid)
)
ENGINE=innodb;
