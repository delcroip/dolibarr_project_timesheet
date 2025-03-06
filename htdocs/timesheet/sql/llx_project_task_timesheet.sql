-- ===================================================================
-- Copyright (C) 2015  Patrick Delcroix <patrick@pmpd.eu>
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


CREATE TABLE llx_project_task_timesheet
(
rowid                 serial ,
date_start              DATE        NOT NULL, -- start date of the period
date_end               DATE        NOT NULL, -- start date of the period
status                   integer default 1, -- enum('DRAFT','SUBMITTED','APPROVED','CANCELLED','REJECTED','CHALLENGED','INVOICED','UNDERAPPROVAL','PLANNED') DEFAULT 'DRAFT',
note                  VARCHAR(1024), -- note
date_creation         DATETIME      NOT NULL,
date_modification     TIMESTAMP     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
fk_userid               integer     NOT NULL,          -- timesheet user
fk_user_modification  integer  default NULL,
PRIMARY KEY (rowid)
)
ENGINE=innodb;
