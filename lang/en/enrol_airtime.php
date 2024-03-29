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
 * Strings for component 'enrol_airtime', language 'en'.
 *
 * @package    enrol_airtime
 * @copyright  2010 Petr Skoda  {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['addgroup'] = 'Add to group';
$string['assignrole'] = 'Assign role';
$string['cohort:config'] = 'Configure cohort instances';
$string['cohort:unenrol'] = 'Unenrol suspended users';
$string['defaultgroupnametext'] = '{$a->name} cohort {$a->increment}';
$string['enrolcohortsynctask'] = 'Cohort enrolment sync task';
$string['instanceexists'] = 'Cohort is already synchronised with selected role';
$string['pluginname'] = 'AirTime Sync';
$string['pluginname_desc'] = 'Cohort enrolment plugin synchronises cohort members with course participants.';
$string['status'] = 'Active';
$string['creategroup'] = 'Create new group';
$string['privacy:metadata:core_group'] = 'Enrol cohort plugin can create a new group or use an existing group to add all the members of the cohort.';

// WEB SERVICE.
// Strings for course stuff.
$string['coursenotexists']  = 'Course does not exist.';

// Strings for cohort stuff.
$string['cohortnotexists']              = 'Cohort does not exist.';
$string['cohortnotavailableatcontext']  = 'Cohort cannot be added to this course.';
$string['cohortnullcontext']            = 'Cohort cannot be added because the context is null.';

// Strings for role stuff.
$string['rolenotexists']            = 'Role does not exist.';
$string['rolenotassignablehere']    = 'Role is not assignable here.';

// Strings for group stuff.
$string['groupnotexists']   = 'Group does not exist.';

// Strings for capabilities stuff.
$string['usermissingrequiredcapability']    = 'User is missing the required capability \'{$a}\' at the course context.';
$string['usermissinganycapability']         = 'User is missing one of the following capabilities at the course context: {$a}.';

// Strings for enrolment method stuff.
$string['enrolmentmethodnotavailable']  = 'Could not instantiate enrol_cohort.';

// Strings for status stuff.
$string['statusinvalid'] = 'Invalid status {$a}. Possible statuses are: 0 - active, 1 - not active.';

// Strings for instance.
$string['instancegroupnone']    = 'Enrol instance group none.';
$string['instancenotexists']    = 'Unknown enrolment instance.';

// Strings for webservice function add_instance.
$string['addinstance:201']              = 'Cohort enrolment instance added.';
$string['addinstance:400']              = 'Could not add cohort enrolment instance.';
$string['addinstance:usingdefaultname'] = 'Using system generated name.';
$string['addinstance:courseissite']     = 'Can not add instance to the site course.';

// Strings for webservice function update_instance.
$string['updateinstance:200'] = 'Cohort enrolment instance updated.';
$string['updateinstance:400'] = 'Could not update cohort enrolment instance.';
$string['updateinstance:nochange'] = 'No changes were made to the cohort enrolment instance.';

// Strings for webservice function delete_instance.
$string['deleteinstance:200'] = 'Cohort enrolment instance deleted.';
$string['deleteinstance:400'] = 'Could not delete cohort enrolment instance.';

// Strings for webservice function get_instances.
$string['getinstance:200'] = 'Found {$a->numberofenrolmentinstances} cohort enrolment instances for the course with id {$a->courseid}.';
$string['getinstances:200'] = 'Found {$a->numberofenrolmentinstances} cohort enrolment instances in {$a->numberofcourses} courses (All courses).';
$string['getinstances:400'] = 'Could not get cohort enrolment instances.';
$string['getinstances:courseissite']    = 'Can not get instances for the site course.';

// String for an unknown HTTP status code.
$string['unknownstatuscode'] = 'Unknown status code {$a}.';

// Strings for exceptions.
$string['objectnotfound']               = 'Object does not exist!';
$string['objectnotfound:message']       = 'Could not find {$a->object} with {$a->key} {$a->value}';
$string['invalidcourse']                = 'Course is invalid!';
$string['invalidcourse:issite']         = 'Can not add instance to the site course.';
$string['unavailableatcontext']         = 'Object is not available at this context!';
$string['unavailableatcontext:message'] = '{$a->object} with id {$a->id} is not {$a->word} at this context.';
$string['invalidstatus']                = 'Invalid enrolment instance status!';
$string['invalidstatus:message']        = 'Possible values for enrolment instance status are: {$a->enabled} - Enabled, {$a->disabled} - Disabled. Got {$a->actual}.';
$string['cohortenrolmethodnotavailable']    = 'The cohort enrolment method is not available.';
$string['enrolcohortalreadysyncedwithrole']         = 'A cohort enrol instance for this role already exists';
$string['enrolcohortalreadysyncedwithrole:message'] = 'A cohort enrol instance with id {$a->enrolid} is already synchronised with the role {$a->roleid}';

$string['enrol_exclusions'] = 'Enrollment Exclusions';
$string['userid'] = 'User ID';
$string['usernotfound'] = 'User not found!';
$string['excludedcourses'] = 'Excluded courses';