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
 * Cohort enrolment method not available exception for enrol_airtime.
 *
 * @package     enrol_airtime
 * @author      Donald Barrett <donald.barrett@learningworks.co.nz>
 * @copyright   2018 onwards, LearningWorks ltd
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace enrol_airtime\exceptions;

// No direct access.
defined('MOODLE_INTERNAL') || die();

use enrol_airtime\tools;

class cohort_enrol_method_not_available_exception extends \moodle_exception {
    public function __construct() {
        parent::__construct('cohortenrolmethodnotavailable', tools::COMPONENT_NAME);
    }
}