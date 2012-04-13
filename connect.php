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
require_once($CFG->libdir . '/moodlelib.php');
require_once($CFG->libdir . '/weblib.php');

# helpmenow library
require_once(dirname(__FILE__) . '/lib.php');

# require login
require_login(0, false);

# get our parameters
$requestid = required_param('requestid', PARAM_INT);
$connect = optional_param('connect', 0, PARAM_INT);

# get the request
$request = new helpmenow_request($requestid);

if ($connect) {     # for the helper/requested_user
    if (isset($request->queueid)) {     # queue request
        # check privileges
        $queue = new helpmenow_queue($request->queueid);
        if ($queue->get_privilege() !== HELPMENOW_QUEUE_HELPER) {
            # todo: print a permission failure message and exit
        }

        # new meeting
        $meeting = helpmenow_meeting::new_meeting($queue->plugin);

        # queue meetings are owned by the helper
        $meeting->owner_userid = $USER->id;
    } else if (isset($request->requested_userid)) {     # direct request
        # check privileges
        if ($USER->id !== $request->requested_userid) {
            # todo: print a wrong user permission failure message and exit
        }

        # new meeting
        $meeting = helpmenow_meeting::new_meeting();

        # direct requests are owned by the user who sent it
        $meeting->owner_userid = $request->userid;
    } else {
        # todo: "what the heck are you doing?" failure message and close
    }
    # update the request with the meetingid so we know its been accepted
    $request->meetingid = $meeting->id;
    $request->update();

    # get the description from the request and create the meeting
    $meeting->description = $request->description;
    $meeting->create();

    # add the requesting user to the meeting
    $meeting->add_user($request->userid);

    # connect user to the meeting
    $url = $meeting->connect_user();
    $meeting->update(); # new meeting
    redirect($url);
}

# for the helpee/requester

if ($USER->id !== $request->userid) {
    # todo: print a wrong user permission failure message and close
}

# if we have a meeting
if (isset($request->meetingid)) {
    # get the meeting
    $meeting = helpmenow_meeting::get_meeting($request->meetingid);

    # delete the request
    $request->delete();

    # connect user to the meeting
    $url = $meeting->connect_user();
    $meeting->update();
    redirect($url);
}

# title, navbar, and a nice box
$title = get_string('connect', 'block_helpmenow');
$nav = array(array('name' => $title));
print_header($title, $title, build_navigation($nav));

# set the last refresh so cron doesn't clean this up
$request->last_refresh = time();
$request->update();
# todo: display some sort of cancel link

# refresh after some configurable number of seconds
$refresh_url = new moodle_url();
$refresh_url->param('requestid', $request->id);
redirect($refresh_url->out(), get_string('please_wait', 'block_helpmenow'), $CFG->helpmenow_refresh_rate);

# footer
print_footer();

?>
