# FIXME if not LLX_ what to do ?
CREATE VIEW view_pjtTskUsr AS
SELECT DISTINCT pjt.rowid as projectId,pjt.`ref` as projectRef,
pjt.title as projectTitle,pjt.dateo as projectStart, pjt.datee as projectStop,
tsk.rowid as taskId,tsk.`ref` as taskRef,tsk.label as taskTitle,tsk.dateo as taskStart, tsk.datee as taskStop,
usr.rowid as userId ,firstname,lastname
FROM ((llx_element_contact as ctc 
JOIN llx_user as usr 
ON ctc.fk_socpeople=usr.rowid )
JOIN llx_projet_task as tsk
ON ctc.element_id=tsk.rowid)
JOIN llx_projet as pjt
ON tsk.fk_projet=pjt.rowid
WHERE ctc.fk_c_type_contact BETWEEN "180" AND "181"
