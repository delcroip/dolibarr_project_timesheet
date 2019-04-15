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


CREATE TABLE llx_attendance_user
(
rowid                 SERIAL ,
fk_user               integer,
user_id               varchar(64) UNIQUE, -- ID number This could be the working ID, students ID, license ID, but should be unique.
card_number           integer, -- This corresponds to the number of an RFID card, this depends on the verify style.
password              VARCHAR(128), --Password to access, this depends on the verify style.
verify_style          integer DEFAULT NULL, --ets the way the user verifies on the machine, e.g. use password and fingerprint, use RFID card or fingerprint.
date_modification     TIMESTAMP     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,       -- timesheet user (redondant)
fk_user_modification  integer  DEFAULT NULL,
PRIMARY KEY (rowid)
)
ENGINE=innodb;

CREATE TABLE llx_attendance_system_user
(
rowid                 SERIAL ,
uid                   integer NOT NULL,
user_id               varchar(64) UNIQUE, -- ID number This could be the working ID, students ID, license ID, but should be unique.
permissions           integer NOT NULL,  -- his sets the level of actions that a user may perform, regular employees are 'common users' while the IT admins may be 'superadmins'.
enabled               integer default '1',--A user may be enabled or disabled.
group                 integer DEFAULT '1', -- New users are by default on group 1, but there may be 100 different groups, a user can only belong to one group, they could inherit permissions and settings from the group to which they belong, at the same time a group may have 3 timezones and a verify style.
timezone1             integer DEFAULT NULL,
timezone2             integer DEFAULT NULL,
timezone3             integer DEFAULT NULL,
verify_style          integer DEFAULT NULL, --ets the way the user verifies on the machine, e.g. use password and fingerprint, use RFID card or fingerprint.
date_modification     TIMESTAMP     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,       -- timesheet user (redondant)
fk_user_modification  integer  DEFAULT NULL,
PRIMARY KEY (rowid)
)
ENGINE=innodb;

CREATE TABLE llx_attendance_user_template
(
rowid                 SERIAL ,
user_id               varchar(64) UNIQUE, -- ID number This could be the working ID, students ID, license ID, but should be unique.
template           blob NOT NULL,  -- his sets the level of actions that a user may perform, regular employees are 'common users' while the IT admins may be 'superadmins'.
finger               integer default '1',--A user may be enabled or disabled.
date_modification     TIMESTAMP     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,       -- timesheet user (redondant)
fk_user_modification  integer  DEFAULT NULL,
PRIMARY KEY (rowid)
)
ENGINE=innodb;