# dolibarr_project_timesheet changelog
4.6.6
- support php 8
- show task even if not assigned anymore but with time
- fix chrono icon for apple
- add startime for chrono
- fix filer timesheet
- fix CSRF tocken issues
- fix js error element cannot be null
- fix css error


4.6.0
- fix missing line in approvals
- fix php _ errors
- fix remaining csrf missing token
- add empty line if other tieme registered
- fix holiday blockade on half days
- fix table prefix 
- 

4.5.8
- fix mysql issue (approval and box)
- fix missing include (getToken)

4.5.7
- fix psql issue (approval and box)

4.5.6
- fix csrf issue in other pages
- fix box issue

4.5.5
- fix: pdf header with small pictures
- misc: white logo


4.5.4
- fix: save and next
- fix: update progress only #190
- fix: calcualte total lines

4.5.3
- better adaptative header size in PDF


4.5.2
- fix missing subordinates on user reports
- add "all" whenever someone have subordinates
- add date sorting in the pdf reports
- trads updates (visa -> signature + DE fixes)
- adds the buttons on top and bottom of the page 
- fix bad report url
- fix foreach js syntax (nicolasb827)


4.5.1
- Fix Project data on reports 

4.5.0
- new; first verison of the TS missing reminder
- new: add submit/save next button on ts pages
- new: add propal lines in the invoice service assignation screen (behave as lumpsum)
- new: add lump sum option the invoice service assignation screen
- new: show "all users" report
- new: enable masking "import from agenda"
- new add customer code in exports
- new: total lines are dynamic
- new: box for average/Max timesheet delta with hours per week for the user
- fix: role authorisation issue on project/task
- fix issue when selecting "curent user" in ts for other option
- fix: unblock holiday if not approved
- fix: project name on user report


4.4.10 (2021-12-01)
- fix the watch clock issue (going way to fast)

4.4.10 (2021-10-16)
- fix sendapproval
- Fix: User rights for viewing PDF. #165 
- FIx: SQL syntax error. #164 

4.4.9 (2021-08-28)
- fix white button
- fix sunday not showed


4.4.8 (2021-08-06)
- fix: This month link
- fix: wrong user report for user with subordinates


4.4.7 (2021-07-18)
- new setting to manage public holiday time (separeated from holiday management)
- fix: show public holiday without country
- fix: pgsql issue


4.4.6 (2021-06-06)
- new import from agenda
- new show public holiday
- new block time entry during public holiday
- fix total takes holiday into account


4.4.5 (2021-05-11)
- fix approval admin rights

4.4.4 (2021-05-07)
- fix: approval not working (likely since 4.4.0)
- misc: hide project report if not the rights
- fix: perm issue on approval admin


4.4.3 (2021-05-03)
- fix block holiday setting
- fix missing perms trads

4.4.2 (2021-05-02)
- fix report user userlist
- new: prems

4.4.1(2021-05-01)
- fix: tasknote deleteion not working
- fix: week note not saved
- new: allow project all rights to access to all project reports
- new: allow attendance admin to enter time for everyone


4.4.0 (2021-04-30)
- fix: UI improvements
- new: block holiday

4.3.9 (2021-02-09)
- new permission attendance->admin has the same priviledge as Admin 

4.3.8
- new: add dropdown for export format
- fix: billing role was not allowed to get reports

4.3.7
- fix activation issue
- fix: name of the pdf

4.3.6
- new: billing role
- new: add time on public project
- fix: add a single task as favorites

4.3.5
- fix: user report doesn't show all users

4.3.4
- new: support cust language in invoice
- new: support cust price for service
- fix: setup "show task in invoice" correctly displayed  

4.3.3
- new: add chrono fopr other
- fix: report pgsql
- fix report html not correct (task missing)

4.3.2
- new: pdf user report 
- fix: SQL error on ts page when draft hidden

4.3.1
- fix: show only active user in unserreport (admin)
- fix: missing SQL quotes
- fix: wrong task in reports

4.3.0
- new: possibility to ungroup reports
- fix: remove closed project (without end date) task
- new: improve timesheet box (add ts to submit and layout imnprovement)

4.2.2
- new: show the time not editable (when there is several task time for ady/task)

4.2.1
- fix: blank timesheet page

