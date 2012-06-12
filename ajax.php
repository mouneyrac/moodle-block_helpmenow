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
 * ajax server
 *
 * @package     block_helpmenow
 * @copyright   2012 VLACS
 * @author      David Zaharee <dzaharee@vlacs.org>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

# suppress error messages
error_reporting(0);
ob_start();

# moodle stuff
require_once((dirname(dirname(dirname(__FILE__)))) . '/config.php');

# helpmenow library
require_once(dirname(__FILE__) . '/lib.php');

if (!isloggedin()) {
    ob_end_clean();
    header('HTTP/1.1 401 Unauthorized');
    die;
}

require_login(0, false);

try {
    # get the request body
    $request = @json_decode(file_get_contents('php://input'));

    # get queue for motd and students functions
    if ($request->function === 'motd' or $request->function === 'instructor') {
        if (!$queue = get_record('block_helpmenow_queue', 'userid', $USER->id)) {
            throw new Exception('No instructor queue exists for user');
            break;
        }
        $queue = helpmenow_queue::get_instance(null, $queue);
        $queue->helper[$USER->id]->last_activity = time();
        $queue->helper[$USER->id]->update();
    }

    # process
    $response = new stdClass;
    switch ($request->function) {
    case 'motd':
        $queue->description = $request->motd;
        if (!$queue->update()) {
            throw new Exception('Could not update queue');
            break;
        }
        $response->motd = $queue->description;
        break;
    case 'instructor':
        $student_records = helpmenow_get_students();

        # login state
        $login = new moodle_url("$CFG->wwwroot/blocks/helpmenow/login.php");
        $login->param('queueid', $queue->id);
        if ($queue->helper[$USER->id]->isloggedin) {
            $login->param('login', 0);
            $login_status = get_string('in_office', 'block_helpmenow');
            $login_text = get_string('leave_office', 'block_helpmenow');
        } else {
            $login->param('login', 1);
            $login_status = get_string('out_office', 'block_helpmenow');
            $login_text = get_string('enter_office', 'block_helpmenow');
        }
        $response->login_html = "$login_status " . link_to_popup_window($login->out(), 'connect', $login_text, 400, 700, null, null, true);

        $response->border_style = 'none';

        # student list
        $response->students = array();
        foreach($student_records as $s) {
            $student = (object) array(
                'userid' => $s->id,
                'fullname' => fullname($s),
                'html' => '',
            );
            if ($queue->helper[$USER->id]->isloggedin) {
                $request = new moodle_url("$CFG->wwwroot/blocks/helpmenow/new_request.php");
                $request->param('userid', $student->userid);
                $student->html .= link_to_popup_window($request->out(), 'connect', $student->fullname, 400, 700, null, null, true) . "<br />";
            } else {
                $student->html .= $student->fullname;
            }
            if (isset($queue->request[$student->userid])) {
                $response->border_style = "5px solid #5CB063";
                $student->request = $queue->request[$s->id]->description;
                $student->html .= "<div style=\"margin-left:1em;\">" . $queue->request[$s->id]->description . "</div>";
            }
            $response->students[] = $student;
        }
        usort($response->students, function ($a, $b) {
            if (isset($a->request) === isset($b->request)) {
                return 0;
            }
            return isset($a->request) ? -1 : 1;
        });
        break;
    case 'queues':
        $queues = helpmenow_queue::get_queues_block();
        $response->queues = array();
        foreach ($queues as $q) {
            if ($q->get_privilege() !== HELPMENOW_QUEUE_HELPEE) {
                continue;
            }

            $queue = (object) array(
                'queueid' => $q->id,
                'name' => $q->name,
                'description' => $q->description,
                'html' => '',
            );

            if (isset($prev_type) and ($prev_type != $q->type)) {
                $queue->html .= "<hr />";
            }

            # if the user has a request, display it, otherwise give a link
            # to create one
            if (isset($q->request[$USER->id])) {
                $queue->html .= "<div style=\"border:5px solid #5CB063;\">";
                $connect = new moodle_url("$CFG->wwwroot/blocks/helpmenow/connect.php");
                $connect->param('requestid', $q->request[$USER->id]->id);
                if ($q->type === HELPMENOW_QUEUE_TYPE_INSTRUCTOR) {
                    $q->request[$USER->id]->last_refresh = time();
                    $q->request[$USER->id]->update();
                    $smalltext = $q->request[$USER->id]->description;
                } else {
                    $smalltext = get_string('pending', 'block_helpmenow');
                }
                $linktext = "<b>$q->name</b><br /><div style='text-align:center;font-size:small;'>$smalltext</div>";
                $queue->html .= link_to_popup_window($connect->out(), 'connect', $linktext, 400, 700, null, null, true);
            } else {
                $queue->html .= "<div>";
                if ($q->check_available()) {
                    $request = new moodle_url("$CFG->wwwroot/blocks/helpmenow/new_request.php");
                    $request->param('queueid', $q->id);
                    $linktext = "<b>$q->name</b><br />";
                    if ($q->type === HELPMENOW_QUEUE_TYPE_HELPDESK) {
                        $linktext .= "<div style='text-align:center;font-size:small;'>" . get_string('new_request', 'block_helpmenow') . "</div>";
                    }
                    $queue->html .= link_to_popup_window($request->out(), 'connect', $linktext, 400, 700, null, null, true);
                } else {
                    # todo: make this smarter (helpers leave message or configurable)
                    $queue->html.= "<b>$q->name</b><br /><div style='text-align:center;font-size:small;'>" . get_string('queue_na_short', 'block_helpmenow') . "</div>";
                }
            }
            if ($q->type === HELPMENOW_QUEUE_TYPE_HELPDESK or
                    ($q->type === HELPMENOW_QUEUE_TYPE_INSTRUCTOR and $q->check_available())) {
                $queue->html .= "<div style=\"margin-left:1em;\">" . $q->description . "</div>";
            }
            $queue->html .= "</div>";
            $response->queues[] = $queue;

            $prev_type = $q->type;
        }
        break;
    default:
        throw new Exception('Unknown method');
    }
} catch (Exception $e) {
    ob_end_clean();
    header('HTTP/1.1 400 Bad Request');
    if ($CFG->debugdisplay) {
        $response = new stdClass;
        $response->error = $e->getMessage();
        echo json_encode($response);
    }
    die;
}

ob_end_clean();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache');
header('Expires: '. gmdate('D, d M Y H:i:s', 0) .' GMT');
header('Pragma: no-cache');
echo json_encode($response);

?>
