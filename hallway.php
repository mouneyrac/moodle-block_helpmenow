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
 * This script is a quick 'n' dirty list of Instructor queues, who's online,
 * and meeting links.
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

# contexts and cap check
$sitecontext = get_context_instance(CONTEXT_SYSTEM, SITEID);
$context = get_context_instance(CONTEXT_COURSE, $COURSE->id);
if (!has_capability(HELPMENOW_CAP_MANAGE, $sitecontext)) {
    redirect($course_url);
}

# title, navbar, and a nice box
$title = get_string('admin', 'block_helpmenow');
$nav = array(array('name' => $title));
print_header($title, $title, build_navigation($nav));
print_box_start('generalbox centerpara');

print_heading("Administrators' Hallway");

$sql = "
    SELECT q.*
    FROM {$CFG->prefix}block_helpmenow_queue q
    JOIN {$CFG->prefix}block_helpmenow_helper h ON h.queueid = q.id
    JOIN {$CFG->prefix}user u ON h.userid = u.id
    LEFT JOIN {$CFG->prefix}block_helpmenow_meeting m ON m.id = h.meetingid
    WHERE q.type = '".HELPMENOW_QUEUE_TYPE_INSTRUCTOR."'
    ORDER BY h.isloggedin DESC, u.lastname ASC
";
$queues = helpmenow_queue::objects_from_records(get_records_sql($sql));

# start setting up the table
$table = (object) array(
    'head' => array(
        get_string('name'),
        'MOTD',
        'Logged In?',
        'Meeting',
    ),
    'data' => array(),
);

foreach ($queues as $q) {
    if (reset($q->helper)->isloggedin) {
        $login_status = "Yes";

        $meeting = helpmenow_meeting::get_instance(reset($q->helper)->meetingid);
        $meeting_url = $meeting->connect();
        $meeting_link = "<a href=\"$meeting_url\" target=\"_blank\">Wander In</a>";
    } else {
        $login_status = "No";
        $meeting_link = "N/A";
    }
    $table->data[] = array(
        $q->name,
        $q->description,
        $login_status,
        $meeting_link,
    );
}

print_table($table);

print_box_end();

# footer
print_footer();

?>
