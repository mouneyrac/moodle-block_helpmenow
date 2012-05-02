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

# check privileges/availability
$queue = helpmenow_queue::get_instance($queueid);
if ($queue->get_privilege() !== HELPMENOW_QUEUE_HELPEE) {
    helpmenow_fatal_error(get_string('permission_error', 'block_helpmenow'));
}
if (!$queue->check_available()) {
    helpmenow_fatal_error(get_string('missing_helper', 'block_helpmenow'));
}
$class = helpmenow_request::get_class($queue->plugin);
    
# form
$form = $class::get_form();
if ($form->is_cancelled()) {                # cancelled
    # todo: close the window
    helpmenow_fatal_error('You may now close this window.');
} else if ($formdata = $form->get_data()) {     # submitted
    $formdata = stripslashes_recursive($formdata);  # stupid forms addslashes when we are already doing it
    $request = $class::process_form($formdata);
    # redirect to connect.php
    $connect = new moodle_url("$CFG->wwwroot/blocks/helpmenow/connect.php");
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
    'queueid' => $queueid,
    'plugin' => $queue->plugin,
);
$form->set_data($toform);
$form->display();

print_box_end();

# footer
print_footer();

?>
