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
 * External settings page for enrol_airtime
 *
 * @package    enrol_airtime
 * @copyright  2021 Moodle US
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use enrol_airtime\enrol_exclusions;

require_once('../../config.php');
require_once($CFG->dirroot.'/course/lib.php');
require_once($CFG->dirroot.'/lib/formslib.php');

$userid = optional_param('userid', null, PARAM_INT);

$context = context_system::instance();

$PAGE->set_pagelayout('admin');
$PAGE->set_context($context);

$PAGE->set_url('/enrol/airtime/enrol_exclusions.php');

$returnurl = new moodle_url('/enrol/airtime/enrol_exclusions.php', [
    'userid' => $userid
]);

$exclusions = \enrol_airtime\external::get_user_exclusions($userid);

$formdata = new stdClass;
$formdata->userid = $userid;
$formdata->excludedcourses = [];

if (!empty($exclusions)) {
    $formdata->excludedcourses = $exclusions;
}

// First create the form.
$args = array(
    'userid' => $formdata->userid,
    'excludedcourses' => $formdata->excludedcourses,
);

$editform = new \enrol_airtime\form\enrol_exclusions(null, $args);

if ($editform->is_cancelled()) {
    // The form has been cancelled, take them back to what ever the return to is.
    redirect($returnurl);
} else if ($editform->no_submit_button_pressed()) {
    $data = $editform->get_submitted_data();
    $userid = empty($data->userid) ? 0 : $data->userid;
    $redirecturl = new moodle_url('/enrol/airtime/enrol_exclusions.php', [
        'userid' => $userid
    ]);
    redirect($redirecturl);
} else if ($data = $editform->get_data()) {
    $rec = new stdClass();
    $rec->userid = $data->userid;
    foreach ($data->excludedcourses as $excludedcourse) {
        $rec->courseid = $excludedcourse;
        if (!$existing = $DB->get_record('enrol_airtime_exclusions', ['userid' => $rec->userid, 'courseid' => $excludedcourse])) {
            $rec->id = $DB->insert_record('enrol_airtime_exclusions', $rec);
        }
    }

    $currentexclusions = \enrol_airtime\external::get_user_exclusions($userid);
    foreach ($currentexclusions as $currentexclusion) {
        if (!in_array($currentexclusion, $data->excludedcourses)) {
            $DB->delete_records('enrol_airtime_exclusions', ['userid' => $userid, 'courseid' => $currentexclusion]);
        }
    }

    redirect($returnurl);
}

$site = get_site();

$title = get_string('enrol_exclusions', 'enrol_airtime');

$PAGE->set_title($title);
$PAGE->set_heading($title);

echo $OUTPUT->header();
echo $OUTPUT->heading($title);
$editform->set_data($formdata);
$editform->display();
echo $OUTPUT->footer();