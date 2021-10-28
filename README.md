# Enrol AirTime
This plugin triggers is a modification to the cohort sync enrollment plugin

## About this repository
The branch master contains all current code for this repository. Individual issue branches are related to specific development issues.

## General Documentation
- Enable this enrollment plugin
- Create a cohort
- Add this enrollment method to a course and select the cohort related to it
- To add exclusions, there is an 'Enrollment Exclusions' button in Site Admin -> Plugins -> Enrolments -> AirTime Sync

### Webservices
####enrol_airtime_add_exclusion
Accepts a Moodle user id and a Moodle course id as parameters to set an exclusion for a user. Returns true or an exception.
####enrol_airtime_remove_exclusion
Accepts a Moodle user id and a Moodle course id as parameters to remove an exclusion for a user. Returns true or an exception.
####enrol_airtime_get_exclusions
Accepts a Moodle user id and returns an array of course ids that are excluded for that user.
