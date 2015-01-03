dolibarr_project_timesheet
==========================

Timesheet view for project in Dolibarr

- enable bulk timesheet update for all the task opened for one week
- shows tasks where the dolibarr user is set as a contributor and if the task is still open the week selected
- (new)possibility to enter timesheet per day or per hours ( the nunmber of hours per days could be change in the config page)

Principle
- get the task opened with the dolibarr user configured as contributor
- the timesheet class herit from the task class therefore all the changes use task methods as delTimeSpent, updateTimeSpent, addTimeSpent

Next dev:
- generate some kind of report per calandar month / projet
