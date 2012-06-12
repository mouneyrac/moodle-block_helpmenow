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
$string['blockname'] = 'Help Me Now';
$string['helpmenow'] = 'Help Me Now';

# plugin strings
$plugins = get_list_of_plugins('plugins', '', dirname(dirname(dirname(__FILE__))));
foreach ($plugins as $pluginname) {
    $path = dirname(dirname(dirname(__FILE__))) . "/plugins/$pluginname/lang/en_utf8.php";
    if (file_exists($path)) {
        require($path);
    }
}

# block
$string['new_request'] = '(Click to get help)';
$string['pending'] = '(Click to view your request)';
$string['queue_na_short'] = '(Currently Offline)';
$string['helper_link'] = 'Open My Queues';
$string['admin_link'] = 'Manage Queues';
$string['in_office'] = '';
$string['out_office'] = 'Out of Office |';
$string['enter_office'] = 'Enter Office';
$string['leave_office'] = 'Leave Office';

# new_request.php
$string['new_request_heading'] = 'New Request';
$string['description'] = 'Description (140 characters or less)';
$string['submitrequest'] = 'Submit request';

# connect.php
$string['connect'] = 'Connecting';
$string['missing_helper'] = "We're sorry, there are no available helpers at this time. Please try again later, or contact us by calling 603.778.2500 and selecting option #2, or by <a href='https://courses.vlacs.org/blocks/helpdesk/new.php'>submitting a help desk ticket here.</a>";
$string['please_wait'] = 'Helpers may currently be helping other users. Please wait while we attempt to connect you.';
$string['missing_request'] = 'Error: request does not exist.';
$string['too_slow'] = 'Another helper has already answered this request.';
$string['your_request'] = 'Your pending request:';

# admin.php / edit.php / delete.php
$string['global_admin'] = 'Global Queues';
$string['course_admin'] = 'Queues: ';
$string['admin'] = 'Manage Queues';
$string['new_queue'] = 'Add Queue';
$string['weight'] = 'Weight';
$string['plugin'] = 'Plugin';
$string['helpers'] = 'Helpers';
$string['queue_edit'] = 'Edit Queue';
$string['global_link'] = 'Manage Global Queues';
$string['confirm_delete'] = 'Confirm you wish to delete queue ';

# assign.php
$string['assign_title'] = 'Assign Helpers';
$string['assign_heading'] = 'Assigning helpers for: ';
$string['assigned_status'] = 'Is user a helper?';
$string['assigned_link'] = 'Update';
$string['assign'] = 'Assign as helper';
$string['unassign'] = 'Remove as helper';
$string['back'] = 'Back to queue management';

# helper interface
$string['inactive_message'] = 'Due to inactivity you will soon be logged out of the following queues:';
$string['inactive_link'] = 'Remain Logged In';
$string['logged_in_helpers'] = 'Logged In Helpers: ';
$string['none'] = 'None';
$string['meetings'] = 'Current Meetings';
$string['helper'] = 'helper';
$string['requests'] = 'Requests';
$string['no_requests'] = 'There are currently no requests in queue.';
$string['no_meetings'] = 'There are currently no meetings.';
$string['login'] = 'Log In';
$string['logout'] = 'Log Out';
$string['loggedout'] = 'You are currently logged out.';
$string['loggedin'] = '';

$string['auto_queue_desc'] = 'Course Queue';

# launch.php
$string['connecting'] = 'You are being connected to the meeting. Once it opens you may close this window.';
$string['nopopup'] = 'If the meeting window does not appear, please enable popups or ';
$string['click_here'] = 'click here.';
$string['participants'] = 'Participants:';

# settings
# $string['settings_heading'] = 'Help Me Now Settings';
# $string['settings_heading_desc'] = '';
$string['settings_plugin'] = 'Default plugin';
$string['settings_plugin_desc'] = 'Default plugin queues should use';
$string['settings_block_message'] = 'Block message';
$string['settings_block_message_desc'] = 'Message displayed in the block';
$string['settings_meeting_timeout'] = 'Meeting timeout';
$string['settings_meeting_timeout_desc'] = "Time meeting is 'active' when using plugins that aren't able to track this";
$string['settings_request_timeout'] = 'Request timeout';
$string['settings_request_timeout_desc'] = 'Time before request is deemed abandoned';
$string['settings_refresh_rate'] = 'Request refresh rate';
$string['settings_refresh_rate_desc'] = 'Time in seconds between refreshes when waiting for a meeting';
$string['settings_helper_refresh_rate'] = 'Helper refresh rate';
$string['settings_helper_refresh_rate_desc'] = 'Time in seconds between refreshes on helper interface';
$string['settings_autocreate_queue'] = 'Autocreate instructor queues';
$string['settings_autocreate_queue_desc'] = 'Automatically creates an instructor queue the first time an instructor loads the block';
$string['settings_autoadd_helpers'] = 'Autoadd course queue helpers';
$string['settings_autoadd_helpers_desc'] = 'When autocreating course queues, also add helpers to the queue who have the queue answer capability in the course';
$string['settings_helper_refresh_timeout'] = 'Helper refresh timeout';
$string['settings_helper_refresh_timeout_desc'] = 'Time delay in minutes before logging out a helper who has not refreshed the helper interace page';
$string['settings_helper_activity_timeout'] = 'Helper activity timeout';
$string['settings_helper_activity_timeout_desc'] = 'Time delay in minutes before logging out a helper who has not answered pending requests';
$string['settings_helper_activity_warning'] = 'Helper activity warning';
$string['settings_helper_activity_warning_desc'] = 'Time delay in minutes before warning a helper they will be logged out';

# caps
$string['helpmenow:course_queue_answer'] = 'Answer requests in course queues';
$string['helpmenow:global_queue_answer'] = 'Answer requests in global queues';
$string['helpmenow:manage_queues'] = 'Manage queue creation and helper assigning';
$string['helpmenow:queue_ask'] = 'Ask for meetings in queues';

$string['permission_error'] = 'You do not have permission to view this page. You may have been linked to it in error.';

?>
