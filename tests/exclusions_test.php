<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Contains tests for the exclusions functionality
 *
 * @package   enrol_airtime
 * @copyright 2021 Moodle US
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot.'/cohort/lib.php');
require_once($CFG->dirroot.'/group/lib.php');
require_once($CFG->dirroot.'/enrol/airtime/lib.php');

/**
 * Contains tests for the exclusions functionality
 *
 * @package   enrol_airtime
 * @copyright 2021 Moodle US
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class enrol_airtime_exclusions_testcase extends advanced_testcase {

    protected function enable_plugin() {
        $enabled = enrol_get_plugins(true);
        $enabled['airtime'] = true;
        $enabled = array_keys($enabled);
        set_config('enrol_plugins_enabled', implode(',', $enabled));
    }

    protected function disable_plugin() {
        $enabled = enrol_get_plugins(true);
        unset($enabled['airtime']);
        $enabled = array_keys($enabled);
        set_config('enrol_plugins_enabled', implode(',', $enabled));
    }

    public function test_course_exclusions() {
        global $DB;

        $this->resetAfterTest();

        $trace = new null_progress_trace();

        // Setup a few courses and categories.

        $cohortplugin = enrol_get_plugin('airtime');
        $manualplugin = enrol_get_plugin('manual');

        $studentrole = $DB->get_record('role', array('shortname'=>'student'));
        $this->assertNotEmpty($studentrole);
        $teacherrole = $DB->get_record('role', array('shortname'=>'teacher'));
        $this->assertNotEmpty($teacherrole);
        $managerrole = $DB->get_record('role', array('shortname'=>'manager'));
        $this->assertNotEmpty($managerrole);

        $cat1 = $this->getDataGenerator()->create_category();

        $course1 = $this->getDataGenerator()->create_course(array('category'=>$cat1->id));
        $course2 = $this->getDataGenerator()->create_course(array('category'=>$cat1->id));
        $maninstance1 = $DB->get_record('enrol', array('courseid'=>$course1->id, 'enrol'=>'manual'), '*', MUST_EXIST);

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();

        $cohort1 = $this->getDataGenerator()->create_cohort();

        $manualplugin->enrol_user($maninstance1, $user2->id, $teacherrole->id);
        $manualplugin->enrol_user($maninstance1, $user3->id, $managerrole->id);

        $this->assertEquals(2, $DB->count_records('role_assignments', array()));
        $this->assertEquals(2, $DB->count_records('user_enrolments', array()));


        $id = $cohortplugin->add_instance($course1, array('customint1'=>$cohort1->id, 'roleid'=>$studentrole->id));
        $cohortinstance1 = $DB->get_record('enrol', array('id'=>$id));

        $id = $cohortplugin->add_instance($course2, array('customint1'=>$cohort1->id, 'roleid'=>$studentrole->id));
        $cohortinstance2 = $DB->get_record('enrol', array('id'=>$id));

        $this->enable_plugin();
        // Test events with no exclusions
        cohort_add_member($cohort1->id, $user1->id);

        $this->assertEquals(4, $DB->count_records('role_assignments', array()));
        $this->assertEquals(4, $DB->count_records('user_enrolments', array()));

        cohort_remove_member($cohort1->id, $user1->id);

        $this->assertEquals(2, $DB->count_records('role_assignments', array()));
        $this->assertEquals(2, $DB->count_records('user_enrolments', array()));

        // Test sync with no exclusions
        $this->disable_plugin(); // Prevents event sync.

        cohort_add_member($cohort1->id, $user1->id);
        $this->enable_plugin();
        enrol_airtime_sync($trace, null);
        $this->assertEquals(4, $DB->count_records('role_assignments', array()));
        $this->assertEquals(4, $DB->count_records('user_enrolments', array()));

        $this->disable_plugin();
        cohort_remove_member($cohort1->id, $user1->id);
        $this->enable_plugin();
        enrol_airtime_sync($trace, null);
        $this->assertEquals(2, $DB->count_records('role_assignments', array()));
        $this->assertEquals(2, $DB->count_records('user_enrolments', array()));

        // Test events with exclusion
        \enrol_airtime\external::add_exclusion($user1->id, $course1->id);
        cohort_add_member($cohort1->id, $user1->id);

        $this->assertEquals(3, $DB->count_records('role_assignments', array()));
        $this->assertEquals(3, $DB->count_records('user_enrolments', array()));

        cohort_remove_member($cohort1->id, $user1->id);

        $this->assertEquals(2, $DB->count_records('role_assignments', array()));
        $this->assertEquals(2, $DB->count_records('user_enrolments', array()));

        // Test sync with no exclusions
        $this->disable_plugin(); // Prevents event sync.

        cohort_add_member($cohort1->id, $user1->id);
        $this->enable_plugin();
        enrol_airtime_sync($trace, null);
        $this->assertEquals(3, $DB->count_records('role_assignments', array()));
        $this->assertEquals(3, $DB->count_records('user_enrolments', array()));

        $this->disable_plugin();
        cohort_remove_member($cohort1->id, $user1->id);
        $this->enable_plugin();
        enrol_airtime_sync($trace, null);
        $this->assertEquals(2, $DB->count_records('role_assignments', array()));
        $this->assertEquals(2, $DB->count_records('user_enrolments', array()));
    }
}
