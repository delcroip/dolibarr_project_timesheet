# dolibarr_project_timesheet
Module to increase time registration efficiency for Dolibarr, well suited for counsulting firm but not only


# Functionnalities
 - Timespend entry by week ot Month for all the eligible tasks of an user in the timesheet page (splited week possible, possible to add time spent for subordinates)
 - total per day and per timesheet
 - Holiday are showed in the timesheet page
 - Layout customisation (show/hide the '00:00', show/hide the draft project task,show ref or not, show the related project or not ... )
 - Dolibarr Print mode supported 
 - Timesheet approval by N+1 (home box & email reminder possible)
 - Tasks can be masked/showed via favoris
 - User report by month
 - Project report by month
 - create invoice from the project report

 
# Functionnalities eligible for removal

# Functionnalities not maintained
 - timesheet navigation & submit done with Ajax ( no reload of the entire page needed)

# known bug/limitation
- Back ground color not working with the metro theme (work arround: replace "background:#fafafa!important" by "background:" in htdocs\theme\metro\style.css.php:2253).

# Next dev under anaylse
- Ressource planning  (planning for TL and PM + weekly summary by email to user)
    - non-project related TS 
    - simple attendance logging

# Next developement for other release
- integrate weekly hour in the ts messages along with a week total (possible issue with splitted week) 
- handle the right for cust /  supplier / other approval  
- show the Quantity in the step 2 of invoicing (js)
- my timesheet page to see the status of the TS
    - add automatic reminder for the approval
    - reminder when TS is not filled in ( email and home page)
- better ajax error when adding fav
- add total to the otherAP
- maintain the ajax behavior



# Change log

2.2.9 change log from 2.2.8
 - fix: project invoice not working
 - fix: project report (date/user/task) not working
 - clean: use oddeven instead of pair/impair as list class

2.2.8 change log from 2.2.7
 - new: add a super total: total of every day present on the timesheet screen 

2.2.7 change log from 2.2.6
 - fix: error in pgsql while generating the invoice
 - fix: link the created invoice to the project
 - fix: error in the reports pages with mysql
 - fix:ts draft not removed upon timespan change leading to days not accessible
 - clean: Invoice creation page: layout improvement

2.2.6 Change log from 2.2.3
 - new: support PostGreSQL database
 - new/fix: support custom project roles
 - new/fix: support Dolibarr 7.0.0
 - fix: behaviour of notes in the timesheet pages with favourites
 - fix: "not defied" showing instead of project name
 - fix: correction of the message when a favourite was added
 - clean: enum are not user anymore
 - clean: removal of dead code

2.2.3 Change log from 2.2.2
 - fix: trad issue
 - fix: sql error in approvals

2.2.2 Change log from 2.2
 - new: deletion of draft timsheet when switching between time span (no impact on time entered) 
 - fix: issue with winter time

2.2 Change log from 2.1.3
 - NEW: timesheet entry per month
 - NEW: add time spent for subordinates
 - fix: colation issue with latin/latin_swedish on approval page
 - fix: approval with project only
 - clean: files and classes renaming
 - clean: small date only appear for month mode


2.1.3 Change log from 2.1.2
 - fix : start and end date missing in task line
 - fix : task end/start in middle of the week wasn't taken into account

2.1.2 Change log from 2.1.1
 - fix third party not showed when note wasn't activated
 - fix: holiday time wasnot adding-up in the total lines

2.1.1 Change log from 2.0.1
 - fix: Contact email correction
 - fix: js blocked if the module was in the custom folder
 - fix: default date for report is the current date not jan 2020

2.1 Change log from 2.0.1
- fix: Submit (without pushing save before)save correctly the time for approval
- fix:javascript error that prevented to color change upon time entry
- fix: progress not showing up
- fix: weeks with a 8th day
- fix: dolibarr 6.0 compatibility
- change:"New" button removed from the admin page,
- change: end date showed on the admin card page 

2.0.1 Change log from 2.0
 - fix: Project approval corrected (for non admin no approval was shown)
 - fix: PHP warning removed
 - fix: Home timesheet box correted (was not showing the # of timesheet to approve)
 - new: send email over TS rejection

2.0 Change log from 1.5.1:   
 - new: Week over two month can be splited in 2 so an approval per month is possible.
 - new: note availale for each task (also in the approval flow)    
 - new: chained approval for project
 - new: create invoice from the project report
 - new: reports shows time in hours and days
 - new: tab in the setup for better browsing experience (in JS so config is kept when changing tab)
 - new: favoris in a tab (not a new page)
 - new: favoris can be set simply by pushing on a star next to the task name in timesheet screen
 - new: better handling of search boxes
 - new: more translation (ES, DE, IT, FR, US)



Change log from 1.4.3:

 - Timesheet approval by N+1, 
 - Reminder (email) for to be approved timesheet possible through dolibarr planned tasks 
 - admin wiew for the Approval (change a approval status outside the normal approval flow)
 - Home box with the pending timesheet to be approved
 - Blocking some weekdays (e.g week ends)
 - Holiday showed in the timesheet
 - Holiday time can be included in the timesheet totals
 - Typo correction for French.
 
Change log from 1.4.1: 

 - correction of the Spanish language (thanks to vinclar)
 - possible to deactivate the dolibarr Ajax for the dropdown list for the setup page (in case of issue to add whitelist) 
 - keep the whitlistmode after submit / go to date / next / previsous week


Change log from 1.4: 

- bugfix for the tasktime date in the project page
- link to have the different whitelist behaviour (black list, and none)
- Spanish language (google trad)
- typo correction for French
- support the print mode for timesheet & the report
- show the project open to everyone on the new whitelist page

Change log from 1.3.7:

- layout improvement: timesheet, setup page, reports
- whitelist to show only some project/task
- taslk column customisation 
- new task column: company, parent task
- new report option: report all, export friendly layout
- user report available for the N-2, N-3 

Change log from 1.3.6:

- compatible avec dolibarr 3.7


Change log from 1.3.3:

- Works with PHP<=5.3
- Possibility to remove the 0:00
- Color code for already filled tasktime / new tasktime and error
- Bux fixes in the report
- Better date dialog
- N+1 is able to check the user report of his N's
