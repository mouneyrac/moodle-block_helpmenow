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
 * This script handles the queue edit form.
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
require_once(dirname(__FILE__) . '/form.php');

# require login
require_login(0, false);

# get our parameters
$queueid = required_param('queueid', PARAM_INT);
$courseid = optional_param('courseid', 0, PARAM_INT);
$userid = optional_param('userid', 0, PARAM_INT);
$assign = optional_param('assign', 0, PARAM_INT);

# COURSE
$COURSE = get_record('course', 'id', $courseid);

# urls
$this_url = new moodle_url();
$this_url->param('queueid', $queueid); 
$this_url->param('courseid', $courseid); # don't turn into a string yet, will be used for assign/unassign links
$admin_url = new moodle_url("$CFG->wwwroot/blocks/helpmenow/admin.php");
$admin_url->param('courseid', $courseid);
$admin_url = $admin_url->out();

# context and cap check
$sitecontext = get_context_instance(CONTEXT_SYSTEM, SITEID);
if (!has_capability(HELPMENOW_CAP_MANAGE, $sitecontext)) {
    redirect($course_url);
}

$queue = new helpmenow_queue($queueid);
$context = get_context_instance_by_id($queue->contextid);
$cap = ($context->contextlevel == CONTEXT_SYSTEM) ? HELPMENOW_CAP_GLOBAL_QUEUE_ANSWER : HELPMENOW_CAP_COURSE_QUEUE_ANSWER;

if ($userid) {      # assigning/unassigning users
    if ($assign) {  # assigning
        if (isset($queue->helper[$userid])) {
            # todo: error, already assigned
        } else {
            # double check they can be assigned
            if (has_capability($cap, $context)) {
                $helper = (object) array(
                    'queueid' => $queue->id,
                    'userid' => $userid,
                    'isloggedin' => 0,
                );
                $helper = new helpmenow_helper(null, $helper);
                $helper->insert();
                $queue->helper[$userid] = $helper;
            } else {
                # todo: error, can't be assigned
            }
        }
    } else {        # unassigning
        if (isset($queue->helper[$userid])) {
            $queue->helper[$userid]->delete();
            unset($queue->helper[$userid]);
        } else {
            # todo: error, can't unassign user who isn't assigned
        }
    }
    # redirect back to the list
    redirect($this_url->out());
} 

# title, navbar, and a nice box
$title = get_string('assign_title', 'block_helpmenow');
$nav = array(
    array('name' => get_string('admin', 'block_helpmenow'), 'link' => $admin_url),
    array('name' => $title),
);
print_header($title, $title, build_navigation($nav));
print_box_start('generalbox centerpara');

print_heading(get_string('assign_heading', 'block_helpmenow') . $queue->name);

$users = get_users_by_capability($context, $cap, 'u.id, u.username, u.firstname, u.lastname', '', '', '', '', '', false);

# todo: if we don't get any users we should print a helpful messages about capabilities

# start setting up the table
$table = (object) array(
    'head' => array(
        get_string('name'),
        get_string('username'),
        get_string('assigned_status', 'block_helpmenow'),
        get_string('assigned_link', 'block_helpmenow'),
    ),
    'data' => array(),
);

foreach ($users as $u) {
    if (isset($queue->helper[$u->id])) {
        $assign = 0;
        $status = get_string('yes');
        $link_text = get_string('unassign', 'block_helpmenow');
    } else {
        $assign = 1;
        $status = get_string('no');
        $link_text = get_string('assign', 'block_helpmenow');
    }
    $this_url->param('userid', $u->id);
    $this_url->param('assign', $assign);
    $tmp = $this_url->out();
    $table->data[] = array(
        "$u->lastname, $u->firstname",
        $u->username,
        $status,
        "<a href='$tmp'>$link_text</a>",
    );
}

print_table($table);

$back = get_string('back', 'block_helpmenow');
echo "<p><a href='$admin_url'>$back</a></p>";

print_box_end();

# footer
print_footer();

?>