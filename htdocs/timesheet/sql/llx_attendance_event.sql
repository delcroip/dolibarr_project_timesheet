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


CREATE TABLE llx_attendance_event
(
rowid                   serial ,
date_time_event          DATETIME        NOT NULL , -- start date of the period
event_location_ref      VARCHAR(1024) DEFAULT NULL, -- IP or equipment of loggin
event_type              integer default 1,-- (1-->'heartbeat','sign-in','sign-out,auto-sign-in, auto-sign-out) DEFAULT 'heartbeat',
note                  VARCHAR(1024),
date_modification     TIMESTAMP     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
fk_userid             integer  NOT NULL,          -- timesheet user (redondant)
fk_user_modification  integer  default NULL,
fk_third_party        integer DEFAULT NULL, -- null means time for the company
fk_task               integer DEFAULT NULL,
fk_project            integer DEFAULT NULL,
token                   varchar(64) DEFAULT NULL,  -- to assign time on a finacial code (future proof) or token
status               integer DEFAULT NULL,
PRIMARY KEY (rowid)
)
ENGINE=innodb;
