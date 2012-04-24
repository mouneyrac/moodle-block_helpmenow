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
 * This script handles administration of queues.
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
$courseid = optional_param('courseid', 0, PARAM_INT);

# COURSE
$COURSE = get_record('course', 'id', $courseid);

# assign.php and edit.php urls
$assign = new moodle_url("$CFG->wwwroot/blocks/helpmenow/assign.php");
$assign->param('courseid', $courseid);
$edit = new moodle_url("$CFG->wwwroot/blocks/helpmenow/edit.php");
$edit->param('courseid', $courseid);

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

if ($courseid == SITEID) {    # global queues
    print_heading(get_string('global_admin', 'block_helpmenow'));
    $queues = helpmenow_queue::get_queues_by_context(array($sitecontext->id));
    $tmp = '';
} else {            # course queues
    print_heading(get_string('course_admin', 'block_helpmenow') . $COURSE->fullname);
    $queues = helpmenow_queue::get_queues_by_context(array($context->id));
    $global_url = new moodle_url();
    $global_url->param('courseid', SITEID);
    $global_url = $global_url->out();
    $global = get_string('global_link', 'block_helpmenow');
    $tmp = "<a href='$global_url'>$global</a> | ";
}

# start setting up the table
$table = (object) array(
    'head' => array(
        get_string('name'),
        get_string('description'),
        get_string('weight', 'block_helpmenow'),
        get_string('plugin', 'block_helpmenow'),
        get_string('helpers', 'block_helpmenow'),
    ),
    'data' => array(),
);
    
foreach ($queues as $q) {
    $assign->param('queueid', $q->id);
    $assign_url = $assign->out();
    $helper_count = count($q->helper);
    $edit->param('queueid', $q->id);
    $edit_url = $edit->out();

    $table->data[] = array(
        "<a href='$edit_url'>$q->name</a>",
        $q->description,
        $q->weight,
        $q->plugin,
        "<a href='$assign_url'>$helper_count</a>",
    );
}

print_table($table);

# link to add queue
$edit->param('queueid', 0);
$edit_url = $edit->out();
$new_queue_text = get_string('new_queue', 'block_helpmenow');
echo "<p>$tmp<a href='$edit_url'>$new_queue_text</a></p>";

print_box_end();

# footer
print_footer();

?>
