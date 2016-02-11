# dolibarr_project_timesheet
Timesheet view for project in Dolibarr
timesheet navigation & submit done with Ajax ( no reload of the entire page needed)

# Functionnalities
- Enable to enter all the time spent on task for a week
- Possibility to limit the task showedby using a whitelist
- Time report per user and per project.
- Customisation of the task information to show (show ref or not, show the related project or not ...)
- layout customisation (show/hide the '00:00', show/hide the draft project task ... )

# known bug/limitation
- when session is timed out, the login page isn't showed
- If the combo box ajax bug, it's not possible to enter new whitelist, new config parameters enable to deactivate for all dolibarr.
- Back ground color not working with the metro theme (work arround: replace "background:#fafafa!important" by "background:" in htdocs\theme\metro\style.css.php:2253).

# Next developement
- support the holidays creation/deduction from the timesheets --> fields test will be needed to validate the Ajax before starting the next steps
- Timesheet approval/rejection by N+1
