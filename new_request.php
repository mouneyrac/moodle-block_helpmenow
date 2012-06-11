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
 * This script handles the new request form.
 *
 * @package     block_helpmenow
 * @copyright   2012 VLACS
 * @author      David Zaharee <dzaharee@vlacs.org>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

# moodle stuff
require_once((dirname(dirname(dirname(__FILE__)))) . '/config.php');

# helpmenow library & forms
require_once(dirname(__FILE__) . '/lib.php');
require_once(dirname(__FILE__) . '/form.php');

# require login
require_login(0, false);

# get our parameters
$queueid = optional_param('queueid', 0, PARAM_INT);
$userid = optional_param('userid', 0, PARAM_INT);

$connect = new moodle_url("$CFG->wwwroot/blocks/helpmenow/connect.php");

# this case is an instructor clicking on a student's name in the block
if ($userid and ($userid != $USER->id)) {
    $sql = "
        SELECT q.*
        FROM {$CFG->prefix}block_helpmenow_queue q
        WHERE q.userid = $USER->id
    ";
    if (!$queue = get_record_sql($sql)) {
        helpmenow_fatal_error(get_string('permission_error', 'block_helpmenow'));
    }
    $queue = helpmenow_queue::get_instance(null, $queue);

    # if there is a request already, add the student to the instructors meeting
    if ($existing_request = get_record('block_helpmenow_request', 'userid', $userid, 'queueid', $queue->id)) {
        $existing_request = helpmenow_request::get_instance(null, $existing_request);
        $meeting = helpmenow_meeting::get_instance($queue->helper[$USER->id]->meetingid);

        $existing_request->meetingid = $meeting->id;
        $existing_request->update();

        $meeting->add_user($existing_request->userid);
        $meeting->update();

        helpmenow_fatal_error('You may now close this window.', true, true);
    }
} else {
    $userid = $USER->id;

    # check privileges/availability
    $queue = helpmenow_queue::get_instance($queueid);
    if ($queue->get_privilege() !== HELPMENOW_QUEUE_HELPEE) {
        helpmenow_fatal_error(get_string('permission_error', 'block_helpmenow'));
    }
    if (!$queue->check_available()) {
        helpmenow_fatal_error(get_string('missing_helper', 'block_helpmenow'));
    }
    if ($existing_request = get_record('block_helpmenow_request', 'userid', $userid, 'queueid', $queue->id)) {
        $connect->param('requestid', $existing_request->id);
        redirect($connect->out());
    }
}
$class = helpmenow_request::get_class($queue->plugin);
    
# form
$form = $class::get_form();
if ($form->is_cancelled()) {                # cancelled
    # todo: close the window
    helpmenow_fatal_error('You may now close this window.', true, true);
} else if ($formdata = $form->get_data()) {     # submitted
    $formdata = stripslashes_recursive($formdata);  # stupid forms addslashes when we are already doing it
    $request = $class::process_form($formdata);

    # log
    helpmenow_log($USER->id, 'new_request', "requestid: {$request->id}");

    if ($USER->id !== $userid) {
        $request->meetingid = $queue->helper[$USER->id]->meetingid;
        $request->update();
        helpmenow_fatal_error('You may now close this window.', true, true);
    }

    # redirect to connect.php
    $connect->param('requestid', $request->id);
    redirect($connect->out());
}

# title, navbar, and a nice box
$title = get_string('new_request_heading', 'block_helpmenow');
$nav = array(array('name' => $title));
print_header($title, $title, build_navigation($nav));
print_box_start('generalbox centerpara');

# print form
$toform = array(
    'queueid' => $queue->id,
    'plugin' => $queue->plugin,
    'userid' => $userid
);
$form->set_data($toform);
$form->display();

print_box_end();

# footer
print_footer();

?>
