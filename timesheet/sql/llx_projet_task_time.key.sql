/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
/**
 * Author:  delcroip
 * Created: 06-avr.-2017
 */

ALTER TABLE llx_projet_task_time ADD COLUMN status   integer default 1; -- enum('DRAFT','SUBMITTED','APPROVED','CANCELLED','REJECTED','CHALLENGED','INVOICED','UNDERAPPROVAL','PLANNED') DEFAULT 'DRAFT';
ALTER TABLE llx_projet_task_time ADD COLUMN fk_task_time_approval   integer;


ALTER TABLE llx_projet_task_time ADD CONSTRAINT fk_ptt_ptta_id  FOREIGN KEY (fk_task_time_approval) REFERENCES llx_project_task_time_approval(rowid) ON DELETE NO ACTION ON UPDATE CASCADE;

--/*llx_projet_task_tim remove enum 2.3.3.5 --> 2.4*/
ALTER TABLE llx_projet_task_time MODIFY COLUMN status   integer default 1;
