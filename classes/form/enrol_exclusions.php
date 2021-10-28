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
 * Edit form class for enrol_airtime settings
 *
 * @package    enrol_airtime
 * @copyright  2021 Moodle US
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace enrol_airtime\form;

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir.'/formslib.php');

/**
 * The form for handling editing a course.
 */
class enrol_exclusions extends \moodleform {
    protected $course;
    protected $context;

    /**
     * Form definition.
     */
    public function definition() {
        global $DB;

        $mform = $this->_form;

        $userid = optional_param('userid', null, PARAM_INT);

        $mform->addElement('text', 'userid', get_string('userid', 'enrol_airtime'));
        $mform->setType('userid', PARAM_INT);

        $mform->addElement('submit', 'refresh', get_string('refresh'));
        $mform->registerNoSubmitButton('refresh');

        $autcompleteoptions = array(
                'multiple' => true,
                'noselectionstring' => get_string('none'),
        );
        // Get user options.
        if ($userid) {
            if ($user = $DB->get_record('user', ['id' => $userid, 'deleted' => 0])) {
                $mform->addElement('static', 'exclusionsforuser', $user->firstname . ' ' . $user->lastname, '');
                $courses = $DB->get_records_menu('course', [], '', 'id,fullname');
                $mform->addElement('autocomplete', 'excludedcourses', get_string('excludedcourses', 'enrol_airtime'), $courses, $autcompleteoptions);

                $this->add_action_buttons();
            } else {
                $mform->addElement('static', 'usernotfound', '', get_string('usernotfound', 'enrol_airtime'));
            }
        }



    }

    /**
     * Validation.
     *
     * @param array $data
     * @param array $files
     * @return array the errors that were found
     */
    public function validation($data, $files) {
        global $DB;

        $errors = parent::validation($data, $files);

        return $errors;
    }

}

