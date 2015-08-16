# dolibarr_project_timesheet
Timesheet view for project in Dolibarr

# Functionnalities
- Enable to enter all the time spent on task for a week
- Possibility to limit the task showedby using a whitelist
- Time report per user and per project.
- Customisation of the task information to show (show ref or not, show the related project or not ...)
- layout customisation (show/hide the '00:00', show/hide the draft project task ... )

# known bug/limitation
- If the combo box ajax bug, it's not possible to enter new whitelist, this happen with Doibarr 3.5.7 (To disable the AJAX combo box in dolibarr you have to set the variable MAIN_DISABLE_AJAX_COMBOX to 1 in setup>others)

# Next developement
- Timesheet approval/rejection by N+1
- (to be confirmed) support the holidays creation/deduction from the timesheets
