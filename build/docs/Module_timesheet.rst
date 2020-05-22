Current version **4.1.3**

Dolibarr minimum version : **3.5.6**

Dolibarr latest version tested : **11.0**

Author: Patrick Delcroix contact@pmpd.eu

   Note: this module is not able to do any changes to invoices; the only
   invoice related action possible is the creation of a draft invoice by
   using the Dolibarr core methods (there is no invoice related database
   request in the module)

.. contents:: a title for the contents
    :depth: 2

Prerequisites:
==============

-  .. rubric:: Other module
      :name: other-module

Project module must be activated (should be automatically activated upon
timesheet activation)

-  .. rubric:: Project created and validated
      :name: project-created-and-validated

draft project won’t be shown by default but they can be shown by
changing the module configuration (see setup page part)

-  .. rubric:: Project open
      :name: project-open

Timespent entry is only possible if date of the entry is between start
and end date of the project; no start date mean that the project’s task
will be shown until the end date; no end date means that the project’s
task will be always shown after the start date

-  .. rubric:: Task created and open
      :name: task-created-and-open

Timespent entry is only possible if date of the entry is between start
and end date for the task. No start date mean that the task will be
shown until the end date; no end date means that the task will be always
shown after the start date

-  .. rubric:: User assigned to the task
      :name: user-assigned-to-the-task

in order to assign a user to a task he must be first assign to the
project via Project→contact Project. Then he can be assigned to the task
: Task > resource assignation.



.. include:: TimesheetView.rst
   :literal:

.. include:: Attendance.rst
   :literal:

.. include:: Favourite.rst
   :literal:

.. include:: Approval.rst
   :literal:

.. include:: ProjectReports.rst
   :literal:

.. include:: ProjectInvoice.rst
   :literal: