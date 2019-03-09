<?php
/* Copyright (C) 2018 delcroip <patrick@pmpd.eu>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */
/**
 *      \file       test/phpunit/TimesheetUserTasksTest.php
 *                \ingroup    test
 *      \brief      PHPUnit test
 *                \remarks        To run this script as CLI:  phpunit filename.php
 */
global $conf,$user,$langs,$db;
//define('TEST_DB_FORCE_TYPE','mysql');        // This is to force using mysql driver
//require_once 'PHPUnit/Autoload.php';
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/timesheet/class/TimesheetUserTasks.class.php';
if(empty($user->id)) {
        print "Load permissions for admin user nb 1\n";
        $user->fetch(1);
        $user->getrights();
}
$conf->global->MAIN_DISABLE_ALL_MAILS=1;
/**
 * Class for PHPUnit tests
 *
 * @backupGlobals disabled
 * @backupStaticAttributes enabled
 * @remarks        backupGlobals must be disabled to have db,conf,user and lang not erased.
 */
class TimesheetTest extends PHPUnit_Framework_Testcase
{
        protected $savconf;
        protected $savuser;
        protected $savlangs;
        protected $savdb;
        /**
         * Constructor
         * We save global variables into local variables
         *
         * @return TimesheetUserTasksTest
         */
        public function __construct()
        {
                parent::__construct();
                //$this->sharedFixture
                global $conf,$user,$langs,$db;
                $this->savconf=$conf;
                $this->savuser=$user;
                $this->savlangs=$langs;
                $this->savdb=$db;
                print __METHOD__." db->type=".$db->type." user->id=".$user->id;
                //print " - db ".$db->db;
                print "\n";
        }
        // Static methods
    public static function setUpBeforeClass()
    {
        global $conf,$user,$langs,$db;
                $db->begin();        // This is to have all actions inside a transaction even if test launched without suite.
        print __METHOD__."\n";
    }
    // tear down after class
    public static function tearDownAfterClass()
    {
        global $conf,$user,$langs,$db;
                $db->rollback();
                print __METHOD__."\n";
    }
        /**
         * Init phpunit tests
         *
         * @return        void
         */
    protected function setUp()
    {
        global $conf,$user,$langs,$db;
                $conf=$this->savconf;
                $user=$this->savuser;
                $langs=$this->savlangs;
                $db=$this->savdb;
                print __METHOD__."\n";
                //print $db->getVersion()."\n";
    }
        /**
         * End phpunit tests
         *
         * @return        void
         */
    protected function tearDown()
    {
        print __METHOD__."\n";
    }
    /**
     * testTimesheetUserTasksCreate
     *
     * @return        void
     */
    public function testTimesheetUserTasksCreate()
    {
        global $conf,$user,$langs,$db;
                $conf=$this->savconf;
                $user=$this->savuser;
                $langs=$this->savlangs;
                $db=$this->savdb;
                $localobject=new TimesheetUserTasks($this->savdb, 1);//FIXEME
        $localobject->initAsSpecimen();
        $localobject->date_start= mktime();
        $result=$localobject->create($user);
        $this->assertLessThan($result, 0);
        print __METHOD__." result=".$result."\n";
        return $result;
    }
    /**
     * testTimesheetUserTasksFetch
     *
     * @param        int                $id                Id of object
     * @return        void
     *
     * @depends        testTimesheetUserTasksCreate
     * The depends says test is run only if previous is ok
     */
    public function testTimesheetUserTasksFetch($id)
    {
        global $conf,$user,$langs,$db;
                $conf=$this->savconf;
                $user=$this->savuser;
                $langs=$this->savlangs;
                $db=$this->savdb;
                $localobject=new TimesheetUserTasks($this->savdb, 1);
        $result=$localobject->fetch($id);
        $this->assertLessThan($result, 0);
        print __METHOD__." id=".$id." result=".$result."\n";
        return $localobject;
    }
    /**
     * testTimesheetUserTasksValid
     *
     * @param        TimesheetUserTasks        $localobject        TimesheetUserTasks
     * @return        TimesheetUserTasks
     *
     * @depends        testTimesheetUserTasksFetch
     * The depends says test is run only if previous is ok
     */
    public function testTimesheetUserTasksValid($localobject)
    {
        global $conf,$user,$langs,$db;
                $conf=$this->savconf;
                $user=$this->savuser;
                $langs=$this->savlangs;
                $db=$this->savdb;
        $result=$localobject->setValid($user);
        print __METHOD__." id=".$localobject->id." result=".$result."\n";
        $this->assertLessThan($result, 0);
        return $localobject;
    }
        /**
     * testTimesheetUserTasksClose
     *
     * @param        TimesheetUserTasks        $localobject        TimesheetUserTasks
     * @return        int
     *
     * @depends testTimesheetUserTasksValid
     * The depends says test is run only if previous is ok
     */
    public function testTimesheetUserTasksOther($localobject)
    {
        global $conf,$user,$langs,$db;
        $conf=$this->savconf;
        $user=$this->savuser;
        $langs=$this->savlangs;
        $db=$this->savdb;
        //$result=$localobject->setClose($user);
        //print __METHOD__." id=".$localobject->id." result=".$result."\n";
        //$this->assertLessThan($result, 0);
        return $localobject->id;
    }
    /**
     * testTimesheetUserTasksDelete
     *
     * @param        int                $id                Id of project
     * @return        void
     *
     * @depends        testTimesheetUserTasksClose
     * The depends says test is run only if previous is ok
     */
    public function testTimesheetUserTasksDelete($id)
    {
        global $conf,$user,$langs,$db;
                $conf=$this->savconf;
                $user=$this->savuser;
                $langs=$this->savlangs;
                $db=$this->savdb;
                $localobject=new TimesheetUserTasks($this->savdb, 1);
        $result=$localobject->fetch($id);
                $result=$localobject->delete($user);
                print __METHOD__." id=".$id." result=".$result."\n";
        $this->assertLessThan($result, 0);
        return $result;
    }
}
