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

# VLACS
require_once($CFG->dirroot . '/blocks/geniusapis/lib.php');

# require login
require_login(0, false);

# get our parameters
$meetingid = required_param('meetingid', PARAM_INT);
$plugin = optional_param('plugin', '', PARAM_TEXT);

if ($meetingid != 0) {
    # get the meeting
    $meeting = helpmenow_meeting::get_instance($meetingid);

    if (isset($meeting->queueid)) {
        $queue = helpmenow_queue::get_instance($meeting->queueid);
    }
    $helper = (isset($queue) and $queue->get_privilege() == HELPMENOW_QUEUE_HELPER);

    # check to make sure this user belongs in this meeting
    if (!isset($meeting->meeting2user[$USER->id])) {
        # if they're a helper for this queue, add them to it
        if ($helper) {
            $meeting->add_user();
            $meeting->update();
            helpmenow_log($USER->id, 'joined_meeting', "meetingid: {$meetingid}");
        } else {
            helpmenow_fatal_error(get_string('permission_error', 'block_helpmenow'));
        }
    }
    
    $connect_url = $meeting->connect();
    $pluginclass = helpmenow_plugin::get_class($meeting->plugin);
} else {
    $connect_url = "/";
    $pluginclass = helpmenow_plugin::get_class($plugin);
    $meeting = (object) array(
        'plugin' => $plugin,
    );
}

$body = ($meetingid != 0) ? "meeting onload=\"meeting = window.open('$connect_url', 'meeting', 'menubar=0,location=0,scrollbars,resizable,height=400,width=700');\"" : "";
print_header('', '', '', '', '', true, '&nbsp;', '', false, $body);

# connect message and nopopup link
print_box_start();
echo "<h2>" . $pluginclass::connect_message() . "</h2>";
echo "<p align='center'>" . get_string('nopopup', 'block_helpmenow') . "<a href='$connect_url'>" . get_string('click_here', 'block_helpmenow') . "</a></p>";
print_box_end();

echo "<div>";

# general stuff & configurable message
$firstcol = true;
if ((isset($CFG->helpmenow_connect_message) and strlen($CFG->helpmenow_connect_message)) or $helper) {
    $firstcol = false;
    echo "<div style=\"width:49%;display:inline-block;padding-right:1%;vertical-align:top;\">";
    if ($helper) {
        print_box_start();
        echo get_string('participants', 'block_helpmenow') . "<ul>";
        $userurl = new moodle_url("$CFG->wwwroot/user/view.php");
        foreach ($meeting->meeting2user as $m2u) {
            # helper doesn't need their own info...
            if ($m2u->userid == $USER->id) {
                continue;
            }
            $userurl->param('id', $m2u->userid);
            $user = get_record('user', 'id', $m2u->userid);
            $name = fullname($user);
            echo "<li>" . link_to_popup_window($userurl->out(), 'user', $name, 400, 700, null, null, true);
            echo "</li>";
        }
        echo "</ul>";
        print_box_end();
    }
    if (isset($CFG->helpmenow_connect_message) and strlen($CFG->helpmenow_connect_message)) {
        print_box($CFG->helpmenow_connect_message);
    }
    echo "</div>";
}
$setting = "helpmenow_{$meeting->plugin}_connect_message";
if (isset($CFG->$setting) and strlen($CFG->$setting)) {
    $side = 'right';
    if (!$firstcol) {
        $side = 'left';
    }
    echo "<div style=\"width:49%;display:inline-block;padding-$side:1%;vertical-align:top;\">";
    print_box($CFG->$setting);
    echo "</div>";
}

echo "</div>";

?>