4.2.0
- fix: send approval reminder
- fix: favorite edit card update the task when the project is selected
- fix: update the note and progress from team approval
- fix: update declared progress from chrono
- fix: error in team approval (#109)
- new: link propal to timesheet invoice (#101)
- new: favorite tab in chrono (#111)
- new: add user, project and task link in reports (#104)
- new: use the hourly or daily rate as default in invoice unit price

4.1.2
- fix count issue on chrono page
- fix UTF-8 lang file 

4.1.1 
- new: add timesheet for other works with project "subordinate"

4.1.0
- new: timespent admin
- new: option to allow entering time on closed day (e.g. weekend)
- new: invoiced time background color (approved color)
- new: option to allow modification invoiced time
- new: hidden option now in admin page (hide name, eval, round day )
- fix: remove warning tasktimesheet not an array
- fix: remove warning with float hour per day
- fix: label not display in attendance sheet

4.0.18
- fix report issue (first group splited in two)
- new: update task progress declared from timesheet

4.0.17
- new keeping project when moving to report
- fix last week/month report short links
- clean: project invoice as a project tab

4.0.16
- fix favorite not wworking

4.0.15
- new filter on task info 
- new: note icon changes if the note is filled
- new: quick link for report (this week ...)
- fix: error in 4.0.14 change for project report

4.0.14
- fix: error in project report from 4.0.11
4.0.13
- fix: report take now into account time in the end date

4.0.12
- fix: correct the reports (first line was always wrong)

4.0.11
- clean: use task_datehour iso task_date to retrieve time spent
- new: hide ref become hide title
- fix company link not working
- fix warning when reopening an empty approval submission
- fix #83 version compare issue on php 5

4.0.10
 - fix total not display in timesheet page
 - fix submittion without changes of timesheet

4.0.9
 - fix:remove errro message of 4.0.8
 
4.0.8 change log from 4.0.7
 - new: block creation of timespent after the end of event
 - fix: add missing trad 
 - fix: line where only the note was modified were not sent to the server in 4.0.7
 - fix: previous week link not working on slitted week if the first day of the month was a monday
 - fix: display hours iso seconds

4.0.7 change log from 4.0.3
 - new: xlsx, csv, tsv report export for several projects
 - new: more flexibility in time enter (support .5, 0 ... ctr+v ) 
 - new: (EXPERIMENTAL) support multiple taxes in invoice (TIMESHEET_EVAL_ADDLINE must be set to 1)
 - fix: selllist condition compatible with pgsql
 - fix: php warnings displayed
 - fix: only line modified are sent to the servers (support more task lines)

4.0.3 change log from 4.0.0
 - new: chrono status (auto stop)
 - new: max value for chrono
 - new: default value for chrono (when max is reached)
 - new: xlsx, csv, tsv report export 
 - new: remove signbox from pdf
 - fix: multi line comment in comment display in pdf
 - fix: pgsql error of wrong column
 - fix: project approval without team approval
 - fix: sellect sellist without space between column names
 - fix: time display issue
 - fix: tabletop display issue on pdf

4.0.0 change log from 3.2
 - new chrono pages per task
 - new time type in pdf can differt frm timesheet page
 - new roles : timesheet user, chrono user and admin chrono
 - clean: pdf export user date instead of UNIX time
 - fix: note editing in approval flow
 - fix: approver saved properly in the bd
 - fix: pgSQL compatibility issue
 - fix: no from-to date showed on invoice
 - fix: main menu icon display in dolibarr 8


3.2 change log from 3.1.7
 - new: report project between dates instead of per month
 - new: invoice project between dates instead of per month
 - new: show the cusror pointer when the favourite start is hoovered
 - fix: fix pdf layout issue when there is too many time for an user

3.1.7 change log from 3.1.2
 - fix: date were sometime displayed on two lines
 - fix: avoid max approval to be set at 0 (was genereating issues elsewhere)
 - fix remove project closed from admin view of project report
 - fix: support installation in a subfolder of the custom folder
 - fix: error in case the timesheet favourite didn't have any task related
 - clean: align time formating accros the module
 - better layout for the total in the pdf

3.1.2 change log from 2.2.11
 - new: button to generate users' attendance sheet from project report pages
 - fix: #57 note were not saved when containing a simple quote
 - fix: install in htdoc folder
 - fix: #50 boxapproval
 - fix: format & typo
 - fix: #48 timesheet start 1 day in advance
 - fix: #55 invoice show close & draf project in the dropdown list
 - fix: #49 removing the value in a timesheet will put it a 0


2.2.11 change log from 2.2.10
 - fix: background color missing with approval status
 - clean: total calculation improvments
 - clean: add new trads
 - clean: remove display error

2.2.10 change log from 2.2.9
 - new: line total header
 - new: add a total line every 10 task line
 - fix: in day mode, the leading 0 isn't mandatory anymore (e.g. ".1")
 - clean: use liste_title for totals
 - clean: improve total & overtime behaviour

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

