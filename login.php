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
 * This script logs helpers and instructors in and out
 *
 * @package     block_helpmenow
 * @copyright   2012 VLACS
 * @author      David Zaharee <dzaharee@vlacs.org>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once((dirname(dirname(dirname(__FILE__)))) . '/config.php');
require_once(dirname(__FILE__) . '/lib.php');
require_once(dirname(__FILE__) . '/plugins/gotomeeting/user2plugin.php');

# require login
require_login(0, false);

# get our parameters
$login = required_param('login', PARAM_INT);
$queueid = optional_param('queueid', 0, PARAM_INT);

# todo: log this

$message = '';
if ($queueid) {     # helper
    if (!$record = get_record('block_helpmenow_helper', 'queueid', $queueid, 'userid', $USER->id)) {
        helpmenow_fatal_error('You do not have permission to view this page.');
    }
    $message = "queueid: $queueid, ";
} else {    # instructor
    if (!$record = get_record('block_helpmenow_user', 'userid', $USER->id)) {
        helpmenow_fatal_error('You do not have permission to view this page.');
    }
}

if ($login) {
    helpmenow_log($USER->id, 'logged_in', $message); 
    $record->isloggedin = time();
} else {
    $duration = time() - $record->isloggedin;
    helpmenow_log($USER->id, 'logged_out', "{$message}duration: $duration seconds");
    $record->isloggedin = 0;
}

if ($queueid) {     # helper
    update_record('block_helpmenow_helper', $record);
} else {    # instructor
    update_record('block_helpmenow_user', addslashes_recursive($record));
}

# gotomeeting
if ($record = get_record('block_helpmenow_user2plugin', 'userid', $USER->id, 'plugin', 'gotomeeting')) {
    $user2plugin = new helpmenow_user2plugin_gotomeeting(null, $record);
} else {
    $user2plugin = false;
}
if ($login) {
    if (!$user2plugin or !isset($user2plugin->meetingid)) {
        $create_url = new moodle_url("$CFG->wwwroot/blocks/helpmenow/plugins/gotomeeting/create.php");
        redirect($create_url->out());
    }
} else {
    $sql = "
        SELECT 1
        WHERE EXISTS (
            SELECT 1
            FROM {$CFG->prefix}block_helpmenow_helper
            WHERE userid = $USER->id
            AND isloggedin <> 0
        )
        OR EXISTS (
            SELECT 1
            FROM {$CFG->prefix}block_helpmenow_user
            WHERE userid = $USER->id
            AND isloggedin <> 0
        )
    ";
    if (!record_exists_sql($sql)) {
        foreach (array('join_url', 'max_participants', 'unique_meetingid', 'meetingid') as $attribute) {
            unset($user2plugin->$attribute);
        }
        $user2plugin->update();
    }
}

helpmenow_fatal_error('You may now close this window', true, true);

?>
