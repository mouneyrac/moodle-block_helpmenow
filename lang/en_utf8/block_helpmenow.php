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
 * This is the english translation file for get_string() for all translatable 
 * text in the help me now block.
 *
 * @package     block_helpmenow
 * @copyright   2010 VLACS
 * @author      David Zaharee <dzaharee@vlacs.org>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'Help Me Now Block';
$string['Blockname'] = 'Help Me Now';
$string['helpmenow'] = 'Help Me Now';

# block
$string['new_request'] = 'Request Meeting';
$string['pending'] = 'Your pending request:';
$string['login'] = 'Log In';
$string['logout'] = 'Log Out';
$string['admin_link'] = 'Manage Queues';
$string['queue_na_short'] = 'Sorry, this queue is not available.';

# new_request.php
$string['new_request_heading'] = 'New Request';
$string['description'] = 'Description (140 characters or less)';

# connect.php
$string['connect'] = 'Connecting';
$string['please_wait'] = 'Please wait while we connect you...';

# admin.php / edit.php
$string['global_admin'] = 'Global Queues';
$string['course_admin'] = 'Queues: ';
$string['admin'] = 'Manage Queues';
$string['new_queue'] = 'Add Queue';
$string['weight'] = 'Weight';
$string['plugin'] = 'Plugin';
$string['helpers'] = 'Helpers';
$string['queue_edit'] = 'Edit Queue';
$string['global_link'] = 'Manage Global Queues';

# assign.php
$string['assign_title'] = 'Assign Helpers';
$string['assign_heading'] = 'Assigning helpers for: ';
$string['assigned_status'] = 'Is user a helper?';
$string['assigned_link'] = 'Update';
$string['assign'] = 'Assign as helper';
$string['unassign'] = 'Remove as helper';
$string['back'] = 'Back to queue management';

$string['auto_queue_desc'] = 'Course Queue';

# settings
# $string['settings_heading'] = 'Help Me Now Settings';
# $string['settings_heading_desc'] = '';  # todo
$string['settings_plugin'] = 'Default plugin';
$string['settings_plugin_desc'] = 'Default plugin queues should use';
$string['settings_meeting_timeout'] = 'Meeting timeout';
$string['settings_meeting_timeout_desc'] = "Time meeting is 'active' when using plugins that aren't able to track this";
$string['settings_request_timeout'] = 'Request timeout';
$string['settings_request_timeout_desc'] = 'Time before request is deemed abandoned';
$string['settings_refresh_rate'] = 'Request refresh rate';
$string['settings_refresh_rate_desc'] = 'Time between refreshes when waiting for a meeting';
$string['settings_autocreate_queue'] = 'Autocreate course queues';
$string['settings_autocreate_queue_desc'] = 'Automatically creates one course queue with the shortname of the course in each course';
$string['settings_autoadd_helpers'] = 'Autoadd course queue helpers';
$string['settings_autoadd_helpers_desc'] = 'When autocreate course queues, also add helpers to the queue who have the queue answer capability in the course';

# caps
$string['helpmenow:course_queue_answer'] = 'Answer requests in course queues';
$string['helpmenow:global_queue_answer'] = 'Answer requests in global queues';
$string['helpmenow:manage_queues'] = 'Manage queue creation and helper assigning';
$string['helpmenow:queue_ask'] = 'Ask for meetings in queues';

?>
