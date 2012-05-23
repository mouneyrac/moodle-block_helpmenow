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
 * This script handles refreshing the requesting user's page until a helper or
 * the requested user accepts. It also handles the accepting by said helper or
 * requested user.
 *
 * @package     block_helpmenow
 * @copyright   2012 VLACS
 * @author      David Zaharee <dzaharee@vlacs.org>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

# moodle stuff
require_once((dirname(dirname(dirname(__FILE__)))) . '/config.php');

# helpmenow library
require_once(dirname(__FILE__) . '/lib.php');

# require login
require_login(0, false);

# get our parameters
$requestid = optional_param('requestid', 0, PARAM_INT);
$meetingid = optional_param('meetingid', 0, PARAM_INT);
$connect = optional_param('connect', 0, PARAM_INT);

# get the request
if (!$request = helpmenow_request::get_instance($requestid)) {
    helpmenow_fatal_error(get_string('missing_request', 'block_helpmenow'));
}

# launch.php
$launch = new moodle_url($CFG->wwwroot . '/blocks/helpmenow/launch.php');

# for the helper
if ($connect) {
    # check privileges
    $queue = helpmenow_queue::get_instance($request->queueid);
    if ($queue->get_privilege() !== HELPMENOW_QUEUE_HELPER) {
        helpmenow_fatal_error(get_string('permission_error', 'block_helpmenow'));
    }

    if (isset($request->meetingid)) {
        helpmenow_fatal_error(get_string('too_slow', 'block_helpmenow'));
    }

    # new meeting
    $meeting = helpmenow_meeting::new_instance($queue->plugin);
    $meeting->owner_userid = $USER->id;
    $meeting->description = $request->description;  # get the description from the request
    $meeting->queueid = $request->queueid;
    $meeting->create();
    $meeting->insert();

    # add both users to the meeting
    $meeting->add_user();
    $meeting->add_user($request->userid);
    $meeting->update();

    # update the request with the meetingid so we know its been accepted
    $request->meetingid = $meeting->id;
    $request->update();

    $only_request = true;
    foreach ($queue->request as $r) {
        if ($r->id == $requestid) {
            continue;
        }
        $only_request = false;
        break;
    }

    if ($only_request) {
        foreach ($queue->helper as $h) {
            $h->last_action = 0;
            $h->update();
        }
    } else {
        $queue->helper[$USER->id]->last_action = 0;
        $queue->helper[$USER->id]->update();
    }

    # log
    helpmenow_log($USER->id, 'answered_request', "requestid: {$requestid}; meetingid: {$meeting->id}");

    $launch->param('meetingid', $meeting->id);
    redirect($launch->out());
}

# for the helpee/requester

if ($USER->id !== $request->userid) {
    helpmenow_fatal_error(get_string('permission_error', 'block_helpmenow'));
}

# if we have a meeting
if (isset($request->meetingid)) {
    # delete the request
    $request->delete();

    # log
    helpmenow_log($USER->id, 'connected_to_meeting', "requestid: {$request->id}; meetingid: {$meetingid}");

    # connect user to the meeting
    $launch->param('meetingid', $meeting->id);
    redirect($launch->out());
}

# check to make sure we still have a helper
$queue = helpmenow_queue::get_instance($request->queueid);

# title, navbar, and a nice box
$title = get_string('connect', 'block_helpmenow');
$nav = array(array('name' => $title));
$refresh = '';
if ($queue->check_available()) {
    $refresh = "<meta http-equiv=\"refresh\" content=\"{$CFG->helpmenow_refresh_rate}\" />";
}
print_header($title, $title, build_navigation($nav), '', $refresh);

if (!$queue->check_available()) {
    $request->delete();

    if (strlen($CFG->helpmenow_missing_helper)) {
        $message = $CFG->helpmenow_missing_helper;
    } else {
        $message = get_string('missing_helper', 'block_helpmenow');
    }
    helpmenow_fatal_error($message, false);
}

# set the last refresh so cron doesn't clean this up
$request->last_refresh = time();
$request->update();
# todo: display some sort of cancel link

print_box_start('generalbox');
echo "<p align='center'>" . get_string('please_wait', 'block_helpmenow') . "</p>" .
    "<p align='center'>" . get_string('pending', 'block_helpmenow') . "<br />&quot;" .
    $request->description . "&quot;</p>";

# footer
//print_footer();

?>
