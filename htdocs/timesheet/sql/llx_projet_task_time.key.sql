-- ===================================================================
-- Copyright (C) 2018  Patrick Delcroix <patrick@pmpd.eu>
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

ALTER TABLE llx_projet_task_time ADD COLUMN status   integer default 1; -- enum('DRAFT','SUBMITTED','APPROVED','CANCELLED','REJECTED','CHALLENGED','INVOICED','UNDERAPPROVAL','PLANNED') DEFAULT 'DRAFT';
ALTER TABLE llx_projet_task_time ADD COLUMN fk_task_time_approval   integer;


ALTER TABLE llx_projet_task_time ADD CONSTRAINT fk_ptt_ptta_id  FOREIGN KEY (fk_task_time_approval) REFERENCES llx_project_task_time_approval(rowid) ON DELETE NO ACTION ON UPDATE CASCADE;

--/*llx_projet_task_tim remove enum 2.3.3.5 --> 2.4*/
ALTER TABLE llx_projet_task_time MODIFY COLUMN status   integer default 1;
