-- ===================================================================
-- Copyright (C) 2017  Patrick Delcroix <patrick@pmpd.eu>
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
-- TS Revision 2.0.2

-- this table is used to store the timesheet favorit


CREATE TABLE llx_time_spent
(
rowid                   integer     NOT NULL AUTO_INCREMENT,
date_start              DATE        NOT NULL , -- start date of the period
date_end                DATE        NOT NULL , -- start date of the period
time_start              TIME NOT NULL,
time_end                TIME NOT NULL,
weekdays                CHAR(8) default "x1111100",

end_place               VARCHAR(1024) DEFAULT NULL, -- IP or equipment of loggin
timetype               tinyint ,  ---( 1-->'Not_related',2-->'task','project','timespent'
status                 tinyint , -- (1-->'DRAFT','SUBMITTED','APPROVED','CANCELLED','REJECTED','CHALLENGED','INVOICED','UNDERAPPROVAL','PLANNED')
note                  VARCHAR(1024),
date_creation         DATETIME      NOT NULL,
date_modification     TIMESTAMP     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
fk_userid             integer  NOT NULL,          -- timesheet user (redondant)
fk_user_creation      integer,
fk_user_modification  integer  default NULL,
fk_element_id        integer DEFAULT NULL, -- can be task, project, timespent ...
PRIMARY KEY (rowid)
)
ENGINE=innodb;
