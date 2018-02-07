/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
/**
 * Author:  delcroip
 * Created: 04-févr.-2018
 */

ALTER TABLE llx_project_task_timesheet ADD CONSTRAINT fk_ptts_user_idc  FOREIGN KEY (fk_userid ) REFERENCES llx_user(rowid) ON DELETE NO ACTION ON UPDATE CASCADE;
ALTER TABLE llx_project_task_timesheet ADD CONSTRAINT fk_ptts_user_idm  FOREIGN KEY (fk_user_modification) REFERENCES llx_user(rowid) ON DELETE NO ACTION ON UPDATE CASCADE;

--/*llx_project_task_timesheet remove enum 2.3.3.5 --> 2.4*/
ALTER TABLE llx_project_task_timesheet MODIFY COLUMN status   integer default 1;
