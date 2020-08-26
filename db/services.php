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
 * Definition of webservices for enrol_airtime.
 *
 * @package    enrol_airtime
 * @copyright  2020 Michael Gardener <mgardnener@cissq.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// No direct access.
defined('MOODLE_INTERNAL') || die();

// The individual capabilities.
$cohortview         = 'moodle/cohort:view';
$roleassign         = 'moodle/role:assign';
$coursemanagegroups = 'moodle/course:managegroups';
$courseenrolconfig  = 'moodle/course:enrolconfig';
$enrolairtimeconfig = 'enrol/airtime:config';

// Put the capabilities together for each webservice function.
$addcapabilities    = "{$cohortview}, {$roleassign}, {$coursemanagegroups}, {$courseenrolconfig}, {$enrolairtimeconfig}";
$updatecapabilities = "{$roleassign}, {$coursemanagegroups}, {$courseenrolconfig}, {$enrolairtimeconfig}";
$deletecapabilities = "{$cohortview}, {$coursemanagegroups}, {$courseenrolconfig}, {$enrolairtimeconfig}";
$getcapabilities    = "{$cohortview}, {$courseenrolconfig}, {$enrolairtimeconfig}";

// We defined the web service functions to install.
$functions = [
    'enrol_airtime_add_instance' => [
        'classname'     => 'enrol_airtime\external',
        'methodname'    => 'add_instance',
        'classpath'     => '',
        'description'   => 'Adds a new cohort sync enrolment instance to the specified course.',
        'capabilities'  => $addcapabilities,
        'type'          => 'create'
    ],
    'enrol_airtime_update_instance' => [
        'classname'     => 'enrol_airtime\external',
        'methodname'    => 'update_instance',
        'classpath'     => '',
        'description'   => 'Updates an existing cohort enrolment instance.',
        'capabilities'  => $updatecapabilities,
        'type'          => 'update'
    ],
    'enrol_airtime_delete_instance' => [
        'classname'     => 'enrol_airtime\external',
        'methodname'    => 'delete_instance',
        'classpath'     => '',
        'description'   => 'Deletes an existing cohort enrolment instance.',
        'capabilities'  => $deletecapabilities,
        'type'          => 'delete'
    ],
    'enrol_airtime_get_instances' => [
        'classname'     => 'enrol_airtime\external',
        'methodname'    => 'get_instances',
        'classpath'     => '',
        'description'   => 'Gets a list of cohort enrolment instances.',
        'capabilities'  => $getcapabilities,
        'type'          => 'get'
    ]
];