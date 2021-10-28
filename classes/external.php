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
 * External API.
 *
 * @package    enrol_airtime
 * @copyright  2020 Michael Gardener <mgardnener@cissq.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace enrol_airtime;

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/externallib.php");
require_once("$CFG->libdir/grade/grade_scale.php");

use context_system;
use context_course;
use context_helper;
use context_user;
use coding_exception;
use enrol_airtime\tools;
use external_api;
use external_function_parameters;
use external_value;
use external_format_value;
use external_single_structure;
use external_multiple_structure;
use invalid_parameter_exception;
use required_capability_exception;
use enrol_airtime\responses;
use enrol_airtime\exceptions\course_not_found_exception;
use enrol_airtime\exceptions\cohort_not_found_exception;
use enrol_airtime\exceptions\role_not_found_exception;
use enrol_airtime\exceptions\group_not_found_exception;
use enrol_airtime\exceptions\course_is_site_exception;
use enrol_airtime\exceptions\cohort_not_available_at_context_exception;
use enrol_airtime\exceptions\role_not_assignable_at_context_exception;
use enrol_airtime\exceptions\invalid_status_exception;
use enrol_airtime\exceptions\cohort_enrol_method_not_available_exception;
use enrol_airtime\exceptions\cohort_enrol_instance_already_synced_with_role_exception;
use enrol_airtime\exceptions\cohort_enrol_instance_not_found_exception;
use enrol_airtime\exceptions\user_not_found_exception;
use enrol_airtime\exceptions\user_enrollment_not_found_exception;


