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
-- TS Revision 1.5.0

-- this table is used to store the timesheet favorit


CREATE TABLE llx_timesheet_user
(
rowid                 integer NOT NULL AUTO_INCREMENT,
fk_userid               integer NOT NULL,          
year_week_date          DATE NOT NULL, 
status                enum('DRAFT','SUBMITTED','APPROVED','CANCELLED','REJECTED','CHALLENGED') DEFAULT 'DRAFT',
target            enum('team','project','customer','provider','other') DEFAULT 'team', -- a team ts is always needed 
fk_project_tasktime_list VARCHAR(512), 
fk_user_approval              integer default NULL,
date_creation         DATETIME NOT NULL,
date_modification     TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,  
fk_user_creation        integer,
fk_user_modification         integer,
fk_timesheet_user       integer, -- in case target is not team 
fk_task       integer, -- in case target is not team, querry on task
note       VARCHAR(1024), -- in case target is not team, querry on task
PRIMARY KEY (rowid)
) 
ENGINE=innodb;

--ALTER TABLE llx_timesheet_user
--ADD ( fk_timesheet_user       integer, 
--fk_task       integer, 
--note       VARCHAR(1024)
--)