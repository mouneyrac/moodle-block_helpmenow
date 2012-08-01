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
 * This script creates wiziq sessions
 *
 * @package     block_helpmenow
 * @copyright   2012 VLACS
 * @author      David Zaharee <dzaharee@vlacs.org>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/config.php');
require_once(dirname(__FILE__) . '/lib.php');

require_login(0, false);

$session_id = required_param('sessionid', PARAM_INT);

# verify sesion
if (!helpmenow_verify_session($session_id)) {
    helpmenow_fatal_error('You do not have permission to view this page.');
}

# make sure user is instructor or helper
$user = get_record('block_helpmenow_user', 'userid', $USER->id);
$helper = get_records('block_helpmenow_helper', 'userid', $USER->id);
if (!$user and !$helper) {
    helpmenow_fatal_error('You do not have permission to view this page.');
}

if (!$user2plugin = helpmenow_user2plugin_wiziq::get_user2plugin()) {
    helpmenow_fatal_error('No user2plugin');
}

if (!$user2plugin->verify_active_meeting()) {
    $user2plugin->create_meeting();     # create meeting only if we don't have one
}

if (!helpmenow_wiziq_invite($session_id, $user2plugin->class_id)) {
    helpmenow_fatal_error('Could not insert message record');
}

redirect($user2plugin->presenter_url);

?>
