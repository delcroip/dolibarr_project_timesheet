# dolibarr_project_timesheet
Timesheet view for project in Dolibarr


# Functionnalities
 - Timespend entry by week for all the eligible task of an user per week in the timesheet page (splited week possible)
 - Holiday are showed in the timesheet page
 - Layout customisation (show/hide the '00:00', show/hide the draft project task,show ref or not, show the related project or not ... )
 - Dolibarr Print mode supported 
 - Timesheet approval by N+1 (home box & email reminder possible)
 - Tasks can be masked/showed via favoris
 - User report by month
 - Project report by month
 - create invoice from the project report

 
# Functionnalities eligible for removal
- favoris 

# Functionnalities not maintained
 - timesheet navigation & submit done with Ajax ( no reload of the entire page needed)

# known bug/limitation
- If the combo box ajax bug, it's not possible to enter new whitelist, new config parameters enable to deactivate for all dolibarr.
- Back ground color not working with the metro theme (work arround: replace "background:#fafafa!important" by "background:" in htdocs\theme\metro\style.css.php:2253).



# Next developement for other release

- handle the right for cust /  supplier / other approval  
- add automatic reminder for the approval
- add feature in Project to foresee workload per user, task and "week"
- reminder when TS is not filled in ( email and home page)
- show the Quantity in the step 2 of invoicing (js)
- maintain the ajax behavior
- my timesheet page to see the status of the TS
- better ajax error when adding fav
- add total to the otherAP
- TS submission per month



# Change log
2.0.1 Change log from 2.0
 - Project approval corrected (for non admin no approval was shown)
 - PHP warning removed
 - Home timesheet box correted (was not showing the # of timesheet to approve)
 - send email over TS rejection

2.0 Change log from 1.5.1:   
 - Week over two month can be splited in 2 so an approval per month is possible.
 - note availale for each task (also in the approval flow)    
 - chained approval for project
 - create invoice from the project report
 - reports shows time in hours and days
 - tab in the setup for better browsing experience (in JS so config is kept when changing tab)
 - favoris in a tab (not a new page)
 - favoris can be set simply by pushing on a star next to the task name in timesheet screen
 - better handling of search boxes
 - more translation (ES, DE, IT, FR, US)



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
