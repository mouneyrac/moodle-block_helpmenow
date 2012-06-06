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

# moodle stuff
require_once((dirname(dirname(dirname(__FILE__)))) . '/config.php');

# helpmenow library
require_once(dirname(__FILE__) . '/lib.php');

if (!isloggedin()) {
    header('HTTP/1.1 401 Unauthorized');
    die;
}

$error = false;

# get the request body
$request = json_decode(@file_get_contents('php://input'));

# process
$response = new stdClass;
switch ($request->function) {
case 'motd':
    if (!$queue = get_record('block_helpmenow_queue', 'userid', $USER->id)) {
        $error = HELPMENOW_ERROR_REQUEST;
        $response->error = 'No instructor queue exists for user';
        break;
    }
    $queue = helpmenow_queue::get_instance(null, $queue);
    $queue->description = $request->motd;
    if (!$queue->update()) {
        $error = HELPMENOW_ERROR_SERVER;
        $response->error = 'Could not update queue';
        break;
    }
    $response->motd = $queue->description;
    break;
default:
    $error = HELPMENOW_ERROR_REQUEST;
    $response->error = 'Unknown method';
}

# respond
if ($error) {
    switch ($error) {
    case HELPMENOW_ERROR_REQUEST:
        header('HTTP/1.1 400 Bad Request');
        break;
    default:
    case HELPMENOW_ERROR_SERVER:
        header('HTTP/1.1 500 Internal Server Error');
        break;
    }
    if (!$CFG->debugdisplay) {  # don't include error messages in the reponse if Moodle's set to not display error messages
        unset($response->error);
    }
} else {
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-cache');
    header('Expires: '. gmdate('D, d M Y H:i:s', 0) .' GMT');
    header('Pragma: no-cache');
}
echo json_encode($response);

?>
