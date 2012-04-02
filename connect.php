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

# title and navbar
$title = get_string('connect', 'block_helpmenow');
$nav = array(array('name' => $title));
print_header($title, $title, build_navigation($nav));

# get the request
$request = new helpmenow_request($requestid);

if ($connect) {     # for the helper/requested_user
    if (isset($request->queueid)) {     # queue request
        # check privileges
        $queue = new helpmenow_queue($request->queueid);
        if ($queue->check_privilege !== HELPMENOW_QUEUE_HELPER) {
            # todo: print a permission failure message and exit
        }

        # create the meeting
        $meeting = helpmenow_meeting::create_meeting($queue->plugin);

        # queue meetings are owned by the helper
        $meeting->owner_userid = $USER->id;
    } else if (isset($request->requested_userid)) {     # direct request
        # check privileges
        if ($USER->id !== $request->requested_userid) {
            # todo: print a wrong user permission failure message and exit
        }

        # create the meeting
        $meeting = helpmenow_meeting::create_meeting();

        # update the request with the meetingid so we know its been accepted
        $request->meetingid = $meeting->id;
        $request->insert();

        # direct requests are owned by the user who sent it
        $meeting->owner_userid = $request->userid;
    } else {
        # todo: "what the heck are you doing?" failure message and close
    }

    $meeting->description = $request->description;

    # connect user to the meeting
    redirect($meeting->connect_user());
} else {            # for the helpee/requester
    if ($USER->id !== $request->userid) {
        # todo: print a wrong user permission failure message and close
    }

    # check if we have a meeting
    if (isset($request->meetingid)) {
        # get the meeting
        $meeting = helpmenow_meeting::get_meeting($request->meetingid);

        # delete the request
        $request->delete();

        # connect user to the meeting
        redirect($meeting->connect_user());
    } else {
        # set the last refresh so cron doesn't clean this up
        $request->last_refresh = time();
        # todo: display some sort of cancel link
        # todo: refresh after some configurable number of seconds
    }
}

# footer
print_footer();

?>