/**
 * External API class.
 *
 * @package    enrol_airtime
 * @copyright  2020 Michael Gardener <mgardnener@cissq.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class external extends external_api {
    /**
     * Constants that define the query strings i.e. https://example.url?querystring[key]=value&querystring[key]=value etcetera.
     */
    const QUERYSTRING_INSTANCE  = 'instance';
    const QUERYSTRING_COURSE    = 'course';
    const QUERYSTRING_ENROLLMENT    = 'enrollment';

    /**
     * Constants that define group creation modes. Create group is already defined. Values are as per the add instance mform.
     */
    const COHORT_GROUP_CREATE_NONE = 0;
    const COHORT_GROUP_CREATE_NEW = -1;

    /**
     * The value that says to the webservice function get_instances() to get all cohort enrolment instances.
     */
    const GET_INSTANCES_COURSEID_ALL = -1;

    /**
     * Constants that map the customint field names to the name of the fields.
     */
    const FIELD_GROUP   = 'customint2';
    const FIELD_COHORT  = 'customint1';

    /**
     * A constant that defines a webservice function call that has errors.
     */
    const WEBSERVICE_FUNCTION_CALL_HAS_ERRORS_ID = -1;

    /// <editor-fold desc="Other non webservice function specific functions that can be used internally.">

    /**
     * All the webservices functions defined in this external lib return the same stuff.
     * This is some dry code.
     *
     * @return external_single_structure
     */
    public static function webservice_function_returns() {
        return new external_single_structure([
            'id'        => new external_value(PARAM_INT, 'The id of the enrolment instance'),
            'code'      => new external_value(PARAM_INT, 'HTTP status code'),
            'message'   => new external_value(PARAM_TEXT, 'Human readable response message'),
            'data' => new external_multiple_structure(
                new external_single_structure(
                    [
                        'object'    => new external_value(PARAM_TEXT, 'The object this is describing'),
                        'id'        => new external_value(PARAM_INT, 'The id of the object', VALUE_OPTIONAL),
                        'name'      => new external_value(PARAM_TEXT, 'The name of the object', VALUE_OPTIONAL),
                        'courseid'  => new external_value(PARAM_INT, 'The id of the related course', VALUE_OPTIONAL),
                        'cohortid'  => new external_value(PARAM_INT, 'The id of the cohort', VALUE_OPTIONAL),
                        'roleid'    => new external_value(PARAM_INT, 'The id of the related role', VALUE_OPTIONAL),
                        'groupid'   => new external_value(PARAM_INT, 'The id of the group', VALUE_OPTIONAL),
                        'idnumber'  => new external_value(PARAM_RAW, 'The idnumber of the object', VALUE_OPTIONAL),
                        'shortname' => new external_value(PARAM_TEXT, 'The shortname of the object', VALUE_OPTIONAL),
                        'suspend'    => new external_value(PARAM_INT, 'The suspend status of the object', VALUE_OPTIONAL),
                        'active'    => new external_value(PARAM_TEXT, 'Enrolment instance is active or not', VALUE_OPTIONAL),
                        'visible'   => new external_value(PARAM_INT, 'The visibility of the object', VALUE_OPTIONAL),
                        'format'    => new external_value(PARAM_PLUGIN, 'The course format', VALUE_OPTIONAL)
                    ],
                    'extra details',
                    VALUE_OPTIONAL
                )
            )
        ]);
    }

    /**
     * The functions get_instances and delete_instance will return the following structure that shows details of
     * the cohort enrolment instance including the related course, cohort, role assigned, and group.
     *
     * @return external_single_structure
     */
    public static function webservice_function_enrolment_instance_returns() {
        // Definition of extra details for course in get enrolment instance/s.
        $coursedetails = new external_single_structure(
            [
                'object'    => new external_value(PARAM_TEXT, 'The type of object'),
                'id'        => new external_value(PARAM_INT, 'The id of the course'),
                'idnumber'  => new external_value(PARAM_RAW, 'The idnumber of the course'),
                'name'      => new external_value(PARAM_TEXT, 'The name of the course'),
                'shortname' => new external_value(PARAM_TEXT, 'The shortname of the course'),
                'visible'   => new external_value(PARAM_INT, 'The visibility of the course'),
                'format'    => new external_value(PARAM_PLUGIN, 'The course format')
            ],
            'More detail about the course associated to a cohort enrolment instance',
            VALUE_OPTIONAL
        );

        // Definition of extra details for cohort in get enrolment instance/s.
        $cohortdetails = new external_single_structure(
            [
                'object'    => new external_value(PARAM_TEXT, 'The type of object'),
                'id'        => new external_value(PARAM_INT, 'The id of the cohort'),
                'idnumber'  => new external_value(PARAM_RAW, 'The idnumber of the cohort'),
                'name'      => new external_value(PARAM_TEXT, 'The name of the cohort'),
                'visible'   => new external_value(PARAM_INT, 'The visibility of the cohort')
            ],
            'More detail about the course associated to a cohort enrolment instance',
            VALUE_OPTIONAL
        );

        // Definition of extra details for role in get enrolment instance/s.
        $roledetails = new external_single_structure(
            [
                'object'    => new external_value(PARAM_TEXT, 'The type of object'),
                'id'        => new external_value(PARAM_INT, 'The id of the role'),
                'shortname' => new external_value(PARAM_TEXT, 'The shortname of the role'),
            ],
            'More detail about the course associated to a cohort enrolment instance',
            VALUE_OPTIONAL
        );

        // Definition of extra details for role in get enrolment instance/s.
        $groupdetails = new external_single_structure(
            [
                'object'    => new external_value(PARAM_TEXT, 'The type of object'),
                'id'        => new external_value(PARAM_INT, 'The id of the group'),
                'courseid'  => new external_value(PARAM_INT, 'The id of the course this group belongs to'),
                'name'      => new external_value(PARAM_TEXT, 'The name of the group'),
            ],
            'More detail about the course associated to a cohort enrolment instance',
            VALUE_OPTIONAL
        );

        return new external_single_structure([
            'id'        => new external_value(PARAM_INT, 'The id of the enrolment instance'),
            'code'      => new external_value(PARAM_INT, 'HTTP status code'),
            'message'   => new external_value(PARAM_TEXT, 'Human readable response message'),
            'data' => new external_multiple_structure(
                new external_single_structure(
                    [
                        'object'    => new external_value(PARAM_TEXT, 'The object this is describing'),
                        'id'        => new external_value(PARAM_INT, 'The id of the object', VALUE_OPTIONAL),
                        'name'      => new external_value(PARAM_TEXT, 'The name of the object', VALUE_OPTIONAL),
                        'courseid'  => new external_value(PARAM_INT, 'The id of the related course', VALUE_OPTIONAL),
                        'cohortid'  => new external_value(PARAM_INT, 'The id of the cohort', VALUE_OPTIONAL),
                        'roleid'    => new external_value(PARAM_INT, 'The id of the related role', VALUE_OPTIONAL),
                        'groupid'   => new external_value(PARAM_INT, 'The id of the group', VALUE_OPTIONAL),
                        'idnumber'  => new external_value(PARAM_RAW, 'The idnumber of the object', VALUE_OPTIONAL),
                        'shortname' => new external_value(PARAM_TEXT, 'The shortname of the object', VALUE_OPTIONAL),
                        'suspend'    => new external_value(PARAM_INT, 'The suspend status of the object', VALUE_OPTIONAL),
                        'active'    => new external_value(PARAM_TEXT, 'Enrolment instance is active or not', VALUE_OPTIONAL),
                        'visible'   => new external_value(PARAM_INT, 'The visibility of the object', VALUE_OPTIONAL),
                        'format'    => new external_value(PARAM_PLUGIN, 'The course format', VALUE_OPTIONAL),
                        'course'    => $coursedetails,
                        'cohort'    => $cohortdetails,
                        'role'      => $roledetails,
                        'group'     => $groupdetails
                    ],
                    'extra details',
                    VALUE_OPTIONAL
                )
            )
        ]);
    }

    /**
     * This function gets assignable roles for a course context.
     *
     * @param null $context
     * @return array
     */
    public static function get_assignable_roles($context = null) {
        return $context instanceof \context_course ? get_assignable_roles($context) : [];
    }

    /// </editor-fold>

    /// <editor-fold desc="Functions for add_instance().">

    /**
     * Gets the default value for an add_instance() parameter. Use properly. No error checking happens.
     *
     * @param string $parametername
     * @return mixed
     */
    public static function add_instance_get_parameter_default_value($parametername = '') {
        // Just ask for the right things and one shall receive. We shan't be making any mistakes.
        return self::add_instance_parameters()->keys[self::QUERYSTRING_INSTANCE]->keys[$parametername]->default;
    }

    /**
     * Returns description of the add_instance function parameters.
     *
     * @return external_function_parameters
     */
    public static function add_instance_parameters() {
        $courseidexternalvalue  = new external_value(
            PARAM_INT, 'The id of the course.', VALUE_REQUIRED
        );

        $cohortidexternalvalue  = new external_value(
            PARAM_INT, 'The id of the cohort.', VALUE_REQUIRED
        );

        $roleidexternalvalue    = new external_value(
            PARAM_INT, 'The id of an existing role to assign users.', VALUE_REQUIRED
        );

        $groupidexternalvalue   = new external_value(
            PARAM_INT, 'The id of a group to add users to.', VALUE_OPTIONAL, self::COHORT_GROUP_CREATE_NONE
        );

        $nameexternalvalue      = new external_value(
            PARAM_TEXT, 'The name of the cohort enrolment instance.', VALUE_OPTIONAL, ''
        );

        $statusexternalvalue    = new external_value(
            PARAM_INT, 'The status of the enrolment method.', VALUE_OPTIONAL, ENROL_INSTANCE_ENABLED
        );

        return new external_function_parameters([
            self::QUERYSTRING_INSTANCE => new external_single_structure([
                'courseid'  => $courseidexternalvalue,
                'cohortid'  => $cohortidexternalvalue,
                'roleid'    => $roleidexternalvalue,
                'groupid'   => $groupidexternalvalue,
                'name'      => $nameexternalvalue,
                'suspend'    => $statusexternalvalue
            ])
        ]);
    }

    /**
     * Returns description of the add_instance() function return value.
     *
     * @return external_single_structure
     */
    public static function add_instance_returns() {
        return self::webservice_function_returns();
    }

    /**
     * Adds a cohort enrolment instance to a given course.
     *
     * @param $params
     * @return array
     * @throws coding_exception
     * @throws dml_exception
     * @throws invalid_parameter_exception
     * @throws moodle_exception
     */
    public static function add_instance($params) {
        global $CFG, $DB, $SITE;

        require_once("{$CFG->dirroot}/cohort/lib.php");

        // Check the call for parameters.
        $params = self::validate_parameters(self::add_instance_parameters(), [self::QUERYSTRING_INSTANCE => $params]);

        // Other data.
        $extradata = [];

        // Get the course.
        $courseid = $params[self::QUERYSTRING_INSTANCE]['courseid'];

        // Validate the course. This is required.
        if ($courseid == $SITE->id) {
            throw new course_is_site_exception();
        } else if (!$DB->record_exists('course', ['id' => $courseid])) {
            throw new course_not_found_exception($courseid);
        }

        // Set the context to course for validation.
        $context = \context_course::instance($courseid);

        // Add info to the response object.
        $extradata[] = (new responses\course($courseid))->to_array();

        // Get the cohort. This is required
        $cohortid = $params[self::QUERYSTRING_INSTANCE]['cohortid'];

        // Validate the cohort. This is required.
        if (!$DB->record_exists('cohort', ['id' => $cohortid])) {
            throw new cohort_not_found_exception($cohortid);
        }

        // Add some info to the response object.
        $extradata[] = (new responses\cohort($cohortid))->to_array();

        // Get the available cohorts.
        $availablecohorts = cohort_get_available_cohorts($context, 0, 0, 0);
        if (empty($availablecohorts) || !isset($availablecohorts[$cohortid])) {
            throw new cohort_not_available_at_context_exception($cohortid);
        }

        // Get the role.
        $roleid = $params[self::QUERYSTRING_INSTANCE]['roleid'];

        // Validate the role. This is required.
        $assignableroles = self::get_assignable_roles($context);

        if (!$DB->record_exists('role', ['id' => $roleid])) {
            // Role doesn't exist.
            throw new role_not_found_exception($roleid);
        } else if (empty($assignableroles) || !isset($assignableroles[$roleid])) {
            // Role is not assignable at this context.
            throw new role_not_assignable_at_context_exception($roleid);
        }

        $extradata[] = (new responses\role($roleid))->to_array();

        // Get the group.
        if (!isset($params[self::QUERYSTRING_INSTANCE]['groupid'])) {
            $groupid = self::add_instance_get_parameter_default_value('groupid');
        } else {
            $groupid = $params[self::QUERYSTRING_INSTANCE]['groupid'];
        }

        // Validate the group. This is optional.
        if (!is_null($groupid)) {
            $groupcreatemodes = [self::COHORT_GROUP_CREATE_NONE, self::COHORT_GROUP_CREATE_NEW];
            $coursegroupexists = $DB->record_exists('groups', ['courseid' => $courseid, 'id' => $groupid]);
            if (!in_array($groupid, $groupcreatemodes) && !$coursegroupexists) {
                // Provided group id doesn't exist for this course.
                throw new group_not_found_exception($groupid);
            }
        } else {
            // Get the default value specified for the parameter groupid.
            $groupid = self::add_instance_get_parameter_default_value('groupid');
        }

        // Validate the name of the cohort enrolment instance. This is optional.
        if (!isset($params[self::QUERYSTRING_INSTANCE]['name'])) {
            $name = self::add_instance_get_parameter_default_value('name');
        } else {
            $name = $params[self::QUERYSTRING_INSTANCE]['name'];
        }

        // Check the users capabilities to ensure that they can do this.
        if ($context instanceof \context_course) {
            // Check that the user has the required capabilities for the course context. This may not be needed.
            $requiredcapabilities = [
                'moodle/course:enrolconfig',
                'enrol/cohort:config',
                'moodle/cohort:view',
                'moodle/course:managegroups',
                'moodle/role:assign'
            ];

            foreach ($requiredcapabilities as $requiredcapability) {
                if (!has_capability($requiredcapability, $context)) {
                    throw new required_capability_exception($context, $requiredcapability, 'nopermissions', '');
                }
            }
        }

        // Validate the status and set to a default.
        if (!isset($params[self::QUERYSTRING_INSTANCE]['suspend'])) {
            $status = self::add_instance_get_parameter_default_value('suspend');
        } else {
            $status = $params[self::QUERYSTRING_INSTANCE]['suspend'];
        }

        if (!is_null($status) && !in_array($status, [ENROL_INSTANCE_ENABLED, ENROL_INSTANCE_DISABLED])) {
            throw new invalid_status_exception($status);
        } else {
            // Set status to the default.
            $status = self::add_instance_get_parameter_default_value('suspend');
        }

        // This is the important one. Check if the cohort enrolment instance is available for use.
        if (!$cohortenrolment = enrol_get_plugin('airtime')) {
            throw new cohort_enrol_method_not_available_exception();
        }

        // Prepare the data to be returned as the response.
        $extradata[] = [
            'object'    => 'data',
            'cohortid'  => $cohortid,
            'roleid'    => $roleid,
            'groupid'   => $groupid,
            'name'      => $name,
            'suspend'    => $status
        ];

        // We are anticipating a success.
        $code = 201;
        $message = tools::get_string('addinstance:201', $code);

        // The initial response. The field id will be filled in later.
        $response = [
            'code'      => $code,
            'message'   => $message,
            'data'      => $extradata
        ];

        // Prepare the fields.
        $fields = [
            'name'              => $name,
            'status'            => $status,
            'roleid'            => $roleid,
            'id'                => 0,
            'courseid'          => $courseid,
            'type'              => 'cohort',
            self::FIELD_COHORT  => $cohortid,
            self::FIELD_GROUP   => $groupid
        ];

        // Before creation ensure that there isn't an instance already synced with this role.
        $sqlwhere = "roleid = :roleid AND customint1 = :customint1 AND courseid = :courseid AND enrol = 'airtime' AND id <> :id";
        $sqlparams = [
            'roleid'            => $roleid,
            self::FIELD_COHORT  => $cohortid,
            'courseid'          => $courseid,
            'id'                => $fields['id']
        ];

        if ($DB->record_exists_select('enrol', $sqlwhere, $sqlparams)) {
            // Don't add instance. Send an error response.
            $instance = $DB->get_record_select('enrol', $sqlwhere, $sqlparams);

            throw new cohort_enrol_instance_already_synced_with_role_exception($instance->id, $roleid);
        }

        // Get the full course object.
        $course = $DB->get_record('course', ['id' => $courseid]);

        // After all that hard work we can now add the instance.
        $response['id'] = $cohortenrolment->add_instance($course, $fields);

        // Get the enrolment instance.
        $realenrolinstance = $DB->get_record('enrol', ['id' => $response['id']]);
        if (empty($realenrolinstance->name)) {
            $enrolinstancename =
                $cohortenrolment->get_instance_name($realenrolinstance).' - '.tools::get_string('addinstance:usingdefaultname');
        } else {
            $enrolinstancename = $realenrolinstance->name;
        }

        // Add data about the group to the response.
        $realgroupid = $DB->get_field('enrol', self::FIELD_GROUP, ['id' => $response['id']]);
        $extradata[] = (new responses\group($realgroupid, 'group', $courseid))->to_array();

        // Add data about the enrolment instance to the response.
        $extradata[] = (new responses\enrol(
            $response['id'], 'enrol', $enrolinstancename, $status, $roleid, $courseid, $cohortid, $realgroupid
        ))->to_array();

        // Add the additional extra data to the response.
        $response['data'] = $extradata;

        // Return some data.
        return $response;
    }

    /// </editor-fold>

    /// <editor-fold desc="Functions for update_instance().">

    /**
     * Gets the default value for an add_instance() parameter. Use properly. No error checking happens.
     *
     * @param string $parametername
     * @return mixed
     */
    public static function update_instance_get_parameter_default_value($parametername = '') {
        // Just ask for the right things and one shall receive. We shan't be making any mistakes.
        return self::update_instance_parameters()->keys[self::QUERYSTRING_INSTANCE]->keys[$parametername]->default;
    }

    /**
     * Returns description of the update_instance() function parameters.
     *
     * @return external_function_parameters
     */
    public static function update_instance_parameters() {
        return new external_function_parameters([
            self::QUERYSTRING_INSTANCE => new external_single_structure([
                'id'      => new external_value(PARAM_INT, 'The id of the enrolment instance.', VALUE_REQUIRED),
                'name'    => new external_value(PARAM_TEXT, 'The name you want to give the enrolment instance.', VALUE_OPTIONAL),
                'suspend'  => new external_value(PARAM_INT, 'The status of the enrolment method.', VALUE_OPTIONAL),
                'roleid'  => new external_value(PARAM_INT, 'The id of an existing role to assign users.', VALUE_OPTIONAL),
                'groupid' => new external_value(PARAM_INT, 'The id of a group to add users to.', VALUE_OPTIONAL)
            ])
        ]);
    }

    /**
     * Returns description of the update_instance function return value.
     *
     * @return external_single_structure
     */
    public static function update_instance_returns() {
        return self::webservice_function_returns();
    }

    /**
     * Updates an existing cohort enrolment instance.
     *
     * @param $params
     * @return array
     * @throws coding_exception
     * @throws dml_exception
     * @throws invalid_parameter_exception
     * @throws moodle_exception
     */
    public static function update_instance($params) {
        global $DB, $SITE;

        // Check the call for parameters.
        $params = self::validate_parameters(self::update_instance_parameters(), [self::QUERYSTRING_INSTANCE => $params]);

        // Other data.
        $extradata = [];

        // A place to put the updated enrolment data.
        $data = new \stdClass();

        // Preset the context.
        $context = null;

        // Get the enrolment instance id.
        $id = $params[self::QUERYSTRING_INSTANCE]['id'];

        // Validate the enrolment instance.
        $sqlwhere = "enrol = 'airtime' AND id = :id AND courseid <> :courseid";
        $sqlparams = ['id' => $id, 'courseid' => $SITE->id];

        $enrolmentinstance = null;

        if (!$DB->record_exists_select('enrol', $sqlwhere, $sqlparams)) {
            throw new cohort_enrol_instance_not_found_exception($id);
        } else {
            $enrolmentinstance = $DB->get_record_select('enrol', $sqlwhere, $sqlparams);
            $context = \context_course::instance($enrolmentinstance->courseid);
        }

        // Get the enrolment instance name.
        if (!isset($params[self::QUERYSTRING_INSTANCE]['name'])) {
            $name = $enrolmentinstance->name;
        } else {
            $name = $params[self::QUERYSTRING_INSTANCE]['name'];
        }

        if (!empty($name)) {
            $data->name = $name;
        }

        // Get the enrolment instance status.
        if (!isset($params[self::QUERYSTRING_INSTANCE]['suspend'])) {
            $status = $enrolmentinstance->status;
        } else {
            $status = $params[self::QUERYSTRING_INSTANCE]['suspend'];
        }

        // Validate the enrolment instance status.
        if (!is_null($status) && !in_array($status, [ENROL_INSTANCE_ENABLED, ENROL_INSTANCE_DISABLED])) {
            throw new invalid_status_exception($status);
        } else if (!is_null($status) && in_array($status, [ENROL_INSTANCE_ENABLED, ENROL_INSTANCE_DISABLED])) {
            $data->status = $status;
        }

        // Get the enrolment instance role id.
        if (!isset($params[self::QUERYSTRING_INSTANCE]['roleid'])) {
            $roleid = $enrolmentinstance->roleid;
        } else {
            $roleid = $params[self::QUERYSTRING_INSTANCE]['roleid'];
        }

        if (!empty($roleid)) {
            // Validate the role. This is required.
            $assignableroles = self::get_assignable_roles($context);

            if (!$DB->record_exists('role', ['id' => $roleid])) {
                // Role doesn't exist.
                throw new role_not_found_exception($roleid);
            } else if (empty($assignableroles) || !isset($assignableroles[$roleid])) {
                // Role is not assignable at this context.
                throw new role_not_assignable_at_context_exception($roleid);
            } else {
                $data->roleid = $roleid;
            }
        }

        // Get the group id.
        if (!isset($params[self::QUERYSTRING_INSTANCE]['groupid'])) {
            $groupid = $enrolmentinstance->{self::FIELD_GROUP};
        } else {
            $groupid = $params[self::QUERYSTRING_INSTANCE]['groupid'];
        }
        // Validate the group id.
        if (!empty($groupid)) {
            $groupcreatemodes = [self::COHORT_GROUP_CREATE_NONE, self::COHORT_GROUP_CREATE_NEW];
            $groupexistsforcourse = $DB->record_exists('groups', ['courseid' => $enrolmentinstance->courseid, 'id' => $groupid]);
            if (!in_array($groupid, $groupcreatemodes) && !is_null($enrolmentinstance) && !$groupexistsforcourse) {
                // Provided group id doesn't exist for this course.
                throw new group_not_found_exception($groupid);
            }
        }

        $data->{self::FIELD_GROUP} = $groupid;

        // Return the supplied parameters.
        $extradata[] = [
            'id'        => $id,
            'object'    => 'params',
            'roleid'    => $roleid,
            'name'      => $name,
            'suspend'    => $status,
            'groupid'   => $groupid
        ];

        // Check for things that are the same.
        if ($enrolmentinstance instanceof \stdClass && $context instanceof \context_course) {
            foreach ($data as $property => $value) {
                if (isset($enrolmentinstance->$property) && $enrolmentinstance->$property != $value) {
                    // Add role detail to the response object.
                    if (\core_text::strtolower($property) == 'roleid') {
                        $extradata[] = (new responses\role($value))->to_array();
                    }
                }
            }
        }

        // This is the important one. Check if the cohort enrolment instance is available for use.
        if (!$cohortenrolment = enrol_get_plugin('airtime')) {
            throw new cohort_enrol_method_not_available_exception();
        }

        // Check the params passed aka only id is passed
        $noupdations = count($params[self::QUERYSTRING_INSTANCE]) === 1 && isset($params[self::QUERYSTRING_INSTANCE]['id']);

        // Response message.
        if ($noupdations) {
            $message = tools::get_string('updateinstance:nochange');
        } else {
            $message = tools::get_string('updateinstance:200');

            // Add the cohort id to the data.
            $data->{self::FIELD_COHORT} = $enrolmentinstance->{self::FIELD_COHORT};

            $previousgroupid = $enrolmentinstance->{self::FIELD_GROUP};

            // Haven't even updated the enrolment instance and we are celebrating.
            $cohortenrolment->update_instance($enrolmentinstance, $data);

            // Add the course to the response object.
            $extradata[] = (new responses\course($enrolmentinstance->courseid))->to_array();

            // Add the cohort detail to the response object.
            $extradata[] = (new responses\cohort($enrolmentinstance->{self::FIELD_COHORT}))->to_array();

            // Get the new group and add to the response object.
            if ($groupid == COHORT_CREATE_GROUP) {
                $extradata[] = (new responses\group($data->{self::FIELD_GROUP}, 'group', $enrolmentinstance->courseid))->to_array();
            } else if ($groupid != $previousgroupid) {
                $extradata[] = (new responses\group($groupid, 'group', $enrolmentinstance->courseid))->to_array();
            }

            // Add detail about the enrolment instance.
            $extradata[] = (new responses\enrol(
                $id,
                'enrol',
                $enrolmentinstance->name,
                $enrolmentinstance->status,
                $enrolmentinstance->roleid,
                $enrolmentinstance->courseid,
                $enrolmentinstance->{self::FIELD_COHORT},
                $enrolmentinstance->{self::FIELD_GROUP}
            ))->to_array();
        }

        // The HTTP status code.
        $code = 200;

        // Prepare the response.
        $response = [
            'id'        => $id,
            'code'      => $code,
            'message'   => $message,
            'data'      => $extradata
        ];

        return $response;
    }

    /// </editor-fold>

    /// <editor-fold desc="Functions for delete_instance().">

    /**
     * Returns description of the delete_instance() function parameters.
     *
     * @return external_function_parameters
     */
    public static function delete_instance_parameters() {
        return new external_function_parameters([
            self::QUERYSTRING_INSTANCE => new external_single_structure([
                'id' => new external_value(PARAM_INT, 'The id of the enrolment instance to delete.', VALUE_REQUIRED)
            ])
        ]);
    }

    /**
     * Returns description of the delete_instance() function return value.
     *
     * @return external_single_structure
     */
    public static function delete_instance_returns() {
        return self::webservice_function_enrolment_instance_returns();
    }

    /**
     * Deletes a cohort enrolment instance.
     *
     * @param $params
     * @return array
     * @throws coding_exception
     * @throws dml_exception
     * @throws invalid_parameter_exception
     * @throws moodle_exception
     */
    public static function delete_instance($params) {
        global $DB;

        // Check the parameters.
        $params = self::validate_parameters(self::delete_instance_parameters(), [self::QUERYSTRING_INSTANCE => $params]);

        $extradata = [];

        // Get the enrolment instance id.
        $id = $params[self::QUERYSTRING_INSTANCE]['id'];

        // Validate the enrolment instance id.
        if (!$DB->record_exists('enrol', ['id' => $id, 'enrol' => 'airtime'])) {
            throw new cohort_enrol_instance_not_found_exception($id);
        }

        // This is the important one. Check if the cohort enrolment instance is available for use.
        if (!$cohortenrolment = enrol_get_plugin('airtime')) {
            throw new cohort_enrol_method_not_available_exception();
        }

        // Set the HTTP response code.
        $code = 200;

        // Get the enrolment instance Nelly ;).
        $ei = $DB->get_record('enrol', ['id' => $id, 'enrol' => 'airtime']);

        // Make a E.I. response.
        $eiresponse = new responses\enrol(
            $id, 'enrol', $ei->name, $ei->status, $ei->roleid, $ei->courseid, $ei->{self::FIELD_COHORT}, $ei->{self::FIELD_GROUP}
        );

        // Get other details about the deleted enrolment instance.
        $eiresponse->set_course((new responses\course($ei->courseid))->to_array());
        $eiresponse->set_cohort((new responses\cohort($ei->{self::FIELD_COHORT}))->to_array());
        $eiresponse->set_role((new responses\role($ei->roleid))->to_array());
        $eiresponse->set_group((new responses\group($ei->{self::FIELD_GROUP}, 'group', $ei->courseid))->to_array());

        // Add the E.I. to the response.
        $extradata[] = $eiresponse->to_array();

        // Actuals delete the enrolment instance.
        $cohortenrolment->delete_instance($ei);

        // The following is a message of success.
        $message = tools::get_string('deleteinstance:200');

        // Prepare the response and then send it.
        $response = [
            'id'        => $id,
            'code'      => $code,
            'message'   => $message,
            'data'      => $extradata
        ];

        return $response;
    }

    /// </editor-fold>

    /// <editor-fold desc="Functions for get_instances().">

    /**
     * Returns description of the get_instances() function return value.
     *
     * @return external_function_parameters
     */
    public static function get_instances_parameters() {
        return new external_function_parameters([
            self::QUERYSTRING_COURSE => new external_single_structure([
                'id' => new external_value(PARAM_INT, 'The id of a course to get enrolment instances for.', VALUE_REQUIRED)
            ])
        ]);
    }

    /**
     * Returns description of the get_instances() function return value.
     *
     * @return external_single_structure
     */
    public static function get_instances_returns() {
        return self::webservice_function_enrolment_instance_returns();
    }

    /**
     * Gets the enrolment instances for a course or the entire site.
     *
     * @param $params
     * @return array
     * @throws coding_exception
     * @throws dml_exception
     * @throws invalid_parameter_exception
     * @throws moodle_exception
     */
    public static function get_instances($params) {
        global $DB, $SITE;

        // Check the parameters.
        $params = self::validate_parameters(self::get_instances_parameters(), [self::QUERYSTRING_COURSE => $params]);

        $extradata = [];

        // Get the courseid aka id.
        $courseid = $params[self::QUERYSTRING_COURSE]['id'];

        // Validate the courseid.
        if ($courseid == $SITE->id) {
            throw new course_is_site_exception();
        } else if (!$DB->record_exists('course', ['id' => $courseid]) && $courseid != -1) {
            throw new course_not_found_exception($courseid);
        }

        // Get the courses.
        if ($courseid == self::GET_INSTANCES_COURSEID_ALL) {
            $courses = $DB->get_records('course');
        } else {
            $courses = $DB->get_records('course', ['id' => $courseid]);
        }

        $foundsitecourse = false;
        $numberofenrolmentmethods = 0;

        foreach ($courses as $course) {
            // Skip the site course.
            if ($course->id == $SITE->id) {
                $foundsitecourse = true;
                continue;
            }

            // Skip courses that don't have cohort enrolment methods.
            if (!$DB->record_exists('enrol', ['enrol' => 'airtime', 'courseid' => $course->id])) {
                continue;
            }

            // Get the cohort enrolment instances for this course.
            $enrolmentinstances = $DB->get_records('enrol', ['enrol' => 'airtime', 'courseid' => $course->id]);

            foreach ($enrolmentinstances as $enrolmentinstance) {
                // Make some info about the enrolment instance.
                $ei = new responses\enrol(
                    $enrolmentinstance->id,
                    'enrol',
                    $enrolmentinstance->name,
                    $enrolmentinstance->status,
                    $enrolmentinstance->roleid,
                    $enrolmentinstance->courseid,
                    $enrolmentinstance->{self::FIELD_COHORT},
                    $enrolmentinstance->{self::FIELD_GROUP}
                );

                // Get other details about the enrolment instance.
                $ei->set_course((new responses\course($enrolmentinstance->courseid))->to_array());
                $ei->set_cohort((new responses\cohort($enrolmentinstance->{self::FIELD_COHORT}))->to_array());
                $ei->set_role((new responses\role($enrolmentinstance->roleid))->to_array());

                // Made an object for this so it doesn't exceed 132 characters.
                $groupresponse = new responses\group(
                    $enrolmentinstance->{self::FIELD_GROUP}, 'group', $enrolmentinstance->courseid
                );

                $ei->set_group($groupresponse->to_array());

                $extradata[] = $ei->to_array();

                $numberofenrolmentmethods += 1;
            }
        }

        // Count things and make langstring placeholders.
        $numberofcourses = count($courses);
        if ($foundsitecourse) {
            $numberofcourses -= 1;
        }

        $a = [
            'courseid'                      => $courseid,
            'numberofenrolmentinstances'    => $numberofenrolmentmethods,
            'numberofcourses'               => $numberofcourses
        ];

        if ($courseid == self::GET_INSTANCES_COURSEID_ALL) {
            $message = tools::get_string('getinstances:200', $a);
        } else {
            $message = tools::get_string('getinstance:200', $a);
        }

        // Prepare the response.
        $response = [
            'id'        => $courseid,
            'code'      => 200,
            'message'   => $message,
            'data'      => $extradata
        ];

        return $response;
    }

    /// </editor-fold>

    /**
     * Returns description of the update_instance() function parameters.
     *
     * @return external_function_parameters
     */
    public static function update_user_enrollment_parameters() {
        return new external_function_parameters(
                array(
                        self::QUERYSTRING_ENROLLMENT => new external_multiple_structure(
                                new external_single_structure(
                                        array(
                                                'userid' => new external_value(PARAM_INT, 'The id of the User.', VALUE_REQUIRED),
                                                'courseid' => new external_value(PARAM_INT, 'The id of the Course.', VALUE_REQUIRED),
                                                'suspend' => new external_value(PARAM_INT, 'The status of the user enrollment .', VALUE_OPTIONAL),
                                                'timestart' => new external_value(PARAM_INT, '', VALUE_OPTIONAL),
                                                'timeend' => new external_value(PARAM_INT, '', VALUE_OPTIONAL)
                                        )
                                )
                        )
                )
        );
    }

    /**
     * Returns description of the update_user_enrollment function return value.
     *
     * @return external_single_structure
     */
    public static function update_user_enrollment_returns() {
        return self::webservice_function_returns();
    }

    /**
     * Updates an existing cohort enrolment instance.
     *
     * @param $params
     * @return array
     * @throws coding_exception
     * @throws dml_exception
     * @throws invalid_parameter_exception
     * @throws moodle_exception
     */
    public static function update_user_enrollment($params) {
        global $DB, $SITE;

        // Check the call for parameters.
        $params = self::validate_parameters(self::update_user_enrollment_parameters(), [self::QUERYSTRING_ENROLLMENT => $params]);

        // This is the important one. Check if the cohort enrolment instance is available for use.
        if (!$cohortenrolment = enrol_get_plugin('airtime')) {
            throw new cohort_enrol_method_not_available_exception();
        }

        // Other data.
        $extradata = [];

        // Preset the context.
        $context = null;

        foreach ($params['enrollment'] as $enrollrecord) {
            // Get the enrolment instance id.
            $userid = $enrollrecord['userid'];
            $courseid = $enrollrecord['courseid'];

            if (!$user = $DB->get_record('user', ['id' => $userid])) {
                throw new user_not_found_exception($userid);
            }

            // Validate the enrolment instance.
            $sqlwhere = "enrol = 'airtime' AND courseid = :courseid";
            $sqlparams = ['courseid' => $courseid];

            $enrolmentinstance = null;

            if (!$enrollinstances = $DB->get_records_select('enrol', $sqlwhere, $sqlparams)) {
                throw new cohort_enrol_instance_not_found_exception();
            }

            list($insql, $sqlparams) = $DB->get_in_or_equal(array_keys($enrollinstances), SQL_PARAMS_NAMED);

            $sqlparams['userid'] = $userid;

            if (!$userenrollments =
                    $DB->get_records_select('user_enrolments', "enrolid {$insql} AND userid = :userid", $sqlparams)) {
                throw new user_enrollment_not_found_exception();
            }

            $noupdations = true;

            foreach ($userenrollments as $userenrollment) {
                $update = false;

                $data = new \stdClass();
                $data->id = $userenrollment->id;

                // Get the enrolment instance status.
                if (isset($enrollrecord['suspend'])) {
                    $status = $enrollrecord['suspend'];

                    // Validate the enrolment instance status.
                    if (!is_null($status) && !in_array($status, [ENROL_USER_ACTIVE, ENROL_USER_SUSPENDED])) {
                        throw new invalid_status_exception($status);
                    } else if (!is_null($status) && in_array($status, [ENROL_USER_ACTIVE, ENROL_USER_SUSPENDED])) {
                        $data->status = $status;
                        $update = true;
                    }
                }

                if (isset($enrollrecord['timestart'])) {
                    $data->timestart = $enrollrecord['timestart'];
                    $update = true;
                }

                if (isset($enrollrecord['timeend'])) {
                    $data->timeend = $enrollrecord['timeend'];
                    $update = true;
                }

                if ($update) {
                    $DB->update_record('user_enrolments', $data);
                    $noupdations = false;
                    $extradata[] = [
                            'object' => 'user_enrolments',
                            'id' => $data->id,
                            'courseid' => $courseid
                    ];
                }
            }
        }

        // Response message.
        if ($noupdations) {
            $message = tools::get_string('updateinstance:nochange');
        } else {
            $message = tools::get_string('updateinstance:200');
        }

        // The HTTP status code.
        $code = 200;

        // Prepare the response.
        $response = [
            'id'        => $userid,
            'code'      => $code,
            'message'   => $message,
            'data'      => $extradata
        ];

        return $response;
    }

    public static function add_exclusion_parameters() {
        return new external_function_parameters(
                array(
                        'userid' => new external_value(PARAM_INT, 'The id of the User.', VALUE_REQUIRED),
                        'courseid' => new external_value(PARAM_INT, 'The id of the Course.', VALUE_REQUIRED),
                )
        );
    }

    public static function add_exclusion($userid, $courseid) {
        global $DB;

        $params = self::validate_parameters(self::add_exclusion_parameters(), ['userid' => $userid, 'courseid' => $courseid]);

        if (!$DB->record_exists('user', ['id' => $params['userid']])) {
            throw new invalid_parameter_exception("User {$params['userid']} not found!");
        }

        if (!$DB->record_exists('course', ['id' => $params['courseid']])) {
            throw new invalid_parameter_exception("Course {$params['courseid']} not found!");
        }

        if (!$DB->record_exists('enrol_airtime_exclusions', ['userid' => $params['userid'], 'courseid' => $params['courseid']])) {
            $record = new \stdClass();
            $record->userid = $params['userid'];
            $record->courseid = $params['courseid'];
            $DB->insert_record('enrol_airtime_exclusions', $record);
        }

        return true;
    }

    public static function add_exclusion_returns() {
        return new external_value(PARAM_BOOL, 'True if the update was successful.');
    }

    public static function remove_exclusion_parameters() {
        return new external_function_parameters(
                array(
                        'userid' => new external_value(PARAM_INT, 'The id of the User.', VALUE_REQUIRED),
                        'courseid' => new external_value(PARAM_INT, 'The id of the Course.', VALUE_REQUIRED),
                )
        );
    }

    public static function remove_exclusion($userid, $courseid) {
        global $DB;

        $params = self::validate_parameters(self::remove_exclusion_parameters(), ['userid' => $userid, 'courseid' => $courseid]);

        $DB->delete_records('enrol_airtime_exclusions', ['userid' => $params['userid'], 'courseid' => $params['courseid']]);

        return true;
    }

    public static function remove_exclusion_returns() {
        return new external_value(PARAM_BOOL, 'True if the update was successful.');
    }

    public static function get_user_exclusions_parameters() {
        return new external_function_parameters(
                array(
                        'userid' => new external_value(PARAM_INT, 'Moodle user database id', VALUE_REQUIRED),
                )
        );
    }

    public static function get_user_exclusions($userid) {
        global $DB;

        $params = self::validate_parameters(self::get_user_exclusions_parameters(), ['userid' => $userid]);

        $exclusions = $DB->get_fieldset_select('enrol_airtime_exclusions', 'courseid', 'userid = ?', [$params['userid']]);

        if (empty($exclusions)) {
            return [];
        }
        return $exclusions;
    }

    public static function get_user_exclusions_returns() {
        return new external_multiple_structure(
                new external_value(PARAM_TEXT, 'Moodle course database id')
        );
    }

}