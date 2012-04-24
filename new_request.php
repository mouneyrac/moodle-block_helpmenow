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

# todo: right now we're assuming every request is a queue

# moodle stuff
require_once((dirname(dirname(dirname(__FILE__)))) . '/config.php');

# helpmenow library & forms
require_once(dirname(__FILE__) . '/lib.php');
require_once(dirname(__FILE__) . '/form.php');

# require login
require_login(0, false);

# get our parameters
$params = (object) array(
    'queueid' => optional_param('queueid', 0, PARAM_INT),
    'userid' => optional_param('requested_userid', 0, PARAM_INT),
);

# check privileges/availability
# if ($params->queueid) {
    $queue = helpmenow_queue::get_instance($params->queueid);
    if ($queue->get_privilege() !== HELPMENOW_QUEUE_HELPEE) {
        # todo: print a permission failure message and close the window
    }
    if (!$queue->check_available()) {
        # todo: print a queue not available message and close
    }
# } else if ($params->requested_userid) {
#     $context = get_context_instance(CONTEXT_SYSTEM, SITEID);
#     if (!has_capability(HELPMENOW_CAP_REQUEST, $context)) {
#         # todo: print a permission failure message and close
#     }
# } else {
#     # todo: "what the heck are you doing?" failure message and close
# }

# form
$form = new helpmenow_request_form();
if ($form->is_cancelled()) {                # cancelled
    # todo: close the window
} else if ($data = $form->get_data()) {     # submitted
    # at this point we know we only have one of these, but we also want to
    # unset the other one:
    # if (!$params->queueid) {
    #     unset($data->queueid);
    # } else {
    #     unset($data->requested_userid);
    # }

    # make the new request
    $request = helpmenow_request::new_instance($queue->plugin);
    $request->queueid = $data->queueid;
    $request->description = $data->description;
    $request->userid = $USER->id;
    $request->last_refresh = time();
    $request->insert();

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
$form->set_data($params);
$form->display();

print_box_end();

# footer
print_footer();

?>
