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
 * This script logs helpers in and out of queues.
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
$queueid = required_param('queueid', PARAM_INT);
$login = optional_param('login', 0, PARAM_INT);

# login/out the helper
$queue = helpmenow_queue::get_instance($queueid);
if ($login) {
    $queue->login();
    helpmenow_log($USER->id, 'logged_in', "queueid: {$queueid}");

    if ($queue->type === HELPMENOW_QUEUE_TYPE_INSTRUCTOR) {
        $meeting = helpmenow_meeting::new_instance($queue->plugin);
        $meeting->owner_userid = $USER->id;
        $meeting->description = $queue->name;  # get the description from the request
        $meeting->queueid = $queue->id;
        $meeting->create();
        $meeting->insert();

        $meeting->add_user();
        $meeting->update();

        # add meetingid to helper
        $queue->helper[$USER->id]->meetingid = $meeting->id;
        $queue->helper[$USER->id]->update();

        $launch = new moodle_url($CFG->wwwroot . '/blocks/helpmenow/launch.php');
        $launch->param('meetingid', $meeting->id);
        redirect($launch->out());
    }
} else {
    $queue->logout();
    helpmenow_log($USER->id, 'logged_out', "queueid: {$queueid}");
    if ($queue->type === HELPMENOW_QUEUE_TYPE_INSTRUCTOR) {
        $queue->helper[$USER->id]->meetingid = 0;
        $queue->helper[$USER->id]->update();
        helpmenow_fatal_error('You may now close this window', true, true);
    }
}

# we're done, now redirect
$helpmenow_url = new moodle_url("$CFG->wwwroot/blocks/helpmenow/helpmenow.php");
$helpmenow_url = $helpmenow_url->out();
redirect($helpmenow_url);

?>
