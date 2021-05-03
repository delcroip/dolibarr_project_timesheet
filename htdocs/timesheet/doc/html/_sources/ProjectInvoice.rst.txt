Project Invoice
===============

This module enables the creation of an invoice based on the time spent.
The time spent can be grouped by user, by task or by user and task to
convert the sum of the time spent in quantity of existing services, or
one-time services (only for the invoice, not saved) or to not be
invoiced.

**Prerequisite**: the user making the invoice must be part of the
project, have right to make invoice and able to see the customer to
bill.

In order to achieve it, there is two steps before the draft invoice
creation.

-  .. rubric:: First invoice step: the general project’s invoice
      parameters
      :name: first-invoice-step-the-general-projects-invoice-parameters

|image29| in this screen, the project, the dates, the customer, the
grouping method, invoiceable task need to be defined, if one comes from
the report the same value should be checked.

-  .. rubric:: Second Invoice step: assignation of service to each time
      spent group
      :name: second-invoice-step-assignation-of-service-to-each-time-spent-group

This screen will define what will be shown in the invoice.

The “Existing” field

Services defined in Product/service menu could be used. This field is a
search box, just typing text in it will fetch services with a name close
to the text entered. the service consumption will be updated correctly
as for a normal invoice where an existing service is used. This field
can have a default value, this value can come from different places:

-  If there is a default service defined on the **task** card, this one
   will be used

-  if not and If there is a default service defined on the **user**
   card, this one will be used

-  if not and If there is a default service defined on the **module
   setup**, this one will be used

the “New” fields

If something is entered in those fields then an ad-hoc service will be
used with the price & VAT specified in the different columns.

|image30|\ The two last columns are used the generate the quantity of
the service that will be invoiced. The duration of the time spent on
task are shown in the column “Saved duration” and it will be converted
base on quantity based on the Quantity per unit column. If day is
selected, the calculation will use the hours per day set in the module
setup page. The quantity is editable

Example:

196 hours, with 1 day (8 h) as Quantity per unit will make a quantity of
196/(1*8)= 24,5.

in the Latest Dolibarr, once the invoice is created the time spend will
be linked to the invoice and invoice line (information not used today)

.. |image29| image:: img/image29.png
   :width: 6.26806in
   :height: 2.175in
.. |image30| image:: img/image30.png
   :width: 6.26806in
   :height: 1.78958in