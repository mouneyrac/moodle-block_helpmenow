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

require_once((dirname(dirname(dirname(__FILE__)))) . '/config.php');
require_once(dirname(__FILE__) . '/lib.php');
helpmenow_plugin::get_plugins();

# require login
require_login(0, false);

# contexts and cap check
$sitecontext = get_context_instance(CONTEXT_SYSTEM, SITEID);
if (!has_capability(HELPMENOW_CAP_MANAGE, $sitecontext)) {
    redirect();
}

# title, navbar, and a nice box
$title = "Administrators' Hallway";
$nav = array(array('name' => $title));
print_header($title, $title, build_navigation($nav));
print_box_start('generalbox centerpara');

print_heading($title);

$instructors = get_records_sql("
    SELECT *
    FROM {$CFG->prefix}block_helpmenow_user hu
    JOIN {$CFG->prefix}user u ON u.id = hu.userid
");

# start setting up the table
# todo: plugin abstraction
$table = (object) array(
    'head' => array(
        get_string('name'),
        'MOTD',
        'Logged In?',
        'GoToMeeting',
        'WizIQ',
    ),
    'data' => array(),
);

usort($instructors, function($a, $b) {
    if (!($a->isloggedin xor $b->isloggedin)) {
        return strcmp(strtolower("$a->lastname $a->firstname"), strtolower("$b->lastname $b->firstname"));
    }
    return $a->isloggedin ? -1 : 1;
});

foreach ($instructors as $i) {
    if ($i->isloggedin) {
        $login_status = "Yes";

        # gtm
        if (!$user2plugin = get_record('block_helpmenow_user2plugin', 'userid', $i->userid, 'plugin', 'gotomeeting')) {
            $meeting_link = 'Not Found';
        } else {
            $user2plugin = new helpmenow_user2plugin_gotomeeting(null, $user2plugin);
            $meeting_link = "<a href=\"$user2plugin->join_url\" target=\"_blank\">Wander In</a>";
        }

        # wiziq
        if (!$wiziq_u2p = get_record('block_helpmenow_user2plugin', 'userid', $i->userid, 'plugin', 'wiziq')) {
            $wiziq_meeting_link = 'Not Found';
        } else {
            $wiziq_u2p = new helpmenow_user2plugin_wiziq(null, $wiziq_u2p);
            $join_url = new moodle_url("$CFG->wwwroot/blocks/helpmenow/plugins/wiziq/join.php");
            $join_url->param('classid', $wiziq_u2p->class_id);
            $join_url = $join_url->out();
            $wiziq_meeting_link = "<a href=\"$join_url\" target=\"_blank\">Wander In</a>";
        }
    } else {
        $login_status = "No";
        $meeting_link = "N/A";
        $wiziq_meeting_link = "N/A";
    }
    $table->data[] = array(
        fullname($i),
        $i->motd,
        $login_status,
        $meeting_link,
        $wiziq_meeting_link,
    );
}

print_table($table);

print_box_end();

# footer
print_footer();

?>
