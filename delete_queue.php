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
 * This script handles deleting queues
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
# forms
require_once(dirname(__FILE__) . '/form.php');

# require login
require_login(0, false);

# get our parameters
$queueid = optional_param('queueid', 0, PARAM_INT);
$courseid = optional_param('courseid', 0, PARAM_INT);
$delete = optional_param('delete', 0, PARAM_INT);

# COURSE
$COURSE = get_record('course', 'id', $courseid);

# urls
$admin_url = new moodle_url("$CFG->wwwroot/blocks/helpmenow/admin.php");
$admin_url->param('courseid', $courseid);
$admin_url = $admin_url->out();
$delete_url = new moodle_url();
$delete_url->param('queueid', $queueid);
$delete_url->param('courseid', $courseid);
$delete_url->param('delete', 1);
$delete_url = $delete_url->out();

# contexts and cap check
$sitecontext = get_context_instance(CONTEXT_SYSTEM, SITEID);
$context = get_context_instance(CONTEXT_COURSE, $courseid);
if (!has_capability(HELPMENOW_CAP_MANAGE, $sitecontext)) {
    redirect($course_url);
}

$queue = helpmenow_queue::get_instance($queueid);

if ($delete) {
    $queue->delete();
    redirect($admin_url);
}

# title, navbar, and a nice box
$title = get_string('queue_edit', 'block_helpmenow');
$nav = array(
    array('name' => get_string('admin', 'block_helpmenow'), 'link' => $admin_url),
    array('name' => $title),
);
print_header($title, $title, build_navigation($nav));
print_box_start('generalbox centerpara');

notice_yesno(get_string('confirm_delete', 'block_helpmenow') . $queue->name, $delete_url, $admin_url);

print_box_end();

# footer
print_footer();

?>
