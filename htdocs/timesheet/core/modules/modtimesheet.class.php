<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@capnetworks.com>
 *
 * This program is free software;you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation;either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY;without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */
/**
 *        \defgroup   timesheet     Module timesheet
 *  \brief      Example of a module descriptor.
 *                                Such a file must be copied into htdocs/timesheet/core/modules directory.
 *  \file       htdocs/timesheet/core/modules/modTimesheet.class.php
 *  \ingroup    timesheet
 *  \brief      Description and activation file for module timesheet
 */
include_once DOL_DOCUMENT_ROOT .'/core/modules/DolibarrModules.class.php';
/**
 *  Description and activation class for module timesheet
 */
class modTimesheet extends DolibarrModules
{
        /**
         *   Constructor. Define names, constants, directories, boxes, permissions
         *
         *   @param      DoliDB                $db      Database handler
         */
        public function __construct($db)
        {
        global $langs, $conf;
        $this->db = $db;
                // Id for module(must be unique).
                // Use here a free id(See in Home -> System information -> Dolibarr for list of used modules id).
                $this->numero = 861002;
                // Key text used to identify module(for permissions, menus, etc...)
                $this->rights_class = 'timesheet';
                // Family can be 'crm', 'financial', 'hr', 'projects', 'products', 'ecm', 'technic', 'other'
                // It is used to group modules in module setup page
                $this->family = "projects";
                // Module label(no space allowed), used if translation string 'ModuleXXXName' not found(where XXX is value of numeric property 'numero' of module)
                $this->name = preg_replace('/^mod/i', '', get_class($this));
                // Module description, used if translation string 'ModuleXXXDesc' not found(where XXX is value of numeric property 'numero' of module)
                $this->description = "TimesheetView";
		        $this->editor_name = 'Patrick Delcroix';
		        $this->editor_url = 'https://github.com/delcroip';
                // Possible values for version are: 'development', 'experimental', 'dolibarr' or version

                $this->version = '4.6.6';

                // Key used in llx_cons table to save module status enabled/disabled(where timesheet is value of property name of module in uppercase)
                $this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
                // Where to store the module in setup page(0=common, 1=interface, 2=others, 3=very specific)
                $this->special = 0;
                // Name of image file used for this module.
                // If file is in theme/yourtheme/img directory under name object_pictovalue.png, use this->picto='pictovalue'
                // If file is in module/img directory under name object_pictovalue.png, use this->picto='pictovalue@module'
                $this->picto='timesheet@timesheet';
                // Defined all module parts(triggers, login, substitutions, menus, css, etc...)
                // for default path (eg: /timesheet/core/xxxxx) (0=disable, 1=enable)
                // for specific path of parts(eg: /timesheet/core/modules/barcode)
                // for specific css file(eg: /timesheet/css/timesheet.css.php)
                $this->module_parts = array('triggers' => 0,
                                            'css' => array('/timesheet/core/css/timesheet.css'));
                ////$this->module_parts = array(
                //                         'triggers' => 0,        // Set this to 1 if module has its own trigger directory(core/triggers)
                //                                                        'login' => 0,        // Set this to 1 if module has its own login method directory(core/login)
                //                                                        'substitutions' => 0,        // Set this to 1 if module has its own substitution function file(core/substitutions)
                //                                                        'menus' => 0,        // Set this to 1 if module has its own menus handler directory(core/menus)
                //                                                        'theme' => 0,        // Set this to 1 if module has its own theme directory(theme)
                //                         'tpl' => 0,        // Set this to 1 if module overwrite template dir(core/tpl)
                //                                                        'barcode' => 0,        // Set this to 1 if module has its own barcode directory(core/modules/barcode)
                //                                                        'models' => 0,        // Set this to 1 if module has its own models directory(core/modules/xxx)
                //                                                        'css' => array('/timesheet/css/timesheet.css.php'),        // Set this to relative path of css file if module has its own css file
                //                                                        'js' => array('/timesheet/js/timesheet.js'), // Set this to relative path of js file if module must load a js on all pages
                //                                                        'hooks' => array('hookcontext1', 'hookcontext2')        // Set here all hooks context managed by module
                //                                                        'dir' => array('output' => 'othermodulename'), // To force the default directories names
                //                                                        'workflow' => array('WORKFLOW_MODULE1_YOURACTIONTYPE_MODULE2' => array('enabled' => '! empty($conf->module1->enabled) && ! empty($conf->module2->enabled)', 'picto' => 'yourpicto@timesheet')) // Set here all workflow context managed by module
                //                      );
                //$this->module_parts = array();
                //$this->module_parts = array('css' => array('/timesheet/css/timesheet.css'));
                // Data directories to create when module is enabled.
                // Example: this->dirs = array("/timesheet/temp");
                $this->dirs = array("/timesheet", "/timesheet/reports", "/timesheet/users", "/timesheet/tasks");
                // Config pages. Put here list of php page, stored into timesheet/admin directory, to use to setup module.
                $this->config_page_url = array("timesheetsetup.php@timesheet");
                // Dependencies
                $this->hidden = false;                        // A condition to hide module
                $this->depends = array('modProjet');                // List of modules id that must be enabled if this module is enabled
                $this->requiredby = array();        // List of modules id to disable if this one is disabled
                $this->conflictwith = array();        // List of modules id this module is in conflict with
                $this->phpmin = array(5, 0);                                        // Minimum version of PHP required by module
                $this->need_dolibarr_version = array(3, 5);        // Minimum version of Dolibarr required by module
                $this->langfiles = array("timesheet@timesheet");
                // Constants
                // List of particular constants to add when module is enabled(key, 'chaine', value, desc, visible, 'current' or 'allentities', deleteonunactive)
                // Example: $this->const=array(0 => array('timesheet_MYNEWCONST1', 'chaine', 'myvalue', 'This is a constant to add', 1),
                //                             1 => array('timesheet_MYNEWCONST2', 'chaine', 'myvalue', 'This is another constant to add', 0, 'current', 1)
                //);
                $r = 0;
                $this->const = array();
                $this->const[$r] = array("TIMESHEET_VERSION", "chaine", $this->version, "save the timesheet verison");// hours or days
                $r++;
                $this->const[$r] = array("TIMESHEET_ATTENDANCE", "int", 1, "layout mode of the timesheets");// hours or days
                $r++;
                $this->const[$r] = array("TIMESHEET_ATTENDANCE_SYSTEM", "int", 1, "Activation of attentance system");// hours or days
                $r++;
                $this->const[$r] = array("TIMESHEET_TIME_TYPE", "chaine", "hours", "layout mode of the timesheets");// hours or days
                $r++;
                $this->const[$r] = array("TIMESHEET_DAY_DURATION", "int", 8, "number of hour per day(used for the layout per day)");
                $r++;
                $this->const[$r] = array("TIMESHEET_TIME_SPAN", "chaine", "splitedWeek", "timespan of the timesheets");// hours or days
                $r++;
                 $this->const[$r] = array("TIMESHEET_HIDE_DRAFT", "int", 0, "option to mask to task belonging to draft project");
                $r++;
                $this->const[$r] = array("TIMESHEET_HIDE_ZEROS", "int", 0, "option to hide the 00:00");
                $r++;
                $this->const[$r] = array("TIMESHEET_HEADERS", "chaine", "Tasks", "list of headers to show inthe timesheets");
                $r++;
                $this->const[$r] = array("TIMESHEET_HIDE_REF", "int", 0, "option to hide the title in the timesheets");
                $r++;
                $this->const[$r] = array("TIMESHEET_SHOW_TIMESPENT_NOTE", "int", 1, "show the note next to the time entry");// hours or days
                $r++;
                $this->const[$r] = array("TIMESHEET_ADD_DOCS", "int", 0, "Allow to join files to timesheets");
                $r++;
               $this->const[$r] = array("TIMESHEET_ADD_FOR_OTHER", "int", 0, "enable to time spent entry for subordinates");// hours or days
                $r++;
                $this->const[$r] = array("TIMESHEET_WHITELIST_MODE", "int", 0, "Option to change the behaviour of the whitelist:-whiteliste, 1-blackliste, 2-no impact ");
                $r++;
                $this->const[$r] = array("TIMESHEET_WHITELIST", "int", 1, "Activate the whitelist:");
                $r++;
                $this->const[$r] = array("TIMESHEET_COL_DRAFT", "chaine", "FFFFFF", "color of draft");
                $r++;
                $this->const[$r] = array("TIMESHEET_COL_VALUE", "chaine", "F0FFF0", "color of day with entry");
                $r++;
                $this->const[$r] = array("TIMESHEET_COL_FROZEN", "chaine", "909090", "color of closed/frozen");
                $r++;
                $this->const[$r] = array("TIMESHEET_COL_SUBMITTED", "chaine", "00FFFF", "color of submitted");
                $r++;
                $this->const[$r] = array("TIMESHEET_COL_APPROVED", "chaine", "00FF00", "color of approved");
                $r++;
                $this->const[$r] = array("TIMESHEET_COL_CANCELLED", "chaine", "FFFF00", "color of cancelled");
                $r++;
                $this->const[$r] = array("TIMESHEET_COL_REJECTED", "chaine", "FF0000", "color of rejected");
                $r++;
                $this->const[$r] = array("TIMESHEET_DAY_MAX_DURATION", "int", 12, "max working hours per days");
                $r++;
                $this->const[$r] = array("TIMESHEET_ADD_HOLIDAY_TIME", "int", 1, "count the holiday in total or not");
                $r++;
                $this->const[$r] = array("TIMESHEET_BLOCK_HOLIDAY", "int", 1, "block time entry on holiday");
                $r++;
                $this->const[$r] = array("TIMESHEET_ADD_PUBLICHOLIDAY_TIME", "int", 1, "count the public holiday in total or not");
                $r++;
                $this->const[$r] = array("TIMESHEET_BLOCK_PUBLICHOLIDAY", "int", 1, "block time entry on public holiday");
                $r++;
                $this->const[$r] = array("TIMESHEET_OPEN_DAYS", "chaine", "_1111100", "normal day for time booking");
                $r++;
                $this->const[$r] = array("TIMESHEET_APPROVAL_BY_WEEK", "int", 0, "Approval by week instead of by user");
                $r++;
                $this->const[$r] = array("TIMESHEET_MAX_APPROVAL", "int", 5, "Max TS per Approval page");
                $r++;
                $this->const[$r] = array("TIMESHEET_APPROVAL_FLOWS", "chaine", "_00000", "Approval flows ");
                $r++;
                $this->const[$r] = array("TIMESHEET_INVOICE_METHOD", "int", 0, "Approval by week instead of by user");
                $r++;
                $this->const[$r] = array("TIMESHEET_INVOICE_TASKTIME", "chaine", "all", "set the default task to include in the invoice item");
                $r++;
                $this->const[$r] = array("TIMESHEET_INVOICE_TIMETYPE", "chaine", "days", "set the default task to include in the invoice item");
                $r++;
                $this->const[$r] = array("TIMESHEET_INVOICE_SERVICE", "int", 0, "set a default service for the invoice item");
                $r++;
                $this->const[$r] = array("TIMESHEET_INVOICE_SHOW_TASK", "int", 1, "Show task on the invoice item ");
                $r++;
                $this->const[$r] = array("TIMESHEET_INVOICE_SHOW_USER", "int", 1, "Show user on the invoice item ");
                $r++;
                $this->const[$r] = array("TIMESHEET_EVENT_MAX_DURATION", "int", 8, "max event duration");// hours or days
                $r++;
                $this->const[$r] = array("TIMESHEET_EVENT_DEFAULT_DURATION", "int", 2, "default event duration");// hours or days
                $r++;
                $this->const[$r] = array("TIMESHEET_EVENT_MIN_DURATION", "int", "0", "minimum time per chrono");// hours or days
                $r++;
                $this->const[$r] = array("TIMESHEET_EVENT_NOT_CREATE_TIMESPENT", "int", "0", "hide the sign box on pdf");// hours or days
                $r++;
                $this->const[$r] = array("TIMESHEET_PDF_HIDE_SIGNBOX", "int", "0", "hide the sign box on pdf");// hours or days
                $r++;
                $this->const[$r] = array("TIMESHEET_PDF_NOTEISOTASK", "int", 0, "save the timesheet verison");// hours or days
                $r++;
                $this->const[$r] = array("TIMESHEET_PDF_HIDE_NAME", "int", "0", "hide name in PDF");// hours or days
                $r++;
                $this->const[$r] = array("TIMESHEET_EXPORT_FORMAT", "chaine", "tsv", "export format xls ... ");// hours or days
                $r++;
                $this->const[$r] = array("TIMESHEET_EVAL_ADDLINE", "int", "0", "process add line vian an eval function running the invoice card page");// hours or days
                $r++;
                $this->const[$r] = array("TIMESHEET_ROUND", "int", "3", "round timespend display in day");// hours or days
                $r++;
                $this->const[$r] = array("TIMESHEET_SEARCHBOX", "int", "0", "enable search box in favourite");// hours or days
                $r++;
                $this->const[$r] = array("TIMESHEET_UNBLOCK_INVOICED", "int", "0", "unblock editing invoiced time");// hours or days
                $r++;
                $this->const[$r] = array("TIMESHEET_UNBLOCK_CLOSED", "int", "0", "unblock editing  closed day");// hours or days
                $r++;
                $this->const[$r] = array("MAIN_DISABLE_AJAX_COMBOX", "int", "0", "disable combo box");// hours or days
                $r++;
                $this->const[$r] = array("MAIN_DISABLE_AJAX_COMBOX", "int", "0", "disable combo box");// hours or days
                $r++;
                $this->const[$r] = array("TIMESHEET_OVERTIME_CHECK_WEEKS", "int", "30", "Number of week used for the overwork box");// hours or days
                $r++;
                $this->const[$r] = array("TIMESHEET_TIMESHEET_IMPORT_AGENDA", "int", "0", "Enable the import agenda button");// hours or days
                $r++;
                
                 //$this->const[2] = array("CONST3", "chaine", "valeur3", "Libelle3");
                // Array to add new pages in new tabs
                // Example: $this->tabs = array('objecttype:+tabname1:Title1:mylangfile@timesheet:$user->rights->timesheet->read:/timesheet/mynewtab1.php?id=__ID__',        // To add a new tab identified by code tabname1
        //                              'objecttype:+tabname2:Title2:mylangfile@timesheet:$user->rights->othermodule->read:/timesheet/mynewtab2.php?id=__ID__',        // To add another new tab identified by code tabname2
        //                              'objecttype:-tabname':NU:conditiontoremove);// To remove an existing tab identified by code tabname
                // where objecttype can be
                // 'thirdparty'       to add a tab in third party view
                // 'intervention'     to add a tab in intervention view
                // 'order_supplier'   to add a tab in supplier order view
                // 'invoice_supplier' to add a tab in supplier invoice view
                // 'invoice'          to add a tab in customer invoice view
                // 'order'            to add a tab in customer order view
                // 'product'          to add a tab in product view
                // 'stock'            to add a tab in stock view
                // 'propal'           to add a tab in propal view
                // 'member'           to add a tab in fundation member view
                // 'contract'         to add a tab in contract view
                // 'user'             to add a tab in user view
                // 'group'            to add a tab in group view
                // 'contact'          to add a tab in contact view
                // 'payment'                  to add a tab in payment view
                // 'payment_supplier' to add a tab in supplier payment view
                // 'categories_x'          to add a tab in category view(replace 'x' by type of category(0=product, 1=supplier, 2=customer, 3=member)
                // 'opensurveypoll'          to add a tab in opensurvey poll view
        $this->tabs = array();
		// Example:
		// $this->tabs[] = array('data' => 'objecttype:+tabname1:Title1:mylangfile@project_cost:$user->rights->project_cost->read:/project_cost/mynewtab1.php?id=__ID__');  					// To add a new tab identified by code tabname1
        $this->tabs[] = array('data' => 'project:+invoice:projectInvoice:timesheet@timesheet:$user->rights->facture->creer:/timesheet/TimesheetProjectInvoice.php?projectid=__ID__');  					// To add a new tab identified by code tabname1
        $this->tabs[] = array('data' => 'project:+report:projectReport:timesheet@timesheet:$user->rights->timesheet->report->projet||$user->rights->timesheet->report->admin:/timesheet/TimesheetReportProject.php?projectSelected=__ID__');  					// To add a new tab identified by code tabname1
        // Dictionaries
        if (! isset($conf->timesheet->enabled)) {
            $conf->timesheet=new stdClass();
            $conf->timesheet->enabled=0;
        }
                $this->dictionaries=array();
        /* Example:
        if (! isset($conf->timesheet->enabled)) $conf->timesheet->enabled=0;        // This is to avoid warnings
        $this->dictionaries=array(
            'langs' => 'mylangfile@timesheet',
            'tabname' => array(MAIN_DB_PREFIX."table1", MAIN_DB_PREFIX."table2", MAIN_DB_PREFIX."table3"),                // List of tables we want to see into dictonnary editor
            'tablib' => array("Table1", "Table2", "Table3"),                                                                                                        // Label of tables
            'tabsql' => array('SELECT f.rowid as rowid, f.code, f.label, f.active FROM '.MAIN_DB_PREFIX.'table1 as f', 'SELECT f.rowid as rowid, f.code, f.label, f.active FROM '.MAIN_DB_PREFIX.'table2 as f', 'SELECT f.rowid as rowid, f.code, f.label, f.active FROM '.MAIN_DB_PREFIX.'table3 as f'),        // Request to select fields
            'tabsqlsort' => array("label ASC", "label ASC", "label ASC"),                                                                                                                                                                        // Sort order
            'tabfield' => array("code, label", "code, label", "code, label"),                                                                                                                                                                        // List of fields(result of select to show dictionary)
            'tabfieldvalue' => array("code, label", "code, label", "code, label"),                                                                                                                                                                // List of fields(list of fields to edit a record)
            'tabfieldinsert' => array("code, label", "code, label", "code, label"),                                                                                                                                                        // List of fields(list of fields for insert)
            'tabrowid' => array("rowid", "rowid", "rowid"),                                                                                                                                                                                                        // Name of columns with primary key(try to always name it 'rowid')
            'tabcond' => array($conf->timesheet->enabled, $conf->timesheet->enabled, $conf->timesheet->enabled)                                                                                                // Condition to show each dictionary
      );
        */
        // Boxes
                // Add here list of php file(s) stored in core/boxes that contains class to show a box.
        $this->boxes = array(
                0 => array(
                        'file' => 'box_approval.php@timesheet',
                        'note' => 'timesheetApproval',
                        'enabledbydefaulton' => 'Home'),
                1 => array(
                        'file' => 'box_time.php@timesheet',
                        'note' => 'timesheet',
                        'enabledbydefaulton' => 'Home')
        ); // List of boxes
                // Example:
                //$this->boxes=array(array(0 => array('file' => 'myboxa.php', 'note' => '', 'enabledbydefaulton' => 'Home'), 1 => array('file' => 'myboxb.php', 'note' => ''), 2 => array('file' => 'myboxc.php', 'note' => '')););
                // Permissions
                $this->rights = array();                // Permission array used by this module
                $r = 0;
                $this->rights[$r][0] = 86100200;                                // Permission id(must not be already used)
                $this->rights[$r][1] = 'TimesheetUser';        // Permission label
                $this->rights[$r][3] = 0;                                        // Permission by default for new user(0/1)
                $this->rights[$r][4] = 'timesheet';
                $this->rights[$r][5] = 'user';                                  // In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)
                //$this->rights[$r][5] = 'team';                                // In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)
                $r ++;
                $this->rights[$r][0] = 86100201;                                // Permission id(must not be already used)
                $this->rights[$r][1] = 'TimesheetAdmin';        // Permission label
                $this->rights[$r][3] = 0;                                        // Permission by default for new user(0/1)
                $this->rights[$r][4] = 'timesheet';
                $this->rights[$r][5] = 'admin';                                  // In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)
                //$this->rights[$r][5] = 'team';                                // In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)
				$r++;                // Add here list of permission defined by an id, a label, a boolean and two constant strings.
				$this->rights[$r][0] = 86100203;                                // Permission id(must not be already used)
				$this->rights[$r][1] = 'ExportRead';        // Permission label
				$this->rights[$r][3] = 0;                                        // Permission by default for new user(0/1)
				$this->rights[$r][4] = 'read';                                // In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)
				//$this->rights[$r][5] = 'admin';                                // In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)
				$r++;
        		//$r = 0;
                $this->rights[$r][0] = 86100211;                                // Permission id(must not be already used)
                $this->rights[$r][1] = 'ApprovalTeam';        // Permission label
                $this->rights[$r][3] = 0;                                        // Permission by default for new user(0/1)
                $this->rights[$r][4] = 'approval';                                // In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)
                $this->rights[$r][5] = 'team';                                // In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)
                $r++;
                $this->rights[$r][0] = 86100212;                                // Permission id(must not be already used)
                $this->rights[$r][1] = 'ApprovalAdmin';        // Permission label
                $this->rights[$r][3] = 0;                                        // Permission by default for new user(0/1)
                $this->rights[$r][4] = 'approval';                                // In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)
                $this->rights[$r][5] = 'admin';                                // In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)
                $r++;
                $this->rights[$r][0] = 86100213;                                // Permission id(must not be already used)
                $this->rights[$r][1] = 'ApprovalOther';        // Permission label
                $this->rights[$r][3] = 0;                                        // Permission by default for new user(0/1)
                $this->rights[$r][4] = 'approval';                                // In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)
                $this->rights[$r][5] = 'other';
                $r++;                // Add here list of permission defined by an id, a label, a boolean and two constant strings.
                                // Add here list of permission defined by an id, a label, a boolean and two constant strings.
                $this->rights[$r][0] = 86100240;                                // Permission id(must not be already used)
                $this->rights[$r][1] = 'ReportUser';        // Permission label
                $this->rights[$r][3] = 0;                                        // Permission by default for new user(0/1)
                $this->rights[$r][4] = 'report';                                // In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)
                $this->rights[$r][5] = 'user';
                $r++;
                $this->rights[$r][0] = 86100241;                                // Permission id(must not be already used)
                $this->rights[$r][1] = 'ReportProject';        // Permission label
                $this->rights[$r][3] = 0;                                        // Permission by default for new user(0/1)
                $this->rights[$r][4] = 'report';                                // In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)
                $this->rights[$r][5] = 'project';
                $r++;                // Add here list of permission defined by an id, a label, a boolean and two constant strings.
                $this->rights[$r][0] = 86100242;                                // Permission id(must not be already used)
                $this->rights[$r][1] = 'ReportAdmin';        // Permission label
                $this->rights[$r][3] = 0;                                        // Permission by default for new user(0/1)
                $this->rights[$r][4] = 'report';                                // In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)
                $this->rights[$r][5] = 'admin';                                // In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)
                $r++;                // Add here list of permission defined by an id, a label, a boolean and two constant strings.
                $this->rights[$r][0] = 86100250;                                // Permission id(must not be already used)
                $this->rights[$r][1] = 'AttendanceUser';        // Permission label
                $this->rights[$r][3] = 0;                                        // Permission by default for new user(0/1)
                $this->rights[$r][4] = 'attendance';                                // In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)
                $this->rights[$r][5] = 'user';                                // In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)
                $r++;
                $this->rights[$r][0] = 86100251;                                // Permission id(must not be already used)
                $this->rights[$r][1] = 'AttendanceAdmin';        // Permission label
                $this->rights[$r][3] = 0;                                        // Permission by default for new user(0/1)
                $this->rights[$r][4] = 'attendance';                                // In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)
                $this->rights[$r][5] = 'admin';                                // In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)
                $r++;
// Example:
                // $this->rights[$r][0] = 2000;                                // Permission id(must not be already used)
                // $this->rights[$r][1] = 'Permision label';        // Permission label
                // $this->rights[$r][3] = 1;                                        // Permission by default for new user(0/1)
                // $this->rights[$r][4] = 'level1';                                // In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)
                // $this->rights[$r][5] = 'level2';                                // In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)
                // $r++;
                // Main menu entries
                $this->menu = array();                        // List of menus to add
                $r = 0;
                // Add here entries to declare new menus
                //
                // Example to declare a new Top Menu entry and its Left menu entry:
                $this->menu[$r]=array('fk_menu' => 0, // Put 0 if this is a top menu
                        'type' => 'top',                                        // This is a Top menu entry
                        'titre' => 'Timesheet',
                        'mainmenu' => 'timesheet',
                        'leftmenu' => 'timesheet',
                        'url' => '/timesheet/Timesheet.php',
                        'langs' => 'timesheet@timesheet',                // Lang file to use(without .lang) by module. File must be in langs/code_CODE/ directory.
                        'position' => 100,
                        'enabled' => '$conf->timesheet->enabled && !($user->rights->timesheet->attendance->user && $conf->global->TIMESHEET_ATTENDANCE==1)',        // Define condition to show or hide menu entry. Use '$conf->timesheet->enabled' if entry must be visible if module is enabled.
                        'perms' => '$user->rights->timesheet->timesheet->user || $user->rights->timesheet->timesheet->admin',                                        // Use 'perms' => '$user->rights->timesheet->level1->level2' if you want your menu with a permission rules
                        'target' => '',
                        'user' => 2);                                                // 0=Menu for internal users, 1=external users, 2=both
                $r++;
                $this->menu[$r]=array('fk_menu' => 0,                    // Use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx, fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
                        'type' => 'top',                                // This is a Left menu entry
                        'titre' => 'Attendance',
                        'mainmenu' => 'timesheet',
                        'leftmenu' => 'attendance',
                        'url' => '/timesheet/AttendanceClock.php',
                        'langs' => 'timesheet@timesheet',                // Lang file to use(without .lang) by module. File must be in langs/code_CODE/ directory.
                        'position' => 100,
                        'enabled' => '$conf->timesheet->enabled && $user->rights->timesheet->attendance->user && $conf->global->TIMESHEET_ATTENDANCE==1',
                        'perms' => '$user->rights->timesheet->attendance->user || $user->rights->timesheet->attendance->admin',                                        // Use 'perms' => '$user->rights->timesheet->level1->level2' if you want your menu with a permission rules
                        'target' => '',
                        'user' => 2);
                $r++;
                $this->menu[$r]=array('fk_menu' => 'fk_mainmenu=timesheet', // Put 0 if this is a top menu
                        'type' => 'left',                                        // This is a Top menu entry
                        'titre' => 'Timesheet',
                        'mainmenu' => 'timesheet',
                        'leftmenu' => 'timesheet',
                        'url' => '/timesheet/Timesheet.php?#',
                        'langs' => 'timesheet@timesheet',                // Lang file to use(without .lang) by module. File must be in langs/code_CODE/ directory.
                        'position' => 100,
                        'enabled' => '$conf->timesheet->enabled',        // Define condition to show or hide menu entry. Use '$conf->timesheet->enabled' if entry must be visible if module is enabled.
                        'perms' => '$user->rights->timesheet->timesheet->user || $user->rights->timesheet->timesheet->admin',                                        // Use 'perms' => '$user->rights->timesheet->level1->level2' if you want your menu with a permission rules
                        'target' => '',
                        'user' => 2);                                                // 0=Menu for internal users, 1=external users, 2=both
                $r++;
                $this->menu[$r]=array('fk_menu' => 'fk_mainmenu=timesheet',                    // Use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx, fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
                        'type' => 'left',                                        // This is a Left menu entry
                        'titre' => 'Attendance',
                        'mainmenu' => 'timesheet',
                        'leftmenu' => 'attendance',
                        'url' => '/timesheet/AttendanceClock.php?#',
                        'langs' => 'timesheet@timesheet',                // Lang file to use(without .lang) by module. File must be in langs/code_CODE/ directory.
                        'position' => 200,
                        'enabled' => '$conf->global->TIMESHEET_ATTENDANCE==1',
                        'perms' => '$user->rights->timesheet->attendance->user || $user->rights->timesheet->attendance->admin',                                        // Use 'perms' => '$user->rights->timesheet->level1->level2' if you want your menu with a permission rules
                        'target' => '',
                        'user' => 2);
                $r++;
                $this->menu[$r]=array('fk_menu' => 'fk_mainmenu=timesheet,fk_leftmenu=attendance',                    // Use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx, fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
                        'type' => 'left',                                        // This is a Left menu entry
                        'titre' => 'AttendanceAdmin',
                        'mainmenu' => 'timesheet',
                        'leftmenu' => 'Attendance',
                        'url' => '/timesheet/AttendanceEventAdmin.php',
                        'langs' => 'timesheet@timesheet',                // Lang file to use(without .lang) by module. File must be in langs/code_CODE/ directory.
                        'position' => 210,
                        'enabled' => '$conf->global->TIMESHEET_ATTENDANCE',
                        'perms' => '$user->rights->timesheet->attendance->admin',                                        // Use 'perms' => '$user->rights->timesheet->level1->level2' if you want your menu with a permission rules
                        'target' => '',
                        'user' => 2);
                $r++;
                $this->menu[$r]=array('fk_menu' => 'fk_mainmenu=timesheet,fk_leftmenu=timesheet',                    // Use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx, fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
                        'type' => 'left',                                        // This is a Left menu entry
                        'titre' => 'userReport',
                        'mainmenu' => 'timesheet',
                        'leftmenu' => 'Timesheet',
                        'url' => '/timesheet/TimesheetReportUser.php',
                        'langs' => 'timesheet@timesheet',                // Lang file to use(without .lang) by module. File must be in langs/code_CODE/ directory.
                        'position' => 130,
                        'enabled' => '$conf->timesheet->enabled', // Define condition to show or hide menu entry. Use '$conf->timesheet->enabled' if entry must be visible if module is enabled. Use '$leftmenu == \'system\'' to show if leftmenu system is selected.
                        'perms' => '$user->rights->timesheet->report->admin || $user->rights->timesheet->report->user',                                        // Use 'perms' => '$user->rights->timesheet->level1->level2' if you want your menu with a permission rules
                        'target' => '',
                        'user' => 2);
                $r++;

                $this->menu[$r]=array('fk_menu' => 'fk_mainmenu=timesheet,fk_leftmenu=timesheet',                    // Use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx, fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
                        'type' => 'left',                                        // This is a Left menu entry
                        'titre' => 'Timesheetwhitelist',
                        'mainmenu' => 'timesheet',
                        'leftmenu' => 'Timesheet',
                        'url' => '/timesheet/TimesheetFavouriteAdmin.php',
                        'langs' => 'timesheet@timesheet',                // Lang file to use(without .lang) by module. File must be in langs/code_CODE/ directory.
                        'position' => 110,
                        'enabled' => '$conf->global->TIMESHEET_WHITELIST == 1', // Define condition to show or hide menu entry. Use '$conf->timesheet->enabled' if entry must be visible if module is enabled. Use '$leftmenu == \'system\'' to show if leftmenu system is selected.
                        'perms' => '$user->rights->timesheet->attendance->user || $user->rights->timesheet->attendance->admin || $user->rights->timesheet->timesheet->user || $user->rights->timesheet->timesheet->admin',                                        // Use 'perms' => '$user->rights->timesheet->level1->level2' if you want your menu with a permission rules
                        'target' => '',
                        'user' => 2);
                  $r++;
                $this->menu[$r]=array('fk_menu' => 'fk_mainmenu=project,fk_leftmenu=projects',                    // Use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx, fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
                        'type' => 'left',                                        // This is a Left menu entry
                        'titre' => 'projectReport',
                        'mainmenu' => 'project',
                        'leftmenu' => 'projectReport',
                        'url' => '/timesheet/TimesheetReportProject.php?hidetab=1',
                        'langs' => 'timesheet@timesheet',                // Lang file to use(without .lang) by module. File must be in langs/code_CODE/ directory.
                        'position' => 120,
                        'enabled' => '$conf->timesheet->enabled', // Define condition to show or hide menu entry. Use '$conf->timesheet->enabled' if entry must be visible if module is enabled. Use '$leftmenu == \'system\'' to show if leftmenu system is selected.
                        'perms' => '$user->rights->timesheet->report->admin || $user->rights->timesheet->report->project',                                        // Use 'perms' => '$user->rights->timesheet->level1->level2' if you want your menu with a permission rules
                        'target' => '',
                        'user' => 2);
                $r++;
               /*   $this->menu[$r]=array('fk_menu' => 'fk_mainmenu=project,fk_leftmenu=projects',                    // Use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx, fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
                        'type' => 'left',                                        // This is a Left menu entry
                        'titre' => 'projectInvoice',
                        'mainmenu' => 'project',
                        'leftmenu' => 'projectInvoice',
                        'url' => '/timesheet/TimesheetProjectInvoice.php',
                        'langs' => 'timesheet@timesheet',                // Lang file to use(without .lang) by module. File must be in langs/code_CODE/ directory.
                        'position' => 121,
                        'enabled' => '$conf->timesheet->enabled', // Define condition to show or hide menu entry. Use '$conf->timesheet->enabled' if entry must be visible if module is enabled. Use '$leftmenu == \'system\'' to show if leftmenu system is selected.
                        'perms' => '$user->rights->facture->creer',                                        // Use 'perms' => '$user->rights->timesheet->level1->level2' if you want your menu with a permission rules
                        'target' => '',
                        'user' => 2);*/
                $r++;
                $this->menu[$r]=array('fk_menu' => 'fk_mainmenu=timesheet',                    // Use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx, fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
                        'type' => 'left',                                        // This is a Left menu entry
                        'titre' => 'Timesheetapproval',
                        'mainmenu' => 'timesheet',
                        'leftmenu' => 'timesheetapproval',
                        'url' => '/timesheet/TimesheetTeamApproval.php',
                        'langs' => 'timesheet@timesheet',                // Lang file to use(without .lang) by module. File must be in langs/code_CODE/ directory.
                        'position' => 300,
                        'enabled' => '$conf->global->TIMESHEET_APPROVAL_FLOWS != "_00000"', // Define condition to show or hide menu entry. Use '$conf->timesheet->enabled' if entry must be visible if module is enabled. Use '$leftmenu == \'system\'' to show if leftmenu system is selected.
                        'perms' => '$user->rights->timesheet->approval->team || $user->rights->timesheet->approval->admin',                                        // Use 'perms' => '$user->rights->timesheet->level1->level2' if you want your menu with a permission rules
                        'target' => '',
                        'user' => 2);
                $r++;
                $this->menu[$r]=array('fk_menu' => 'fk_mainmenu=timesheet,fk_leftmenu=timesheetapproval',                    // Use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx, fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
                        'type' => 'left',                                        // This is a Left menu entry
                        'titre' => 'Adminapproval',
                        'mainmenu' => 'timesheet',
                        'leftmenu' => 'timesheetapproval',
                        'url' => '/timesheet/TimesheetUserTasksAdmin.php?view=list&sortfield=t.date_start&sortorder=desc',
                        'langs' => 'timesheet@timesheet',                // Lang file to use(without .lang) by module. File must be in langs/code_CODE/ directory.
                        'position' => 310,
                        'enabled' => '$conf->global->TIMESHEET_APPROVAL_FLOWS != "_00000"', // Define condition to show or hide menu entry. Use '$conf->timesheet->enabled' if entry must be visible if module is enabled. Use '$leftmenu == \'system\'' to show if leftmenu system is selected.
                        'perms' => '$user->rights->timesheet->approval->admin',                                        // Use 'perms' => '$user->rights->timesheet->level1->level2' if you want your menu with a permission rules
                        'target' => '',
                        'user' => 2);
                $r++;

// impoort
/*                $r++;
                $this->import_code[$r]=$this->rights_class.'_'.$r;
                $this->import_label[$r]="ImportDataset_Kimai";        // Translation key
                $this->import_icon[$r]='project';
                $this->import_entities_array[$r]=array('pt.fk_user' => 'user');                // We define here only fields that use another icon that the one defined into import_icon
                $this->import_tables_array[$r]=array('ptt' => MAIN_DB_PREFIX.'project_task_time');
                $this->import_fields_array[$r]=array('ptt.fk_task' => "ThirdPartyName*", 'ptt.fk_user' => "User*");
                $this->import_convertvalue_array[$r]=array(
                                'ptt.fk_task' => array('rule' => 'fetchidfromref', 'classfile' => '/timesheet/class/timesheet.class.php', 'class' => 'Timesheet', 'method' => 'fetch', 'element' => 'ThirdParty'),
                                'sr.fk_user' => array('rule' => 'fetchidfromref', 'classfile' => '/user/class/user.class.php', 'class' => 'User', 'method' => 'fetch', 'element' => 'User')
              );
                $this->import_examplevalues_array[$r]=array('sr.fk_soc' => "MyBigCompany", 'sr.fk_user' => "login");*/
                // Exports
                //$r = 1;
                // Example:
                // $this->export_code[$r]=$this->rights_class.'_'.$r;
                // $this->export_label[$r]='CustomersInvoicesAndInvoiceLines';        // Translation key(used only if key ExportDataset_xxx_z not found)
        }
        /**
         *                Function called when module is enabled.
         *                The init function add constants, boxes, permissions and menus(defined in constructor) into Dolibarr database.
         *                It also creates data directories
         *
         *      @param      string        $options    Options when enabling module('', 'noboxes')
         *      @return     int              1 if OK, 0 if KO
         */
        public function init($options = '')
        {
            global $db, $conf;
            $result = $this->_load_tables('/timesheet/sql/');
            $sql = array();
            $sql[0] = 'DELETE FROM '.MAIN_DB_PREFIX.'project_task_timesheet';
            $sql[0].= ' WHERE status IN (1, 5)';//'DRAFT', 'REJECTED'
            if ($db->type=='pgsql') {
                $sql[1] ="INSERT INTO ".MAIN_DB_PREFIX."document_model(nom, type, entity) VALUES('rat', 'timesheetReport', ".$conf->entity.") ON CONFLICT(nom, type, entity) DO NOTHING;";
                $sql[2] ="INSERT INTO ".MAIN_DB_PREFIX."c_type_contact(rowid, element, source, code, libelle, active ) values (8210160, 'project',  'internal', 'PROJECTBILLING', 'Responsable Facturation Projet', 1) ON CONFLICT(element, source, code) DO NOTHING;";
            }else {
                $sql[1] ="INSERT IGNORE INTO ".MAIN_DB_PREFIX."document_model(nom, type, entity) VALUES('rat', 'timesheetReport', ".$conf->entity.");";
                $sql[2] ="INSERT IGNORE INTO ".MAIN_DB_PREFIX."c_type_contact(rowid, element, source, code, libelle, active ) values (8210160, 'project',  'internal', 'PROJECTBILLING', 'Responsable Facturation Projet', 1);";
            }
            dolibarr_set_const($db, "TIMESHEET_VERSION", $this->version, 'chaine', 0, '', $conf->entity);
            include_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
            $extrafields = new ExtraFields($this->db);
            // add the "Default server" select list to the user
            $extrafields->addExtraField('fk_service', "DefaultService", 'sellist', 1, '', 'user', 0, 0, '', array('options' => array("product:ref|label:rowid::tosell='1' AND fk_product_type='1'" => 'N')), 1, 1, 3, 0, '', 0, 'timesheet@ptimesheet', '$conf->timesheet->enabled');
            // add the "Default server" select list to the task
            $extrafields->addExtraField('fk_service', "DefaultService", 'sellist', 1, '', 'projet_task', 0, 0, '', array('options' => array("product:ref|label:rowid::tosell='1' AND fk_product_type='1'" => 'N')), 1, 1, 3, 0, '', 0, 'timesheet@ptimesheet', '$conf->timesheet->enabled');
            // allow ext id of 32 char
           // $extrafields->addExtraField('external_id', "ExternalId", 'varchar', 100, 32, 'user', 1, 0, '', '', 1, '$user->rights->timesheet->AttendanceAdmin', 3, 'specify the id of the external system', '', 0, 'timesheet@ptimesheet', '$conf->global->ATTENDANCE_EXT_SYSTEM');
            // add the "invoicable" bool to the task
            $extrafields->addExtraField('invoiceable', "Invoiceable", 'boolean', 1, '', 'projet_task', 0, 0, '', '', 1, 1, 1, 0, '', 0, 'timesheet@timesheet', '$conf->timesheet->enabled');
            return $this->_init($sql, $options);
        }
        /**
         *                Function called when module is disabled.
         *      Remove from database constants, boxes and permissions from Dolibarr database.
         *                Data directories are not deleted
         *
     *      @param      string        $options    Options when enabling module('', 'noboxes')
         *      @return     int              1 if OK, 0 if KO
         */
        public function remove($options = '')
        {

                $sql = array();
                return $this->_remove($sql, $options);
        }
}
