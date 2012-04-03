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

# require login
require_login(0, false);

# get our parameters
$queueid = optional_param('queueid', 0, PARAM_INT);
$courseid = optional_param('courseid', 0, PARAM_INT);

# COURSE and urls
$COURSE = get_record('course', 'id', $courseid);
$course_url = new moodle_url("$CFG->wwwroot/course/view.php");
$course_url->param('id', $COURSE->id);
$course_url = $course_url->out();
$admin_url = new moodle_url("$CFG->wwwroot/block/helpmenow/admin.php");
$admin_url->param('courseid', $courseid);
$admin_url = $admin_url->out();

# contexts and cap check
$sitecontext = get_context_instance(CONTEXT_SYSTEM, SITEID);
if (!has_capability(HELPMENOW_CAP_ADMIN, $sitecontext)) {
    redirect($course_url);
}

# title and navbar
$title = get_string('queue_edit', 'block_helpmenow');
$nav = array(
    array('name' => get_string('admin', 'block_helpmenow'), 'link' => $admin_url),
    array('name' => $title),
);
print_header($title, $title, build_navigation($nav));

# form
$form = new helpmenow_queue_form();
if ($form->is_cancelled()) {                # cancelled
    redirect($admin_url);
} else if ($data = $form->get_data()) {     # submitted
    $queue = new helpmenow_queue(null, (object) $data);
    if ($queueid) {
        $queue->id = $queueid;
        $queue->update();
    } else {
        $queue->insert();
    }

    # redirect back to admin.php
    redirect($admin_url);
} else {                                    # print form
    if ($queueid) {     # existing queue
        $queue = new helpmenow_queue($queueid);
        $toform = array(
            'queueid' => $queueid,
            'courseid' => $courseid,
            'name' => $q->name,
            'description' => $q->description,
            'plugin', $q->plugin,
            'weight', $q->weight,
        );
    } else {            # new queue
        $toform = array(
            'queueid' => $queueid,
            'courseid' => $courseid,
        );
    }

    $form->set_data($toform);
    $form->display();
}

# footer
print_footer();

?>
