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
 * This script handles opening subplugin meetings
 *
 * @package     block_helpmenow
 * @copyright   2012 VLACS
 * @author      David Zaharee <dzaharee@vlacs.org>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

# moodle stuff
require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');

# helpmenow stuff
require_once(dirname(__FILE__) . '/lib.php');
require_once(dirname(__FILE__) . '/meeting.php');

# require login
require_login(0, false);

# get our parameters
$meetingid = required_param('meetingid', PARAM_INT);

if ($meetingid != 0) {
    # get the meeting
    $meeting = helpmenow_meeting::get_instance($meetingid);

    # check to make sure this user belongs in this meeting
    if (!isset($meeting->meeting2user[$USER->id])) {
        helpmenow_fatal_error(get_string('permission_error', 'block_helpmenow'));
    }
    
    $connect_url = $meeting->connect();
    $pluginclass = helpmenow_plugin::get_class($meeting->plugin);
    if (isset($meeting->queueid)) {
        $queue = helpmenow_queue::get_instance($meeting->queueid);
    }
} else {
    $connect_url = "/";
    $pluginclass = "helpmenow_plugin";
}

$body = ($meetingid != 0) ? "meeting onload=\"meeting = window.open('$connect_url', 'meeting', 'menubar=0,location=0,scrollbars,resizable,height=400,width=700');\"" : "";
print_header('', '', '', '', '', true, '&nbsp;', '', false, $body);

# connect message and nopopup link
print_box_start();
echo "<h2>" . $plugin::connect_message() . "</h2>";
echo "<p align='center'>" . get_string('nopopup', 'block_helpmenow') . "<a href='$connect_url'>" . get_string('click_here', 'block_helpmenow') . "</a></p>";
print_box_end();

echo "<div>";

# general stuff & configurable message
if (strlen($CFG->helpmenow_connect_message) or (isset($queue) and $queue->get_privilege() == HELPMENOW_QUEUE_HELPER)) {
    echo "<div style=\"width:49%;display:inline-block;\">";
    # todo: add student info for helpers
    if (strlen($CFG->helpmenow_connect_message)) {
        print_box($CFG->helpmenow_connect_message);
    }
    echo "</div>";
}
$setting = "helpmenow_{$meeting->plugin}_connect_message";
if (strlen($CFG->$setting)) {
    echo "<div style=\"width:49%;display:inline-block;\">";
    print_box($CFG->$setting);
    echo "</div>";
}

echo "</div>";

?>
