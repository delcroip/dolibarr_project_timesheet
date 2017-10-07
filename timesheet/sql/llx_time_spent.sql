-- ===================================================================
-- Copyright (C) 2017  Patrick Delcroix <pmpdelcroix@gmail.com>
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
datetime_start              DATETIME        NOT NULL , -- start date of the period
start_place             VARCHAR(1024) DEFAULT NULL, -- IP or equipment of loggin
datetime_end                DATETIME        NOT NULL , -- start date of the period
end_place               VARCHAR(1024) DEFAULT NULL, -- IP or equipment of loggin
status                  tinyint , -- (1-->'DRAFT','SUBMITTED','APPROVED','CANCELLED','REJECTED','CHALLENGED','INVOICED','UNDERAPPROVAL','PLANNED') DEFAULT 'DRAFT',
note                  VARCHAR(1024),
date_creation         DATETIME      NOT NULL,
date_modification     TIMESTAMP     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,  
fk_userid             integer  NOT NULL,          -- timesheet user (redondant)
fk_user_creation      integer,
fk_user_modification  integer  default NULL,
fk_third_party        integer DEFAULT NULL, -- null means time for the company
PRIMARY KEY (rowid)
) 
ENGINE=innodb;
