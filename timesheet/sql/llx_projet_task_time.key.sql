/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
/**
 * Author:  delcroip
 * Created: 06-avr.-2017
 */

ALTER TABLE llx_projet_task_time ADD COLUMN status  enum('DRAFT','SUBMITTED','APPROVED','CANCELLED','REJECTED','CHALLENGED','INVOICED','UNDERAPPROVAL') DEFAULT 'DRAFT';
ALTER TABLE llx_projet_task_time ADD COLUMN status  enum('DRAFT','SUBMITTED','APPROVED','CANCELLED','REJECTED','CHALLENGED','INVOICED','UNDERAPPROVAL') DEFAULT 'DRAFT';
ALTER TABLE llx_projet_task_time ADD COLUMN status  enum('DRAFT','SUBMITTED','APPROVED','CANCELLED','REJECTED','CHALLENGED','INVOICED','UNDERAPPROVAL') DEFAULT 'DRAFT';